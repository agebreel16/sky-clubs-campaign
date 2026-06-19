# 🔴 إصلاح #1: حل ازدواجية منطق الترقية/التهبيط (TD-001)

## المشكلة
منطق الترقية/التهبيط يُنفَّذ مرتين عند كل Import:
- مرة عبر `AgentObserver::checkAndApplyClubChanges()` (يُطلق من `$agent->update()`)
- مرة عبر `ProcessDataImport::processAgentRow()` (الـ Job نفسه)

**النتيجة:** مكافآت مزدوجة، سجلات تاريخية مكررة، تذاكر يانصيب مضاعفة.

## الحل المختار: **الخيار ب — إيقاف Observer داخل الـ Job**

### لماذا هذا الخيار؟
- الـ Job له صلاحية كاملة على transaction واحد متماسك
- Observer يبقى يعمل للتعديلات اليدوية (Admin من Filament)
- لا حاجة لإعادة كتابة المنطق بالكامل

---

## المهام المطلوبة من Claude Code

### المهمة 1: إيقاف Observer داخل ProcessDataImport

**الملف:** `app/Jobs/ProcessDataImport.php`

في دالة `handle()`، غلِّف منطق المعالجة بـ `Agent::withoutEvents()`:

```php
public function handle(): void
{
    $this->import->update(['status' => 'processing']);

    try {
        $rows = $this->readExcelFile();
        $clubs = Club::where('is_active', true)->orderBy('club_order')->get();
        $stats = ['promoted' => 0, 'demoted' => 0, 'warnings' => 0, 'recovered' => 0];

        DB::transaction(function () use ($rows, $clubs, &$stats) {
            // ✅ إيقاف Observer لمنع الازدواجية
            Agent::withoutEvents(function () use ($rows, $clubs, &$stats) {
                foreach ($rows as $row) {
                    $this->processAgentRow($row, $clubs, $stats);
                }
            });
        });

        $this->import->update([
            'status' => 'success',
            'processed_at' => now(),
            'stats' => $stats,
        ]);
    } catch (\Throwable $e) {
        $this->import->update([
            'status' => 'failed',
            'error_message' => $e->getMessage(),
        ]);
        throw $e;
    }
}
```

### المهمة 2: نقل منطق AuditLog يدوياً داخل الـ Job

لأن Observer متوقف، تأكد أن `processAgentRow()` تُنشئ `AuditLog` يدوياً:

```php
protected function processAgentRow(array $row, $clubs, array &$stats): void
{
    // ... منطق العثور على Agent والـ updates الموجود ...

    $oldValues = $agent->only(['current_total', 'transfer_count', 'new_line_count', 'current_club_id']);

    $agent->update([
        'current_total' => max($agent->pre_campaign_count, $row['current_total']),
        'transfer_count' => max(0, $row['transfer_count']),
        'new_line_count' => max(0, $row['new_line_count']),
    ]);

    // ✅ إنشاء AuditLog يدوياً (لأن Observer متوقف)
    AuditLog::create([
        'auditable_type' => Agent::class,
        'auditable_id' => $agent->agent_id,
        'event' => 'updated',
        'old_values' => $oldValues,
        'new_values' => $agent->only(['current_total', 'transfer_count', 'new_line_count']),
        'user_id' => $this->import->uploaded_by,
        'ip_address' => null,
        'source' => 'import',
        'import_id' => $this->import->import_id,
    ]);

    // ... باقي منطق Promotion/Demotion كما هو ...
}
```

### المهمة 3: حذف منطق checkAndApplyClubChanges المكرر من Observer

**الملف:** `app/Observers/AgentObserver.php`

دالة `checkAndApplyClubChanges()` تكرر منطق الـ Job. عند التعديل اليدوي من Admin Panel، يجب أن تستخدم نفس مسار `handleClubChange()` الموجود.

**استبدل دالة `updated()` بهذا:**

```php
public function updated(Agent $agent): void
{
    // 1. تسجيل التعديل في AuditLog (للتعديلات اليدوية)
    if (!app()->runningInConsole() || auth()->check()) {
        $this->logAudit($agent);
    }

    // 2. إذا تغيّر النادي مباشرة (نادر) — handleClubChange
    if ($agent->wasChanged('current_club_id')) {
        $this->handleClubChange($agent);
    }

    // 3. إذا بدأ عداد التهبيط
    if ($agent->wasChanged('demotion_timer_start') && $agent->demotion_timer_start !== null) {
        $this->handleDemotionTimerStart($agent);
    }

    // 4. ✅ إذا تغيّرت الأرقام (تعديل يدوي من Admin) — أعد التقييم
    $statsChanged = $agent->wasChanged([
        'current_total',
        'transfer_count',
        'new_line_count',
        'pre_campaign_count',
    ]);

    if ($statsChanged) {
        $this->reevaluateClubMembership($agent);
    }
}
```

### المهمة 4: استبدال checkAndApplyClubChanges بـ reevaluateClubMembership نظيفة

**الملف:** `app/Observers/AgentObserver.php`

**أضف الدالة التالية (واحذف القديمة):**

```php
/**
 * إعادة تقييم النادي عند تعديل يدوي للأرقام.
 * تستخدم نفس مسار handleClubChange لضمان اتساق البيانات.
 */
protected function reevaluateClubMembership(Agent $agent): void
{
    $agent->refresh();

    $clubs = Club::where('is_active', true)->orderBy('club_order')->get();
    $increase = $agent->current_total - $agent->pre_campaign_count;

    // أعلى نادٍ يستحقه الوكيل
    $eligibleClub = $clubs
        ->filter(fn($c) => $c->required_increase <= max(0, $increase))
        ->sortByDesc('club_order')
        ->first();

    $newClubId = $eligibleClub?->club_id;
    $currentClubId = $agent->current_club_id;
    $currentClub = $agent->currentClub;
    $currentOrder = $currentClub?->club_order ?? 0;
    $newOrder = $eligibleClub?->club_order ?? 0;

    // [PROMOTION]
    if ($newOrder > $currentOrder) {
        // ✅ استخدام Eloquent save() لإطلاق Observer مرة واحدة فقط
        // لكن نمنع recursion عبر withoutEvents للتعديل نفسه
        Agent::withoutEvents(function () use ($agent, $newClubId, $eligibleClub) {
            $isFirst = $this->checkIsFirstArrival($eligibleClub);

            $agent->update([
                'current_club_id' => $newClubId,
                'club_entry_date' => now(),
                'demotion_timer_start' => null,
                'is_first_arrival' => $isFirst,
            ]);
        });

        // إنشاء السجلات يدوياً (مرة واحدة فقط)
        $this->createPromotionRecords($agent, $currentClub, $eligibleClub);
        return;
    }

    // [DEMOTION TIMER LOGIC]
    if ($currentClub && $newOrder < $currentOrder) {
        $transferPct = $agent->transfer_percentage;

        // بدء العداد
        if ($transferPct < 60 && $agent->demotion_timer_start === null) {
            Agent::withoutEvents(function () use ($agent) {
                $agent->update(['demotion_timer_start' => now()]);
            });
            $this->handleDemotionTimerStart($agent);
            return;
        }

        // تنفيذ التهبيط
        if ($agent->demotion_timer_start !== null) {
            $daysSince = now()->diffInDays($agent->demotion_timer_start);
            if ($daysSince >= $currentClub->demotion_timer_days) {
                Agent::withoutEvents(function () use ($agent, $eligibleClub) {
                    $agent->update([
                        'current_club_id' => $eligibleClub?->club_id,
                        'demotion_timer_start' => null,
                    ]);
                });
                $this->createDemotionRecords($agent, $currentClub, $eligibleClub);
            }
        }
        return;
    }

    // [RECOVERY]
    if ($newOrder === $currentOrder && $agent->demotion_timer_start !== null) {
        Agent::withoutEvents(function () use ($agent) {
            $agent->update(['demotion_timer_start' => null]);
        });
        $this->createRecoveryRecord($agent, $currentClub);
    }
}

/**
 * تستخرج منطق إنشاء سجلات الترقية في مكان واحد.
 */
protected function createPromotionRecords(Agent $agent, ?Club $fromClub, Club $toClub): void
{
    HistoryLog::create([
        'agent_id' => $agent->agent_id,
        'event_type' => 'promotion',
        'from_club_id' => $fromClub?->club_id,
        'to_club_id' => $toClub->club_id,
        'event_date' => now(),
        'metadata' => [
            'campaign_increase' => $agent->campaign_increase,
            'transfer_percentage' => $agent->transfer_percentage,
        ],
    ]);

    Reward::create([
        'agent_id' => $agent->agent_id,
        'club_id' => $toClub->club_id,
        'reward_type' => 'club_entry',
        'amount' => $toClub->base_reward,
        'payment_status' => 'pending',
    ]);

    if ($agent->is_first_arrival) {
        Reward::create([
            'agent_id' => $agent->agent_id,
            'club_id' => $toClub->club_id,
            'reward_type' => 'first_arrival',
            'amount' => $toClub->first_arrival_reward,
            'payment_status' => 'pending',
        ]);
    }

    for ($i = 0; $i < $toClub->entry_opportunities; $i++) {
        Opportunity::create([
            'agent_id' => $agent->agent_id,
            'club_id' => $toClub->club_id,
            'opportunity_type' => 'entry',
        ]);
    }

    if ($agent->is_first_arrival) {
        Opportunity::create([
            'agent_id' => $agent->agent_id,
            'club_id' => $toClub->club_id,
            'opportunity_type' => 'first_arrival',
        ]);
    }

    AgentNotification::create([
        'agent_id' => $agent->agent_id,
        'club_id' => $toClub->club_id,
        'type' => 'promotion',
        'message' => "تهانينا! تم ترقيتك إلى {$toClub->name}",
    ]);
}

protected function createDemotionRecords(Agent $agent, Club $fromClub, ?Club $toClub): void
{
    HistoryLog::create([
        'agent_id' => $agent->agent_id,
        'event_type' => 'demotion',
        'from_club_id' => $fromClub->club_id,
        'to_club_id' => $toClub?->club_id,
        'event_date' => now(),
    ]);

    AgentNotification::create([
        'agent_id' => $agent->agent_id,
        'club_id' => $toClub?->club_id,
        'type' => 'demotion',
        'message' => "تم تهبيطك إلى " . ($toClub?->name ?? 'خارج النوادي'),
    ]);
}

protected function createRecoveryRecord(Agent $agent, Club $club): void
{
    HistoryLog::create([
        'agent_id' => $agent->agent_id,
        'event_type' => 'achievement',
        'from_club_id' => $club->club_id,
        'to_club_id' => $club->club_id,
        'event_date' => now(),
        'metadata' => ['note' => 'تم التعافي وإيقاف عداد التهبيط'],
    ]);

    AgentNotification::create([
        'agent_id' => $agent->agent_id,
        'club_id' => $club->club_id,
        'type' => 'recovery',
        'message' => 'أحسنت! تم إيقاف عداد التهبيط',
    ]);
}

protected function checkIsFirstArrival(Club $club): bool
{
    $arrivedCount = Agent::where('current_club_id', $club->club_id)
        ->where('is_first_arrival', true)
        ->count();
    return $arrivedCount < $club->first_arrival_slots;
}
```

### المهمة 5: مزامنة منطق Job مع Observer

**الملف:** `app/Jobs/ProcessDataImport.php`

داخل `processAgentRow()`، استبدل منطق إنشاء السجلات بنداء الـ helpers من Observer (أو انسخها لتجنب dependency):

```php
// بعد $agent->update([...stats]);
// بعد DailySnapshot::create([...]);

$agent->refresh();
$increase = $agent->current_total - $agent->pre_campaign_count;
$eligibleClub = $clubs
    ->filter(fn($c) => $c->required_increase <= max(0, $increase))
    ->sortByDesc('club_order')
    ->first();

$currentClub = $agent->currentClub;
$currentOrder = $currentClub?->club_order ?? 0;
$newOrder = $eligibleClub?->club_order ?? 0;

// [PROMOTION]
if ($newOrder > $currentOrder && $eligibleClub) {
    $isFirst = $this->checkIsFirstArrival($eligibleClub);

    Agent::where('agent_id', $agent->agent_id)->update([
        'current_club_id' => $eligibleClub->club_id,
        'club_entry_date' => now(),
        'demotion_timer_start' => null,
        'is_first_arrival' => $isFirst,
    ]);

    $agent->refresh();
    $this->createPromotionRecords($agent, $currentClub, $eligibleClub);
    $stats['promoted']++;
}

// ... باقي المنطق (Demotion / Recovery / Bonus) كما هو ...
```

---

## اختبار الحل

بعد تطبيق التعديلات، شغّل هذا الاختبار اليدوي:

1. **اختبار Import:**
   - ارفع Excel فيه وكيل واحد بترقية واضحة
   - تحقق من جدول `rewards`: يجب أن يكون **سجل واحد فقط** للترقية
   - تحقق من `history_logs`: يجب أن يكون **سجل واحد فقط** بـ `event_type = 'promotion'`

2. **اختبار التعديل اليدوي:**
   - افتح وكيلاً في `/admin/agents/{id}/edit`
   - عدّل `current_total` يدوياً ليؤهله للترقية
   - تحقق نفس الشيء: سجل واحد فقط لكل نوع

3. **اختبار AgentNotification:**
   - في الحالتين، تحقق من `agent_notifications`: يجب أن يكون هناك إشعار واحد للترقية

---

## التحقق من النجاح

```sql
-- يجب ألا يكون هناك مكافآت مكررة لنفس الوكيل في نفس اليوم
SELECT agent_id, club_id, reward_type, DATE(created_at), COUNT(*)
FROM rewards
GROUP BY agent_id, club_id, reward_type, DATE(created_at)
HAVING COUNT(*) > 1;
-- يجب أن يُرجع 0 صفوف

-- نفس الشيء للـ history_logs
SELECT agent_id, event_type, DATE(event_date), COUNT(*)
FROM history_logs
WHERE event_type = 'promotion'
GROUP BY agent_id, event_type, DATE(event_date)
HAVING COUNT(*) > 1;
-- يجب أن يُرجع 0 صفوف
```

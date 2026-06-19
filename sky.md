# Sky Clubs Campaign — Technical Blueprint
> **الإصدار:** 3.0 | **التاريخ:** 2026-06-20 | **الكاتب:** Lead Software Architect (AI)
> **Stack:** Laravel 13 · Filament 5.6 · PHP 8.4 · MySQL 8.0 · Livewire 3

---

## 1. Executive Summary

**Sky Clubs Campaign** هو نظام إدارة حملات مبيعات لشركة اتصالات. يتتبع أداء **الوكلاء** (مندوبو المبيعات) عبر ثلاثة أندية تصاعدية (Launch → Excellence → Peak). يُحكم النظام ترقية الوكلاء وتهبيطهم آلياً بناءً على بيانات تُستورد يومياً من Excel، مع منح مكافآت مالية وفرص يانصيب لمن يحقق الأهداف.

**المستخدمون:**
- **Admin Panel** (`/admin`): موظفو الشركة — إدارة كاملة للنظام.
- **Distributor Panel** (`/distributor`): الموزعون — قراءة فقط لوكلائهم + إدارة حسابهم.

**دورة الحياة الأساسية:**
```
رفع Excel يومي → ProcessDataImport Job → تحديث أرقام الوكيل
    → تقييم ترقية/تهبيط → إنشاء ClubChangeRequest(pending) ← لا تطبيق مباشر
    → Admin يراجع في ClubChangeRequestResource → يقبل أو يرفض
    → عند القبول: Agent::where()->update() + HistoryLog + Reward + Opportunity
    → AgentNotification (فقط عند قبول ترقية)
```

**تصنيف المخالفين:** وكلاء يُشتبه في تلاعبهم بالأرقام — يُصنَّفون يدوياً عند رفض ترقية مشبوهة (`is_violator=true`). المخالف يُتجاوز كلياً في الاستيراد اليومي، ويظهر فقط في تبويب "المخالفون" منفصلاً عن بقية الأندية.

**نطاق الحملة:** 2026-05-01 → 2027-04-30

---

## 2. Database Schema & Relationship Map

### 2.1 جدول جميع الجداول

| الجدول | PK | FK المهمة | الغرض |
|---|---|---|---|
| `clubs` | `club_id` (UUID) | — | إعداد الأندية الثلاثة — مركز التحكم الديناميكي |
| `agents` | `agent_id` (UUID) | `current_club_id → clubs`, `distributor_id → distributors` | كيان الوكيل + أرقامه الحالية. يحتوي `portal_token` (64 hex, nullable unique) لبوابة الوكيل. حقل `last_self_sync_at` (timestamp nullable) لآخر مزامنة ذاتية من البوابة. حقول المخالف: `is_violator`, `violator_since`, `violator_reason` |
| `club_change_requests` | `id` (UUID) | `agent_id → agents` (CASCADE), `import_id → data_imports` (SET NULL), `from_club_id / to_club_id → clubs` (SET NULL), `reviewed_by → users` (SET NULL) | طلبات تغيير النادي المعلّقة — الاستيراد يُنشئها، الأدمن يراجعها |
| `distributors` | `id` (UUID) | — | الموزعون (بيانات تسجيل الدخول + ربط الوكلاء) |
| `rewards` | `reward_id` (UUID) | `agent_id → agents`, `club_id → clubs` | المكافآت المالية (دخول النادي + الأوائل) |
| `opportunities` | `opportunity_id` (UUID) | `agent_id → agents`, `club_id → clubs` | تذاكر اليانصيب (entry / first_arrival / bonus) |
| `history_logs` | `log_id` (UUID) | `agent_id`, `from_club_id`, `to_club_id` | سجل أحداث الترقية/التهبيط/الرفض/المخالفة — **Immutable**. event_type ENUM: `promotion` `demotion` `warning` `achievement` `data_import` `rejection` `violation` |
| `data_imports` | `import_id` (UUID) | `uploaded_by → users`, `processed_by → users` | سجل عمليات استيراد البيانات — source_type: `excel` / `api` / `deals_api`. يحتوي: `progress` (0-100%)، `error_details` (JSON)، `api_url`، `api_token`. لا يوجد unique constraint على `data_date` — مصادر متعددة ممكنة في نفس اليوم |
| `agent_import_logs` | `id` (UUID) | `imported_by → users` | سجل عمليات استيراد وكلاء جدد (Excel أو API) — إنشاء مسجلات جديدة |
| `daily_snapshots` | `snapshot_id` (UUID) | `import_id`, `agent_id`, `club_id_at_date` | لقطة يومية لأرقام كل وكيل — تُستخدم أيضاً للمخطط الزمني في ViewAgent |
| `audit_logs` | `audit_id` (UUID) | `user_id → users` | سجل تعديلات يدوية من الـ Admin — **Immutable** |
| `notifications` | `notification_id` (UUID) | `agent_id → agents` (CASCADE), `club_id → clubs` (SET NULL) | إشعارات داخل النظام للوكلاء — اسم الجدول الفعلي في DB هو `notifications` وليس `agent_notifications` |
| `push_subscriptions` | `id` (UUID) | `pushable_id → agents` (uuidMorphs) | اشتراكات WebPush للمتصفحات — تُنشأ عند السماح بالإشعارات في البوابة |
| `users` | `id` (UUID) | — | موظفو الشركة (Admin Panel) |
| `roles` | `id` | — | تعريف أدوار المستخدمين |
| `permissions` | `id` | — | تعريف الصلاحيات |
| `role_has_permissions` | — | `role_id`, `permission_id` | ربط الدور بالصلاحيات (Pivot) |

### 2.1.1 Schema تفصيلي — جدول `notifications` (AgentNotification)

> ⚠️ **تنبيه:** اسم الجدول في DB هو `notifications` وليس `agent_notifications`. الـ Model هو `AgentNotification`.

| العمود | النوع | تفاصيل |
|---|---|---|
| `notification_id` | UUID PK | — |
| `agent_id` | UUID FK → agents (CASCADE) | — |
| `notification_type` | enum(6) | `milestone` \| `progress` \| `achievement` \| `promotion` \| `demotion` \| `warning` |
| `title` | string(200) | عنوان الإشعار (عربي/إنجليزي) |
| `body` | text | النص الكامل |
| `category` | enum | `in_club` \| `outside_clubs` |
| `stage` | string nullable | `on_starting_line` \| `in_progress` \| `on_doors` — فقط عند `outside_clubs`. يُستخدم لعرض تقدم الوكيل قبل دخول أي نادٍ |
| `current_count` | uint nullable | عدد خطوط الوكيل وقت توليد الإشعار (للعرض السياقي) |
| `required_count` | uint nullable | الخطوط المطلوبة للمرحلة التالية (لشريط التقدم) |
| `club_id` | UUID FK nullable (SET NULL) | النادي المرتبط |
| `is_read` | boolean (default: false) | — |
| `sent_at` | timestamp | وقت الإرسال (UTC) — يُستخدمه `NotificationBell::checkNew()` كنقطة مقارنة |
| `read_at` | timestamp nullable | وقت القراءة (UTC) — يُعيَّن بـ `markRead()` / `markAllRead()` |
| `deleted_at` | soft delete | الإشعارات المحذوفة تُخفى عن الوكيل لكن تبقى للـ audit |
| `created_at` | timestamp | — |

**Indexes:** `idx_notif_agent_id` · `idx_notif_type` · `idx_notif_sent_at` · `idx_notif_is_read` · `idx_notif_club_id` · `idx_notif_agent_is_read` (عدد الغير مقروء) · `idx_notif_agent_sent` (قائمة الإشعارات مرتبة)

### 2.1.2 Schema تفصيلي — جدول `club_change_requests`

> نظام الموافقة على تغييرات النادي (الإصدار 2.0 — 2026-06-18). الاستيراد يُنشئ السجل، الأدمن يُقرِّر.

| العمود | النوع | تفاصيل |
|---|---|---|
| `id` | UUID PK | HasUuids |
| `agent_id` | UUID FK → agents (CASCADE) | — |
| `import_id` | UUID FK nullable → data_imports (SET NULL) | الاستيراد الذي اكتشف التغيير |
| `from_club_id` | UUID FK nullable → clubs (SET NULL) | النادي الحالي وقت الاكتشاف (null = خارج) |
| `to_club_id` | UUID FK nullable → clubs (SET NULL) | النادي المقترح (null = خارج) |
| `change_type` | ENUM('promotion','demotion') | نوع التغيير |
| `agent_stats_snapshot` | JSON | لقطة الأرقام وقت الاكتشاف: `campaign_increase, transfer_count, new_line_count, current_total, transfer_pct` |
| `status` | ENUM('pending','approved','rejected','auto_cancelled') DEFAULT 'pending' | حالة الطلب |
| `reviewed_by` | UUID FK nullable → users (SET NULL) | الأدمن الذي قرر |
| `reviewed_at` | timestamp nullable | وقت القرار |
| `rejection_reason` | text nullable | سبب الرفض (يُكتب أيضاً في HistoryLog) |
| `created_at, updated_at` | timestamps | — |

**Indexes:** `idx_ccr_agent_status (agent_id, status)` · `idx_ccr_status` · `idx_ccr_created_at`

**دورة حياة الطلب:**
```
pending → (approved | rejected)
pending → auto_cancelled  ← إذا جاء import جديد والوضع تغيّر
```

### 2.2 Relationship Map

```
users ──────────────────────────────┐
   (uploaded_by)                    │
                                    ▼
distributors ──── agents ◄──── data_imports ────► daily_snapshots
    1:N         1:N (current)   (FK: import_id)
                    │
              current_club_id
                    │
                    ▼
                 clubs
                    │
        ┌──────────┬┴──────────┐
        ▼          ▼           ▼
     rewards  opportunities  history_logs
   (per club)  (per club)   (from/to club)
        │
   agent_notifications
```

**تفصيل العلاقات:**

| من | إلى | نوع | تفصيل |
|---|---|---|---|
| `Distributor` | `Agent` | HasMany | وكيل واحد لكل موزع |
| `Agent` | `Club` | BelongsTo | عبر `current_club_id` — نادٍ واحد أو NULL |
| `Agent` | `Reward` | HasMany | مكافأة واحدة على الأقل عند كل ترقية |
| `Agent` | `Opportunity` | HasMany | تذاكر يانصيب — تراكمية |
| `Agent` | `HistoryLog` | HasMany | كل حدث (ترقية/تهبيط/رفض/مخالفة) |
| `Agent` | `DailySnapshot` | HasMany | لقطة يومية لكل import |
| `Agent` | `ClubChangeRequest` | HasMany | طلبات تغيير النادي المرتبطة بالوكيل |
| `Club` | `Agent` | HasMany | عبر `current_club_id` |
| `DataImport` | `DailySnapshot` | HasMany | كل عملية import تولّد لقطات |
| `DataImport` | `ClubChangeRequest` | HasMany | كل import قد يُنشئ طلبات معلّقة |

### 2.3 Immutability Guards — أين تقع الحماية؟

| المستوى | الآلية | التفاصيل |
|---|---|---|
| **DB — CHECK Constraints** | `ALTER TABLE agents ADD CONSTRAINT chk_*` | 8 قيود على `agents`: baseline > 0، pre_campaign ≤ baseline، current_total ≥ pre_campaign، entry_date ≥ 2026-05-01 |
| **DB — CHECK Constraints** | على `clubs` | 12 قيد: club_order > 0، required_increase > 0، نسب بين 0.00-1.00… |
| **App — Model Comment** | `HistoryLog`, `AuditLog` | `// Immutable — no updates or deletes from application code` — تطبّق بالاتفاق لا بقيد DB |
| **App — Form Disabled** | `AgentResource::form()` | `baseline_count` → `->disabledOn('edit')` — لا يظهر في نموذج التعديل |
| **DB — FK onDelete** | `agents.current_club_id` | `SET NULL` عند حذف النادي — آمن |
| **DB — FK onDelete** | `agents.distributor_id` | `SET NULL` عند حذف الموزع — آمن |

> ⚠️ **ملاحظة:** لا توجد DB Triggers. الحماية من التعديل على `HistoryLog` و`AuditLog` هي **اتفاق على مستوى الكود فقط**، وليست مفروضة بـ DB constraint. يمكن لأي `Model::update()` مباشر أن يكسرها.

---

## 3. Architecture & Filament Config

### 3.1 هيكلية الـ Panels

```
app/Providers/Filament/
├── AdminPanelProvider.php    → /admin   (guard: web,    model: User)
└── DistributorPanelProvider.php → /distributor (guard: distributor, model: Distributor)
```

| الخاصية | Admin Panel | Distributor Panel |
|---|---|---|
| ID | `admin` | `distributor` |
| Path | `/admin` | `/distributor` |
| Auth Guard | `web` | `distributor` |
| Auth Model | `User` | `Distributor` |
| canAccessPanel | `is_active && email_verified && role ∈ [6 roles]` | `panel_id === 'distributor' && is_active` |
| Color | Sky (أزرق) | Teal (أخضر مخضر) |
| Resources Discovery | `app/Filament/Resources/` | `app/Filament/DistributorPanel/Resources/` |
| Widgets | 5 widgets | 3 widgets |
| Nav Groups | إدارة الحملة / المالية / البيانات / النظام | وكلائي / حسابي |

### 3.2 جدول Resources

| Resource | Model | Panel | صلاحيات | Special Logic |
|---|---|---|---|---|
| `AgentResource` | `Agent` | Admin | `AgentPolicy` (Authenticatable-typed + instanceof User guard) | `autoAssignClub()` عند الإنشاء/التعديل — يحسب النادي تلقائياً. `mutateFormDataBeforeCreate()` يحدد `current_club_id` |
| `ClubChangeRequestResource` | `ClubChangeRequest` | Admin | — | صفحة مراجعة طلبات التغيير. Actions: قبول (Query Builder + Reward + Opportunity + إشعار ترقية فقط) / رفض (rejection_reason + خيار mark_as_violator للترقيات). **BulkAction** "قبول الترقيات المحددة" (promotion+pending فقط — التهبيط يبقى يدوياً). canCreate()=false. Navigation badge يُظهر عدد المعلّق |
| `ClubResource` | `Club` | Admin | `ClubPolicy` | CRUD كامل — أي تعديل يؤثر فوراً على كل الحسابات |
| `DistributorResource` | `Distributor` | Admin | — | `ViewDistributor` يتضمن `Action::make('assign_agents')` لتعيين وكلاء بالجملة |
| `RewardResource` | `Reward` | Admin | `RewardPolicy` | تعديل `payment_status` (pending/paid/failed) |
| `DataImportResource` | `DataImport` | Admin | `DataImportPolicy` | `CreateDataImport::afterCreate()` → `ProcessDataImport::dispatch()` — تحديث إحصائيات وكلاء موجودين |
| `AgentImportResource` | `AgentImportLog` | Admin | — | `CreateAgentImport::afterCreate()` → `ProcessAgentImport::dispatch()` — إنشاء وكلاء جدد من Excel أو API |
| `OpportunityResource` | `Opportunity` | Admin | — | Read-only + إمكانية إلغاء الفرص |
| `HistoryLogResource` | `HistoryLog` | Admin | — | Read-only فقط |
| `AuditLogResource` | `AuditLog` | Admin | — | Read-only فقط |
| `DailySnapshotResource` | `DailySnapshot` | Admin | — | Read-only فقط |
| `NotificationResource` | `AgentNotification` | Admin | — | Read-only فقط |
| `UserResource` | `User` | Admin | `UserPolicy` | إدارة المستخدمين + الأدوار |
| `MyAgentsResource` | `Agent` | Distributor | **Override** `getAuthorizationResponse()` | `getEloquentQuery()` مقيد بـ `distributor_id`. قراءة فقط (create/edit/delete → deny) |

### 3.3 Admin Panel Widgets

| Widget | النوع | الوظيفة |
|---|---|---|
| `CampaignStatsOverview` | StatsOverview | 7 بطاقات: إجمالي الوكلاء · في الأندية · خارجها · المخالفون (`is_violator=true`) · طلبات معلّقة (`ClubChangeRequest pending`) · أيام الحملة المتبقية · نسبة الإنجاز الزمني |
| `ClubStatusWidget` | Custom (Blade) | حالة كل نادٍ: عدد الأعضاء (مستثنى المخالفون) · الطاقة · مؤشر اليانصيب |
| `TodayActivityWidget` | StatsOverview/Table | نشاط اليوم: ترقيات · تهبيطات · طلبات جديدة اليوم (`ClubChangeRequest pending` اليوم) |
| `PendingChangesWidget` | TableWidget | آخر 10 طلبات `ClubChangeRequest` معلّقة. `canView()` يُعيد false عندما لا توجد طلبات. Action "مراجعة الكل" → ClubChangeRequestResource |
| `ImportStatusWidget` | StatsOverview | آخر عمليات الاستيراد + حالتها |
| `AgentsStatsWidget` | Custom (Blade) | **Header Widget لـ ListAgents فقط** (`canView(): false` — مخفي عن Dashboard). 4 بطاقات: إجمالي الوكلاء · في الأندية · المخالفون (`is_violator=true`) · الأوائل |

### 3.4 Distributor Panel Widgets

| Widget | الوظيفة |
|---|---|
| `DistributorOverviewWidget` | 6 بطاقات إحصائية مقيّدة بـ `distributor_id`: إجمالي الوكلاء · داخل الأندية (+ خارجها) · المخالفون (`is_violator=true`) · الأوائل · إجمالي الزيادة (transfer+new_lines) · إجمالي المكافآت (₪) |
| `ClubBreakdownWidget` | توزيع وكلاء الموزع عبر الأندية (Blade custom view) — مستثنى المخالفون من عدد الأعضاء |

### 3.5 Distributor Panel Pages

| الصفحة | المسار | الوظيفة |
|---|---|---|
| `DistributorLogin` | `/distributor/login` | صفحة دخول مخصصة للـ Distributor Panel |
| `MyProfile` | `/distributor/my-profile` | تعديل اسم/هاتف/إيميل/منطقة وكلمة المرور |
| `Dashboard` | `/distributor` | لوحة التحكم مع 3 widgets |
| `ListMyAgents` | `/distributor/my-agents` | جدول وكلاء الموزع فقط |
| `ViewMyAgent` | `/distributor/my-agents/{id}` | تفاصيل كاملة read-only (infolist) للوكيل |

---

## 4. Core Business Logic (القلب النابض)

### 4.1 ProcessDataImport — الـ Job

**الملف:** `app/Jobs/ProcessDataImport.php`
**التشغيل:** Queue (async) — يُطلق من `CreateDataImport::afterCreate()` أو يدوياً من `DataImportResource`.
**Timeout:** 300 ثانية · **Tries:** 1 (لا retry — يمنع تكرار AuditLog) · **failed():** يُحدِّث `import.status = 'failed'` كـ safety net.

**مسار التنفيذ (الإصدار 2.0 — Approval Flow):**

```
handle()
  ├─ import.status = 'processing'
  ├─ readExcelFile() / readDealsApi()
  │    ├─ [excel] يقرأ xlsx — columns: [agent_id, current_total, transfer_count, new_line_count]
  │    └─ [api]   ->where('is_violator', false)  ← يستثني المخالفين من استعلام الوكلاء
  │
  ├─ SET SESSION innodb_lock_wait_timeout = 5  ← يمنع تجميد الـ import إذا كان Admin يعدّل وكيلاً بالتزامن
  └─ DB::transaction()
       └─ Agent::withoutEvents(closure)   ← Observer مُعطَّل كلياً
            └─ foreach row → processAgentRow($row, $clubs, &$stats)

processAgentRow():
  ├─ [1] إيجاد الوكيل بالـ UUID أو الاسم — إذا لم يُوجد: not_found++ والتالي
  │
  ├─ [2] ← جديد ← فحص المخالفة
  │    if ($agent->is_violator) { $stats['skipped_violators']++; return; }
  │    ← تجاوز كامل — لا تحديث أرقام، لا DailySnapshot، لا طلبات جديدة ←
  │
  ├─ [3] تحديث الأرقام:
  │    $agent->update([current_total, transfer_count, new_line_count, pre_campaign_count])
  │    AuditLog::create(...)     ← يدوي بديلاً عن Observer (withoutEvents)
  │    DailySnapshot::create(...)
  │    $agent->refresh()
  │
  ├─ [4] حساب التأهيل:
  │    $campaignIncrease = transfer_count + new_line_count
  │    $bestClub = أعلى نادٍ يستوفي (required_increase + required_transfer_count)
  │    $newOrder = $bestClub?->club_order ?? 0
  │    $currentOrder = $agent->club?->club_order ?? 0
  │
  ├─ [5] جلب الطلب المعلّق الموجود:
  │    $existingPending = ClubChangeRequest::where('agent_id', ...)->where('status','pending')->first()
  │
  ├─ [6] Snapshot اللحظي:
  │    $snapshot = [campaign_increase, transfer_count, new_line_count, current_total, transfer_pct]
  │
  ├─ [7a] PROMOTION ($newOrder > $currentOrder):
  │    ← لا تغيير لـ current_club_id ← فقط طلب معلّق:
  │    إذا $existingPending:
  │      - نفس النادي → تحديث snapshot فقط
  │      - نادٍ مختلف → auto_cancel القديم + إنشاء جديد
  │    إلا: ClubChangeRequest::create([..., change_type='promotion', status='pending'])
  │    $stats['pending_promotions']++
  │
  ├─ [7b] DEMOTION ($currentClub && $newOrder < $currentOrder):
  │    نفس المنطق — change_type='demotion'
  │    $stats['pending_demotions']++
  │
  ├─ [7c] لا تغيير (وضع مناسب أو خارج الأندية):
  │    إذا يوجد طلب معلّق لم يعد ملائماً → auto_cancel
  │
  └─ [8] BONUS OPPORTUNITIES — Peak Club فقط (يعمل بناءً على $currentClub الفعلي):
       floor(new_line_count / bonus_per_numbers) − existing → Opportunity::create(bonus)

  ├─ import.status = 'success' + {processed, pending_promotions, pending_demotions, skipped_violators, not_found, ...}
  └─ [on exception] import.status = 'failed' + error_message
```

> **⚠️ قاعدة حرجة:** `ProcessDataImport` لا يُغيِّر `current_club_id` أبداً. لا `$pendingNotifies`. لا إشعارات من الـ Job. التغيير يُطبَّق فقط عند موافقة الأدمن في `ClubChangeRequestResource`.

**قيود التحقق في processAgentRow:**
- `pre_campaign_count` يتناقص فقط (min مع القيمة السابقة)
- `current_total = max(pre_campaign_count, imported_value)` — لا يقل عن pre_campaign_count
- `transfer_count` و `new_line_count` = max(0, imported_value) — لا تكون سالبة
- العملية كلها في `DB::transaction()` — إما كل شيء أو لا شيء

### 4.2 AgentObserver — المراقب

**الملف:** `app/Observers/AgentObserver.php`
**التسجيل:** `AppServiceProvider::boot()` → `Agent::observe(AgentObserver::class)`
**يُطلق:** عند كل `Agent::save()` أو `$agent->update([...])` (Eloquent method — NOT query builder)
**لا يُطلق:** داخل `ProcessDataImport` — مُعطَّل عبر `Agent::withoutEvents()`. يعمل فقط عند التعديل اليدوي من Admin Panel.

```
updated(Agent $agent)
  ├─ [إذا تغيّر current_club_id] → handleClubChange()
  │    ├─ يحدد eventType: promotion / demotion
  │    ├─ HistoryLog::create(...)
  │    ├─ [promotion] Reward::create(base) + Reward::create(first_arrival إذا is_first)
  │    ├─ [promotion] Opportunity::create(entry × N) + Opportunity::create(first_arrival)
  │    └─ AgentNotification::create(promotion)  ← إشعار فقط للترقية
  │
  ├─ [إذا تغيّر is_violator من true إلى false] → handleViolatorRemoval()
  │    ├─ Agent::where()->update([violator_since=null, violator_reason=null])
  │    ├─ HistoryLog::create(achievement, reason='إلغاء تصنيف المخالفة')
  │    └─ ← لا إشعار للوكيل ←
  │
  ├─ logAudit()
  │    └─ AuditLog::create(changes, old_values, new_values, user_id, ip)
  │
  └─ [إذا تغيّرت: current_total / transfer_count / new_line_count / pre_campaign_count]
       └─ checkAndApplyPromotion()  ← مسار التعديل اليدوي فقط — لا يُستدعى أثناء Import
            ├─ $agent->refresh()
            └─ [PROMOTION فقط] Agent::where()->update(club, entry_date, is_first_arrival)
                 ← Query Builder — يتجاوز Observer لمنع infinite loop ←
                 ← HistoryLog + Reward + Opportunity + AgentNotification (WebPush) ←
```

> **قواعد Observer (الإصدار 2.0):**
> - `handleClubChange()` تُطلق عند تعديل يدوي عبر Eloquent (لا تُطلق من Import الذي يستخدم withoutEvents)
> - `checkAndApplyPromotion()` تتعامل مع الترقية اليدوية فقط — **لا تهبيط، لا عداد**
> - لا إشعار للوكيل عند التهبيط — لا في Observer ولا في Import
> - الموافقة على طلبات ClubChangeRequest تستخدم Query Builder مباشرة (لا Eloquent) لتجنب تشغيل Observer مرة ثانية

### 4.3 ProcessAgentImport — استيراد وكلاء جدد

**الملف:** `app/Jobs/ProcessAgentImport.php`
**التشغيل:** Queue (async) — يُطلق من `CreateAgentImport::afterCreate()` أو زر "إعادة المحاولة".
**الغرض:** إنشاء مسجلات وكلاء جدد من نظام خارجي — **مختلف تماماً عن ProcessDataImport** (الذي يُحدِّث إحصائيات وكلاء موجودين).

**القواعد الجوهرية:**
- `current_club_id = NULL` دائماً — الـ DataImport اليومي يُعيّن النادي طبيعياً
- لا تُنشأ Rewards أو Opportunities — استيراد تاريخي فقط
- UUID الوكيل مأخوذ من المصدر الخارجي كما هو
- وكيل بنفس UUID موجود مسبقاً → يُتخطى (skip)
- صف بيانات خاطئة → يُسجَّل في `error_details` ويُتخطى، بقية الصفوف تكمل

```
handle()
  ├─ log.status = 'processing'
  ├─ readData()
  │    ├─ [excel] IOFactory → التحقق من columns [agent_id, agent_name, baseline_count, pre_campaign_count, current_total]
  │    └─ [api]   Http::withToken(token)->get(url) → يدعم: array / {"data":[]} / {"agents":[]}
  │
  └─ Agent::withoutEvents(fn)   ← Observer مُعطَّل كلياً
       └─ foreach row:
            ├─ Agent::find(uuid) موجود؟ → skipped++
            ├─ validate DB constraints → rejected++ + error_details
            ├─ Agent::forceCreate([uuid, name, baseline, pre_campaign, current, ...])
            │    current_club_id = null, is_first_arrival = false, entry_date = null
            └─ AuditLog::create(...)  ← يدوي

  ├─ log.status = 'success' + stats
  └─ [exception] log.status = 'failed'
```

**حقول Excel:** مطلوبة: `agent_id`, `agent_name`, `baseline_count`, `pre_campaign_count`, `current_total` — اختيارية: `transfer_count`, `new_line_count`, `distributor_id`, `phone`

### 4.4 Club Auto-Assignment Logic

**شروط التأهيل للنادي — يجب تحقيق الشرطَين معاً:**
1. `campaign_increase (transfer + new_lines) >= required_increase` — الحد الأدنى للإجمالي
2. `transfer_count >= required_transfer_count` — الحد الأدنى للتحويل (= 60% من `required_increase` لكل الأندية)

**AgentResource::autoAssignClub()** (تُستدعى live عند تغيير أي من الحقول: `pre_campaign_count`، `current_total`، `transfer_count`، `new_line_count` — **فقط في نموذج الإنشاء `create`**):
```php
$increase      = transfer_count + new_line_count;
$transferCount = transfer_count;
$club = Club::where('is_active', true)
    ->where('required_increase', '<=', max(0, $increase))
    ->where('required_transfer_count', '<=', max(0, $transferCount))  // ✅ الشرط الثاني
    ->orderByDesc('club_order')
    ->first(); // أعلى نادٍ مؤهَّل بكلا الشرطين
$set('current_club_id', $club?->club_id);
```

**CreateAgent::mutateFormDataBeforeCreate()**: نفس المنطق قبل الحفظ — ضمان اتساق.

### 4.4 RBAC & Policies

**أدوار المستخدمين (Admin Panel):**

| الدور | القراءة | الإنشاء | التعديل | الحذف |
|---|---|---|---|---|
| `super_admin` | ✅ | ✅ | ✅ | ✅ |
| `admin` | ✅ | ✅ | ✅ | ✅ |
| `supervisor` | ✅ | ✅ | ✅ | ❌ |
| `data_entry` | ✅ | ✅ | ✅ | ❌ |
| `viewer` | ✅ | ❌ | ❌ | ❌ |
| `finance_officer` | ✅ | ❌ | ❌ | ❌ |

**AgentPolicy** (يطبّق على Admin Panel فقط):

| الدالة | الشرط |
|---|---|
| `viewAny` / `view` | `user.is_active` |
| `create` / `update` | role ∈ {super_admin, admin, supervisor, data_entry} |
| `delete` / `restore` | role ∈ {super_admin, admin} |
| `forceDelete` | دائماً `false` |

**تجاوز الـ Policy في Distributor Panel:**

```php
// MyAgentsResource::getAuthorizationResponse() — يتجاوز Gate بالكامل
match($action) {
    'viewAny' => Response::allow(),
    'view'    => $record->distributor_id === auth('distributor')->id() ? allow : deny,
    default   => Response::deny(),  // create, update, delete كلها مرفوضة
}
```

**طبقات الأمان الثلاث في Distributor Panel:**
1. `authGuard('distributor')` → فصل كامل عن `web` guard
2. `getEloquentQuery()->where('distributor_id', auth('distributor')->id())` → تصفية SQL
3. `getAuthorizationResponse()` override → رفض صريح لأي عملية غير view

---

## 5. Project File Map & Functionality

### 5.1 Models (`app/Models/`)

| الملف | يُستدعى متى؟ | الدور في دورة الحياة |
|---|---|---|
| `Agent.php` | في كل مكان | الكيان المحوري. Accessors: `campaign_increase`, `transfer_percentage`, `baseline_loss`. حقول المخالف: `is_violator`, `violator_since`, `violator_reason`. حقل `last_self_sync_at` (datetime cast, nullable) — يُعيَّن بعد كل self-sync. علاقة `clubChangeRequests()` |
| `Club.php` | عند Import + تقييم الترقية | يحمل إعداد الأندية. `getLotteryUnlocked` يحسب هل يُفتح اليانصيب |
| `ClubChangeRequest.php` | في Import + ClubChangeRequestResource | طلبات تغيير النادي المعلّقة. Scopes: `scopePending()`. العلاقات: `agent()`, `fromClub()`, `toClub()`, `import()`, `reviewer()` |
| `Distributor.php` | تسجيل دخول Distributor Panel | `canAccessPanel()` يفرض عزل الـ Panel |
| `Reward.php` | بعد كل ترقية | يُنشأ تلقائياً، يُعدَّل `payment_status` يدوياً |
| `Opportunity.php` | بعد كل ترقية | `timestamps = false` — لا وقت تحديث |
| `HistoryLog.php` | بعد كل حدث | `timestamps = false`. تعليق Immutable — لا تُحذف/تُعدَّل |
| `DataImport.php` | عند رفع Excel | يتتبع حالة الاستيراد + إحصائياته |
| `DailySnapshot.php` | داخل Job لكل صف | `timestamps = false`. أرشيف تاريخي للأرقام |
| `AuditLog.php` | داخل Observer | `timestamps = false`. تعليق Immutable |
| `AgentImportLog.php` | عند طلب استيراد وكلاء | يتتبع حالة + إحصائيات + أخطاء per-row لكل عملية استيراد وكلاء جدد |
| `AgentNotification.php` | في Observer + Job | إشعارات داخلية للوكلاء |
| `User.php` | Admin Panel | `hasPermission()` يستعلم `role_has_permissions` |
| `AppSetting.php` | Console Commands + SyncDailyNumbers | جدول إعدادات النظام — `AppSetting::get('key', 'default')` static helper. يخزّن: `numbers_api_url`, `numbers_api_token`, `daily_sync_time` |

### 5.2 Jobs (`app/Jobs/`)

| الملف | كيف يُطلق؟ | التفاصيل |
|---|---|---|
| `ProcessDataImport.php` | `CreateDataImport::afterCreate()` + يدوياً من `NumbersApiSettings` / `DealsApiSettings` / `SyncAgentDeals` | ShouldQueue · timeout=300s · tries=1 · failed() safety net · تحديث إحصائيات وكلاء موجودين |
| `ProcessAgentImport.php` | `CreateAgentImport::afterCreate()` + زر retry + `AgentsApiSettings::syncNow()` | ShouldQueue · timeout=300s · tries=1 · failed() safety net · إنشاء وكلاء جدد من Excel أو API |
| `ProcessDistributorSync.php` | `DistributorsApiSettings::syncNow()` | ShouldQueue · timeout=120s · يجلب الموزعين من API خارجي (Bearer Token) → يُنشئ Distributor جديد لكل UUID غير موجود (skip إن موجود حتى مع SoftDelete). يدعم: `{"distributors":[]}` / `{"data":[]}` / `[...]`. يحفظ `last_distributor_sync` + `last_distributor_sync_result` في AppSetting |
| `ProcessAgentSelfSync.php` | `AgentSyncing::runSync()` مباشرة — **لا queue** | **Synchronous** — يُشغَّل عبر `$job->handle()` مباشرة (لا `dispatch()`). timeout=30s · tries=1. POST إلى Deals API لوكيل واحد (`GetSubCustomerDeals`) → تحديث أرقامه + `DailySnapshot::where()->update()` (لا insert — `import_id NOT NULL`) + إنشاء `ClubChangeRequest(pending)` عند تغيّر الاستحقاق. يُحدِّث `last_self_sync_at` دائماً عند الانتهاء. يتجاوز الوكيل المخالف كلياً. `import_id = null` في أي `ClubChangeRequest` ينشئه (nullable ✅) |

### 5.3 Observers (`app/Observers/`)

| الملف | متى يُطلق؟ | ماذا يفعل؟ |
|---|---|---|
| `AgentObserver.php` | `Agent::save()` / `$model->update()` | يراقب: club change (Eloquent فقط) → `handleClubChange()`. إلغاء مخالفة → `handleViolatorRemoval()`. بيانات أرقام → `checkAndApplyPromotion()` (ترقية يدوية فقط، لا تهبيط). **لا عداد تهبيط** |
| `RewardObserver.php` | `Reward::save()` عند تغيير `payment_status` | عند التحويل إلى `paid` → إشعار داخلي + WebPush للوكيل. عند التحويل إلى `failed` → إشعار تحذير + WebPush + SMS |

### 5.4 Policies (`app/Policies/`)

| الملف | يحمي؟ |
|---|---|
| `AgentPolicy.php` | `Agent` — Admin only (type-hint: `User`) |
| `ClubPolicy.php` | `Club` — Admin only |
| `RewardPolicy.php` | `Reward` — Admin only |
| `DataImportPolicy.php` | `DataImport` — Admin only |
| `UserPolicy.php` | `User` — Admin only |

### 5.5 Resources — Admin Panel (`app/Filament/Resources/`)

| المجلد/الملف | يُفتح متى؟ | الوظيفة |
|---|---|---|
| `AgentResource.php` | `/admin/agents` | جدول + نموذج إنشاء/تعديل. الـ `autoAssignClub` يعمل live. عمود `is_violator` (أيقونة تحذير). تبويب "المخالفون" في ListAgents (is_violator=true فقط). فلتر `is_violator` بدلاً من فلتر العداد |
| `AgentResource/Pages/CreateAgent.php` | `/admin/agents/create` | `mutateFormDataBeforeCreate` يحدد النادي تلقائياً |
| `AgentResource/Pages/ViewAgent.php` | `/admin/agents/{id}` | infolist مفصّل: بيانات + إحصائيات + مخطط زمني (يومي/أسبوعي/شهري). Section "تحذير مخالف" إذا `is_violator=true` |
| `AgentResource/Pages/EditAgent.php` | `/admin/agents/{id}/edit` | `baseline_count` مقفل. Section "حالة المخالفة" (مرئي فقط إذا `is_violator=true`) — Toggle لإلغاء المخالفة |
| `AgentResource/RelationManagers/` | داخل ViewAgent | 3 RelationManagers: HistoryLogs + Opportunities + Rewards |
| `ClubChangeRequestResource.php` | `/admin/club-change-requests` | مراجعة طلبات التغيير — قبول/رفض. Badge يعرض عدد المعلّق في شريط التنقل |
| `ClubChangeRequestResource/Pages/ListClubChangeRequests.php` | `/admin/club-change-requests` | قائمة الطلبات، default filter: pending |
| `ClubResource.php` | `/admin/clubs` | إعداد الأندية — أي تعديل يؤثر فوراً على كل الحسابات |
| `DataImportResource.php` | `/admin/data-imports` | رفع Excel → يُطلق Job. زر "إعادة المعالجة" للفاشل — تحديث إحصائيات وكلاء موجودين |
| `AgentImportResource.php` | `/admin/agent-imports` | استيراد وكلاء جدد من Excel أو API → يُطلق ProcessAgentImport Job |
| `DistributorResource.php` | `/admin/distributors` | إدارة الموزعين + نموذج كلمة مرور |
| `DistributorResource/Pages/ViewDistributor.php` | `/admin/distributors/{id}` | Action "تعيين وكلاء" بالجملة عبر multi-select modal |
| `DistributorResource/RelationManagers/AgentsRelationManager.php` | داخل ViewDistributor | `AssociateAction` + `DissociateAction` لربط/فك وكلاء |
| `RewardResource.php` | `/admin/rewards` | تعديل `payment_status`: pending → paid → failed |
| `HistoryLogResource.php` | `/admin/history-logs` | قراءة فقط |
| `AuditLogResource.php` | `/admin/audit-logs` | قراءة فقط |
| `DailySnapshotResource.php` | `/admin/daily-snapshots` | قراءة فقط |
| `OpportunityResource.php` | `/admin/opportunities` | قراءة + إمكانية إلغاء |
| `NotificationResource.php` | `/admin/notifications` | قراءة فقط |
| `UserResource.php` | `/admin/users` | إدارة الموظفين |

### 5.5.1 Filament Pages — Admin Panel (`app/Filament/Pages/`)

كل صفحات "إعدادات API" تنتمي للـ Navigation Group "إعدادات API" وتشترك في نفس النمط: حفظ الإعدادات في `AppSetting` + زر "الآن" يُطلق Job + عرض آخر sync.

| الصفحة | Sort | المسار | الوظيفة |
|---|---|---|---|
| `AdminLogin` | — | `/admin/login` | صفحة دخول مخصصة للـ Admin Panel |
| `AgentsApiSettings` | 91 | `/admin/agents-api-settings` | يحفظ `agents_api_url` + `agents_api_token`. زر "استيراد الآن" يُنشئ `AgentImportLog` ويُطلق `ProcessAgentImport`. يعرض آخر استيراد (`created_count`, `skipped_count`) |
| `DistributorsApiSettings` | 92 | `/admin/distributors-api-settings` | يحفظ `distributors_api_url` + `distributors_api_token`. زر "مزامنة الآن" يُطلق `ProcessDistributorSync`. يعرض `last_distributor_sync` + `last_distributor_sync_result` من AppSetting |
| `NumbersApiSettings` | 93 | `/admin/numbers-api-settings` | يحفظ `numbers_api_url` + `numbers_api_token` + `daily_sync_time`. زر "مزامنة الآن" يُنشئ `DataImport(source_type='api')` ويُطلق `ProcessDataImport`. يعرض آخر import يومي |
| `DealsApiSettings` | 95 | `/admin/deals-api-settings` | يحفظ `deals_api_url` + `deals_api_username` + `deals_api_password` + `deals_campaign_start_date` + `deals_sync_enabled` + `deals_sync_interval_minutes`. زر "مزامنة الآن" (`syncNow()`) + "اختبار الاتصال" (`testConnection()`). Hero Banner ديناميكي يُظهر حالة المزامنة (مفعّلة/معطّلة + الفترة الزمنية). Navigation Label: "مزامنة أرقام الوكلاء" |

---

### 5.6 Resources — Distributor Panel (`app/Filament/DistributorPanel/`)

| الملف | الوظيفة |
|---|---|
| `Resources/MyAgentsResource.php` | جدول وكلاء الموزع. تجاوز كامل للـ Policy عبر `getAuthorizationResponse()` |
| `Resources/MyAgentsResource/Pages/ListMyAgents.php` | قائمة الوكلاء |
| `Resources/MyAgentsResource/Pages/ViewMyAgent.php` | infolist مفصّل: بيانات + نادٍ + KPIs + مكافآت (RepeatableEntry) + فرص. Section "تنبيه" إذا `is_violator=true` |
| `Pages/MyProfile.php` | تعديل بيانات الموزع الشخصية + كلمة المرور. يستخدم `Schema` (Filament 5) |

### 5.7 Custom Blade Views (`resources/views/filament/`)

**Admin Pages:**
| الملف | الوظيفة |
|---|---|
| `pages/admin-login.blade.php` | View صفحة دخول Admin Panel المخصصة |
| `pages/agents-api-settings.blade.php` | View صفحة إعدادات API الوكلاء |
| `pages/distributors-api-settings.blade.php` | View صفحة إعدادات API الموزعين |
| `pages/numbers-api-settings.blade.php` | View صفحة إعدادات API الأرقام اليومية |
| `pages/deals-api-settings.blade.php` | View صفحة مزامنة أرقام الوكلاء (Hero Banner + إعدادات + SyncStatusBadge) |
| `pages/distributor-login.blade.php` | View صفحة دخول Distributor Panel المخصصة |

**Reusable Components:**
| الملف | الوظيفة |
|---|---|
| `components/excel-fields-notice.blade.php` | تنبيه بحقول Excel المطلوبة في `AgentImportResource` |
| `components/agent-import-excel-notice.blade.php` | تنبيه تفصيلي لاستيراد الوكلاء من Excel |
| `components/agent-import-api-notice.blade.php` | تنبيه تفصيلي لاستيراد الوكلاء من API |
| `components/api-field.blade.php` | حقل URL لـ API (مُعاد الاستخدام عبر صفحات الإعدادات) |
| `components/api-token-field.blade.php` | حقل Bearer Token (مُعاد الاستخدام) |

**Widgets:**
| الملف | الوظيفة |
|---|---|
| `widgets/club-status-widget.blade.php` | حالة الأندية (عدد الأعضاء، الطاقة، مؤشر اليانصيب) |
| `widgets/import-status-widget.blade.php` | آخر عمليات الاستيراد + حالتها |
| `widgets/agents-stats-widget.blade.php` | View لـ AgentsStatsWidget (header widget في ListAgents) |
| `widgets/campaign-stats-overview.blade.php` | View مخصص لـ CampaignStatsOverview |

**Partials (Layout & Theming):**
| الملف | الوظيفة |
|---|---|
| `layouts/auth-login.blade.php` | Layout مخصص لصفحات الدخول (Admin + Distributor) |
| `partials/admin-theme.blade.php` | ألوان وثيم Admin Panel المخصص (يُضمَّن في الـ header) |
| `partials/sidebar-logo-mark.blade.php` | شعار Sidebar المخصص |
| `partials/topbar-portal-badge.blade.php` | موضع SyncStatusBadge في الـ topbar للـ Admin Panel |
| `partials/sync-badge.blade.php` | Partial خام لتضمين SyncStatusBadge |
| ~~`resources/agent-resource/pages/demotion-report.blade.php`~~ | **محذوف** — DemotionReport page حُذفت |

**Agent Resource:**
| الملف | الوظيفة |
|---|---|
| `agent/daily-progress-chart.blade.php` | مخطط خطي (Chart.js 4.4.9 CDN) لزيادة الحملة اليومية في ViewAgent. أزرار يومي/أسبوعي/شهري client-side بـ vanilla JS عبر `@script`. ثلاثة datasets من `daily_snapshots` تُحسب في PHP |
| `agent/portal-link-modal.blade.php` | Modal مشاركة رابط بوابة الوكيل (Alpine.js زر نسخ) |

**Distributor Panel:**
| الملف | الوظيفة |
|---|---|
| `distributor/widgets/club-breakdown-widget.blade.php` | توزيع وكلاء الموزع عبر الأندية |
| `distributor/pages/my-profile.blade.php` | صفحة الملف الشخصي للموزع |

**معادلة المخطط الزمني:**
```
campaign_increase (per day) = snapshot.transfer_count + snapshot.new_line_count
```
- الـ dataset الأسبوعي = آخر snapshot من كل أسبوع (`groupBy('Y-W')`)
- الـ dataset الشهري = آخر snapshot من كل شهر (`groupBy('Y-m')`)
- القسم مخفي تلقائياً إذا لم تكن هناك snapshots للوكيل

### 5.8 Console Commands (`app/Console/Commands/`)

جميع الأوامر مسجَّلة في `routes/console.php` مع `->withoutOverlapping()->runInBackground()`.

| الأمر | التوقيت | الوظيفة |
|---|---|---|
| `SyncDailyNumbers` | يومياً عند `AppSetting::get('daily_sync_time', '02:00')` | يقرأ `numbers_api_url` + `numbers_api_token` من `AppSetting`، يُنشئ `DataImport(source_type='api')`، ويُطلق `ProcessDataImport::dispatch()` |
| `CreateMonthlyMaintenanceOpportunities` | اليوم الأول من كل شهر عند 01:00 | لكل وكيل لديه `current_club_id`: يتحقق idempotency → ينشئ `Opportunity(type='maintenance')` + `AgentNotification(achievement)` + WebPush |
| `SyncAgentDeals` | كل دقيقة `->everyMinute()->withoutOverlapping()->runInBackground()` | يتحقق من `deals_sync_enabled` + الـ interval → إذا حان الوقت يُنشئ `DataImport(source_type='deals_api')` ويُطلق `ProcessDataImport::dispatch()`. **ثنائي المسار:** الـ Scheduler يُشغّله عند تفعيله — وـ Alpine.js في `SyncStatusBadge` يُشغّله client-side عند انتهاء العداد (حتى بدون Scheduler) |

**إعدادات `AppSetting` الكاملة:**

| المفتاح | القيمة الافتراضية | يُستخدم في |
|---|---|---|
| `numbers_api_url` | — | `NumbersApiSettings`, `SyncDailyNumbers`, `ProcessDataImport` |
| `numbers_api_token` | — | `NumbersApiSettings`, `SyncDailyNumbers`, `ProcessDataImport` |
| `daily_sync_time` | `'02:00'` | `NumbersApiSettings`, `routes/console.php` |
| `agents_api_url` | — | `AgentsApiSettings`, `ProcessAgentImport` |
| `agents_api_token` | — | `AgentsApiSettings`, `ProcessAgentImport` |
| `distributors_api_url` | — | `DistributorsApiSettings`, `ProcessDistributorSync` |
| `distributors_api_token` | — | `DistributorsApiSettings`, `ProcessDistributorSync` |
| `last_distributor_sync` | — | يكتبه `ProcessDistributorSync`، يقرأه `DistributorsApiSettings::getLastSync()` |
| `last_distributor_sync_result` | — | يكتبه `ProcessDistributorSync` (ملخص نصي: "تم إضافة X موزع جديد، تجاوز Y") |
| `deals_api_url` | — | `DealsApiSettings`, `SyncAgentDeals`, `SyncStatusBadge::autoSync()`, `ProcessAgentSelfSync` |
| `deals_api_username` | — | `DealsApiSettings`, `SyncAgentDeals`, `ProcessDataImport`, `ProcessAgentSelfSync` |
| `deals_api_password` | — | `DealsApiSettings`, `ProcessDataImport`, `ProcessAgentSelfSync` |
| `deals_campaign_start_date` | `'2026-05-01'` | `DealsApiSettings`, `ProcessDataImport`, `ProcessAgentSelfSync` |
| `deals_sync_enabled` | `'0'` | `SyncAgentDeals`, `SyncStatusBadge` |
| `deals_sync_interval_minutes` | `120` (دقيقة)، حد أدنى `5` | `SyncAgentDeals`, `SyncStatusBadge` |

---

## 6. User Journey & Event Triggers

### 6.1 سيناريو: رفع بيانات Excel يومية

```
Admin يفتح /admin/data-imports/create
  → يملأ: data_date, source_type=excel, يرفع الملف
  → CreateDataImport::mutateFormDataBeforeCreate()
       ├─ status = 'pending'
       └─ uploaded_by = Auth::id()
  → DataImport::create() → redirect إلى view
  → CreateDataImport::afterCreate()
       └─ ProcessDataImport::dispatch($import)  [Queue]
  
  [في Queue Worker]
  → ProcessDataImport::handle()
       → import.status = 'processing'
       → readExcelFile() → مصفوفة صفوف
       → DB::transaction():
            لكل وكيل:
              إذا is_violator=true → skipped_violators++ والتالي (بدون أي تحديث)
              $agent->update(stats) [withoutEvents — لا Observer]
              AuditLog::create(...)
              DailySnapshot::create()
              $agent->refresh()
              تقييم الأهلية → إنشاء/تحديث/إلغاء ClubChangeRequest(pending)
              ← لا تغيير لـ current_club_id ←
              ← لا إشعارات ←
       → import.status = 'success' + {pending_promotions, pending_demotions, skipped_violators, ...}

Admin يفتح /admin/club-change-requests (PendingChangesWidget يُنبِّه)
  → يُراجع كل طلب → يقبل أو يرفض (انظر §6.6)
```

### 6.2 سيناريو: تعديل يدوي لأرقام الوكيل (Admin)

```
Admin يفتح /admin/agents/{id}/edit
  → يعدّل current_total أو transfer_count
  → $agent->save() [Eloquent]
       → AgentObserver::updated()
            ├─ logAudit() → AuditLog::create()
            └─ [wasChanged current_total/transfer_count]
                 → checkAndApplyPromotion()
                      ├─ $agent->refresh()
                      └─ [PROMOTION فقط] Agent::where()->update(club) [Query Builder]
                           ← HistoryLog + Reward + Opportunity + AgentNotification + WebPush ←
```

> **ملاحظة (الإصدار 2.0):** التعديل اليدوي من Admin يُطبَّق فوراً (لا يمر بـ ClubChangeRequest). هذا مقصود — Admin هو المُقرِّر، والـ Approval Flow مخصص للاستيراد الآلي فقط. **لا عداد تهبيط** — التهبيط اليدوي يتم عبر `ClubChangeRequestResource` أو تعديل `current_club_id` مباشرة في EditAgent.

### 6.3 سيناريو: استيراد وكلاء جدد (Excel أو API)

```
Admin يفتح /admin/agent-imports/create
  → يختار source_type: excel أو api
  → [excel] يرفع ملف xlsx | [api] يُدخل URL + Token
  → CreateAgentImport::mutateFormDataBeforeCreate()
       ├─ status = 'pending'
       └─ imported_by = Auth::id()
  → AgentImportLog::create() → redirect إلى view
  → CreateAgentImport::afterCreate()
       └─ ProcessAgentImport::dispatch($log)  [Queue]

  [في Queue Worker]
  → ProcessAgentImport::handle()
       → log.status = 'processing'
       → readData() → مصفوفة صفوف (من Excel أو API)
       → Agent::withoutEvents():
            لكل وكيل:
              إذا UUID موجود → skipped++
              تحقق من constraints → rejected++ إذا فشل
              Agent::forceCreate([uuid_خارجي, ...]) ← current_club_id = NULL
              AuditLog::create(...)
       → log.status = 'success' + {created, skipped, rejected}
```

> **ملاحظة:** الوكلاء المستوردون يبدأون بـ `current_club_id = NULL`. عند أول `ProcessDataImport` يومي، يُقيَّم كل وكيل ويُعيَّن للنادي المناسب طبيعياً.

### 6.4 سيناريو: دفع مكافأة

```
Admin يفتح /admin/rewards
  → يجد مكافأة بحالة 'pending'
  → يضغط تعديل → payment_status = 'paid' + paid_date
  → Reward::save() → RewardObserver::updated()
       ├─ [paid]   AgentNotification::create(achievement) + WebPush للوكيل
       └─ [failed] AgentNotification::create(warning) + WebPush + SMS للوكيل
  → التغيير يظهر في ViewMyAgent للموزع عبر RepeatableEntry
```

### 6.4 سيناريو: تهبيط وكيل (الإصدار 2.0)

```
[عند كل import يومي]
ProcessDataImport::processAgentRow():
  ├─ إذا لم يتأهل لنادٍ (newOrder < currentOrder):
  │    └─ ClubChangeRequest::create([change_type='demotion', status='pending'])
  │         ← لا تغيير لـ current_club_id ← لا HistoryLog ← لا إشعار ←
  │
  └─ إذا تحسَّنت الأرقام وعاد للتأهل → auto_cancel الطلب القديم

[Admin يراجع الطلب في /admin/club-change-requests]
  ├─ قبول التهبيط:
  │    Agent::where()->update(current_club_id = to_club_id)  ← Query Builder
  │    HistoryLog(demotion)
  │    ← لا إشعار للوكيل ←
  └─ رفض التهبيط:
       ClubChangeRequest::update(status='rejected', rejection_reason)
       HistoryLog(rejection)
       ← لا إشعار للوكيل ←
```

### 6.6 سيناريو: مراجعة طلب ترقية + تصنيف مخالف (جديد)

```
[بعد Import]
PendingChangesWidget يظهر في Dashboard الأدمن + Badge عدد المعلّق في القائمة

Admin يفتح /admin/club-change-requests:
  → يجد طلب ترقية (مشبوه)

الحالة 1: قبول الترقية:
  → approveRequest($record):
       ← $isFirst = Agent::where('current_club_id', $toClub->club_id)->count() < $toClub->first_arrival_count
       Agent::where()->update([current_club_id, entry_date, is_first_arrival])  ← Query Builder
       HistoryLog(promotion)
       Reward::create(base أو first_arrival)
       Opportunity::create(entry × N)  [+ first_arrival إذا isFirst]
       AgentNotification::create(promotion) + $agent->notify(WebPush)  ← إشعار الوكيل
       ClubChangeRequest::update(status='approved', reviewed_by, reviewed_at)

الحالة 2: رفض الترقية (بدون مخالفة):
  → rejectRequest($record, ['rejection_reason' => '...', 'mark_as_violator' => false]):
       ClubChangeRequest::update(status='rejected', rejection_reason, ...)
       HistoryLog(rejection)
       ← لا إشعار للوكيل ←

الحالة 3: رفض الترقية + تصنيف مخالف:
  → rejectRequest($record, ['rejection_reason' => '...', 'mark_as_violator' => true]):
       ClubChangeRequest::update(status='rejected', ...)
       HistoryLog(rejection)
       Agent::where()->update([is_violator=true, violator_since=now(), violator_reason=...])  ← Query Builder
       HistoryLog(violation)
       ← لا إشعار للوكيل ←
       [في Import التالي] الوكيل يُتجاوز كلياً (is_violator=true)

الحالة 4: إلغاء تصنيف المخالف (من EditAgent):
  → Admin يفتح /admin/agents/{id}/edit
  → يُعطّل Toggle 'is_violator'
  → $agent->save() [Eloquent] → AgentObserver::handleViolatorRemoval()
       Agent::where()->update([violator_since=null, violator_reason=null])
       HistoryLog(achievement, reason='إلغاء تصنيف المخالفة')
       ← لا إشعار للوكيل ←
       [في Import التالي] الوكيل يُعالَج بشكل طبيعي
```

### 6.5 سيناريو: تسجيل دخول موزع

```
/distributor/login
  → Filament::auth() = Guard 'distributor'
  → Distributor::canAccessPanel() checks: panel_id='distributor' && is_active=true
  → Dashboard: DistributorOverviewWidget (stats مقيّدة)
  → /distributor/my-agents: MyAgentsResource
       → getEloquentQuery()->where('distributor_id', auth('distributor')->id())
       → getAuthorizationResponse() override → لا تصل لـ Gate لا AgentPolicy
```

---

## 7. Bottlenecks & Risk Assessment

### 7.1 المخاطر التقنية

| المخاطرة | الخطورة | التفاصيل |
|---|---|---|
| ~~**AgentNotification Missing Path**~~ | ✅ مُصلَح | `checkAndApplyPromotion()` يُنشئ `AgentNotification` + WebPush عند الترقية اليدوية. `ClubChangeRequestResource::approveRequest()` يُنشئ `AgentNotification` + WebPush عند قبول ترقية فقط. لا إشعار عند التهبيط أو الرفض أو المخالفة. |
| ~~**Missing DB-level Immutability**~~ | ✅ مُصلَح (2026-06-19) | `HistoryLog` و`AuditLog` يرميان `LogicException` عند استدعاء `performUpdate()` أو `performDeleteOnModel()` — حماية حقيقية لا بتعليق فقط. |
| **Queue Worker Dependency** | 🟡 متوسطة (مُحسَّن) | إذا توقف Queue Worker، كل imports ستبقى pending بصمت. لا alerting مدمج. **تحسين (2026-06-19):** `tries=1` + `failed()` تضمن تحديث `import.status = 'failed'` حتى عند الـ catastrophic failure قبل الـ try-catch. الحل الكامل (Slack/email alerting) لا يزال مفتوحاً. |
| **Transaction Locking** | 🟡 متوسطة (مُخففة) | `DB::transaction()` في `ProcessDataImport` يُقفل صفوف `agents`. **تخفيف (2026-06-19):** `SET SESSION innodb_lock_wait_timeout = 5` يجعل تعارض الـ Admin يفشل في 5 ثوانٍ بدلاً من التجمّد 50 ثانية. الحل الجذري (per-row micro-transactions) مُؤجَّل. |
| ~~**AgentPolicy Type Mismatch**~~ | ✅ مُصلَح (2026-06-19) | `AgentPolicy` محوّلة لـ `Authenticatable` type-hint مع `instanceof User` guard في كل method — أي Panel جديد يصل للـ Policy بـ non-User يحصل على `false` بدلاً من crash. |
| ~~**N+1 في ClubBreakdownWidget**~~ | ✅ مُصلَح (2026-06-19) | من 13 query → 6 queries: query واحدة aggregate للأعداد + first_arrivals (per distributor)، query واحدة للأعداد الكلية، 3 queries لـ latestMember (1 لكل نادٍ). |
| **Approval Bottleneck** | 🟢 مُعالَج جزئياً (2026-06-19) | **BulkAction** "قبول الترقيات المحددة" مُضاف — يعالج عدة ترقيات معلّقة دفعة واحدة مع try-catch per-record. التهبيط يبقى يدوياً عمداً (قرار حرج). |
| **Self-Sync API Latency** | 🟡 متوسطة | `ProcessAgentSelfSync` يستدعي Deals API synchronously (timeout=15s). إذا كان الـ API بطيئاً أو معطلاً → الوكيل ينتظر حتى 15 ثانية على صفحة المزامنة. الـ `updateSyncTime()` تُنفَّذ دائماً عند أي نتيجة → لا crash. لا fallback خارجي (retry أو alerting) حالياً. |
| **`$agent->refresh()` في الـ Job** | 🟠 منخفضة | `$agent->refresh()` بعد `update()` يُعيد تحميل البيانات من DB — ضروري لأن `update()` لا يحدّث الـ instance تلقائياً. Observer لا يعمل هنا (withoutEvents) لذا لا تعارض، لكن أي `Agent::where()->update()` موازٍ من Admin في نفس اللحظة قد يُسبّب تقييماً على بيانات غير متسقة. مُخفَّف بـ lock_wait_timeout=5. |
| ~~**Bonus Opportunities لا تتحقق من Idempotency كافية**~~ | ✅ مُغلَق | قراءة الكود أثبتت أن الـ idempotency موجودة فعلاً: `$existing = count()` → `for ($i = $existing; $i < $bonusCount; $i++)` — لا تكرار حتى عند تشغيل Import مرتين. |

---

## 8. Technical Debt

### ~~TD-002~~: ✅ مُصلَح — AgentPolicy محوّلة لـ `Authenticatable` (2026-06-19)

**الوضع السابق:** `AgentPolicy::view(User $user, ...)` — type-hint صريح.
**الإصلاح:** كل methods محوّلة لـ `Authenticatable $user` مع `instanceof User` guard → أي Panel جديد يصل للـ Policy بـ non-User يحصل على `false` بدلاً من PHP fatal error.

---

### ~~TD-003~~: ✅ مُصلَح — Immutability بقيد حقيقي (2026-06-19)

**الوضع السابق:** `HistoryLog` و`AuditLog` محمية بتعليق `// Immutable` فقط.
**الإصلاح:** `performUpdate()` و`performDeleteOnModel()` في كلا الـ Model يرميان `LogicException` — حماية على مستوى Eloquent لا يمكن تجاوزها بالخطأ.

---

### ~~TD-004~~: ✅ مُصلَح — Campaign Increase

**الوضع السابق:** `campaign_increase = current_total - pre_campaign_count` — كان يعاقب الوكيل على خسارة خطوط قديمة.

**الوضع الحالي:** `campaign_increase = transfer_count + new_line_count` — يعكس الخطوط المضافة فقط خلال الحملة.

مُصلَح في 12 ملف (2026-05-05):
- `Agent::getCampaignIncreaseAttribute()` · `DailySnapshot::getCampaignIncreaseAttribute()`
- `ProcessDataImport` · `AgentObserver` · `AgentResource` · `CreateAgent` · `ViewAgent` · `EditAgent`
- `MyAgentsResource` · `ViewMyAgent` · `AgentsRelationManager` · `DistributorOverviewWidget`

---

### ~~TD-006~~: ✅ مُصلَح — إشعارات مفقودة في ProcessDataImport (2026-05-23)

**المشاكل السابقة:**
1. `'message'` بدلاً من `'body'` في `AgentNotification::create()` للتحذير — body كانت null دائماً.
2. لا `AgentNotification` عند الترقية من الاستيراد اليومي.
3. لا `AgentNotification` عند تنفيذ التهبيط (في `ProcessDataImport` و`AgentObserver::checkAndApplyClubChanges()`).
4. لا `AgentNotification` عند التعافي من `ProcessDataImport`.

**الإصلاح:** `processAgentRow()` يستقبل الآن `array &$pendingNotifies`. كل حدث (ترقية/تهبيط/تعافي/تحذير):
- يُنشئ `AgentNotification::create()` **داخل** الـ transaction (آمن).
- يُضيف entry لـ `$pendingNotifies[]` → `$agent->notify()` يُرسَل **بعد** `DB::transaction()` (تجنباً لـ HTTP داخل DB lock).

**الإصلاح في AgentObserver:** إضافة `AgentNotification::create()` + `$agent->notify(sendSms: true)` في `checkAndApplyClubChanges()` عند تنفيذ التهبيط الفعلي.

---

### ~~TD-007~~: ✅ مُصلَح — NotificationBell لا يتزامن مع markAllRead() (2026-05-23)

**المشكلة:** `markAllRead()` في `AgentNotifications` component يُحدّث DB لكن `NotificationBell` (component مستقل) لا يعلم بالتغيير حتى الـ poll التالي (5 ثوانٍ).

**الإصلاح:** `AgentNotifications::markAllRead()` يُطلق `$this->dispatch('notifications-marked-all-read')`. `NotificationBell` يستقبله عبر `#[On('notifications-marked-all-read')]` ويُصفِّر `$unreadCount` فوراً.

---

### ~~TD-008~~: ✅ مُصلَح — AudioContext تسرب عند تعدد الإشعارات (2026-05-23)

**المشكلة:** `playPortalNotifSound()` كانت تُنشئ `new AudioContext()` في كل استدعاء. Chrome يسمح بـ 6 contexts كحد أقصى — الصوت يتوقف بعد الإشعار السادس.

**الإصلاح:** `_portalAudioCtx` singleton على مستوى الـ page في `agent-portal.blade.php`.

---

### ~~TD-010~~: ✅ مُغلَق — عداد التهبيط بالكامل (2026-06-18)

**الوضع:** `demotion_timer_start` (agents) و`demotion_timer_days` (clubs) حُذفا بالكامل.
**الحل:** نظام Approval Flow — الاستيراد يُنشئ `ClubChangeRequest(pending)` بدلاً من إدارة عداد. TD-009 أصبح غير ذي صلة (العداد نفسه مُزال).

---

### ~~TD-009~~: ✅ مُصلَح — عداد التهبيط لا يبدأ عند نسبة تحويل < 60% (2026-05-23)

**المشكلة:** وكيل في نادي الانطلاق بـ transfer=10 و new_lines=15 (الإجمالي=25 = required_increase). حساب `bestClub` كان يستخدم `required_increase` فقط → الوكيل "يتأهل" للنادي → `newOrder == currentOrder` → لا يدخل شرط عداد التهبيط → لا إشعار.

**السبب الجذري:** `required_transfer_count` (= 60% من required_increase) موجود في جدول `clubs` لكن كان مهمَلاً تماماً في التأهيل.

**الإصلاح:** إضافة الشرط الثاني في 4 أماكن:
- `AgentObserver::checkAndApplyClubChanges()` — foreach loop
- `ProcessDataImport::processAgentRow()` — foreach loop
- `AgentResource::autoAssignClub()` — DB query
- `CreateAgent::mutateFormDataBeforeCreate()` — DB query

---

### ~~TD-005~~: ✅ مُغلَق — AtRiskAgentsWidget حُذف

**الوضع السابق:** `AtRiskAgentsWidget` كان يستخدم `DATE_ADD` raw query على `demotion_timer_start`.

**الحل (2026-06-18):** الـ Widget حُذف بالكامل من Admin Panel وDistributor Panel. عداد التهبيط نُزع من المشروع كلياً. بديله: `PendingChangesWidget` الذي يعرض `ClubChangeRequest(pending)` بدون أي raw queries.

---

## 9. Club Configuration Reference

> **الإصدار 2.0:** حُذف عمود `demotion_days` — لا يوجد عداد تهبيط في المشروع.

| النادي | club_order | required_increase | required_transfer_count | base_reward | first_arrival_reward | first_arrival_slots | seat_capacity | grand_prize | entry_opportunities | bonus |
|---|---|---|---|---|---|---|---|---|---|---|
| Launch Club | 1 | 25 | 15 | 300 ₪ | 600 ₪ | 10 | 90 | 15,000 ₪ | 1 | ❌ |
| Excellence Club | 2 | 50 | 30 | 700 ₪ | 1,500 ₪ | 5 | 55 | 35,000 ₪ | 2 | ❌ |
| Peak Club | 3 | 100 | 60 | 1,000 ₪ | 2,000 ₪ | 5 | 45 | 70,000 ₪ | 3 | ✅ (1/20 lines) |

---

## 10. Quick Reference Cheatsheet

```
أريد تعديل شرط الترقية؟              → ClubResource: required_increase + required_transfer_count
أريد مراجعة طلبات الترقية/التهبيط؟   → /admin/club-change-requests (ClubChangeRequestResource)
أريد رفع بيانات يومية؟               → DataImportResource: create + رفع Excel (→ يُنشئ ClubChangeRequests)
أريد استيراد وكلاء جدد؟              → AgentImportResource: create + Excel أو API
أريد مراجعة سبب ترقية وكيل؟          → AgentResource > ViewAgent > HistoryLogs tab
أريد تعيين وكيل لموزع؟               → DistributorResource > ViewDistributor > "تعيين وكلاء"
أريد دفع مكافأة؟                     → RewardResource: تعديل payment_status = paid
أريد إضافة موزع جديد؟                → DistributorResource: create
أريد مراجعة تعديل يدوي؟              → AuditLogResource
أريد تصنيف وكيل كمخالف؟              → ClubChangeRequestResource > رفض ترقية + تفعيل "تصنيف مخالف"
أريد إلغاء تصنيف مخالف؟              → AgentResource > EditAgent > Toggle "is_violator" (off)
أريد إضافة resource في Distributor Panel؟
    → اذكر getAuthorizationResponse() override في الكلاس لتجنب TD-002
```

---

---

## 11. API Specifications (للشركة الخارجية)

> هذه المواصفات تُرسَل للشركة التي تمتلك النظام المصدر لتجهيز الـ API endpoints وتوفير مفاتيح الوصول.

### نوع الاتصال

```
بروتوكول:   HTTPS
مصادقة:     Bearer Token (يُدخَل في النظام عند إنشاء كل import)
طريقة:      GET فقط
صيغة الرد:  JSON (Content-Type: application/json)
ترميز:      UTF-8
```

---

### API 1 — البيانات اليومية للوكلاء

**الاستخدام:** يُستدعى يومياً لتحديث أرقام الوكلاء الموجودين في النظام.

**الطلب:**
```
GET {API_URL}
Authorization: Bearer {TOKEN}
```

**صيغة الرد — أي من الثلاثة مقبولة:**
```json
{ "data": [ ...agents ] }
{ "agents": [ ...agents ] }
[ ...agents ]
```

**بنية كل وكيل:**
```json
{
  "agent_id":           "550e8400-e29b-41d4-a716-446655440000",
  "current_total":      130,
  "transfer_count":     20,
  "new_line_count":     30,
  "pre_campaign_count": 80
}
```

| الحقل | النوع | الوصف | إلزامي؟ |
|---|---|---|---|
| `agent_id` | UUID string | معرّف الوكيل — يطابق UUID الموجود في النظام | ✅ |
| `current_total` | integer ≥ 0 | إجمالي الخطوط النشطة (قديم + حملة) | ✅ |
| `transfer_count` | integer ≥ 0 | خطوط التحويل المضافة منذ بداية الحملة (تراكمي) | ✅ |
| `new_line_count` | integer ≥ 0 | الخطوط الجديدة المضافة منذ بداية الحملة (تراكمي) | ✅ |
| `pre_campaign_count` | integer ≥ 0 | الخطوط القديمة المتبقية نشطة حتى اليوم | ✅ |

**قواعد البيانات:**
```
current_total = pre_campaign_count + transfer_count + new_line_count
pre_campaign_count ≤ baseline_count  (لا يرتفع عن قيمة بداية الحملة)
transfer_count, new_line_count: تراكمي — لا يتناقصان إلا عند فسخ خطوط
```

---

### API 2 — استيراد وكلاء جدد

**الاستخدام:** يُستدعى مرة واحدة أو عند إضافة دُفعة وكلاء جدد.

**الطلب:**
```
GET {API_URL}
Authorization: Bearer {TOKEN}
```

**بنية كل وكيل:**
```json
{
  "agent_id":           "550e8400-e29b-41d4-a716-446655440001",
  "agent_name":         "أحمد محمد",
  "phone":              "0501234567",
  "baseline_count":     100,
  "pre_campaign_count": 100,
  "current_total":      100,
  "transfer_count":     0,
  "new_line_count":     0,
  "distributor_id":     "660e8400-e29b-41d4-a716-446655440002"
}
```

| الحقل | النوع | الوصف | إلزامي؟ |
|---|---|---|---|
| `agent_id` | UUID string | المعرّف الفريد من النظام الخارجي | ✅ |
| `agent_name` | string | اسم الوكيل | ✅ |
| `baseline_count` | integer > 0 | إجمالي الخطوط يوم 2026-05-01 — **لا يتغير أبداً** | ✅ |
| `pre_campaign_count` | integer ≥ 0 | نفس baseline_count عند الاستيراد الأول | ✅ |
| `current_total` | integer ≥ 0 | إجمالي الخطوط وقت الاستيراد | ✅ |
| `transfer_count` | integer ≥ 0 | خطوط التحويل وقت الاستيراد | اختياري |
| `new_line_count` | integer ≥ 0 | الخطوط الجديدة وقت الاستيراد | اختياري |
| `distributor_id` | UUID string | معرّف الموزع المسؤول | اختياري |
| `phone` | string ≤ 20 | رقم جوال الوكيل | اختياري |

**قواعد:**
```
baseline_count = pre_campaign_count  (عند الاستيراد الأول)
current_total ≥ pre_campaign_count
pre_campaign_count ≤ baseline_count
```

---

### أكواد الاستجابة

| الكود | المعنى |
|---|---|
| 200 | ✅ نجح الطلب — البيانات في الـ body |
| 401 | ❌ Token غير صالح |
| 403 | ❌ صلاحيات غير كافية |
| 404 | ❌ Endpoint غير موجود |
| 500 | ❌ خطأ في السيرفر |

---

---

## 12. Agent Self-Service Portal — بوابة الوكيل الذاتية

> **الفرع:** `feature/agent-portal` | **تاريخ الإضافة:** 2026-05-09

### 12.1 الغرض والمبدأ

بوابة قراءة فقط يُشاركها Admin مع كل وكيل عبر رابط آمن. لا كلمة مرور — الرابط يحتوي `uuid + portal_token` سري، وبعد الدخول الأول تُنشأ `session` تحمي بقية الصفحات.

**الهدف:** شفافية أداء الوكيل + إشعارات فورية (WebPush browser + SMS للتهبيط فقط).

---

### 12.2 Auth Flow

```
Admin → ViewAgent → "شارك الرابط"
  → mountUsing(): إذا portal_token = null → agent.generatePortalToken()
  → modal يعرض: /agent/{uuid}?token=xxxxx

الوكيل يفتح الرابط:
  → AgentPortalController::enter()
       ├─ hash_equals($agent->portal_token, $request->token) — constant-time comparison
       ├─ $request->session()->regenerate()
       ├─ session['agent_portal_id'] = $uuid
       └─ redirect → /agent/{uuid}/syncing  ← صفحة المزامنة أولاً

/agent/{uuid}/syncing → AgentSyncing (Livewire) [جديد — §12.10]:
  ├─ Render فوري: spinner + "جارٍ تحديث بياناتك..."
  └─ wire:init="runSync" → Livewire XHR → ProcessAgentSelfSync->handle() [sync] → redirect → dashboard

الصفحات المحمية:
  → AgentPortalAuth middleware
       ├─ session['agent_portal_id'] === $uuid ؟ تابع : redirect → enter + error
       └─ $request->attributes->set('portal_agent', $agent)

"تجديد الرابط":
  → agent.generatePortalToken() → token جديد → الرابط القديم يُعطي 403 فوراً
```

**أمان إضافي:** throttle `10,1` على مسار `enter` لمنع brute-force.

---

### 12.3 Routes (10 مسارات)

| الطريقة | المسار | الحماية | الهدف |
|---|---|---|---|
| GET | `/agent/{uuid}` | throttle:10,1 | دخول بالرابط → session → redirect لـ /syncing |
| GET | `/agent/{uuid}/syncing` | agent.portal.auth | صفحة المزامنة — spinner + wire:init → runSync → dashboard |
| GET | `/agent/{uuid}/dashboard` | agent.portal.auth | الرئيسية (KPIs) |
| GET | `/agent/{uuid}/progress` | agent.portal.auth | مخطط الأداء |
| GET | `/agent/{uuid}/notifications` | agent.portal.auth | الإشعارات |
| GET | `/agent/{uuid}/rewards` | agent.portal.auth | المكافآت |
| GET | `/agent/{uuid}/opportunities` | agent.portal.auth | اليانصيب |
| GET | `/agent/{uuid}/history` | agent.portal.auth | السجل |
| POST | `/agent/{uuid}/logout` | agent.portal.auth | تسجيل الخروج |
| POST | `/agent/{uuid}/push/subscribe` | agent.portal.auth | حفظ WebPush subscription |

---

### 12.3.1 SyncStatusBadge — Badge المزامنة التلقائية

**الملف:** `app/Livewire/SyncStatusBadge.php` | **View:** `resources/views/livewire/sync-status-badge.blade.php`

Livewire component يُعرض في الـ Header (Admin Panel) — يُراقب حالة مزامنة Deals API ويُشغّلها تلقائياً client-side.

**الوظائف:**
- `refresh()` — يُحدّث حالة آخر `DataImport(source_type='deals_api')` + يحسب `nextSyncAt` بناءً على آخر مزامنة + `deals_sync_interval_minutes`
- `autoSync()` — يُطلَق من Alpine.js عند وصول العداد لـ 00:00. يتحقق: deals_sync_enabled + interval + لا يوجد import جارٍ → يُنشئ `DataImport` + يُطلق `ProcessDataImport::dispatch()`

**حالات العرض (`$displayStatus`):**
- `idle` — عداد تنازلي Alpine.js يعمل (HH:MM أو MM:SS)
- `pending` — import في الانتظار (animation نبض)
- `processing` — يعمل الآن (spinner + شريط تقدم %)
- `success` — اكتملت (تبقى 60 ثانية ثم ترجع idle)

**Auto-trigger Architecture:**
```
Alpine.js tick() كل ثانية:
  ├─ diff === 0 && !triggered && displayStatus === 'idle'
  │    → triggered = true → $wire.autoSync()
  └─ Livewire re-render بعد autoSync: displayStatus = 'pending' → يمنع الإعادة
```
`wire:poll.30000ms` كـ fallback لتحديث الحالة.

> **ملاحظة:** القاعدة هي Client-driven auto-sync — المتصفح (لا Task Scheduler) هو من يُطلق المزامنة. يتطلب Queue Worker نشطاً (`php artisan queue:work --timeout=360`) لمعالجة الـ jobs.

---

### 12.4 Livewire Components (7 components)

| Component | الملف | الوظيفة |
|---|---|---|
| `AgentPortalPage` | `app/Livewire/AgentPortal/AgentPortalPage.php` | Abstract base — يحمّل `$agent` + `renderWithLayout()` |
| `AgentSyncing` | `AgentPortal/AgentSyncing.php` | صفحة المزامنة — `wire:init="runSync"` يُطلق `ProcessAgentSelfSync->handle()` synchronously. عند الانتهاء: redirect للـ dashboard. يُعرض spinner "جارٍ تحديث بياناتك..." خلال الانتظار (لا polling، لا queue) |
| `AgentDashboard` | `AgentPortal/AgentDashboard.php` | KPIs + club badge. Banner احمر أعلى الصفحة إذا `$agent->is_violator` ("حسابك مُعلَّق من قِبَل الإدارة"). يُعرض "آخر مزامنة: X" إذا `last_self_sync_at` غير null |
| `AgentProgress` | `AgentPortal/AgentProgress.php` | 3 datasets من dailySnapshots — Alpine.js يُبدّل client-side |
| `AgentNotifications` | `AgentPortal/AgentNotifications.php` | قائمة إشعارات مع `markRead()` + `markAllRead()` + filter |
| `AgentRewards` | `AgentPortal/AgentRewards.php` | مكافآت مع payment_status badge |
| `AgentOpportunities` | `AgentPortal/AgentOpportunities.php` | فرص مجمّعة بـ club |
| `AgentHistory` | `AgentPortal/AgentHistory.php` | سجل مرتّب بـ `event_timestamp DESC` |
| `NotificationBell` | `AgentPortal/NotificationBell.php` | **Bell في الـ navbar** — `wire:poll.5s` يكشف إشعارات جديدة + toast (max 3) + صوت. يستقبل حدث `notifications-marked-all-read` ويُصفِّر `unreadCount` فوراً. أنواع الإشعارات (6): `promotion` 🏆 / `demotion` 📉 / `warning` ⚠️ / `achievement` 🌟 / `milestone` / `progress` 🔔 |

> **ملاحظة معمارية:** `#[Layout]` لا يُمرّر خصائص الـ component للـ layout في Livewire 3.
> الحل: `renderWithLayout()` في `AgentPortalPage` يستخدم `->layout('layouts.agent-portal', ['agent' => $this->agent])` صراحةً.

---

### 12.5 نظام الإشعارات الفوري (NotificationBell)

```
كل 5 ثوانٍ: wire:poll → NotificationBell::checkNew()
  ├─ يستعلم عن AgentNotification حيث sent_at > lastSeenAt
  ├─ لكل إشعار جديد: $this->dispatch('new-portal-notification', id, title, body, type)
  └─ يُحدِّث unreadCount في الـ bell

Alpine.js يستقبل الحدث:
  ├─ playPortalNotifSound(type) — Web Audio API، نغمة مختلفة لكل نوع:
  │    promotion:   [880, 1100, 1320] Hz    — 3 نغمات تصاعدية
  │    achievement: [880, 1100, 1320, 1760] Hz — 4 نغمات تصاعدية
  │    warning:     [600, 480] Hz            — نغمتان تنازليتان
  │    demotion:    [220] Hz                 — نغمة منخفضة واحدة
  │    info/أخرى:  [660] Hz                 — نغمة متوسطة واحدة
  │    (_portalAudioCtx singleton — يمنع تجاوز حد Chrome 6 contexts)
  ├─ toast يظهر بـ animation (slide + scale) من الأعلى (max 3 toasts في نفس الوقت)
  ├─ مدة الـ toast: 8000–12000ms حسب النوع ثم auto-dismiss
  ├─ لون حسب النوع: 🏆 أخضر (promotion) / ⚠️ أصفر (warning) / 📉 أحمر (demotion) / 🌟 أخضر فاتح (achievement)
  └─ الضغط على Toast → $wire.markRead(id) + dismiss
```

---

### 12.6 نظام الإشعارات متعدد القنوات

| الحدث | AgentNotification (DB) | WebPush (browser) | SMS |
|---|---|---|---|
| قبول ترقية — `ClubChangeRequestResource::approveRequest()` | ✅ `promotion` | ✅ (إذا portal_token موجود) | ❌ |
| ترقية يدوية — `AgentObserver::checkAndApplyPromotion()` | ✅ `promotion` | ✅ (إذا portal_token موجود) | ❌ |
| ترقية يدوية — `AgentObserver::handleClubChange()` | ✅ `promotion` | ✅ (إذا portal_token موجود) | ❌ |
| رفض طلب ترقية أو تهبيط | ❌ | ❌ | ❌ |
| قبول تهبيط — `ClubChangeRequestResource::approveRequest()` | ❌ | ❌ | ❌ |
| تصنيف مخالف — `ClubChangeRequestResource::rejectRequest()` | ❌ | ❌ | ❌ |
| إلغاء تصنيف مخالف — `AgentObserver::handleViolatorRemoval()` | ❌ | ❌ | ❌ |
| دفع مكافأة (`RewardObserver`) | ✅ `achievement` | ✅ | ❌ |
| فشل دفع مكافأة (`RewardObserver`) | ✅ `warning` | ✅ | ✅ |
| فرصة صيانة شهرية (`CreateMonthlyMaintenanceOpportunities`) | ✅ `achievement` | ✅ (إذا portal_token موجود) | ❌ |

> **القاعدة الذهبية (الإصدار 2.0):** الإشعار للوكيل **فقط عند قبول ترقية**. لا إشعار على رفض أو تهبيط أو مخالفة أو إلغاء مخالفة.
>
> **هندسة الإشعارات:** لا `$pendingNotifies` في `ProcessDataImport` بعد الآن — الـ Job لا يُرسل أي إشعارات. الإشعارات تصدر فقط من `ClubChangeRequestResource` (قبول ترقية) أو `AgentObserver` (تعديل يدوي) أو `RewardObserver` (مكافأة).

**WebPush:** package `laravel-notification-channels/webpush` — VAPID keys في `.env`.
**SMS:** abstraction layer — `SmsDriver` interface → `NullSmsDriver` (dev) / `UnifonicSmsDriver` (prod).
**Queue:** `QUEUE_CONNECTION=sync` في dev (الإشعارات تُرسَل فوراً). في prod: تغيير لـ `database` أو `redis` مع queue worker.

> **ملاحظة:** `ProcessAgentSelfSync` لا يستخدم Queue إطلاقاً — يُشغَّل synchronously عبر `$job->handle()` مباشرة في `AgentSyncing::runSync()`. يعمل حتى بدون queue worker.

---

### 12.7 Agent Model — التعديلات

```php
use HasUuids, SoftDeletes, Notifiable, HasPushSubscriptions;

// Fillable additions:
'portal_token',         // nullable unique string(64)
'last_self_sync_at',    // nullable timestamp — تُحدَّث بعد كل self-sync

// Casts additions:
'last_self_sync_at' => 'datetime',

// Methods added:
generatePortalToken(): string        // bin2hex(random_bytes(32)) + update()
getPortalUrl(): string               // route('agent.portal.enter', [uuid, token])
routeNotificationForSms(): ?string   // returns $this->phone

// Renamed (conflict with Notifiable trait):
notifications() → agentNotifications()  // HasMany AgentNotification
```

---

### 12.8 Admin Actions في ViewAgent

| الـ Action | متى يُنفَّذ | السلوك |
|---|---|---|
| `share_portal_link` | `mountUsing()` — قبل فتح الـ modal | إذا portal_token = null: يُولِّد token. Modal يعرض URL مع زر نسخ Alpine.js |
| `regenerate_portal_link` | `action()` مع `requiresConfirmation()` | يُولِّد token جديد → الرابط القديم يُعطي 403 فوراً |

> **لماذا `mountUsing()` لا `action()`؟** — `action()` لا يُنفَّذ عندما `modalSubmitAction(false)` لأن الـ modal ليس له زر submit.

---

### 12.10 نظام المزامنة الذاتية — Agent Self-Sync (2026-06-20)

#### الهدف
عند كل دخول للوكيل عبر رابطه (`/agent/{uuid}?token=...`)، تُطلق مزامنة لأرقامه من Deals API — نفس الـ API في الاستيراد الجماعي (`GetSubCustomerDeals`) لكن لوكيل واحد فقط. يُعرض spinner أثناء الانتظار ثم ينتقل فوراً للـ dashboard.

#### "مزامنة مرة واحدة لكل session"
- ✅ الوكيل يفتح الرابط → sync
- ❌ التنقل بين صفحات البوابة → لا sync
- ✅ إغلاق المتصفح وإعادة فتح الرابط → session جديد → sync جديد

#### مسار التنفيذ الكامل

```
/agent/{uuid}?token=... → AgentPortalController::enter()
  ├─ hash_equals(portal_token, request.token) — constant-time
  ├─ session()->regenerate() + session['agent_portal_id'] = $uuid
  └─ redirect → /agent/{uuid}/syncing

/agent/{uuid}/syncing → AgentSyncing (Livewire)
  ├─ Render فوري: spinner CSS + "جارٍ تحديث بياناتك..."
  └─ wire:init="runSync"  ← Livewire XHR بعد أول render

AgentSyncing::runSync()  [Livewire XHR — blocks until done]
  ├─ new ProcessAgentSelfSync($this->agent)->handle()   ← synchronous
  └─ $this->redirectRoute('agent.portal.dashboard', ...)

ProcessAgentSelfSync::handle()
  ├─ [is_violator=true] → updateSyncTime() + return
  ├─ [deals_api_url فارغ] → updateSyncTime() + return
  ├─ HTTP POST deals_api_url (apiName='GetSubCustomerDeals',
  │   wildcards=[agent_id, campaign_start_date, today]) — timeout=15s
  ├─ [result !== 'SUCCESS'] → updateSyncTime() + return
  ├─ $newLines  = rows.where('task_name','new-order').where('status','Activated').sum('count')
  ├─ $transfers = rows.where('task_name','number-portability').where('status','Activated').sum('count')
  └─ Agent::withoutEvents(fn → processRow($newLines, $transfers, $clubs)):
        $agent->update([transfer_count, new_line_count, current_total])
        DailySnapshot::where(data_date=today, agent_id)->update([...])   ← لا insert
        تقييم الأهلية → ClubChangeRequest::create(pending) عند تغيّر المستحَق
  └─ updateSyncTime()  ← يُسجَّل last_self_sync_at دائماً
```

#### لماذا `update()` لا `updateOrCreate()` في DailySnapshot؟

`daily_snapshots.import_id` هو `NOT NULL` في الـ migration — لا يقبل `null`. الـ self-sync لا يملك `import_id` حقيقياً:
- ❌ `DailySnapshot::updateOrCreate(['import_id' => null, ...])` → DB exception عند محاولة INSERT
- ✅ `DailySnapshot::where(data_date, agent_id)->update([...])` → يُحدِّث الصف الموجود فقط، لا يُنشئ جديداً

> **نتيجة:** إذا لم يُجرَ import يومي لهذا الوكيل بعد، لا يوجد snapshot → `update()` لا يُؤثر (0 rows) → OK.

#### لماذا `wire:init` لا Queue؟

`QUEUE_CONNECTION=database` يتطلب queue worker نشطاً — بدونه يبقى الـ job في `jobs` table أبداً. الحل:

| النهج | المشكلة |
|---|---|
| `ProcessAgentSelfSync::dispatch()` + wire:poll | يتطلب queue worker. بدونه: لا معالجة → timeout → redirect بلا sync |
| `$job->handle()` synchronous + `wire:init` | لا queue worker مطلوب. wire:init يُطلق Livewire XHR → المستخدم يرى spinner خلال استدعاء API الفعلي → redirect فور الانتهاء |

**المقايضة:** مدة API call (≤15s) = المستخدم ينتظر على صفحة المزامنة. إذا فشل API أو timeout → `updateSyncTime()` تُنفَّذ دائماً → redirect للـ dashboard بلا crash.

#### نقطة دقيقة: `import_id` في ClubChangeRequest

`club_change_requests.import_id` هو **nullable** ✅ — يُمرَّر `null` عند self-sync (لا import مرتبط). آمن تماماً في الـ schema.

---

### 12.9 ملفات البوابة الكاملة

| نوع | الملف |
|---|---|
| Migration | `database/migrations/2026_05_09_000001_add_portal_token_to_agents_table.php` |
| Migration | `database/migrations/2026_05_09_000002_create_push_subscriptions_table.php` (uuidMorphs) |
| Migration | `database/migrations/2026_06_18_000001_create_club_change_requests_table.php` |
| Migration | `database/migrations/2026_06_18_000002_add_violator_fields_to_agents_table.php` |
| Migration | `database/migrations/2026_06_18_000003_remove_demotion_timer_fields.php` |
| Migration | `database/migrations/2026_06_18_000004_add_rejection_violation_to_history_logs_event_type.php` |
| Migration | `database/migrations/2026_06_19_212705_add_last_self_sync_at_to_agents_table.php` |
| Middleware | `app/Http/Middleware/AgentPortalAuth.php` |
| Controller | `app/Http/Controllers/AgentPortalController.php` |
| Notification | `app/Notifications/AgentPortalNotification.php` |
| SMS Contract | `app/Contracts/SmsDriver.php` |
| SMS Channel | `app/Channels/SmsChannel.php` |
| SMS Drivers | `app/Sms/NullSmsDriver.php` · `app/Sms/UnifonicSmsDriver.php` |
| Job | `app/Jobs/ProcessAgentSelfSync.php` |
| Livewire (8) | `app/Livewire/AgentPortal/{AgentPortalPage,AgentSyncing,AgentDashboard,AgentProgress,AgentNotifications,AgentRewards,AgentOpportunities,AgentHistory,NotificationBell}.php` |
| Layout | `resources/views/layouts/agent-portal.blade.php` |
| Views (8) | `resources/views/livewire/agent-portal/{syncing,dashboard,progress,notifications,rewards,opportunities,history,notification-bell}.blade.php` |
| Admin Modal | `resources/views/filament/agent/portal-link-modal.blade.php` |
| Service Worker | `public/sw.js` |

---

*هذا المستند تم توليده بتحليل الكود المصدري الكامل للمشروع بتاريخ 2026-05-01.*
*آخر تحديث: 2026-06-18 — الإصدار 2.0: نظام Approval Flow لطلبات تغيير النادي (ClubChangeRequestResource) + حذف عداد التهبيط نهائياً + تصنيف المخالفين (is_violator) + قاعدة الإشعار للوكيل عند قبول الترقية فقط.*
*آخر تحديث: 2026-05-23 — إصلاح إشعارات ProcessDataImport (TD-006): إضافة AgentNotification لكل الأحداث + $pendingNotifies pattern لإرسال WebPush/SMS بعد الـ transaction. إصلاح NotificationBell تزامن (TD-007). إصلاح AudioContext singleton (TD-008). تحديث §7.1 و§12.6. إصلاح شرط تأهيل النادي (TD-009): إضافة required_transfer_count للفلترة في AgentObserver + ProcessDataImport + AgentResource + CreateAgent — كان عداد التهبيط لا يبدأ عند نسبة تحويل < 60%. تحديث §4.4 و§6.4.*
*آخر تحديث: 2026-05-23 (مراجعة نظام الإشعارات) — تصحيح اسم جدول `notifications` (كان خطأً `agent_notifications`) في §2.1. إضافة §2.1.1 بـ schema كامل لجدول notifications (15 حقل + indexes). تصحيح enum notification_type من 4 إلى 6 أنواع (milestone, progress). تحديث خريطة الأصوات في §12.5 (نغمة مختلفة لكل نوع). تحديث وصف NotificationBell في §12.4 (max 3 toasts، 6 أنواع). إضافة صف الفرصة الشهرية في جدول §12.6. إضافة §5.8 Console Commands (SyncDailyNumbers + CreateMonthlyMaintenanceOpportunities). إضافة AppSetting model في §5.1.*
*آخر تحديث: 2026-06-09 (Deals API Auto-Sync + تدقيق شامل) — إضافة §5.5.1 صفحة `DealsApiSettings` (مزامنة أرقام الوكلاء). إضافة §12.3.1 `SyncStatusBadge` (client-driven auto-sync عبر Alpine.js). إضافة `SyncAgentDeals` في §5.8. توثيق `ProcessDistributorSync` job (§5.2). توثيق 4 صفحات API settings ناقصة (§5.5.1). إضافة `AgentsStatsWidget` (§3.3). تحديث DistributorOverviewWidget (§3.4). إضافة DistributorLogin (§3.5). توثيق 22 Blade file في §5.7 (كانت ناقصة). توسيع جدول AppSetting لـ 15 مفتاح مع الاستخدام (§5.8). تحديث DataImport schema (source_type=deals_api، progress، error_details، حذف unique constraint).*
*آخر تحديث: 2026-06-19 (معالجة Bottlenecks §7.1) — إصلاح TD-002: AgentPolicy → Authenticatable + instanceof guard. إصلاح TD-003: HistoryLog + AuditLog → performUpdate/performDeleteOnModel يرميان LogicException. تحسين Queue Jobs: tries=1 + failed() safety net في ProcessDataImport + ProcessAgentImport. تخفيف Transaction Locking: SET SESSION innodb_lock_wait_timeout=5 قبل DB::transaction. إصلاح N+1 ClubBreakdownWidget: من 13 query → 6 queries. إضافة BulkAction "قبول الترقيات المحددة" في ClubChangeRequestResource. إغلاق Bonus Idempotency (كانت مُعالَجة فعلاً في الكود).*
*آخر تحديث: 2026-06-20 (الإصدار 3.0 — نظام Agent Self-Sync) — إضافة §12.10 شرح كامل لنظام المزامنة الذاتية. إضافة `ProcessAgentSelfSync` job (synchronous، لا queue) في §5.2. إضافة `AgentSyncing` Livewire component في §12.4. إضافة مسار `/syncing` في §12.3 (10 مسارات). تحديث Auth Flow في §12.2 (enter → /syncing → wire:init → dashboard). تحديث Agent Model §12.7 وجدول agents §2.1 بحقل `last_self_sync_at`. تحديث AppSetting §5.8 لإضافة `ProcessAgentSelfSync` في deals_api_*. إضافة مخاطرة Self-Sync API Latency في §7.1. إصلاحان حرجان: (1) `DailySnapshot.import_id NOT NULL` → استخدام `update()` لا `updateOrCreate()` — كان يمنع إنشاء ClubChangeRequest كلياً. (2) Queue dependency → wire:init synchronous pattern بلا queue worker.*
*يجب تحديثه عند أي تغيير جوهري في: ProcessDataImport، AgentObserver، بنية الـ Clubs، نظام المصادقة، Agent Portal، Console Commands المجدولة، أو SyncStatusBadge.*

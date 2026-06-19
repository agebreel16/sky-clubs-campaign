# 🟠 إصلاح #4: AgentPolicy + N+1 Queries + توحيد campaign_increase

هذا الملف يجمع 3 إصلاحات أصغر يمكن تطبيقها معاً.

---

## الجزء أ: إصلاح AgentPolicy Type-Hint (TD-002)

### المشكلة
`AgentPolicy` تتطلب `User` صراحة، فإذا أُضيف Resource جديد في Distributor Panel سيكسر.

### الحل: استخدام Authenticatable

**الملف:** `app/Policies/AgentPolicy.php`

```php
<?php

namespace App\Policies;

use App\Models\Agent;
use App\Models\Distributor;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Auth\Access\HandlesAuthorization;

class AgentPolicy
{
    use HandlesAuthorization;

    /**
     * أي مستخدم Distributor يُحوَّل تلقائياً للـ scope الخاص به.
     */
    protected function isDistributor(Authenticatable $user): bool
    {
        return $user instanceof Distributor;
    }

    /**
     * فحص دور User من Admin Panel.
     */
    protected function userHasRole(Authenticatable $user, array $roles): bool
    {
        if (!$user instanceof User) {
            return false;
        }
        return $user->hasAnyRole($roles);
    }

    public function viewAny(Authenticatable $user): bool
    {
        // Distributor: مسموح (سيُقيَّد بـ scope في Resource)
        if ($this->isDistributor($user)) {
            return $user->is_active;
        }
        // User Admin: لازم is_active
        return $user instanceof User && $user->is_active;
    }

    public function view(Authenticatable $user, Agent $agent): bool
    {
        // Distributor: فقط وكلاؤه
        if ($this->isDistributor($user)) {
            return $user->is_active && $agent->distributor_id === $user->id;
        }
        return $user instanceof User && $user->is_active;
    }

    public function create(Authenticatable $user): bool
    {
        if ($this->isDistributor($user)) {
            return false; // Distributors لا ينشئون وكلاء
        }
        return $this->userHasRole($user, ['super_admin', 'admin', 'supervisor', 'data_entry']);
    }

    public function update(Authenticatable $user, Agent $agent): bool
    {
        if ($this->isDistributor($user)) {
            return false;
        }
        return $this->userHasRole($user, ['super_admin', 'admin', 'supervisor', 'data_entry']);
    }

    public function delete(Authenticatable $user, Agent $agent): bool
    {
        if ($this->isDistributor($user)) {
            return false;
        }
        return $this->userHasRole($user, ['super_admin', 'admin']);
    }

    public function restore(Authenticatable $user, Agent $agent): bool
    {
        if ($this->isDistributor($user)) {
            return false;
        }
        return $this->userHasRole($user, ['super_admin', 'admin']);
    }

    public function forceDelete(Authenticatable $user, Agent $agent): bool
    {
        return false; // مرفوض دائماً
    }
}
```

### تبسيط MyAgentsResource

**الآن يمكنك حذف `getAuthorizationResponse()` override من `MyAgentsResource`** لأن الـ Policy تتعامل مع الحالتين، **لكن** حافظ على `getEloquentQuery()` للـ scope:

**الملف:** `app/Filament/DistributorPanel/Resources/MyAgentsResource.php`

```php
public static function getEloquentQuery(): Builder
{
    return parent::getEloquentQuery()
        ->where('distributor_id', auth('distributor')->id());
}

// ❌ يمكن حذف getAuthorizationResponse() لأن الـ Policy تتعامل مع الحالة
// لكن أبقِ هذا فقط للحماية المضاعفة (defense in depth):
public static function canCreate(): bool { return false; }
public static function canEdit($record): bool { return false; }
public static function canDelete($record): bool { return false; }
```

---

## الجزء ب: إصلاح N+1 في ClubBreakdownWidget (TD-005)

### المشكلة
كل نادٍ = استعلام منفصل (3-4 queries × 3 أندية = 12 query).

### الحل: استعلام واحد مُجمَّع

**الملف:** `app/Filament/DistributorPanel/Widgets/ClubBreakdownWidget.php`

```php
<?php

namespace App\Filament\DistributorPanel\Widgets;

use App\Models\Agent;
use App\Models\Club;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class ClubBreakdownWidget extends Widget
{
    protected string $view = 'filament.distributor-panel.widgets.club-breakdown';

    protected function getViewData(): array
    {
        $distributorId = auth('distributor')->id();

        // ✅ استعلام واحد بدل 12
        $stats = Agent::query()
            ->where('distributor_id', $distributorId)
            ->select('current_club_id', DB::raw('COUNT(*) as total'))
            ->selectRaw('SUM(CASE WHEN demotion_timer_start IS NOT NULL THEN 1 ELSE 0 END) as at_risk')
            ->selectRaw('SUM(CASE WHEN is_first_arrival = 1 THEN 1 ELSE 0 END) as first_arrivals')
            ->groupBy('current_club_id')
            ->get()
            ->keyBy('current_club_id');

        // ✅ جلب الأندية مرة واحدة
        $clubs = Club::where('is_active', true)
            ->orderBy('club_order')
            ->get();

        $breakdown = $clubs->map(function ($club) use ($stats) {
            $stat = $stats->get($club->club_id);
            return [
                'club' => $club,
                'total' => $stat?->total ?? 0,
                'at_risk' => $stat?->at_risk ?? 0,
                'first_arrivals' => $stat?->first_arrivals ?? 0,
            ];
        });

        // وكلاء بدون نادٍ
        $unassigned = $stats->get(null);

        return [
            'breakdown' => $breakdown,
            'unassigned' => $unassigned?->total ?? 0,
        ];
    }
}
```

### الـ View

**الملف:** `resources/views/filament/distributor-panel/widgets/club-breakdown.blade.php`

تأكد أنه يستخدم الـ structure الجديد:

```blade
<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">توزيع وكلائي حسب النادي</x-slot>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @foreach($breakdown as $row)
                <div class="rounded-lg border p-4">
                    <h3 class="font-bold">{{ $row['club']->name }}</h3>
                    <p class="text-2xl">{{ $row['total'] }} وكيل</p>
                    @if($row['at_risk'] > 0)
                        <p class="text-sm text-warning-600">
                            ⚠️ {{ $row['at_risk'] }} في خطر
                        </p>
                    @endif
                    @if($row['first_arrivals'] > 0)
                        <p class="text-sm text-success-600">
                            🥇 {{ $row['first_arrivals'] }} من الأوائل
                        </p>
                    @endif
                </div>
            @endforeach

            @if($unassigned > 0)
                <div class="rounded-lg border border-dashed p-4">
                    <h3 class="font-bold text-gray-500">بدون نادٍ</h3>
                    <p class="text-2xl">{{ $unassigned }} وكيل</p>
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
```

---

## الجزء ج: توحيد campaign_increase (TD-004)

### المشكلة
الصيغة `current_total - pre_campaign_count` مكررة في 6+ أماكن.

### الحل: عمود محسوب في DB + Accessor واحد

#### الخطوة 1: إضافة عمود محسوب (Generated Column)

**أنشئ migration:**

```bash
php artisan make:migration add_campaign_increase_to_agents
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // عمود محسوب — MySQL يحسبه تلقائياً
        DB::statement("
            ALTER TABLE agents
            ADD COLUMN campaign_increase INT 
            GENERATED ALWAYS AS (current_total - pre_campaign_count) STORED
        ");

        // Index للاستخدام في WHERE/ORDER BY
        DB::statement("CREATE INDEX idx_agents_campaign_increase ON agents(campaign_increase)");
    }

    public function down(): void
    {
        DB::statement("DROP INDEX idx_agents_campaign_increase ON agents");
        DB::statement("ALTER TABLE agents DROP COLUMN campaign_increase");
    }
};
```

#### الخطوة 2: تبسيط الـ Accessor

**الملف:** `app/Models/Agent.php`

```php
// ❌ احذف Accessor القديم:
// public function getCampaignIncreaseAttribute() { ... }

// ✅ الآن campaign_increase يأتي مباشرة من DB كعمود عادي
// لا حاجة لـ accessor، Laravel سيقرأه تلقائياً.

// إذا كنت تريد إضافة caster لضمان النوع:
protected $casts = [
    // ... casts الموجودة
    'campaign_increase' => 'integer',
];
```

#### الخطوة 3: تحديث الاستخدامات

ابحث في الـ project عن أي مكان يستخدم الصيغة يدوياً:

```bash
# في terminal — ابحث عن الاستخدامات
grep -rn "current_total - pre_campaign_count" app/
grep -rn "current_total.*pre_campaign_count" app/
```

استبدل كل مثيل بـ `$agent->campaign_increase` مباشرة.

#### الخطوة 4: نفس الشيء لـ DailySnapshot

**أنشئ migration:**

```php
DB::statement("
    ALTER TABLE daily_snapshots
    ADD COLUMN campaign_increase INT 
    GENERATED ALWAYS AS (current_total - pre_campaign_count) STORED
");
```

ثم في `DailySnapshot.php` احذف الـ accessor.

---

## الفائدة من Generated Column

```sql
-- ✅ يمكنك الآن استخدامها مباشرة في queries:
SELECT * FROM agents WHERE campaign_increase >= 100;

-- ✅ ORDER BY سريع جداً (مع الـ index)
SELECT * FROM agents ORDER BY campaign_increase DESC LIMIT 10;

-- ✅ في Eloquent:
Agent::where('campaign_increase', '>=', 100)->get();
Agent::orderBy('campaign_increase', 'desc')->take(10)->get();
```

---

## التحقق من النجاح

### اختبار أ — Policy:
```php
// في tinker
>>> $distributor = Distributor::first();
>>> Auth::guard('distributor')->login($distributor);
>>> $agent = Agent::where('distributor_id', $distributor->id)->first();
>>> $distributor->can('view', $agent);  // true
>>> $otherAgent = Agent::where('distributor_id', '!=', $distributor->id)->first();
>>> $distributor->can('view', $otherAgent);  // false
>>> $distributor->can('update', $agent);  // false
```

### اختبار ب — N+1:
```php
// قبل التعديل: ~12 queries
// بعد التعديل: 2 queries فقط
DB::enableQueryLog();
$widget = new \App\Filament\DistributorPanel\Widgets\ClubBreakdownWidget();
// شغّل getViewData()
dump(DB::getQueryLog()); // يجب أن يكون عدد الـ queries ≤ 2
```

### اختبار ج — campaign_increase:
```sql
-- يجب أن يعطي نفس النتائج
SELECT agent_id, current_total, pre_campaign_count, campaign_increase
FROM agents LIMIT 5;
-- العمود campaign_increase يجب أن يساوي current_total - pre_campaign_count
```

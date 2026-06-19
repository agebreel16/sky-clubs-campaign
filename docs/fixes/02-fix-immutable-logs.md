# 🟡 إصلاح #2: حماية السجلات الدائمة (HistoryLog & AuditLog) — TD-003

## المشكلة
`HistoryLog` و `AuditLog` يجب أن تكونا **immutable** (لا يمكن تعديلها أو حذفها بعد الإنشاء)، لكن الحماية الحالية مجرد تعليق `// Immutable`. أي مطوّر يمكنه استدعاء:

```php
HistoryLog::where(...)->update([...]);  // يعمل بدون مشكلة! ❌
HistoryLog::where(...)->delete();        // يعمل بدون مشكلة! ❌
```

## الحل: حماية متعددة الطبقات

### الطبقة 1: على مستوى Model (PHP)
رمي Exception عند محاولة Update أو Delete.

### الطبقة 2: على مستوى Database (MySQL Trigger)
حماية حقيقية لا يمكن تجاوزها حتى من query مباشر.

---

## المهام المطلوبة من Claude Code

### المهمة 1: إنشاء Trait مشترك للـ Immutable Models

**أنشئ ملف جديد:** `app/Models/Concerns/IsImmutable.php`

```php
<?php

namespace App\Models\Concerns;

trait IsImmutable
{
    /**
     * تسجيل event listeners لمنع UPDATE و DELETE.
     */
    public static function bootIsImmutable(): void
    {
        static::updating(function ($model) {
            throw new \LogicException(
                static::class . ' is immutable. Updates are not allowed. ' .
                'Record ID: ' . $model->getKey()
            );
        });

        static::deleting(function ($model) {
            throw new \LogicException(
                static::class . ' is immutable. Deletes are not allowed. ' .
                'Record ID: ' . $model->getKey()
            );
        });
    }

    /**
     * منع saveQuietly() أيضاً (يتجاوز events).
     */
    public function save(array $options = []): bool
    {
        if ($this->exists) {
            throw new \LogicException(
                static::class . ' is immutable. Cannot save changes to existing record.'
            );
        }
        return parent::save($options);
    }
}
```

### المهمة 2: تطبيق الـ Trait على HistoryLog

**الملف:** `app/Models/HistoryLog.php`

```php
<?php

namespace App\Models;

use App\Models\Concerns\IsImmutable;
use Illuminate\Database\Eloquent\Model;

class HistoryLog extends Model
{
    use IsImmutable;  // ✅ أضف هذا

    protected $primaryKey = 'log_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    // ... باقي الكود كما هو ...
}
```

### المهمة 3: تطبيق الـ Trait على AuditLog

**الملف:** `app/Models/AuditLog.php`

```php
<?php

namespace App\Models;

use App\Models\Concerns\IsImmutable;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use IsImmutable;  // ✅ أضف هذا

    protected $primaryKey = 'audit_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    // ... باقي الكود كما هو ...
}
```

### المهمة 4: إضافة DB Triggers (الحماية الحقيقية)

**أنشئ migration جديد:**

```bash
php artisan make:migration add_immutability_triggers_to_logs
```

**في الملف الجديد:**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Trigger لـ history_logs — منع UPDATE
        DB::unprepared("
            CREATE TRIGGER history_logs_prevent_update
            BEFORE UPDATE ON history_logs
            FOR EACH ROW
            BEGIN
                SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'history_logs is immutable: UPDATE forbidden';
            END
        ");

        // Trigger لـ history_logs — منع DELETE
        DB::unprepared("
            CREATE TRIGGER history_logs_prevent_delete
            BEFORE DELETE ON history_logs
            FOR EACH ROW
            BEGIN
                SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'history_logs is immutable: DELETE forbidden';
            END
        ");

        // Trigger لـ audit_logs — منع UPDATE
        DB::unprepared("
            CREATE TRIGGER audit_logs_prevent_update
            BEFORE UPDATE ON audit_logs
            FOR EACH ROW
            BEGIN
                SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'audit_logs is immutable: UPDATE forbidden';
            END
        ");

        // Trigger لـ audit_logs — منع DELETE
        DB::unprepared("
            CREATE TRIGGER audit_logs_prevent_delete
            BEFORE DELETE ON audit_logs
            FOR EACH ROW
            BEGIN
                SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'audit_logs is immutable: DELETE forbidden';
            END
        ");
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS history_logs_prevent_update');
        DB::unprepared('DROP TRIGGER IF EXISTS history_logs_prevent_delete');
        DB::unprepared('DROP TRIGGER IF EXISTS audit_logs_prevent_update');
        DB::unprepared('DROP TRIGGER IF EXISTS audit_logs_prevent_delete');
    }
};
```

### المهمة 5: التشغيل والاختبار

```bash
php artisan migrate
```

**اختبار الحل:**

```php
// في tinker — يجب أن ترمي Exception
php artisan tinker

>>> $log = HistoryLog::first();
>>> $log->event_type = 'fake';
>>> $log->save();
// LogicException: HistoryLog is immutable...

>>> HistoryLog::where('log_id', $log->log_id)->update(['event_type' => 'fake']);
// SQLSTATE[45000]: history_logs is immutable: UPDATE forbidden ✅

>>> HistoryLog::where('log_id', $log->log_id)->delete();
// SQLSTATE[45000]: history_logs is immutable: DELETE forbidden ✅
```

---

## ملاحظات مهمة

### ⚠️ تأثير على التطوير
- لن تستطيع حذف سجلات HistoryLog/AuditLog من tinker بعد الآن.
- إذا احتجت ذلك في dev، استخدم: `DB::statement('DROP TRIGGER ...')` مؤقتاً.

### ⚠️ Seeder و Factory
إذا كان لديك seeders تنشئ ثم تعدل سجلات، ستفشل. تأكد من إنشاء السجلات بحالتها النهائية فقط.

### ⚠️ TRUNCATE ما زال يعمل
الـ Triggers تمنع UPDATE/DELETE فقط. `TRUNCATE TABLE history_logs` ما زال يعمل (يستخدم في الاختبارات). إذا أردت منعه أيضاً:

```sql
-- لكن لا أنصح به في dev:
-- TRUNCATE لا يطلق row-level triggers
-- الحل: استخدم REVOKE TRUNCATE من المستخدم الإنتاجي
REVOKE DROP, ALTER, TRUNCATE ON history_logs FROM 'app_user'@'%';
```

---

## التحقق من النجاح

```sql
-- تأكد من وجود الـ Triggers
SHOW TRIGGERS WHERE `Table` IN ('history_logs', 'audit_logs');

-- يجب أن ترى 4 triggers
```

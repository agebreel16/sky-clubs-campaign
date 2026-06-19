# 📋 خطة الإصلاح الشاملة لمشروع Sky Clubs Campaign

> **استخدام:** أعطِ هذا الملف لـ Claude Code مع الملفات المرفقة في نفس المجلد، وسينفّذ الإصلاحات بالترتيب.

---

## 🎯 السياق

أنت تعمل على مشروع `Sky Clubs Campaign` — نظام Laravel 13 + Filament 5.6 لإدارة حملات مبيعات. المشروع لديه 8 مشاكل تقنية موثّقة. مهمتك تنفيذها بالترتيب التالي مع **اختبار كل مرحلة** قبل الانتقال للتي بعدها.

---

## ⚠️ قبل البدء — قواعد ذهبية

1. **أنشئ branch جديد لكل إصلاح:**
   ```bash
   git checkout -b fix/01-dual-write-path
   ```

2. **شغّل الاختبارات قبل وبعد كل تغيير:**
   ```bash
   php artisan test
   ```

3. **خذ نسخة احتياطية من DB قبل أي migration:**
   ```bash
   mysqldump -u root -p sky_clubs > backup_before_fix.sql
   ```

4. **لا تنفّذ كل الإصلاحات دفعة واحدة.** نفّذ → اختبر → commit → انتقل.

5. **عند الشك، اسأل قبل التنفيذ.** خاصة في الإصلاحات الحرجة (#1 و #2).

---

## 📂 ترتيب التنفيذ

### المرحلة 1 — حرجة (نفّذ أولاً) 🔴

**الإصلاح #1: حل ازدواجية الترقية (Dual Write Path)**
- 📄 الملف: `01-fix-dual-write-path.md`
- ⏱️ الوقت المتوقع: 3-4 ساعات
- 🎯 الهدف: منع إنشاء مكافآت/سجلات مكررة
- ⚠️ **خطر:** يلمس قلب الـ business logic. اختبر بعناية شديدة.

**التحقق قبل الانتقال:**
```sql
-- يجب أن يُرجع 0 صفوف
SELECT agent_id, club_id, reward_type, DATE(created_at), COUNT(*)
FROM rewards
WHERE created_at >= NOW() - INTERVAL 1 DAY
GROUP BY agent_id, club_id, reward_type, DATE(created_at)
HAVING COUNT(*) > 1;
```

---

### المرحلة 2 — مهمة (نفّذ ثانياً) 🟡

**الإصلاح #2: حماية السجلات الدائمة**
- 📄 الملف: `02-fix-immutable-logs.md`
- ⏱️ الوقت المتوقع: 1-2 ساعة
- 🎯 الهدف: منع تعديل/حذف HistoryLog و AuditLog

**الإصلاح #3: مراقبة الـ Queue**
- 📄 الملف: `03-fix-queue-monitoring.md`
- ⏱️ الوقت المتوقع: 2-3 ساعات
- 🎯 الهدف: اكتشاف فشل Queue Worker تلقائياً

**ملاحظة:** الإصلاحان #2 و #3 مستقلان عن بعضهما، يمكن تنفيذهما بالتوازي إذا كان فريقك أكثر من شخص.

---

### المرحلة 3 — تحسينات (نفّذ ثالثاً) 🟠

**الإصلاح #4: Policy + N+1 + توحيد الحسابات**
- 📄 الملف: `04-fix-policy-and-performance.md`
- ⏱️ الوقت المتوقع: 2-3 ساعات
- 🎯 الهدف: تنظيف الكود وتسريع الـ Dashboard

---

## 🧪 اختبارات إجبارية بعد كل مرحلة

### بعد المرحلة 1 (الإصلاح #1):

```bash
# 1. اختبر Import يدوياً
php artisan tinker
>>> $import = \App\Models\DataImport::factory()->create();
>>> \App\Jobs\ProcessDataImport::dispatchSync($import);
>>> // تحقق من النتائج

# 2. تحقق من عدم وجود تكرار
SELECT COUNT(*) FROM rewards 
WHERE created_at >= NOW() - INTERVAL 5 MINUTE;
```

### بعد المرحلة 2 (الإصلاحان #2 و #3):

```bash
# اختبر Immutability
php artisan tinker
>>> $log = \App\Models\HistoryLog::first();
>>> $log->update(['event_type' => 'test']);  
# يجب أن ترمي LogicException

# اختبر Queue Monitoring
php artisan imports:check-stuck
```

### بعد المرحلة 3 (الإصلاح #4):

```bash
# اختبر Performance
php artisan tinker
>>> DB::enableQueryLog();
>>> // افتح Dashboard أو Widget
>>> count(DB::getQueryLog())
# يجب أن يكون أقل من 5
```

---

## 🚨 خطة التراجع (Rollback)

إذا فشل أي إصلاح في الإنتاج:

```bash
# للـ migrations:
php artisan migrate:rollback --step=1

# للكود:
git checkout main
git revert <commit-hash>

# لـ DB:
mysql -u root -p sky_clubs < backup_before_fix.sql
```

---

## 📊 جدول متابعة

اطبع هذا الجدول وحدّثه بعد كل خطوة:

| الإصلاح | تاريخ البدء | تاريخ الانتهاء | اختبارات نجحت؟ | ملاحظات |
|---|---|---|---|---|
| #1 — Dual Write | | | ☐ | |
| #2 — Immutable Logs | | | ☐ | |
| #3 — Queue Monitoring | | | ☐ | |
| #4 — Policy + Perf | | | ☐ | |

---

## 💡 نصائح إضافية لـ Claude Code

1. **اقرأ ملف `sky.md` الأصلي أولاً** — يحتوي على map كامل للمشروع.

2. **استخدم `Agent::withoutEvents()` بحذر** — تأكد من أنك تنشئ السجلات التي يفترض Observer إنشاءها يدوياً.

3. **الـ DB Triggers على MySQL فقط** — إذا كنت تختبر على SQLite (في الـ tests)، التف عليها بـ:
   ```php
   if (DB::connection()->getDriverName() === 'mysql') {
       DB::unprepared('CREATE TRIGGER ...');
   }
   ```

4. **Filament 5.6 syntax** — استخدم `Schema` و `Form::make()` الجديدة، ليس Filament 3.

5. **عند تعديل Observer:** تذكّر أن `$model->wasChanged()` يعمل في `updated()` فقط، لا في `updating()`.

---

## ✅ معايير القبول النهائية

قبل اعتبار المشروع جاهزاً، تحقق من:

- [ ] لا توجد مكافآت مكررة لنفس الوكيل في نفس اليوم
- [ ] لا يمكن تعديل HistoryLog أو AuditLog (DB Trigger يرفض)
- [ ] Queue Health Widget يظهر في Dashboard
- [ ] Command `imports:check-stuck` يعمل ومجدول
- [ ] Distributor Panel يعمل بنفس الكفاءة (لا regression)
- [ ] Dashboard يفتح في أقل من ثانيتين
- [ ] جميع الـ tests الموجودة تنجح
- [ ] Failed Jobs تُسجَّل وتُنبَّه عنها
- [ ] لا يوجد warning في `php artisan about`

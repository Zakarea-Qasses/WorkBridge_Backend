# DATABASE_FIXES

## المشاكل التي تم إصلاحها

- منع إنشاء عقود مكررة لنفس مصدر العقد عبر قيود unique في جدول `contracts`.
- منع إنشاء عقد وظيفة إلا إذا كان طلب الوظيفة للمستخدم مقبولاً.
- منع قبول عرض مشروع غير `pending` ومنع إنشاء عقد ثانٍ لنفس المشروع.
- منع أن يكون طرفا العقد نفس المستخدم.
- منع المبالغ الصفرية أو السالبة في العقود وحركات العقود والخدمات والمشاريع ورواتب الوظائف عند الإدخال.
- منع الرصيد السالب عند خصم مبالغ العقود.
- تثبيت نطاق التقييم بين 1 و5 على مستوى قاعدة البيانات.
- تثبيت منع المحادثة مع النفس على مستوى قاعدة البيانات، مع بقاء المنع الموجود في الكود.
- تشديد التحقق من أن المدينة تتبع المحافظة عند تحديث profile أو company أو project.
- تغيير حذف `wallet_transactions.wallet_id` من cascade إلى restrict حتى لا تضيع الحركات المالية بحذف المحفظة.

## الملفات المعدلة والجديدة

### الملفات المعدلة

- `app/Services/ContractService.php`
- `app/Http/Controllers/Api/ApplicationController.php`
- `app/Http/Controllers/Api/ContractController.php`
- `app/Http/Controllers/Api/JobApplyController.php`
- `app/Http/Controllers/Api/ServiceController.php`
- `app/Http/Controllers/Api/UserProjectController.php`
- `app/Http/Controllers/Api/ProfileController.php`
- `app/Http/Controllers/Api/CompanyController.php`
- `app/Http/Controllers/Api/JobPostController.php`

### الملفات الجديدة

- `database/migrations/2026_06_24_000001_add_database_integrity_fixes.php`
- `DATABASE_FIXES.md`

## أسماء Migrations الجديدة

- `2026_06_24_000001_add_database_integrity_fixes.php`

## أوامر التشغيل

```bash
composer install
php artisan migrate
php artisan test
```

## نتيجة migrate:status

فشل الأمر:

```text
php artisan migrate:status
```

السبب:

```text
SQLSTATE[HY000] [2002] No connection could be made because the target machine actively refused it
Connection: mysql, Host: 127.0.0.1, Port: 3306, Database: WorkBridge_DB
```

## نتيجة الاختبارات

فشل الأمر:

```text
php artisan test
```

النتيجة:

```text
PASS  Tests\Unit\ExampleTest
FAIL  Tests\Feature\ExampleTest
Expected response status code [200] but received 500.
Tests: 1 failed, 1 passed (2 assertions)
```

سبب الفشل من سجل Laravel:

```text
SQLSTATE[HY000] [2002] No connection could be made because the target machine actively refused it
```

الفشل مرتبط باتصال MySQL أثناء اختبار `/`، وليس بخطأ syntax في الملفات المعدلة.

## المشاكل التي لم تصلح

- لم يتم تشغيل migrations فعلياً لأن MySQL غير متاح على `127.0.0.1:3306`.
- إذا كانت قاعدة البيانات الحالية تحتوي عقوداً مكررة أو قيماً سالبة/صفرية مخالفة، فقد يحتاج تشغيل migration إلى تنظيف تلك السجلات أولاً.
- لم أضف composite foreign key بين `city_id` و`governorate_id`; تم الاكتفاء بالتحقق في controllers لأن هذا هو الأسلوب المستخدم حالياً في المشروع.

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>طلب مستند إضافي من إدارة WorkBridge</title>
</head>
<body style="margin:0; padding:24px; background-color: #f4f6f8; font-family: Tahoma, Arial, sans-serif; color: #1f2937;">
    <div style="max-width:640px; margin:0 auto; background-color: #ffffff; border:1px solid #e5e7eb; padding:32px;">
        <h1 style="margin: 0 0 24px; font-size: 22px; color: #111827;">طلب مستند إضافي</h1>

        <p>مرحبًا {{ $companyName }}،</p>

        <p>تطلب منكم إدارة منصة WorkBridge تزويدها بمستند إضافي وفق التفاصيل التالية:</p>

        <p><strong>اسم المستند المطلوب:</strong> {{ $documentRequest->document_name }}</p>
        <p><strong>سبب الطلب:</strong> {{ $documentRequest->reason }}</p>
        <p><strong>تاريخ إرسال الطلب:</strong> {{ $requestDate }}</p>

        <p>يرجى تجهيز المستند المطلوب وإرساله إلى إدارة المنصة لاستكمال الإجراءات اللازمة.</p>

        <p style="margin-top:32px;">
            مع التحية،<br>
            إدارة منصة WorkBridge
        </p>
    </div>
</body>
</html>

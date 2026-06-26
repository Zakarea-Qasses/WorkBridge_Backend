<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>تأكيد البريد الإلكتروني</title>

    <style>
        @media screen and (max-width: 600px) {
            .email-container {
                width: 94% !important;
            }

            .content {
                padding: 30px 22px !important;
            }

            .brand-name {
                font-size: 28px !important;
            }

            .title {
                font-size: 22px !important;
            }

            .otp-code {
                font-size: 29px !important;
                letter-spacing: 6px !important;
                padding: 16px 20px !important;
            }
        }
    </style>
</head>

<body style="
    margin: 0;
    padding: 0;
    background-color: #eef6ff;
    font-family: Tahoma, Arial, sans-serif;
    direction: rtl;
">

<table role="presentation"
       width="100%"
       cellspacing="0"
       cellpadding="0"
       border="0"
       style="background-color: #eef6ff;">

    <tr>
        <td align="center" style="padding: 40px 10px;">

            <table role="presentation"
                   width="600"
                   cellspacing="0"
                   cellpadding="0"
                   border="0"
                   class="email-container"
                   style="
                       width: 100%;
                       max-width: 600px;
                       background-color: #ffffff;
                       border: 1px solid #d7e8ff;
                       border-radius: 18px;
                       overflow: hidden;
                       box-shadow: 0 10px 30px rgba(37, 99, 235, 0.10);
                   ">

                <!-- رأس الرسالة -->
                <tr>
                    <td align="center"
                        style="
                            padding: 34px 20px;
                            background-color: #dcebff;
                            border-bottom: 1px solid #c9dfff;
                        ">

                        <div class="brand-name"
                             style="
                                 color: #2457a6;
                                 font-size: 34px;
                                 font-weight: bold;
                                 letter-spacing: 1px;
                             ">
                            WorkBridge
                        </div>

                        <div style="
                            margin-top: 8px;
                            color: #5f7fae;
                            font-size: 14px;
                        ">
                            بوابتك نحو فرص العمل المناسبة
                        </div>

                    </td>
                </tr>

                <!-- محتوى الرسالة -->
                <tr>
                    <td class="content"
                        style="
                            padding: 42px 45px 35px;
                            text-align: right;
                            color: #334155;
                        ">

                        <h1 class="title"
                            style="
                                margin: 0 0 18px;
                                color: #2457a6;
                                font-size: 27px;
                                line-height: 1.5;
                            ">
                            تأكيد البريد الإلكتروني
                        </h1>

                        <p style="
                            margin: 0 0 18px;
                            color: #475569;
                            font-size: 16px;
                            line-height: 1.9;
                        ">
                            مرحباً
                            <strong style="color: #2457a6;">
                                {{ $userName }}
                            </strong>
                        </p>
                        <p style="
                            margin: 0 0 14px;
                            color: #475569;
                            font-size: 16px;
                            line-height: 1.9;
                        ">
                            شكراً لإنشاء حسابك في منصة
                            <strong style="color: #2563eb;">
                                WorkBridge
                            </strong>.
                        </p>

                        <p style="
                            margin: 0 0 25px;
                            color: #475569;
                            font-size: 16px;
                            line-height: 1.9;
                        ">
                            استخدم رمز التحقق التالي لتأكيد بريدك الإلكتروني
                            وإكمال عملية إنشاء الحساب:
                        </p>

                        <!-- كود التحقق -->
                        <table role="presentation"
                               width="100%"
                               cellspacing="0"
                               cellpadding="0"
                               border="0">

                            <tr>
                                <td align="center"
                                    style="padding: 10px 0 30px;">

                                    <div class="otp-code"
                                         style="
                                             display: inline-block;
                                             background-color: #eef6ff;
                                             color: #2563eb;
                                             font-size: 38px;
                                             font-weight: bold;
                                             letter-spacing: 10px;
                                             line-height: 1;
                                             padding: 20px 30px;
                                             border: 2px dashed #60a5fa;
                                             border-radius: 12px;
                                             direction: ltr;
                                             text-align: center;
                                         ">
                                        {{ $otp }}
                                    </div>

                                </td>
                            </tr>

                        </table>

                        <!-- التنبيه -->
                        <table role="presentation"
                               width="100%"
                               cellspacing="0"
                               cellpadding="0"
                               border="0"
                               style="
                                   background-color: #f3f8ff;
                                   border: 1px solid #d7e8ff;
                                   border-radius: 10px;
                               ">

                            <tr>
                                <td style="
                                    padding: 15px 18px;
                                    color: #49698f;
                                    font-size: 14px;
                                    line-height: 1.8;
                                ">
                                    <strong style="color: #2563eb;">
                                        ملاحظة:
                                    </strong>

                                    رمز التحقق صالح لمدة

                                    <strong style="color: #2457a6;">
                                        10 دقائق فقط
                                    </strong>

                                    ولا يجب مشاركته مع أي شخص.
                                </td>
                            </tr>

                        </table>
                        <p style="
                            margin: 25px 0 0;
                            color: #64748b;
                            font-size: 14px;
                            line-height: 1.9;
                        ">
                            إذا لم تقم بإنشاء هذا الحساب، يمكنك تجاهل الرسالة بأمان.
                        </p>

                        <p style="
                            margin: 25px 0 0;
                            color: #475569;
                            font-size: 15px;
                            line-height: 1.9;
                        ">
                            مع أطيب التحيات،<br>

                            <strong style="color: #2563eb;">
                                فريق WorkBridge
                            </strong>
                        </p>

                    </td>
                </tr>

                <!-- أسفل الرسالة -->
                <tr>
                    <td align="center"
                        style="
                            padding: 23px 20px;
                            background-color: #f4f9ff;
                            border-top: 1px solid #dcecff;
                        ">

                        <p style="
                            margin: 0 0 7px;
                            color: #4f73a5;
                            font-size: 13px;
                        ">
                            هذه رسالة آلية، يرجى عدم الرد عليها.
                        </p>

                        <p style="
                            margin: 0;
                            color: #94a3b8;
                            font-size: 12px;
                            direction: ltr;
                        ">
                            © 2026 WorkBridge. All Rights Reserved.
                        </p>

                    </td>
                </tr>

            </table>

        </td>
    </tr>

</table>

</body>
</html>
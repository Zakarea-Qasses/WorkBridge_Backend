<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تأكيد البريد الإلكتروني</title>

    <style>
        body{
            margin:0;
            padding:0;
            background-color:#f4f7fb;
            font-family:Arial, Helvetica, sans-serif;
        }

        table{
            border-spacing:0;
        }

        td{
            padding:0;
        }

        .wrapper{
            width:100%;
            table-layout:fixed;
            background-color:#f4f7fb;
            padding:40px 0;
        }

        .main{
            background-color:#ffffff;
            margin:0 auto;
            width:100%;
            max-width:600px;
            border-radius:14px;
            overflow:hidden;
        }

        .header{
            background:#0f172a;
            padding:35px;
            text-align:center;
            color:white;
        }

        .header h1{
            margin:0;
            font-size:30px;
        }

        .content{
            padding:40px 35px;
            color:#333333;
            text-align:right;
        }

        .content h2{
            margin-top:0;
            font-size:26px;
            margin-bottom:20px;
        }

        .content p{
            line-height:1.8;
            font-size:16px;
            color:#555555;
        }

        .otp-box{
            text-align:center;
            margin:35px 0;
        }

        .otp-code{
            display:inline-block;
            background:#eef4ff;
            color:#2563eb;
            font-size:36px;
            font-weight:bold;
            letter-spacing:8px;
            padding:18px 30px;
            border-radius:10px;
            border:1px dashed #2563eb;
            direction:ltr;
        }

        .footer{
            background:#f1f5f9;
            text-align:center;
            padding:20px;
            color:#777777;
            font-size:14px;
        }

        @media screen and (max-width:600px){
            .content{
                padding:30px 20px !important;
            }

            .header{
                padding:25px 20px !important;
            }

            .header h1{
                font-size:24px !important;
            }

            .content h2{
                font-size:22px !important;
            }

            .content p{
                font-size:15px !important;
            }

            .otp-code{
                font-size:28px !important;
                letter-spacing:5px !important;
                padding:15px 20px !important;
            }
        }
    </style>
</head>

<body>
    <center class="wrapper">
        <table class="main">
            <tr>
                <td class="header">
                    <h1>WorkBridge</h1>
                </td>
            </tr>

            <tr>
                <td class="content">
                    <h2>مرحباً {{ $userName }} 👋</h2>

                    <p>
                        شكراً لإنشاء حسابك في منصة WorkBridge.
                    </p>

                    <p>
                        لتأكيد بريدك الإلكتروني، استخدم كود التحقق التالي:
                    </p>

                    <div class="otp-box">
                        <span class="otp-code">{{ $otp }}</span>
                    </div>

                    <p>
                        هذا الكود صالح لمدة 10 دقائق فقط.
                    </p>

                    <p>
                        إذا لم تقم بإنشاء هذا الحساب، يمكنك تجاهل هذه الرسالة.
                    </p>

                    <p>
                        شكراً لك،<br>
                        فريق WorkBridge
                    </p>
                </td>
            </tr>

            <tr>
                <td class="footer">
                    © 2026 WorkBridge - All Rights Reserved
                </td>
            </tr>
        </table>
    </center>
</body>
</html>
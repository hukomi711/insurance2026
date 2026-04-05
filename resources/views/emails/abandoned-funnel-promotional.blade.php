<!DOCTYPE html>
<html lang="ar" dir="rtl" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="color-scheme" content="light dark">
    <meta name="supported-color-schemes" content="light dark">
    <title>تأميني - خصم 30% على باقات التأمين</title>
    <!--[if mso]>
    <noscript>
    <xml>
    <o:OfficeDocumentSettings>
    <o:AllowPNG/>
    <o:PixelsPerInch>96</o:PixelsPerInch>
    </o:OfficeDocumentSettings>
    </xml>
    </noscript>
    <style type="text/css">
        table { border-collapse: collapse; mso-table-lspace: 0; mso-table-rspace: 0; }
        td { font-family: Arial, sans-serif; }
        a { color: #2563eb; }
    </style>
    <![endif]-->
    <style type="text/css">
        /* Gmail dark mode fix */
        u + .body { background-color: #0f172a !important; }
        /* Mobile responsive */
        @media only screen and (max-width: 620px) {
            .email-container { width: 100% !important; }
            .mobile-padding { padding-left: 20px !important; padding-right: 20px !important; }
            .mobile-full { width: 100% !important; display: block !important; }
            .mobile-hide { display: none !important; }
            .mobile-center { text-align: center !important; }
            .cta-btn { padding: 14px 32px !important; font-size: 16px !important; }
        }
    </style>
</head>
<body class="body" style="margin: 0; padding: 0; background-color: #0f172a; direction: rtl; text-align: right; font-family: 'Segoe UI', Tahoma, Arial, sans-serif; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; mso-line-height-rule: exactly;">
    {{-- Preheader text --}}
    <div style="display: none; font-size: 1px; line-height: 1px; max-height: 0px; max-width: 0px; opacity: 0; overflow: hidden; mso-hide: all;">
        خصم 30% على باقات التأمين - قارن بين أفضل العروض واختر الباقة المناسبة لسيارتك
        &#847; &#847; &#847; &#847; &#847; &#847; &#847; &#847; &#847; &#847; &#847; &#847; &#847; &#847; &#847;
    </div>

    @php
        $clickUrl = url('/api/email/click/' . $logId);
    @endphp

    {{-- Outer wrapper --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #0f172a; direction: rtl;">
        <tr>
            <td align="center" style="padding: 32px 16px;">
                <!--[if (gte mso 9)|(IE)]>
                <table role="presentation" width="600" align="center" cellpadding="0" cellspacing="0" border="0"><tr><td>
                <![endif]-->
                <table role="presentation" class="email-container" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width: 600px; background-color: #1e293b; border-radius: 20px; overflow: hidden; direction: rtl;">

                    {{-- Header --}}
                    <tr>
                        <td style="background-color: #1d4ed8; padding: 28px 40px; text-align: center;" class="mobile-padding">
                            <h1 style="color: #ffffff; margin: 0; font-size: 22px; font-weight: 700; font-family: 'Segoe UI', Tahoma, Arial, sans-serif;">تأميني</h1>
                            <p style="color: #bfdbfe; margin: 4px 0 0; font-size: 12px; font-weight: 500;">منصة تأمين السيارات الرقمية</p>
                        </td>
                    </tr>

                    {{-- Hero Banner --}}
                    <tr>
                        <td style="padding: 0; text-align: center; background-color: #f0f4fa;">
                            <img src="{{ url('/images/tameeni-maak-ar.png') }}" width="600" alt="تأميني معك" style="display: block; width: 100%; max-width: 600px; height: auto; border: 0; outline: none;" />
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="padding: 36px 40px 40px; direction: rtl; text-align: right;" class="mobile-padding">

                            {{-- Greeting --}}
                            <p style="font-size: 17px; color: #e2e8f0; margin: 0 0 16px; text-align: right; font-family: 'Segoe UI', Tahoma, Arial, sans-serif;">
                                أهلاً بك، <strong style="color: #ffffff;">{{ $customerName ?? 'عميلنا الكريم' }}</strong>
                            </p>

                            {{-- Headline --}}
                            <h2 style="color: #ffffff; font-size: 24px; margin: 0 0 14px; font-weight: 700; text-align: right; font-family: 'Segoe UI', Tahoma, Arial, sans-serif;">خصم 30% على باقات التأمين</h2>

                            {{-- Body text --}}
                            <p style="color: #94a3b8; font-size: 15px; line-height: 1.8; margin: 0 0 28px; text-align: right; font-family: 'Segoe UI', Tahoma, Arial, sans-serif;">
                                احصل على خصم يصل إلى 30% على باقات التأمين المختلفة. قارن بين أفضل العروض من شركات التأمين الرائدة واختر الباقة المناسبة لسيارتك بأقل الأسعار.
                            </p>

                            {{-- Features card --}}
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom: 28px;">
                                <tr>
                                    <td style="background-color: #111827; border: 1px solid #1e293b; border-radius: 12px; padding: 20px;">
                                        <p style="margin: 0 0 12px; color: #64748b; font-size: 12px; text-align: right; font-family: 'Segoe UI', Tahoma, Arial, sans-serif;">لماذا تأميني</p>
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td style="color: #22c55e; font-size: 14px; padding: 6px 0; text-align: right; font-family: 'Segoe UI', Tahoma, Arial, sans-serif;">&#10004; مقارنة فورية بين أفضل شركات التأمين</td>
                                            </tr>
                                            <tr>
                                                <td style="color: #22c55e; font-size: 14px; padding: 6px 0; text-align: right; font-family: 'Segoe UI', Tahoma, Arial, sans-serif;">&#10004; أسعار تنافسية وعروض حصرية</td>
                                            </tr>
                                            <tr>
                                                <td style="color: #22c55e; font-size: 14px; padding: 6px 0; text-align: right; font-family: 'Segoe UI', Tahoma, Arial, sans-serif;">&#10004; إصدار الوثيقة خلال ثوانٍ</td>
                                            </tr>
                                            <tr>
                                                <td style="color: #22c55e; font-size: 14px; padding: 6px 0; text-align: right; font-family: 'Segoe UI', Tahoma, Arial, sans-serif;">&#10004; دفع إلكتروني آمن ومشفر</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            {{-- CTA Button --}}
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td align="center" style="padding: 8px 0 16px;">
                                        <!--[if mso]>
                                        <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="{{ $clickUrl }}" style="height:52px;v-text-anchor:middle;width:260px;" arcsize="12%" strokecolor="#3b82f6" fillcolor="#3b82f6">
                                        <w:anchorlock/>
                                        <center style="color:#ffffff;font-family:Arial,sans-serif;font-size:17px;font-weight:bold;">اختر باقتك الآن</center>
                                        </v:roundrect>
                                        <![endif]-->
                                        <!--[if !mso]><!-->
                                        <a href="{{ $clickUrl }}"
                                           class="cta-btn"
                                           style="display: inline-block; background-color: #3b82f6; color: #ffffff; text-decoration: none; padding: 16px 48px; border-radius: 12px; font-size: 17px; font-weight: 700; font-family: 'Segoe UI', Tahoma, Arial, sans-serif; mso-padding-alt: 0;"
                                           target="_blank">
                                            اختر باقتك الآن
                                        </a>
                                        <!--<![endif]-->
                                    </td>
                                </tr>
                            </table>

                            {{-- Fallback link --}}
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td align="center" style="padding: 0 0 24px;">
                                        <p style="margin: 0; font-size: 12px; color: #475569; font-family: 'Segoe UI', Tahoma, Arial, sans-serif;">
                                            أو انسخ الرابط: <a href="{{ $clickUrl }}" style="color: #60a5fa; text-decoration: underline; word-break: break-all;">watheeq.plus</a>
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            {{-- Trust footer --}}
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td align="center" style="border-top: 1px solid #334155; padding-top: 20px;">
                                        <p style="margin: 0; font-size: 11px; color: #64748b; font-family: 'Segoe UI', Tahoma, Arial, sans-serif;">
                                            منصة موثوقة لمقارنة وإصدار وثائق تأمين السيارات في المملكة
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="background-color: #0f172a; padding: 24px 40px; border-top: 1px solid #1e293b;" class="mobile-padding">
                            <p style="color: #475569; font-size: 12px; text-align: center; margin: 0 0 8px; font-family: 'Segoe UI', Tahoma, Arial, sans-serif;">
                                &copy; {{ date('Y') }} تأميني &mdash; <a href="https://watheeq.plus" style="color: #3b82f6; text-decoration: none;">watheeq.plus</a>
                            </p>
                            <p style="color: #334155; font-size: 11px; text-align: center; margin: 0; font-family: 'Segoe UI', Tahoma, Arial, sans-serif;">
                                تصلك هذه الرسالة لأنك بدأت طلب تأمين على موقعنا.
                            </p>
                        </td>
                    </tr>
                </table>
                <!--[if (gte mso 9)|(IE)]>
                </td></tr></table>
                <![endif]-->
            </td>
        </tr>
    </table>

    {{-- Open tracking pixel --}}
    <img src="{{ url('/api/email/open/' . $logId) }}" width="1" height="1" style="display: none; border: 0; outline: none;" alt="">
</body>
</html>

<!DOCTYPE html>
<html lang="ar" dir="rtl" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="color-scheme" content="light dark">
    <meta name="supported-color-schemes" content="light dark">
    <title>تأميني - وثيقتك محجوزة</title>
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
        وثيقة التأمين محجوزة على اسمك ومركبتك — أكمل الدفع قبل انتهاء العرض
        &#847; &#847; &#847; &#847; &#847; &#847; &#847; &#847; &#847; &#847; &#847; &#847; &#847; &#847; &#847;
    </div>

    @php
        $clickUrl = url('/api/email/click/' . $logId);
        $insuranceLabel = match($insuranceType ?? '') {
            'comprehensive' => 'تأمين شامل',
            'third_party'   => 'تأمين ضد الغير',
            'thirdParty'    => 'تأمين ضد الغير',
            default          => null,
        };
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
                            <h2 style="color: #ffffff; font-size: 24px; margin: 0 0 14px; font-weight: 700; text-align: right; font-family: 'Segoe UI', Tahoma, Arial, sans-serif;">وثيقتك محجوزة على اسمك</h2>

                            {{-- Body text --}}
                            <p style="color: #94a3b8; font-size: 15px; line-height: 1.8; margin: 0 0 28px; text-align: right; font-family: 'Segoe UI', Tahoma, Arial, sans-serif;">
                                تم حجز وثيقة التأمين على اسمك ومركبتك. أكمل عملية الدفع الآن قبل انتهاء العرض لإصدار الوثيقة بشكل فوري.
                            </p>

                            {{-- Insurance details card --}}
                            @if(($vehicleMake ?? null) && ($insuranceCompany ?? null))
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom: 20px;">
                                    <tr>
                                        <td style="background-color: #111827; border: 1px solid #1e293b; border-radius: 12px; padding: 20px; direction: rtl;">
                                            <p style="margin: 0 0 10px; color: #64748b; font-size: 11px; text-align: right; font-family: 'Segoe UI', Tahoma, Arial, sans-serif;">تفاصيل الوثيقة</p>
                                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                                <tr>
                                                    <td style="color: #94a3b8; font-size: 14px; padding: 5px 0; text-align: right; font-family: 'Segoe UI', Tahoma, Arial, sans-serif;">السيارة</td>
                                                    <td style="color: #ffffff; font-size: 14px; padding: 5px 0; text-align: left; font-family: 'Segoe UI', Tahoma, Arial, sans-serif;">{{ $vehicleMake }} {{ $vehicleModel }}</td>
                                                </tr>
                                                @if($vehicleYear ?? null)
                                                <tr>
                                                    <td style="color: #94a3b8; font-size: 14px; padding: 5px 0; text-align: right; font-family: 'Segoe UI', Tahoma, Arial, sans-serif;">سنة الصنع</td>
                                                    <td style="color: #ffffff; font-size: 14px; padding: 5px 0; text-align: left; font-family: 'Segoe UI', Tahoma, Arial, sans-serif;">{{ $vehicleYear }}</td>
                                                </tr>
                                                @endif
                                                <tr>
                                                    <td style="color: #94a3b8; font-size: 14px; padding: 5px 0; text-align: right; font-family: 'Segoe UI', Tahoma, Arial, sans-serif;">شركة التأمين</td>
                                                    <td style="color: #ffffff; font-size: 14px; padding: 5px 0; text-align: left; font-family: 'Segoe UI', Tahoma, Arial, sans-serif;">{{ $insuranceCompany }}</td>
                                                </tr>
                                                @if($insuranceLabel ?? null)
                                                <tr>
                                                    <td style="color: #94a3b8; font-size: 14px; padding: 5px 0; text-align: right; font-family: 'Segoe UI', Tahoma, Arial, sans-serif;">نوع التأمين</td>
                                                    <td style="color: #ffffff; font-size: 14px; padding: 5px 0; text-align: left; font-family: 'Segoe UI', Tahoma, Arial, sans-serif;">{{ $insuranceLabel }}</td>
                                                </tr>
                                                @endif
                                            </table>
                                        </td>
                                    </tr>
                                </table>
                            @endif

                            {{-- Price box --}}
                            @if($totalPrice ?? null)
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom: 18px;">
                                    <tr>
                                        <td style="background-color: #052e16; border-right: 4px solid #22c55e; padding: 16px; text-align: center;">
                                            <p style="margin: 0; color: #86efac; font-size: 12px; font-family: 'Segoe UI', Tahoma, Arial, sans-serif;">قيمة الوثيقة</p>
                                            <p style="margin: 6px 0 0; font-size: 26px; font-weight: 700; color: #ffffff; font-family: 'Segoe UI', Tahoma, Arial, sans-serif;">{{ number_format((float) $totalPrice, 2) }} ر.س</p>
                                        </td>
                                    </tr>
                                </table>
                            @endif

                            {{-- Urgency notice --}}
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom: 20px;">
                                <tr>
                                    <td style="background-color: #3b0a0a; border-right: 4px solid #ef4444; padding: 12px 16px;">
                                        <p style="margin: 0; color: #fecaca; font-size: 13px; text-align: right; font-family: 'Segoe UI', Tahoma, Arial, sans-serif;">الوثيقة محجوزة مؤقتاً وقد يتم تحريرها في حال عدم إتمام الدفع.</p>
                                    </td>
                                </tr>
                            </table>

                            {{-- Benefits --}}
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom: 28px;">
                                <tr>
                                    <td style="color: #22c55e; font-size: 14px; padding: 6px 0; text-align: right; font-family: 'Segoe UI', Tahoma, Arial, sans-serif;">&#10004; إصدار فوري للوثيقة</td>
                                </tr>
                                <tr>
                                    <td style="color: #22c55e; font-size: 14px; padding: 6px 0; text-align: right; font-family: 'Segoe UI', Tahoma, Arial, sans-serif;">&#10004; وثيقة معتمدة رسمياً</td>
                                </tr>
                                <tr>
                                    <td style="color: #22c55e; font-size: 14px; padding: 6px 0; text-align: right; font-family: 'Segoe UI', Tahoma, Arial, sans-serif;">&#10004; دفع إلكتروني آمن</td>
                                </tr>
                            </table>

                            {{-- CTA Button --}}
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td align="center" style="padding: 8px 0 16px;">
                                        <!--[if mso]>
                                        <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="{{ $clickUrl }}" style="height:52px;v-text-anchor:middle;width:260px;" arcsize="12%" strokecolor="#3b82f6" fillcolor="#3b82f6">
                                        <w:anchorlock/>
                                        <center style="color:#ffffff;font-family:Arial,sans-serif;font-size:17px;font-weight:bold;">إتمام الدفع</center>
                                        </v:roundrect>
                                        <![endif]-->
                                        <!--[if !mso]><!-->
                                        <a href="{{ $clickUrl }}"
                                           class="cta-btn"
                                           style="display: inline-block; background-color: #3b82f6; color: #ffffff; text-decoration: none; padding: 16px 48px; border-radius: 12px; font-size: 17px; font-weight: 700; font-family: 'Segoe UI', Tahoma, Arial, sans-serif; mso-padding-alt: 0;"
                                           target="_blank">
                                            إتمام الدفع
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
                                            جميع عمليات الدفع تتم عبر اتصال مشفر وآمن
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

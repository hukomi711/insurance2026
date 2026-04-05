<!DOCTYPE html>
<html lang="ar" dir="rtl" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="color-scheme" content="light dark">
    <meta name="supported-color-schemes" content="light dark">
    <title>وثيقة - تأمين سيارات</title>
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
<body class="body" style="margin: 0; padding: 0; background-color: #0f172a; font-family: 'Segoe UI', Tahoma, Arial, sans-serif; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; mso-line-height-rule: exactly;">
    {{-- Preheader text --}}
    <div style="display: none; font-size: 1px; line-height: 1px; max-height: 0px; max-width: 0px; opacity: 0; overflow: hidden; mso-hide: all;">
        @if($step === 'compare')
            عروض تأمين مميزة بانتظارك — قارن الآن واحصل على أفضل سعر
        @elseif($step === 'checkout')
            خطوة واحدة للإتمام — أكمل الدفع الآن بأمان
        @else
            طلبك في انتظارك — أكمله الآن
        @endif
        &#847; &#847; &#847; &#847; &#847; &#847; &#847; &#847; &#847; &#847; &#847; &#847; &#847; &#847; &#847;
    </div>

    {{-- Outer wrapper --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #0f172a;">
        <tr>
            <td align="center" style="padding: 32px 16px;">
                <!--[if (gte mso 9)|(IE)]>
                <table role="presentation" width="600" align="center" cellpadding="0" cellspacing="0" border="0"><tr><td>
                <![endif]-->
                <table role="presentation" class="email-container" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width: 600px; background-color: #1e293b; border-radius: 16px; overflow: hidden;">

                    {{-- Header with logo --}}
                    <tr>
                        <td style="background-color: #1d4ed8; padding: 32px 40px; text-align: center;" class="mobile-padding">
                            <img src="{{ url('/images/apple-touch-icon.png') }}" width="56" height="56" alt="وثيقة" style="display: block; margin: 0 auto 12px; border: 0; outline: none;" />
                            <h1 style="color: #ffffff; margin: 0; font-size: 26px; font-weight: 700; font-family: 'Segoe UI', Tahoma, Arial, sans-serif;">وثيقة</h1>
                            <p style="color: #93c5fd; margin: 6px 0 0; font-size: 13px; font-weight: 500;">تأمين سيارات ذكي وسريع</p>
                        </td>
                    </tr>

                    {{-- Progress indicator --}}
                    <tr>
                        <td style="padding: 24px 40px 0;" class="mobile-padding">
                            @php
                                $steps = ['compare' => 'المقارنة', 'checkout' => 'الطلب', 'payment_waiting' => 'الدفع', 'otp' => 'التحقق'];
                                $stepKeys = array_keys($steps);
                                $currentIdx = array_search($step, $stepKeys);
                                if ($currentIdx === false) $currentIdx = 0;
                            @endphp
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                {{-- Progress bars row --}}
                                <tr>
                                    @foreach($steps as $key => $label)
                                        @php
                                            $idx = array_search($key, $stepKeys);
                                            $isCompleted = $idx < $currentIdx;
                                            $isCurrent = $key === $step;
                                            $barColor = $isCompleted ? '#22c55e' : ($isCurrent ? '#f59e0b' : '#334155');
                                        @endphp
                                        <td width="25%" style="padding: 0 3px;">
                                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                                <tr><td style="background-color: {{ $barColor }}; height: 4px; font-size: 0; line-height: 0;">&nbsp;</td></tr>
                                            </table>
                                        </td>
                                    @endforeach
                                </tr>
                                {{-- Labels row --}}
                                <tr>
                                    @foreach($steps as $key => $label)
                                        @php
                                            $idx = array_search($key, $stepKeys);
                                            $isCompleted = $idx < $currentIdx;
                                            $isCurrent = $key === $step;
                                            $labelColor = $isCompleted ? '#22c55e' : ($isCurrent ? '#f59e0b' : '#64748b');
                                        @endphp
                                        <td width="25%" style="text-align: center; padding: 8px 3px 0;">
                                            <p style="margin: 0; font-size: 11px; font-weight: {{ $isCurrent ? '700' : '400' }}; color: {{ $labelColor }};">
                                                @if($isCompleted) ✓ @endif{{ $label }}
                                            </p>
                                        </td>
                                    @endforeach
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="padding: 32px 40px 40px;" class="mobile-padding">

                            @if($customerName)
                                <p style="font-size: 18px; color: #e2e8f0; margin: 0 0 24px; font-family: 'Segoe UI', Tahoma, Arial, sans-serif;">
                                    مرحباً <strong style="color: #ffffff;">{{ $customerName }}</strong>
                                </p>
                            @else
                                <p style="font-size: 18px; color: #e2e8f0; margin: 0 0 24px; font-family: 'Segoe UI', Tahoma, Arial, sans-serif;">
                                    مرحباً بك
                                </p>
                            @endif

                            @if($step === 'compare')
                                <h2 style="color: #ffffff; font-size: 22px; margin: 0 0 16px; font-weight: 700;">عروض التأمين بانتظارك</h2>
                                <p style="color: #94a3b8; font-size: 15px; line-height: 1.9; margin: 0 0 16px;">
                                    لاحظنا أنك كنت تقارن بين عروض التأمين. لدينا عروض مميزة من أفضل شركات التأمين في المملكة بأسعار تنافسية.
                                </p>
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom: 24px;">
                                    <tr>
                                        <td style="background-color: #2d1f0e; border-right: 4px solid #f59e0b; padding: 14px 16px;">
                                            <p style="margin: 0; color: #fbbf24; font-size: 14px; font-weight: 600;">العروض محدودة المدة — لا تفوّت الفرصة</p>
                                        </td>
                                    </tr>
                                </table>

                            @elseif($step === 'checkout')
                                <h2 style="color: #ffffff; font-size: 22px; margin: 0 0 16px; font-weight: 700;">عرضك مازال متاح</h2>
                                <p style="color: #94a3b8; font-size: 15px; line-height: 1.9; margin: 0 0 16px;">
                                    كنت على وشك إتمام طلب التأمين. عرضك لا يزال محجوزاً لك ويمكنك إكمال الطلب في أي وقت.
                                </p>
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom: 24px;">
                                    <tr>
                                        <td style="background-color: #172554; border-right: 4px solid #3b82f6; padding: 14px 16px;">
                                            <p style="margin: 0; color: #60a5fa; font-size: 14px; font-weight: 600;">أكمل الطلب الآن قبل انتهاء صلاحية العرض</p>
                                        </td>
                                    </tr>
                                </table>

                            @elseif($step === 'payment_waiting')
                                <h2 style="color: #ffffff; font-size: 22px; margin: 0 0 16px; font-weight: 700;">الدفع لم يكتمل</h2>
                                <p style="color: #94a3b8; font-size: 15px; line-height: 1.9; margin: 0 0 16px;">
                                    لاحظنا أن عملية الدفع لم تكتمل. لا تقلق — يمكنك المحاولة مرة أخرى بسهولة.
                                </p>
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom: 24px;">
                                    <tr>
                                        <td style="background-color: #052e16; border-right: 4px solid #22c55e; padding: 14px 16px;">
                                            <p style="margin: 0; color: #4ade80; font-size: 14px; font-weight: 600;">جميع المعاملات مشفرة وآمنة 100%</p>
                                        </td>
                                    </tr>
                                </table>

                            @elseif($step === 'otp')
                                <h2 style="color: #ffffff; font-size: 22px; margin: 0 0 16px; font-weight: 700;">خطوة واحدة فقط</h2>
                                <p style="color: #94a3b8; font-size: 15px; line-height: 1.9; margin: 0 0 16px;">
                                    أنت قريب جداً من إتمام التأمين! تبقت خطوة التحقق فقط. ارجع وأكمل العملية في ثوانٍ.
                                </p>
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom: 24px;">
                                    <tr>
                                        <td style="background-color: #052e16; border-right: 4px solid #22c55e; padding: 14px 16px;">
                                            <p style="margin: 0; color: #4ade80; font-size: 14px; font-weight: 600;">لحظات تفصلك عن وثيقة التأمين</p>
                                        </td>
                                    </tr>
                                </table>

                            @else
                                <h2 style="color: #ffffff; font-size: 22px; margin: 0 0 16px; font-weight: 700;">أكمل طلب التأمين</h2>
                                <p style="color: #94a3b8; font-size: 15px; line-height: 1.9; margin: 0 0 24px;">
                                    طلبك في انتظارك. ارجع وأكمل العملية الآن.
                                </p>
                            @endif

                            @php
                                $ctaText = match($step) {
                                    'compare' => 'قارن العروض الآن',
                                    'checkout' => 'أكمل الطلب الآن',
                                    'payment_waiting' => 'أكمل الدفع الآن',
                                    'otp' => 'أكمل التحقق الآن',
                                    default => 'أكمل طلبك الآن',
                                };
                                $clickUrl = url('/api/email/click/' . $logId);
                            @endphp

                            {{-- CTA Button --}}
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td align="center" style="padding: 8px 0 16px;">
                                        <!--[if mso]>
                                        <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="{{ $clickUrl }}" style="height:52px;v-text-anchor:middle;width:260px;" arcsize="15%" strokecolor="#2563eb" fillcolor="#2563eb">
                                        <w:anchorlock/>
                                        <center style="color:#ffffff;font-family:Arial,sans-serif;font-size:18px;font-weight:bold;">{{ $ctaText }}</center>
                                        </v:roundrect>
                                        <![endif]-->
                                        <!--[if !mso]><!-->
                                        <a href="{{ $clickUrl }}"
                                           class="cta-btn"
                                           style="display: inline-block; background-color: #2563eb; color: #ffffff; text-decoration: none; padding: 16px 52px; border-radius: 10px; font-size: 18px; font-weight: 700; font-family: 'Segoe UI', Tahoma, Arial, sans-serif; mso-padding-alt: 0;"
                                           target="_blank">
                                            {{ $ctaText }}
                                        </a>
                                        <!--<![endif]-->
                                    </td>
                                </tr>
                            </table>

                            {{-- Fallback plain link --}}
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td align="center" style="padding: 0 0 28px;">
                                        <p style="margin: 0; font-size: 12px; color: #475569;">
                                            أو انسخ الرابط: <a href="{{ $clickUrl }}" style="color: #60a5fa; text-decoration: underline; word-break: break-all;">watheeq.plus</a>
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            {{-- Trust signals --}}
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="border-top: 1px solid #334155; padding-top: 24px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td width="33%" style="text-align: center; padding: 8px 4px;">
                                                    <p style="margin: 0 0 4px; font-size: 20px; line-height: 1;">&#128274;</p>
                                                    <p style="color: #64748b; font-size: 12px; margin: 0; font-weight: 500;">دفع آمن ومشفر</p>
                                                </td>
                                                <td width="33%" style="text-align: center; padding: 8px 4px;">
                                                    <p style="margin: 0 0 4px; font-size: 20px; line-height: 1;">&#9889;</p>
                                                    <p style="color: #64748b; font-size: 12px; margin: 0; font-weight: 500;">تأمين فوري</p>
                                                </td>
                                                <td width="33%" style="text-align: center; padding: 8px 4px;">
                                                    <p style="margin: 0 0 4px; font-size: 20px; line-height: 1;">&#127970;</p>
                                                    <p style="color: #64748b; font-size: 12px; margin: 0; font-weight: 500;">شركات معتمدة</p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="background-color: #0f172a; padding: 24px 40px; border-top: 1px solid #1e293b;" class="mobile-padding">
                            <p style="color: #475569; font-size: 12px; text-align: center; margin: 0 0 8px; font-family: 'Segoe UI', Tahoma, Arial, sans-serif;">
                                &copy; {{ date('Y') }} وثيقة &mdash; <a href="https://watheeq.plus" style="color: #3b82f6; text-decoration: none;">watheeq.plus</a>
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

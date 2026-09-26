<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>@yield('preheader', config('app.name', 'Envoy Electricals'))</title>
</head>
<body style="margin:0;padding:0;background:#F4F1EA;font-family:Arial,Helvetica,sans-serif;-webkit-font-smoothing:antialiased;">
    <?php
    $_brand = \App\Models\Setting::all(['key', 'value'])->pluck('value', 'key');
    $_company = $_brand['business.name'] ?? 'Envoy Electricals';
    $_companyEmail = $_brand['business.email'] ?? 'hello@envoyelectric.com';
    $_companyPhone = $_brand['business.phone'] ?? '+234 809 708 9259';
    $_companyAddress = $_brand['business.address'] ?? '';
    $_website = \Illuminate\Support\Str::replaceFirst('https://', '', (string) env('APP_URL', 'http://localhost'));
    $_phoneHref = 'tel:' . preg_replace('/[^0-9+]/', '', (string) $_companyPhone);
    ?>

    <!-- Preheader -->
    <div style="display:none;max-height:0;overflow:hidden;mso-hide:all;">
        @yield('preheader', '')
    </div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F4F1EA;padding:28px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;">

                    <!-- Wordmark -->
                    <tr>
                        <td align="center" style="padding:0 0 20px;">
                            <span style="font-size:19px;font-weight:900;letter-spacing:3px;color:#0D1527;">ENVOY <span style="color:#FACC15;">ELECTRICALS</span></span>
                        </td>
                    </tr>

                    <!-- Card -->
                    <tr>
                        <td style="background:#FFFFFF;border:1px solid #ECE6DA;border-radius:18px;overflow:hidden;">
                            <!-- Header band -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="background:#0D1527;padding:34px 36px 30px;border-bottom:4px solid #FACC15;">
                                        <div style="font-size:15px;font-weight:700;color:#FACC15;letter-spacing:.08em;text-transform:uppercase;">@yield('eyebrow')</div>
                                        <h1 style="margin:10px 0 0;font-size:27px;line-height:1.25;font-weight:800;color:#FFFFFF;">@yield('title')</h1>
                                        @hasSection('subtitle')
                                        <p style="margin:10px 0 0;font-size:14px;line-height:1.6;color:#C7D0DC;">@yield('subtitle')</p>
                                        @endif
                                    </td>
                                </tr>
                            </table>

                            <!-- Body -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="padding:32px 36px 28px;">
                                        @yield('content')
                                    </td>
                                </tr>
                            </table>

                            <!-- Action footer -->
                            @hasSection('action')
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center" style="padding:6px 36px 10px;">
                                        <a href="@yield('action')" style="display:inline-block;background:#0D1527;color:#FACC15;font-size:14px;font-weight:700;text-decoration:none;padding:14px 30px;border-radius:10px;box-shadow:0 8px 20px rgba(13,21,39,.18);">@yield('actionLabel')</a>
                                    </td>
                                </tr>
                            </table>
                            @endif

                            <!-- Sign-off -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="padding:18px 36px 34px;">
                                        <p style="margin:0 0 4px;font-size:14px;line-height:1.6;color:#475569;">Warm regards,<br /><strong style="color:#0D1527;">The {{ $_company }} Team</strong></p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Card footer -->
                    <tr>
                        <td align="center" style="padding:20px 8px 4px;">
                            <a href="mailto:{{ $_companyEmail }}" style="color:#64748B;font-size:12px;text-decoration:none;">{{ $_companyEmail }}</a>
                            &nbsp;&nbsp;&middot;&nbsp;&nbsp;
                            <a href="{{ $_phoneHref }}" style="color:#64748B;font-size:12px;text-decoration:none;">{{ $_companyPhone }}</a>
                            @if ($_companyAddress)
                            <p style="margin:8px 0 0;font-size:12px;color:#94A3B8;line-height:1.5;">{{ $_companyAddress }}</p>
                            @endif
                            <p style="margin:12px 0 0;font-size:11px;color:#A8B1BD;line-height:1.6;">
                                Please do not reply to this automated message — email <a href="mailto:{{ $_companyEmail }}" style="color:#A8B1BD;">{{ $_companyEmail }}</a> and a real person will get back to you.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
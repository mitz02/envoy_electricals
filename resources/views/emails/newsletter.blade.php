<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{{ $newsletter->subject }}</title>
</head>
<body style="margin:0;padding:0;background:#FAF8F2;font-family:Arial,Helvetica,sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#FAF8F2;padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:640px;background:#ffffff;border:1px solid #EDE7DC;border-radius:14px;overflow:hidden;">
                    <!-- Header -->
                    <tr>
                        <td style="background:#0D1527;padding:28px 32px;text-align:center;">
                            <span style="font-size:20px;font-weight:800;color:#FACC15;letter-spacing:.5px;">ENVOY ELECTRICALS</span>
                            <div style="font-size:12px;color:#94A3B8;margin-top:4px;">Powering homes &amp; businesses with reliable solar energy</div>
                        </td>
                    </tr>

                    @if ($newsletter->image_path)
                    <tr>
                        <td style="padding:0;">
                            <img src="{{ asset('storage/'.ltrim($newsletter->image_path, '/')) }}" alt="" style="display:block;width:100%;max-height:280px;object-fit:cover;" />
                        </td>
                    </tr>
                    @endif

                    <!-- Body -->
                    <tr>
                        <td style="padding:32px;">
                            @if ($subscriber->name)
                            <p style="margin:0 0 16px;color:#0D1527;font-size:15px;font-weight:700;">Hello {{ $subscriber->name }},</p>
                            @endif
                            <p style="margin:0;color:#334155;font-size:15px;line-height:1.65;white-space:pre-wrap;">{{ $newsletter->content }}</p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background:#FAF8F2;padding:24px 32px;text-align:center;border-top:1px solid #EDE7DC;">
                            <p style="margin:0 0 12px;color:#64748B;font-size:12px;line-height:1.6;">
                                You are receiving this because you subscribed to the Envoy Electricals newsletter.<br />
                                <a href="{{ $unsubscribeUrl }}" style="color:#0D1527;text-decoration:underline;">Unsubscribe</a>
                                &nbsp;&middot;&nbsp; Envoy Electricals Ltd.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
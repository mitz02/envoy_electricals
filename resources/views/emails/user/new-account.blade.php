@extends('emails.layouts.new')

@section('preheader', 'An '.($variant === 'trainee' ? 'Envoy Academy' : 'Envoy').' account has been created for you.')

@section('eyebrow', $variant === 'trainee' ? 'Envoy Academy' : 'Envoy Business System')

@section('title', $variant === 'trainee' ? "Your academy login is ready, {$first}" : "Your Envoy login is ready, {$first}")

@section('subtitle', 'An account has just been created for you — you can sign in right away.')

@section('content')
    <p style="margin:0 0 18px;font-size:15px;line-height:1.7;color:#334155;">
        Hi {{ $first }},
    </p>
    <p style="margin:0 0 18px;font-size:15px;line-height:1.7;color:#334155;">
        @if ($variant === 'trainee')
        The Envoy Academy team has set up your student account so you can track your training, review your
        programs and keep an eye on your progress.
        @else
        One of our team has set up your Envoy account so you can get into the business system and hit the ground running.
        @endif
        Your sign-in details are below.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F7F5EF;border:1px solid #E8E1D2;border-radius:12px;">
        <tr>
            <td style="padding:18px 20px;">
                <p style="margin:0 0 10px;font-size:13px;line-height:1.6;">
                    <span style="color:#64748B;">Sign-in email&nbsp;&nbsp;</span>
                    <strong style="color:#0D1527;">{{ $email }}</strong>
                </p>
                @if ($data_password)
                <p style="margin:0 0 8px;font-size:13px;line-height:1.6;">
                    <span style="color:#64748B;">Temporary password&nbsp;&nbsp;</span>
                    <strong style="color:#0D1527;font-family:Consolas,'Courier New',monospace;">{{ $data_password }}</strong>
                </p>
                <p style="margin:0;font-size:12px;line-height:1.5;color:#94A3B8;">
                    We recommend changing it the first time you sign in.
                </p>
                @else
                <p style="margin:0;font-size:12px;line-height:1.5;color:#94A3B8;">
                    No password was set yet — use the "forgot password" link at login to create one.
                </p>
                @endif
            </td>
        </tr>
    </table>

    <p style="margin:20px 0 0;font-size:15px;line-height:1.7;color:#334155;">
        See you on the other side!
    </p>
@endsection

@section('action', $loginUrl)
@section('actionLabel', $variant === 'trainee' ? 'Log in to Envoy Academy' : 'Log in to Envoy')
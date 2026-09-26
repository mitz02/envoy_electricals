@extends('emails.layouts.new')

@section('preheader', 'Welcome to '.($variant === 'portal' ? 'Envoy Academy' : 'Envoy').', '.$first.'!')

@section('eyebrow', $variant === 'portal' ? 'Envoy Academy' : 'Welcome home')

@section('title', $variant === 'portal' ? "Welcome aboard, {$first}!" : ($variant === 'buyer' ? "Great to have you, {$first}!" : "Welcome to the team, {$first}!"))

@section('subtitle', $variant === 'portal'
    ? 'You now have access to Envoy Academy — your spot on the road to certified solar expertise.'
    : ($variant === 'buyer'
        ? 'Your Envoy account is live. Shop solar products, track your orders and manage payments in one place.'
        : 'Your Envoy account is live. The whole business is now at your fingertips.'))

@section('content')
    <p style="margin:0 0 18px;font-size:15px;line-height:1.7;color:#334155;">
        Hi {{ $first }},
    </p>

    @if ($variant === 'portal')
    <p style="margin:0 0 16px;font-size:15px;line-height:1.7;color:#334155;">
        We're genuinely thrilled to have you with us. Solar energy is the future, and by joining the academy
        you've taken the first real step toward being part of it.
    </p>
    <p style="margin:0 0 16px;font-size:15px;line-height:1.7;color:#334155;">
        Take a look around, pick a program that fits your level, and start learning at your own pace.
        Every course is built to take you from where you are to certified professional — no shortcuts, just
        solid, practical training.
    </p>
    <p style="margin:0;font-size:15px;line-height:1.7;color:#334155;">
        Your login is <strong style="color:#0D1527;">{{ $email }}</strong>. The next step is yours — explore a
        program and enroll when you're ready.
    </p>
    @elseif ($variant === 'buyer')
    <p style="margin:0 0 16px;font-size:15px;line-height:1.7;color:#334155;">
        Great to have you here. Your buyer account is ready, so you can explore our solar panels,
        inverters, batteries and accessories, check out faster and follow every order from the
        moment you place it to delivery.
    </p>
    <p style="margin:0;font-size:15px;line-height:1.7;color:#334155;">
        Your login is <strong style="color:#0D1527;">{{ $email }}</strong>. Head to your dashboard to
        view your orders and payment history — and don't hesitate to reply to this email if you need a hand.
    </p>
    @else
    <p style="margin:0 0 16px;font-size:15px;line-height:1.7;color:#334155;">
        Great to have you here. Your account is ready, so you can jump straight in — head over to the dashboard
        and you'll find orders, customers, inventory, payroll and everything else we run day to day.
    </p>
    <p style="margin:0;font-size:15px;line-height:1.7;color:#334155;">
        If anything doesn't make sense at first, don't sweat it. Ask around, and remember you can always
        reply to this email — a real human reads every message.
    </p>
    @endif
@endsection

@section('action', $variant === 'portal' ? $exploreUrl : ($variant === 'buyer' ? $exploreUrl : $loginUrl))
@section('actionLabel', $variant === 'portal' ? 'Explore programs' : ($variant === 'buyer' ? 'Go to your dashboard' : 'Open your dashboard'))
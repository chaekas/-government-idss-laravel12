<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Create farmer account · Government IDSS</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="login-body">
<div class="login-layout">
    <section class="login-intro">
        <a class="brand" href="{{ route('login') }}"><span class="mark">ID</span><span><strong>Government IDSS</strong><small>Ministry of Livestock & Fisheries</small></span></a>
        <div class="intro-copy">
            <small>FARMER SELF-REGISTRATION</small>
            <h1>Help protect<br><span>animal health.</span></h1>
            <p>Create a farmer account to access the disease surveillance workspace and report suspected livestock or poultry health issues.</p>
        </div>
        <small class="intro-foot">Your account is limited to farmer functions. Ministry staff access is assigned by an administrator.</small>
    </section>
    <section class="login-wrap">
        <form class="login-card" method="POST" action="{{ route('register.store') }}">
            @csrf
            <small class="kicker">CREATE ACCOUNT</small>
            <h2>Join the platform</h2>
            <p>Enter your details to create a farmer account.</p>
            @if($errors->any())
                <div class="alert error"><ul style="margin:0;padding-left:18px">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif
            <label for="name">Full name</label>
            <input id="name" name="name" type="text" value="{{ old('name') }}" autocomplete="name" maxlength="160" required autofocus>
            <label for="email">Email address</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" maxlength="180" required>
            <label for="phone">Phone number (optional)</label>
            <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" autocomplete="tel" maxlength="40">
            <label for="region">Region</label>
            <input id="region" name="region" type="text" value="{{ old('region') }}" maxlength="120" required>
            <label for="district">District</label>
            <input id="district" name="district" type="text" value="{{ old('district') }}" maxlength="120" required>
            <label for="password">Password (at least 12 characters, including letters and numbers)</label>
            <input id="password" name="password" type="password" autocomplete="new-password" minlength="12" required>
            <label for="password_confirmation">Confirm password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="12" required>
            <button class="button primary full" style="margin-top:20px" type="submit">Create farmer account →</button>
            <p class="footnote">Already registered? <a href="{{ route('login') }}" style="color:var(--green);font-weight:bold;text-decoration:underline">Sign in</a></p>
            <p class="footnote">Registration creates a farmer account only. Staff and administrator roles cannot be selected here.</p>
        </form>
    </section>
</div>
</body>
</html>

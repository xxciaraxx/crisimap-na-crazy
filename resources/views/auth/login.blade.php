<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sign in · CRISIMAP</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        #loginPage .input-icon{
            display:grid;
            place-items:center;
            width:1.25rem;
            height:1.25rem;
            color:#60728a;
        }
        #loginPage .input-icon svg,
        #loginPage .security-note > span svg{
            width:100%;
            height:100%;
            stroke:currentColor;
            fill:none;
            stroke-width:1.8;
            stroke-linecap:round;
            stroke-linejoin:round;
        }
        #loginPage .security-note > span{
            width:1.35rem;
            height:1.35rem;
            flex:0 0 1.35rem;
            display:inline-flex;
            align-items:center;
            justify-content:center;
        }
        #loginPage .feature{
            transition:background 180ms ease, box-shadow 180ms ease, border-color 180ms ease;
        }
        #loginPage .feature small{
            display:block !important;
            position:static !important;
            margin-top:.35rem;
            opacity:1 !important;
            transform:none !important;
            pointer-events:auto !important;
        }
        #loginPage .feature:hover,
        #loginPage .feature:focus-within{
            background:#ffffff12;
            border-color:#ffffff20;
            box-shadow:0 10px 22px #01295747;
        }
        #loginPage .feature:hover p,
        #loginPage .feature:focus-within p,
        #loginPage .feature:hover small,
        #loginPage .feature:focus-within small,
        #loginPage .feature:hover span,
        #loginPage .feature:focus-within span{
            opacity:1 !important;
            transform:none !important;
            animation:none !important;
        }
    </style>
</head>
<body class="crisi-login">
    <main class="login-shell" id="loginPage">
        <section class="brand-panel" aria-labelledby="welcome-title">
            <span class="shape shape-blue" aria-hidden="true"></span><span class="shape shape-green" aria-hidden="true"></span><span class="shape shape-yellow" aria-hidden="true"></span>
            <div class="brand-glow" aria-hidden="true"></div>
            <div class="brand-content">
                <div class="brand-marks reveal reveal-logo">
                    <img class="lgu-seal" src="{{ asset('images/tagudin-logo.png') }}" alt="Municipality of Tagudin, Ilocos Sur logo"><span class="logo-divider" aria-hidden="true"></span>
                    <div class="crisimap-mark"><img class="crisimap-official-logo" src="{{ asset('images/crisimap-official-logo.png') }}" alt="CRISIMAP official logo"><span><b>CRISI</b><b>MAP</b><small>AICS BENEFICIARY SYSTEM</small></span></div>
                </div>
                <div class="reveal reveal-title">
                    <p class="eyebrow">MSWDO TAGUDIN · ILOCOS SUR</p>
                    <h1 id="welcome-title">A GIS-Based Monitoring &amp; Mapping System</h1>
                    <h2>For AICS Beneficiary Distribution</h2>
                </div>
                <p class="brand-description reveal reveal-description">Create your authorized staff account to manage beneficiary records, map service delivery, and support accountable public assistance.</p>
                <div class="feature-grid reveal reveal-features" aria-label="CRISIMAP capabilities">
                    <div class="feature"><span>♟</span><p>Beneficiary<br>Management</p><small>Organized and accountable records.</small></div><div class="feature feature-location"><span>●</span><p>GIS Mapping &amp;<br>Visualization</p><small>Location-informed service delivery.</small></div><div class="feature feature-report"><span>▤</span><p>Analytics &amp;<br>Reports</p><small>Clear, actionable program insights.</small></div><div class="feature feature-shield"><span>⌾</span><p>Secure &amp;<br>Reliable</p><small>Protected staff access and audit trails.</small></div>
                </div>
            </div>
        </section>

        <section class="form-panel" aria-labelledby="signin-title">
            <span class="right-shape right-red" aria-hidden="true"></span><span class="right-shape right-green" aria-hidden="true"></span>
            <div class="login-card">
                <div class="form-heading"><p class="eyebrow">SECURE STAFF ACCESS</p><h2 id="signin-title">Sign in to CRISIMAP</h2><p>Access your account to continue.</p></div>
                @if (session('status'))<div class="status-message" role="status">{{ session('status') }}</div>@endif
                <aside class="security-note"><span aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 3.75 20.25 6.5v4.78c0 4.76-3.15 8.84-8.25 10.97-5.1-2.13-8.25-6.21-8.25-10.97V6.5L12 3.75Z"/><path d="m9.5 12.1 1.9 1.9 3.6-3.6"/></svg></span><p><strong>Protected system access</strong><br>Activities are monitored and logged for authorized MSWDO Tagudin personnel.</p></aside>
                <form method="POST" action="{{ route('login') }}" id="loginForm" novalidate>
                    @csrf
                    <div class="input-group @error('email') has-error @enderror">
                        <label for="email">Email address</label>
                        <span class="input-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M15.75 7.5a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z"/><path d="M4.5 20.25a7.5 7.5 0 0 1 15 0"/></svg></span>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="Email address" aria-describedby="email-error">
                        @error('email')<p class="field-error" id="email-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="input-group @error('password') has-error @enderror">
                        <label for="password">Password</label>
                        <span class="input-icon lock-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M16.5 10.5V7.875a4.5 4.5 0 1 0-9 0V10.5"/><path d="M6.75 10.5h10.5a1.5 1.5 0 0 1 1.5 1.5v6.75a1.5 1.5 0 0 1-1.5 1.5H6.75a1.5 1.5 0 0 1-1.5-1.5V12a1.5 1.5 0 0 1 1.5-1.5Z"/></svg></span>
                        <input id="password" name="password" type="password" required autocomplete="current-password" placeholder="Password" aria-describedby="password-error">
                        <button class="password-toggle" type="button" aria-controls="password" aria-label="Show password" aria-pressed="false"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.5"/><path class="eye-slash" d="m4 4 16 16"/></svg></button>
                        @error('password')<p class="field-error" id="password-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="form-options">
                        <label class="remember"><input id="remember" type="checkbox" name="remember"><span class="checkmark">✓</span><span>Remember me</span></label>
                    </div>
                    <button class="sign-in" id="signInButton" type="submit"><span class="button-text">Sign in</span><span class="button-arrow" aria-hidden="true">→</span><span class="spinner" aria-hidden="true"></span></button>
                    @if (Route::has('password.request'))<a class="forgot-link" href="{{ route('password.request') }}">Forgot password?</a>@endif
                </form>
                <div class="account-switch"><p>New to CRISIMAP?</p><a class="auth-switch-button" href="{{ route('register') }}"><span aria-hidden="true">+</span>Create an account</a></div>
                <p class="contact-copy">Need assistance? <a class="contact-link" href="mailto:mswdo@tagudin.gov.ph"><span aria-hidden="true">✉</span>Contact System Administrator</a></p>
            </div>
        </section>
    </main>
    <script>
        const toggle = document.querySelector('.password-toggle'), password = document.querySelector('#password');
        toggle?.addEventListener('click', () => { const shown = password.type === 'text'; password.type = shown ? 'password' : 'text'; toggle.classList.toggle('is-visible', !shown); toggle.setAttribute('aria-label', shown ? 'Show password' : 'Hide password'); toggle.setAttribute('aria-pressed', String(!shown)); password.classList.add('password-flash'); setTimeout(() => password.classList.remove('password-flash'), 200); });
        const signInButton = document.querySelector('#signInButton');
        signInButton?.addEventListener('click', event => { const ripple = document.createElement('i'); const rect = signInButton.getBoundingClientRect(); ripple.className = 'button-ripple'; ripple.style.left = `${event.clientX - rect.left}px`; ripple.style.top = `${event.clientY - rect.top}px`; signInButton.append(ripple); ripple.addEventListener('animationend', () => ripple.remove()); });
        document.querySelector('#loginForm')?.addEventListener('submit', function () { if (this.checkValidity()) { signInButton.classList.add('is-loading'); signInButton.disabled = true; } });
    </script>
</body>
</html>

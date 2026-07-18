<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Create account · CRISIMAP</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="crisi-login">
    <main class="login-shell" id="registrationPage">
        <section class="brand-panel" aria-labelledby="registration-welcome-title">
            <span class="shape shape-blue" aria-hidden="true"></span><span class="shape shape-green" aria-hidden="true"></span><span class="shape shape-yellow" aria-hidden="true"></span>
            <div class="brand-glow" aria-hidden="true"></div>
            <div class="brand-content">
                <div class="brand-marks reveal reveal-logo">
                    <div class="lgu-seal" aria-label="Municipality of Tagudin, Ilocos Sur"><span>1916</span><strong>TAGUDIN</strong><span>ILOCOS SUR</span></div><span class="logo-divider" aria-hidden="true"></span>
                    <div class="crisimap-mark"><span class="map-pin">●</span><span><b>CRISI</b><b>MAP</b><small>AICS BENEFICIARY SYSTEM</small></span></div>
                </div>
                <div class="reveal reveal-title">
                    <p class="eyebrow">MSWDO TAGUDIN · ILOCOS SUR</p>
                    <h1 id="registration-welcome-title">A GIS-Based Monitoring &amp; Mapping System</h1>
                    <h2>For AICS Beneficiary Distribution</h2>
                </div>
                <p class="brand-description reveal reveal-description">Create your authorized staff account to manage beneficiary records, map service delivery, and support accountable public assistance.</p>
                <div class="feature-grid reveal reveal-features" aria-label="CRISIMAP capabilities">
                    <div class="feature"><span>♟</span><p>Beneficiary<br>Management</p><small>Organized and accountable records.</small></div><div class="feature feature-location"><span>●</span><p>GIS Mapping &amp;<br>Visualization</p><small>Location-informed service delivery.</small></div><div class="feature feature-report"><span>▤</span><p>Analytics &amp;<br>Reports</p><small>Clear, actionable program insights.</small></div><div class="feature feature-shield"><span>⌾</span><p>Secure &amp;<br>Reliable</p><small>Protected staff access and audit trails.</small></div>
                </div>
            </div>
            <footer class="brand-footer reveal reveal-footer"><span class="footer-shield">✓</span><span>Empowering MSWDO Tagudin through technology<br>for better service and data-driven decision making.</span></footer>
        </section>

        <section class="form-panel" aria-labelledby="signup-title">
            <span class="right-shape right-red" aria-hidden="true"></span><span class="right-shape right-green" aria-hidden="true"></span>
            <div class="login-card registration-card">
                <div class="form-heading"><p class="eyebrow">AUTHORIZED STAFF REGISTRATION</p><h2 id="signup-title">Create your account</h2><p>Register to access CRISIMAP securely.</p></div>
                <aside class="security-note"><span>⌾</span><p><strong>Authorized personnel only</strong><br>Your account helps maintain secure, accountable system access.</p></aside>
                <form method="POST" action="{{ route('register') }}" id="registrationForm" novalidate>
                    @csrf
                    <div class="input-group @error('name') has-error @enderror">
                        <label for="name">Full name</label><span class="input-icon" aria-hidden="true">◉</span>
                        <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus autocomplete="name" placeholder="Full name" aria-describedby="name-error">
                        @error('name')<p class="field-error" id="name-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="input-group @error('email') has-error @enderror">
                        <label for="email">Email address</label><span class="input-icon" aria-hidden="true">◉</span>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="username" placeholder="Email address" aria-describedby="email-error">
                        @error('email')<p class="field-error" id="email-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="input-group @error('password') has-error @enderror">
                        <label for="password">Password</label><span class="input-icon lock-icon" aria-hidden="true">▣</span>
                        <input id="password" name="password" type="password" required autocomplete="new-password" placeholder="Password" aria-describedby="password-error">
                        <button class="password-toggle" type="button" data-password-toggle="password" aria-label="Show password">SHOW</button>
                        @error('password')<p class="field-error" id="password-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="input-group @error('password_confirmation') has-error @enderror">
                        <label for="password_confirmation">Confirm password</label><span class="input-icon lock-icon" aria-hidden="true">▣</span>
                        <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" placeholder="Confirm password" aria-describedby="password-confirmation-error">
                        <button class="password-toggle" type="button" data-password-toggle="password_confirmation" aria-label="Show password">SHOW</button>
                        @error('password_confirmation')<p class="field-error" id="password-confirmation-error">{{ $message }}</p>@enderror
                    </div>
                    <button class="sign-in registration-submit" id="registerButton" type="submit"><span class="button-text">Create account</span><span class="button-arrow" aria-hidden="true">→</span><span class="spinner" aria-hidden="true"></span></button>
                </form>
                <div class="account-switch"><p>Already have an account?</p><a class="auth-switch-button" href="{{ route('login') }}"><span aria-hidden="true">→</span>Back to sign in</a></div>
            </div>
        </section>
    </main>
    <script>
        document.querySelectorAll('[data-password-toggle]').forEach(toggle => toggle.addEventListener('click', () => { const field = document.getElementById(toggle.dataset.passwordToggle), shown = field.type === 'text'; field.type = shown ? 'password' : 'text'; toggle.textContent = shown ? 'SHOW' : 'HIDE'; toggle.classList.toggle('is-visible', !shown); toggle.setAttribute('aria-label', shown ? 'Show password' : 'Hide password'); field.classList.add('password-flash'); setTimeout(() => field.classList.remove('password-flash'), 200); }));
        const registerButton = document.querySelector('#registerButton');
        registerButton?.addEventListener('click', event => { const ripple = document.createElement('i'), rect = registerButton.getBoundingClientRect(); ripple.className = 'button-ripple'; ripple.style.left = `${event.clientX - rect.left}px`; ripple.style.top = `${event.clientY - rect.top}px`; registerButton.append(ripple); ripple.addEventListener('animationend', () => ripple.remove()); });
        document.querySelector('#registrationForm')?.addEventListener('submit', function () { if (this.checkValidity()) { registerButton.classList.add('is-loading'); registerButton.disabled = true; } });
    </script>
</body>
</html>

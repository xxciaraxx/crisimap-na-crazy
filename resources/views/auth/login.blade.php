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
</head>
<body class="crisi-login">
    <main class="login-shell" id="loginPage">
        <section class="brand-panel" aria-labelledby="welcome-title">
            <span class="shape shape-blue" aria-hidden="true"></span><span class="shape shape-green" aria-hidden="true"></span><span class="shape shape-yellow" aria-hidden="true"></span>
            <div class="brand-glow" aria-hidden="true"></div>
            <div class="brand-content">
                <div class="brand-marks reveal reveal-logo">
                    <div class="lgu-seal" aria-label="Municipality of Tagudin, Ilocos Sur"><span>1916</span><strong>TAGUDIN</strong><span>ILOCOS SUR</span></div><span class="logo-divider" aria-hidden="true"></span>
                    <div class="crisimap-mark"><span class="map-pin">●</span><span><b>CRISI</b><b>MAP</b><small>AICS BENEFICIARY SYSTEM</small></span></div>
                </div>
                <div class="reveal reveal-title">
                    <p class="eyebrow">MSWDO TAGUDIN · ILOCOS SUR</p>
                    <h1 id="welcome-title">A GIS-Based Monitoring &amp; Mapping System</h1>
                    <h2>For AICS Beneficiary Distribution</h2>
                </div>
                <p class="brand-description reveal reveal-description">A secure, data-informed platform for managing assistance to individuals in crisis situations and delivering better public service.</p>
                <div class="feature-grid reveal reveal-features" aria-label="CRISIMAP capabilities">
                    <div class="feature"><span>♟</span><p>Beneficiary<br>Management</p><small>Organized and accountable records.</small></div><div class="feature feature-location"><span>●</span><p>GIS Mapping &<br>Visualization</p><small>Location-informed service delivery.</small></div><div class="feature feature-report"><span>▤</span><p>Analytics &amp;<br>Reports</p><small>Clear, actionable program insights.</small></div><div class="feature feature-shield"><span>⌾</span><p>Secure &amp;<br>Reliable</p><small>Protected staff access and audit trails.</small></div>
                </div>
            </div>
            <footer class="brand-footer reveal reveal-footer"><span class="footer-shield">✓</span><span>Empowering MSWDO Tagudin through technology<br>for better service and data-driven decision making.</span></footer>
        </section>

        <section class="form-panel" aria-labelledby="signin-title">
            <span class="right-shape right-red" aria-hidden="true"></span><span class="right-shape right-green" aria-hidden="true"></span>
            <div class="login-card">
                <div class="form-heading"><p class="eyebrow">SECURE STAFF ACCESS</p><h2 id="signin-title">Sign in to CRISIMAP</h2><p>Access your account to continue.</p></div>
                @if (session('status'))<div class="status-message" role="status">{{ session('status') }}</div>@endif
                <aside class="security-note"><span>⌾</span><p><strong>Protected system access</strong><br>Activities are monitored and logged for authorized MSWDO Tagudin personnel.</p></aside>
                <form method="POST" action="{{ route('login') }}" id="loginForm" novalidate>
                    @csrf
                    <div class="input-group @error('email') has-error @enderror">
                        <label for="email">Email address</label>
                        <span class="input-icon" aria-hidden="true">◉</span>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="Email address" aria-describedby="email-error">
                        @error('email')<p class="field-error" id="email-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="input-group @error('password') has-error @enderror">
                        <label for="password">Password</label>
                        <span class="input-icon lock-icon" aria-hidden="true">▣</span>
                        <input id="password" name="password" type="password" required autocomplete="current-password" placeholder="Password" aria-describedby="password-error">
                        <button class="password-toggle" type="button" aria-controls="password" aria-label="Show password">SHOW</button>
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
        toggle?.addEventListener('click', () => { const shown = password.type === 'text'; password.type = shown ? 'password' : 'text'; toggle.textContent = shown ? 'SHOW' : 'HIDE'; toggle.classList.toggle('is-visible', !shown); toggle.setAttribute('aria-label', shown ? 'Show password' : 'Hide password'); password.classList.add('password-flash'); setTimeout(() => password.classList.remove('password-flash'), 200); });
        const signInButton = document.querySelector('#signInButton');
        signInButton?.addEventListener('click', event => { const ripple = document.createElement('i'); const rect = signInButton.getBoundingClientRect(); ripple.className = 'button-ripple'; ripple.style.left = `${event.clientX - rect.left}px`; ripple.style.top = `${event.clientY - rect.top}px`; signInButton.append(ripple); ripple.addEventListener('animationend', () => ripple.remove()); });
        document.querySelector('#loginForm')?.addEventListener('submit', function () { if (this.checkValidity()) { signInButton.classList.add('is-loading'); signInButton.disabled = true; } });
    </script>
</body>
</html>

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Forgot password · CRISIMAP</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        #forgotPage .input-icon{
            display:grid;
            place-items:center;
            width:1.25rem;
            height:1.25rem;
            color:#60728a;
        }
        #forgotPage .input-icon svg,
        #forgotPage .security-note > span svg{
            width:100%;
            height:100%;
            stroke:currentColor;
            fill:none;
            stroke-width:1.8;
            stroke-linecap:round;
            stroke-linejoin:round;
        }
        #forgotPage .security-note > span{
            width:1.35rem;
            height:1.35rem;
            flex:0 0 1.35rem;
            display:inline-flex;
            align-items:center;
            justify-content:center;
        }
        #forgotPage .feature{
            transition:background 180ms ease, box-shadow 180ms ease, border-color 180ms ease;
        }
        #forgotPage .feature small{
            display:block !important;
            position:static !important;
            margin-top:.35rem;
            opacity:1 !important;
            transform:none !important;
            pointer-events:auto !important;
        }
        #forgotPage .feature:hover,
        #forgotPage .feature:focus-within{
            background:#ffffff12;
            border-color:#ffffff20;
            box-shadow:0 10px 22px #01295747;
        }
        #forgotPage .feature:hover p,
        #forgotPage .feature:focus-within p,
        #forgotPage .feature:hover small,
        #forgotPage .feature:focus-within small,
        #forgotPage .feature:hover span,
        #forgotPage .feature:focus-within span{
            opacity:1 !important;
            transform:none !important;
            animation:none !important;
        }
        .reset-copy{
            margin:0 0 1rem;
            color:#536174;
            font-size:.95rem;
            line-height:1.55;
        }
        .reset-badge{
            display:inline-flex;
            align-items:center;
            gap:.45rem;
            margin-bottom:1rem;
            padding:.45rem .72rem;
            border:1px solid #d5e3ff;
            border-radius:999px;
            background:#f5f9ff;
            color:#1a5fbe;
            font-size:.78rem;
            font-weight:700;
            letter-spacing:.02em;
        }
        .reset-badge svg{
            width:1rem;
            height:1rem;
            stroke:currentColor;
            fill:none;
            stroke-width:2;
            stroke-linecap:round;
            stroke-linejoin:round;
        }
        .input-group input:focus-visible,
        .sign-in:focus-visible,
        .auth-switch-button:focus-visible{
            outline:3px solid #1565c055;
            outline-offset:2px;
        }
    </style>
</head>
<body class="crisi-login">
    <main class="login-shell" id="forgotPage">
        <section class="brand-panel" aria-labelledby="forgot-welcome-title">
            <span class="shape shape-blue" aria-hidden="true"></span><span class="shape shape-green" aria-hidden="true"></span><span class="shape shape-yellow" aria-hidden="true"></span>
            <div class="brand-glow" aria-hidden="true"></div>
            <div class="brand-content">
                <div class="brand-marks reveal reveal-logo">
                    <img class="lgu-seal" src="{{ asset('images/tagudin-logo.png') }}" alt="Municipality of Tagudin, Ilocos Sur logo"><span class="logo-divider" aria-hidden="true"></span>
                    <div class="crisimap-mark"><img class="crisimap-official-logo" src="{{ asset('images/crisimap-official-logo.png') }}" alt="CRISIMAP official logo"><span><b>CRISI</b><b>MAP</b><small>AICS BENEFICIARY SYSTEM</small></span></div>
                </div>
                <div class="reveal reveal-title">
                    <p class="eyebrow">MSWDO TAGUDIN · ILOCOS SUR</p>
                    <h1 id="forgot-welcome-title">Account Recovery Made Simple</h1>
                    <h2>Reset your CRISIMAP access securely</h2>
                </div>
                <p class="brand-description reveal reveal-description">We’ll send a password reset link to your registered email so you can restore access quickly and safely.</p>
                <div class="feature-grid reveal reveal-features" aria-label="CRISIMAP capabilities">
                    <div class="feature"><span>♟</span><p>Secure<br>Recovery</p><small>Password reset by verified email.</small></div><div class="feature feature-location"><span>●</span><p>Fast<br>Access</p><small>Get back into your account quickly.</small></div><div class="feature feature-report"><span>▤</span><p>Account<br>Safety</p><small>Keep staff access controlled.</small></div><div class="feature feature-shield"><span>⌾</span><p>Trusted<br>Support</p><small>Built for authorized personnel.</small></div>
                </div>
            </div>
            <footer class="brand-footer reveal reveal-footer"><span class="footer-shield">✓</span><span>Need help recovering access?<br>Use your official work email to continue.</span></footer>
        </section>

        <section class="form-panel" aria-labelledby="forgot-title">
            <span class="right-shape right-red" aria-hidden="true"></span><span class="right-shape right-green" aria-hidden="true"></span>
            <div class="login-card">
                <div class="form-heading">
                    <p class="eyebrow">PASSWORD RECOVERY</p>
                    <h2 id="forgot-title">Forgot your password?</h2>
                    <p>Enter your email and we’ll send a reset link.</p>
                </div>

                @if (session('status'))
                    <div class="status-message" role="status">{{ session('status') }}</div>
                @endif

                <aside class="security-note">
                    <span aria-hidden="true">
                        <svg viewBox="0 0 24 24">
                            <path d="M12 3.75 20.25 6.5v4.78c0 4.76-3.15 8.84-8.25 10.97-5.1-2.13-8.25-6.21-8.25-10.97V6.5L12 3.75Z"/>
                            <path d="m9.5 12.1 1.9 1.9 3.6-3.6"/>
                        </svg>
                    </span>
                    <p><strong>Verified recovery only</strong><br>We send reset links only to registered CRISIMAP accounts.</p>
                </aside>

                <form method="POST" action="{{ route('password.email') }}" id="forgotForm" novalidate>
                    @csrf
                    <div class="input-group @error('email') has-error @enderror">
                        <label for="email">Email address</label>
                        <span class="input-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24">
                                <path d="M3.75 6.75h16.5v10.5H3.75z"/>
                                <path d="m3.75 7.5 8.25 5.25L20.25 7.5"/>
                            </svg>
                        </span>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="email" placeholder="Email address" aria-describedby="email-error">
                        @error('email')<p class="field-error" id="email-error">{{ $message }}</p>@enderror
                    </div>

                    <button class="sign-in" id="forgotButton" type="submit">
                        <span class="button-text">Email reset link</span>
                        <span class="button-arrow" aria-hidden="true">→</span>
                        <span class="spinner" aria-hidden="true"></span>
                    </button>
                </form>

                <div class="account-switch">
                    <p>Remembered your password?</p>
                    <a class="auth-switch-button" href="{{ route('login') }}"><span aria-hidden="true">←</span>Back to sign in</a>
                </div>
                <p class="contact-copy">Need assistance? <a class="contact-link" href="mailto:mswdo@tagudin.gov.ph"><span aria-hidden="true">✉</span>Contact System Administrator</a></p>
            </div>
        </section>
    </main>
    <script>
        const forgotButton = document.querySelector('#forgotButton');
        forgotButton?.addEventListener('click', event => {
            const ripple = document.createElement('i');
            const rect = forgotButton.getBoundingClientRect();
            ripple.className = 'button-ripple';
            ripple.style.left = `${event.clientX - rect.left}px`;
            ripple.style.top = `${event.clientY - rect.top}px`;
            forgotButton.append(ripple);
            ripple.addEventListener('animationend', () => ripple.remove());
        });

        document.querySelector('#forgotForm')?.addEventListener('submit', function () {
            if (this.checkValidity()) {
                forgotButton.classList.add('is-loading');
                forgotButton.disabled = true;
            }
        });
    </script>
</body>
</html>

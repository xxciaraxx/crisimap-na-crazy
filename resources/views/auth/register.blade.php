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
    <style>
        #registrationPage .input-icon{
            display:grid;
            place-items:center;
            width:1.25rem;
            height:1.25rem;
            color:#60728a;
        }
        #registrationPage .input-icon svg,
        #registrationPage .security-note > span svg{
            width:100%;
            height:100%;
            stroke:currentColor;
            fill:none;
            stroke-width:1.8;
            stroke-linecap:round;
            stroke-linejoin:round;
        }
        #registrationPage .security-note > span{
            width:1.35rem;
            height:1.35rem;
            flex:0 0 1.35rem;
            display:inline-flex;
            align-items:center;
            justify-content:center;
        }
        #registrationPage .approval-notice{
            display:flex;
            gap:.7rem;
            align-items:flex-start;
            margin:1rem 0 .2rem;
            padding:.75rem .9rem;
            border:1px solid #bfdbfe;
            border-radius:10px;
            background:#eff6ff;
            color:#315273;
            font-size:.78rem;
            line-height:1.4;
        }
        #registrationPage .approval-notice svg{
            width:1.1rem;
            height:1.1rem;
            flex:0 0 1.1rem;
            margin-top:.04rem;
            stroke:#1565c0;
            fill:none;
            stroke-width:2;
            stroke-linecap:round;
            stroke-linejoin:round;
        }
        #registrationPage .approval-notice strong{color:#104f9e}
        #registrationPage .feature{
            transition:background 180ms ease, box-shadow 180ms ease, border-color 180ms ease;
        }
        #registrationPage .feature small{
            display:block !important;
            position:static !important;
            margin-top:.35rem;
            opacity:1 !important;
            transform:none !important;
            pointer-events:auto !important;
        }
        #registrationPage .feature:hover,
        #registrationPage .feature:focus-within{
            background:#ffffff12;
            border-color:#ffffff20;
            box-shadow:0 10px 22px #01295747;
        }
        #registrationPage .feature:hover p,
        #registrationPage .feature:focus-within p,
        #registrationPage .feature:hover small,
        #registrationPage .feature:focus-within small,
        #registrationPage .feature:hover span,
        #registrationPage .feature:focus-within span{
            opacity:1 !important;
            transform:none !important;
            animation:none !important;
        }

        /* ---- Password requirements UI: smaller, tighter, and easier to scan ---- */
        .password-requirements{
            display:none;
            position:absolute;
            z-index:5;
            top:calc(100% + .45rem);
            left:0;
            right:0;
            margin:0;
            padding:.65rem .7rem;
            border:1px solid #d7e4ff;
            border-radius:14px;
            background:linear-gradient(180deg,#f7fbff 0%,#eef5ff 100%);
            color:#0f172a;
            font-size:.8rem;
            box-shadow:0 8px 18px rgba(21,101,192,.08);
            pointer-events:none;
        }
        .password-requirements.is-visible{display:block}
        .pw-strength{height:.34rem;background:#e6eef8;border-radius:999px;overflow:hidden;margin-bottom:.4rem}
        .pw-strength > i{display:block;height:100%;background:linear-gradient(90deg,#fb7185,#34d399);width:0%;transition:width .18s ease}
        .pw-list{
            display:grid;
            grid-template-columns:repeat(2,minmax(0,1fr));
            gap:.35rem .6rem;
            margin:.2rem 0 0;
            padding:0;
            list-style:none;
        }
        .pw-list li{
            display:flex;
            gap:.45rem;
            align-items:flex-start;
            min-width:0;
            color:#475569;
            font-size:.76rem;
            line-height:1.25;
        }
        .pw-list li.met{color:#064e3b}
        .pw-list li .dot{width:.92rem;height:.92rem;flex:0 0 .92rem;border-radius:999px;display:inline-flex;align-items:center;justify-content:center;font-size:.58rem;background:#e2e8f0;color:#64748b;margin-top:.08rem}
        .pw-list li.met .dot{background:#bbf7d0;color:#064e3b}
        /* Bug fix: the dot used to render a hard-coded "✓" for every item regardless
           of whether the requirement was actually met, which made unmet requirements
           look satisfied. The glyph now reflects real state via CSS content. */
        .pw-list li .dot::before{content:"•";}
        .pw-list li.met .dot::before{content:"✓";}
        .password-message{
            display:none;
            margin-top:.45rem;
            padding:.35rem .6rem;
            border-radius:999px;
            background:#ecfdf5;
            border:1px solid #a7f3d0;
            color:#065f46;
            font-size:.74rem;
            font-weight:700;
        }
        .password-message.is-visible{display:inline-flex;align-items:center}
        .sr-only{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}
        @media (max-width:640px){
            .pw-list{grid-template-columns:1fr}
        }

        /* Clearer keyboard focus rings across the form, without changing the palette */
        .input-group input:focus-visible,
        .input-group select:focus-visible,
        .password-toggle:focus-visible,
        .sign-in:focus-visible,
        .auth-switch-button:focus-visible{
            outline:3px solid #1565c055;
            outline-offset:2px;
        }
        #registrationPage .role-group select{
            display:block;
            width:100% !important;
            height:54px !important;
            padding:0 .9rem 0 3.8rem !important;
            border:1px solid #cbd5e1;
            border-radius:10px;
            background:#fff;
            color:#182235;
            font:600 .95rem/1.2 Figtree, sans-serif;
            cursor:pointer;
        }
        #registrationPage .role-group select:invalid{color:#718198}
        #registrationPage .role-group:focus-within select{
            border-color:#1565c0;
            box-shadow:0 0 0 4px #1565c014,0 8px 18px #073d8214;
        }
    </style>
</head>
<body class="crisi-login">
    <main class="login-shell" id="registrationPage">
        <section class="brand-panel" aria-labelledby="registration-welcome-title">
            <span class="shape shape-blue" aria-hidden="true"></span><span class="shape shape-green" aria-hidden="true"></span><span class="shape shape-yellow" aria-hidden="true"></span>
            <div class="brand-glow" aria-hidden="true"></div>
            <div class="brand-content">
                <div class="brand-marks reveal reveal-logo">
                    <img class="lgu-seal" src="{{ asset('images/tagudin-logo.png') }}" alt="Municipality of Tagudin, Ilocos Sur logo"><span class="logo-divider" aria-hidden="true"></span>
                    <div class="crisimap-mark"><span class="map-pin">●</span><span><b>CRISI</b><b>MAP</b><small>AICS BENEFICIARY SYSTEM</small></span></div>
                </div>
                <div class="reveal reveal-title">
                    <p class="eyebrow">MSWDO TAGUDIN · ILOCOS SUR</p>
                    <h1 id="registration-welcome-title">A GIS-Based Monitoring &amp; Mapping System</h1>
                    <h2>For AICS Beneficiary Distribution</h2>
                </div>
                <p class="brand-description reveal reveal-description">Create your authorized staff account to manage beneficiary records, map service delivery, and support accountable public assistance.</p>
                <div class="feature-grid reveal reveal-features" aria-label="CRISIMAP capabilities">
                    <div class="feature"><span>♟</span><p>Beneficiary<br>Management</p><small>Organized and accountable records.</small></div>
                    <div class="feature feature-location"><span>●</span><p>GIS Mapping &amp;<br>Visualization</p><small>Location-informed service delivery.</small></div>
                    <div class="feature feature-report"><span>▤</span><p>Analytics &amp;<br>Reports</p><small>Clear, actionable program insights.</small></div>
                    <div class="feature feature-shield"><span>⌾</span><p>Secure &amp;<br>Reliable</p><small>Protected staff access and audit trails.</small></div>
                </div>
            </div>
        </section>

        <section class="form-panel" aria-labelledby="signup-title">
            <span class="right-shape right-red" aria-hidden="true"></span><span class="right-shape right-green" aria-hidden="true"></span>
            <div class="login-card registration-card">
                <div class="form-heading"><p class="eyebrow">AUTHORIZED STAFF REGISTRATION</p><h2 id="signup-title">Create your account</h2><p>Register to access CRISIMAP securely.</p></div>
                <aside class="security-note"><span aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 3.75 20.25 6.5v4.78c0 4.76-3.15 8.84-8.25 10.97-5.1-2.13-8.25-6.21-8.25-10.97V6.5L12 3.75Z"/><path d="m9.5 12.1 1.9 1.9 3.6-3.6"/></svg></span><p><strong>Authorized personnel only</strong><br>Your account helps maintain secure, accountable system access.</p></aside>
                <form method="POST" action="{{ route('register') }}" id="registrationForm" novalidate>
                    @csrf
                    <div class="input-group @error('name') has-error @enderror">
                        <label for="name">Full name</label><span class="input-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M15.75 7.5a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z"/><path d="M4.5 20.25a7.5 7.5 0 0 1 15 0"/></svg></span>
                        <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus autocomplete="name" placeholder="Full name" aria-describedby="name-error">
                        @error('name')<p class="field-error" id="name-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="input-group @error('email') has-error @enderror">
                        <label for="email">Email address</label><span class="input-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M3.75 6.75h16.5v10.5H3.75z"/><path d="m3.75 7.5 8.25 5.25L20.25 7.5"/></svg></span>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="username" placeholder="Email address" aria-describedby="email-error">
                        @error('email')<p class="field-error" id="email-error">{{ $message }}</p>@enderror
                    </div>
                    @if ($availableLeadershipRoles)
                        <div class="input-group role-group @error('role') has-error @enderror">
                            <span class="input-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="4" y="3.75" width="16" height="16.5" rx="2"/><path d="M8 8.25h8M8 12h2.25M8 15.75h5.5"/></svg></span>
                            <select id="role" name="role" required aria-label="Position" aria-describedby="role-error">
                                <option value="" disabled @selected(! old('role'))>Choose a position</option>
                                @if (in_array('admin', $availableLeadershipRoles, true))
                                    <option value="admin" @selected(old('role') === 'admin')>MSWDO Head</option>
                                @endif
                                @if (in_array('focal_person', $availableLeadershipRoles, true))
                                    <option value="focal_person" @selected(old('role') === 'focal_person')>Focal Person</option>
                                @endif
                                <option value="social_officer" @selected(old('role') === 'social_officer')>Social Worker Officer</option>
                            </select>
                            @error('role')<p class="field-error" id="role-error">{{ $message }}</p>@enderror
                        </div>
                    @endif
                    <div class="input-group @error('password') has-error @enderror">
                        <label for="password">Password</label><span class="input-icon lock-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M16.5 10.5V7.875a4.5 4.5 0 1 0-9 0V10.5"/><path d="M6.75 10.5h10.5a1.5 1.5 0 0 1 1.5 1.5v6.75a1.5 1.5 0 0 1-1.5 1.5H6.75a1.5 1.5 0 0 1-1.5-1.5V12a1.5 1.5 0 0 1 1.5-1.5Z"/></svg></span>
                        <input id="password" name="password" type="password" required minlength="8"
                               pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}"
                               title="Must be at least 8 characters and include an uppercase letter, a lowercase letter, a number, and a special character."
                               autocomplete="new-password" placeholder="Password"
                               aria-describedby="password-error passwordRequirements passwordMessage">
                        <button class="password-toggle" type="button" data-password-toggle="password" aria-label="Show password" aria-pressed="false">SHOW</button>
                        @error('password')<p class="field-error" id="password-error">{{ $message }}</p>@enderror
                        <p id="passwordMessage" class="password-message" aria-live="polite">Great! Password meets all requirements.</p>
                        <div id="passwordRequirements" class="password-requirements" aria-live="polite">
                            <div class="pw-strength" aria-hidden="true"><i id="pwStrengthBar"></i></div>
                            <div style="display:flex;justify-content:space-between;margin-bottom:.25rem"><small id="pwStrengthLabel">Minimum 8 characters</small><small id="pwStrengthStatus"></small></div>
                            <ul class="pw-list" id="pwCriteriaList">
                                <li data-test="length"><span class="dot" aria-hidden="true"></span>Minimum 8 characters<span class="sr-only" data-status></span></li>
                                <li data-test="upper"><span class="dot" aria-hidden="true"></span>At least 1 uppercase letter<span class="sr-only" data-status></span></li>
                                <li data-test="lower"><span class="dot" aria-hidden="true"></span>At least 1 lowercase letter<span class="sr-only" data-status></span></li>
                                <li data-test="number"><span class="dot" aria-hidden="true"></span>At least 1 number<span class="sr-only" data-status></span></li>
                                <li data-test="special"><span class="dot" aria-hidden="true"></span>At least 1 special character (!@#$...)<span class="sr-only" data-status></span></li>
                            </ul>
                        </div>
                    </div>
                    <div class="input-group @error('password_confirmation') has-error @enderror">
                        <label for="password_confirmation">Confirm password</label><span class="input-icon lock-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M16.5 10.5V7.875a4.5 4.5 0 1 0-9 0V10.5"/><path d="M6.75 10.5h10.5a1.5 1.5 0 0 1 1.5 1.5v6.75a1.5 1.5 0 0 1-1.5 1.5H6.75a1.5 1.5 0 0 1-1.5-1.5V12a1.5 1.5 0 0 1 1.5-1.5Z"/></svg></span>
                        <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" placeholder="Confirm password" aria-describedby="password-confirmation-error password-confirmation-mismatch">
                        <button class="password-toggle" type="button" data-password-toggle="password_confirmation" aria-label="Show password" aria-pressed="false">SHOW</button>
                        @error('password_confirmation')<p class="field-error" id="password-confirmation-error">{{ $message }}</p>@enderror
                        <p class="field-error" id="password-confirmation-mismatch" style="display:none" aria-live="assertive">Passwords do not match.</p>
                    </div>
                    <aside class="approval-notice" role="note">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="8.25"/><path d="M12 10.5v5M12 7.75h.01"/></svg>
                        <span><strong>Approval required.</strong> After you create your account, please wait for a System Administrator to review and approve your access.</span>
                    </aside>
                    <button class="sign-in registration-submit" id="registerButton" type="submit"><span class="button-text">Create account</span><span class="button-arrow" aria-hidden="true">→</span><span class="spinner" aria-hidden="true"></span></button>
                </form>
                <div class="account-switch"><p>Already have an account?</p><a class="auth-switch-button" href="{{ route('login') }}"><span aria-hidden="true">→</span>Back to sign in</a></div>
            </div>
        </section>
    </main>
    <script>
        (function(){
            const pwd = document.getElementById('password');
            const confirmField = document.getElementById('password_confirmation');
            const mismatchMsg = document.getElementById('password-confirmation-mismatch');
            const container = document.getElementById('passwordRequirements');
            const message = document.getElementById('passwordMessage');
            const bar = document.getElementById('pwStrengthBar');
            const label = document.getElementById('pwStrengthLabel');
            const status = document.getElementById('pwStrengthStatus');
            const items = Array.from(document.querySelectorAll('#pwCriteriaList li'));
            const tests = {
                length: v => v.length >= 8,
                upper: v => /[A-Z]/.test(v),
                lower: v => /[a-z]/.test(v),
                number: v => /[0-9]/.test(v),
                special: v => /[^A-Za-z0-9]/.test(v)
            };
            function update(v){
                const any = v.length > 0;
                const passed = items.reduce((count, li) => tests[li.dataset.test](v) ? count + 1 : count, 0);
                const total = items.length;
                const complete = passed === total;
                container.classList.toggle('is-visible', any && !complete);
                message.classList.toggle('is-visible', any && complete);
                items.forEach(li => {
                    const key = li.dataset.test;
                    const met = tests[key](v);
                    li.classList.toggle('met', met);
                    const statusEl = li.querySelector('[data-status]');
                    if (statusEl) statusEl.textContent = any ? (met ? ' – met' : ' – not met') : '';
                });
                const pct = Math.round((passed/total)*100);
                bar.style.width = pct + '%';
                if(complete){ label.textContent = 'Strong'; status.textContent = ''; }
                else if(passed >= 3){ label.textContent = 'Moderate'; status.textContent = pct + '%'; }
                else { label.textContent = 'Weak'; status.textContent = pct + '%'; }
            }
            function checkMatch(){
                const show = confirmField.value.length > 0 && pwd.value !== confirmField.value;
                mismatchMsg.style.display = show ? 'block' : 'none';
                return !show;
            }
            pwd?.addEventListener('input', e => { update(e.target.value); checkMatch(); });
            confirmField?.addEventListener('input', checkMatch);
            if(pwd && pwd.value) update(pwd.value);
        })();

        document.querySelectorAll('[data-password-toggle]').forEach(toggle => toggle.addEventListener('click', () => {
            const field = document.getElementById(toggle.dataset.passwordToggle), shown = field.type === 'text';
            field.type = shown ? 'password' : 'text';
            toggle.textContent = shown ? 'SHOW' : 'HIDE';
            toggle.classList.toggle('is-visible', !shown);
            toggle.setAttribute('aria-label', shown ? 'Show password' : 'Hide password');
            toggle.setAttribute('aria-pressed', String(!shown));
            field.classList.add('password-flash');
            setTimeout(() => field.classList.remove('password-flash'), 200);
        }));

        const registerButton = document.querySelector('#registerButton');
        let isSubmitting = false;
        registerButton?.addEventListener('click', event => {
            const ripple = document.createElement('i'), rect = registerButton.getBoundingClientRect();
            ripple.className = 'button-ripple';
            ripple.style.left = `${event.clientX - rect.left}px`;
            ripple.style.top = `${event.clientY - rect.top}px`;
            registerButton.append(ripple);
            ripple.addEventListener('animationend', () => ripple.remove());
        });

        document.querySelector('#registrationForm')?.addEventListener('submit', function (e) {
            if (isSubmitting) { e.preventDefault(); return; }

            if (!this.checkValidity()) {
                e.preventDefault();
                this.reportValidity();
                return;
            }

            const pwd = document.getElementById('password');
            const confirmField = document.getElementById('password_confirmation');
            const mismatchMsg = document.getElementById('password-confirmation-mismatch');
            if (pwd.value !== confirmField.value) {
                e.preventDefault();
                mismatchMsg.style.display = 'block';
                confirmField.focus();
                return;
            }

            isSubmitting = true;
            registerButton.classList.add('is-loading');
            registerButton.disabled = true;
        });
    </script>
</body>
</html>

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register — UNICare</title>
    <link rel="stylesheet" href="{{ asset('css/patient.css') }}">

    <style>
        .login-shell {
            display: flex;
            min-height: 100vh;
            width: 100vw;
            overflow: hidden;
        }

        .login-hero {
            flex: 3;
            display: flex;
            align-items: flex-end;
            justify-content: center;
            overflow: hidden;
            position: relative;
            margin-left: -10%;
        }

        .login-pylon {
            width: auto;
            height: 130%;
            max-width: none;
            object-fit: contain;
            margin-bottom: -5%;
            margin-right: 30%;
        }

        .login-panel {
            flex: 2;
            overflow-y: auto;
        }

        /* Role toggle */
        .register-role-toggle {
            display: flex;
            gap: 0;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #d1d5db;
            margin-bottom: 1rem;
        }

        .register-role-toggle input[type="radio"] {
            display: none;
        }

        .register-role-toggle label {
            flex: 1;
            text-align: center;
            padding: 0.55rem 0;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 500;
            color: #6b7280;
            background: #f9fafb;
            transition: background 0.15s, color 0.15s;
            user-select: none;
        }

        .register-role-toggle input[type="radio"]:checked + label {
            background: var(--color-primary, #2563eb);
            color: #fff;
        }

        /* Two-column name row */
        .register-row {
            display: flex;
            gap: 0.75rem;
        }

        .register-row .login-input {
            flex: 1;
            min-width: 0;
        }

        /* Login link */
        .register-login-link {
            text-align: center;
            margin-top: 1rem;
            font-size: 0.8125rem;
            color: #6b7280;
        }

        .register-login-link a {
            color: var(--color-primary, #2563eb);
            text-decoration: none;
            font-weight: 500;
        }

        .register-login-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body class="login-body">
    <div class="login-shell">
        <aside class="login-hero" aria-hidden="true">
            <img
                src="{{ asset('images/pup-pylon.png') }}"
                alt=""
                class="login-pylon"
                style="position:fixed top:0 bottom:0 border:none width:100% height:300px transform:scale(2)"
            >
        </aside>

        <main class="login-panel">
            <div class="login-form-wrap">
                <div class="login-brand">
                    <img
                        src="{{ asset('images/unicare-logo.png') }}"
                        alt=""
                        class="login-logo-img"
                    >
                    <div class="login-brand-text">
                        <p class="login-brand-title">UNICare</p>
                        <p class="login-brand-subtitle">The University Hospital</p>
                    </div>
                </div>

                @if ($errors->any())
                    <div class="login-flash login-flash--error">
                        @if ($errors->has('error'))
                            <p>{{ $errors->first('error') }}</p>
                        @else
                            <p>{{ $errors->first() }}</p>
                        @endif
                    </div>
                @endif

                <form method="POST" action="{{ route('auth.register') }}" class="login-form">
                    @csrf

                    {{-- Role toggle --}}
                    <div class="register-role-toggle">
                        <input
                            type="radio"
                            name="role"
                            id="role-patient"
                            value="patient"
                            {{ old('role', 'patient') === 'patient' ? 'checked' : '' }}
                        >
                        <label for="role-patient">Patient</label>

                        <input
                            type="radio"
                            name="role"
                            id="role-doctor"
                            value="doctor"
                            {{ old('role') === 'doctor' ? 'checked' : '' }}
                        >
                        <label for="role-doctor">Doctor</label>
                    </div>

                    <input
                        type="text"
                        name="name"
                        class="login-input"
                        placeholder="Full Name"
                        value="{{ old('name') }}"
                        required
                        autofocus
                    >

                    <input
                        type="email"
                        name="email"
                        class="login-input"
                        placeholder="Email Address"
                        value="{{ old('email') }}"
                        required
                    >

                    <input
                        type="password"
                        name="password"
                        class="login-input"
                        placeholder="Password"
                        required
                    >

                    <input
                        type="password"
                        name="password_confirmation"
                        class="login-input"
                        placeholder="Confirm Password"
                        required
                    >

                    <button type="submit" class="login-submit">
                        Create Account
                    </button>
                </form>

                <p class="register-login-link">
                    Already have an account? <a href="{{ route('auth.login') }}">Log in</a>
                </p>
            </div>
        </main>
    </div>
</body>
</html>
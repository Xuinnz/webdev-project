<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login — UNICare</title>
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
            justify-content: flex-start; 
            overflow: hidden; 
            position: relative;
        }

        .login-pylon {
            height: 90%;
            width: auto;
            object-fit: contain;
            margin-bottom: -5%;
            margin-left: -5%;
            transform: scale(1.15);
            transform-origin: bottom left;
        }

        .login-panel {
            flex: 3;
        }

        .register-link {
            text-align: center;
            margin-top: 1rem;
            font-size: 0.8125rem;
            color: #6b7280;
        }

        .register-link a {
            color: var(--color-primary, #2563eb);
            text-decoration: none;
            font-weight: 400;
        }

        .register-link a:hover {
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

                @if (session('success'))
                    <div class="login-flash login-flash--success">
                        {{ session('success') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="login-flash login-flash--error">
                        @if ($errors->has('error'))
                            <p>{{ $errors->first('error') }}</p>
                        @else
                            <p>{{ $errors->first() }}</p>
                        @endif
                    </div>
                @endif

                <form method="POST" action="{{ route('auth.login') }}" class="login-form">
                    @csrf

                    <input
                        type="email"
                        name="email"
                        class="login-input"
                        placeholder="Enter Email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                    >

                    <input
                        type="password"
                        name="password"
                        class="login-input"
                        placeholder="Enter Password"
                        required
                    >

                    <button type="submit" class="login-submit">
                        Login
                    </button>

                    <p class="register-link">
                    Don't have an account yet? <a href="{{ route('auth.register.show') }}">Register</a>
                    </p>
                </form>
            </div>
        </main>
    </div>
</body>
</html>

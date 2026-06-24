<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login — UNICare</title>
    <link rel="stylesheet" href="{{ asset('css/patient.css') }}">

     <style>
        /* Ensures the main container takes full viewport height and manages its children as flex items */
        .login-shell {
            display: flex; /* Make it a flex container */
            min-height: 100vh;
            width: 100vw; /* Ensure it takes full viewport width */
            overflow: hidden; /* Prevents horizontal scroll if children overflow */
        }

        /* Styles for the left hero section containing the image */
        .login-hero {
            flex: 3; /* Allocates 3 parts of the available space */
            display: flex;
            align-items: flex-end; /* Aligns the image element to the bottom of this container */
            justify-content: center; /* Centers the image element horizontally within its column */
            overflow: hidden; /* Crucial: Clips any part of the image *element* that goes outside its bounds, without distorting the image content */
            position: relative; /* Allows for positioning children if needed */
            margin-left: -10%;
        }

        /* Styles for the image itself */
        .login-pylon {
            width: auto; /* Let width be determined by height to maintain aspect ratio */
            height: 130%; /* Make the image element significantly taller than its container */
            max-width: none; /* Override any max-width that might be inherited and constrain it */
            object-fit: contain; /* CRUCIAL: Ensures the entire image content is visible within its element, scaling it without cropping */
            margin-bottom: -5%; 
            margin-right: 30%; /* Optional: Adjusts horizontal positioning if needed */
        }

        /* Styles for the right panel containing the login form */
        .login-panel {
            flex: 2; /* Allocates 2 parts of the available space */
            /* Add any other specific styles for the login-panel if necessary */
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
                </form>
            </div>
        </main>
    </div>
</body>
</html>

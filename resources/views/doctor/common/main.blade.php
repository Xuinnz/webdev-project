<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'UNICare') — Doctor Portal</title>
    <link rel="stylesheet" href="{{ asset('css/patient.css') }}">
    @vite(['resources/js/app.js'])
</head>
<body x-data="pageTransitions">
    <div class="unicare-shell" :class="leaving ? 'animate-unicare-out' : ''">
        <aside class="unicare-sidebar animate-unicare-in-left">
            <div>
                <div class="unicare-brand animate-unicare-in stagger-1">
                    <div class="unicare-logo">
                        <img src="{{ asset('images/unicare-logo.png') }}" alt="UNICare logo">
                    </div>
                    <div>
                        <p class="unicare-brand-title">UNICare</p>
                        <p class="unicare-brand-subtitle">Doctor Portal</p>
                    </div>
                </div>

                <nav class="unicare-nav">
                    @php
                        $links = [
                            ['label' => 'Dashboard', 'route' => 'doctor.dashboard'],
                            ['label' => 'Appointments', 'route' => 'doctor.appointments.index'],
                            ['label' => 'Patients', 'route' => 'doctor.patients.index'],
                            ['label' => 'Profile', 'route' => 'doctor.profile'],
                            ['label' => 'Schedule', 'route' => 'doctor.schedule'],
                        ];
                    @endphp

                    @foreach ($links as $index => $link)
                        <a
                            href="{{ route($link['route']) }}"
                            @click="navigate($event, '{{ route($link['route']) }}')"
                            @class([
                                'unicare-nav-link animate-unicare-in',
                                'is-active' => request()->routeIs($link['route']),
                                'stagger-' . ($index + 2) => true,
                            ])
                        >
                            {{ $link['label'] }}
                        </a>
                    @endforeach
                </nav>
            </div>

            <form action="{{ route('auth.logout') }}" method="POST">
                @csrf
                <button type="submit" class="unicare-logout animate-unicare-in stagger-6 w-full text-left border-0 bg-transparent cursor-pointer">
                    <span aria-hidden="true">&#8614;</span>
                    Logout
                </button>
            </form>
        </aside>

        <main class="unicare-main animate-unicare-in-right stagger-2">
            <div :class="leaving ? 'animate-unicare-out' : ''">
                @include('doctor.common.flash')
                @yield('content')
            </div>
        </main>
    </div>
</body>
</html>

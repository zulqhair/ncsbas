<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>NCSBAS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-light">
    @auth
        <div class="d-flex min-vh-100">
            <aside class="bg-primary text-white d-flex flex-column p-3 shadow" style="width: 250px;">
                <a class="text-white text-decoration-none fs-4 fw-bold mb-4" href="{{ route('dashboard') }}">
                    NCSBAS
                </a>

                <p class="small text-white-50 mb-4">
                    {{ auth()->user()->name }}<br>
                    {{ ucfirst(auth()->user()->role) }}
                </p>

                <nav class="nav nav-pills flex-column gap-2">
                    <a
                        class="nav-link text-white {{ request()->routeIs('dashboard') ? 'active bg-white text-primary' : '' }}"
                        href="{{ route('dashboard') }}"
                    >
                        Dashboard
                    </a>
                    <a
                        class="nav-link text-white {{ request()->routeIs('assessments.*') ? 'active bg-white text-primary' : '' }}"
                        href="{{ route('assessments.index') }}"
                    >
                        Assessor Module
                    </a>
                    @if(auth()->user()->isAdmin())
                        <a
                            class="nav-link text-white {{ request()->routeIs('admin.users.*') ? 'active bg-white text-primary' : '' }}"
                            href="{{ route('admin.users.index') }}"
                        >
                            User Management
                        </a>
                    @endif
                </nav>

                <form class="mt-auto" method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn btn-outline-light w-100" type="submit">Log out</button>
                </form>
            </aside>

            <main class="flex-grow-1 py-4">
                <div class="container-fluid px-4">
                    @if(session('status'))
                        <div class="alert alert-success">{{ session('status') }}</div>
                    @endif

                    @yield('content')
                </div>
            </main>
        </div>
    @else
        <main class="py-5">
            <div class="container">
                @yield('content')
            </div>
        </main>
    @endauth
</body>
</html>

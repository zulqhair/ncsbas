<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#071A3D">
    <title>@yield('title', 'Assessment workspace') · NCSBAS</title>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <a class="skip-link" href="#main-content">Skip to main content</a>
    <header class="site-header">
        <div class="site-container identity-row">
            <a class="brand" href="{{ route('home') }}" aria-label="NCSBAS home">
                <span class="brand-symbol"><x-icon name="shield" size="30" /></span>
                <span><strong>NCSBAS<span class="brand-period">.</span></strong><span class="brand-description">National Cyber Security<br>Baseline Assessment System</span></span>
            </a>
            <div class="identity-meta">
                @auth
                    <span class="user-avatar" aria-hidden="true">{{ mb_substr(auth()->user()->name, 0, 1) }}</span>
                    <span class="user-identity"><strong>{{ auth()->user()->name }}</strong><span>{{ ucfirst(auth()->user()->role) }} workspace</span></span>
                @else
                    <span class="identity-tag"><x-icon name="shield" /> NCSB v1.1 assessment platform</span>
                @endauth
            </div>
        </div>
        <div class="navigation-band">
            <div class="site-container navigation-container">
                <button class="menu-toggle" type="button" aria-expanded="true" aria-controls="primary-navigation" data-menu-toggle hidden><x-icon name="menu" /> Menu</button>
                <nav id="primary-navigation" class="primary-navigation" aria-label="Main navigation">
                    <div class="nav-links">
                        @auth
                            <a href="{{ route('dashboard') }}" @if(request()->routeIs('dashboard')) aria-current="page" @endif><x-icon name="grid" /> Dashboard</a>
                            <a href="{{ route('assessments.index') }}" @if(request()->routeIs('assessments.*')) aria-current="page" @endif><x-icon name="document" /> Assessor Module</a>
                            @if(auth()->user()->isAdmin() || auth()->user()->role === 'reviewer')
                                <a href="{{ route('reviews.index') }}" @if(request()->routeIs('reviews.*')) aria-current="page" @endif><x-icon name="review" /> Reviewer Module</a>
                            @endif
                            @if(auth()->user()->isAdmin())
                                <a href="{{ route('admin.users.index') }}" @if(request()->routeIs('admin.users.*')) aria-current="page" @endif><x-icon name="users" /> User management</a>
                            @endif
                        @else
                            <a href="{{ route('home') }}" @if(request()->routeIs('home')) aria-current="page" @endif>Overview</a>
                            <a href="{{ route('home') }}#assessment-process">Assessment process</a>
                            <a href="{{ route('home') }}#workspace-roles">Workspace roles</a>
                        @endauth
                    </div>
                    @auth
                        <form method="POST" action="{{ route('logout') }}">@csrf<button class="nav-action" type="submit"><x-icon name="logout" /> Log out</button></form>
                    @else
                        <a class="nav-action" href="{{ route('login') }}">Log in <x-icon name="arrow" /></a>
                    @endauth
                </nav>
            </div>
        </div>
    </header>
    <main id="main-content" class="site-container main-content" tabindex="-1">
        @if(session('status'))
            <div class="alert alert-success notice" role="status"><x-icon name="check" /><div>{{ session('status') }}</div></div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger" role="alert" tabindex="-1" data-error-summary>
                <strong>Please check the information below.</strong>
                <ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif
        @yield('content')
    </main>
    <footer class="site-footer">
        <div class="site-container footer-content">
            <div><strong>NCSBAS</strong><p>National Cyber Security Baseline Assessment System</p></div>
            <div class="footer-meta"><span>NCSB v1.1</span><span>Assess. Review. Report.</span><a href="#main-content">Back to top ↑</a></div>
        </div>
    </footer>
</body>
</html>

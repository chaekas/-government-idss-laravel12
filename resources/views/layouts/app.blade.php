<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title','Dashboard') · Government IDSS</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
@php
    $role = auth()->user()->role;
    // Support canonical and legacy super-admin role values on existing accounts.
    $adminRoles = ['system_admin', 'super_admin', 'super-admin', 'administrator', 'admin'];
    $isAdmin = in_array($role, $adminRoles, true);
    $fieldRoles = ['field_officer', 'regional_veterinary_officer'];
    $labRoles = ['laboratory_technician', 'laboratory_manager', 'zonal_laboratory_administrator'];
    $leadershipRoles = ['regional_veterinary_officer', 'mlf_officer'];
@endphp
<div class="shell">
    <aside class="sidebar">
        <a class="brand" href="{{ route('dashboard') }}"><span class="mark">ID</span><span><strong>Government IDSS</strong><small>Livestock & Fisheries</small></span></a>
        <p class="nav-label">WORKSPACE</p>
        <nav>
            <a class="{{ request()->routeIs('dashboard')?'active':'' }}" href="{{ route('dashboard') }}">▦ Dashboard</a>

            @if($isAdmin || in_array($role, $fieldRoles, true))
                <a class="{{ request()->routeIs('farmers.*')?'active':'' }}" href="{{ route('farmers.index') }}">♙ Farmers</a>
                <a class="{{ request()->routeIs('farms.*')?'active':'' }}" href="{{ route('farms.index') }}">⌂ Farms</a>
                <a class="{{ request()->routeIs('animals.*')?'active':'' }}" href="{{ route('animals.index') }}">🐔 Poultry / animals</a>
                <a class="{{ request()->routeIs('investigations.*')?'active':'' }}" href="{{ route('investigations.index') }}">◎ Investigations</a>
            @endif

            @if(in_array($role, ['farmer','field_officer','regional_veterinary_officer','mlf_officer','system_admin'], true))
                <a class="{{ request()->routeIs('cases.*')?'active':'' }}" href="{{ route('cases.index') }}">✚ Disease cases</a>
            @endif

            @if($isAdmin || in_array($role, ['field_officer','regional_veterinary_officer','laboratory_technician','laboratory_manager','zonal_laboratory_administrator','mlf_officer'], true))
                <a class="{{ request()->routeIs('samples.*')?'active':'' }}" href="{{ route('samples.index') }}">◈ Samples & custody</a>
            @endif

            @if($isAdmin || in_array($role, $labRoles, true) || $role === 'mlf_officer')
                <a class="{{ request()->routeIs('laboratory.*')?'active':'' }}" href="{{ route('laboratory.index') }}">⚗ Laboratory results</a>
            @endif

            @if($isAdmin || in_array($role, ['mlf_officer','zonal_laboratory_administrator'], true))
                <a class="{{ request()->routeIs('laboratories.*')?'active':'' }}" href="{{ route('laboratories.index') }}">▣ Laboratory registry</a>
            @endif

            @if($isAdmin || in_array($role, ['field_officer','regional_veterinary_officer','mlf_officer'], true))
                <a class="{{ request()->routeIs('outbreaks.*')?'active':'' }}" href="{{ route('outbreaks.index') }}">◉ Outbreaks</a>
            @endif

            <a class="{{ request()->routeIs('notifications.*')?'active':'' }}" href="{{ route('notifications.index') }}">♢ Notifications</a>

            @if($isAdmin || in_array($role, $leadershipRoles, true))
                <a class="{{ request()->routeIs('reports.*')?'active':'' }}" href="{{ route('reports.index') }}">▤ Reports & exports</a>
                <a class="{{ request()->routeIs('gis.*')?'active':'' }}" href="{{ route('gis.index') }}">⌖ GIS map</a>
            @endif

            @if($isAdmin)
                <a class="{{ request()->routeIs('users.*')?'active':'' }}" href="{{ route('users.index') }}">♙ Users management</a>
                <a class="{{ request()->routeIs('audit.*')?'active':'' }}" href="{{ route('audit.index') }}">▤ Audit trail</a>
            @endif
        </nav>
        <div class="side-foot">● Surveillance workspace</div>
    </aside>
    <main class="main">
        <header class="topbar">
            <div><small>MINISTRY OF LIVESTOCK AND FISHERIES</small><strong>Integrated Disease Surveillance System</strong></div>
            <div class="account"><span class="avatar">{{ strtoupper(substr(auth()->user()->name,0,1)) }}</span><span><b>{{ auth()->user()->name }}</b><small>{{ config('idss.roles.'.$role.'.label', in_array($role, config('idss.administrator_aliases', []), true) ? 'System Administrator' : str_replace('_',' ',ucfirst($role))) }}</small></span><form method="POST" action="{{ route('logout') }}">@csrf<button class="logout">Sign out</button></form></div>
        </header>
        <section class="content">
            @if(session('status'))<div class="alert success">{{ session('status') }}</div>@endif
            @if($errors->any())<div class="alert error"><b>Please check the form:</b><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            @yield('content')
        </section>
        <footer>IDSS · Early detection. Traceable investigations. Evidence-led response.</footer>
    </main>
</div>
</body>
</html>

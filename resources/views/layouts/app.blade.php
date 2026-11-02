<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}"><title>@yield('title','Dashboard') · Government IDSS</title><link rel="stylesheet" href="{{ asset('css/app.css') }}"></head>
<body><div class="shell"><aside class="sidebar"><a class="brand" href="{{ route('dashboard') }}"><span class="mark">ID</span><span><strong>Government IDSS</strong><small>Livestock & Fisheries</small></span></a><p class="nav-label">WORKSPACE</p><nav>
<a class="{{ request()->routeIs('dashboard')?'active':'' }}" href="{{ route('dashboard') }}">▦ Dashboard</a>
<a class="{{ request()->routeIs('farmers.*')?'active':'' }}" href="{{ route('farmers.index') }}">♙ Farmers</a>
<a class="{{ request()->routeIs('farms.*')?'active':'' }}" href="{{ route('farms.index') }}">⌂ Farms</a>
<a class="{{ request()->routeIs('animals.*')?'active':'' }}" href="{{ route('animals.index') }}">🐔 Poultry / animals</a>
<a class="{{ request()->routeIs('cases.*')?'active':'' }}" href="{{ route('cases.index') }}">✚ Disease cases</a>
<a class="{{ request()->routeIs('investigations.*')?'active':'' }}" href="{{ route('investigations.index') }}">◎ Investigations</a>
<a class="{{ request()->routeIs('samples.*')?'active':'' }}" href="{{ route('samples.index') }}">◈ Samples & custody</a>
<a class="{{ request()->routeIs('laboratory.*')?'active':'' }}" href="{{ route('laboratory.index') }}">⚗ Laboratory</a>
<a class="{{ request()->routeIs('outbreaks.*')?'active':'' }}" href="{{ route('outbreaks.index') }}">◉ Outbreaks</a>
@if(in_array(auth()->user()->role, ['system_admin','administrator','admin'], true))<a class="{{ request()->routeIs('users.*')?'active':'' }}" href="{{ route('users.index') }}">♙ User management</a>@endif
</nav><div class="side-foot">● Surveillance workspace</div></aside><main class="main"><header class="topbar"><div><small>MINISTRY OF LIVESTOCK AND FISHERIES</small><strong>Integrated Disease Surveillance System</strong></div><div class="account"><span class="avatar">{{ strtoupper(substr(auth()->user()->name,0,1)) }}</span><span><b>{{ auth()->user()->name }}</b><small>{{ str_replace('_',' ',ucfirst(auth()->user()->role)) }}</small></span><form method="POST" action="{{ route('logout') }}">@csrf<button class="logout">Sign out</button></form></div></header><section class="content">@if(session('status'))<div class="alert success">{{ session('status') }}</div>@endif @if($errors->any())<div class="alert error"><b>Please check the form:</b><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif @yield('content')</section><footer>IDSS · Early detection. Traceable investigations. Evidence-led response.</footer></main></div></body></html>

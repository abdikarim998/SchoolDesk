<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · School Desk</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
</head>
<body class="app-body role-{{ auth()->user()->role }}">
    <a class="skip-link" href="#main-content">Skip to content</a>
    <div class="app-frame">
        <aside class="topbar sidebar" aria-label="School portal">
            <a class="brand" href="{{ route('dashboard') }}"><span class="brand-mark" aria-hidden="true">S</span><span>School<span class="brand-light">Desk</span></span></a>
            <button class="mobile-menu-toggle" type="button" aria-label="Open navigation" aria-controls="main-navigation" aria-expanded="false">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
            </button>
            <nav class="main-nav {{ auth()->user()->role === 'admin' && request()->routeIs('admin.manage*') ? 'management-nav' : '' }}" id="main-navigation" aria-label="Main navigation">
                <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}"><span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg></span><span>Overview</span></a>
                @if(auth()->user()->role === 'admin')
                    @if(request()->routeIs('admin.manage*'))
                        @foreach(['accounts' => 'Accounts', 'classes' => 'Classes & courses', 'teaching-assignments' => 'Teaching assignments', 'assignments-exams' => 'Assignments & Exams', 'timetable' => 'Timetable', 'parent-links' => 'Parent links'] as $section => $label)
                            <a href="{{ $section === 'accounts' ? route('admin.manage') : route('admin.manage.section', $section) }}" class="{{ ($activeSection ?? 'accounts') === $section ? 'active' : '' }}"><span>{{ $label }}</span></a>
                        @endforeach
                    @else
                        <a href="{{ route('admin.manage') }}" class="{{ request()->routeIs('admin.*') ? 'active' : '' }}"><span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 20V5.5A1.5 1.5 0 0 1 5.5 4h13A1.5 1.5 0 0 1 20 5.5V20M8 8h8M8 12h8M8 16h4M2.5 20h19"/></svg></span><span>Manage school</span></a>
                    @endif
                @elseif(auth()->user()->role === 'teacher')
                    <a href="{{ route('teacher.work') }}#attendance" class="{{ request()->routeIs('teacher.work') ? 'active' : '' }}" data-section-link="attendance"><span>Lesson attendance</span></a>
                    <a href="{{ route('teacher.work') }}#marks" data-section-link="marks"><span>Assignments & exams</span></a>
                @elseif(auth()->user()->role === 'student')
                    <a href="{{ route('dashboard') }}#student-courses" data-section-link="student-courses"><span>Courses</span></a>
                    <a href="{{ route('dashboard') }}#student-timetable" data-section-link="student-timetable"><span>Timetable</span></a>
                    <a href="{{ route('dashboard') }}#student-attendance" data-section-link="student-attendance"><span>Attendance</span></a>
                    <a href="{{ route('dashboard') }}#student-assessments" data-section-link="student-assessments"><span>Assignments & exams</span></a>
                @elseif(auth()->user()->role === 'parent')
                    @if(isset($childSnapshots) && count($childSnapshots))
                        <a href="{{ route('dashboard') }}#parent-children" data-section-link="parent-children"><span>My children</span></a>
                        <a href="{{ route('dashboard') }}#parent-attendance" data-section-link="parent-attendance"><span>Attendance</span></a>
                        <a href="{{ route('dashboard') }}#parent-marks" data-section-link="parent-marks"><span>Marks & class work</span></a>
                    @endif
                @endif
            </nav>
            <div class="sidebar-bottom">
                <div class="school-context"><span class="school-context-mark">SD</span><span>School portal<small>Management system</small></span></div>
                <div class="account-menu">
                    <span class="avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                    <span class="account-name">{{ auth()->user()->name }}<small>{{ ucfirst(auth()->user()->role) }}</small></span>
                    <form method="POST" action="{{ route('logout') }}">@csrf<button class="button button-quiet button-small" type="submit">Sign out</button></form>
                </div>
            </div>
        </aside>
        <div class="app-main">
            <main class="page-shell" id="main-content">
                @if(session('status'))<div class="notice notice-success">{{ session('status') }}</div>@endif
                @if($errors->any())
                    <div class="notice notice-error"><strong>Please check the form.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                @endif
                @yield('content')
            </main>
            <footer class="site-footer">School Desk <span>·</span> School Management System</footer>
        </div>
    </div>
    <script>
        (() => {
            const header = document.querySelector('.topbar.sidebar');
            const toggle = document.querySelector('.mobile-menu-toggle');
            if (!header || !toggle) return;

            const mobile = window.matchMedia('(max-width: 900px)');
            const closeMenu = () => {
                header.classList.remove('menu-open');
                toggle.setAttribute('aria-expanded', 'false');
                toggle.setAttribute('aria-label', 'Open navigation');
            };

            toggle.addEventListener('click', () => {
                const isOpen = header.classList.toggle('menu-open');
                toggle.setAttribute('aria-expanded', String(isOpen));
                toggle.setAttribute('aria-label', isOpen ? 'Close navigation' : 'Open navigation');
            });

            document.querySelectorAll('[data-searchable-choice]').forEach(choice => {
                const search = choice.querySelector('[data-choice-search]');
                const select = choice.querySelector('[data-choice-select]');
                const emptyMessage = choice.querySelector('[data-choice-empty]');
                if (!search || !select || !select.options.length) return;

                const placeholder = select.options[0].cloneNode(true);
                const options = Array.from(select.options).slice(1).map(option => option.cloneNode(true));
                search.addEventListener('input', () => {
                    const query = search.value.trim().toLocaleLowerCase();
                    const matches = options.filter(option => option.textContent.toLocaleLowerCase().includes(query));
                    const selectedValue = select.value;
                    select.replaceChildren(placeholder.cloneNode(true), ...matches.map(option => option.cloneNode(true)));
                    select.value = matches.some(option => option.value === selectedValue) ? selectedValue : '';
                    if (emptyMessage) emptyMessage.hidden = matches.length > 0;
                });
            });

            header.querySelectorAll('.main-nav a').forEach(link => link.addEventListener('click', closeMenu));
            document.addEventListener('click', event => {
                if (mobile.matches && !header.contains(event.target)) closeMenu();
            });
            document.addEventListener('keydown', event => {
                if (event.key === 'Escape') closeMenu();
            });
            mobile.addEventListener('change', closeMenu);

            const navLinks = [...document.querySelectorAll('.main-nav [data-section-link], .section-nav a[href*="#"]')];
            const sectionLinks = new Map();
            navLinks.forEach(link => {
                try {
                    const targetUrl = new URL(link.href, window.location.href);
                    if (targetUrl.pathname !== window.location.pathname || !targetUrl.hash) return;
                    const target = document.getElementById(decodeURIComponent(targetUrl.hash.slice(1)));
                    if (!target) return;
                    if (!sectionLinks.has(target.id)) sectionLinks.set(target.id, []);
                    sectionLinks.get(target.id).push(link);
                } catch (_) { /* Ignore malformed optional navigation links. */ }
            });

            if ('IntersectionObserver' in window && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                const revealItems = [...document.querySelectorAll('.page-heading, .stat-card, .panel, .callout')];
                revealItems.forEach((item, index) => {
                    item.classList.add('reveal-on-scroll');
                    item.style.setProperty('--reveal-delay', `${Math.min((index % 5) * 55, 220)}ms`);
                });
                const revealObserver = new IntersectionObserver(entries => {
                    entries.forEach(entry => {
                        if (!entry.isIntersecting) return;
                        entry.target.classList.add('is-visible');
                        revealObserver.unobserve(entry.target);
                    });
                }, { threshold: 0.08, rootMargin: '0px 0px -28px 0px' });
                revealItems.forEach(item => revealObserver.observe(item));

                if (sectionLinks.size) {
                    const sectionObserver = new IntersectionObserver(entries => {
                        const visible = entries.filter(entry => entry.isIntersecting)
                            .sort((a, b) => b.intersectionRatio - a.intersectionRatio)[0];
                        if (!visible) return;
                        document.querySelectorAll('.main-nav a.active, .section-nav a.active').forEach(link => {
                            link.classList.remove('active');
                            link.removeAttribute('aria-current');
                        });
                        sectionLinks.get(visible.target.id)?.forEach(link => {
                            link.classList.add('active');
                            link.setAttribute('aria-current', 'location');
                        });
                    }, { threshold: [0.1, 0.35], rootMargin: '-18% 0px -58% 0px' });
                    sectionLinks.forEach((links, id) => {
                        const target = document.getElementById(id);
                        if (target) sectionObserver.observe(target);
                    });
                }
            }
        })();
    </script>
</body>
</html>

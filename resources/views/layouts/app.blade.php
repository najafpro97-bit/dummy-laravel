<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Dummy Test Site' }} &middot; {{ config('app.name') }}</title>
    <style>
        :root { --brand: #ff2d20; --bg: #0f172a; --card: #1e293b; --text: #e2e8f0; --muted: #94a3b8; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif;
            background: radial-gradient(1200px 600px at 50% -10%, #1e293b 0%, var(--bg) 60%);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 18px 28px;
            border-bottom: 1px solid rgba(148, 163, 184, .2);
        }
        .logo { display: flex; align-items: center; gap: 10px; font-weight: 700; letter-spacing: .3px; }
        .logo .dot { width: 12px; height: 12px; border-radius: 50%; background: var(--brand); box-shadow: 0 0 18px var(--brand); }
        nav a {
            color: var(--muted);
            text-decoration: none;
            margin-left: 20px;
            font-size: 14px;
            transition: color .15s ease;
        }
        nav a:hover, nav a.active { color: #fff; }
        main { flex: 1; width: 100%; max-width: 900px; margin: 0 auto; padding: 56px 28px; }
        h1 { font-size: clamp(28px, 5vw, 44px); line-height: 1.15; margin: 0 0 12px; }
        p.lead { color: var(--muted); font-size: 17px; line-height: 1.6; max-width: 640px; }
        .card { background: var(--card); border: 1px solid rgba(148,163,184,.18); border-radius: 14px; padding: 22px; margin-top: 28px; }
        ul.clean { list-style: none; padding: 0; margin: 0; }
        ul.clean li { padding: 10px 0; border-bottom: 1px dashed rgba(148,163,184,.2); font-size: 15px; }
        ul.clean li:last-child { border-bottom: none; }
        .badge { display: inline-block; font-size: 12px; padding: 4px 10px; border-radius: 999px; background: rgba(255,45,32,.15); color: #ff8a80; border: 1px solid rgba(255,45,32,.35); }
        code { background: rgba(148,163,184,.15); padding: 2px 6px; border-radius: 6px; font-size: 13px; }
        footer { padding: 22px 28px; text-align: center; color: var(--muted); font-size: 13px; border-top: 1px solid rgba(148,163,184,.2); }
    </style>
</head>
<body>
    <header>
        <div class="logo"><span class="dot"></span> {{ config('app.name') }}</div>
        <nav>
            <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}">Home</a>
            <a href="{{ route('about') }}" class="{{ request()->routeIs('about') ? 'active' : '' }}">About</a>
            <a href="{{ route('health') }}" target="_blank">Health</a>
        </nav>
    </header>

    <main>
        @yield('content')
    </main>

    <footer>
        &copy; {{ date('Y') }} {{ config('app.name') }} &middot; Dummy test deployment
    </footer>
</body>
</html>

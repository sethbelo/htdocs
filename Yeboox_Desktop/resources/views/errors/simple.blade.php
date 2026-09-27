<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Yeboox POS — {{ $title }}</title>
    <style>
        :root { color-scheme: light dark; font-family: Inter, ui-sans-serif, system-ui, sans-serif; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 24px; background: #09090b; color: #18181b; }
        .shell { width: min(100%, 700px); overflow: hidden; border-radius: 28px; background: #fff; box-shadow: 0 30px 80px rgba(0,0,0,.35); }
        .hero { padding: 28px; color: #fff; background: linear-gradient(135deg,#6d28d9,#9333ea,#c026d3); }
        .logo { height: 42px; width: auto; padding: 8px 12px; border-radius: 12px; background: #fff; }
        .body { padding: 32px; }
        .code { display: inline-flex; margin-bottom: 14px; padding: 6px 10px; border-radius: 999px; color: #6d28d9; background: #f3e8ff; font-size: 12px; font-weight: 800; letter-spacing: .12em; }
        h1 { margin: 0; font-size: clamp(1.5rem,4vw,2.1rem); }
        p { color: #52525b; line-height: 1.65; }
        .actions { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 24px; }
        button, a { border: 0; border-radius: 12px; padding: 12px 18px; font: inherit; font-weight: 700; cursor: pointer; text-decoration: none; }
        button { color: #fff; background: #7c3aed; }
        a { color: #3f3f46; background: #f4f4f5; }
        @media (prefers-color-scheme: dark) { .shell { background:#18181b; color:#fafafa; } p { color:#d4d4d8; } a { background:#27272a; color:#e4e4e7; } }
    </style>
</head>
<body>
    <main class="shell">
        <div class="hero"><img class="logo" src="{{ asset('images/yeboox-logo.png') }}" alt="Yeboox"></div>
        <section class="body">
            <span class="code">ERREUR {{ $code }}</span>
            <h1>{{ $title }}</h1>
            <p>{{ $message }}</p>
            <div class="actions">
                @if($retry ?? false)<button type="button" onclick="window.location.reload()">Réessayer</button>@endif
                <a href="{{ url('/') }}">Revenir à l’accueil</a>
            </div>
        </section>
    </main>
</body>
</html>

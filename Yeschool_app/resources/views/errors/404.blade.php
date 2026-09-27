<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Non Trouvée | Yeschool</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #0a0a10;
            position: relative;
            overflow: hidden;
        }

        .bg-dashboard {
            position: fixed; inset: 0; z-index: 0;
        }
        .bg-dashboard::before {
            content: '';
            position: absolute; inset: 0;
            background: linear-gradient(135deg, #111118 0%, #0d0d14 50%, #0f0e18 100%);
        }
        .bg-dashboard .sidebar-ghost {
            position: absolute; left: 0; top: 0; bottom: 0; width: 260px;
            background: rgba(255,255,255,0.02);
            border-right: 1px solid rgba(255,255,255,0.04);
        }
        .bg-dashboard .topbar-ghost {
            position: absolute; top: 0; left: 260px; right: 0; height: 64px;
            background: rgba(255,255,255,0.015);
            border-bottom: 1px solid rgba(255,255,255,0.04);
        }
        .bg-dashboard .card-ghost {
            position: absolute; border-radius: 16px;
            background: rgba(255,255,255,0.015);
            border: 1px solid rgba(255,255,255,0.03);
        }
        .bg-dashboard .card-ghost:nth-child(3) { top: 90px; left: 290px; width: 280px; height: 120px; }
        .bg-dashboard .card-ghost:nth-child(4) { top: 90px; left: 590px; width: 280px; height: 120px; }
        .bg-dashboard .card-ghost:nth-child(5) { top: 90px; right: 30px; width: 280px; height: 120px; }
        .bg-dashboard .card-ghost:nth-child(6) { top: 240px; left: 290px; right: 30px; height: 300px; }

        .overlay {
            position: fixed; inset: 0;
            background: rgba(0, 0, 0, 0.65);
            backdrop-filter: blur(6px);
            -webkit-backdrop-filter: blur(6px);
            z-index: 10;
        }

        .error-modal {
            position: relative; z-index: 20;
            max-width: 420px; width: calc(100% - 32px);
            background: linear-gradient(165deg, rgba(20, 20, 30, 0.95), rgba(12, 12, 20, 0.98));
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 24px;
            padding: 40px 32px 32px;
            text-align: center;
            box-shadow:
                0 0 0 1px rgba(255,255,255,0.03),
                0 25px 60px rgba(0, 0, 0, 0.5),
                0 0 120px rgba(99, 102, 241, 0.06);
            animation: modalIn 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }
        @keyframes modalIn {
            0% { opacity: 0; transform: scale(0.92) translateY(20px); }
            100% { opacity: 1; transform: scale(1) translateY(0); }
        }

        .icon-badge {
            width: 64px; height: 64px; border-radius: 18px;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 24px;
            background: rgba(99, 102, 241, 0.08);
            border: 1px solid rgba(99, 102, 241, 0.2);
            color: #818cf8;
            box-shadow: 0 0 30px rgba(99, 102, 241, 0.08);
        }

        .error-code {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 72px; font-weight: 700; line-height: 1;
            background: linear-gradient(135deg, #818cf8, #a78bfa);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            background-clip: text; margin-bottom: 8px;
        }
        .error-title { font-size: 18px; font-weight: 700; color: #f1f5f9; margin-bottom: 8px; }
        .error-desc { font-size: 13px; color: #94a3b8; line-height: 1.7; margin-bottom: 28px; }
        .divider { height: 1px; background: linear-gradient(90deg, transparent, rgba(255,255,255,0.06), transparent); margin-bottom: 24px; }

        .btn-primary {
            display: block; width: 100%; padding: 12px 20px; border-radius: 14px;
            font-size: 14px; font-weight: 600; font-family: 'Inter', sans-serif;
            color: #fff; text-decoration: none;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            border: none; cursor: pointer; transition: all 0.2s;
            box-shadow: 0 4px 16px rgba(79, 70, 229, 0.2);
        }
        .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 24px rgba(79, 70, 229, 0.3); }
        .btn-primary:active { transform: scale(0.97); }

        .btn-ghost {
            display: block; width: 100%; padding: 10px 20px; border-radius: 14px;
            font-size: 13px; font-weight: 500; font-family: 'Inter', sans-serif;
            color: #94a3b8; text-decoration: none;
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.06);
            cursor: pointer; transition: all 0.2s; margin-top: 10px; text-align: center;
        }
        .btn-ghost:hover { color: #e2e8f0; background: rgba(255,255,255,0.06); border-color: rgba(255,255,255,0.1); }

        .countdown { font-size: 12px; color: #64748b; margin-top: 16px; }
        .countdown span { color: #94a3b8; font-weight: 600; }
    </style>
</head>
<body>
    <div class="bg-dashboard">
        <div class="sidebar-ghost"></div>
        <div class="topbar-ghost"></div>
        <div class="card-ghost"></div>
        <div class="card-ghost"></div>
        <div class="card-ghost"></div>
        <div class="card-ghost"></div>
    </div>
    <div class="overlay"></div>

    <div class="error-modal">
        <div class="icon-badge">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                <line x1="8" y1="11" x2="14" y2="11"/>
            </svg>
        </div>

        <div class="error-code">404</div>
        <h1 class="error-title">Page Non Trouvée</h1>
        <p class="error-desc">
            La page que vous recherchez n'existe pas ou a été déplacée. Vérifiez l'URL ou retournez au tableau de bord.
        </p>

        @php
            $dashboardUrl = rescue(fn() => \App\Filament\Pages\ProDashboard::getUrl(), url(env('FILAMENT_PATH', 'admin')));
        @endphp

        <a href="{{ $dashboardUrl }}" class="btn-primary">
            Retour au Tableau de Bord
        </a>
        <button class="btn-ghost" onclick="history.back()">
            ← Revenir en arrière
        </button>

        <p class="countdown">Redirection automatique dans <span id="timer">10</span>s</p>
    </div>

    <script>
        let seconds = 10;
        const timer = document.getElementById('timer');
        const interval = setInterval(() => {
            seconds--;
            timer.textContent = seconds;
            if (seconds <= 0) { clearInterval(interval); window.location.href = "{{ $dashboardUrl }}"; }
        }, 1000);
    </script>
</body>
</html>

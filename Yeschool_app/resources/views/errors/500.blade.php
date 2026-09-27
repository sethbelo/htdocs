<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - Erreur Serveur | Yeschool</title>
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
                0 0 120px rgba(239, 68, 68, 0.06);
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
            background: rgba(239, 68, 68, 0.08);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #f87171;
            box-shadow: 0 0 30px rgba(239, 68, 68, 0.08);
        }

        .error-code {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 72px; font-weight: 700; line-height: 1;
            background: linear-gradient(135deg, #ef4444, #ec4899);
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
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
            </svg>
        </div>

        <div class="error-code">500</div>
        <h1 class="error-title">Erreur Interne du Serveur</h1>
        <p class="error-desc">
            Un problème inattendu est survenu. Nos équipes ont été notifiées et travaillent à résoudre le problème. Veuillez réessayer dans quelques instants.
        </p>

        @php
            $dashboardUrl = rescue(fn() => \App\Filament\Pages\ProDashboard::getUrl(), url(env('FILAMENT_PATH', 'admin')));
        @endphp

        <a href="{{ $dashboardUrl }}" class="btn-primary">
            Retour au Tableau de Bord
        </a>
        <button class="btn-ghost" onclick="location.reload()">
            ↻ Réessayer cette page
        </button>

        <p class="countdown">Redirection automatique dans <span id="timer">12</span>s</p>
    </div>

    <script>
        let seconds = 12;
        const timer = document.getElementById('timer');
        const interval = setInterval(() => {
            seconds--;
            timer.textContent = seconds;
            if (seconds <= 0) { clearInterval(interval); window.location.href = "{{ $dashboardUrl }}"; }
        }, 1000);
    </script>
</body>
</html>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yeboox POS — Hors Connexion</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
            color: #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            text-align: center;
            padding: 2rem;
        }
        .container { max-width: 480px; }
        .icon-wrap {
            width: 120px; height: 120px; margin: 0 auto 2rem;
            border-radius: 50%;
            background: rgba(16, 185, 129, 0.1);
            border: 2px solid rgba(16, 185, 129, 0.2);
            display: flex; align-items: center; justify-content: center;
            animation: pulse 2s ease-in-out infinite;
        }
        .icon { font-size: 3.5rem; }
        h1 { font-size: 2rem; font-weight: 800; margin-bottom: 0.75rem; color: #f8fafc; }
        p { color: #94a3b8; line-height: 1.7; margin-bottom: 1.5rem; font-size: 1.05rem; }
        .btn {
            display: inline-flex; align-items: center; gap: 0.5rem;
            padding: 1rem 2.5rem;
            background: linear-gradient(135deg, #10b981, #059669);
            color: white; border: none;
            border-radius: 1rem; font-size: 1rem; font-weight: 700;
            cursor: pointer; text-decoration: none;
            transition: all 0.3s; box-shadow: 0 8px 30px rgba(16, 185, 129, 0.3);
        }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 12px 40px rgba(16, 185, 129, 0.4); }
        .status {
            margin-top: 2rem; padding: 0.75rem 1.5rem;
            background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: 0.75rem; font-size: 0.85rem; color: #fca5a5;
            display: flex; align-items: center; gap: 0.5rem; justify-content: center;
        }
        .dot { width: 8px; height: 8px; border-radius: 50%; background: #ef4444; animation: blink 1.5s infinite; }
        @keyframes pulse { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.05); } }
        @keyframes blink { 0%, 100% { opacity: 1; } 50% { opacity: 0.3; } }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon-wrap">
            <span class="icon">📡</span>
        </div>
        <h1>Mode Hors Connexion</h1>
        <p>Vous n'êtes pas connecté à Internet. Vos commandes en cours sont sauvegardées et seront synchronisées automatiquement au retour de la connexion.</p>
        <button class="btn" onclick="window.location.reload()">
            🔄 Réessayer la connexion
        </button>
        <div class="status">
            <span class="dot"></span>
            En attente de connexion réseau...
        </div>
    </div>
    <script>
        window.addEventListener('online', () => window.location.reload());
    </script>
</body>
</html>

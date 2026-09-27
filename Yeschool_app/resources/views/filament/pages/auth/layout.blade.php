<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      x-data="yeschoolTheme()"
      :class="{ 'dark': resolvedTheme === 'dark' }"
      :data-theme="resolvedTheme === 'dark' ? 'dark' : 'light'"
      x-cloak>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="YESCHOOL — Plateforme SaaS de gestion scolaire intelligente par Yetoo Technologie. Connectez-vous à votre espace.">

    <title>Connexion — YESCHOOL</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700,800&display=swap" rel="stylesheet" />

    <!-- Anti-FOUC: Apply dark class before page paints -->
    <script>
        (function() {
            try {
                var t = localStorage.getItem('sigs-theme') || "{{ \App\Models\Setting::get('default_theme', 'system') }}";
                var isDark = t === 'dark' || (t === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
                if (isDark) {
                    document.documentElement.classList.add('dark');
                    document.documentElement.setAttribute('data-theme', 'dark');
                } else {
                    document.documentElement.classList.remove('dark');
                    document.documentElement.setAttribute('data-theme', 'light');
                }
            } catch(e) {}
        })();
    </script>

    @livewireStyles

    <style>
        /* ═══════════════════════════════════════════════════
           YESCHOOL LOGIN — Custom Design System
           Palette: Blue #2563eb · Violet #7c3aed · Cyan #06b6d4
           ═══════════════════════════════════════════════════ */

        *, *::before, *::after { box-sizing: border-box; }

        html, body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            min-height: 100dvh;
            background-color: var(--ys-bg);
            transition: background-color var(--ys-transition);
        }

        :root {
            --ys-bg: #f8fafc;
            --ys-primary: #2563eb;
            --ys-primary-hover: #1d4ed8;
            --ys-secondary: #7c3aed;
            --ys-accent: #06b6d4;
            --ys-text: #0f172a;
            --ys-text-secondary: #475569;
            --ys-muted: #64748b;
            --ys-card-bg: rgba(255, 255, 255, 0.88);
            --ys-card-border: rgba(148, 163, 184, 0.25);
            --ys-input-bg: #ffffff;
            --ys-input-border: #cbd5e1;
            --ys-input-focus: #2563eb;
            --ys-blob-1: rgba(37, 99, 235, 0.12);
            --ys-blob-2: rgba(124, 58, 237, 0.10);
            --ys-blob-3: rgba(6, 182, 212, 0.08);
            --ys-shadow: 0 25px 60px -12px rgba(0, 0, 0, 0.08);
            --ys-shadow-lg: 0 32px 80px -16px rgba(0, 0, 0, 0.12);
            --ys-radius: 1rem;
            --ys-transition: 300ms cubic-bezier(0.4, 0, 0.2, 1);
        }

        .dark {
            --ys-bg: #020617;
            --ys-primary: #60a5fa;
            --ys-primary-hover: #93bbfd;
            --ys-secondary: #a78bfa;
            --ys-accent: #22d3ee;
            --ys-text: #f8fafc;
            --ys-text-secondary: #cbd5e1;
            --ys-muted: #94a3b8;
            --ys-card-bg: rgba(15, 23, 42, 0.82);
            --ys-card-border: rgba(148, 163, 184, 0.12);
            --ys-input-bg: rgba(30, 41, 59, 0.6);
            --ys-input-border: rgba(100, 116, 139, 0.4);
            --ys-input-focus: #60a5fa;
            --ys-blob-1: rgba(96, 165, 250, 0.08);
            --ys-blob-2: rgba(167, 139, 250, 0.07);
            --ys-blob-3: rgba(34, 211, 238, 0.06);
            --ys-shadow: 0 25px 60px -12px rgba(0, 0, 0, 0.4);
            --ys-shadow-lg: 0 32px 80px -16px rgba(0, 0, 0, 0.5);
        }

        /* Page */
        .ys-page {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background-color: var(--ys-bg);
            color: var(--ys-text);
            min-height: 100vh;
            min-height: 100dvh;
            width: 100%;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow-y: auto;
            padding: clamp(1.5rem, 5vh, 3rem) 1rem;
            transition: background-color var(--ys-transition), color var(--ys-transition);
        }

        /* ── Background Layer ── */
        .ys-bg-layer {
            position: fixed;
            inset: 0;
            overflow: hidden;
            z-index: 0;
            pointer-events: none;
        }

        .ys-blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            will-change: transform;
        }

        .ys-blob-1 {
            width: min(600px, 60vw);
            height: min(600px, 60vw);
            background: var(--ys-blob-1);
            top: -10%;
            right: -5%;
            animation: ys-float-1 20s ease-in-out infinite;
        }

        .ys-blob-2 {
            width: min(500px, 50vw);
            height: min(500px, 50vw);
            background: var(--ys-blob-2);
            bottom: -15%;
            left: -10%;
            animation: ys-float-2 25s ease-in-out infinite;
        }

        .ys-blob-3 {
            width: min(350px, 40vw);
            height: min(350px, 40vw);
            background: var(--ys-blob-3);
            top: 40%;
            left: 50%;
            animation: ys-float-3 18s ease-in-out infinite;
        }

        @keyframes ys-float-1 {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(-40px, 30px) scale(1.05); }
            66% { transform: translate(20px, -20px) scale(0.95); }
        }
        @keyframes ys-float-2 {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(30px, -40px) scale(1.08); }
            66% { transform: translate(-20px, 20px) scale(0.92); }
        }
        @keyframes ys-float-3 {
            0%, 100% { transform: translate(-50%, 0) scale(1); }
            50% { transform: translate(-50%, -30px) scale(1.1); }
        }

        /* Grid pattern */
        .ys-grid-pattern {
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(var(--ys-card-border) 1px, transparent 1px),
                linear-gradient(90deg, var(--ys-card-border) 1px, transparent 1px);
            background-size: 60px 60px;
            opacity: 0.3;
            mask-image: radial-gradient(ellipse 70% 60% at 50% 50%, black 20%, transparent 70%);
            -webkit-mask-image: radial-gradient(ellipse 70% 60% at 50% 50%, black 20%, transparent 70%);
        }

        /* ── Main Container ── */
        .ys-container {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: clamp(2rem, 5vw, 4rem);
            width: 100%;
            max-width: 1100px;
            padding: 0;
        }

        /* ── Left Panel (Features) ── */
        .ys-features-panel {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        .ys-features-title {
            font-size: 2.25rem;
            font-weight: 800;
            line-height: 1.15;
            letter-spacing: -0.03em;
        }

        .ys-features-title .ys-gradient-text {
            background: linear-gradient(135deg, var(--ys-primary), var(--ys-secondary), var(--ys-accent));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .ys-features-subtitle {
            color: var(--ys-muted);
            font-size: 1.05rem;
            line-height: 1.6;
            max-width: 380px;
        }

        .ys-feature-list {
            display: flex;
            flex-direction: column;
            gap: 0.875rem;
        }

        .ys-feature-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.925rem;
            color: var(--ys-text-secondary);
            transition: color var(--ys-transition);
        }

        .ys-feature-item:hover {
            color: var(--ys-text);
        }

        .ys-feature-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 10px;
            flex-shrink: 0;
            transition: transform var(--ys-transition), box-shadow var(--ys-transition);
        }

        .ys-feature-item:hover .ys-feature-icon {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px -4px rgba(37, 99, 235, 0.25);
        }

        .ys-feature-icon.blue   { background: rgba(37, 99, 235, 0.1); color: var(--ys-primary); }
        .ys-feature-icon.violet { background: rgba(124, 58, 237, 0.1); color: var(--ys-secondary); }
        .ys-feature-icon.cyan   { background: rgba(6, 182, 212, 0.1); color: var(--ys-accent); }
        .ys-feature-icon.green  { background: rgba(16, 185, 129, 0.1); color: #10b981; }

        /* ── Login Card ── */
        .ys-login-card {
            width: 100%;
            max-width: 420px;
            flex-shrink: 0;
            background: var(--ys-card-bg);
            backdrop-filter: blur(24px) saturate(1.4);
            -webkit-backdrop-filter: blur(24px) saturate(1.4);
            border: 1px solid var(--ys-card-border);
            border-radius: var(--ys-radius);
            padding: 2.5rem 2.25rem 2rem;
            box-shadow: var(--ys-shadow);
            transition: background var(--ys-transition), border-color var(--ys-transition), box-shadow var(--ys-transition);
            animation: ys-card-enter 0.6s cubic-bezier(0.16, 1, 0.3, 1) both;
        }

        @keyframes ys-card-enter {
            from {
                opacity: 0;
                transform: translateY(24px) scale(0.97);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .ys-login-card:hover {
            box-shadow: var(--ys-shadow-lg);
        }

        /* Logo */
        .ys-logo-block {
            text-align: center;
            margin-bottom: 1.75rem;
        }

        .ys-logo-icon {
            width: 52px;
            height: 52px;
            margin: 0 auto 0.75rem;
            border-radius: 14px;
            background: linear-gradient(135deg, var(--ys-primary), var(--ys-secondary));
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 24px -6px rgba(37, 99, 235, 0.35);
        }

        .ys-logo-name {
            font-size: 1.45rem;
            font-weight: 800;
            letter-spacing: -0.03em;
            background: linear-gradient(135deg, var(--ys-primary), var(--ys-secondary));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .ys-logo-tagline {
            font-size: 0.8rem;
            color: var(--ys-muted);
            margin-top: 0.15rem;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            font-weight: 500;
        }

        .ys-card-title {
            text-align: center;
            font-size: 0.95rem;
            color: var(--ys-text-secondary);
            margin-bottom: 1.5rem;
        }

        /* Form */
        .ys-form-group {
            margin-bottom: 1.125rem;
        }

        .ys-label {
            display: block;
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--ys-text);
            margin-bottom: 0.375rem;
        }

        .ys-input-wrapper {
            position: relative;
        }

        .ys-input {
            width: 100%;
            padding: 0.6875rem 0.875rem;
            font-size: 0.9rem;
            font-family: inherit;
            color: var(--ys-text);
            background: var(--ys-input-bg);
            border: 1.5px solid var(--ys-input-border);
            border-radius: 0.625rem;
            outline: none;
            transition: border-color var(--ys-transition), box-shadow var(--ys-transition), background var(--ys-transition);
        }

        .ys-input::placeholder {
            color: var(--ys-muted);
            opacity: 0.7;
        }

        .ys-input:focus {
            border-color: var(--ys-input-focus);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }

        .dark .ys-input:focus {
            box-shadow: 0 0 0 3px rgba(96, 165, 250, 0.15);
        }

        .ys-input.is-error {
            border-color: #ef4444;
        }

        .ys-input.is-error:focus {
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.12);
        }

        .ys-password-toggle {
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: var(--ys-muted);
            padding: 0.25rem;
            display: flex;
            transition: color var(--ys-transition);
        }

        .ys-password-toggle:hover {
            color: var(--ys-text);
        }

        .ys-error-text {
            font-size: 0.775rem;
            color: #ef4444;
            margin-top: 0.3rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        /* Row: remember + forgot */
        .ys-form-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
        }

        .ys-checkbox-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.825rem;
            color: var(--ys-text-secondary);
            cursor: pointer;
            user-select: none;
        }

        .ys-checkbox {
            width: 16px;
            height: 16px;
            accent-color: var(--ys-primary);
            border-radius: 4px;
            cursor: pointer;
        }

        .ys-forgot-link {
            font-size: 0.8rem;
            font-weight: 500;
            color: var(--ys-primary);
            text-decoration: none;
            transition: color var(--ys-transition);
        }

        .ys-forgot-link:hover {
            color: var(--ys-secondary);
            text-decoration: underline;
        }

        /* Submit */
        .ys-btn-primary {
            width: 100%;
            padding: 0.75rem;
            font-size: 0.9rem;
            font-weight: 600;
            font-family: inherit;
            color: #ffffff;
            background: linear-gradient(135deg, var(--ys-primary), var(--ys-secondary));
            border: none;
            border-radius: 0.625rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: opacity var(--ys-transition), transform var(--ys-transition), box-shadow var(--ys-transition);
            box-shadow: 0 4px 14px -3px rgba(37, 99, 235, 0.35);
        }

        .ys-btn-primary:hover:not(:disabled) {
            opacity: 0.92;
            transform: translateY(-1px);
            box-shadow: 0 8px 24px -4px rgba(37, 99, 235, 0.4);
        }

        .ys-btn-primary:active:not(:disabled) {
            transform: translateY(0);
        }

        .ys-btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        /* Spinner */
        .ys-spinner {
            width: 18px;
            height: 18px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: ys-spin 0.6s linear infinite;
        }

        @keyframes ys-spin {
            to { transform: rotate(360deg); }
        }

        /* Separator */
        .ys-separator {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin: 1.25rem 0;
            color: var(--ys-muted);
            font-size: 0.775rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .ys-separator::before,
        .ys-separator::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--ys-card-border);
        }

        /* Google button */
        .ys-btn-google {
            width: 100%;
            padding: 0.6875rem;
            font-size: 0.875rem;
            font-weight: 500;
            font-family: inherit;
            color: var(--ys-text);
            background: var(--ys-input-bg);
            border: 1.5px solid var(--ys-input-border);
            border-radius: 0.625rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.625rem;
            transition: border-color var(--ys-transition), background var(--ys-transition), transform var(--ys-transition);
        }

        .ys-btn-google:hover {
            border-color: var(--ys-primary);
            transform: translateY(-1px);
        }

        /* Status message */
        .ys-status {
            background: rgba(16, 185, 129, 0.08);
            border: 1px solid rgba(16, 185, 129, 0.2);
            color: #059669;
            font-size: 0.8125rem;
            padding: 0.625rem 0.875rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            text-align: center;
        }

        .dark .ys-status {
            color: #34d399;
        }

        /* Footer */
        .ys-footer {
            text-align: center;
            margin-top: 1.25rem;
            font-size: 0.725rem;
            color: var(--ys-muted);
            letter-spacing: 0.02em;
        }

        /* Theme toggle */
        .ys-theme-toggle {
            position: fixed;
            top: 1.25rem;
            right: 1.25rem;
            z-index: 50;
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: var(--ys-card-bg);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid var(--ys-card-border);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--ys-muted);
            transition: color var(--ys-transition), background var(--ys-transition), transform var(--ys-transition);
        }

        .ys-theme-toggle:hover {
            color: var(--ys-text);
            transform: scale(1.08);
        }

        /* ── SVG Illustration ── */
        .ys-illustration {
            margin-top: 1.5rem;
            display: flex;
            justify-content: center;
        }

        .ys-illustration svg {
            width: 220px;
            height: auto;
            opacity: 0.65;
            transition: opacity var(--ys-transition);
        }

        .ys-illustration:hover svg {
            opacity: 0.85;
        }

        /* ── Responsive ── */
        @media (max-width: 900px) {
            .ys-container {
                flex-direction: column-reverse;
                gap: 2rem;
                max-width: 480px;
                padding: 0;
            }

            .ys-features-panel {
                display: none;
            }

            .ys-login-card {
                max-width: 100%;
            }
        }

        @media (max-width: 480px) {
            .ys-login-card {
                padding: 2rem 1.5rem 1.5rem;
                border-radius: 0.875rem;
            }

            .ys-features-title {
                font-size: 1.75rem;
            }
        }

        /* Electron fix: no drag region except top bar */
        .ys-electron-safe {
            -webkit-app-region: no-drag;
        }
    </style>
</head>
<body class="ys-electron-safe" x-cloak>
    {{ $slot }}
@livewireScripts

    <script>
        /* ═══════════════════════════════════════════════════
           YESCHOOL — Theme Manager (Alpine.js)
           Compatible: Browser + Electron
           ═══════════════════════════════════════════════════ */
        function yeschoolTheme() {
            return {
                theme: 'system', // 'system', 'light', 'dark'
                resolvedTheme: 'light',

                init() {
                    this.theme = localStorage.getItem('sigs-theme') || "{{ \App\Models\Setting::get('default_theme', 'system') }}";
                    this.resolveTheme();

                    // Watch system changes (Electron follows OS theme)
                    window.matchMedia('(prefers-color-scheme: dark)')
                        .addEventListener('change', () => {
                            if (this.theme === 'system') {
                                this.resolveTheme();
                            }
                        });

                    this.$watch('theme', (val) => {
                        localStorage.setItem('sigs-theme', val);
                        this.resolveTheme();
                    });
                },

                resolveTheme() {
                    if (this.theme === 'system') {
                        this.resolvedTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                    } else {
                        this.resolvedTheme = this.theme;
                    }
                    // Sync darkMode for older components / Filament panel consistency
                    localStorage.setItem('darkMode', JSON.stringify(this.resolvedTheme === 'dark'));
                },

                toggleTheme() {
                    const modes = ['system', 'light', 'dark'];
                    const nextIndex = (modes.indexOf(this.theme) + 1) % modes.length;
                    this.theme = modes[nextIndex];
                }
            };
        }

        function loginForm() {
            return {
                showPassword: false,
                loading: false,
                loadingPasskey: false,
                supportsPasskeys: false,
                passkeyError: null,

                init() {
                    this.supportsPasskeys = !!window.PublicKeyCredential;
                },

                onSubmit(event) {
                    this.loading = true;
                    // Allow form to submit naturally; re-enable on page load if validation fails
                },

                async loginWithPasskey() {
                    if (!this.supportsPasskeys) {
                        this.passkeyError = "Votre appareil ne supporte pas les Passkeys.";
                        return;
                    }

                    this.loadingPasskey = true;
                    this.passkeyError = null;

                    try {
                        if (typeof Webauthn === 'undefined') {
                            throw new Error("WebAuthn script not loaded.");
                        }
                        
                        await new Webauthn().login();
                        // Redirection en cas de succès (le script gère la redirection en interne généralement, ou on reload)
                        window.location.href = '/dashboard';

                    } catch (e) {
                        console.error(e);
                        this.passkeyError = e.message || "La connexion par Passkey a échoué.";
                    } finally {
                        this.loadingPasskey = false;
                    }
                }
            };
        }
    </script>
</body>
</html>

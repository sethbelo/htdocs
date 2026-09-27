<div>

    <!-- Theme Toggle Button -->
    <button class="ys-theme-toggle"
            @click="toggleTheme()"
            :title="theme === 'system' ? 'Thème : Système (Changement automatique)' : (theme === 'light' ? 'Thème : Clair' : 'Thème : Sombre')"
            :aria-label="theme === 'system' ? 'Activer le thème clair' : (theme === 'light' ? 'Activer le thème sombre' : 'Activer le thème système')">
        <template x-if="theme === 'system'">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/>
            </svg>
        </template>
        <template x-if="theme === 'light'">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
            </svg>
        </template>
        <template x-if="theme === 'dark'">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
            </svg>
        </template>
    </button>

    <div class="ys-page">
        <!-- ── Background Layer ── -->
        <div class="ys-bg-layer" aria-hidden="true">
            <div class="ys-blob ys-blob-1"></div>
            <div class="ys-blob ys-blob-2"></div>
            <div class="ys-blob ys-blob-3"></div>
            <div class="ys-grid-pattern"></div>
        </div>

        <!-- ── Main Content ── -->
        <div class="ys-container">

            <!-- Left Panel — Features -->
            <div class="ys-features-panel">
                <div>
                    <h1 class="ys-features-title">
                        <span class="ys-gradient-text">Gestion scolaire</span><br>intelligente.
                    </h1>
                    <p class="ys-features-subtitle">
                        La plateforme qui simplifie la gestion de vos établissements scolaires, de l'inscription au bulletin.
                    </p>
                </div>

                <div class="ys-feature-list">
                    <div class="ys-feature-item">
                        <div class="ys-feature-icon blue">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
                        </div>
                        <span>Gestion académique complète</span>
                    </div>
                    <div class="ys-feature-item">
                        <div class="ys-feature-icon violet">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        </div>
                        <span>Cloud sécurisé & chiffré</span>
                    </div>
                    <div class="ys-feature-item">
                        <div class="ys-feature-icon cyan">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                        </div>
                        <span>Multi-établissements & campus</span>
                    </div>
                    <div class="ys-feature-item">
                        <div class="ys-feature-icon green">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        </div>
                        <span>Web · Mobile · Desktop (Electron)</span>
                    </div>
                </div>

                <!-- Inline SVG Illustration -->
                <div class="ys-illustration">
                    <svg viewBox="0 0 280 180" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <!-- Cloud -->
                        <ellipse cx="140" cy="68" rx="72" ry="38" fill="var(--ys-blob-1)" stroke="var(--ys-primary)" stroke-width="1.2" opacity="0.7"/>
                        <ellipse cx="110" cy="62" rx="36" ry="24" fill="var(--ys-blob-2)" opacity="0.5"/>
                        <ellipse cx="170" cy="65" rx="28" ry="20" fill="var(--ys-blob-3)" opacity="0.5"/>
                        <!-- Graduation Cap -->
                        <polygon points="140,30 110,48 140,58 170,48" fill="var(--ys-primary)" opacity="0.75"/>
                        <line x1="140" y1="58" x2="140" y2="78" stroke="var(--ys-primary)" stroke-width="1.5" opacity="0.5"/>
                        <circle cx="140" cy="80" r="3" fill="var(--ys-secondary)" opacity="0.6"/>
                        <!-- Dashboard lines -->
                        <rect x="60" y="110" width="50" height="6" rx="3" fill="var(--ys-primary)" opacity="0.18"/>
                        <rect x="60" y="122" width="35" height="6" rx="3" fill="var(--ys-secondary)" opacity="0.14"/>
                        <rect x="60" y="134" width="42" height="6" rx="3" fill="var(--ys-accent)" opacity="0.12"/>
                        <!-- Connections -->
                        <circle cx="180" cy="115" r="14" stroke="var(--ys-primary)" stroke-width="1" fill="none" opacity="0.3"/>
                        <circle cx="210" cy="130" r="10" stroke="var(--ys-secondary)" stroke-width="1" fill="none" opacity="0.25"/>
                        <line x1="192" y1="120" x2="202" y2="126" stroke="var(--ys-muted)" stroke-width="0.8" opacity="0.3"/>
                        <!-- Abstract dots -->
                        <circle cx="45" cy="95" r="3" fill="var(--ys-primary)" opacity="0.2"/>
                        <circle cx="240" cy="90" r="2.5" fill="var(--ys-secondary)" opacity="0.2"/>
                        <circle cx="230" cy="155" r="2" fill="var(--ys-accent)" opacity="0.2"/>
                    </svg>
                </div>
            </div>

            <!-- Right Panel — Login Card -->
            <div class="ys-login-card" x-data="loginForm()">
                <!-- Logo -->
                <div class="ys-logo-block">
                    <div class="ys-logo-icon">
                        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/>
                            <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>
                        </svg>
                    </div>
                    <div class="ys-logo-name">YESCHOOL</div>
                    <div class="ys-logo-tagline">Smart School Platform</div>
                </div>

                <p class="ys-card-title">Connectez-vous à votre espace scolaire</p>

                <!-- Session Status -->
                @if (session('status'))
                    <div class="ys-status">
                        {{ session('status') }}
                    </div>
                @endif

                <!-- Login Form -->
                <form wire:submit="authenticate">

                    <!-- Email -->
                    <div class="ys-form-group">
                        <label for="email" class="ys-label">Adresse e-mail</label>
                        <div class="ys-input-wrapper">
                            <input
                                id="email"
                                type="email"
                                wire:model="data.email"
                                class="ys-input @error('data.email') is-error @enderror"
                                placeholder="vous@etablissement.com"
                                required
                                autofocus
                                autocomplete="email"
                            >
                        </div>
                        @error('data.email')
                            <p class="ys-error-text">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <!-- Password -->
                    <div class="ys-form-group">
                        <label for="password" class="ys-label">Mot de passe</label>
                        <div class="ys-input-wrapper">
                            <input
                                :type="showPassword ? 'text' : 'password'"
                                id="password"
                                wire:model="data.password"
                                class="ys-input @error('data.password') is-error @enderror"
                                placeholder="••••••••"
                                required
                                autocomplete="current-password"
                                style="padding-right: 2.75rem;"
                            >
                            <button type="button" class="ys-password-toggle" @click="showPassword = !showPassword" :aria-label="showPassword ? 'Masquer' : 'Afficher'">
                                <template x-if="!showPassword">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                </template>
                                <template x-if="showPassword">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                                </template>
                            </button>
                        </div>
                        @error('data.password')
                            <p class="ys-error-text">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <!-- Remember + Forgot -->
                    <div class="ys-form-row">
                        <label class="ys-checkbox-label">
                            <input type="checkbox" wire:model="data.remember" class="ys-checkbox">
                            Se souvenir de moi
                        </label>

                        @if (filament()->hasPasswordReset())
                            <a href="{{ filament()->getRequestPasswordResetUrl() }}" class="ys-forgot-link">
                                Mot de passe oublié ?
                            </a>
                        @endif
                    </div>

                    <!-- Submit -->
                    <button type="submit" class="ys-btn-primary" wire:loading.attr="disabled" wire:target="authenticate">
                        <span class="ys-spinner" wire:loading wire:target="authenticate"></span>
                        <span wire:loading.remove wire:target="authenticate">Se connecter</span>
                        <span wire:loading wire:target="authenticate">Connexion en cours…</span>
                    </button>
                </form>

                <div class="ys-separator">ou</div>

                <button type="button" class="ys-btn-google" @click="loginWithPasskey()" :disabled="loadingPasskey" x-show="supportsPasskeys">
                    <template x-if="loadingPasskey">
                        <span class="ys-spinner" style="border-color: rgba(0,0,0,0.1); border-top-color: var(--ys-primary);"></span>
                    </template>
                    <template x-if="!loadingPasskey">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 2a10 10 0 1 0 10 10H12V2z"/>
                            <path d="M12 12 2.1 12"/>
                            <path d="M12 12 20.5 4"/>
                            <path d="M12 12 18 20.5"/>
                        </svg>
                    </template>
                    <span x-text="loadingPasskey ? 'Vérification...' : 'Connexion rapide avec Passkey'"></span>
                </button>
                <p x-show="passkeyError" x-text="passkeyError" class="ys-error-text mt-2 text-center w-full justify-center"></p>

                {{-- Uncomment to enable Google OAuth
                <div class="ys-separator">ou</div>
                <button type="button" class="ys-btn-google" onclick="window.location='{{ url('/auth/google') }}'">
                    <svg width="18" height="18" viewBox="0 0 24 24">
                        <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/>
                        <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                        <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18A11.96 11.96 0 0 0 1 12c0 1.94.46 3.77 1.18 5.4l3.66-2.84z" fill="#FBBC05"/>
                        <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                    </svg>
                    Continuer avec Google
                </button>
                --}}

                <!-- Footer -->
                <div class="ys-footer">
                    © {{ date('Y') }} <strong>Yetoo Technologie</strong> — Tous droits réservés
                </div>
            </div>
        </div>
    </div>

    
</div>
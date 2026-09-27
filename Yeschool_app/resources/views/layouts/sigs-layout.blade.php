<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" 
      x-data="sigsApp()" 
      x-init="initTheme()"
      :class="{ 'dark': resolvedTheme === 'dark' }"
      :data-theme="resolvedTheme === 'dark' ? 'dark' : 'light'">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Yebook SIGS') }} - Pro Dashboard</title>

    <!-- Tailwind CDN with DaisyUI -->
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.7.2/dist/full.min.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        space: ['Space Grotesk', 'sans-serif'],
                    },
                    colors: {
                        primary: '#4f46e5',
                    }
                }
            }
        }
    </script>



    <!-- ApexCharts -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <!-- intl-tel-input CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@23.0.4/build/css/intlTelInput.css">

    <!-- QuillJS CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/quill@2.0.0/dist/quill.snow.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ asset('css/sigs-design-system.css') }}">

    <style>
        [x-cloak] { display: none !important; }
        
        /* Theme toggle indicator animation */
        .theme-indicator {
            transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1), 
                        width 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        /* Smooth theme transition for entire page */
        html.theme-transitioning,
        html.theme-transitioning *,
        html.theme-transitioning *::before,
        html.theme-transitioning *::after {
            transition: background-color 0.3s ease, 
                        color 0.2s ease, 
                        border-color 0.3s ease,
                        box-shadow 0.3s ease,
                        fill 0.2s ease,
                        stroke 0.2s ease !important;
        }
    </style>

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
</head>
<body class="antialiased bg-slate-50 dark:bg-[#0a0a0a] text-slate-900 dark:text-slate-100">

    <!-- PRELOADER -->
    <div id="sigs-preloader" class="fixed inset-0 z-[99999] bg-white dark:bg-[#0a0a0a] flex flex-col items-center justify-center transition-all duration-500" style="margin-top: 0px !important;">
        <div class="relative flex items-center justify-center">
            <!-- Spinner Ring -->
            <div class="w-24 h-24 border-4 border-slate-100 dark:border-slate-800 border-t-indigo-600 dark:border-t-indigo-400 rounded-full animate-spin"></div>
            <!-- School Icon in Center -->
            <svg class="w-10 h-10 text-indigo-600 dark:text-indigo-400 animate-pulse absolute" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 10v6M2 10l10-5 10 5-10 5z"/>
                <path d="M6 12v5c0 2 2 3 6 3s6-1 6-3v-5"/>
            </svg>
        </div>
        <p class="mt-4 text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-widest animate-pulse font-space">Chargement en cours...</p>
    </div>

    <!-- PRELOADER SCRIPT -->
    <script>
        (function() {
            function hidePreloader() {
                var preloader = document.getElementById('sigs-preloader');
                if (preloader && preloader.style.display !== 'none') {
                    preloader.style.opacity = '0';
                    preloader.style.pointerEvents = 'none';
                    setTimeout(function() {
                        preloader.style.display = 'none';
                    }, 500);
                }
            }
            window.addEventListener('load', hidePreloader);
            // Fallback timeout to prevent stuck preloader (4s max)
            setTimeout(hidePreloader, 4000);
        })();
    </script>

    <div class="flex h-screen overflow-hidden" 
         x-data="{ sidebarOpen: true, notificationsOpen: false }">
        
        <!-- Sidebar Component -->
        <x-sigs.sidebar />

        <!-- Main Content Wrapper -->
        <div class="relative flex flex-col flex-1 overflow-y-auto overflow-x-hidden bg-slate-50 dark:bg-[#0a0a0a] transition-colors duration-300">
            
            <!-- Topbar Component -->
            <x-sigs.topbar />

            <!-- Main Content Area -->
            <main class="w-full grow p-6">
                {{ $slot }}
            </main>

        </div>
        
        <!-- Notifications Offcanvas Component -->
        @livewire('sigs-notifications-panel')
        
    </div>

    @livewireScripts
    
    <script>
        function sigsApp() {
            return {
                theme: 'system', // 'light', 'dark', 'system'
                resolvedTheme: 'light',
                
                initTheme() {
                    this.theme = localStorage.getItem('sigs-theme') || "{{ \App\Models\Setting::get('default_theme', 'system') }}";
                    this.resolveTheme();
                    
                    // Listen for system preference changes
                    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
                        if (this.theme === 'system') this.resolveTheme();
                    });
                    
                    this.$watch('theme', (val) => {
                        localStorage.setItem('sigs-theme', val);
                        // Trigger smooth transition
                        document.documentElement.classList.add('theme-transitioning');
                        this.resolveTheme();
                        setTimeout(() => document.documentElement.classList.remove('theme-transitioning'), 350);
                    });

                    // Synchronize dynamic theme changes from Livewire events
                    window.addEventListener('theme-updated', (e) => {
                        this.theme = e.detail.theme;
                    });
                },
                
                resolveTheme() {
                    if (this.theme === 'system') {
                        this.resolvedTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                    } else {
                        this.resolvedTheme = this.theme;
                    }
                    // Sync darkMode for older components
                    localStorage.setItem('darkMode', JSON.stringify(this.resolvedTheme === 'dark'));
                },
                
                setTheme(t) {
                    this.theme = t;
                },
                
                get indicatorOffset() {
                    const map = { system: 0, light: 1, dark: 2 };
                    return (map[this.theme] || 0) * 32;
                }
            };
        }
    </script>

    <!-- intl-tel-input JS -->
    <script src="https://cdn.jsdelivr.net/npm/intl-tel-input@23.0.4/build/js/intlTelInput.min.js"></script>

    <!-- QuillJS JS -->
    <script src="https://cdn.jsdelivr.net/npm/quill@2.0.0/dist/quill.js"></script>

    <!-- Unified Confirmation Modal -->
    <div x-data="{
        open: false,
        title: '',
        message: '',
        confirmText: 'Confirmer',
        cancelText: 'Annuler',
        confirmType: 'danger',
        onConfirm: null,
        
        init() {
            window.addEventListener('show-custom-confirm', (e) => {
                this.title = e.detail.title || 'Confirmation';
                this.message = e.detail.message || 'Êtes-vous sûr de vouloir effectuer cette action ?';
                this.confirmText = e.detail.confirmText || 'Confirmer';
                this.cancelText = e.detail.cancelText || 'Annuler';
                this.confirmType = e.detail.type || 'danger';
                this.onConfirm = e.detail.onConfirm;
                this.open = true;
            });
        },
        
        proceed() {
            if (this.onConfirm) this.onConfirm();
            this.open = false;
        }
    }"
         x-show="open"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
         x-cloak>
         
         <div @click.away="open = false" class="bg-white dark:bg-[#0c0c0c] border border-slate-200 dark:border-[#1a1a1a] rounded-3xl p-6 max-w-md w-full shadow-2xl space-y-6 transform transition-all">
             <div class="flex items-start gap-4">
                 <div :class="{
                     'bg-rose-50 text-rose-600 dark:bg-rose-950/20 dark:text-rose-400': confirmType === 'danger',
                     'bg-amber-50 text-amber-600 dark:bg-amber-950/20 dark:text-amber-400': confirmType === 'warning',
                     'bg-indigo-50 text-indigo-600 dark:bg-indigo-950/20 dark:text-indigo-400': confirmType === 'info'
                 }" class="w-12 h-12 rounded-2xl flex items-center justify-center flex-shrink-0">
                     <svg x-show="confirmType === 'danger'" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                         <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                     </svg>
                     <svg x-show="confirmType === 'warning'" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                         <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                     </svg>
                     <svg x-show="confirmType === 'info'" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                         <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                     </svg>
                 </div>
                 <div class="space-y-1 overflow-hidden flex-1">
                     <h3 class="text-lg font-bold text-slate-900 dark:text-white" x-text="title"></h3>
                     <p class="text-sm text-slate-500 dark:text-slate-400 break-words" x-text="message"></p>
                 </div>
             </div>
             <div class="flex items-center justify-end gap-3">
                 <button type="button" @click="open = false" class="px-4 py-2 text-sm font-semibold bg-slate-100 hover:bg-slate-200 dark:bg-[#1a1a1a] dark:hover:bg-[#2a2a2a] text-slate-700 dark:text-slate-300 rounded-xl transition-colors" x-text="cancelText"></button>
                 <button type="button" @click="proceed()" :class="{
                     'bg-rose-600 hover:bg-rose-700 text-white': confirmType === 'danger',
                     'bg-amber-500 hover:bg-amber-600 text-white': confirmType === 'warning',
                     'bg-indigo-600 hover:bg-indigo-700 text-white': confirmType === 'info'
                 }" class="px-5 py-2 text-sm font-semibold rounded-xl transition-colors shadow-sm" x-text="confirmText"></button>
             </div>
         </div>
    </div>

    <!-- Initialize Lucide Icons & Custom Confirmation Hooks -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (typeof lucide !== 'undefined') lucide.createIcons();
        });
        document.addEventListener('livewire:navigated', () => {
            if (typeof lucide !== 'undefined') lucide.createIcons();
        });

        window.confirmAction = function(options) {
            window.dispatchEvent(new CustomEvent('show-custom-confirm', {
                detail: options
            }));
        };

        document.addEventListener('livewire:init', () => {
            Livewire.hook('request', ({ respond }) => {
                respond(() => {
                    setTimeout(() => {
                        if (typeof lucide !== 'undefined') {
                            lucide.createIcons();
                        }
                    }, 50);
                });
            });

            // Intercept all wire:confirm directives
            Livewire.directive('confirm', ({ el, directive, component }) => {
                return ({ content, next }) => {
                    let confirmType = 'danger';
                    let title = 'Confirmer l\'action';
                    
                    let lower = content.toLowerCase();
                    if (lower.includes('archiv')) {
                        confirmType = 'warning';
                        title = 'Confirmer l\'archivage';
                    } else if (lower.includes('supprim') || lower.includes('retirer')) {
                        confirmType = 'danger';
                        title = 'Confirmer la suppression';
                    } else if (lower.includes('départ') || lower.includes('résil') || lower.includes('déconnect')) {
                        confirmType = 'danger';
                        title = 'Action critique';
                    } else {
                        confirmType = 'info';
                        title = 'Confirmation';
                    }

                    window.confirmAction({
                        title: title,
                        message: content,
                        confirmText: 'Confirmer',
                        cancelText: 'Annuler',
                        type: confirmType,
                        onConfirm: next
                    });
                };
            });
        });
    </script>
</body>
</html>

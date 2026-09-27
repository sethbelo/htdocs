<x-filament-widgets::widget class="pro-bento-col-12">
    <link rel="stylesheet" href="{{ asset('css/pro-dashboard.css') }}">
    <style>
        .fi-header { display: none !important; }
    </style>
    <div class="pro-glass-card pro-gradient-mesh p-8 md:p-12 relative overflow-hidden flex flex-col justify-center items-start min-h-[250px]">
        
        <div class="relative z-10">
            <h1 class="text-4xl md:text-5xl font-bold text-gray-900 dark:text-white mb-2 tracking-tight">
                Bonjour, <span class="pro-text-gradient">{{ $this->getUserName() }}</span> 👋
            </h1>
            <p class="text-lg md:text-xl text-gray-600 dark:text-gray-300 max-w-2xl mt-4 leading-relaxed font-light">
                Bienvenue sur le tableau de bord de <strong class="font-medium text-gray-800 dark:text-gray-100">Yeschool</strong>. 
                Gérez vos établissements, suivez les statistiques et accédez aux dernières activités en un clin d'œil.
            </p>
            
            <div class="mt-8 flex gap-4">
                <button class="px-6 py-2.5 rounded-xl text-white font-medium shadow-lg transition-transform hover:-translate-y-1" style="background: linear-gradient(135deg, #2563eb, #7c3aed);">
                    Gérer les Inscriptions
                </button>
                <button class="px-6 py-2.5 rounded-xl font-medium bg-white/50 dark:bg-slate-800/50 backdrop-blur-md border border-white/40 dark:border-slate-700 hover:bg-white/70 dark:hover:bg-slate-800/80 transition-colors text-gray-800 dark:text-gray-200">
                    Voir les rapports
                </button>
            </div>
        </div>

        <!-- Decorative floating elements -->
        <div class="absolute top-10 right-10 w-32 h-32 bg-blue-500/20 rounded-full blur-3xl mix-blend-multiply dark:mix-blend-lighten animate-pulse"></div>
        <div class="absolute -bottom-10 right-40 w-40 h-40 bg-purple-500/20 rounded-full blur-3xl mix-blend-multiply dark:mix-blend-lighten animate-pulse" style="animation-delay: 2s;"></div>
    </div>
</x-filament-widgets::widget>

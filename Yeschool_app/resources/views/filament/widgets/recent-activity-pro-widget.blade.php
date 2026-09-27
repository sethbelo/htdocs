<x-filament-widgets::widget class="pro-bento-col-12 mt-6">
    <div class="pro-glass-card p-6 md:p-8">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-display font-semibold text-gray-900 dark:text-white">Activités Récentes</h2>
            <button class="text-sm font-medium text-blue-600 dark:text-blue-400 hover:underline">Voir tout</button>
        </div>

        <div class="space-y-6">
            @foreach($activities as $activity)
                <div class="flex items-start gap-4 p-4 rounded-2xl transition-colors hover:bg-gray-50/50 dark:hover:bg-slate-800/50">
                    <div class="flex-shrink-0 w-12 h-12 rounded-full flex items-center justify-center {{ $activity['color'] }}">
                        <x-icon name="{{ $activity['icon'] }}" class="w-6 h-6" />
                    </div>
                    <div class="flex-1">
                        <h4 class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ $activity['title'] }}</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $activity['description'] }}</p>
                    </div>
                    <div class="text-xs font-medium text-gray-500 dark:text-gray-400 whitespace-nowrap">
                        {{ $activity['time'] }}
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-filament-widgets::widget>

<x-filament-widgets::widget class="pro-bento-col-12 mt-6">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        @foreach($stats as $stat)
            <div class="pro-glass-card p-6 flex flex-col relative overflow-hidden group">
                <!-- Icon background blob -->
                <div class="absolute -right-6 -top-6 w-24 h-24 rounded-full bg-gradient-to-br {{ $stat['color'] }} opacity-10 group-hover:scale-150 transition-transform duration-500 ease-out"></div>
                
                <div class="flex items-center justify-between mb-4 relative z-10">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ $stat['label'] }}</h3>
                    <div class="w-10 h-10 rounded-xl bg-white dark:bg-slate-800 shadow-sm flex items-center justify-center border border-gray-100 dark:border-slate-700">
                        <x-icon name="{{ $stat['icon'] }}" class="w-5 h-5 text-gray-700 dark:text-gray-300" />
                    </div>
                </div>
                
                <div class="mt-auto relative z-10">
                    <div class="flex items-baseline gap-2">
                        <span class="text-3xl font-display font-bold text-gray-900 dark:text-white tracking-tight">{{ $stat['value'] }}</span>
                    </div>
                    <div class="mt-2 text-sm {{ $stat['trend_color'] }} font-medium flex items-center gap-1">
                        @if(str_contains($stat['trend'], '+'))
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                        @elseif(str_contains($stat['trend'], '-'))
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0v-8m0 8l-8-8-4 4-6-6"></path></svg>
                        @endif
                        {{ $stat['trend'] }} depuis le mois dernier
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</x-filament-widgets::widget>

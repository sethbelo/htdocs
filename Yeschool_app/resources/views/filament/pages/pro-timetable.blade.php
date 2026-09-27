<div class="h-full flex flex-col p-6 max-w-7xl mx-auto">

    <!-- HEADER & ACTIONS -->
    <div class="flex justify-between items-center mb-8 no-print">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Emploi du temps</h1>
            <p class="text-slate-500 text-sm mt-1">Configurez les plages horaires de cours, les matières enseignées et l'enseignant titulaire par classe.</p>
        </div>
        <div class="flex items-center gap-3">
            <!-- Mode selection (Weekly Grid vs Gantt Chart) -->
            <div class="flex bg-slate-100 dark:bg-[#111] p-1 rounded-lg border border-slate-200 dark:border-[#333] shrink-0">
                <button wire:click="$set('viewMode', 'weekly_grid')" 
                        class="px-4 py-1.5 text-xs font-semibold rounded-md transition-all {{ $viewMode === 'weekly_grid' ? 'bg-white dark:bg-[#1a1a1a] text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-300' }}">
                    Grille Classique
                </button>
                <button wire:click="$set('viewMode', 'gantt')" 
                        class="px-4 py-1.5 text-xs font-semibold rounded-md transition-all {{ $viewMode === 'gantt' ? 'bg-white dark:bg-[#1a1a1a] text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-300' }}">
                    Planning Gantt
                </button>
            </div>

            <button type="button" wire:click="exportPdf" class="p-2.5 bg-white dark:bg-[#111] text-slate-700 dark:text-slate-200 rounded-lg border border-slate-200 dark:border-[#333] hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors shadow-sm flex items-center justify-center" title="Exporter PDF">
                <i data-lucide="file-text" class="w-5 h-5"></i>
            </button>
            @if(!auth()->user()->hasRole('Enseignant'))
            <button type="button" wire:click="createSchedule" class="p-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors shadow-sm flex items-center justify-center" title="Ajouter un cours">
                <i data-lucide="calendar-plus" class="w-5 h-5"></i>
            </button>
            @endif
        </div>
    </div>

    <!-- MAIN CONTENT AREA -->
    <div class="flex-1 bg-white dark:bg-[#0a0a0a] rounded-2xl border border-slate-200 dark:border-[#333] shadow-sm overflow-hidden flex flex-col min-h-[600px]">
        
        <!-- Toolbar for Classic View -->
        @if($viewMode === 'weekly_grid')
            <div class="flex flex-col sm:flex-row items-center justify-between p-4 border-b border-slate-200 dark:border-[#333] gap-4 bg-slate-50/50 dark:bg-[#111]/30 relative z-10 no-print">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400 shadow-sm border border-indigo-200 dark:border-indigo-800">
                         <i data-lucide="layers" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-0.5">Sélectionner une classe</label>
                        <select wire:model.live="selectedClassId" class="block w-64 py-1.5 pl-0 pr-8 text-base font-bold text-slate-900 dark:text-white border-transparent bg-transparent focus:ring-0 cursor-pointer">
                            @foreach($schoolClasses as $c)
                                <option value="{{ $c['id'] }}">{{ $c['name'] }} ({{ $c['level'] }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        @else
            <!-- Toolbar for Gantt View (filters by week and month) -->
            <div class="flex flex-wrap items-end gap-4 p-4 border-b border-slate-200 dark:border-[#333] bg-slate-50/50 dark:bg-[#111]/30 relative z-10 no-print">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Mois</label>
                    <select wire:model.live="selectedMonth" class="py-1.5 px-3 border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] text-xs font-semibold text-slate-700 dark:text-slate-300">
                        @for($m = 1; $m <= 12; $m++)
                            @php
                                $monthVal = date('Y') . '-' . str_pad($m, 2, '0', STR_PAD_LEFT);
                                $monthLabel = Carbon\Carbon::create(date('Y'), $m, 1)->translatedFormat('F Y');
                            @endphp
                            <option value="{{ $monthVal }}">{{ ucfirst($monthLabel) }}</option>
                        @endfor
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Semaine de cours</label>
                    <select wire:model.live="selectedWeek" class="py-1.5 px-3 border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] text-xs font-semibold text-slate-700 dark:text-slate-300">
                        @foreach($this->getWeeksOfMonth() as $wk)
                            <option value="{{ $wk['week_num'] }}">{{ $wk['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Affichage par</label>
                    <select wire:model.live="ganttType" class="py-1.5 px-3 border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] text-xs font-semibold text-slate-700 dark:text-slate-300">
                        <option value="classes">Classes</option>
                        <option value="teachers">Enseignants</option>
                    </select>
                </div>
            </div>

            <!-- Gantt Day Tabs -->
            <div class="flex gap-2 border-b border-slate-200 dark:border-[#333] px-4 pt-2 bg-slate-50/20 dark:bg-[#111]/10 no-print">
                @foreach($this->getWeekDates() as $index => $dateInfo)
                    <button wire:click="$set('selectedDayIndex', {{ $index }})" 
                            class="px-4 py-2 border-b-2 text-xs font-bold transition-all flex flex-col items-center
                            {{ $selectedDayIndex == $index ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400' : 'border-transparent text-slate-400 hover:text-slate-800' }}">
                        <span>{{ $dateInfo['day_name'] }}</span>
                        <span class="text-[10px] opacity-75 font-normal mt-0.5">{{ $dateInfo['short_date'] }}</span>
                    </button>
                @endforeach
            </div>
        @endif

        <!-- Dynamic Schedule Grid -->
        <div class="flex-1 overflow-auto relative bg-white dark:bg-[#0a0a0a]">
            
            <div wire:loading.delay class="absolute inset-0 bg-white/50 dark:bg-[#0a0a0a]/50 z-20 backdrop-blur-sm flex items-center justify-center">
                <i data-lucide="loader-2" class="w-8 h-8 animate-spin text-indigo-600"></i>
            </div>

            @if($viewMode === 'weekly_grid')
                @if(!$selectedClassId)
                    <div class="flex flex-col items-center justify-center h-64">
                        <i data-lucide="calendar" class="w-12 h-12 text-slate-300 dark:text-slate-600 mb-4"></i>
                        <p class="text-slate-500 dark:text-slate-400">Sélectionnez une classe pour voir son emploi du temps.</p>
                    </div>
                @else
                    <div class="min-w-[800px] h-full flex flex-col">
                        <!-- Days Header -->
                        <div class="flex border-b border-slate-200 dark:border-[#333] sticky top-0 z-10 bg-white dark:bg-[#0a0a0a]">
                            <div class="w-16 shrink-0 border-r border-slate-200 dark:border-[#333]"></div> <!-- Time column header -->
                            @foreach($days as $day)
                                <div class="flex-1 py-3 text-center border-r border-slate-200 dark:border-[#333] last:border-0">
                                    <span class="text-sm font-bold text-slate-700 dark:text-slate-300">{{ $day }}</span>
                                </div>
                            @endforeach
                        </div>

                        <!-- Time Slots & Content -->
                        <div class="flex flex-1 relative">
                            <!-- Time Column -->
                            <div class="w-16 shrink-0 border-r border-slate-200 dark:border-[#333] flex flex-col relative z-0">
                                @foreach($timeSlots as $time)
                                    <div class="h-24 border-b border-slate-200 dark:border-[#333] flex justify-center pt-2">
                                        <span class="text-xs font-medium text-slate-400 dark:text-slate-500">{{ $time }}</span>
                                    </div>
                                @endforeach
                            </div>

                            <!-- Days Columns -->
                            <div class="flex flex-1 relative">
                                <!-- Background Grid -->
                                <div class="absolute inset-0 flex">
                                    @foreach($days as $day)
                                        <div class="flex-1 border-r border-slate-200 dark:border-[#333] last:border-0 flex flex-col group relative">
                                            @foreach($timeSlots as $time)
                                                <div @if(!auth()->user()->hasRole('Enseignant')) wire:click="createSchedule('{{ $day }}', '{{ $time }}')" @endif class="h-24 border-b border-slate-200 dark:border-[#333] @if(!auth()->user()->hasRole('Enseignant')) cursor-pointer hover:bg-indigo-50/50 dark:hover:bg-indigo-900/10 @endif transition-colors relative">
                                                    @if(!auth()->user()->hasRole('Enseignant'))
                                                    <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                                        <i data-lucide="plus" class="w-5 h-5 text-indigo-300 dark:text-indigo-700"></i>
                                                    </div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    @endforeach
                                </div>

                                <!-- Absolute Positioned Events -->
                                <div class="absolute inset-0 flex pointer-events-none">
                                    @foreach($days as $day)
                                        <div class="flex-1 relative">
                                            @if(isset($weeklySchedule[$day]))
                                                @foreach($weeklySchedule[$day] as $event)
                                                    @php
                                                        $startHour = (int) substr($event['start_time'], 0, 2);
                                                        $startMin = (int) substr($event['start_time'], 3, 2);
                                                        
                                                        $endHour = (int) substr($event['end_time'], 0, 2);
                                                        $endMin = (int) substr($event['end_time'], 3, 2);
                                                        
                                                        $topMinutes = (($startHour - 8) * 60) + $startMin;
                                                        $durationMinutes = (($endHour - $startHour) * 60) + ($endMin - $startMin);
                                                        
                                                        $topPx = $topMinutes * 1.6;
                                                        $heightPx = $durationMinutes * 1.6;

                                                        $color = $event['subject']['color'] ?? '#4f46e5';
                                                    @endphp
                                                    <div class="absolute w-[94%] left-[3%] rounded-xl shadow-sm overflow-hidden p-2.5 flex flex-col pointer-events-auto @if(!auth()->user()->hasRole('Enseignant')) cursor-pointer hover:ring-2 hover:ring-white dark:hover:ring-[#0a0a0a] @endif transition-all group"
                                                         style="top: {{ $topPx }}px; height: {{ max(48, $heightPx) }}px; background-color: {{ $color }}15; border-left: 4px solid {{ $color }};"
                                                         @if(!auth()->user()->hasRole('Enseignant')) wire:click="editSchedule({{ $event['id'] }})" @endif>
                                                        
                                                        <div class="flex justify-between items-start mb-1">
                                                            <h4 class="text-sm font-bold" style="color: {{ $color }}">{{ $event['subject']['name'] }}</h4>
                                                            <div class="text-[10px] font-semibold px-1.5 py-0.5 rounded-full bg-white/50 dark:bg-black/20" style="color: {{ $color }}">
                                                                {{ substr($event['start_time'], 0, 5) }} - {{ substr($event['end_time'], 0, 5) }}
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="mt-auto space-y-0.5 text-xs font-medium" style="color: {{ $color }}">
                                                            @if($event['teacher'])
                                                            <div class="flex items-center gap-1 opacity-80">
                                                                <i data-lucide="user" class="w-3 h-3"></i> {{ $event['teacher']['first_name'] }} {{ $event['teacher']['last_name'] }}
                                                            </div>
                                                            @endif
                                                            @if($event['room'])
                                                            <div class="flex items-center gap-1 opacity-80">
                                                                <i data-lucide="map-pin" class="w-3 h-3"></i> {{ $event['room'] }}
                                                            </div>
                                                            @endif
                                                        </div>

                                                        <!-- Edit overlay button (visible on hover) -->
                                                        @if(!auth()->user()->hasRole('Enseignant'))
                                                        <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity bg-white dark:bg-black/50 p-1 rounded-md shadow-sm text-slate-700 dark:text-white">
                                                            <i data-lucide="edit-2" class="w-3 h-3"></i>
                                                        </div>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            @else
                <!-- GANTT VIEW MODE -->
                <div class="min-w-[1000px] h-full flex flex-col">
                    
                    <!-- Gantt Timeline Header -->
                    <div class="flex border-b border-slate-200 dark:border-[#333] bg-slate-50 dark:bg-[#111] py-3 font-semibold text-xs text-slate-500 sticky top-0 z-10">
                        <div class="w-48 shrink-0 pl-4 border-r border-slate-200 dark:border-[#333]">
                            {{ $ganttType === 'classes' ? 'Classes' : 'Enseignants' }}
                        </div>
                        <div class="flex-1 flex relative">
                            @for($h = 8; $h <= 17; $h++)
                                <div class="flex-1 text-center border-r border-slate-200 dark:border-[#333] last:border-0">
                                    {{ str_pad($h, 2, '0', STR_PAD_LEFT) }}:00
                                </div>
                            @endfor
                        </div>
                    </div>

                    <!-- Gantt Rows Content -->
                    <div class="flex-1 divide-y divide-slate-200 dark:divide-[#333]">
                        @php
                            $activeDayName = $days[$selectedDayIndex];
                            $schedulesCollection = collect($allSchedules)->where('day_of_week', $activeDayName);
                        @endphp

                        @if($ganttType === 'classes')
                            @foreach($schoolClasses as $c)
                                @php
                                    $rowId = $c['id'];
                                    $rowName = $c['name'] . ' (' . $c['level'] . ')';
                                    $rowSchedules = $schedulesCollection->where('school_class_id', $rowId);
                                @endphp
                                <div class="flex h-16 items-center relative hover:bg-slate-50/50 dark:hover:bg-[#111]/30 transition-colors">
                                    <div class="w-48 shrink-0 pl-4 border-r border-slate-200 dark:border-[#333] font-bold text-slate-700 dark:text-slate-300 truncate h-full flex items-center bg-slate-50/10">
                                        {{ $rowName }}
                                    </div>
                                    <div class="flex-1 h-full relative z-0 flex">
                                        <!-- Background Hour cells to click and create a schedule -->
                                        @for($h = 8; $h <= 17; $h++)
                                            <div class="flex-1 border-r border-slate-200 dark:border-[#333] last:border-0 h-full @if(!auth()->user()->hasRole('Enseignant')) cursor-pointer hover:bg-indigo-50/30 dark:hover:bg-indigo-900/5 @endif transition-colors"
                                                 @if(!auth()->user()->hasRole('Enseignant')) wire:click="createScheduleFromGantt('{{ $rowId }}', '{{ str_pad($h, 2, '0', STR_PAD_LEFT) }}:00')" @endif>
                                            </div>
                                        @endfor
                                        
                                        <!-- Render Gantt Bars -->
                                        @foreach($rowSchedules as $sched)
                                            @php
                                                $startParts = explode(':', $sched['start_time']);
                                                $endParts = explode(':', $sched['end_time']);
                                                $startMin = (int)$startParts[0] * 60 + (int)$startParts[1];
                                                $endMin = (int)$endParts[0] * 60 + (int)$endParts[1];
                                                
                                                // Map between 08:00 (480) and 18:00 (1080)
                                                $startMin = max(480, min(1080, $startMin));
                                                $endMin = max(480, min(1080, $endMin));
                                                
                                                $left = (($startMin - 480) / 600) * 100;
                                                $width = (($endMin - $startMin) / 600) * 100;
                                                $color = $sched['subject']['color'] ?? '#4f46e5';
                                            @endphp
                                            <div class="absolute top-2.5 h-11 rounded-lg px-2.5 py-1.5 flex flex-col justify-between overflow-hidden @if(!auth()->user()->hasRole('Enseignant')) cursor-pointer hover:shadow-lg hover:brightness-95 @endif transition-all border z-10"
                                                 style="left: calc({{ $left }}% + 4px); width: calc({{ $width }}% - 8px); background-color: {{ $color }}15; border-color: {{ $color }}; color: {{ $color }};"
                                                 @if(!auth()->user()->hasRole('Enseignant')) wire:click.stop="editSchedule({{ $sched['id'] }})" @endif
                                                 title="{{ $sched['subject']['name'] }} ({{ substr($sched['start_time'], 0, 5) }} - {{ substr($sched['end_time'], 0, 5) }}) - Enseignant: {{ $sched['teacher'] ? $sched['teacher']['first_name'] . ' ' . $sched['teacher']['last_name'] : 'N/A' }}">
                                                <div class="font-bold text-[10px] truncate leading-none">{{ $sched['subject']['name'] }}</div>
                                                <div class="flex justify-between items-center text-[8px] opacity-80 truncate leading-none">
                                                    <span>{{ $sched['teacher'] ? $sched['teacher']['first_name'] . ' ' . $sched['teacher']['last_name'] : 'N/A' }}</span>
                                                    <span class="font-bold">{{ substr($sched['start_time'], 0, 5) }} - {{ substr($sched['end_time'], 0, 5) }}</span>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        @else
                            @foreach($teachers as $t)
                                @php
                                    $rowId = $t['id'];
                                    $rowName = $t['first_name'] . ' ' . $t['last_name'];
                                    $rowSchedules = $schedulesCollection->where('teacher_id', $rowId);
                                @endphp
                                <div class="flex h-16 items-center relative hover:bg-slate-50/50 dark:hover:bg-[#111]/30 transition-colors">
                                    <div class="w-48 shrink-0 pl-4 border-r border-slate-200 dark:border-[#333] font-bold text-slate-700 dark:text-slate-300 truncate h-full flex items-center bg-slate-50/10">
                                        {{ $rowName }}
                                    </div>
                                    <div class="flex-1 h-full relative z-0 flex">
                                        <!-- Background Hour cells to click and create a schedule -->
                                        @for($h = 8; $h <= 17; $h++)
                                            <div class="flex-1 border-r border-slate-200 dark:border-[#333] last:border-0 h-full @if(!auth()->user()->hasRole('Enseignant')) cursor-pointer hover:bg-indigo-50/30 dark:hover:bg-indigo-900/5 @endif transition-colors"
                                                 @if(!auth()->user()->hasRole('Enseignant')) wire:click="createScheduleFromGantt('{{ $rowId }}', '{{ str_pad($h, 2, '0', STR_PAD_LEFT) }}:00')" @endif>
                                            </div>
                                        @endfor
                                        
                                        <!-- Render Gantt Bars -->
                                        @foreach($rowSchedules as $sched)
                                            @php
                                                $startParts = explode(':', $sched['start_time']);
                                                $endParts = explode(':', $sched['end_time']);
                                                $startMin = (int)$startParts[0] * 60 + (int)$startParts[1];
                                                $endMin = (int)$endParts[0] * 60 + (int)$endParts[1];
                                                
                                                // Map between 08:00 (480) and 18:00 (1080)
                                                $startMin = max(480, min(1080, $startMin));
                                                $endMin = max(480, min(1080, $endMin));
                                                
                                                $left = (($startMin - 480) / 600) * 100;
                                                $width = (($endMin - $startMin) / 600) * 100;
                                                $color = $sched['subject']['color'] ?? '#4f46e5';
                                            @endphp
                                            <div class="absolute top-2.5 h-11 rounded-lg px-2.5 py-1.5 flex flex-col justify-between overflow-hidden @if(!auth()->user()->hasRole('Enseignant')) cursor-pointer hover:shadow-lg hover:brightness-95 @endif transition-all border z-10"
                                                 style="left: calc({{ $left }}% + 4px); width: calc({{ $width }}% - 8px); background-color: {{ $color }}15; border-color: {{ $color }}; color: {{ $color }};"
                                                 @if(!auth()->user()->hasRole('Enseignant')) wire:click.stop="editSchedule({{ $sched['id'] }})" @endif
                                                 title="{{ $sched['subject']['name'] }} ({{ substr($sched['start_time'], 0, 5) }} - {{ substr($sched['end_time'], 0, 5) }}) - Classe: {{ $sched['school_class'] ? $sched['school_class']['name'] : 'N/A' }}">
                                                <div class="font-bold text-[10px] truncate leading-none">{{ $sched['subject']['name'] }}</div>
                                                <div class="flex justify-between items-center text-[8px] opacity-80 truncate leading-none">
                                                    <span>{{ $sched['school_class'] ? $sched['school_class']['name'] : 'N/A' }}</span>
                                                    <span class="font-bold">{{ substr($sched['start_time'], 0, 5) }} - {{ substr($sched['end_time'], 0, 5) }}</span>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
            @endif

        </div>
    </div>


    <!-- SIDE-OVER: Form -->
    <div x-show="$wire.isOffcanvasOpen" 
         class="fixed inset-0 z-50 overflow-hidden" 
         aria-labelledby="slide-over-title" 
         role="dialog" 
         aria-modal="true"
         x-cloak>
        
        <div x-show="$wire.isOffcanvasOpen"
             x-transition:enter="ease-in-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in-out duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" 
             @click="$wire.isOffcanvasOpen = false"></div>

        <div class="fixed inset-y-0 right-0 flex max-w-full pl-10">
            <div x-show="$wire.isOffcanvasOpen"
                 x-transition:enter="transform transition ease-in-out duration-300 sm:duration-400"
                 x-transition:enter-start="translate-x-full"
                 x-transition:enter-end="translate-x-0"
                 x-transition:leave="transform transition ease-in-out duration-300 sm:duration-400"
                 x-transition:leave-start="translate-x-0"
                 x-transition:leave-end="translate-x-full"
                 class="w-screen max-w-md pointer-events-auto">
                 
                <div class="flex h-full flex-col overflow-y-scroll bg-white dark:bg-[#0a0a0a] shadow-2xl border-l border-slate-200 dark:border-[#333]">
                    
                    <div class="px-6 py-6 sm:px-8 border-b border-slate-200 dark:border-[#333] bg-slate-50/50 dark:bg-[#111]/30 relative">
                        <button type="button" @click="$wire.isOffcanvasOpen = false" class="absolute top-6 right-6 p-2 rounded-full text-slate-400 hover:text-slate-900 hover:bg-slate-100 dark:hover:text-white dark:hover:bg-[#1a1a1a] transition-all">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                        <h2 class="text-xl font-bold text-slate-900 dark:text-white mt-2">
                            {{ $schedule_id ? 'Modifier le créneau' : 'Ajouter un cours' }}
                        </h2>
                    </div>

                    <div class="p-6 sm:px-8 flex-1 overflow-y-auto">
                        <form wire:submit.prevent="saveSchedule" class="space-y-5">
                            
                            <!-- Class Information Selection -->
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1 font-semibold">Classe cible <span class="text-rose-500">*</span></label>
                                <select wire:model="selectedClassId" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white">
                                    @foreach($schoolClasses as $c)
                                        <option value="{{ $c['id'] }}">{{ $c['name'] }} ({{ $c['level'] }})</option>
                                    @endforeach
                                </select>
                                @error('selectedClassId') <span class="text-xs text-rose-500 mt-1">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1 font-semibold">Matière <span class="text-rose-500">*</span></label>
                                <select wire:model="form_subject_id" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white">
                                    <option value="">Sélectionner une matière</option>
                                    @foreach($subjects as $sub)
                                        <option value="{{ $sub['id'] }}">{{ $sub['name'] }}</option>
                                    @endforeach
                                </select>
                                @error('form_subject_id') <span class="text-xs text-rose-500 mt-1">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1 font-semibold">Jour de la semaine <span class="text-rose-500">*</span></label>
                                <select wire:model="form_day" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white">
                                    @foreach($days as $day)
                                        <option value="{{ $day }}">{{ $day }}</option>
                                    @endforeach
                                </select>
                                @error('form_day') <span class="text-xs text-rose-500 mt-1">{{ $message }}</span> @enderror
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1 font-semibold">Heure de début <span class="text-rose-500">*</span></label>
                                    <input type="time" wire:model="form_start_time" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white">
                                    @error('form_start_time') <span class="text-xs text-rose-500 mt-1">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1 font-semibold">Heure de fin <span class="text-rose-500">*</span></label>
                                    <input type="time" wire:model="form_end_time" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white">
                                    @error('form_end_time') <span class="text-xs text-rose-500 mt-1">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1 font-semibold">Professeur (Optionnel)</label>
                                <select wire:model="form_teacher_id" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white">
                                    <option value="">Sélectionner un professeur</option>
                                    @foreach($teachers as $t)
                                        <option value="{{ $t['id'] }}">{{ $t['first_name'] }} {{ $t['last_name'] }}</option>
                                    @endforeach
                                </select>
                                @error('form_teacher_id') <span class="text-xs text-rose-500 mt-1">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1 font-semibold">Salle (Optionnel)</label>
                                <input type="text" wire:model="form_room" placeholder="Ex: Salle 204" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white">
                            </div>

                            <div class="pt-6 mt-6 border-t border-slate-200 dark:border-[#333] flex gap-3">
                                @if($schedule_id)
                                    <button type="button" wire:click="deleteSchedule({{ $schedule_id }})" wire:confirm="Supprimer ce créneau ?" class="px-4 py-2 bg-rose-50 dark:bg-rose-900/20 text-rose-600 dark:text-rose-400 font-semibold rounded-lg hover:bg-rose-100 dark:hover:bg-rose-900/40 transition-all">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                @endif
                                <button type="button" @click="$wire.isOffcanvasOpen = false" class="flex-1 py-2 bg-white dark:bg-[#111] text-slate-700 dark:text-slate-300 font-semibold rounded-lg border border-slate-200 dark:border-[#333] hover:bg-slate-50 dark:hover:bg-[#1a1a1a] transition-all">
                                    Annuler
                                </button>
                                <button type="submit" class="flex-1 py-2 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 shadow-sm transition-all flex items-center justify-center gap-2">
                                    <i data-lucide="save" class="w-4 h-4"></i> Enregistrer
                                    <div wire:loading wire:target="saveSchedule" class="ml-2 w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                                </button>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

@script
<script>
    Livewire.hook('morph.updated', ({ component, el }) => {
        if(typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    });
</script>
@endscript

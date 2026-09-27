<div class="h-full flex flex-col p-6 max-w-7xl mx-auto space-y-8">
    <style>
        /* Quill JS Dark Mode compatibility overrides */
        .dark .ql-toolbar.ql-snow {
            border-color: #333 !important;
            background-color: #0f0f0f !important;
        }
        .dark .ql-container.ql-snow {
            border-color: #333 !important;
            background-color: #050505 !important;
        }
        .dark .ql-snow .ql-stroke {
            stroke: #cbd5e1 !important;
        }
        .dark .ql-snow .ql-fill {
            fill: #cbd5e1 !important;
        }
        .dark .ql-snow .ql-picker {
            color: #cbd5e1 !important;
        }
        .dark .ql-snow .ql-picker-options {
            background-color: #0f0f0f !important;
            border-color: #333 !important;
        }
        .dark .ql-editor.ql-blank::before {
            color: #475569 !important;
        }
        .dark .ql-editor {
            color: #f8fafc !important;
        }
        .ql-toolbar.ql-snow {
            border-top-left-radius: 12px;
            border-top-right-radius: 12px;
            border-color: #e2e8f0;
        }
        .ql-container.ql-snow {
            border-bottom-left-radius: 12px;
            border-bottom-right-radius: 12px;
            border-color: #e2e8f0;
        }
    </style>

    <!-- HEADER & ACTIONS -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white font-space">Analyses & Reporting RH</h1>
            <p class="text-slate-500 text-sm mt-1 font-space">Visualisez les indicateurs clés et exportez vos bilans et analyses RH.</p>
        </div>
    </div>

    <!-- STATS GRID -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <!-- Mass Salary -->
        <div class="bg-white dark:bg-[#0c0c0c] border border-slate-200 dark:border-[#1a1a1a] rounded-2xl p-6 shadow-sm flex items-center gap-4">
            <div class="p-3 bg-indigo-50 dark:bg-indigo-950/20 text-indigo-600 dark:text-indigo-400 rounded-xl">
                <i data-lucide="banknote" class="w-6 h-6"></i>
            </div>
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase">Masse Salariale Mensuelle</p>
                <h3 class="text-xl font-bold text-slate-900 dark:text-white mt-1">{{ number_format($stats['total_salary'], 2, ',', ' ') }} $</h3>
            </div>
        </div>

        <!-- Average Salary -->
        <div class="bg-white dark:bg-[#0c0c0c] border border-slate-200 dark:border-[#1a1a1a] rounded-2xl p-6 shadow-sm flex items-center gap-4">
            <div class="p-3 bg-emerald-50 dark:bg-emerald-950/20 text-emerald-600 dark:text-emerald-400 rounded-xl">
                <i data-lucide="trending-up" class="w-6 h-6"></i>
            </div>
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase">Salaire Moyen</p>
                <h3 class="text-xl font-bold text-slate-900 dark:text-white mt-1">{{ number_format($stats['avg_salary'], 2, ',', ' ') }} $</h3>
            </div>
        </div>

        <!-- Attendance Rate -->
        <div class="bg-white dark:bg-[#0c0c0c] border border-slate-200 dark:border-[#1a1a1a] rounded-2xl p-6 shadow-sm flex items-center gap-4">
            <div class="p-3 bg-blue-50 dark:bg-blue-950/20 text-blue-600 dark:text-blue-400 rounded-xl">
                <i data-lucide="check-square" class="w-6 h-6"></i>
            </div>
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase">Taux de Présence Moyen</p>
                <h3 class="text-xl font-bold text-slate-900 dark:text-white mt-1">{{ $stats['presence_rate'] }}%</h3>
            </div>
        </div>

        <!-- Active Conflicts -->
        <div class="bg-white dark:bg-[#0c0c0c] border border-slate-200 dark:border-[#1a1a1a] rounded-2xl p-6 shadow-sm flex items-center gap-4">
            <div class="p-3 bg-rose-50 dark:bg-rose-950/20 text-rose-600 dark:text-rose-400 rounded-xl">
                <i data-lucide="shield-alert" class="w-6 h-6"></i>
            </div>
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase">Incidents / Conflits Actifs</p>
                <h3 class="text-xl font-bold text-slate-900 dark:text-white mt-1">
                    {{ $stats['conflicts_active'] }} <span class="text-xs font-normal text-rose-500 font-sans">({{ $stats['conflicts_critical'] }} critiques)</span>
                </h3>
            </div>
        </div>
    </div>

    <!-- CHARTS GRID -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Chart 1: Specialties -->
        <div class="bg-white dark:bg-[#0a0a0a] border border-slate-200 dark:border-[#333] rounded-2xl p-6 shadow-sm">
            <h4 class="text-sm font-bold text-slate-700 dark:text-slate-300 mb-4 uppercase tracking-wider font-space">Répartition des effectifs par fonction</h4>
            <div x-data="{
                init() {
                    const specs = @js($stats['specialties']);
                    const labels = specs.map(s => s.specialty);
                    const counts = specs.map(s => s.count);
                    const options = {
                        series: counts,
                        chart: { type: 'donut', height: 260 },
                        labels: labels,
                        colors: ['#4f46e5', '#10b981', '#f59e0b', '#3b82f6', '#ec4899', '#8b5cf6', '#06b6d4'],
                        legend: { position: 'bottom', labels: { colors: '#94a3b8' } },
                        stroke: { show: false }
                    };
                    const chart = new ApexCharts(this.$refs.specChart, options);
                    chart.render();
                }
            }" class="w-full flex justify-center">
                <div x-ref="specChart" class="w-full max-w-[340px] h-[260px]"></div>
            </div>
        </div>

        <!-- Chart 2: Employee status -->
        <div class="bg-white dark:bg-[#0a0a0a] border border-slate-200 dark:border-[#333] rounded-2xl p-6 shadow-sm">
            <h4 class="text-sm font-bold text-slate-700 dark:text-slate-300 mb-4 uppercase tracking-wider font-space">Statut des effectifs globaux</h4>
            <div x-data="{
                init() {
                    const stats = @js($stats);
                    const categories = ['Actifs', 'Candidats', 'En congé', 'Anciens'];
                    const counts = [stats.active_employees, stats.candidats, stats.leaves_count, stats.anciens_count];
                    const options = {
                        series: [{ name: 'Collaborateurs', data: counts }],
                        chart: { type: 'bar', height: 260, toolbar: { show: false } },
                        colors: ['#10b981', '#f59e0b', '#3b82f6', '#94a3b8'],
                        plotOptions: {
                            bar: {
                                borderRadius: 8,
                                columnWidth: '45%',
                                distributed: true
                            }
                        },
                        legend: { show: false },
                        xaxis: {
                            categories: categories,
                            labels: { style: { colors: '#94a3b8' } }
                        },
                        yaxis: {
                            labels: { style: { colors: '#94a3b8' } }
                        },
                        grid: { borderColor: '#e2e8f01a' }
                    };
                    const chart = new ApexCharts(this.$refs.statusChart, options);
                    chart.render();
                }
            }" class="w-full">
                <div x-ref="statusChart" class="w-full h-[260px]"></div>
            </div>
        </div>
    </div>

    <!-- REPORT EDITOR SECTION -->
    <div class="bg-white dark:bg-[#0a0a0a] border border-slate-200 dark:border-[#333] rounded-2xl p-6 shadow-sm space-y-6">
        <div>
            <h3 class="text-md font-bold text-slate-900 dark:text-white font-space flex items-center gap-2">
                <i data-lucide="file-edit" class="w-5 h-5 text-indigo-500"></i>
                Rédiger le rapport d'analyse textuel
            </h3>
            <p class="text-slate-500 text-xs mt-1">Saisissez vos observations, bilans et préconisations. Ce texte sera inclus dans l'export PDF du rapport.</p>
        </div>

        <div class="space-y-4">
            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Titre du Rapport</label>
                <input type="text" wire:model="report_title" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white font-semibold">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Corps du Rapport</label>
                <div x-data="{
                    quill: null,
                    content: @entangle('report_content')
                }" x-init="
                    quill = new Quill($refs.editor, {
                        theme: 'snow',
                        placeholder: 'Rédigez votre rapport...',
                        modules: {
                            toolbar: [
                                ['bold', 'italic', 'underline'],
                                [{ 'header': [2, 3, false] }],
                                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                                ['clean']
                            ]
                        }
                    });
                    quill.root.innerHTML = content || '';
                    quill.on('text-change', () => {
                        content = quill.root.innerHTML;
                    });
                    $watch('content', value => {
                        if (value !== quill.root.innerHTML) {
                            quill.root.innerHTML = value || '';
                        }
                    });
                }" wire:ignore class="space-y-2">
                    <div class="bg-white dark:bg-[#050505] rounded-xl overflow-hidden border border-slate-200 dark:border-[#333] shadow-sm">
                        <div x-ref="editor" class="min-h-[220px] text-sm text-slate-900 dark:text-white"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-end pt-4 border-t border-slate-100 dark:border-[#1a1a1a]">
            <button type="button" wire:click="exportReportPdf" class="px-6 py-2.5 bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 transition-all shadow-md shadow-indigo-500/20 flex items-center gap-2">
                <i data-lucide="file-text" class="w-4 h-4"></i>
                Générer & Exporter le Rapport PDF
            </button>
        </div>
    </div>
</div>

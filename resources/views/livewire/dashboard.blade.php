<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Printing" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Printing', 'href' => route('printing.dashboard'), 'icon' => 'printer'],
            ['label' => 'Dashboard', 'icon' => 'chart-bar'],
        ]" />
    </x-slot>

    {{-- Ansicht --}}
    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Ansicht" icon="heroicon-o-eye" width="w-72" :defaultOpen="true">
            <div class="p-4 space-y-6">
                <section>
                    <h3 class="text-[10px] font-semibold uppercase tracking-wider text-[var(--ui-muted)] mb-2">Perspektive</h3>
                    <div class="space-y-1">
                        <button type="button" wire:click="$set('perspective', 'personal')"
                            class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors {{ $perspective === 'personal' ? 'bg-[var(--ui-primary)] text-[var(--ui-on-primary)] font-medium' : 'text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)]' }}">
                            @svg('heroicon-o-user', 'w-5 h-5 shrink-0')
                            <span>Persönlich</span>
                        </button>
                        <button type="button" wire:click="$set('perspective', 'team')"
                            class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors {{ $perspective === 'team' ? 'bg-[var(--ui-primary)] text-[var(--ui-on-primary)] font-medium' : 'text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)]' }}">
                            @svg('heroicon-o-users', 'w-5 h-5 shrink-0')
                            <span>Team</span>
                        </button>
                    </div>
                    <p class="text-xs text-[var(--ui-muted)] mt-2 px-1">
                        {{ $perspective === 'personal' ? 'Deine eigenen Aufträge und Drucker.' : 'Alle Drucker und Jobs des Teams.' }}
                    </p>
                </section>

                <section>
                    <h3 class="text-[10px] font-semibold uppercase tracking-wider text-[var(--ui-muted)] mb-2">Auf einen Blick</h3>
                    <dl class="rounded-lg border border-[var(--ui-border)] divide-y divide-[var(--ui-border)] overflow-hidden">
                        <div class="flex items-center justify-between gap-3 px-3 py-2">
                            <dt class="text-xs text-[var(--ui-muted)]">Drucker aktiv</dt>
                            <dd class="text-sm text-[var(--ui-secondary)] m-0">{{ $activePrinters }} / {{ $totalPrinters }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3 px-3 py-2">
                            <dt class="text-xs text-[var(--ui-muted)]">Gruppen aktiv</dt>
                            <dd class="text-sm text-[var(--ui-secondary)] m-0">{{ $activeGroups }} / {{ $totalGroups }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3 px-3 py-2">
                            <dt class="text-xs text-[var(--ui-muted)]">Jobs wartend</dt>
                            <dd class="text-sm text-[var(--ui-secondary)] m-0">{{ $pendingJobs }}</dd>
                        </div>
                    </dl>
                </section>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    {{-- Aktivitäten --}}
    <x-slot name="activity">
        <x-ui-page-sidebar title="Aktivitäten" icon="heroicon-o-bolt" width="w-80" :defaultOpen="false" storeKey="activityOpen" side="right">
            <div class="p-4">
                <livewire:printing.activity-feed wire:key="printing-activity-feed" />
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-ui-page-container>
        {{-- Kennzahlen --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <x-ui-dashboard-tile
                title="Drucker"
                :count="$totalPrinters"
                subtitle="aktiv: {{ $activePrinters }}"
                icon="printer"
                variant="primary"
                size="lg"
                :href="route('printing.printers.index')"
            />
            <x-ui-dashboard-tile
                title="Gruppen"
                :count="$totalGroups"
                subtitle="aktiv: {{ $activeGroups }}"
                icon="folder"
                variant="secondary"
                size="lg"
                :href="route('printing.groups.index')"
            />
            <x-ui-dashboard-tile
                title="Print Jobs"
                :count="$totalJobs"
                subtitle="wartend: {{ $pendingJobs }}"
                icon="document-text"
                variant="warning"
                size="lg"
                :href="route('printing.jobs.index')"
            />
        </div>

        {{-- Status-Übersicht --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <x-ui-dashboard-tile title="Bereit" :count="$printerStatus['ready']" icon="check-circle" variant="success" size="sm" />
            <x-ui-dashboard-tile title="Beschäftigt" :count="$printerStatus['busy']" icon="clock" variant="warning" size="sm" />
            <x-ui-dashboard-tile title="Fehler" :count="$printerStatus['error']" icon="x-circle" variant="danger" size="sm" />
            <x-ui-dashboard-tile title="Abgeschlossen" :count="$completedJobs" icon="check" variant="success" size="sm" />
        </div>

        {{-- Neueste Jobs --}}
        <div class="space-y-3">
            <div class="flex items-center gap-2">
                @svg('heroicon-o-queue-list', 'w-5 h-5 text-[var(--ui-secondary)]')
                <h3 class="text-base font-semibold text-[var(--ui-secondary)] m-0">Neueste Print Jobs</h3>
                <x-ui-badge variant="secondary" size="sm">{{ $recentJobs->count() }}</x-ui-badge>
                <span class="text-xs text-[var(--ui-muted)] ml-1">letzte 10 Aufträge im Team</span>
            </div>

            @if($recentJobs->count() > 0)
                <x-ui-table>
                    <x-ui-table-header>
                        <x-ui-table-header-cell>Template</x-ui-table-header-cell>
                        <x-ui-table-header-cell>Status</x-ui-table-header-cell>
                        <x-ui-table-header-cell>Ziel</x-ui-table-header-cell>
                        <x-ui-table-header-cell>Erstellt</x-ui-table-header-cell>
                    </x-ui-table-header>

                    <x-ui-table-body>
                        @foreach($recentJobs as $job)
                            <x-ui-table-row
                                clickable="true"
                                :href="route('printing.jobs.show', ['job' => $job->id])"
                            >
                                <x-ui-table-cell>
                                    <span class="font-medium text-[var(--ui-secondary)]">{{ $job->template }}</span>
                                </x-ui-table-cell>
                                <x-ui-table-cell>
                                    <x-ui-badge
                                        variant="{{ $job->status_color }}"
                                        size="sm"
                                    >
                                        {{ $job->status_description }}
                                    </x-ui-badge>
                                </x-ui-table-cell>
                                <x-ui-table-cell>
                                    @if($job->printer)
                                        {{ $job->printer->name }}
                                    @elseif($job->printerGroup)
                                        Gruppe: {{ $job->printerGroup->name }}
                                    @else
                                        –
                                    @endif
                                </x-ui-table-cell>
                                <x-ui-table-cell>{{ $job->created_at->diffForHumans() }}</x-ui-table-cell>
                            </x-ui-table-row>
                        @endforeach
                    </x-ui-table-body>
                </x-ui-table>
            @else
                <div class="rounded-xl bg-[var(--ui-surface)] border border-[var(--ui-border)] shadow-sm p-12 text-center">
                    @svg('heroicon-o-queue-list', 'w-10 h-10 mx-auto text-[var(--ui-muted)] opacity-40 mb-3')
                    <div class="text-base font-medium text-[var(--ui-secondary)]">Keine Print Jobs</div>
                    <div class="text-sm text-[var(--ui-muted)] mt-1">Sobald Aufträge erstellt werden, erscheinen sie hier.</div>
                </div>
            @endif
        </div>
    </x-ui-page-container>
</x-ui-page>

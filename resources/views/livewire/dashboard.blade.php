<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Printing" icon="heroicon-o-printer" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Printing', 'href' => route('printing.dashboard'), 'icon' => 'printer'],
            ['label' => 'Dashboard'],
        ]">
            {{-- Der frühere Hero-Balken trug nur diese eine Aussage – und nahm
                 dafür die halbe erste Bildschirmhöhe. Als Marke in der
                 Actionbar steht sie dort, wo man ohnehin hinsieht, und die
                 Kennzahlen beginnen oben. --}}
            @if($failedJobs > 0)
                <x-nx-button variant="danger" :href="route('printing.jobs.index')" wire:navigate>
                    @svg('heroicon-o-exclamation-triangle', 'w-4 h-4')
                    <span>{{ $failedJobs }} fehlgeschlagen</span>
                </x-nx-button>
            @else
                <x-nx-badge variant="success" dot>Alles im grünen Bereich</x-nx-badge>
            @endif
        </x-ui-page-actionbar>
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
    <div class="space-y-6">

    {{-- Kennzahlen --}}
    <x-nx-stat-grid>
        <x-nx-stat label="Drucker" :value="$activePrinters . ' / ' . $totalPrinters"
            hint="aktiv" icon="heroicon-o-printer" accent="var(--nx-accent)"
            :href="route('printing.printers.index')" wire:navigate />
        <x-nx-stat label="Gruppen" :value="$activeGroups . ' / ' . $totalGroups"
            hint="aktiv" icon="heroicon-o-folder" accent="var(--nx-accent)"
            :href="route('printing.groups.index')" wire:navigate />
        <x-nx-stat label="Print Jobs" :value="(string) $totalJobs"
            :hint="$pendingJobs . ' wartend · ' . $completedJobs . ' gedruckt'"
            icon="heroicon-o-document-text" accent="var(--nx-info)"
            :href="route('printing.jobs.index')" wire:navigate />
        {{-- Der Akzent zieht nur an, wenn wirklich etwas liegt; bei null
             bleibt die Kachel ruhig, statt mit Warnfarbe Aufmerksamkeit zu
             fordern. --}}
        <x-nx-stat label="Fehlgeschlagen" :value="(string) $failedJobs"
            :hint="$failedJobs === 0 ? 'nichts liegengeblieben' : 'warten auf einen zweiten Versuch'"
            icon="heroicon-o-exclamation-triangle"
            :accent="$failedJobs > 0 ? 'var(--nx-danger)' : 'var(--nx-muted)'"
            :href="route('printing.jobs.index')" wire:navigate />
    </x-nx-stat-grid>

    {{-- Drucker-Status: gezählt wird nach anstehenden Aufträgen, nicht nach
         einer Rückmeldung des Geräts – CloudPRNT-Drucker fragen nur an, sie
         melden von sich aus nichts. --}}
    <x-nx-section icon="heroicon-o-signal" title="Drucker-Status" description="Aktive Drucker nach anstehenden Aufträgen">
        <x-nx-stat-grid cols="3">
            <x-nx-stat label="Bereit" :value="(string) $printerStatus['ready']" icon="heroicon-o-check-circle" accent="var(--nx-success)" />
            <x-nx-stat label="Beschäftigt" :value="(string) $printerStatus['busy']" icon="heroicon-o-clock" accent="var(--nx-warning)" />
            <x-nx-stat label="Fehler" :value="(string) $printerStatus['error']" icon="heroicon-o-x-circle"
                :accent="$printerStatus['error'] > 0 ? 'var(--nx-danger)' : 'var(--nx-muted)'" />
        </x-nx-stat-grid>
    </x-nx-section>

    {{-- Neueste Jobs --}}
    <x-nx-card flush>
        <div class="flex items-center gap-2 border-b border-[color:var(--nx-line)] px-4 py-3">
            @svg('heroicon-o-queue-list', 'w-4 h-4 text-[color:var(--nx-muted)]')
            <h2 class="m-0 text-xs font-semibold text-[color:var(--nx-muted)]">Neueste Print Jobs</h2>
            <span class="text-xs text-[color:var(--nx-faint)]">letzte {{ $recentJobs->count() }} im Team</span>
            <a href="{{ route('printing.jobs.index') }}" wire:navigate class="ml-auto text-xs text-[color:var(--nx-muted)] transition-colors hover:text-[color:var(--nx-text)]">Alle</a>
        </div>

        @if($recentJobs->count() > 0)
            <x-nx-table>
                <x-nx-table-header>
                    <x-nx-table-header-cell>Template</x-nx-table-header-cell>
                    <x-nx-table-header-cell>Status</x-nx-table-header-cell>
                    <x-nx-table-header-cell>Ziel</x-nx-table-header-cell>
                    <x-nx-table-header-cell>Erstellt</x-nx-table-header-cell>
                </x-nx-table-header>
                <x-nx-table-body>
                    @foreach($recentJobs as $job)
                        <x-nx-table-row wire:key="dash-job-{{ $job->id }}" clickable :href="route('printing.jobs.show', ['job' => $job->id])">
                            <x-nx-table-cell>
                                <span class="font-medium text-[color:var(--nx-text)]">{{ $job->template }}</span>
                            </x-nx-table-cell>
                            <x-nx-table-cell>
                                <x-nx-badge :variant="$job->status_color">{{ $job->status_description }}</x-nx-badge>
                            </x-nx-table-cell>
                            <x-nx-table-cell class="text-[color:var(--nx-muted)]">
                                @if($job->printer)
                                    {{ $job->printer->name }}
                                @elseif($job->printerGroup)
                                    Gruppe: {{ $job->printerGroup->name }}
                                @else
                                    <span class="text-[color:var(--nx-faint)]">–</span>
                                @endif
                            </x-nx-table-cell>
                            <x-nx-table-cell class="whitespace-nowrap text-[color:var(--nx-muted)]">{{ $job->created_at->diffForHumans() }}</x-nx-table-cell>
                        </x-nx-table-row>
                    @endforeach
                </x-nx-table-body>
            </x-nx-table>
        @else
            <x-nx-empty icon="heroicon-o-queue-list">
                Noch keine Print Jobs
                <x-slot name="action">
                    <span class="text-xs text-[color:var(--nx-faint)]">Sobald Aufträge erstellt werden, erscheinen sie hier.</span>
                </x-slot>
            </x-nx-empty>
        @endif
    </x-nx-card>

    </div>
    </x-ui-page-container>
</x-ui-page>

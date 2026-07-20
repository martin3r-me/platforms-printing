<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Printing" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Printing', 'href' => route('printing.dashboard'), 'icon' => 'printer'],
            ['label' => 'Print Jobs', 'icon' => 'document-text'],
        ]" />
    </x-slot>

    {{-- Filter --}}
    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Filter" icon="heroicon-o-funnel" width="w-72" :defaultOpen="true">
            <div class="p-4 space-y-6">
                <section>
                    <h3 class="text-[10px] font-semibold uppercase tracking-wider text-[var(--ui-muted)] mb-2">Suche</h3>
                    <div class="relative">
                        @svg('heroicon-o-magnifying-glass', 'w-4 h-4 text-[var(--ui-muted)] absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none')
                        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Template, UUID…"
                            class="w-full pl-9 pr-3 py-2 text-sm rounded-lg border border-[var(--ui-border)] bg-[var(--ui-surface)] text-[var(--ui-secondary)] placeholder-[var(--ui-muted)] focus:outline-none focus:ring-2 focus:ring-[var(--ui-primary)]/20 focus:border-[var(--ui-primary)]" />
                    </div>
                </section>

                <section>
                    <h3 class="text-[10px] font-semibold uppercase tracking-wider text-[var(--ui-muted)] mb-2">Status</h3>
                    <div class="space-y-1">
                        @foreach(['all' => 'Alle', 'pending' => 'Wartend', 'processing' => 'In Bearbeitung', 'completed' => 'Gedruckt', 'failed' => 'Fehlgeschlagen', 'cancelled' => 'Abgebrochen'] as $val => $label)
                            <button type="button" wire:click="$set('statusFilter', '{{ $val }}')"
                                class="w-full flex items-center px-3 py-2 rounded-lg text-sm transition-colors {{ $statusFilter === $val ? 'bg-[var(--ui-primary)] text-[var(--ui-on-primary)] font-medium' : 'text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)]' }}">
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
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
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <x-ui-dashboard-tile title="Gesamt" :count="$stats['total']" icon="document-text" variant="primary" size="sm" />
            <x-ui-dashboard-tile title="Wartend" :count="$stats['pending']" icon="clock" variant="warning" size="sm" />
            <x-ui-dashboard-tile title="Abgeschlossen" :count="$stats['completed']" icon="check-circle" variant="success" size="sm" />
            <x-ui-dashboard-tile title="Fehlgeschlagen" :count="$stats['failed']" icon="x-circle" variant="danger" size="sm" />
        </div>

        {{-- Tabelle --}}
        @if($jobs->count() > 0)
            <x-ui-table>
                <x-ui-table-header>
                    <x-ui-table-header-cell>Template</x-ui-table-header-cell>
                    <x-ui-table-header-cell>Status</x-ui-table-header-cell>
                    <x-ui-table-header-cell>Ziel</x-ui-table-header-cell>
                    <x-ui-table-header-cell>Erstellt</x-ui-table-header-cell>
                    <x-ui-table-header-cell align="right">Aktionen</x-ui-table-header-cell>
                </x-ui-table-header>

                <x-ui-table-body>
                    @foreach($jobs as $job)
                        <x-ui-table-row
                            clickable="true"
                            :href="route('printing.jobs.show', ['job' => $job->id])"
                        >
                            <x-ui-table-cell>
                                <span class="font-medium text-[var(--ui-secondary)]">{{ $job->template }}</span>
                            </x-ui-table-cell>
                            <x-ui-table-cell>
                                <x-ui-badge
                                    variant="{{ in_array($job->status, ['pending','processing']) ? 'warning' : ($job->status === 'completed' ? 'success' : ($job->status === 'failed' ? 'danger' : 'secondary')) }}"
                                    size="sm"
                                >
                                    {{ $job->status_description }}
                                </x-ui-badge>
                            </x-ui-table-cell>
                            <x-ui-table-cell>
                                <span class="text-sm">
                                    {{ $job->printable_name }} #{{ $job->printable_id }}
                                    @if($job->printer)
                                        <span class="text-[var(--ui-muted)]">· {{ $job->printer->name }}</span>
                                    @elseif($job->printerGroup)
                                        <span class="text-[var(--ui-muted)]">· {{ $job->printerGroup->name }}</span>
                                    @endif
                                </span>
                            </x-ui-table-cell>
                            <x-ui-table-cell>{{ $job->created_at->diffForHumans() }}</x-ui-table-cell>
                            <x-ui-table-cell align="right">
                                <div class="flex items-center gap-2 justify-end" @click.stop>
                                    @if($job->status === 'failed')
                                        <x-ui-button wire:click="retryJob({{ $job->id }})" size="sm" variant="secondary">Wiederholen</x-ui-button>
                                    @endif
                                    @if(in_array($job->status, ['pending', 'processing']))
                                        <x-ui-button variant="danger-outline" wire:click="cancelJob({{ $job->id }})" size="sm">Abbrechen</x-ui-button>
                                    @endif
                                </div>
                            </x-ui-table-cell>
                        </x-ui-table-row>
                    @endforeach
                </x-ui-table-body>
            </x-ui-table>

            <div>{{ $jobs->links() }}</div>
        @else
            <div class="rounded-xl bg-[var(--ui-surface)] border border-[var(--ui-border)] shadow-sm p-12 text-center">
                @svg('heroicon-o-queue-list', 'w-10 h-10 mx-auto text-[var(--ui-muted)] opacity-40 mb-3')
                <div class="text-base font-medium text-[var(--ui-secondary)]">Keine Jobs gefunden</div>
                <div class="text-sm text-[var(--ui-muted)] mt-1">Sobald Aufträge erstellt werden, erscheinen sie hier.</div>
            </div>
        @endif
    </x-ui-page-container>
</x-ui-page>

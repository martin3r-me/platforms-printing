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

    <x-ui-page-container>
        <!-- Kennzahlen -->
        <div class="grid grid-cols-4 gap-4">
            <x-ui-dashboard-tile title="Gesamt" :count="$stats['total']" icon="document-text" variant="primary" size="lg" />
            <x-ui-dashboard-tile title="Wartend" :count="$stats['pending']" icon="clock" variant="warning" size="lg" />
            <x-ui-dashboard-tile title="Abgeschlossen" :count="$stats['completed']" icon="check-circle" variant="success" size="lg" />
            <x-ui-dashboard-tile title="Fehlgeschlagen" :count="$stats['failed']" icon="x-circle" variant="danger" size="lg" />
        </div>

        <!-- Tabelle -->
        <div class="bg-[var(--ui-surface)] rounded-lg shadow-sm border border-[var(--ui-border)]">
            @if($jobs->count() > 0)
                <x-ui-table>
                    <x-ui-table-header>
                        <x-ui-table-header-cell>Template</x-ui-table-header-cell>
                        <x-ui-table-header-cell>Status</x-ui-table-header-cell>
                        <x-ui-table-header-cell>Ziel</x-ui-table-header-cell>
                        <x-ui-table-header-cell>Erstellt</x-ui-table-header-cell>
                        <x-ui-table-header-cell>Aktionen</x-ui-table-header-cell>
                    </x-ui-table-header>

                    <x-ui-table-body>
                        @foreach($jobs as $job)
                            <x-ui-table-row>
                                <x-ui-table-cell>
                                    <a href="{{ route('printing.jobs.show', $job) }}" class="text-[var(--ui-primary)] hover:underline">{{ $job->template }}</a>
                                </x-ui-table-cell>
                                <x-ui-table-cell>
                                    <x-ui-badge
                                        variant="{{ in_array($job->status, ['pending','processing']) ? 'warning' : ($job->status === 'completed' ? 'success' : ($job->status === 'failed' ? 'danger' : 'secondary')) }}"
                                        size="sm"
                                    >
                                        {{ ucfirst($job->status) }}
                                    </x-ui-badge>
                                </x-ui-table-cell>
                                <x-ui-table-cell>
                                    {{ $job->printable_type }} #{{ $job->printable_id }}
                                    @if($job->printer)
                                        • {{ $job->printer->name }}
                                    @endif
                                    @if($job->printerGroup)
                                        • {{ $job->printerGroup->name }}
                                    @endif
                                </x-ui-table-cell>
                                <x-ui-table-cell>{{ $job->created_at->diffForHumans() }}</x-ui-table-cell>
                                <x-ui-table-cell>
                                    @if($job->status === 'failed')
                                        <x-ui-button wire:click="retryJob({{ $job->id }})" size="sm" variant="secondary">Wiederholen</x-ui-button>
                                    @endif
                                    @if(in_array($job->status, ['pending', 'processing']))
                                        <x-ui-button variant="danger-outline" wire:click="cancelJob({{ $job->id }})" size="sm">Abbrechen</x-ui-button>
                                    @endif
                                </x-ui-table-cell>
                            </x-ui-table-row>
                        @endforeach
                    </x-ui-table-body>
                </x-ui-table>
            @else
                <div class="text-center py-12 text-[var(--ui-muted)]">Keine Jobs gefunden</div>
            @endif
        </div>

        <div class="mt-4">{{ $jobs->links() }}</div>
    </x-ui-page-container>
</x-ui-page>

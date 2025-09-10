<div>
    <div>
        <div class="d-flex justify-between items-center">
            <div>
                <h1>{{ $printer->name }}</h1>
                <p>{{ $printer->location }}</p>
            </div>
            <div>
                <x-ui-badge variant="{{ $printer->is_active ? 'success' : 'danger' }}" size="sm">
                    {{ $printer->is_active ? 'Aktiv' : 'Inaktiv' }}
                </x-ui-badge>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-4 gap-4 mb-6">
        <x-ui-dashboard-tile title="Gesamt Jobs" :count="$stats['total']" icon="document-text" variant="primary" size="lg" />
        <x-ui-dashboard-tile title="Wartend" :count="$stats['pending']" icon="clock" variant="warning" size="lg" />
        <x-ui-dashboard-tile title="Abgeschlossen" :count="$stats['completed']" icon="check-circle" variant="success" size="lg" />
        <x-ui-dashboard-tile title="Fehlgeschlagen" :count="$stats['failed']" icon="x-circle" variant="danger" size="lg" />
    </div>

    <div>
        <x-ui-input-select
            name="statusFilter"
            label="Status filtern"
            :options="[
                ['value' => 'all', 'label' => 'Alle Status'],
                ['value' => 'pending', 'label' => 'Wartend'],
                ['value' => 'processing', 'label' => 'Verarbeitung'],
                ['value' => 'completed', 'label' => 'Abgeschlossen'],
                ['value' => 'failed', 'label' => 'Fehlgeschlagen'],
                ['value' => 'cancelled', 'label' => 'Abgebrochen'],
            ]"
            optionValue="value"
            optionLabel="label"
            wire:model.live="statusFilter"
        />
    </div>

    @if($jobs->count() > 0)
        <x-ui-table>
            <x-ui-table-header>
                <x-ui-table-header-cell>Template</x-ui-table-header-cell>
                <x-ui-table-header-cell>Status</x-ui-table-header-cell>
                <x-ui-table-header-cell>Objekt</x-ui-table-header-cell>
                <x-ui-table-header-cell align="right">Aktionen</x-ui-table-header-cell>
            </x-ui-table-header>

            <x-ui-table-body>
                @foreach($jobs as $job)
                    <x-ui-table-row>
                        <x-ui-table-cell>
                            <div class="font-medium">{{ $job->template }}</div>
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
                            <div class="text-sm text-muted">{{ $job->printable_type }} #{{ $job->printable_id }}</div>
                        </x-ui-table-cell>
                        <x-ui-table-cell align="right">
                            <div class="d-flex items-center gap-2 justify-end">
                                @if($job->status === 'failed')
                                    <x-ui-button size="sm" variant="secondary" wire:click="retryJob({{ $job->id }})">Wiederholen</x-ui-button>
                                @endif
                                @if(in_array($job->status, ['pending','processing']))
                                    <x-ui-button size="sm" variant="danger-outline" wire:click="cancelJob({{ $job->id }})">Abbrechen</x-ui-button>
                                @endif
                            </div>
                        </x-ui-table-cell>
                    </x-ui-table-row>
                @endforeach
            </x-ui-table-body>
        </x-ui-table>
        <div class="mt-4">{{ $jobs->links() }}</div>
    @else
        <div class="text-center py-8">Keine Jobs gefunden</div>
    @endif
</div>

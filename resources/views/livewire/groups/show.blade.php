<div>
    <div>
        <div>
            <div>
                <h1>{{ $group->name }}</h1>
                <p>{{ $group->description }}</p>
            </div>
            <div>
                <x-ui-badge variant="{{ $group->is_active ? 'success' : 'secondary' }}" size="sm">
                    {{ $group->is_active ? 'Aktiv' : 'Inaktiv' }}
                </x-ui-badge>
            </div>
        </div>
    </div>

    <div>
        <x-ui-dashboard-tile title="Gesamt Jobs" :count="$stats['total']" size="sm" />
        <x-ui-dashboard-tile title="Wartend" :count="$stats['pending']" size="sm" />
        <x-ui-dashboard-tile title="Abgeschlossen" :count="$stats['completed']" size="sm" />
        <x-ui-dashboard-tile title="Fehlgeschlagen" :count="$stats['failed']" size="sm" />
    </div>

    <div>
        <x-ui-input-select wire:model.live="statusFilter">
            <option value="all">Alle Status</option>
            <option value="pending">Wartend</option>
            <option value="processing">Verarbeitung</option>
            <option value="completed">Abgeschlossen</option>
            <option value="failed">Fehlgeschlagen</option>
            <option value="cancelled">Abgebrochen</option>
        </x-ui-input-select>
    </div>

    <div>
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
                            <x-ui-table-cell>{{ $job->template }}</x-ui-table-cell>
                            <x-ui-table-cell>
                                <x-ui-badge
                                    variant="{{ in_array($job->status, ['pending','processing']) ? 'warning' : ($job->status === 'completed' ? 'success' : ($job->status === 'failed' ? 'danger' : 'secondary')) }}"
                                    size="sm"
                                >
                                    {{ ucfirst($job->status) }}
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
                            <x-ui-table-cell>
                                @if($job->status === 'failed')
                                    <x-ui-button wire:click="retryJob({{ $job->id }})" size="sm">Wiederholen</x-ui-button>
                                @endif
                                @if(in_array($job->status, ['pending', 'processing']))
                                    <x-ui-button variant="danger" wire:click="cancelJob({{ $job->id }})" size="sm">Abbrechen</x-ui-button>
                                @endif
                            </x-ui-table-cell>
                        </x-ui-table-row>
                    @endforeach
                </x-ui-table-body>
            </x-ui-table>
        @else
            <div>Keine Jobs gefunden</div>
        @endif
    </div>

    {{ $jobs->links() }}
</div>
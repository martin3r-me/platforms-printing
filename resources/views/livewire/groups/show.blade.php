<div class="h-full overflow-y-auto p-6">
    <!-- Header -->
    <div class="mb-6">
        <div class="d-flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $group->name }}</h1>
                <p class="text-gray-600">{{ $group->description }}</p>
            </div>
            <div>
                <x-ui-badge variant="{{ $group->is_active ? 'success' : 'secondary' }}" size="sm">
                    {{ $group->is_active ? 'Aktiv' : 'Inaktiv' }}
                </x-ui-badge>
            </div>
        </div>
    </div>

    <!-- Kennzahlen -->
    <div class="grid grid-cols-4 gap-4 mb-6">
        <x-ui-dashboard-tile title="Gesamt Jobs" :count="$stats['total']" icon="document-text" variant="primary" size="lg" />
        <x-ui-dashboard-tile title="Wartend" :count="$stats['pending']" icon="clock" variant="warning" size="lg" />
        <x-ui-dashboard-tile title="Abgeschlossen" :count="$stats['completed']" icon="check-circle" variant="success" size="lg" />
        <x-ui-dashboard-tile title="Fehlgeschlagen" :count="$stats['failed']" icon="x-circle" variant="danger" size="lg" />
    </div>

    <!-- Filter -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
        <div class="grid grid-cols-4 gap-4 items-end">
            <div class="col-span-3">
                <x-ui-input-select name="statusFilter" wire:model.live="statusFilter" label="Status filtern">
                    <option value="all">Alle Status</option>
                    <option value="pending">Wartend</option>
                    <option value="processing">Verarbeitung</option>
                    <option value="completed">Abgeschlossen</option>
                    <option value="failed">Fehlgeschlagen</option>
                    <option value="cancelled">Abgebrochen</option>
                </x-ui-input-select>
            </div>
        </div>
    </div>

    <!-- Tabelle -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
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
                            <x-ui-table-cell align="right">
                                <div class="d-flex items-center gap-2 justify-end">
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
        @else
            <div class="text-center py-12 text-gray-600">Keine Jobs gefunden</div>
        @endif
    </div>

    <div class="mt-4">{{ $jobs->links() }}</div>
</div>
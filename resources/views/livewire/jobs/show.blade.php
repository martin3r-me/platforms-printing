<div>
    <div>
        <div>
            <div>
                <h1>Print Job Details</h1>
                <p>Job #{{ $job->id }}</p>
            </div>
            <div>
                <x-ui-badge
                    variant="{{ in_array($job->status, ['pending','processing']) ? 'warning' : ($job->status === 'completed' ? 'success' : ($job->status === 'failed' ? 'danger' : 'secondary')) }}"
                    size="sm"
                >
                    {{ ucfirst($job->status) }}
                </x-ui-badge>
                @if($job->status === 'failed')
                    <x-ui-button wire:click="retryJob" size="sm">Wiederholen</x-ui-button>
                @endif
                @if(in_array($job->status, ['pending', 'processing']))
                    <x-ui-button variant="danger" wire:click="cancelJob" size="sm">Abbrechen</x-ui-button>
                @endif
            </div>
        </div>
    </div>

    <div>
        <div>
            <div>
                <h3>Job Informationen</h3>
                <p>Grundlegende Job-Details</p>
            </div>
            <div>
                <x-ui-table>
                    <x-ui-table-body>
                        <x-ui-table-row>
                            <x-ui-table-cell>Template</x-ui-table-cell>
                            <x-ui-table-cell>{{ $job->template }}</x-ui-table-cell>
                        </x-ui-table-row>
                        <x-ui-table-row>
                            <x-ui-table-cell>UUID</x-ui-table-cell>
                            <x-ui-table-cell>{{ $job->uuid }}</x-ui-table-cell>
                        </x-ui-table-row>
                        <x-ui-table-row>
                            <x-ui-table-cell>Erstellt</x-ui-table-cell>
                            <x-ui-table-cell>{{ $job->created_at->format('d.m.Y H:i:s') }}</x-ui-table-cell>
                        </x-ui-table-row>
                        @if($job->printed_at)
                        <x-ui-table-row>
                            <x-ui-table-cell>Gedruckt</x-ui-table-cell>
                            <x-ui-table-cell>{{ $job->printed_at->format('d.m.Y H:i:s') }}</x-ui-table-cell>
                        </x-ui-table-row>
                        @endif
                    </x-ui-table-body>
                </x-ui-table>
            </div>
        </div>

        <div>
            <div>
                <h3>Ziel-Informationen</h3>
                <p>Drucker und verknüpfte Objekte</p>
            </div>
            <div>
                <x-ui-table>
                    <x-ui-table-body>
                        <x-ui-table-row>
                            <x-ui-table-cell>Drucker</x-ui-table-cell>
                            <x-ui-table-cell>
                                @if($job->printer)
                                    {{ $job->printer->name }}
                                @elseif($job->printerGroup)
                                    Gruppe: {{ $job->printerGroup->name }}
                                @else
                                    Nicht zugewiesen
                                @endif
                            </x-ui-table-cell>
                        </x-ui-table-row>
                        <x-ui-table-row>
                            <x-ui-table-cell>Objekt-Typ</x-ui-table-cell>
                            <x-ui-table-cell>{{ $job->printable_type }}</x-ui-table-cell>
                        </x-ui-table-row>
                        <x-ui-table-row>
                            <x-ui-table-cell>Objekt-ID</x-ui-table-cell>
                            <x-ui-table-cell>{{ $job->printable_id }}</x-ui-table-cell>
                        </x-ui-table-row>
                        @if($job->user)
                        <x-ui-table-row>
                            <x-ui-table-cell>Erstellt von</x-ui-table-cell>
                            <x-ui-table-cell>{{ $job->user->name }}</x-ui-table-cell>
                        </x-ui-table-row>
                        @endif
                    </x-ui-table-body>
                </x-ui-table>
            </div>
        </div>
    </div>

    @if($job->data)
    <div>
        <div>
            <h3>Job-Daten</h3>
            <p>Die Daten, die an den Drucker gesendet werden</p>
        </div>
        <div>
            <div>
                <pre>{{ json_encode($job->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            </div>
        </div>
    </div>
    @endif

    @if($job->error_message)
    <div>
        <div>
            <div></div>
            <div>
                <h3>Fehlermeldung</h3>
                <div>
                    <p>{{ $job->error_message }}</p>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div>
        <x-ui-button wire:click="generateContent">Inhalt generieren</x-ui-button>
        <a href="{{ route('printing.jobs.index') }}">
            <x-ui-button variant="primary">Zurück zur Übersicht</x-ui-button>
        </a>
    </div>
</div>
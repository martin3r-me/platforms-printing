<div class="h-full overflow-y-auto p-6">
    <!-- Header -->
    <div class="mb-6">
        <div class="d-flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Print Job Details</h1>
                <p class="text-gray-600">Job #{{ $job->id }}</p>
            </div>
            <div class="d-flex items-center gap-2">
                <x-ui-badge
                    variant="{{ in_array($job->status, ['pending','processing']) ? 'warning' : ($job->status === 'completed' ? 'success' : ($job->status === 'failed' ? 'danger' : 'secondary')) }}"
                    size="sm"
                >
                    {{ ucfirst($job->status) }}
                </x-ui-badge>
                @if($job->status === 'failed')
                    <x-ui-button wire:click="retryJob" size="sm" variant="secondary">Wiederholen</x-ui-button>
                @endif
                @if(in_array($job->status, ['pending', 'processing']))
                    <x-ui-button variant="danger-outline" wire:click="cancelJob" size="sm">Abbrechen</x-ui-button>
                @endif
            </div>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-6">
        <!-- Job Informationen -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold">Job Informationen</h3>
                <p class="text-sm text-gray-600">Grundlegende Job-Details</p>
            </div>
            <div class="p-6">
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

        <!-- Ziel-Informationen -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold">Ziel-Informationen</h3>
                <p class="text-sm text-gray-600">Drucker und verknüpfte Objekte</p>
            </div>
            <div class="p-6">
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
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 mt-6">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold">Job-Daten</h3>
            <p class="text-sm text-gray-600">Die Daten, die an den Drucker gesendet werden</p>
        </div>
        <div class="p-6">
            <div class="bg-gray-50 rounded-md p-4 overflow-auto">
                <pre class="text-sm">{{ json_encode($job->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            </div>
        </div>
    </div>
    @endif

    @if($job->error_message)
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 mt-6">
        <div class="p-6">
            <h3 class="text-lg font-semibold mb-2">Fehlermeldung</h3>
            <p class="text-sm text-red-700">{{ $job->error_message }}</p>
        </div>
    </div>
    @endif

    <div class="d-flex justify-between items-center mt-6">
        <x-ui-button wire:click="generateContent">Inhalt generieren</x-ui-button>
        <a href="{{ route('printing.jobs.index') }}">
            <x-ui-button variant="primary">Zurück zur Übersicht</x-ui-button>
        </a>
    </div>
</div>
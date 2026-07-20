<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Printing', 'href' => route('printing.dashboard'), 'icon' => 'printer'],
            ['label' => 'Print Jobs', 'href' => route('printing.jobs.index'), 'icon' => 'document-text'],
            ['label' => 'Job #' . $job->id],
        ]">
            <x-ui-badge
                variant="{{ in_array($job->status, ['pending','processing']) ? 'warning' : ($job->status === 'completed' ? 'success' : ($job->status === 'failed' ? 'danger' : 'secondary')) }}"
                size="sm"
            >
                {{ $job->status_description }}
            </x-ui-badge>
            <x-ui-button wire:click="reloadPreview" size="sm" variant="secondary-outline">
                <div class="d-flex items-center gap-2">
                    @svg('heroicon-o-arrow-path', 'w-4 h-4')
                    Vorschau
                </div>
            </x-ui-button>
            @if($job->status === 'failed')
                <x-ui-button wire:click="retryJob" size="sm" variant="secondary">Wiederholen</x-ui-button>
            @endif
            @if(in_array($job->status, ['pending', 'processing']))
                <x-ui-button variant="danger-outline" wire:click="cancelJob" size="sm">Abbrechen</x-ui-button>
            @endif
        </x-ui-page-actionbar>
    </x-slot>

    {{-- Logs / Aktivitäten --}}
    <x-slot name="activity">
        <x-ui-page-sidebar title="Logs" icon="heroicon-o-bolt" width="w-80" :defaultOpen="false" storeKey="activityOpen" side="right">
            <div class="p-4">
                <livewire:activity-log.index
                    :model="$job"
                    :key="get_class($job) . '_' . $job->id"
                />
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-ui-page-container>
        {{-- Status-Zeitleiste --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                <div class="text-xs uppercase tracking-wide text-gray-500 mb-1">Status</div>
                <x-ui-badge
                    variant="{{ in_array($job->status, ['pending','processing']) ? 'warning' : ($job->status === 'completed' ? 'success' : ($job->status === 'failed' ? 'danger' : 'secondary')) }}"
                    size="sm"
                >
                    {{ $job->status_description }}
                </x-ui-badge>
            </div>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                <div class="text-xs uppercase tracking-wide text-gray-500 mb-1">Erstellt</div>
                <div class="text-sm font-medium text-gray-900">{{ $job->created_at->format('d.m.Y H:i') }}</div>
                <div class="text-xs text-gray-500">{{ $job->created_at->diffForHumans() }}</div>
            </div>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                <div class="text-xs uppercase tracking-wide text-gray-500 mb-1">Gedruckt am</div>
                @if($job->printed_at)
                    <div class="text-sm font-medium text-gray-900">{{ $job->printed_at->format('d.m.Y H:i') }}</div>
                    <div class="text-xs text-gray-500">{{ $job->printed_at->diffForHumans() }}</div>
                @else
                    <div class="text-sm text-gray-400">– noch nicht –</div>
                @endif
            </div>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                <div class="text-xs uppercase tracking-wide text-gray-500 mb-1">Versuche</div>
                <div class="text-sm font-medium text-gray-900">{{ $job->retry_count }}× / max. {{ config('printing.jobs.max_retries', 3) }}</div>
            </div>
        </div>

        {{-- Fehlermeldung (prominent, wenn vorhanden) --}}
        @if($job->error_message)
            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                <div class="d-flex items-center gap-2 mb-1">
                    @svg('heroicon-o-exclamation-triangle', 'w-5 h-5 text-red-600')
                    <h3 class="text-sm font-semibold text-red-900">Fehlermeldung</h3>
                </div>
                <p class="text-sm text-red-700">{{ $job->error_message }}</p>
            </div>
        @endif

        {{-- Vorschau: Was wird gedruckt --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="p-6 border-b border-gray-200 d-flex items-center justify-between">
                <div>
                    <div class="d-flex items-center gap-2">
                        @svg('heroicon-o-document-magnifying-glass', 'w-5 h-5 text-gray-700')
                        <h3 class="text-lg font-semibold text-gray-900">Vorschau</h3>
                    </div>
                    <p class="text-sm text-gray-600 mt-1">Der Inhalt, der an den Drucker gesendet wird (Template: {{ config("printing.templates.available.{$job->template}", $job->template) }})</p>
                </div>
                <x-ui-button wire:click="reloadPreview" size="sm" variant="secondary-outline">
                    <div class="d-flex items-center gap-2">
                        @svg('heroicon-o-arrow-path', 'w-4 h-4')
                        Aktualisieren
                    </div>
                </x-ui-button>
            </div>
            <div class="p-6">
                @if($previewError)
                    <div class="bg-red-50 border border-red-200 rounded-md p-4 text-sm text-red-700">
                        Vorschau konnte nicht erzeugt werden: {{ $previewError }}
                    </div>
                @elseif(trim((string) $preview) === '')
                    <div class="text-center py-8 text-gray-500">Kein Inhalt vorhanden.</div>
                @else
                    <div class="bg-gray-900 text-gray-100 rounded-md p-4 overflow-auto max-h-96">
                        <pre class="text-xs font-mono whitespace-pre-wrap break-words">{{ $preview }}</pre>
                    </div>
                @endif
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
                                <x-ui-table-cell>{{ config("printing.templates.available.{$job->template}", $job->template) }}</x-ui-table-cell>
                            </x-ui-table-row>
                            <x-ui-table-row>
                                <x-ui-table-cell>UUID</x-ui-table-cell>
                                <x-ui-table-cell><span class="font-mono text-xs">{{ $job->uuid }}</span></x-ui-table-cell>
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
                            <x-ui-table-row>
                                <x-ui-table-cell>Versuche</x-ui-table-cell>
                                <x-ui-table-cell>{{ $job->retry_count }}</x-ui-table-cell>
                            </x-ui-table-row>
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
                                        <a href="{{ route('printing.printers.show', $job->printer) }}" class="text-primary hover:underline" wire:navigate>{{ $job->printer->name }}</a>
                                    @elseif($job->printerGroup)
                                        <a href="{{ route('printing.groups.show', $job->printerGroup) }}" class="text-primary hover:underline" wire:navigate>Gruppe: {{ $job->printerGroup->name }}</a>
                                    @else
                                        Nicht zugewiesen
                                    @endif
                                </x-ui-table-cell>
                            </x-ui-table-row>
                            <x-ui-table-row>
                                <x-ui-table-cell>Objekt</x-ui-table-cell>
                                <x-ui-table-cell>{{ $job->printable_name }} #{{ $job->printable_id }}</x-ui-table-cell>
                            </x-ui-table-row>
                            <x-ui-table-row>
                                <x-ui-table-cell>Objekt-Typ</x-ui-table-cell>
                                <x-ui-table-cell><span class="font-mono text-xs">{{ $job->printable_type }}</span></x-ui-table-cell>
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

        {{-- Job-Daten (Rohdaten) --}}
        @if($job->data)
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold">Job-Daten</h3>
                <p class="text-sm text-gray-600">Die Rohdaten, aus denen die Vorschau erzeugt wird</p>
            </div>
            <div class="p-6">
                <div class="bg-gray-50 rounded-md p-4 overflow-auto max-h-96">
                    <pre class="text-sm">{{ json_encode($job->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
            </div>
        </div>
        @endif

        <div class="d-flex justify-end items-center">
            <a href="{{ route('printing.jobs.index') }}" wire:navigate>
                <x-ui-button variant="primary">Zurück zur Übersicht</x-ui-button>
            </a>
        </div>
    </x-ui-page-container>
</x-ui-page>

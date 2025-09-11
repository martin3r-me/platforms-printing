<div class="d-flex h-full">
    <!-- Linke Spalte -->
    <div class="flex-grow-1 d-flex flex-col">
        <!-- Header oben (fix) -->
        <div class="border-top-1 border-bottom-1 border-muted border-top-solid border-bottom-solid p-2 flex-shrink-0">
            <div class="d-flex gap-1">
                <div class="d-flex">
                    <a href="{{ route('printing.groups.index') }}" class="d-flex px-3 border-right-solid border-right-1 border-right-muted underline" wire:navigate>
                        Gruppen
                    </a>
                </div>
                <div class="flex-grow-1 text-right d-flex items-center justify-end gap-2">
                    <span>{{ $group->name }}</span>
                    @if($this->isDirty)
                        <x-ui-button 
                            variant="primary" 
                            size="sm"
                            wire:click="save"
                        >
                            <div class="d-flex items-center gap-2">
                                @svg('heroicon-o-check', 'w-4 h-4')
                                Speichern
                            </div>
                        </x-ui-button>
                    @endif
                </div>
            </div>
        </div>

        <!-- Haupt-Content (nimmt Restplatz, scrollt) -->
        <div class="flex-grow-1 overflow-y-auto p-4">
            
            {{-- Gruppen-Daten --}}
            <div class="mb-6">
                <h3 class="text-lg font-semibold mb-4 text-secondary">Gruppen-Daten</h3>
                <div class="grid grid-cols-2 gap-4">
                    <x-ui-input-text 
                        name="group_name"
                        label="Name"
                        wire:model.live.debounce.500ms="group_name"
                        placeholder="Gruppenname eingeben..."
                        required
                        :errorKey="'group_name'"
                    />
                    <x-ui-input-text 
                        name="group_description"
                        label="Beschreibung"
                        wire:model.live.debounce.500ms="group_description"
                        placeholder="Beschreibung eingeben..."
                        :errorKey="'group_description'"
                    />
                </div>
            </div>

            {{-- Status & Einstellungen --}}
            <div class="mb-6">
                <h3 class="text-lg font-semibold mb-4 text-secondary">Status & Einstellungen</h3>
                <div class="grid grid-cols-2 gap-4">
                    <x-ui-input-checkbox
                        model="group_is_active"
                        checked-label="Aktiv"
                        unchecked-label="Gruppe ist aktiv"
                        size="md"
                        block="true"
                    />
                </div>
            </div>

            {{-- Kennzahlen --}}
            <div class="mb-6">
                <h3 class="text-lg font-semibold mb-4 text-secondary">Statistiken</h3>
                <div class="grid grid-cols-4 gap-4">
                    <x-ui-dashboard-tile title="Gesamt Jobs" :count="$stats['total']" icon="document-text" variant="primary" size="lg" />
                    <x-ui-dashboard-tile title="Wartend" :count="$stats['pending']" icon="clock" variant="warning" size="lg" />
                    <x-ui-dashboard-tile title="Abgeschlossen" :count="$stats['completed']" icon="check-circle" variant="success" size="lg" />
                    <x-ui-dashboard-tile title="Fehlgeschlagen" :count="$stats['failed']" icon="x-circle" variant="danger" size="lg" />
                </div>
            </div>

            {{-- Print Jobs --}}
            <div class="mb-6">
                <h3 class="text-lg font-semibold mb-4 text-secondary">Print Jobs</h3>
                @if($jobs->count() > 0)
                    <x-ui-table>
                        <x-ui-table-header>
                            <x-ui-table-header-cell>Template</x-ui-table-header-cell>
                            <x-ui-table-header-cell>Status</x-ui-table-header-cell>
                            <x-ui-table-header-cell>Ziel</x-ui-table-header-cell>
                            <x-ui-table-header-cell>Erstellt</x-ui-table-header-cell>
                        </x-ui-table-header>

                        <x-ui-table-body>
                            @foreach($jobs as $job)
                                <x-ui-table-row 
                                    clickable="true" 
                                    :href="route('printing.jobs.show', ['job' => $job->id])"
                                >
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
                                </x-ui-table-row>
                            @endforeach
                        </x-ui-table-body>
                    </x-ui-table>
                    <div class="mt-4">{{ $jobs->links() }}</div>
                @else
                    <div class="text-center py-8 text-gray-600">
                        <x-heroicon-o-queue-list class="w-12 h-12 text-gray-400 mx-auto mb-3"/>
                        <div class="text-lg font-medium">Keine Jobs gefunden</div>
                        <div>Für diese Gruppe sind aktuell keine Jobs vorhanden.</div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Aktivitäten (immer unten) -->
        <div x-data="{ open: false }" class="flex-shrink-0 border-t border-muted">
            <div 
                @click="open = !open" 
                class="cursor-pointer border-top-1 border-top-solid border-top-muted border-bottom-1 border-bottom-solid border-bottom-muted p-2 text-center d-flex items-center justify-center gap-1 mx-2 shadow-lg"
            >
                AKTIVITÄTEN 
                <span class="text-xs">
                    {{$group->activities->count()}}
                </span>
                <x-heroicon-o-chevron-double-down 
                    class="w-3 h-3" 
                    x-show="!open"
                />
                <x-heroicon-o-chevron-double-up 
                    class="w-3 h-3" 
                    x-show="open"
                />
            </div>
            <div x-show="open" class="p-2 max-h-xs overflow-y-auto">
                <livewire:activity-log.index
                    :model="$group"
                    :key="get_class($group) . '_' . $group->id"
                />
            </div>
        </div>
    </div>

    <!-- Rechte Spalte -->
    <div class="min-w-80 w-80 d-flex flex-col border-left-1 border-left-solid border-left-muted">

        <div class="d-flex gap-2 border-top-1 border-bottom-1 border-muted border-top-solid border-bottom-solid p-2 flex-shrink-0">
            <x-heroicon-o-cog-6-tooth class="w-6 h-6"/>
            Einstellungen
        </div>
        <div class="flex-grow-1 overflow-y-auto p-4">

            {{-- Navigation Buttons --}}
            <div class="d-flex flex-col gap-2 mb-4">
                <x-ui-button 
                    variant="secondary-outline" 
                    size="md" 
                    :href="route('printing.groups.index')" 
                    wire:navigate
                    class="w-full"
                >
                    <div class="d-flex items-center gap-2">
                        @svg('heroicon-o-arrow-left', 'w-4 h-4')
                        Zurück zu Gruppen
                    </div>
                </x-ui-button>
            </div>

            {{-- Kurze Übersicht --}}
            <div class="mb-4 p-3 bg-muted-5 rounded-lg">
                <h4 class="font-semibold mb-2 text-secondary">Gruppen-Übersicht</h4>
                <div class="space-y-1 text-sm">
                    <div><strong>Name:</strong> {{ $group->name }}</div>
                    <div><strong>Beschreibung:</strong> {{ $group->description ?: 'Nicht angegeben' }}</div>
                    <div><strong>Status:</strong> 
                        <x-ui-badge variant="{{ $group->is_active ? 'success' : 'secondary' }}" size="xs">
                            {{ $group->is_active ? 'Aktiv' : 'Inaktiv' }}
                        </x-ui-badge>
                    </div>
                    <div><strong>Drucker:</strong> {{ $group->printers->count() }}</div>
                </div>
            </div>

            <hr>

            {{-- Drucker --}}
            <div class="mb-4">
                <h4 class="font-semibold mb-2">Drucker</h4>
                <div class="space-y-2">
                    @foreach($group->printers as $printer)
                        <div class="d-flex items-center gap-2 p-2 bg-muted-5 rounded cursor-pointer" wire:click="editPrinter({{ $printer->id }})">
                            <span class="flex-grow-1 text-sm">{{ $printer->name }}</span>
                            <x-ui-badge variant="primary" size="xs">{{ $printer->is_active ? 'Aktiv' : 'Inaktiv' }}</x-ui-badge>
                            <div class="flex-shrink-0" @click.stop>
                                <x-ui-button 
                                    size="xs" 
                                    variant="danger-outline"
                                    x-on:click.prevent="$wire.openRemovePrinterModal({{ $printer->id }})"
                                >
                                    <div class="d-flex items-center gap-1">
                                        @svg('heroicon-o-x-mark', 'w-3 h-3')
                                        Entfernen
                                    </div>
                                </x-ui-button>
                            </div>
                        </div>
                    @endforeach
                    @if($group->printers->count() === 0)
                        <p class="text-sm text-muted">Noch keine Drucker zugewiesen.</p>
                    @endif
                    <x-ui-button size="sm" variant="secondary-outline" wire:click="addPrinter">
                        <div class="d-flex items-center gap-2">
                            @svg('heroicon-o-plus', 'w-4 h-4')
                            Drucker zuweisen
                        </div>
                    </x-ui-button>
                </div>
            </div>

            <hr>

        </div>
    </div>

    <!-- Printer Assignment Modal -->
    <x-ui-modal model="printerAssignmentModalShow" size="md">
        <x-slot name="header">
            Drucker zuweisen
        </x-slot>

        <div class="space-y-4">
            <form class="space-y-4">
                <x-ui-input-select
                    name="selectedPrinterId"
                    label="Drucker auswählen"
                    :options="$availablePrinters"
                    optionValue="id"
                    optionLabel="name"
                    :nullable="true"
                    nullLabel="– Drucker auswählen –"
                    wire:model.live="selectedPrinterId"
                />
            </form>
        </div>

        <x-slot name="footer">
            <div class="d-flex justify-end gap-2">
                <x-ui-button type="button" variant="secondary-outline" @click="$wire.closePrinterAssignmentModal()">
                    Abbrechen
                </x-ui-button>
                <x-ui-button type="button" variant="primary" wire:click="assignPrinter">
                    Zuweisen
                </x-ui-button>
            </div>
        </x-slot>
    </x-ui-modal>

    <!-- Remove Printer Confirm Modal -->
    <x-ui-modal model="removePrinterModalShow" size="sm">
        <x-slot name="header">
            Drucker entfernen
        </x-slot>

        <div class="space-y-2">
            <p class="text-sm">Soll dieser Drucker wirklich entfernt werden?</p>
        </div>

        <x-slot name="footer">
            <div class="d-flex justify-end gap-2">
                <x-ui-button type="button" variant="secondary-outline" @click="$wire.closeRemovePrinterModal()">
                    Abbrechen
                </x-ui-button>
                <x-ui-confirm-button 
                    action="confirmRemovePrinter" 
                    text="Entfernen" 
                    confirmText="Jetzt entfernen?"
                    variant="danger"
                    size="sm"
                />
            </div>
        </x-slot>
    </x-ui-modal>
</div>
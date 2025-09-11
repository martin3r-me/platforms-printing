<div class="d-flex h-full">
    <!-- Linke Spalte -->
    <div class="flex-grow-1 d-flex flex-col">
        <!-- Header oben (fix) -->
        <div class="border-top-1 border-bottom-1 border-muted border-top-solid border-bottom-solid p-2 flex-shrink-0">
            <div class="d-flex gap-1">
                <div class="d-flex">
                    <a href="{{ route('printing.printers.index') }}" class="d-flex px-3 border-right-solid border-right-1 border-right-muted underline" wire:navigate>
                        Drucker
                    </a>
                </div>
                <div class="flex-grow-1 text-right d-flex items-center justify-end gap-2">
                    <span>{{ $printer->name }}</span>
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
            
            {{-- Drucker-Daten --}}
            <div class="mb-6">
                <h3 class="text-lg font-semibold mb-4 text-secondary">Drucker-Daten</h3>
                <div class="grid grid-cols-2 gap-4">
                    <x-ui-input-text 
                        name="printer_name"
                        label="Name"
                        wire:model.live.debounce.500ms="printer_name"
                        placeholder="Druckername eingeben..."
                        required
                        :errorKey="'printer_name'"
                    />
                    <x-ui-input-text 
                        name="printer_location"
                        label="Standort"
                        wire:model.live.debounce.500ms="printer_location"
                        placeholder="Standort eingeben..."
                        :errorKey="'printer_location'"
                    />
                </div>
                <div class="grid grid-cols-2 gap-4 mt-4">
                    <x-ui-input-text 
                        name="printer_username"
                        label="Benutzername"
                        wire:model.live.debounce.500ms="printer_username"
                        placeholder="Benutzername (optional)"
                        :errorKey="'printer_username'"
                    />
                    <x-ui-input-text 
                        name="printer_password"
                        label="Passwort"
                        wire:model.live.debounce.500ms="printer_password"
                        type="password"
                        placeholder="Passwort (optional)"
                        :errorKey="'printer_password'"
                    />
                </div>
            </div>

            {{-- Status & Einstellungen --}}
            <div class="mb-6">
                <h3 class="text-lg font-semibold mb-4 text-secondary">Status & Einstellungen</h3>
                <div class="grid grid-cols-2 gap-4">
                    <x-ui-input-checkbox
                        model="printer_is_active"
                        checked-label="Aktiv"
                        unchecked-label="Drucker ist aktiv"
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
                            <x-ui-table-header-cell>Objekt</x-ui-table-header-cell>
                            <x-ui-table-header-cell>Erstellt</x-ui-table-header-cell>
                        </x-ui-table-header>

                        <x-ui-table-body>
                            @foreach($jobs as $job)
                                <x-ui-table-row 
                                    clickable="true" 
                                    :href="route('printing.jobs.show', ['job' => $job->id])"
                                >
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
                                    <x-ui-table-cell>
                                        <div class="text-sm">{{ $job->created_at->diffForHumans() }}</div>
                                    </x-ui-table-cell>
                                </x-ui-table-row>
                            @endforeach
                        </x-ui-table-body>
                    </x-ui-table>
                    <div class="mt-4">{{ $jobs->links() }}</div>
                @else
                    <div class="text-center py-8 text-gray-600">
                        <x-heroicon-o-queue-list class="w-12 h-12 text-gray-400 mx-auto mb-3"/>
                        <div class="text-lg font-medium">Keine Jobs gefunden</div>
                        <div>Für diesen Drucker sind aktuell keine Jobs vorhanden.</div>
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
                    {{$printer->activities->count()}}
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
                    :model="$printer"
                    :key="get_class($printer) . '_' . $printer->id"
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
                    :href="route('printing.printers.index')" 
                    wire:navigate
                    class="w-full"
                >
                    <div class="d-flex items-center gap-2">
                        @svg('heroicon-o-arrow-left', 'w-4 h-4')
                        Zurück zu Druckern
                    </div>
                </x-ui-button>
            </div>

            {{-- Kurze Übersicht --}}
            <div class="mb-4 p-3 bg-muted-5 rounded-lg">
                <h4 class="font-semibold mb-2 text-secondary">Drucker-Übersicht</h4>
                <div class="space-y-1 text-sm">
                    <div><strong>Name:</strong> {{ $printer->name }}</div>
                    <div><strong>Standort:</strong> {{ $printer->location ?: 'Nicht angegeben' }}</div>
                    @if($printer->username)
                        <div><strong>Benutzername:</strong> {{ $printer->username }}</div>
                    @endif
                    <div><strong>Status:</strong> 
                        <x-ui-badge variant="{{ $printer->is_active ? 'success' : 'secondary' }}" size="xs">
                            {{ $printer->is_active ? 'Aktiv' : 'Inaktiv' }}
                        </x-ui-badge>
                    </div>
                </div>
            </div>

            <hr>

            {{-- Gruppen --}}
            <div class="mb-4">
                <h4 class="font-semibold mb-2">Gruppen</h4>
                <div class="space-y-2">
                    @foreach($printer->groups as $group)
                        <div class="d-flex items-center gap-2 p-2 bg-muted-5 rounded cursor-pointer" wire:click="editGroup({{ $group->id }})">
                            <span class="flex-grow-1 text-sm">{{ $group->name }}</span>
                            <x-ui-badge variant="primary" size="xs">{{ $group->is_active ? 'Aktiv' : 'Inaktiv' }}</x-ui-badge>
                            <div class="flex-shrink-0" @click.stop>
                                <x-ui-confirm-button 
                                    action="removeGroup"
                                    :params="[$group->id]"
                                    text="Entfernen" 
                                    confirmText="Aus Gruppe entfernen?" 
                                    variant="danger-outline"
                                    :icon="@svg('heroicon-o-x-mark', 'w-4 h-4')->toHtml()"
                                />
                            </div>
                        </div>
                    @endforeach
                    @if($printer->groups->count() === 0)
                        <p class="text-sm text-muted">Noch keine Gruppen zugewiesen.</p>
                    @endif
                    <x-ui-button size="sm" variant="secondary-outline" wire:click="addGroup">
                        <div class="d-flex items-center gap-2">
                            @svg('heroicon-o-plus', 'w-4 h-4')
                            Gruppe zuweisen
                        </div>
                    </x-ui-button>
                </div>
            </div>

            <hr>

        </div>
    </div>

    <!-- Group Assignment Modal -->
    <x-ui-modal model="groupAssignmentModalShow" size="md">
        <x-slot name="header">
            Gruppe zuweisen
        </x-slot>

        <div class="space-y-4">
            <form class="space-y-4">
                <x-ui-input-select
                    name="selectedGroupId"
                    label="Gruppe auswählen"
                    :options="$availableGroups"
                    optionValue="id"
                    optionLabel="name"
                    :nullable="true"
                    nullLabel="– Gruppe auswählen –"
                    wire:model.live="selectedGroupId"
                />
            </form>
        </div>

        <x-slot name="footer">
            <div class="d-flex justify-end gap-2">
                <x-ui-button type="button" variant="secondary-outline" @click="$wire.closeGroupAssignmentModal()">
                    Abbrechen
                </x-ui-button>
                <x-ui-button type="button" variant="primary" wire:click="assignGroup">
                    Zuweisen
                </x-ui-button>
            </div>
        </x-slot>
    </x-ui-modal>
</div>

<div class="h-full overflow-y-auto p-6">
    <!-- Header -->
    <div class="mb-6">
        <div class="d-flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Drucker</h1>
                <p class="text-gray-600">Verwalten Sie alle Drucker im Team</p>
            </div>
            <div class="d-flex items-center gap-2">
                <x-ui-button variant="primary" wire:click="openCreateModal">
                    <div class="d-flex items-center gap-2">
                        @svg('heroicon-o-plus', 'w-4 h-4')
                        <span>Neuer Drucker</span>
                    </div>
                </x-ui-button>
            </div>
        </div>
    </div>

    <!-- Tabelle -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        @if($printers->count() > 0)
            <x-ui-table>
                <x-ui-table-header>
                    <x-ui-table-header-cell>Name</x-ui-table-header-cell>
                    <x-ui-table-header-cell>Standort</x-ui-table-header-cell>
                    <x-ui-table-header-cell>Benutzername</x-ui-table-header-cell>
                    <x-ui-table-header-cell>Status</x-ui-table-header-cell>
                    <x-ui-table-header-cell align="right">Aktionen</x-ui-table-header-cell>
                </x-ui-table-header>

                <x-ui-table-body>
                    @foreach($printers as $printer)
                        <x-ui-table-row 
                            clickable="true" 
                            :href="route('printing.printers.show', ['printer' => $printer->id])"
                        >
                            <x-ui-table-cell>
                                {{ $printer->name }}
                            </x-ui-table-cell>
                            <x-ui-table-cell>{{ $printer->location }}</x-ui-table-cell>
                            <x-ui-table-cell>{{ $printer->username ?: '–' }}</x-ui-table-cell>
                            <x-ui-table-cell>
                                <x-ui-badge variant="{{ $printer->is_active ? 'success' : 'secondary' }}" size="sm">
                                    {{ $printer->is_active ? 'Aktiv' : 'Inaktiv' }}
                                </x-ui-badge>
                            </x-ui-table-cell>
                            <x-ui-table-cell align="right">
                                <div class="d-flex items-center gap-2 justify-end">
                                    <x-ui-button wire:click="openEditModal({{ $printer->id }})" size="sm" variant="secondary">
                                        Bearbeiten
                                    </x-ui-button>
                                    <x-ui-button wire:click="toggleActive({{ $printer->id }})" size="sm" variant="secondary">
                                        {{ $printer->is_active ? 'Deaktivieren' : 'Aktivieren' }}
                                    </x-ui-button>
                                    <x-ui-button variant="danger" wire:click="deletePrinter({{ $printer->id }})" size="sm">
                                        Löschen
                                    </x-ui-button>
                                </div>
                            </x-ui-table-cell>
                        </x-ui-table-row>
                    @endforeach
                </x-ui-table-body>
            </x-ui-table>
        @else
            <div class="text-center py-12 text-gray-600">
                <x-heroicon-o-printer class="w-12 h-12 text-gray-400 mx-auto mb-3"/>
                <div class="text-lg font-medium">Keine Drucker gefunden</div>
                <div>Erstellen Sie den ersten Drucker, um zu starten.</div>
            </div>
        @endif
    </div>

    <div class="mt-4">{{ $printers->links() }}</div>

    <!-- Create Modal -->
    <x-ui-modal wire:model="modalShow" size="lg">
        <x-slot name="header">
            Drucker anlegen
        </x-slot>

        <div class="space-y-4">
            <form class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <x-ui-input-text name="name" wire:model.live="name" label="Name" />
                    <x-ui-input-text name="location" wire:model.live="location" label="Standort" />
                    <x-ui-input-text name="username" wire:model.live="username" label="Benutzername" />
                    <x-ui-input-text name="password" wire:model.live="password" type="password" label="Passwort" />
                </div>
                <div class="grid grid-cols-1 gap-4">
                    <x-ui-input-select
                        name="group_id"
                        label="Gruppe"
                        :options="$groups"
                        optionValue="id"
                        optionLabel="name"
                        :nullable="true"
                        nullLabel="– Gruppe auswählen –"
                        wire:model.live="group_id"
                    />
                </div>
            </form>
        </div>

        <x-slot name="footer">
            <div class="d-flex justify-end gap-2">
                <x-ui-button type="button" variant="secondary-outline" @click="$wire.closeCreateModal()">
                    Abbrechen
                </x-ui-button>
                <x-ui-button type="button" variant="primary" wire:click="createPrinter">
                    Drucker anlegen
                </x-ui-button>
            </div>
        </x-slot>
    </x-ui-modal>

    <!-- Edit Modal -->
    <x-ui-modal wire:model="editModalShow" size="lg">
        <x-slot name="header">
            Drucker bearbeiten
        </x-slot>

        <div class="space-y-4">
            <form class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <x-ui-input-text name="edit_name" wire:model.live="edit_name" label="Name" />
                    <x-ui-input-text name="edit_location" wire:model.live="edit_location" label="Standort" />
                    <x-ui-input-text name="edit_username" wire:model.live="edit_username" label="Benutzername" />
                    <x-ui-input-text name="edit_password" wire:model.live="edit_password" type="password" label="Passwort" />
                </div>
            </form>
        </div>

        <x-slot name="footer">
            <div class="d-flex justify-end gap-2">
                <x-ui-button type="button" variant="secondary-outline" @click="$wire.closeEditModal()">
                    Abbrechen
                </x-ui-button>
                <x-ui-button type="button" variant="primary" wire:click="updatePrinter">
                    Speichern
                </x-ui-button>
            </div>
        </x-slot>
    </x-ui-modal>
</div>
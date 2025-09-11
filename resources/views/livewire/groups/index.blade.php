<div class="h-full overflow-y-auto p-6">
    <!-- Header -->
    <div class="mb-6">
        <div class="d-flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Drucker-Gruppen</h1>
                <p class="text-gray-600">Verwalten Sie Drucker-Gruppen</p>
            </div>
            <div class="d-flex items-center gap-2">
                <x-ui-button variant="primary" wire:click="openCreateModal">
                    <div class="d-flex items-center gap-2">
                        @svg('heroicon-o-plus', 'w-4 h-4')
                        <span>Neue Gruppe</span>
                    </div>
                </x-ui-button>
            </div>
        </div>
    </div>

    <!-- Tabelle -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        @if($groups->count() > 0)
            <x-ui-table>
                <x-ui-table-header>
                    <x-ui-table-header-cell>Name</x-ui-table-header-cell>
                    <x-ui-table-header-cell>Beschreibung</x-ui-table-header-cell>
                    <x-ui-table-header-cell>Drucker</x-ui-table-header-cell>
                    <x-ui-table-header-cell>Status</x-ui-table-header-cell>
                    <x-ui-table-header-cell align="right">Aktionen</x-ui-table-header-cell>
                </x-ui-table-header>

                <x-ui-table-body>
                    @foreach($groups as $group)
                        <x-ui-table-row 
                            clickable="true" 
                            :href="route('printing.groups.show', ['group' => $group->id])"
                        >
                            <x-ui-table-cell>
                                {{ $group->name }}
                            </x-ui-table-cell>
                            <x-ui-table-cell>{{ $group->description ?: '–' }}</x-ui-table-cell>
                            <x-ui-table-cell>{{ $group->printers->count() }}</x-ui-table-cell>
                            <x-ui-table-cell>
                                <x-ui-badge variant="{{ $group->is_active ? 'success' : 'secondary' }}" size="sm">
                                    {{ $group->is_active ? 'Aktiv' : 'Inaktiv' }}
                                </x-ui-badge>
                            </x-ui-table-cell>
                            <x-ui-table-cell align="right">
                                <div class="d-flex items-center gap-2 justify-end">
                                    <x-ui-button wire:click="openEditModal({{ $group->id }})" size="sm" variant="secondary">
                                        Bearbeiten
                                    </x-ui-button>
                                    <x-ui-button wire:click="toggleActive({{ $group->id }})" size="sm" variant="secondary">
                                        {{ $group->is_active ? 'Deaktivieren' : 'Aktivieren' }}
                                    </x-ui-button>
                                    <x-ui-button variant="danger" wire:click="deleteGroup({{ $group->id }})" size="sm">
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
                <x-heroicon-o-folder class="w-12 h-12 text-gray-400 mx-auto mb-3"/>
                <div class="text-lg font-medium">Keine Gruppen gefunden</div>
                <div>Erstellen Sie die erste Gruppe, um zu starten.</div>
            </div>
        @endif
    </div>

    <div class="mt-4">{{ $groups->links() }}</div>

    <!-- Create Modal -->
    <x-ui-modal wire:model="modalShow" size="lg">
        <x-slot name="header">
            Gruppe anlegen
        </x-slot>

        <div class="space-y-4">
            <form class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <x-ui-input-text name="name" wire:model.live="name" label="Name" />
                    <x-ui-input-text name="description" wire:model.live="description" label="Beschreibung" />
                </div>
            </form>
        </div>

        <x-slot name="footer">
            <div class="d-flex justify-end gap-2">
                <x-ui-button type="button" variant="secondary-outline" @click="$wire.closeCreateModal()">
                    Abbrechen
                </x-ui-button>
                <x-ui-button type="button" variant="primary" wire:click="createGroup">
                    Gruppe anlegen
                </x-ui-button>
            </div>
        </x-slot>
    </x-ui-modal>

    <!-- Edit Modal -->
    <x-ui-modal wire:model="editModalShow" size="lg">
        <x-slot name="header">
            Gruppe bearbeiten
        </x-slot>

        <div class="space-y-4">
            <form class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <x-ui-input-text name="edit_name" wire:model.live="edit_name" label="Name" />
                    <x-ui-input-text name="edit_description" wire:model.live="edit_description" label="Beschreibung" />
                </div>
            </form>
        </div>

        <x-slot name="footer">
            <div class="d-flex justify-end gap-2">
                <x-ui-button type="button" variant="secondary-outline" @click="$wire.closeEditModal()">
                    Abbrechen
                </x-ui-button>
                <x-ui-button type="button" variant="primary" wire:click="updateGroup">
                    Speichern
                </x-ui-button>
            </div>
        </x-slot>
    </x-ui-modal>
</div>
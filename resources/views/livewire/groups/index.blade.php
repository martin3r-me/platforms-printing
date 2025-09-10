<div>
    <div>
        <div>
            <div>
                <h1>Drucker-Gruppen</h1>
                <p>Verwalten Sie Drucker-Gruppen</p>
            </div>
            <x-ui-button wire:click="showCreateModal">Neue Gruppe</x-ui-button>
        </div>
    </div>

    <!-- Filter -->
    <div>
        <div>
            <x-ui-input-text wire:model.live.debounce.300ms="search" placeholder="Gruppen suchen..." />
        </div>
        <div>
            <x-ui-input-select wire:model.live="statusFilter">
                <option value="all">Alle Status</option>
                <option value="active">Aktiv</option>
                <option value="inactive">Inaktiv</option>
            </x-ui-input-select>
        </div>
    </div>

    <!-- Gruppen Tabelle -->
    <div>
        @if($groups->count() > 0)
            <x-ui-table>
                <x-ui-table-header>
                    <x-ui-table-header-cell>Name</x-ui-table-header-cell>
                    <x-ui-table-header-cell>Beschreibung</x-ui-table-header-cell>
                    <x-ui-table-header-cell>Drucker</x-ui-table-header-cell>
                    <x-ui-table-header-cell>Status</x-ui-table-header-cell>
                    <x-ui-table-header-cell>Aktionen</x-ui-table-header-cell>
                </x-ui-table-header>

                <x-ui-table-body>
                    @foreach($groups as $group)
                        <x-ui-table-row>
                            <x-ui-table-cell>
                                <a href="{{ route('printing.groups.show', $group) }}">
                                    {{ $group->name }}
                                </a>
                            </x-ui-table-cell>
                            <x-ui-table-cell>{{ $group->description ?: '–' }}</x-ui-table-cell>
                            <x-ui-table-cell>{{ $group->printers->count() }}</x-ui-table-cell>
                            <x-ui-table-cell>
                                <x-ui-badge variant="{{ $group->is_active ? 'success' : 'secondary' }}" size="sm">
                                    {{ $group->is_active ? 'Aktiv' : 'Inaktiv' }}
                                </x-ui-badge>
                            </x-ui-table-cell>
                            <x-ui-table-cell>
                                <x-ui-button wire:click="toggleActive({{ $group->id }})" size="sm">
                                    {{ $group->is_active ? 'Deaktivieren' : 'Aktivieren' }}
                                </x-ui-button>
                                <x-ui-button variant="danger" wire:click="deleteGroup({{ $group->id }})" size="sm">
                                    Löschen
                                </x-ui-button>
                            </x-ui-table-cell>
                        </x-ui-table-row>
                    @endforeach
                </x-ui-table-body>
            </x-ui-table>
        @else
            <div class="text-center py-8">Keine Gruppen gefunden</div>
        @endif
    </div>

    {{ $groups->links() }}

    <!-- Create Modal -->
    @if($showCreateModal)
        <x-ui-modal wire:model="showCreateModal">
            <div>
                <h3>Neue Gruppe</h3>
                <form wire:submit.prevent="createGroup">
                    <div>
                        <x-ui-input-text wire:model="name" label="Name" />
                        @error('name') <span>{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <x-ui-input-text wire:model="description" label="Beschreibung" type="textarea" />
                        @error('description') <span>{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <x-ui-button type="button" wire:click="hideCreateModal">Abbrechen</x-ui-button>
                        <x-ui-button type="submit" variant="primary">Erstellen</x-ui-button>
                    </div>
                </form>
            </div>
        </x-ui-modal>
    @endif
</div>
<div class="h-full overflow-y-auto p-6">
    <!-- Header -->
    <div class="mb-6">
        <div class="d-flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Drucker</h1>
                <p class="text-gray-600">Verwalten Sie alle Drucker im Team</p>
            </div>
            <div class="d-flex items-center gap-2">
                <x-ui-button variant="primary" wire:click="showCreateModal">
                    <div class="d-flex items-center gap-2">
                        @svg('heroicon-o-plus', 'w-4 h-4')
                        <span>Neuer Drucker</span>
                    </div>
                </x-ui-button>
            </div>
        </div>
    </div>

    <!-- Filterleiste -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
        <div class="grid grid-cols-3 gap-4">
            <div class="col-span-2">
                <x-ui-input-text name="search" wire:model.live.debounce.300ms="search" placeholder="Drucker suchen..." />
            </div>
            <div>
                <x-ui-input-select name="statusFilter" wire:model.live="statusFilter" label="Status">
                    <option value="all">Alle Status</option>
                    <option value="active">Aktiv</option>
                    <option value="inactive">Inaktiv</option>
                </x-ui-input-select>
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
                        <x-ui-table-row>
                            <x-ui-table-cell>
                                <a href="{{ route('printing.printers.show', $printer) }}" class="text-primary hover:underline">
                                    {{ $printer->name }}
                                </a>
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
    @if($showCreateModal)
        <x-ui-modal wire:model="showCreateModal">
            <div class="space-y-4">
                <h3 class="text-lg font-semibold">Neuer Drucker</h3>
                <form wire:submit.prevent="createPrinter" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <x-ui-input-text name="name" wire:model="name" label="Name" />
                        <x-ui-input-text name="location" wire:model="location" label="Standort" />
                        <x-ui-input-text name="username" wire:model="username" label="Benutzername" />
                        <x-ui-input-text name="password" wire:model="password" type="password" label="Passwort" />
                    </div>
                    <div class="d-flex justify-end gap-2">
                        <x-ui-button type="button" variant="secondary" wire:click="hideCreateModal">Abbrechen</x-ui-button>
                        <x-ui-button type="submit" variant="primary">Erstellen</x-ui-button>
                    </div>
                </form>
            </div>
        </x-ui-modal>
    @endif
</div>
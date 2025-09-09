<div>
    <div>
        <div>
            <div>
                <h1>Drucker</h1>
                <p>Verwalten Sie alle Drucker</p>
            </div>
            <x-ui-button wire:click="showCreateModal">Neuer Drucker</x-ui-button>
        </div>
    </div>

    <!-- Filter -->
    <div>
        <div>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Drucker suchen...">
        </div>
        <div>
            <select wire:model.live="statusFilter">
                <option value="all">Alle Status</option>
                <option value="active">Aktiv</option>
                <option value="inactive">Inaktiv</option>
            </select>
        </div>
    </div>

    <!-- Drucker Tabelle -->
    <div>
        <ul>
            @forelse($printers as $printer)
                <li>
                    <div>
                        <div>
                            <div>
                                
                            </div>
                            <div>
                                <div>
                                    <a href="{{ route('printing.printers.show', $printer) }}">
                                        {{ $printer->name }}
                                    </a>
                                </div>
                                <div>
                                    {{ $printer->location }}
                                    @if($printer->username)
                                        • {{ $printer->username }}
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div>
                            <span>
                                {{ $printer->is_active ? 'Aktiv' : 'Inaktiv' }}
                            </span>
                            <x-ui-button wire:click="toggleActive({{ $printer->id }})">
                                {{ $printer->is_active ? 'Deaktivieren' : 'Aktivieren' }}
                            </x-ui-button>
                            <x-ui-button variant="danger" wire:click="deletePrinter({{ $printer->id }})">
                                Löschen
                            </x-ui-button>
                        </div>
                    </div>
                </li>
            @empty
                <li>
                    Keine Drucker gefunden
                </li>
            @endforelse
        </ul>
    </div>

    {{ $printers->links() }}

    <!-- Create Modal -->
    @if($showCreateModal)
        <div>
            <div>
                <div>
                    <h3>Neuer Drucker</h3>
                    <form wire:submit.prevent="createPrinter">
                        <div>
                            <label>Name</label>
                            <input wire:model="name" type="text">
                            @error('name') <span>{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label>Standort</label>
                            <input wire:model="location" type="text">
                            @error('location') <span>{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label>Benutzername</label>
                            <input wire:model="username" type="text">
                            @error('username') <span>{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label>Passwort</label>
                            <input wire:model="password" type="password">
                            @error('password') <span>{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <x-ui-button type="button" wire:click="hideCreateModal">Abbrechen</x-ui-button>
                            <x-ui-button type="submit" variant="primary">Erstellen</x-ui-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Printing" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Printing', 'href' => route('printing.dashboard'), 'icon' => 'printer'],
            ['label' => 'Drucker', 'icon' => 'printer'],
        ]">
            <x-ui-button variant="primary" size="sm" wire:click="openCreateModal">
                <div class="flex items-center gap-2">
                    @svg('heroicon-o-plus', 'w-4 h-4')
                    <span>Neuer Drucker</span>
                </div>
            </x-ui-button>
        </x-ui-page-actionbar>
    </x-slot>

    {{-- Filter --}}
    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Filter" icon="heroicon-o-funnel" width="w-72" :defaultOpen="true">
            <div class="p-4 space-y-6">
                <section>
                    <h3 class="text-[10px] font-semibold uppercase tracking-wider text-[var(--ui-muted)] mb-2">Suche</h3>
                    <div class="relative">
                        @svg('heroicon-o-magnifying-glass', 'w-4 h-4 text-[var(--ui-muted)] absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none')
                        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Name, Standort, Benutzer…"
                            class="w-full pl-9 pr-3 py-2 text-sm rounded-lg border border-[var(--ui-border)] bg-[var(--ui-surface)] text-[var(--ui-secondary)] placeholder-[var(--ui-muted)] focus:outline-none focus:ring-2 focus:ring-[var(--ui-primary)]/20 focus:border-[var(--ui-primary)]" />
                    </div>
                </section>

                <section>
                    <h3 class="text-[10px] font-semibold uppercase tracking-wider text-[var(--ui-muted)] mb-2">Status</h3>
                    <div class="space-y-1">
                        @foreach(['all' => 'Alle', 'active' => 'Aktiv', 'inactive' => 'Inaktiv'] as $val => $label)
                            <button type="button" wire:click="$set('statusFilter', '{{ $val }}')"
                                class="w-full flex items-center px-3 py-2 rounded-lg text-sm transition-colors {{ $statusFilter === $val ? 'bg-[var(--ui-primary)] text-[var(--ui-on-primary)] font-medium' : 'text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)]' }}">
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                </section>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-ui-page-container>
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
                                <span class="font-medium text-[var(--ui-secondary)]">{{ $printer->name }}</span>
                            </x-ui-table-cell>
                            <x-ui-table-cell>{{ $printer->location ?: '–' }}</x-ui-table-cell>
                            <x-ui-table-cell>{{ $printer->username ?: '–' }}</x-ui-table-cell>
                            <x-ui-table-cell>
                                <x-ui-badge variant="{{ $printer->is_active ? 'success' : 'secondary' }}" size="sm">
                                    {{ $printer->is_active ? 'Aktiv' : 'Inaktiv' }}
                                </x-ui-badge>
                            </x-ui-table-cell>
                            <x-ui-table-cell align="right">
                                <div class="flex items-center gap-2 justify-end">
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

            <div>{{ $printers->links() }}</div>
        @else
            <div class="rounded-xl bg-[var(--ui-surface)] border border-[var(--ui-border)] shadow-sm p-12 text-center">
                @svg('heroicon-o-printer', 'w-10 h-10 mx-auto text-[var(--ui-muted)] opacity-40 mb-3')
                <div class="text-base font-medium text-[var(--ui-secondary)]">Keine Drucker gefunden</div>
                <div class="text-sm text-[var(--ui-muted)] mt-1">Erstellen Sie den ersten Drucker, um zu starten.</div>
            </div>
        @endif

        {{-- Create Modal --}}
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
                <div class="flex justify-end gap-2">
                    <x-ui-button type="button" variant="secondary-outline" @click="$wire.closeCreateModal()">
                        Abbrechen
                    </x-ui-button>
                    <x-ui-button type="button" variant="primary" wire:click="createPrinter">
                        Drucker anlegen
                    </x-ui-button>
                </div>
            </x-slot>
        </x-ui-modal>

        {{-- Edit Modal --}}
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
                <div class="flex justify-end gap-2">
                    <x-ui-button type="button" variant="secondary-outline" @click="$wire.closeEditModal()">
                        Abbrechen
                    </x-ui-button>
                    <x-ui-button type="button" variant="primary" wire:click="updatePrinter">
                        Speichern
                    </x-ui-button>
                </div>
            </x-slot>
        </x-ui-modal>
    </x-ui-page-container>
</x-ui-page>

<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Drucker" icon="heroicon-o-printer" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Printing', 'href' => route('printing.dashboard'), 'icon' => 'printer'],
            ['label' => 'Drucker'],
        ]">
            <x-nx-button variant="primary" wire:click="openCreateModal">
                @svg('heroicon-o-plus', 'w-4 h-4')
                <span>Neuer Drucker</span>
            </x-nx-button>
        </x-ui-page-actionbar>
    </x-slot>

    {{-- Aktivitäten --}}
    <x-slot name="activity">
        <x-ui-page-sidebar title="Aktivitäten" icon="heroicon-o-bolt" width="w-80" :defaultOpen="false" storeKey="activityOpen" side="right">
            <div class="p-4">
                <livewire:printing.activity-feed wire:key="printing-activity-feed" />
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-ui-page-container>
    <div class="space-y-5">

    {{-- Filter: rahmenlos, luftig --}}
    <div class="flex flex-wrap items-center gap-x-5 gap-y-2 text-xs">
        <div class="flex flex-wrap items-center gap-1">
            @foreach (['all' => 'Alle', 'active' => 'Aktiv', 'inactive' => 'Inaktiv'] as $val => $label)
                <button type="button" wire:click="$set('statusFilter', '{{ $val }}')"
                    class="rounded-full px-2.5 py-1 transition-colors {{ $statusFilter === $val ? 'bg-[color:var(--nx-active)] font-medium text-[color:var(--nx-text)]' : 'text-[color:var(--nx-muted)] hover:bg-[color:var(--nx-hover)]' }}">{{ $label }}</button>
            @endforeach
        </div>
        <div class="ml-auto w-64">
            <x-ui-input-text name="search" size="sm" wire:model.live.debounce.300ms="search" placeholder="Name, Standort, Benutzer…" />
        </div>
    </div>

    {{-- Tabelle: rahmenlos, Hairlines --}}
    <x-nx-table>
        <x-nx-table-header>
            <x-nx-table-header-cell>Name</x-nx-table-header-cell>
            <x-nx-table-header-cell>Standort</x-nx-table-header-cell>
            <x-nx-table-header-cell>Benutzername</x-nx-table-header-cell>
            <x-nx-table-header-cell align="center">Gruppen</x-nx-table-header-cell>
            <x-nx-table-header-cell>Status</x-nx-table-header-cell>
            <x-nx-table-header-cell align="right"><span class="sr-only">Aktionen</span></x-nx-table-header-cell>
        </x-nx-table-header>
        <x-nx-table-body>
            @forelse($printers as $printer)
                <x-nx-table-row wire:key="printer-{{ $printer->id }}" clickable
                    :href="route('printing.printers.show', ['printer' => $printer->id])" class="group">
                    <x-nx-table-cell>
                        <span class="font-medium text-[color:var(--nx-text)]">{{ $printer->name }}</span>
                        @if($printer->mac_address)
                            <span class="block font-mono text-xs text-[color:var(--nx-faint)]">{{ $printer->mac_address }}</span>
                        @endif
                    </x-nx-table-cell>
                    <x-nx-table-cell class="text-[color:var(--nx-muted)]">{{ $printer->location ?: '–' }}</x-nx-table-cell>
                    <x-nx-table-cell class="text-[color:var(--nx-muted)]">{{ $printer->username ?: '–' }}</x-nx-table-cell>
                    <x-nx-table-cell align="center" class="tabular-nums text-[color:var(--nx-muted)]">{{ $printer->groups->count() }}</x-nx-table-cell>
                    <x-nx-table-cell>
                        <x-nx-badge :variant="$printer->is_active ? 'success' : 'neutral'">
                            {{ $printer->is_active ? 'Aktiv' : 'Inaktiv' }}
                        </x-nx-badge>
                    </x-nx-table-cell>
                    {{-- Aktionen erscheinen beim Hover über die Zeile (Notion-Stil).
                         .stop ist zwingend: die Zeile trägt ein
                         onclick="window.location.href=…", ohne das blubbert jeder
                         Klick hoch und die Seite navigiert weg – Deaktivieren und
                         Löschen landeten so ungewollt auf der Detailseite. --}}
                    <x-nx-table-cell align="right">
                        <div class="flex items-center justify-end gap-0.5 opacity-0 transition-opacity duration-150 group-hover:opacity-100 focus-within:opacity-100">
                            {{-- Bewusst ein Link auf die Detailseite: dort lässt sich
                                 alles einstellen (Zeichensatz, Gruppen, API). Das
                                 frühere Bearbeiten-Modal konnte nur einen Teil davon
                                 und ist weg. --}}
                            <x-nx-button icon variant="ghost" title="Bearbeiten"
                                :href="route('printing.printers.show', ['printer' => $printer->id])"
                                x-on:click.stop>
                                @svg('heroicon-o-pencil-square', 'w-4 h-4')
                            </x-nx-button>
                            <x-nx-button icon variant="ghost"
                                :title="$printer->is_active ? 'Deaktivieren' : 'Aktivieren'"
                                x-on:click.stop.prevent="$wire.toggleActive({{ $printer->id }})">
                                @svg($printer->is_active ? 'heroicon-o-pause-circle' : 'heroicon-o-play-circle', 'w-4 h-4')
                            </x-nx-button>
                            <button type="button" title="Löschen"
                                x-on:click.stop.prevent="$wire.openDeleteModal({{ $printer->id }})"
                                class="inline-flex h-8 w-8 items-center justify-center rounded-[6px] text-[color:var(--nx-danger)] transition-colors hover:bg-[rgba(224,49,49,.08)]">
                                @svg('heroicon-o-trash', 'w-4 h-4')
                            </button>
                        </div>
                    </x-nx-table-cell>
                </x-nx-table-row>
            @empty
                <tr>
                    <td colspan="6">
                        <x-nx-empty icon="heroicon-o-printer">
                            Keine Drucker gefunden
                            <x-slot name="action">
                                <x-nx-button wire:click="openCreateModal">
                                    @svg('heroicon-o-plus', 'w-4 h-4')
                                    <span>Neuer Drucker</span>
                                </x-nx-button>
                            </x-slot>
                        </x-nx-empty>
                    </td>
                </tr>
            @endforelse
        </x-nx-table-body>
    </x-nx-table>

    <div class="flex flex-wrap items-center justify-between gap-3 text-xs text-[color:var(--nx-faint)]">
        <span class="tabular-nums">
            @if ($printers->hasPages())
                {{ $printers->firstItem() }}–{{ $printers->lastItem() }} von {{ $printers->total() }} Druckern
            @else
                {{ $printers->total() }} {{ $printers->total() === 1 ? 'Drucker' : 'Drucker' }}
            @endif
        </span>
        {{ $printers->links('printing::partials.pagination') }}
    </div>

    {{-- Anlegen --}}
    <x-nx-modal wire:model="modalShow" size="lg">
        <x-slot name="header">
            <h2 class="m-0 text-sm font-semibold text-[color:var(--nx-text)]">Drucker anlegen</h2>
        </x-slot>

        <form class="space-y-4">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-ui-input-text name="name" wire:model.live="name" label="Name" />
                <x-ui-input-text name="location" wire:model.live="location" label="Standort" />
                <x-ui-input-text name="username" wire:model.live="username" label="Benutzername" />
                <x-ui-input-text name="password" wire:model.live="password" type="password" label="Passwort" />
            </div>
            <div>
                <x-ui-input-text name="mac_address" wire:model.live="mac_address" label="MAC-Adresse" placeholder="z. B. 00:11:62:AA:BB:CC" />
                <p class="mt-1 text-xs text-[color:var(--nx-muted)]">Zuordnung des Druckers beim CloudPRNT-Polling (Header <code>x-star-mac</code>). Genau so eintragen, wie der Drucker sie sendet.</p>
            </div>
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
        </form>

        <x-slot name="footer">
            <x-nx-button type="button" @click="$wire.closeCreateModal()">Abbrechen</x-nx-button>
            <x-nx-button type="button" variant="primary" wire:click="createPrinter">Drucker anlegen</x-nx-button>
        </x-slot>
    </x-nx-modal>

    {{-- Löschen bestätigen: der Knopf löschte bisher ohne Rückfrage --}}
    <x-nx-modal wire:model="deleteModalShow" size="sm">
        <x-slot name="header">
            <h2 class="m-0 text-sm font-semibold text-[color:var(--nx-text)]">Drucker löschen</h2>
        </x-slot>

        <p class="m-0 text-sm text-[color:var(--nx-text)]">
            @if($this->printerToDelete)
                Soll der Drucker <strong>{{ $this->printerToDelete->name }}</strong> wirklich gelöscht
                werden? Das lässt sich nicht rückgängig machen.
            @endif
        </p>

        <x-slot name="footer">
            <x-nx-button type="button" @click="$wire.closeDeleteModal()">Abbrechen</x-nx-button>
            <x-nx-button type="button" variant="danger" wire:click="confirmDeletePrinter">Löschen</x-nx-button>
        </x-slot>
    </x-nx-modal>

    </div>
    </x-ui-page-container>
</x-ui-page>

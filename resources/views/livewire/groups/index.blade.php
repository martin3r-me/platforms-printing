<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Gruppen" icon="heroicon-o-folder" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Printing', 'href' => route('printing.dashboard'), 'icon' => 'printer'],
            ['label' => 'Gruppen'],
        ]">
            <x-nx-button variant="primary" wire:click="openCreateModal">
                @svg('heroicon-o-plus', 'w-4 h-4')
                <span>Neue Gruppe</span>
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
            <x-ui-input-text name="search" size="sm" wire:model.live.debounce.300ms="search" placeholder="Name, Beschreibung…" />
        </div>
    </div>

    {{-- Tabelle: rahmenlos, Hairlines --}}
    <x-nx-table>
        <x-nx-table-header>
            <x-nx-table-header-cell>Name</x-nx-table-header-cell>
            <x-nx-table-header-cell>Beschreibung</x-nx-table-header-cell>
            <x-nx-table-header-cell align="center">Drucker</x-nx-table-header-cell>
            <x-nx-table-header-cell>Status</x-nx-table-header-cell>
            <x-nx-table-header-cell align="right"><span class="sr-only">Aktionen</span></x-nx-table-header-cell>
        </x-nx-table-header>
        <x-nx-table-body>
            @forelse($groups as $group)
                <x-nx-table-row wire:key="group-{{ $group->id }}" clickable
                    :href="route('printing.groups.show', ['group' => $group->id])" class="group">
                    <x-nx-table-cell>
                        <span class="font-medium text-[color:var(--nx-text)]">{{ $group->name }}</span>
                    </x-nx-table-cell>
                    <x-nx-table-cell class="text-[color:var(--nx-muted)]">{{ $group->description ?: '–' }}</x-nx-table-cell>
                    <x-nx-table-cell align="center" class="tabular-nums text-[color:var(--nx-muted)]">{{ $group->printers->count() }}</x-nx-table-cell>
                    <x-nx-table-cell>
                        <x-nx-badge :variant="$group->is_active ? 'success' : 'neutral'">
                            {{ $group->is_active ? 'Aktiv' : 'Inaktiv' }}
                        </x-nx-badge>
                    </x-nx-table-cell>
                    {{-- Aktionen erscheinen beim Hover über die Zeile (Notion-Stil).
                         .stop ist zwingend: die Zeile trägt ein
                         onclick="window.location.href=…", ohne das blubbert jeder
                         Klick hoch und die Seite navigiert weg – Deaktivieren und
                         Löschen landeten so ungewollt auf der Detailseite. --}}
                    <x-nx-table-cell align="right">
                        <div class="flex items-center justify-end gap-0.5 opacity-0 transition-opacity duration-150 group-hover:opacity-100 focus-within:opacity-100">
                            {{-- Bewusst ein Link auf die Detailseite. Das frühere
                                 Bearbeiten-Modal war toter Code: sein Speichern rief
                                 updateGroup() auf, das es im Component nie gab. --}}
                            <x-nx-button icon variant="ghost" title="Bearbeiten"
                                :href="route('printing.groups.show', ['group' => $group->id])"
                                x-on:click.stop>
                                @svg('heroicon-o-pencil-square', 'w-4 h-4')
                            </x-nx-button>
                            <x-nx-button icon variant="ghost"
                                :title="$group->is_active ? 'Deaktivieren' : 'Aktivieren'"
                                x-on:click.stop.prevent="$wire.toggleActive({{ $group->id }})">
                                @svg($group->is_active ? 'heroicon-o-pause-circle' : 'heroicon-o-play-circle', 'w-4 h-4')
                            </x-nx-button>
                            <button type="button" title="Löschen"
                                x-on:click.stop.prevent="$wire.openDeleteModal({{ $group->id }})"
                                class="inline-flex h-8 w-8 items-center justify-center rounded-[6px] text-[color:var(--nx-danger)] transition-colors hover:bg-[rgba(224,49,49,.08)]">
                                @svg('heroicon-o-trash', 'w-4 h-4')
                            </button>
                        </div>
                    </x-nx-table-cell>
                </x-nx-table-row>
            @empty
                <tr>
                    <td colspan="5">
                        <x-nx-empty icon="heroicon-o-folder">
                            Keine Gruppen gefunden
                            <x-slot name="action">
                                <x-nx-button wire:click="openCreateModal">
                                    @svg('heroicon-o-plus', 'w-4 h-4')
                                    <span>Neue Gruppe</span>
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
            @if ($groups->hasPages())
                {{ $groups->firstItem() }}–{{ $groups->lastItem() }} von {{ $groups->total() }} Gruppen
            @else
                {{ $groups->total() }} {{ $groups->total() === 1 ? 'Gruppe' : 'Gruppen' }}
            @endif
        </span>
        {{ $groups->links('printing::partials.pagination') }}
    </div>

    {{-- Anlegen --}}
    <x-nx-modal wire:model="modalShow" size="lg">
        <x-slot name="header">
            <h2 class="m-0 text-sm font-semibold text-[color:var(--nx-text)]">Gruppe anlegen</h2>
        </x-slot>

        <form class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <x-ui-input-text name="name" wire:model.live="name" label="Name" />
            <x-ui-input-text name="description" wire:model.live="description" label="Beschreibung" />
        </form>

        <x-slot name="footer">
            <x-nx-button type="button" @click="$wire.closeCreateModal()">Abbrechen</x-nx-button>
            <x-nx-button type="button" variant="primary" wire:click="createGroup">Gruppe anlegen</x-nx-button>
        </x-slot>
    </x-nx-modal>

    {{-- Löschen bestätigen: der Knopf löschte bisher ohne Rückfrage --}}
    <x-nx-modal wire:model="deleteModalShow" size="sm">
        <x-slot name="header">
            <h2 class="m-0 text-sm font-semibold text-[color:var(--nx-text)]">Gruppe löschen</h2>
        </x-slot>

        <p class="m-0 text-sm text-[color:var(--nx-text)]">
            @if($this->groupToDelete)
                Soll die Gruppe <strong>{{ $this->groupToDelete->name }}</strong> wirklich gelöscht
                werden? Das lässt sich nicht rückgängig machen.
            @endif
        </p>

        <x-slot name="footer">
            <x-nx-button type="button" @click="$wire.closeDeleteModal()">Abbrechen</x-nx-button>
            <x-nx-button type="button" variant="danger" wire:click="confirmDeleteGroup">Löschen</x-nx-button>
        </x-slot>
    </x-nx-modal>

    </div>
    </x-ui-page-container>
</x-ui-page>

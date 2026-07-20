<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Printing" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Printing', 'href' => route('printing.dashboard'), 'icon' => 'printer'],
            ['label' => 'Gruppen', 'href' => route('printing.groups.index'), 'icon' => 'folder'],
            ['label' => $group->name],
        ]">
            @if($this->isDirty)
                <x-ui-button variant="primary" size="sm" wire:click="save">
                    <div class="flex items-center gap-2">
                        @svg('heroicon-o-check', 'w-4 h-4')
                        Speichern
                    </div>
                </x-ui-button>
            @endif
        </x-ui-page-actionbar>
    </x-slot>

    {{-- Rechte Spalte: Einstellungen --}}
    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Einstellungen" icon="heroicon-o-cog-6-tooth" width="w-80" :defaultOpen="true">
            <div class="p-4 space-y-6">
                {{-- Übersicht --}}
                <section>
                    <h3 class="text-[10px] font-semibold uppercase tracking-wider text-[var(--ui-muted)] mb-2">Übersicht</h3>
                    <dl class="rounded-lg border border-[var(--ui-border)] divide-y divide-[var(--ui-border)] overflow-hidden">
                        <div class="flex items-center justify-between gap-3 px-3 py-2">
                            <dt class="text-xs text-[var(--ui-muted)]">Name</dt>
                            <dd class="text-sm text-[var(--ui-secondary)] m-0 truncate">{{ $group->name }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3 px-3 py-2">
                            <dt class="text-xs text-[var(--ui-muted)]">Beschreibung</dt>
                            <dd class="text-sm text-[var(--ui-secondary)] m-0 truncate">{{ $group->description ?: '–' }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3 px-3 py-2">
                            <dt class="text-xs text-[var(--ui-muted)]">Drucker</dt>
                            <dd class="text-sm text-[var(--ui-secondary)] m-0">{{ $group->printers->count() }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3 px-3 py-2">
                            <dt class="text-xs text-[var(--ui-muted)]">Status</dt>
                            <dd class="m-0">
                                <x-ui-badge variant="{{ $group->is_active ? 'success' : 'secondary' }}" size="xs">
                                    {{ $group->is_active ? 'Aktiv' : 'Inaktiv' }}
                                </x-ui-badge>
                            </dd>
                        </div>
                    </dl>
                </section>

                {{-- Drucker --}}
                <section>
                    <h3 class="text-[10px] font-semibold uppercase tracking-wider text-[var(--ui-muted)] mb-2">Drucker</h3>
                    <div class="space-y-2">
                        @forelse($group->printers as $printer)
                            <div class="flex items-center gap-2 px-3 py-2 rounded-lg border border-[var(--ui-border)] hover:bg-[var(--ui-muted-5)] transition-colors cursor-pointer" wire:click="editPrinter({{ $printer->id }})">
                                <span class="flex-1 min-w-0 truncate text-sm text-[var(--ui-secondary)]">{{ $printer->name }}</span>
                                <x-ui-badge variant="{{ $printer->is_active ? 'success' : 'secondary' }}" size="xs">{{ $printer->is_active ? 'Aktiv' : 'Inaktiv' }}</x-ui-badge>
                                <button type="button" class="shrink-0 text-[var(--ui-muted)] hover:text-[var(--ui-danger)] transition-colors" x-on:click.stop.prevent="$wire.openRemovePrinterModal({{ $printer->id }})" title="Entfernen">
                                    @svg('heroicon-o-x-mark', 'w-4 h-4')
                                </button>
                            </div>
                        @empty
                            <p class="text-sm text-[var(--ui-muted)]">Noch keine Drucker zugewiesen.</p>
                        @endforelse
                        <x-ui-button size="sm" variant="secondary-outline" wire:click="addPrinter" class="w-full">
                            <div class="flex items-center justify-center gap-2">
                                @svg('heroicon-o-plus', 'w-4 h-4')
                                Drucker zuweisen
                            </div>
                        </x-ui-button>
                    </div>
                </section>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    {{-- Aktivitäten --}}
    <x-slot name="activity">
        <x-ui-page-sidebar title="Aktivitäten" icon="heroicon-o-bolt" width="w-80" :defaultOpen="false" storeKey="activityOpen" side="right">
            <div class="p-4">
                <livewire:activity-log.index
                    :model="$group"
                    :key="get_class($group) . '_' . $group->id"
                />
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-ui-page-container>
        {{-- Gruppen-Daten --}}
        <x-ui-panel title="Gruppen-Daten">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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

            <div class="mt-4 pt-4 border-t border-[var(--ui-border)]">
                <x-ui-input-checkbox
                    model="group_is_active"
                    checked-label="Aktiv"
                    unchecked-label="Gruppe ist aktiv"
                    size="md"
                    block="true"
                />
            </div>
        </x-ui-panel>

        {{-- Statistiken --}}
        <x-ui-panel title="Statistiken">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <x-ui-dashboard-tile title="Gesamt Jobs" :count="$stats['total']" icon="document-text" variant="primary" size="sm" />
                <x-ui-dashboard-tile title="Wartend" :count="$stats['pending']" icon="clock" variant="warning" size="sm" />
                <x-ui-dashboard-tile title="Abgeschlossen" :count="$stats['completed']" icon="check-circle" variant="success" size="sm" />
                <x-ui-dashboard-tile title="Fehlgeschlagen" :count="$stats['failed']" icon="x-circle" variant="danger" size="sm" />
            </div>
        </x-ui-panel>

        {{-- Print Jobs --}}
        <div class="space-y-3">
            <h3 class="text-base font-semibold text-[var(--ui-secondary)] m-0">Print Jobs</h3>
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
                                <x-ui-table-cell>
                                    <span class="font-medium text-[var(--ui-secondary)]">{{ $job->template }}</span>
                                </x-ui-table-cell>
                                <x-ui-table-cell>
                                    <x-ui-badge
                                        variant="{{ $job->status_color }}"
                                        size="sm"
                                    >
                                        {{ $job->status_description }}
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
                <div>{{ $jobs->links() }}</div>
            @else
                <div class="rounded-xl bg-[var(--ui-surface)] border border-[var(--ui-border)] shadow-sm p-12 text-center">
                    @svg('heroicon-o-queue-list', 'w-10 h-10 mx-auto text-[var(--ui-muted)] opacity-40 mb-3')
                    <div class="text-base font-medium text-[var(--ui-secondary)]">Keine Jobs gefunden</div>
                    <div class="text-sm text-[var(--ui-muted)] mt-1">Für diese Gruppe sind aktuell keine Jobs vorhanden.</div>
                </div>
            @endif
        </div>

        {{-- Printer Assignment Modal --}}
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
                <div class="flex justify-end gap-2">
                    <x-ui-button type="button" variant="secondary-outline" @click="$wire.closePrinterAssignmentModal()">
                        Abbrechen
                    </x-ui-button>
                    <x-ui-button type="button" variant="primary" wire:click="assignPrinter">
                        Zuweisen
                    </x-ui-button>
                </div>
            </x-slot>
        </x-ui-modal>

        {{-- Remove Printer Confirm Modal --}}
        <x-ui-modal model="removePrinterModalShow" size="sm">
            <x-slot name="header">
                Drucker entfernen
            </x-slot>

            <div class="space-y-2">
                <p class="text-sm text-[var(--ui-secondary)]">Soll dieser Drucker wirklich entfernt werden?</p>
            </div>

            <x-slot name="footer">
                <div class="flex justify-end gap-2">
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
    </x-ui-page-container>
</x-ui-page>

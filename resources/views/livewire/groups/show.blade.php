<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Gruppe" icon="heroicon-o-folder" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Printing', 'href' => route('printing.dashboard'), 'icon' => 'printer'],
            ['label' => 'Gruppen', 'href' => route('printing.groups.index'), 'icon' => 'folder'],
            ['label' => $group->name],
        ]">
            @if($this->isDirty)
                <x-nx-button variant="primary" wire:click="save">
                    @svg('heroicon-o-check', 'w-4 h-4')
                    <span>Speichern</span>
                </x-nx-button>
            @endif
        </x-ui-page-actionbar>
    </x-slot>

    {{-- Linke Spalte: Überblick und Drucker --}}
    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Einstellungen" icon="heroicon-o-cog-6-tooth" width="w-80" :defaultOpen="true">
            <div class="space-y-6 p-4">
                {{-- Übersicht --}}
                <section>
                    <h3 class="mb-2 text-[10px] font-semibold uppercase tracking-wider text-[color:var(--nx-faint)]">Übersicht</h3>
                    <div class="rounded-[8px] border border-[color:var(--nx-line)] divide-y divide-[color:var(--nx-line)]">
                        <div class="flex items-center justify-between gap-3 px-3 py-2">
                            <span class="text-xs text-[color:var(--nx-muted)]">Name</span>
                            <span class="truncate text-sm text-[color:var(--nx-text)]">{{ $group->name }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-3 px-3 py-2">
                            <span class="text-xs text-[color:var(--nx-muted)]">Beschreibung</span>
                            <span class="truncate text-sm text-[color:var(--nx-text)]">{{ $group->description ?: '–' }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-3 px-3 py-2">
                            <span class="text-xs text-[color:var(--nx-muted)]">Drucker</span>
                            <span class="text-sm tabular-nums text-[color:var(--nx-text)]">{{ $group->printers->count() }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-3 px-3 py-2">
                            <span class="text-xs text-[color:var(--nx-muted)]">Status</span>
                            <x-nx-badge :variant="$group->is_active ? 'success' : 'neutral'">{{ $group->is_active ? 'Aktiv' : 'Inaktiv' }}</x-nx-badge>
                        </div>
                    </div>
                </section>

                {{-- Drucker --}}
                <section>
                    <h3 class="mb-2 text-[10px] font-semibold uppercase tracking-wider text-[color:var(--nx-faint)]">Drucker</h3>
                    <div class="space-y-2">
                        @forelse($group->printers as $printer)
                            <div class="flex cursor-pointer items-center gap-2 rounded-[6px] border border-[color:var(--nx-line)] px-3 py-2 transition-colors hover:bg-[color:var(--nx-hover)]" wire:click="editPrinter({{ $printer->id }})">
                                <span class="min-w-0 flex-1 truncate text-sm text-[color:var(--nx-text)]">{{ $printer->name }}</span>
                                <x-nx-badge :variant="$printer->is_active ? 'success' : 'neutral'">{{ $printer->is_active ? 'Aktiv' : 'Inaktiv' }}</x-nx-badge>
                                <button type="button" class="shrink-0 text-[color:var(--nx-faint)] transition-colors hover:text-[color:var(--nx-danger)]" x-on:click.stop.prevent="$wire.openRemovePrinterModal({{ $printer->id }})" title="Entfernen">
                                    @svg('heroicon-o-x-mark', 'w-4 h-4')
                                </button>
                            </div>
                        @empty
                            <p class="m-0 text-xs text-[color:var(--nx-faint)]">Noch keine Drucker zugewiesen.</p>
                        @endforelse
                        <x-nx-button wire:click="addPrinter" class="w-full">
                            @svg('heroicon-o-plus', 'w-4 h-4')
                            <span>Drucker zuweisen</span>
                        </x-nx-button>
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
    <div class="space-y-5">

        {{-- Gruppen-Daten --}}
        <x-nx-section icon="heroicon-o-folder" title="Gruppen-Daten">
            <x-nx-card>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
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

                <div class="mt-4 border-t border-[color:var(--nx-line)] pt-4">
                    <x-ui-input-checkbox
                        model="group_is_active"
                        checked-label="Aktiv"
                        unchecked-label="Gruppe ist aktiv"
                        size="md"
                        block="true"
                    />
                </div>
            </x-nx-card>
        </x-nx-section>

        {{-- Statistiken --}}
        <x-nx-stat-grid>
            <x-nx-stat label="Gesamt Jobs" :value="(string) $stats['total']" icon="heroicon-o-document-text" accent="var(--nx-accent)" />
            <x-nx-stat label="Wartend" :value="(string) $stats['pending']" icon="heroicon-o-clock"
                :accent="$stats['pending'] > 0 ? 'var(--nx-warning)' : 'var(--nx-muted)'" />
            <x-nx-stat label="Abgeschlossen" :value="(string) $stats['completed']" icon="heroicon-o-check-circle" accent="var(--nx-success)" />
            <x-nx-stat label="Fehlgeschlagen" :value="(string) $stats['failed']" icon="heroicon-o-x-circle"
                :accent="$stats['failed'] > 0 ? 'var(--nx-danger)' : 'var(--nx-muted)'" />
        </x-nx-stat-grid>

        {{-- Print Jobs --}}
        <x-nx-card flush>
            <div class="flex items-center gap-2 border-b border-[color:var(--nx-line)] px-4 py-3">
                @svg('heroicon-o-queue-list', 'w-4 h-4 text-[color:var(--nx-muted)]')
                <h2 class="m-0 text-xs font-semibold text-[color:var(--nx-muted)]">Print Jobs</h2>
                <a href="{{ route('printing.jobs.index') }}" wire:navigate class="ml-auto text-xs text-[color:var(--nx-muted)] transition-colors hover:text-[color:var(--nx-text)]">Alle</a>
            </div>

            @if($jobs->count() > 0)
                <x-nx-table>
                    <x-nx-table-header>
                        <x-nx-table-header-cell>Template</x-nx-table-header-cell>
                        <x-nx-table-header-cell>Status</x-nx-table-header-cell>
                        <x-nx-table-header-cell>Ziel</x-nx-table-header-cell>
                        <x-nx-table-header-cell>Erstellt</x-nx-table-header-cell>
                    </x-nx-table-header>
                    <x-nx-table-body>
                        @foreach($jobs as $job)
                            <x-nx-table-row wire:key="group-job-{{ $job->id }}" clickable :href="route('printing.jobs.show', ['job' => $job->id])">
                                <x-nx-table-cell>
                                    <span class="font-medium text-[color:var(--nx-text)]">{{ $job->template }}</span>
                                </x-nx-table-cell>
                                <x-nx-table-cell>
                                    <x-nx-badge :variant="$job->status_color">{{ $job->status_description }}</x-nx-badge>
                                </x-nx-table-cell>
                                <x-nx-table-cell class="text-[color:var(--nx-muted)]">
                                    @if($job->printer)
                                        {{ $job->printer->name }}
                                    @elseif($job->printerGroup)
                                        Gruppe: {{ $job->printerGroup->name }}
                                    @else
                                        <span class="text-[color:var(--nx-faint)]">–</span>
                                    @endif
                                </x-nx-table-cell>
                                <x-nx-table-cell class="whitespace-nowrap text-[color:var(--nx-muted)]">{{ $job->created_at->diffForHumans() }}</x-nx-table-cell>
                            </x-nx-table-row>
                        @endforeach
                    </x-nx-table-body>
                </x-nx-table>
            @else
                <x-nx-empty icon="heroicon-o-queue-list">Für diese Gruppe sind aktuell keine Jobs vorhanden.</x-nx-empty>
            @endif
        </x-nx-card>

        @if($jobs->hasPages())
            <div class="flex justify-end">{{ $jobs->links('printing::partials.pagination') }}</div>
        @endif

        {{-- Drucker zuweisen --}}
        <x-nx-modal model="printerAssignmentModalShow" size="md">
            <x-slot name="header">
                <h2 class="m-0 text-sm font-semibold text-[color:var(--nx-text)]">Drucker zuweisen</h2>
            </x-slot>

            <form>
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

            <x-slot name="footer">
                <x-nx-button type="button" @click="$wire.closePrinterAssignmentModal()">Abbrechen</x-nx-button>
                <x-nx-button type="button" variant="primary" wire:click="assignPrinter">Zuweisen</x-nx-button>
            </x-slot>
        </x-nx-modal>

        {{-- Drucker entfernen --}}
        <x-nx-modal model="removePrinterModalShow" size="sm">
            <x-slot name="header">
                <h2 class="m-0 text-sm font-semibold text-[color:var(--nx-text)]">Drucker entfernen</h2>
            </x-slot>

            <p class="m-0 text-sm text-[color:var(--nx-text)]">Soll dieser Drucker wirklich aus der Gruppe entfernt werden?</p>

            <x-slot name="footer">
                <x-nx-button type="button" @click="$wire.closeRemovePrinterModal()">Abbrechen</x-nx-button>
                {{-- Das Fenster IST die Rückfrage; der frühere Zwei-Klick-Knopf
                     verlangte darin eine zweite. --}}
                <x-nx-button type="button" variant="danger" wire:click="confirmRemovePrinter">Entfernen</x-nx-button>
            </x-slot>
        </x-nx-modal>

    </div>
    </x-ui-page-container>
</x-ui-page>

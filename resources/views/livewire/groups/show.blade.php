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
                    <div class="d-flex items-center gap-2">
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
            <div class="p-4 space-y-4">
                {{-- Kurze Übersicht --}}
                <div class="p-3 bg-muted-5 rounded-lg">
                    <h4 class="font-semibold mb-2 text-secondary">Gruppen-Übersicht</h4>
                    <div class="space-y-1 text-sm">
                        <div><strong>Name:</strong> {{ $group->name }}</div>
                        <div><strong>Beschreibung:</strong> {{ $group->description ?: 'Nicht angegeben' }}</div>
                        <div><strong>Status:</strong>
                            <x-ui-badge variant="{{ $group->is_active ? 'success' : 'secondary' }}" size="xs">
                                {{ $group->is_active ? 'Aktiv' : 'Inaktiv' }}
                            </x-ui-badge>
                        </div>
                        <div><strong>Drucker:</strong> {{ $group->printers->count() }}</div>
                    </div>
                </div>

                {{-- Drucker --}}
                <div>
                    <h4 class="font-semibold mb-2">Drucker</h4>
                    <div class="space-y-2">
                        @foreach($group->printers as $printer)
                            <div class="d-flex items-center gap-2 p-2 bg-muted-5 rounded cursor-pointer" wire:click="editPrinter({{ $printer->id }})">
                                <span class="flex-grow-1 text-sm">{{ $printer->name }}</span>
                                <x-ui-badge variant="primary" size="xs">{{ $printer->is_active ? 'Aktiv' : 'Inaktiv' }}</x-ui-badge>
                                <div class="flex-shrink-0" @click.stop>
                                    <x-ui-button
                                        size="xs"
                                        variant="danger-outline"
                                        x-on:click.prevent="$wire.openRemovePrinterModal({{ $printer->id }})"
                                    >
                                        <div class="d-flex items-center gap-1">
                                            @svg('heroicon-o-x-mark', 'w-3 h-3')
                                            Entfernen
                                        </div>
                                    </x-ui-button>
                                </div>
                            </div>
                        @endforeach
                        @if($group->printers->count() === 0)
                            <p class="text-sm text-muted">Noch keine Drucker zugewiesen.</p>
                        @endif
                        <x-ui-button size="sm" variant="secondary-outline" wire:click="addPrinter">
                            <div class="d-flex items-center gap-2">
                                @svg('heroicon-o-plus', 'w-4 h-4')
                                Drucker zuweisen
                            </div>
                        </x-ui-button>
                    </div>
                </div>
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
        <div>
            <h3 class="text-lg font-semibold mb-4 text-secondary">Gruppen-Daten</h3>
            <div class="grid grid-cols-2 gap-4">
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
        </div>

        {{-- Status & Einstellungen --}}
        <div>
            <h3 class="text-lg font-semibold mb-4 text-secondary">Status & Einstellungen</h3>
            <div class="grid grid-cols-2 gap-4">
                <x-ui-input-checkbox
                    model="group_is_active"
                    checked-label="Aktiv"
                    unchecked-label="Gruppe ist aktiv"
                    size="md"
                    block="true"
                />
            </div>
        </div>

        {{-- Kennzahlen --}}
        <div>
            <h3 class="text-lg font-semibold mb-4 text-secondary">Statistiken</h3>
            <div class="grid grid-cols-4 gap-4">
                <x-ui-dashboard-tile title="Gesamt Jobs" :count="$stats['total']" icon="document-text" variant="primary" size="lg" />
                <x-ui-dashboard-tile title="Wartend" :count="$stats['pending']" icon="clock" variant="warning" size="lg" />
                <x-ui-dashboard-tile title="Abgeschlossen" :count="$stats['completed']" icon="check-circle" variant="success" size="lg" />
                <x-ui-dashboard-tile title="Fehlgeschlagen" :count="$stats['failed']" icon="x-circle" variant="danger" size="lg" />
            </div>
        </div>

        {{-- Print Jobs --}}
        <div>
            <h3 class="text-lg font-semibold mb-4 text-secondary">Print Jobs</h3>
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
                                <x-ui-table-cell>{{ $job->template }}</x-ui-table-cell>
                                <x-ui-table-cell>
                                    <x-ui-badge
                                        variant="{{ in_array($job->status, ['pending','processing']) ? 'warning' : ($job->status === 'completed' ? 'success' : ($job->status === 'failed' ? 'danger' : 'secondary')) }}"
                                        size="sm"
                                    >
                                        {{ ucfirst($job->status) }}
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
                <div class="mt-4">{{ $jobs->links() }}</div>
            @else
                <div class="text-center py-8 text-[var(--ui-muted)]">
                    <x-heroicon-o-queue-list class="w-12 h-12 text-[var(--ui-muted)] mx-auto mb-3"/>
                    <div class="text-lg font-medium">Keine Jobs gefunden</div>
                    <div>Für diese Gruppe sind aktuell keine Jobs vorhanden.</div>
                </div>
            @endif
        </div>

        <!-- Printer Assignment Modal -->
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
                <div class="d-flex justify-end gap-2">
                    <x-ui-button type="button" variant="secondary-outline" @click="$wire.closePrinterAssignmentModal()">
                        Abbrechen
                    </x-ui-button>
                    <x-ui-button type="button" variant="primary" wire:click="assignPrinter">
                        Zuweisen
                    </x-ui-button>
                </div>
            </x-slot>
        </x-ui-modal>

        <!-- Remove Printer Confirm Modal -->
        <x-ui-modal model="removePrinterModalShow" size="sm">
            <x-slot name="header">
                Drucker entfernen
            </x-slot>

            <div class="space-y-2">
                <p class="text-sm">Soll dieser Drucker wirklich entfernt werden?</p>
            </div>

            <x-slot name="footer">
                <div class="d-flex justify-end gap-2">
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

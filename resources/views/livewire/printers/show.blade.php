<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Printing" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Printing', 'href' => route('printing.dashboard'), 'icon' => 'printer'],
            ['label' => 'Drucker', 'href' => route('printing.printers.index'), 'icon' => 'printer'],
            ['label' => $printer->name],
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
                            <dd class="text-sm text-[var(--ui-secondary)] m-0 truncate">{{ $printer->name }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3 px-3 py-2">
                            <dt class="text-xs text-[var(--ui-muted)]">Standort</dt>
                            <dd class="text-sm text-[var(--ui-secondary)] m-0 truncate">{{ $printer->location ?: '–' }}</dd>
                        </div>
                        @if($printer->username)
                            <div class="flex items-center justify-between gap-3 px-3 py-2">
                                <dt class="text-xs text-[var(--ui-muted)]">Benutzername</dt>
                                <dd class="text-sm text-[var(--ui-secondary)] m-0 truncate">{{ $printer->username }}</dd>
                            </div>
                        @endif
                        <div class="flex items-center justify-between gap-3 px-3 py-2">
                            <dt class="text-xs text-[var(--ui-muted)]">Status</dt>
                            <dd class="m-0">
                                <x-ui-badge variant="{{ $printer->is_active ? 'success' : 'secondary' }}" size="xs">
                                    {{ $printer->is_active ? 'Aktiv' : 'Inaktiv' }}
                                </x-ui-badge>
                            </dd>
                        </div>
                    </dl>
                </section>

                {{-- Gruppen --}}
                <section>
                    <h3 class="text-[10px] font-semibold uppercase tracking-wider text-[var(--ui-muted)] mb-2">Gruppen</h3>
                    <div class="space-y-2">
                        @forelse($printer->groups as $group)
                            <div class="flex items-center gap-2 px-3 py-2 rounded-lg border border-[var(--ui-border)] hover:bg-[var(--ui-muted-5)] transition-colors cursor-pointer" wire:click="editGroup({{ $group->id }})">
                                <span class="flex-1 min-w-0 truncate text-sm text-[var(--ui-secondary)]">{{ $group->name }}</span>
                                <x-ui-badge variant="{{ $group->is_active ? 'success' : 'secondary' }}" size="xs">{{ $group->is_active ? 'Aktiv' : 'Inaktiv' }}</x-ui-badge>
                                <button type="button" class="shrink-0 text-[var(--ui-muted)] hover:text-[var(--ui-danger)] transition-colors" x-on:click.stop.prevent="$wire.openRemoveGroupModal({{ $group->id }})" title="Entfernen">
                                    @svg('heroicon-o-x-mark', 'w-4 h-4')
                                </button>
                            </div>
                        @empty
                            <p class="text-sm text-[var(--ui-muted)]">Noch keine Gruppen zugewiesen.</p>
                        @endforelse
                        <x-ui-button size="sm" variant="secondary-outline" wire:click="addGroup" class="w-full">
                            <div class="flex items-center justify-center gap-2">
                                @svg('heroicon-o-plus', 'w-4 h-4')
                                Gruppe zuweisen
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
                    :model="$printer"
                    :key="get_class($printer) . '_' . $printer->id"
                />
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-ui-page-container>
        {{-- Drucker-Daten --}}
        <x-ui-panel title="Drucker-Daten">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-ui-input-text
                    name="printer_name"
                    label="Name"
                    wire:model.live.debounce.500ms="printer_name"
                    placeholder="Druckername eingeben..."
                    required
                    :errorKey="'printer_name'"
                />
                <x-ui-input-text
                    name="printer_location"
                    label="Standort"
                    wire:model.live.debounce.500ms="printer_location"
                    placeholder="Standort eingeben..."
                    :errorKey="'printer_location'"
                />
                <x-ui-input-text
                    name="printer_username"
                    label="Benutzername"
                    wire:model.live.debounce.500ms="printer_username"
                    placeholder="Benutzername (optional)"
                    :errorKey="'printer_username'"
                />
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-[var(--ui-secondary)]">Passwort</label>
                    <div class="flex items-center gap-2">
                        <div class="flex-1 min-w-0 px-3 py-2 rounded-lg border border-[var(--ui-border)] bg-[var(--ui-muted-5)] font-mono text-sm truncate">
                            {{ $this->currentPassword }}
                        </div>
                        <x-ui-button
                            variant="secondary-outline"
                            size="sm"
                            wire:click="togglePasswordVisibility"
                            title="{{ $showPassword ? 'Passwort verbergen' : 'Passwort anzeigen' }}"
                        >
                            @if($showPassword)
                                @svg('heroicon-o-eye-slash', 'w-4 h-4')
                            @else
                                @svg('heroicon-o-eye', 'w-4 h-4')
                            @endif
                        </x-ui-button>
                        <x-ui-button
                            variant="secondary-outline"
                            size="sm"
                            wire:click="openPasswordModal"
                            title="Passwort ändern"
                        >
                            @svg('heroicon-o-pencil', 'w-4 h-4')
                        </x-ui-button>
                    </div>
                </div>
            </div>

            <div class="mt-4 pt-4 border-t border-[var(--ui-border)]">
                <x-ui-input-checkbox
                    model="printer_is_active"
                    checked-label="Aktiv"
                    unchecked-label="Drucker ist aktiv"
                    size="md"
                    block="true"
                />
            </div>
        </x-ui-panel>

        {{-- API-Informationen --}}
        @if($printer->username && $printer->password)
            <x-ui-panel title="API-Informationen" subtitle="Zugangsdaten und Endpunkte für CloudPRNT">
                <div class="space-y-4">
                    <div>
                        <div class="text-xs font-medium text-[var(--ui-muted)] mb-1.5">Basic Auth Header</div>
                        <div class="flex items-center gap-2">
                            <code class="flex-1 min-w-0 px-3 py-2 rounded-lg border border-[var(--ui-border)] bg-[var(--ui-muted-5)] text-xs font-mono break-all">
                                {{ $this->basicAuthHeader }}
                            </code>
                            <x-ui-button
                                variant="secondary-outline"
                                size="sm"
                                onclick="navigator.clipboard.writeText('{{ $this->basicAuthHeader }}')"
                                title="In Zwischenablage kopieren"
                            >
                                @svg('heroicon-o-clipboard', 'w-4 h-4')
                            </x-ui-button>
                        </div>
                    </div>
                    <div>
                        <div class="text-xs font-medium text-[var(--ui-muted)] mb-1.5">API-Endpoints</div>
                        <dl class="rounded-lg border border-[var(--ui-border)] divide-y divide-[var(--ui-border)] overflow-hidden">
                            <div class="flex items-center justify-between gap-3 px-3 py-2">
                                <dt class="text-sm font-medium text-[var(--ui-secondary)]">Poll</dt>
                                <dd class="text-xs font-mono text-[var(--ui-muted)] m-0 truncate">POST {{ url('api/printing/poll') }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-3 px-3 py-2">
                                <dt class="text-sm font-medium text-[var(--ui-secondary)]">Job Download</dt>
                                <dd class="text-xs font-mono text-[var(--ui-muted)] m-0 truncate">GET {{ url('api/printing/job/{uuid}') }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-3 px-3 py-2">
                                <dt class="text-sm font-medium text-[var(--ui-secondary)]">Job Confirmation</dt>
                                <dd class="text-xs font-mono text-[var(--ui-muted)] m-0 truncate">DELETE {{ url('api/printing/confirm/{uuid}') }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </x-ui-panel>
        @endif

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
                        <x-ui-table-header-cell>Objekt</x-ui-table-header-cell>
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
                                    <span class="text-sm text-[var(--ui-muted)]">{{ $job->printable_name }} #{{ $job->printable_id }}</span>
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
                    <div class="text-sm text-[var(--ui-muted)] mt-1">Für diesen Drucker sind aktuell keine Jobs vorhanden.</div>
                </div>
            @endif
        </div>

        {{-- Group Assignment Modal --}}
        <x-ui-modal model="groupAssignmentModalShow" size="md">
            <x-slot name="header">
                Gruppe zuweisen
            </x-slot>

            <div class="space-y-4">
                <form class="space-y-4">
                    <x-ui-input-select
                        name="selectedGroupId"
                        label="Gruppe auswählen"
                        :options="$availableGroups"
                        optionValue="id"
                        optionLabel="name"
                        :nullable="true"
                        nullLabel="– Gruppe auswählen –"
                        wire:model.live="selectedGroupId"
                    />
                </form>
            </div>

            <x-slot name="footer">
                <div class="flex justify-end gap-2">
                    <x-ui-button type="button" variant="secondary-outline" @click="$wire.closeGroupAssignmentModal()">
                        Abbrechen
                    </x-ui-button>
                    <x-ui-button type="button" variant="primary" wire:click="assignGroup">
                        Zuweisen
                    </x-ui-button>
                </div>
            </x-slot>
        </x-ui-modal>

        {{-- Remove Group Confirm Modal --}}
        <x-ui-modal model="removeGroupModalShow" size="sm">
            <x-slot name="header">
                Gruppe entfernen
            </x-slot>

            <div class="space-y-2">
                <p class="text-sm text-[var(--ui-secondary)]">Soll diese Gruppe wirklich entfernt werden?</p>
            </div>

            <x-slot name="footer">
                <div class="flex justify-end gap-2">
                    <x-ui-button type="button" variant="secondary-outline" @click="$wire.closeRemoveGroupModal()">
                        Abbrechen
                    </x-ui-button>
                    <x-ui-confirm-button
                        action="confirmRemoveGroup"
                        text="Entfernen"
                        confirmText="Jetzt entfernen?"
                        variant="danger"
                        size="sm"
                    />
                </div>
            </x-slot>
        </x-ui-modal>

        {{-- Password Change Modal --}}
        <x-ui-modal model="passwordModalShow" size="md">
            <x-slot name="header">
                Passwort ändern
            </x-slot>

            <div class="space-y-4">
                <div class="text-sm text-[var(--ui-muted)]">
                    Geben Sie ein neues Passwort für den Drucker ein. Das Passwort wird für die Basic Auth-Authentifizierung verwendet.
                </div>

                <x-ui-input-text
                    name="newPassword"
                    label="Neues Passwort"
                    wire:model.live="newPassword"
                    type="password"
                    placeholder="Neues Passwort eingeben..."
                    required
                    :errorKey="'newPassword'"
                />

                <x-ui-input-text
                    name="confirmPassword"
                    label="Passwort bestätigen"
                    wire:model.live="confirmPassword"
                    type="password"
                    placeholder="Passwort wiederholen..."
                    required
                    :errorKey="'confirmPassword'"
                />
            </div>

            <x-slot name="footer">
                <div class="flex justify-end gap-2">
                    <x-ui-button type="button" variant="secondary-outline" @click="$wire.closePasswordModal()">
                        Abbrechen
                    </x-ui-button>
                    <x-ui-button type="button" variant="primary" wire:click="updatePassword">
                        Passwort ändern
                    </x-ui-button>
                </div>
            </x-slot>
        </x-ui-modal>
    </x-ui-page-container>
</x-ui-page>

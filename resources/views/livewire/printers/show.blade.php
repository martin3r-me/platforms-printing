<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Printing', 'href' => route('printing.dashboard'), 'icon' => 'printer'],
            ['label' => 'Drucker', 'href' => route('printing.printers.index'), 'icon' => 'printer'],
            ['label' => $printer->name],
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
                    <h4 class="font-semibold mb-2 text-secondary">Drucker-Übersicht</h4>
                    <div class="space-y-1 text-sm">
                        <div><strong>Name:</strong> {{ $printer->name }}</div>
                        <div><strong>Standort:</strong> {{ $printer->location ?: 'Nicht angegeben' }}</div>
                        @if($printer->username)
                            <div><strong>Benutzername:</strong> {{ $printer->username }}</div>
                        @endif
                        <div><strong>Status:</strong>
                            <x-ui-badge variant="{{ $printer->is_active ? 'success' : 'secondary' }}" size="xs">
                                {{ $printer->is_active ? 'Aktiv' : 'Inaktiv' }}
                            </x-ui-badge>
                        </div>
                    </div>
                </div>

                {{-- Gruppen --}}
                <div>
                    <h4 class="font-semibold mb-2">Gruppen</h4>
                    <div class="space-y-2">
                        @foreach($printer->groups as $group)
                            <div class="d-flex items-center gap-2 p-2 bg-muted-5 rounded cursor-pointer" wire:click="editGroup({{ $group->id }})">
                                <span class="flex-grow-1 text-sm">{{ $group->name }}</span>
                                <x-ui-badge variant="primary" size="xs">{{ $group->is_active ? 'Aktiv' : 'Inaktiv' }}</x-ui-badge>
                                <div class="flex-shrink-0" @click.stop>
                                    <x-ui-button
                                        size="xs"
                                        variant="danger-outline"
                                        x-on:click.prevent="$wire.openRemoveGroupModal({{ $group->id }})"
                                    >
                                        <div class="d-flex items-center gap-1">
                                            @svg('heroicon-o-x-mark', 'w-3 h-3')
                                            Entfernen
                                        </div>
                                    </x-ui-button>
                                </div>
                            </div>
                        @endforeach
                        @if($printer->groups->count() === 0)
                            <p class="text-sm text-muted">Noch keine Gruppen zugewiesen.</p>
                        @endif
                        <x-ui-button size="sm" variant="secondary-outline" wire:click="addGroup">
                            <div class="d-flex items-center gap-2">
                                @svg('heroicon-o-plus', 'w-4 h-4')
                                Gruppe zuweisen
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
                    :model="$printer"
                    :key="get_class($printer) . '_' . $printer->id"
                />
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-ui-page-container>
        {{-- Drucker-Daten --}}
        <div>
            <h3 class="text-lg font-semibold mb-4 text-secondary">Drucker-Daten</h3>
            <div class="grid grid-cols-2 gap-4">
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
            </div>
            <div class="grid grid-cols-2 gap-4 mt-4">
                <x-ui-input-text
                    name="printer_username"
                    label="Benutzername"
                    wire:model.live.debounce.500ms="printer_username"
                    placeholder="Benutzername (optional)"
                    :errorKey="'printer_username'"
                />
                <div class="space-y-2">
                    <label class="font-semibold text-sm">Passwort</label>
                    <div class="d-flex items-center gap-2">
                        <div class="flex-grow-1 p-2 border rounded-lg bg-muted-5 font-mono text-sm">
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
        </div>

        {{-- API-Informationen --}}
        @if($printer->username && $printer->password)
            <div>
                <h3 class="text-lg font-semibold mb-4 text-secondary">API-Informationen</h3>
                <div class="space-y-4">
                    <div class="p-4 bg-muted-5 rounded-lg">
                        <h4 class="font-semibold mb-2">Basic Auth Header</h4>
                        <div class="d-flex items-center gap-2">
                            <code class="flex-grow-1 p-2 bg-white border rounded text-sm font-mono break-all">
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
                    <div class="p-4 bg-muted-5 rounded-lg">
                        <h4 class="font-semibold mb-2">API-Endpoints</h4>
                        <div class="space-y-2 text-sm">
                            <div class="d-flex justify-between">
                                <span class="font-medium">Poll:</span>
                                <code class="text-xs">POST {{ url('api/printing/poll') }}</code>
                            </div>
                            <div class="d-flex justify-between">
                                <span class="font-medium">Job Download:</span>
                                <code class="text-xs">GET {{ url('api/printing/job/{uuid}') }}</code>
                            </div>
                            <div class="d-flex justify-between">
                                <span class="font-medium">Job Confirmation:</span>
                                <code class="text-xs">DELETE {{ url('api/printing/confirm/{uuid}') }}</code>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Status & Einstellungen --}}
        <div>
            <h3 class="text-lg font-semibold mb-4 text-secondary">Status & Einstellungen</h3>
            <div class="grid grid-cols-2 gap-4">
                <x-ui-input-checkbox
                    model="printer_is_active"
                    checked-label="Aktiv"
                    unchecked-label="Drucker ist aktiv"
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
                                    <div class="font-medium">{{ $job->template }}</div>
                                </x-ui-table-cell>
                                <x-ui-table-cell>
                                    <x-ui-badge
                                        variant="{{ in_array($job->status, ['pending','processing']) ? 'warning' : ($job->status === 'completed' ? 'success' : ($job->status === 'failed' ? 'danger' : 'secondary')) }}"
                                        size="sm"
                                    >
                                        {{ ucfirst($job->status) }}
                                    </x-ui-badge>
                                </x-ui-table-cell>
                                <x-ui-table-cell>
                                    <div class="text-sm text-muted">{{ $job->printable_type }} #{{ $job->printable_id }}</div>
                                </x-ui-table-cell>
                                <x-ui-table-cell>
                                    <div class="text-sm">{{ $job->created_at->diffForHumans() }}</div>
                                </x-ui-table-cell>
                            </x-ui-table-row>
                        @endforeach
                    </x-ui-table-body>
                </x-ui-table>
                <div class="mt-4">{{ $jobs->links() }}</div>
            @else
                <div class="text-center py-8 text-gray-600">
                    <x-heroicon-o-queue-list class="w-12 h-12 text-gray-400 mx-auto mb-3"/>
                    <div class="text-lg font-medium">Keine Jobs gefunden</div>
                    <div>Für diesen Drucker sind aktuell keine Jobs vorhanden.</div>
                </div>
            @endif
        </div>

        <!-- Group Assignment Modal -->
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
                <div class="d-flex justify-end gap-2">
                    <x-ui-button type="button" variant="secondary-outline" @click="$wire.closeGroupAssignmentModal()">
                        Abbrechen
                    </x-ui-button>
                    <x-ui-button type="button" variant="primary" wire:click="assignGroup">
                        Zuweisen
                    </x-ui-button>
                </div>
            </x-slot>
        </x-ui-modal>

        <!-- Remove Group Confirm Modal -->
        <x-ui-modal model="removeGroupModalShow" size="sm">
            <x-slot name="header">
                Gruppe entfernen
            </x-slot>

            <div class="space-y-2">
                <p class="text-sm">Soll diese Gruppe wirklich entfernt werden?</p>
            </div>

            <x-slot name="footer">
                <div class="d-flex justify-end gap-2">
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

        <!-- Password Change Modal -->
        <x-ui-modal model="passwordModalShow" size="md">
            <x-slot name="header">
                Passwort ändern
            </x-slot>

            <div class="space-y-4">
                <div class="text-sm text-muted">
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
                <div class="d-flex justify-end gap-2">
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

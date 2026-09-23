<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Drucker" icon="heroicon-o-printer" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Printing', 'href' => route('printing.dashboard'), 'icon' => 'printer'],
            ['label' => 'Drucker', 'href' => route('printing.printers.index'), 'icon' => 'printer'],
            ['label' => $printer->name],
        ]">
            @if($this->isDirty)
                <x-nx-button variant="primary" wire:click="save">
                    @svg('heroicon-o-check', 'w-4 h-4')
                    <span>Speichern</span>
                </x-nx-button>
            @endif
        </x-ui-page-actionbar>
    </x-slot>

    {{-- Linke Spalte: Überblick und Gruppen --}}
    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Einstellungen" icon="heroicon-o-cog-6-tooth" width="w-80" :defaultOpen="true">
            <div class="space-y-6 p-4">
                {{-- Übersicht --}}
                <section>
                    <h3 class="mb-2 text-[10px] font-semibold uppercase tracking-wider text-[color:var(--nx-faint)]">Übersicht</h3>
                    <div class="rounded-[8px] border border-[color:var(--nx-line)] divide-y divide-[color:var(--nx-line)]">
                        <div class="flex items-center justify-between gap-3 px-3 py-2">
                            <span class="text-xs text-[color:var(--nx-muted)]">Name</span>
                            <span class="truncate text-sm text-[color:var(--nx-text)]">{{ $printer->name }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-3 px-3 py-2">
                            <span class="text-xs text-[color:var(--nx-muted)]">Standort</span>
                            <span class="truncate text-sm text-[color:var(--nx-text)]">{{ $printer->location ?: '–' }}</span>
                        </div>
                        @if($printer->username)
                            <div class="flex items-center justify-between gap-3 px-3 py-2">
                                <span class="text-xs text-[color:var(--nx-muted)]">Benutzername</span>
                                <span class="truncate text-sm text-[color:var(--nx-text)]">{{ $printer->username }}</span>
                            </div>
                        @endif
                        <div class="flex items-center justify-between gap-3 px-3 py-2">
                            <span class="text-xs text-[color:var(--nx-muted)]">MAC</span>
                            <span class="truncate font-mono text-xs text-[color:var(--nx-text)]">{{ $printer->mac_address ?: '–' }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-3 px-3 py-2">
                            <span class="text-xs text-[color:var(--nx-muted)]">Zeichensatz</span>
                            <span class="truncate font-mono text-xs text-[color:var(--nx-text)]">{{ $printer->codepage() }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-3 px-3 py-2">
                            <span class="text-xs text-[color:var(--nx-muted)]">Status</span>
                            <x-nx-badge :variant="$printer->is_active ? 'success' : 'neutral'">{{ $printer->is_active ? 'Aktiv' : 'Inaktiv' }}</x-nx-badge>
                        </div>
                    </div>
                </section>

                {{-- Gruppen --}}
                <section>
                    <h3 class="mb-2 text-[10px] font-semibold uppercase tracking-wider text-[color:var(--nx-faint)]">Gruppen</h3>
                    <div class="space-y-2">
                        @forelse($printer->groups as $group)
                            <div class="flex cursor-pointer items-center gap-2 rounded-[6px] border border-[color:var(--nx-line)] px-3 py-2 transition-colors hover:bg-[color:var(--nx-hover)]" wire:click="editGroup({{ $group->id }})">
                                <span class="min-w-0 flex-1 truncate text-sm text-[color:var(--nx-text)]">{{ $group->name }}</span>
                                <x-nx-badge :variant="$group->is_active ? 'success' : 'neutral'">{{ $group->is_active ? 'Aktiv' : 'Inaktiv' }}</x-nx-badge>
                                <button type="button" class="shrink-0 text-[color:var(--nx-faint)] transition-colors hover:text-[color:var(--nx-danger)]" x-on:click.stop.prevent="$wire.openRemoveGroupModal({{ $group->id }})" title="Entfernen">
                                    @svg('heroicon-o-x-mark', 'w-4 h-4')
                                </button>
                            </div>
                        @empty
                            <p class="m-0 text-xs text-[color:var(--nx-faint)]">Noch keine Gruppen zugewiesen.</p>
                        @endforelse
                        <x-nx-button wire:click="addGroup" class="w-full">
                            @svg('heroicon-o-plus', 'w-4 h-4')
                            <span>Gruppe zuweisen</span>
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
                    :model="$printer"
                    :key="get_class($printer) . '_' . $printer->id"
                />
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-ui-page-container>
    <div class="space-y-5">

        {{-- Drucker-Daten --}}
        <x-nx-section icon="heroicon-o-printer" title="Drucker-Daten">
            <x-nx-card>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
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
                        <label class="block text-sm font-medium text-[color:var(--nx-text)]">Passwort</label>
                        <div class="flex items-center gap-2">
                            <div class="min-w-0 flex-1 truncate rounded-[6px] border border-[color:var(--nx-line)] bg-[color:var(--nx-bg)] px-3 py-1.5 font-mono text-sm text-[color:var(--nx-text)]">
                                {{ $this->currentPassword }}
                            </div>
                            <x-nx-button icon wire:click="togglePasswordVisibility"
                                title="{{ $showPassword ? 'Passwort verbergen' : 'Passwort anzeigen' }}">
                                @if($showPassword)
                                    @svg('heroicon-o-eye-slash', 'w-4 h-4')
                                @else
                                    @svg('heroicon-o-eye', 'w-4 h-4')
                                @endif
                            </x-nx-button>
                            <x-nx-button icon wire:click="openPasswordModal" title="Passwort ändern">
                                @svg('heroicon-o-pencil', 'w-4 h-4')
                            </x-nx-button>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <x-ui-input-text
                        name="printer_mac_address"
                        label="MAC-Adresse"
                        wire:model.live.debounce.500ms="printer_mac_address"
                        placeholder="z. B. 00:11:62:AA:BB:CC"
                        :errorKey="'printer_mac_address'"
                    />
                    <p class="mt-1 text-xs text-[color:var(--nx-muted)]">Über diese Adresse wird der Drucker beim CloudPRNT-Polling erkannt (Header <code>x-star-mac</code>). Genau so eintragen, wie der Drucker sie sendet.</p>
                </div>

                <div class="mt-4 border-t border-[color:var(--nx-line)] pt-4">
                    <x-ui-input-checkbox
                        model="printer_is_active"
                        checked-label="Aktiv"
                        unchecked-label="Drucker ist aktiv"
                        size="md"
                        block="true"
                    />
                </div>
            </x-nx-card>
        </x-nx-section>

        {{-- Zeichensatz --}}
        <x-nx-section icon="heroicon-o-language" title="Zeichensatz" description="Wie Umlaute an dieses Gerät gesendet werden">
            <x-nx-card>
                <div class="space-y-4">
                    <x-ui-input-select
                        name="printer_codepage"
                        label="Zeichentabelle (Codepage)"
                        :options="collect($this->codepageOptions())->map(fn($label, $value) => ['value' => $value, 'label' => $label])->values()->all()"
                        optionValue="value"
                        optionLabel="label"
                        wire:model.live="printer_codepage"
                        :errorKey="'printer_codepage'"
                    />
                    <p class="-mt-2 text-xs text-[color:var(--nx-muted)]">
                        Muss zur Tabelle des Geräts passen. Stimmt sie nicht, werden Umlaute
                        durch andere Zeichen ersetzt (z.&nbsp;B. <code>ö</code> als <code>•</code>).
                        Welche die richtige ist, verrät der Testdruck unten.
                    </p>

                    <x-ui-input-text
                        name="printer_setup_hex"
                        label="Setup-Bytes (Hex)"
                        wire:model.live.debounce.500ms="printer_setup_hex"
                        placeholder="z. B. 1B 52 00"
                        :errorKey="'printer_setup_hex'"
                    />
                    <p class="-mt-2 text-xs text-[color:var(--nx-muted)]">
                        Steuerbefehl, der jedem Auftrag vorangestellt wird. <code>1B 52 00</code>
                        (ESC R 0) setzt den internationalen Zeichensatz auf USA – ohne das druckt
                        ein auf „Deutschland“ stehendes Gerät <code>@</code> als <code>§</code>.
                        Leer lassen = kein Befehl.
                    </p>

                    {{-- Speichern gehört hierher: der Knopf in der Actionbar ganz
                         oben ist von hier aus nicht zu sehen und wurde übersehen. --}}
                    @if($this->isDirty)
                        <x-nx-callout variant="warning">
                            <div class="flex items-center justify-between gap-4">
                                <span>Nicht gespeichert – erst nach dem Speichern druckt das Gerät mit dieser Tabelle.</span>
                                <x-nx-button variant="primary" wire:click="save" class="shrink-0">
                                    @svg('heroicon-o-check', 'w-4 h-4')
                                    <span>Speichern</span>
                                </x-nx-button>
                            </div>
                        </x-nx-callout>
                    @endif

                    <div class="flex items-center justify-between gap-4 border-t border-[color:var(--nx-line)] pt-4">
                        <div class="min-w-0">
                            <div class="text-sm font-medium text-[color:var(--nx-text)]">Testdruck</div>
                            <p class="m-0 text-xs text-[color:var(--nx-muted)]">
                                Druckt jedes Byte von <code>80</code> bis <code>FF</code> mit seinem Hex-Wert.
                                Auf dem Bon ablesen, welches Byte <code>ä</code>, <code>ö</code> und <code>ü</code>
                                ergibt, und die passende Tabelle oben auswählen.
                            </p>
                        </div>
                        <x-nx-button wire:click="testPrint" class="shrink-0">
                            @svg('heroicon-o-printer', 'w-4 h-4')
                            <span>Testdruck</span>
                        </x-nx-button>
                    </div>
                </div>
            </x-nx-card>
        </x-nx-section>

        {{-- API-Informationen --}}
        @if($printer->username && $printer->password)
            <x-nx-section icon="heroicon-o-key" title="API-Informationen" description="Zugangsdaten und Endpunkte für CloudPRNT">
                <x-nx-card>
                    <div class="space-y-4">
                        <div>
                            <div class="mb-1.5 text-xs font-medium text-[color:var(--nx-muted)]">Basic Auth Header</div>
                            <div class="flex items-center gap-2">
                                <code class="min-w-0 flex-1 break-all rounded-[6px] border border-[color:var(--nx-line)] bg-[color:var(--nx-bg)] px-3 py-2 font-mono text-xs text-[color:var(--nx-text)]">
                                    {{ $this->basicAuthHeader }}
                                </code>
                                <x-nx-button icon
                                    onclick="navigator.clipboard.writeText('{{ $this->basicAuthHeader }}')"
                                    title="In Zwischenablage kopieren">
                                    @svg('heroicon-o-clipboard', 'w-4 h-4')
                                </x-nx-button>
                            </div>
                        </div>
                        <div>
                            <div class="mb-1.5 text-xs font-medium text-[color:var(--nx-muted)]">API-Endpoints</div>
                            <div class="rounded-[8px] border border-[color:var(--nx-line)] divide-y divide-[color:var(--nx-line)]">
                                <div class="flex items-center justify-between gap-3 px-3 py-2">
                                    <span class="text-sm font-medium text-[color:var(--nx-text)]">Poll</span>
                                    <span class="truncate font-mono text-xs text-[color:var(--nx-muted)]">POST {{ $this->apiEndpoints['poll'] }}</span>
                                </div>
                                <div class="flex items-center justify-between gap-3 px-3 py-2">
                                    <span class="text-sm font-medium text-[color:var(--nx-text)]">Job Download</span>
                                    <span class="truncate font-mono text-xs text-[color:var(--nx-muted)]">GET {{ $this->apiEndpoints['download'] }}</span>
                                </div>
                                <div class="flex items-center justify-between gap-3 px-3 py-2">
                                    <span class="text-sm font-medium text-[color:var(--nx-text)]">Job Confirmation</span>
                                    <span class="truncate font-mono text-xs text-[color:var(--nx-muted)]">DELETE {{ $this->apiEndpoints['confirm'] }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </x-nx-card>
            </x-nx-section>
        @endif

        {{-- Selbstauskunft des Geraets: Antwort auf die clientAction-Rueckfrage
             (echter Poll-Takt und unterstuetzte Formate). Erscheint, sobald der
             Drucker das naechste Mal ohne anstehenden Auftrag gepollt hat. --}}
        @php
            $einstellungen  = $printer->settings ?? [];
            $schnappschuss  = $einstellungen['poll_snapshot'] ?? null;
            $taktDaten      = $einstellungen['poll_takt'] ?? null;
            $diagnose       = \Platform\Printing\Support\PrinterSelfReport::diagnose($printer);
            // Nur zeigen, solange aufgezeichnet wird - stehengebliebene Werte
            // sehen sonst aus wie aktuelle Messung.
            $verkehr        = $diagnose ? array_reverse($einstellungen['verkehr'] ?? []) : [];
            $abstaende      = $diagnose ? ($taktDaten['abstaende'] ?? []) : [];
            $selbstauskunft = $einstellungen['self_report'] ?? null;
            $alsText = fn ($w) => json_encode($w, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        @endphp
        @if ($verkehr)
            {{-- Jede Anfrage des Geraets. Zeigt, was zwischen "gemeldet" und
                 "abgeholt" passiert: scheiternde Versuche - oder gar nichts.
                 Faellt weg, sobald die Diagnose wieder aus ist. --}}
            <x-nx-section icon="heroicon-o-arrows-right-left" title="Anfragen des Druckers"
                :description="'Die letzten ' . count($verkehr) . ', neueste zuerst · Aufzeichnung läuft'">
                <x-nx-card flush>
                    <div class="divide-y divide-[color:var(--nx-line)]">
                        @foreach ($verkehr as $eintrag)
                            <div class="flex items-center justify-between gap-3 px-3 py-1.5">
                                <span class="font-mono text-xs text-[color:var(--nx-faint)]">{{ $eintrag['zeit'] ?? '–' }}</span>
                                <span class="flex-1 truncate font-mono text-xs text-[color:var(--nx-text)]">
                                    {{ $eintrag['methode'] ?? '?' }} {{ $eintrag['pfad'] ?? '?' }}
                                </span>
                                <span class="font-mono text-xs {{ ($eintrag['status'] ?? 0) >= 400 ? 'font-semibold text-[color:var(--nx-danger)]' : 'text-[color:var(--nx-muted)]' }}">
                                    {{ $eintrag['status'] ?? '–' }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </x-nx-card>
            </x-nx-section>
        @endif

        {{-- Immer sichtbar: Hier steckt auch der Schalter fuer die Aufzeichnung,
             und den braucht man gerade dann, wenn noch nichts erfasst ist. --}}
        <x-nx-section icon="heroicon-o-chat-bubble-left-right" title="Was der Drucker über sich meldet">
            <x-nx-card>
                @if ($abstaende)
                    {{-- Der GEMESSENE Takt. GetPollInterval nennt nur den eingestellten
                         Wert; ob das Gerät ihn einhält, zeigt sich erst hier. --}}
                    <p class="m-0 mb-1 text-xs font-medium text-[color:var(--nx-text)]">
                        Gemessener Abstand zwischen den Abfragen · zuletzt {{ $taktDaten['zuletzt'] ?? '–' }}
                    </p>
                    <p class="m-0 mb-4 font-mono text-xs text-[color:var(--nx-text)]">
                        {{ implode(' · ', array_map(fn ($a) => $a . 's', $abstaende)) }}
                        <span class="ml-2 text-[color:var(--nx-muted)]">(Ø {{ round(array_sum($abstaende) / max(1, count($abstaende)), 1) }}s)</span>
                    </p>
                @endif

                @if ($schnappschuss)
                    <p class="m-0 mb-1 text-xs font-medium text-[color:var(--nx-text)]">
                        Erster Poll · erfasst am {{ $schnappschuss['erfasst_am'] ?? '–' }}
                        @if (! empty($schnappschuss['protokoll'])) · HTTP {{ $schnappschuss['protokoll'] }} @endif
                    </p>
                    <pre class="m-0 mb-4 overflow-x-auto whitespace-pre-wrap break-all font-mono text-xs text-[color:var(--nx-text)]">{{ $alsText($schnappschuss['rumpf'] ?? null) }}</pre>
                @endif

                @if ($selbstauskunft)
                    <p class="m-0 mb-1 text-xs font-medium text-[color:var(--nx-text)]">Antwort auf die Rückfrage · erfasst am {{ $selbstauskunft['erfasst_am'] ?? '–' }}</p>
                    <pre class="m-0 overflow-x-auto whitespace-pre-wrap break-all font-mono text-xs text-[color:var(--nx-text)]">{{ $alsText($selbstauskunft['antwort'] ?? null) }}</pre>
                    <p class="mt-2 text-xs text-[color:var(--nx-muted)]">
                        <strong>GetPollInterval</strong> ist der Takt, in dem der Drucker wirklich fragt.
                        <strong>Encodings</strong> sind die Formate, die er versteht.
                    </p>
                @else
                    <p class="m-0 text-xs text-[color:var(--nx-muted)]">
                        Auf die Rückfrage (<code>GetPollInterval</code>, <code>Encodings</code>) hat das Gerät bisher nicht
                        geantwortet. Laut Spezifikation unterstützt nicht jedes Gerät jede Anfrage – auch das ist eine Auskunft.
                    </p>
                @endif

                <div class="mt-3 flex flex-wrap items-center gap-3 border-t border-[color:var(--nx-line)] pt-3">
                    <x-nx-button :variant="$diagnose ? 'danger' : 'secondary'" wire:click="toggleDiagnose">
                        {{ $diagnose ? 'Aufzeichnung stoppen' : 'Aufzeichnung starten' }}
                    </x-nx-button>
                    <p class="m-0 flex-1 text-xs text-[color:var(--nx-muted)]">
                        @if ($diagnose)
                            Jede Anfrage des Druckers wird mitgeschrieben. Beim Stoppen werden die Messwerte verworfen.
                        @else
                            Zeichnet jede Anfrage des Druckers auf – zum Suchen. Kostet bei einer Abfrage alle paar
                            Sekunden spürbar Log und Speicher, deshalb danach wieder stoppen.
                        @endif
                    </p>
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
                        <x-nx-table-header-cell>Objekt</x-nx-table-header-cell>
                        <x-nx-table-header-cell>Erstellt</x-nx-table-header-cell>
                    </x-nx-table-header>
                    <x-nx-table-body>
                        @foreach($jobs as $job)
                            <x-nx-table-row wire:key="printer-job-{{ $job->id }}" clickable :href="route('printing.jobs.show', ['job' => $job->id])">
                                <x-nx-table-cell>
                                    <span class="font-medium text-[color:var(--nx-text)]">{{ $job->template }}</span>
                                </x-nx-table-cell>
                                <x-nx-table-cell>
                                    <x-nx-badge :variant="$job->status_color">{{ $job->status_description }}</x-nx-badge>
                                </x-nx-table-cell>
                                <x-nx-table-cell class="text-[color:var(--nx-muted)]">{{ $job->printable_name }} #{{ $job->printable_id }}</x-nx-table-cell>
                                <x-nx-table-cell class="whitespace-nowrap text-[color:var(--nx-muted)]">{{ $job->created_at->diffForHumans() }}</x-nx-table-cell>
                            </x-nx-table-row>
                        @endforeach
                    </x-nx-table-body>
                </x-nx-table>
            @else
                <x-nx-empty icon="heroicon-o-queue-list">Für diesen Drucker sind aktuell keine Jobs vorhanden.</x-nx-empty>
            @endif
        </x-nx-card>

        @if($jobs->hasPages())
            <div class="flex justify-end">{{ $jobs->links('printing::partials.pagination') }}</div>
        @endif

        {{-- Gruppe zuweisen --}}
        <x-nx-modal model="groupAssignmentModalShow" size="md">
            <x-slot name="header">
                <h2 class="m-0 text-sm font-semibold text-[color:var(--nx-text)]">Gruppe zuweisen</h2>
            </x-slot>

            <form>
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

            <x-slot name="footer">
                <x-nx-button type="button" @click="$wire.closeGroupAssignmentModal()">Abbrechen</x-nx-button>
                <x-nx-button type="button" variant="primary" wire:click="assignGroup">Zuweisen</x-nx-button>
            </x-slot>
        </x-nx-modal>

        {{-- Gruppe entfernen --}}
        <x-nx-modal model="removeGroupModalShow" size="sm">
            <x-slot name="header">
                <h2 class="m-0 text-sm font-semibold text-[color:var(--nx-text)]">Gruppe entfernen</h2>
            </x-slot>

            <p class="m-0 text-sm text-[color:var(--nx-text)]">Soll diese Gruppe wirklich vom Drucker entfernt werden?</p>

            <x-slot name="footer">
                <x-nx-button type="button" @click="$wire.closeRemoveGroupModal()">Abbrechen</x-nx-button>
                {{-- Das Fenster IST die Rückfrage; der frühere Zwei-Klick-Knopf
                     verlangte darin eine zweite. --}}
                <x-nx-button type="button" variant="danger" wire:click="confirmRemoveGroup">Entfernen</x-nx-button>
            </x-slot>
        </x-nx-modal>

        {{-- Passwort ändern --}}
        <x-nx-modal model="passwordModalShow" size="md">
            <x-slot name="header">
                <h2 class="m-0 text-sm font-semibold text-[color:var(--nx-text)]">Passwort ändern</h2>
            </x-slot>

            <div class="space-y-4">
                <p class="m-0 text-sm text-[color:var(--nx-muted)]">
                    Geben Sie ein neues Passwort für den Drucker ein. Es wird für die Basic-Auth-Anmeldung verwendet.
                </p>

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
                <x-nx-button type="button" @click="$wire.closePasswordModal()">Abbrechen</x-nx-button>
                <x-nx-button type="button" variant="primary" wire:click="updatePassword">Passwort ändern</x-nx-button>
            </x-slot>
        </x-nx-modal>

    </div>
    </x-ui-page-container>
</x-ui-page>

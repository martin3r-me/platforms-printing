<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Printing" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Printing', 'href' => route('printing.dashboard'), 'icon' => 'printer'],
            ['label' => 'Print Jobs', 'href' => route('printing.jobs.index'), 'icon' => 'document-text'],
            ['label' => 'Job #' . $job->id],
        ]">
            <x-ui-badge
                variant="{{ $job->status_color }}"
                size="sm"
            >
                {{ $job->status_description }}
            </x-ui-badge>
            <x-ui-button wire:click="reloadPreview" size="sm" variant="secondary-outline">
                <div class="flex items-center gap-2">
                    @svg('heroicon-o-arrow-path', 'w-4 h-4')
                    Vorschau
                </div>
            </x-ui-button>
            @if($job->status === 'failed')
                <x-ui-button wire:click="retryJob" size="sm" variant="secondary">Wiederholen</x-ui-button>
            @endif
            @if(in_array($job->status, ['pending', 'processing']))
                <x-ui-button variant="danger-outline" wire:click="cancelJob" size="sm">Abbrechen</x-ui-button>
            @endif
        </x-ui-page-actionbar>
    </x-slot>

    {{-- Logs / Aktivitäten --}}
    <x-slot name="activity">
        <x-ui-page-sidebar title="Logs" icon="heroicon-o-bolt" width="w-80" :defaultOpen="false" storeKey="activityOpen" side="right">
            <div class="p-4">
                <livewire:activity-log.index
                    :model="$job"
                    :key="get_class($job) . '_' . $job->id"
                />
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-ui-page-container>
        {{-- Status-Zeitleiste --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="rounded-xl bg-[var(--ui-surface)] border border-[var(--ui-border)] shadow-sm p-4">
                <div class="text-[10px] font-semibold uppercase tracking-wider text-[var(--ui-muted)] mb-1.5">Status</div>
                <x-ui-badge
                    variant="{{ $job->status_color }}"
                    size="sm"
                >
                    {{ $job->status_description }}
                </x-ui-badge>
            </div>
            <div class="rounded-xl bg-[var(--ui-surface)] border border-[var(--ui-border)] shadow-sm p-4">
                <div class="text-[10px] font-semibold uppercase tracking-wider text-[var(--ui-muted)] mb-1.5">Erstellt</div>
                <div class="text-sm font-medium text-[var(--ui-secondary)]">{{ $job->created_at->format('d.m.Y H:i') }}</div>
                <div class="text-xs text-[var(--ui-muted)]">{{ $job->created_at->diffForHumans() }}</div>
            </div>
            <div class="rounded-xl bg-[var(--ui-surface)] border border-[var(--ui-border)] shadow-sm p-4">
                <div class="text-[10px] font-semibold uppercase tracking-wider text-[var(--ui-muted)] mb-1.5">Gedruckt am</div>
                @if($job->printed_at)
                    <div class="text-sm font-medium text-[var(--ui-secondary)]">{{ $job->printed_at->format('d.m.Y H:i') }}</div>
                    <div class="text-xs text-[var(--ui-muted)]">{{ $job->printed_at->diffForHumans() }}</div>
                @else
                    <div class="text-sm text-[var(--ui-muted)]">– noch nicht –</div>
                @endif
            </div>
            <div class="rounded-xl bg-[var(--ui-surface)] border border-[var(--ui-border)] shadow-sm p-4">
                <div class="text-[10px] font-semibold uppercase tracking-wider text-[var(--ui-muted)] mb-1.5">Versuche</div>
                <div class="text-sm font-medium text-[var(--ui-secondary)]">{{ $job->retry_count }}× / max. {{ config('printing.jobs.max_retries', 3) }}</div>
            </div>
        </div>

        {{-- Fehlermeldung --}}
        @if($job->error_message)
            <div class="rounded-xl bg-[var(--ui-danger-5)] border border-[var(--ui-danger-20)] p-4">
                <div class="flex items-center gap-2 mb-1">
                    @svg('heroicon-o-exclamation-triangle', 'w-5 h-5 text-[var(--ui-danger)]')
                    <h3 class="text-sm font-semibold text-[var(--ui-danger)] m-0">Fehlermeldung</h3>
                </div>
                <p class="text-sm text-[var(--ui-danger)] m-0">{{ $job->error_message }}</p>
            </div>
        @endif

        {{-- Vorschau --}}
        <section class="rounded-xl bg-[var(--ui-surface)] border border-[var(--ui-border)] shadow-[0_1px_3px_rgba(0,0,0,0.04),0_1px_2px_rgba(0,0,0,0.03)]">
            <header class="px-4 py-3 border-b border-[var(--ui-border)] flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        @svg('heroicon-o-document-magnifying-glass', 'w-4 h-4 text-[var(--ui-muted)]')
                        <h3 class="text-base font-semibold text-[var(--ui-secondary)] m-0">Vorschau</h3>
                    </div>
                    <div class="text-xs text-[var(--ui-muted)] mt-0.5">Inhalt, der an den Drucker gesendet wird · Template: {{ config("printing.templates.available.{$job->template}", $job->template) }}</div>
                </div>
                <x-ui-button wire:click="reloadPreview" size="sm" variant="secondary-outline">
                    <div class="flex items-center gap-2">
                        @svg('heroicon-o-arrow-path', 'w-4 h-4')
                        Aktualisieren
                    </div>
                </x-ui-button>
            </header>
            <div class="p-4">
                @if($previewError)
                    <div class="rounded-lg bg-[var(--ui-danger-5)] border border-[var(--ui-danger-20)] p-4 text-sm text-[var(--ui-danger)]">
                        Vorschau konnte nicht erzeugt werden: {{ $previewError }}
                    </div>
                @elseif(trim((string) $preview) === '')
                    <div class="text-center py-8 text-sm text-[var(--ui-muted)]">Kein Inhalt vorhanden.</div>
                @else
                    <div class="flex justify-center overflow-auto max-h-96 py-5 rounded-lg bg-[var(--ui-muted-5)] border border-[var(--ui-border)]">
                        <pre class="w-[320px] max-w-full bg-[var(--ui-surface)] text-[var(--ui-secondary)] shadow-md rounded-sm px-5 py-4 text-[11px] leading-relaxed font-mono whitespace-pre-wrap break-words">{{ $preview }}</pre>
                    </div>
                @endif
            </div>
        </section>

        {{-- Informationen --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <x-ui-panel title="Job Informationen">
                <dl class="rounded-lg border border-[var(--ui-border)] divide-y divide-[var(--ui-border)] overflow-hidden">
                    <div class="flex items-center justify-between gap-3 px-3 py-2">
                        <dt class="text-xs text-[var(--ui-muted)]">Template</dt>
                        <dd class="text-sm text-[var(--ui-secondary)] m-0 truncate">{{ config("printing.templates.available.{$job->template}", $job->template) }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3 px-3 py-2">
                        <dt class="text-xs text-[var(--ui-muted)]">UUID</dt>
                        <dd class="text-xs font-mono text-[var(--ui-secondary)] m-0 truncate">{{ $job->uuid }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3 px-3 py-2">
                        <dt class="text-xs text-[var(--ui-muted)]">Erstellt</dt>
                        <dd class="text-sm text-[var(--ui-secondary)] m-0">{{ $job->created_at->format('d.m.Y H:i:s') }}</dd>
                    </div>
                    @if($job->printed_at)
                        <div class="flex items-center justify-between gap-3 px-3 py-2">
                            <dt class="text-xs text-[var(--ui-muted)]">Gedruckt</dt>
                            <dd class="text-sm text-[var(--ui-secondary)] m-0">{{ $job->printed_at->format('d.m.Y H:i:s') }}</dd>
                        </div>
                    @endif
                    <div class="flex items-center justify-between gap-3 px-3 py-2">
                        <dt class="text-xs text-[var(--ui-muted)]">Versuche</dt>
                        <dd class="text-sm text-[var(--ui-secondary)] m-0">{{ $job->retry_count }}</dd>
                    </div>
                </dl>
            </x-ui-panel>

            <x-ui-panel title="Ziel-Informationen">
                <dl class="rounded-lg border border-[var(--ui-border)] divide-y divide-[var(--ui-border)] overflow-hidden">
                    <div class="flex items-center justify-between gap-3 px-3 py-2">
                        <dt class="text-xs text-[var(--ui-muted)]">Drucker</dt>
                        <dd class="text-sm m-0 truncate">
                            @if($job->printer)
                                <a href="{{ route('printing.printers.show', $job->printer) }}" class="text-[var(--ui-primary)] hover:underline" wire:navigate>{{ $job->printer->name }}</a>
                            @elseif($job->printerGroup)
                                <a href="{{ route('printing.groups.show', $job->printerGroup) }}" class="text-[var(--ui-primary)] hover:underline" wire:navigate>Gruppe: {{ $job->printerGroup->name }}</a>
                            @else
                                <span class="text-[var(--ui-muted)]">Nicht zugewiesen</span>
                            @endif
                        </dd>
                    </div>
                    <div class="flex items-center justify-between gap-3 px-3 py-2">
                        <dt class="text-xs text-[var(--ui-muted)]">Objekt</dt>
                        <dd class="text-sm text-[var(--ui-secondary)] m-0 truncate">{{ $job->printable_name }} #{{ $job->printable_id }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3 px-3 py-2">
                        <dt class="text-xs text-[var(--ui-muted)]">Objekt-Typ</dt>
                        <dd class="text-xs font-mono text-[var(--ui-muted)] m-0 truncate">{{ $job->printable_type }}</dd>
                    </div>
                    @if($job->user)
                        <div class="flex items-center justify-between gap-3 px-3 py-2">
                            <dt class="text-xs text-[var(--ui-muted)]">Erstellt von</dt>
                            <dd class="text-sm text-[var(--ui-secondary)] m-0 truncate">{{ $job->user->name }}</dd>
                        </div>
                    @endif
                </dl>
            </x-ui-panel>
        </div>

        {{-- Job-Daten --}}
        @if($job->data)
            <x-ui-panel title="Job-Daten" subtitle="Die Rohdaten, aus denen die Vorschau erzeugt wird">
                <div class="rounded-lg bg-[var(--ui-muted-5)] border border-[var(--ui-border)] overflow-auto max-h-96">
                    <pre class="p-4 text-xs font-mono text-[var(--ui-secondary)]">{{ json_encode($job->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
            </x-ui-panel>
        @endif

        <div class="flex justify-end">
            <x-ui-button variant="secondary-outline" :href="route('printing.jobs.index')" wire:navigate>
                <div class="flex items-center gap-2">
                    @svg('heroicon-o-arrow-left', 'w-4 h-4')
                    Zurück zur Übersicht
                </div>
            </x-ui-button>
        </div>
    </x-ui-page-container>
</x-ui-page>

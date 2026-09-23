<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Print Job" icon="heroicon-o-document-text" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Printing', 'href' => route('printing.dashboard'), 'icon' => 'printer'],
            ['label' => 'Print Jobs', 'href' => route('printing.jobs.index'), 'icon' => 'document-text'],
            ['label' => 'Job #' . $job->id],
        ]">
            <x-nx-badge :variant="$job->status_color">{{ $job->status_description }}</x-nx-badge>
            <x-nx-button wire:click="reloadPreview" title="Vorschau neu erzeugen">
                @svg('heroicon-o-arrow-path', 'w-4 h-4')
                <span>Vorschau</span>
            </x-nx-button>
            @if($job->status === 'failed')
                <x-nx-button wire:click="retryJob">Wiederholen</x-nx-button>
            @endif
            @if(in_array($job->status, ['pending', 'processing']))
                <x-nx-button variant="danger" wire:click="cancelJob">Abbrechen</x-nx-button>
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
    <div class="space-y-5">

        {{-- Lifecycle-Stepper --}}
        @php
            $stepStates = match($job->status) {
                'pending'    => ['done', 'current', 'pending'],
                'processing' => ['done', 'done', 'current'],
                'completed'  => ['done', 'done', 'done'],
                'failed'     => ['done', 'done', 'error'],
                'cancelled'  => ['done', 'done', 'error'],
                default      => ['current', 'pending', 'pending'],
            };
            $lastIcon = match($job->status) {
                'completed' => 'heroicon-o-check-circle',
                'failed'    => 'heroicon-o-x-circle',
                'cancelled' => 'heroicon-o-no-symbol',
                default     => 'heroicon-o-printer',
            };
            $lastLabel = match($job->status) {
                'failed'    => 'Fehlgeschlagen',
                'cancelled' => 'Abgebrochen',
                default     => 'Gedruckt',
            };
            $steps = [
                ['label' => 'Erstellt',   'icon' => 'heroicon-o-document-plus',   'time' => $job->created_at?->format('d.m.Y H:i')],
                ['label' => 'An Drucker', 'icon' => 'heroicon-o-paper-airplane',  'time' => null],
                ['label' => $lastLabel,   'icon' => $lastIcon,                    'time' => $job->printed_at?->format('d.m.Y H:i')],
            ];
        @endphp
        <x-nx-card>
            {{-- Knoten + Verbinder. Die Farbe steckt im Inhalt, der Rahmen
                 bleibt neutral – dieselbe Aufteilung wie bei den nx-Kacheln. --}}
            <div class="flex items-center">
                @foreach($steps as $i => $step)
                    @php
                        $nodeStyle = match($stepStates[$i]) {
                            'done'    => 'background:var(--nx-success);color:#fff;border-color:transparent',
                            'current' => 'background:var(--nx-accent);color:var(--nx-on-accent);border-color:transparent',
                            'error'   => 'background:var(--nx-danger);color:#fff;border-color:transparent',
                            default   => 'background:var(--nx-surface);color:var(--nx-faint);border-color:var(--nx-line-strong)',
                        };
                        $verbinder = $stepStates[$i] === 'done' ? 'background:var(--nx-success)' : 'background:var(--nx-line)';
                    @endphp
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border-2 transition-colors" style="{{ $nodeStyle }}">
                        @svg($step['icon'], 'w-5 h-5')
                    </div>
                    @if(!$loop->last)
                        <div class="mx-1.5 h-0.5 flex-1 rounded-full" style="{{ $verbinder }}"></div>
                    @endif
                @endforeach
            </div>
            {{-- Beschriftungen --}}
            <div class="mt-3 flex">
                @foreach($steps as $i => $step)
                    <div class="flex-1 {{ $loop->first ? 'text-left' : ($loop->last ? 'text-right' : 'text-center') }}">
                        <div class="text-xs font-medium {{ in_array($stepStates[$i], ['done','current','error']) ? 'text-[color:var(--nx-text)]' : 'text-[color:var(--nx-faint)]' }}">{{ $step['label'] }}</div>
                        <div class="text-[11px] tabular-nums text-[color:var(--nx-faint)]">{{ $step['time'] ?? '—' }}</div>
                    </div>
                @endforeach
            </div>
            @if($job->retry_count > 0)
                <div class="mt-5 flex items-center gap-2 border-t border-[color:var(--nx-line)] pt-4 text-xs text-[color:var(--nx-muted)]">
                    @svg('heroicon-o-arrow-path', 'w-3.5 h-3.5')
                    {{ $job->retry_count }} Wiederholung(en) · max. {{ config('printing.jobs.max_retries', 3) }}
                </div>
            @endif
        </x-nx-card>

        {{-- Fehlermeldung --}}
        @if($job->error_message)
            <x-nx-callout variant="danger" title="Fehlermeldung">{{ $job->error_message }}</x-nx-callout>
        @endif

        {{-- Vorschau --}}
        @php $hatVorschau = ! $previewError && $belege !== []; @endphp
        <x-nx-card flush x-data="bonBild({{ $job->id }})">
            <header class="flex items-center justify-between gap-3 border-b border-[color:var(--nx-line)] px-4 py-3">
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        @svg('heroicon-o-document-magnifying-glass', 'w-4 h-4 text-[color:var(--nx-muted)]')
                        <h2 class="m-0 text-xs font-semibold text-[color:var(--nx-muted)]">Vorschau</h2>
                    </div>
                    <div class="mt-0.5 text-xs text-[color:var(--nx-faint)]">Inhalt, der an den Drucker gesendet wird · Template: {{ config("printing.templates.available.{$job->template}", $job->template) }}</div>
                </div>
                <div class="flex shrink-0 items-center gap-2">
                    @if($hatVorschau)
                        <x-nx-button type="button" @click="alsBild()">
                            @svg('heroicon-o-arrow-down-tray', 'w-4 h-4')
                            <span>Als Bild</span>
                        </x-nx-button>
                        @if(count($belege) > 1)
                            {{-- Ein Bild je Beleg: zum Weiterreichen einzelner
                                 Bons aus einem Sammelauftrag. --}}
                            <x-nx-button type="button" @click="alsBilder()">
                                @svg('heroicon-o-arrow-down-tray', 'w-4 h-4')
                                <span>{{ count($belege) }} Bilder einzeln</span>
                            </x-nx-button>
                        @endif
                    @endif
                    <x-nx-button icon variant="ghost" wire:click="reloadPreview" title="Aktualisieren">
                        @svg('heroicon-o-arrow-path', 'w-4 h-4')
                    </x-nx-button>
                </div>
            </header>
            <div class="p-4">
                @if($previewError)
                    <x-nx-callout variant="danger">Vorschau konnte nicht erzeugt werden: {{ $previewError }}</x-nx-callout>
                @elseif($belege === [])
                    <x-nx-empty icon="heroicon-o-document-magnifying-glass">Kein Inhalt vorhanden.</x-nx-empty>
                @else
                    {{-- Ein Blatt je Beleg: Ein Sammelauftrag enthält mehrere
                         Bons, zwischen denen der Drucker abschneidet. Genauso
                         liegen sie hier untereinander – was aus dem Gerät
                         kommt, ist auch hier ein eigenes Stück Papier.

                         Das Blatt richtet sich nach dem Bon, nicht nach dem
                         Fenster: w-max/whitespace-pre lässt die Trennlinien in
                         einer Zeile bis zum Rand laufen statt sie umzubrechen,
                         items-start gibt dem Papier die Höhe seines Inhalts
                         statt der Höhe des Scroll-Bereichs. Nur Darstellung –
                         der gedruckte Inhalt bleibt unverändert. --}}
                    <div x-ref="papiere" class="flex max-h-96 flex-col items-center gap-5 overflow-auto rounded-[8px] border border-[color:var(--nx-line)] bg-[color:var(--nx-bg)] py-5">
                        @foreach($belege as $beleg)
                            <div class="w-max shrink-0">
                                @if(count($belege) > 1)
                                    <div class="mb-1 text-center text-[11px] text-[color:var(--nx-faint)]">Beleg {{ $loop->iteration }} von {{ count($belege) }}</div>
                                @endif
                                <pre class="whitespace-pre rounded-sm bg-[color:var(--nx-surface)] px-5 py-4 font-mono text-[11px] leading-relaxed text-[color:var(--nx-text)] shadow-[var(--nx-shadow-card)]">{{ $beleg }}</pre>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </x-nx-card>

        {{-- Informationen --}}
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <x-nx-card flush>
                <div class="flex items-center gap-2 border-b border-[color:var(--nx-line)] px-4 py-3">
                    @svg('heroicon-o-information-circle', 'w-4 h-4 text-[color:var(--nx-muted)]')
                    <h2 class="m-0 text-xs font-semibold text-[color:var(--nx-muted)]">Job-Informationen</h2>
                </div>
                <div class="p-2">
                    <x-nx-property-row icon="heroicon-o-document-text" label="Template">
                        {{ config("printing.templates.available.{$job->template}", $job->template) }}
                    </x-nx-property-row>
                    <x-nx-property-row icon="heroicon-o-hashtag" label="UUID">
                        <span class="block truncate font-mono text-xs">{{ $job->uuid }}</span>
                    </x-nx-property-row>
                    <x-nx-property-row icon="heroicon-o-calendar" label="Erstellt">
                        <span class="tabular-nums">{{ $job->created_at->format('d.m.Y H:i:s') }}</span>
                    </x-nx-property-row>
                    @php $angeboten = ($job->data ?? [])['angeboten_um'] ?? null; @endphp
                    @if($angeboten)
                        <x-nx-property-row icon="heroicon-o-megaphone" label="Gemeldet">
                            <span class="tabular-nums">{{ \Illuminate\Support\Carbon::parse($angeboten)->format('d.m.Y H:i:s') }}</span>
                            <span class="text-xs text-[color:var(--nx-faint)]">(+{{ round(abs(\Illuminate\Support\Carbon::parse($angeboten)->diffInSeconds($job->created_at))) }}s)</span>
                        </x-nx-property-row>
                    @endif
                    @if($job->fetched_at)
                        <x-nx-property-row icon="heroicon-o-arrow-down-tray" label="Abgeholt">
                            <span class="tabular-nums">{{ $job->fetched_at->format('d.m.Y H:i:s') }}</span>
                            <span class="text-xs text-[color:var(--nx-faint)]">(+{{ round(abs($job->fetched_at->diffInSeconds($job->created_at))) }}s)</span>
                        </x-nx-property-row>
                    @endif
                    @if($job->printed_at)
                        <x-nx-property-row icon="heroicon-o-printer" label="Gedruckt">
                            <span class="tabular-nums">{{ $job->printed_at->format('d.m.Y H:i:s') }}</span>
                        </x-nx-property-row>
                    @endif
                    <x-nx-property-row icon="heroicon-o-arrow-path" label="Versuche">
                        <span class="tabular-nums">{{ $job->retry_count }}</span>
                    </x-nx-property-row>
                </div>
            </x-nx-card>

            <x-nx-card flush>
                <div class="flex items-center gap-2 border-b border-[color:var(--nx-line)] px-4 py-3">
                    @svg('heroicon-o-map-pin', 'w-4 h-4 text-[color:var(--nx-muted)]')
                    <h2 class="m-0 text-xs font-semibold text-[color:var(--nx-muted)]">Ziel</h2>
                </div>
                <div class="p-2">
                    <x-nx-property-row icon="heroicon-o-printer" label="Drucker">
                        @if($job->printer)
                            <a href="{{ route('printing.printers.show', $job->printer) }}" wire:navigate class="hover:underline">{{ $job->printer->name }}</a>
                        @elseif($job->printerGroup)
                            <a href="{{ route('printing.groups.show', $job->printerGroup) }}" wire:navigate class="hover:underline">Gruppe: {{ $job->printerGroup->name }}</a>
                        @else
                            <span class="text-[color:var(--nx-faint)]">Nicht zugewiesen</span>
                        @endif
                    </x-nx-property-row>
                    <x-nx-property-row icon="heroicon-o-cube" label="Objekt">
                        {{ $job->printable_name }} #{{ $job->printable_id }}
                    </x-nx-property-row>
                    <x-nx-property-row icon="heroicon-o-code-bracket" label="Objekt-Typ">
                        <span class="block truncate font-mono text-xs text-[color:var(--nx-muted)]">{{ $job->printable_type }}</span>
                    </x-nx-property-row>
                    @if($job->user)
                        <x-nx-property-row icon="heroicon-o-user" label="Erstellt von">
                            {{ $job->user->name }}
                        </x-nx-property-row>
                    @endif
                </div>
            </x-nx-card>
        </div>

        {{-- Job-Daten --}}
        @if($job->data)
            <x-nx-section icon="heroicon-o-code-bracket-square" title="Job-Daten" description="Die Rohdaten, aus denen die Vorschau erzeugt wird">
                <x-nx-card flush>
                    <div class="max-h-96 overflow-auto rounded-[8px]">
                        <pre class="m-0 p-4 font-mono text-xs text-[color:var(--nx-text)]">{{ json_encode($job->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    </div>
                </x-nx-card>
            </x-nx-section>
        @endif

        <div class="flex justify-end">
            <x-nx-button :href="route('printing.jobs.index')" wire:navigate>
                @svg('heroicon-o-arrow-left', 'w-4 h-4')
                <span>Zurück zur Übersicht</span>
            </x-nx-button>
        </div>

    </div>
    </x-ui-page-container>
</x-ui-page>
@script
<script>
// Bon als Bild: Der Beleg ist reiner Text in fester Zeichenbreite, deshalb
// wird er Zeile für Zeile auf ein Canvas gezeichnet statt mit einer
// Screenshot-Bibliothek abfotografiert. Das spart eine Abhängigkeit, liefert
// ein scharfes Bild in beliebiger Auflösung und ist unabhängig davon, wie die
// Vorschau gerade im Fenster skaliert oder gescrollt ist.
//
// Ein Sammelauftrag lässt sich auf zwei Arten mitnehmen: alle Blätter
// untereinander auf einem Bild - oder je Beleg eine Datei, wenn einzelne Bons
// an verschiedene Leute gehen.
Alpine.data('bonBild', (jobId) => ({
    /** Die Belege der Vorschau als Zeilen-Listen. */
    belege() {
        const bloecke = this.$refs.papiere
            ? Array.from(this.$refs.papiere.querySelectorAll('pre'))
            : [];

        // Steuerzeichen (ESC/POS) gehören zum Druckstrom, nicht aufs Bild.
        return bloecke.map((pre) => (pre.textContent || '')
            .replace(/[\u0000-\u0008\u000B-\u001F\u007F]/g, '')
            .replace(/\s+$/, '')
            .split('\n'));
    },

    /**
     * Zeichnet die übergebenen Belege untereinander auf ein Canvas.
     *
     * Alle Blätter bekommen dieselbe Breite - die der längsten Zeile -, damit
     * sie untereinander bündig stehen. Der graue Grund liegt nur in den
     * Lücken frei; bei einem einzelnen Beleg deckt das Papier ihn vollständig.
     */
    zeichne(belege) {
        const schrift     = 14;                        // CSS-Pixel
        const zeilenhoehe = Math.round(schrift * 1.5);
        const rand        = 28;
        const luecke      = 24;                        // Abstand zwischen zwei Belegen
        const skala       = Math.max(2, Math.ceil(window.devicePixelRatio || 1));
        const font        = schrift + 'px ui-monospace, SFMono-Regular, Menlo, Consolas, monospace';

        // Breite aus der längsten Zeile: so endet das Papier genau dort, wo die
        // Trennlinien enden - wie beim echten Bon.
        const mass = document.createElement('canvas').getContext('2d');
        mass.font = font;
        const textbreite = belege.reduce(
            (max, zeilen) => zeilen.reduce((m, z) => Math.max(m, mass.measureText(z).width), max),
            0
        );

        const breite = Math.ceil(textbreite) + rand * 2;
        const hoehen = belege.map((zeilen) => zeilen.length * zeilenhoehe + rand * 2);
        const hoehe  = hoehen.reduce((summe, h) => summe + h, 0) + luecke * (belege.length - 1);

        const canvas  = document.createElement('canvas');
        canvas.width  = breite * skala;
        canvas.height = hoehe * skala;

        const ctx = canvas.getContext('2d');
        ctx.scale(skala, skala);
        ctx.font = font;
        ctx.textBaseline = 'top';

        ctx.fillStyle = '#e5e7eb';
        ctx.fillRect(0, 0, breite, hoehe);

        let y = 0;
        belege.forEach((zeilen, i) => {
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, y, breite, hoehen[i]);

            ctx.fillStyle = '#111111';
            zeilen.forEach((z, k) => ctx.fillText(z, rand, y + rand + k * zeilenhoehe));

            y += hoehen[i] + luecke;
        });

        return canvas;
    },

    speichere(canvas, name) {
        canvas.toBlob((blob) => {
            if (! blob) return;

            const url  = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href     = url;
            link.download = name;
            document.body.appendChild(link);
            link.click();
            link.remove();
            URL.revokeObjectURL(url);
        }, 'image/png');
    },

    /** Alles auf einem Bild. */
    alsBild() {
        const belege = this.belege();
        if (! belege.length) return;

        this.speichere(this.zeichne(belege), 'bon-job-' + jobId + '.png');
    },

    /**
     * Je Beleg eine Datei.
     *
     * Die Downloads laufen zeitversetzt: Browser werten einen Schwall
     * gleichzeitiger Downloads als aufdringlich und lassen still nur den
     * ersten durch. Die führende Null hält die Dateien im Ordner in der
     * Reihenfolge des Bons.
     */
    alsBilder() {
        const belege = this.belege();
        if (! belege.length) return;

        belege.forEach((zeilen, i) => {
            const nummer = String(i + 1).padStart(2, '0');

            window.setTimeout(
                () => this.speichere(this.zeichne([zeilen]), 'bon-job-' + jobId + '-beleg-' + nummer + '.png'),
                i * 350
            );
        });
    },
}));
</script>
@endscript

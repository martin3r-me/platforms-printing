<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Print Jobs" icon="heroicon-o-document-text" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Printing', 'href' => route('printing.dashboard'), 'icon' => 'printer'],
            ['label' => 'Print Jobs'],
        ]" />
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

    {{-- Kennzahlen --}}
    <x-nx-stat-grid>
        <x-nx-stat label="Gesamt" :value="(string) $stats['total']" icon="heroicon-o-document-text" accent="var(--nx-accent)" />
        <x-nx-stat label="Wartend" :value="(string) $stats['pending']" icon="heroicon-o-clock"
            :accent="$stats['pending'] > 0 ? 'var(--nx-warning)' : 'var(--nx-muted)'" />
        <x-nx-stat label="Abgeschlossen" :value="(string) $stats['completed']" icon="heroicon-o-check-circle" accent="var(--nx-success)" />
        <x-nx-stat label="Fehlgeschlagen" :value="(string) $stats['failed']" icon="heroicon-o-x-circle"
            :accent="$stats['failed'] > 0 ? 'var(--nx-danger)' : 'var(--nx-muted)'" />
    </x-nx-stat-grid>

    {{-- Filter: rahmenlos, luftig. Stand bisher in einer ausklappbaren Spalte
         links – drei Zustände und ein Suchfeld brauchen keine eigene Spalte,
         und zugeklappt sah die Liste ungefiltert aus, obwohl sie es nicht
         war. --}}
    <div class="flex flex-wrap items-center gap-x-5 gap-y-2 text-xs">
        <div class="flex flex-wrap items-center gap-1">
            @foreach (['all' => 'Alle', 'pending' => 'Wartend', 'processing' => 'In Bearbeitung', 'completed' => 'Gedruckt', 'failed' => 'Fehlgeschlagen', 'cancelled' => 'Abgebrochen'] as $val => $label)
                <button type="button" wire:click="$set('statusFilter', '{{ $val }}')"
                    class="rounded-full px-2.5 py-1 transition-colors {{ $statusFilter === $val ? 'bg-[color:var(--nx-active)] font-medium text-[color:var(--nx-text)]' : 'text-[color:var(--nx-muted)] hover:bg-[color:var(--nx-hover)]' }}">{{ $label }}</button>
            @endforeach
        </div>
        <div class="ml-auto w-64">
            <x-ui-input-text name="search" size="sm" wire:model.live.debounce.300ms="search" placeholder="Template, UUID…" />
        </div>
    </div>

    {{-- Tabelle: rahmenlos, Hairlines --}}
    <x-nx-table>
        <x-nx-table-header>
            <x-nx-table-header-cell>Template</x-nx-table-header-cell>
            <x-nx-table-header-cell>Status</x-nx-table-header-cell>
            <x-nx-table-header-cell>Ziel</x-nx-table-header-cell>
            <x-nx-table-header-cell>Erstellt</x-nx-table-header-cell>
            <x-nx-table-header-cell align="right"><span class="sr-only">Aktionen</span></x-nx-table-header-cell>
        </x-nx-table-header>
        <x-nx-table-body>
            @forelse($jobs as $job)
                <x-nx-table-row wire:key="job-{{ $job->id }}" clickable
                    :href="route('printing.jobs.show', ['job' => $job->id])" class="group">
                    <x-nx-table-cell>
                        <span class="font-medium text-[color:var(--nx-text)]">{{ $job->template }}</span>
                        <span class="block font-mono text-xs text-[color:var(--nx-faint)]">{{ $job->uuid }}</span>
                    </x-nx-table-cell>
                    <x-nx-table-cell>
                        <x-nx-badge :variant="$job->status_color">{{ $job->status_description }}</x-nx-badge>
                    </x-nx-table-cell>
                    <x-nx-table-cell class="text-[color:var(--nx-muted)]">
                        {{ $job->printable_name }} #{{ $job->printable_id }}
                        @if($job->printer)
                            <span class="block text-xs text-[color:var(--nx-faint)]">{{ $job->printer->name }}</span>
                        @elseif($job->printerGroup)
                            <span class="block text-xs text-[color:var(--nx-faint)]">Gruppe: {{ $job->printerGroup->name }}</span>
                        @endif
                    </x-nx-table-cell>
                    <x-nx-table-cell class="whitespace-nowrap text-[color:var(--nx-muted)]">
                        <span class="tabular-nums">{{ $job->created_at->format('d.m.Y') }}</span>
                        <span class="block text-xs tabular-nums text-[color:var(--nx-faint)]">{{ $job->created_at->format('H:i') }} Uhr</span>
                    </x-nx-table-cell>
                    {{-- Aktionen erscheinen beim Hover über die Zeile (Notion-Stil).
                         .stop ist zwingend: die Zeile trägt ein
                         onclick="window.location.href=…", ohne das blubbert
                         jeder Klick hoch und die Seite navigiert weg, bevor
                         Livewire den Auftrag angefasst hat. --}}
                    <x-nx-table-cell align="right">
                        <div class="flex items-center justify-end gap-0.5 opacity-0 transition-opacity duration-150 group-hover:opacity-100 focus-within:opacity-100">
                            @if($job->status === 'failed')
                                <x-nx-button icon variant="ghost" title="Wiederholen"
                                    x-on:click.stop.prevent="$wire.retryJob({{ $job->id }})">
                                    @svg('heroicon-o-arrow-path', 'w-4 h-4')
                                </x-nx-button>
                            @endif
                            @if(in_array($job->status, ['pending', 'processing']))
                                <button type="button" title="Abbrechen"
                                    x-on:click.stop.prevent="$wire.cancelJob({{ $job->id }})"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-[6px] text-[color:var(--nx-danger)] transition-colors hover:bg-[rgba(224,49,49,.08)]">
                                    @svg('heroicon-o-x-mark', 'w-4 h-4')
                                </button>
                            @endif
                        </div>
                    </x-nx-table-cell>
                </x-nx-table-row>
            @empty
                <tr>
                    <td colspan="5">
                        <x-nx-empty icon="heroicon-o-queue-list">
                            Keine Aufträge gefunden
                        </x-nx-empty>
                    </td>
                </tr>
            @endforelse
        </x-nx-table-body>
    </x-nx-table>

    <div class="flex flex-wrap items-center justify-between gap-3 text-xs text-[color:var(--nx-faint)]">
        <span class="tabular-nums">
            @if ($jobs->hasPages())
                {{ $jobs->firstItem() }}–{{ $jobs->lastItem() }} von {{ $jobs->total() }} Aufträgen
            @else
                {{ $jobs->total() }} {{ $jobs->total() === 1 ? 'Auftrag' : 'Aufträge' }}
            @endif
        </span>
        {{ $jobs->links('printing::partials.pagination') }}
    </div>

    </div>
    </x-ui-page-container>
</x-ui-page>

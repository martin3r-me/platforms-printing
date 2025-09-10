<div>
    <div>
        <h1>Printing</h1>
        <p>Übersicht über alle Drucker und Print Jobs</p>
    </div>

    <div>
        <x-ui-dashboard-tile title="Drucker" :count="$totalPrinters" subtitle="{{ $activePrinters }} aktiv" variant="primary" size="lg" />
        <x-ui-dashboard-tile title="Gruppen" :count="$totalGroups" subtitle="{{ $activeGroups }} aktiv" variant="secondary" size="lg" />
        <x-ui-dashboard-tile title="Print Jobs" :count="$totalJobs" subtitle="{{ $pendingJobs }} wartend" variant="info" size="lg" />
        <x-ui-dashboard-tile title="Abgeschlossen" :count="$completedJobs" subtitle="{{ $failedJobs }} fehlgeschlagen" variant="success" size="lg" />
    </div>

    <div>
        <x-ui-dashboard-tile title="Bereit" :count="$printerStatus['ready']" variant="success" size="sm" />
        <x-ui-dashboard-tile title="Beschäftigt" :count="$printerStatus['busy']" variant="warning" size="sm" />
        <x-ui-dashboard-tile title="Fehler" :count="$printerStatus['error']" variant="danger" size="sm" />
    </div>

    <div>
        <h3>Neueste Print Jobs</h3>
        @if($recentJobs->count() > 0)
            <x-ui-table>
                <x-ui-table-header>
                    <x-ui-table-header-cell>Template</x-ui-table-header-cell>
                    <x-ui-table-header-cell>Status</x-ui-table-header-cell>
                    <x-ui-table-header-cell>Ziel</x-ui-table-header-cell>
                    <x-ui-table-header-cell>Erstellt</x-ui-table-header-cell>
                </x-ui-table-header>

                <x-ui-table-body>
                    @foreach($recentJobs as $job)
                        <x-ui-table-row>
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
        @else
            <div>Keine Print Jobs gefunden</div>
        @endif
    </div>
</div>
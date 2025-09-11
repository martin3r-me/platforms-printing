<div class="h-full overflow-y-auto p-6">
    <!-- Header mit Datum und Perspektive-Toggle -->
    <div class="mb-6">
        <div class="d-flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Printing Dashboard</h1>
                <p class="text-gray-600">{{ $currentDay }}, {{ $currentDate }}</p>
            </div>
            <div class="d-flex items-center gap-4">
                <!-- Perspektive-Toggle -->
                <div class="d-flex bg-gray-100 rounded-lg p-1">
                    <button 
                        wire:click="$set('perspective', 'personal')"
                        class="px-4 py-2 rounded-md text-sm font-medium transition"
                        :class="'{{ $perspective }}' === 'personal' 
                            ? 'bg-success text-on-success shadow-sm' 
                            : 'text-gray-600 hover:text-gray-900'"
                    >
                        <div class="d-flex items-center gap-2">
                            @svg('heroicon-o-user', 'w-4 h-4')
                            <span>Persönlich</span>
                        </div>
                    </button>
                    <button 
                        wire:click="$set('perspective', 'team')"
                        class="px-4 py-2 rounded-md text-sm font-medium transition"
                        :class="'{{ $perspective }}' === 'team' 
                            ? 'bg-success text-on-success shadow-sm' 
                            : 'text-gray-600 hover:text-gray-900'"
                    >
                        <div class="d-flex items-center gap-2">
                            @svg('heroicon-o-users', 'w-4 h-4')
                            <span>Team</span>
                        </div>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Perspektive-spezifische Info -->
    @if($perspective === 'personal')
        <div class="mb-4">
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="d-flex items-center gap-2 mb-2">
                    @svg('heroicon-o-user', 'w-5 h-5 text-blue-600')
                    <h3 class="text-lg font-semibold text-blue-900">Persönliche Druck-Übersicht</h3>
                </div>
                <p class="text-blue-700 text-sm">Deine eigenen Aufträge und dir zugewiesene Drucker.</p>
            </div>
        </div>
    @else
        <div class="mb-4">
            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <div class="d-flex items-center gap-2 mb-2">
                    @svg('heroicon-o-users', 'w-5 h-5 text-green-600')
                    <h3 class="text-lg font-semibold text-green-900">Team-Übersicht</h3>
                </div>
                <p class="text-green-700 text-sm">Alle Drucker und Jobs des Teams im Blick.</p>
            </div>
        </div>
    @endif

    <!-- Oberes Kennzahlen-Grid (3er) -->
    <div class="grid grid-cols-3 gap-4 mb-8">
        <x-ui-dashboard-tile
            title="Drucker"
            :count="$totalPrinters"
            subtitle="aktiv: {{ $activePrinters }}"
            icon="printer"
            variant="primary"
            size="lg"
        />

        <x-ui-dashboard-tile
            title="Gruppen"
            :count="$totalGroups"
            subtitle="aktiv: {{ $activeGroups }}"
            icon="folder"
            variant="secondary"
            size="lg"
        />

        <x-ui-dashboard-tile
            title="Print Jobs"
            :count="$totalJobs"
            subtitle="wartend: {{ $pendingJobs }}"
            icon="document-text"
            variant="warning"
            size="lg"
        />
    </div>

    <!-- Job-Status-Übersicht (kleine Kacheln) -->
    <div class="grid grid-cols-4 gap-4 mb-8">
        <x-ui-dashboard-tile
            title="Bereit"
            :count="$printerStatus['ready']"
            icon="check-circle"
            variant="success"
            size="sm"
        />
        <x-ui-dashboard-tile
            title="Beschäftigt"
            :count="$printerStatus['busy']"
            icon="clock"
            variant="warning"
            size="sm"
        />
        <x-ui-dashboard-tile
            title="Fehler"
            :count="$printerStatus['error']"
            icon="x-circle"
            variant="danger"
            size="sm"
        />
        <x-ui-dashboard-tile
            title="Abgeschlossen"
            :count="$completedJobs"
            icon="check"
            variant="success"
            size="sm"
        />
    </div>

    <!-- Neueste Jobs Tabelle -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="p-6 border-b border-gray-200">
            <div class="d-flex items-center gap-2">
                <x-heroicon-o-queue-list class="w-5 h-5 text-gray-700"/>
                <h3 class="text-lg font-semibold text-gray-900">Neueste Print Jobs</h3>
                <x-ui-badge variant="neutral" size="sm">{{ $recentJobs->count() }}</x-ui-badge>
            </div>
            <p class="text-sm text-gray-600 mt-1">Die letzten 10 Aufträge in deinem Team</p>
        </div>
        <div class="p-6">
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
                <div class="text-center py-8 text-gray-600">Keine Print Jobs gefunden</div>
            @endif
        </div>
    </div>
</div>
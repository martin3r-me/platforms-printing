<div class="h-full overflow-y-auto p-6">
    <!-- Header mit Datum -->
    <div class="mb-6">
        <div class="d-flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Printing Service Dashboard</h1>
                <p class="text-gray-600">{{ now()->format('l, d.m.Y') }}</p>
            </div>
            <div class="d-flex items-center gap-4">
                <!-- Perspektive-Toggle -->
                <div class="d-flex bg-gray-100 rounded-lg p-1">
                    <button 
                        wire:click="$set('perspective', 'overview')"
                        class="px-4 py-2 rounded-md text-sm font-medium transition"
                        :class="'{{ $perspective ?? 'overview' }}' === 'overview' 
                            ? 'bg-success text-on-success shadow-sm' 
                            : 'text-gray-600 hover:text-gray-900'"
                    >
                        <div class="d-flex items-center gap-2">
                            @svg('heroicon-o-printer', 'w-4 h-4')
                            <span>Übersicht</span>
                        </div>
                    </button>
                    <button 
                        wire:click="$set('perspective', 'jobs')"
                        class="px-4 py-2 rounded-md text-sm font-medium transition"
                        :class="'{{ $perspective ?? 'overview' }}' === 'jobs' 
                            ? 'bg-success text-on-success shadow-sm' 
                            : 'text-gray-600 hover:text-gray-900'"
                    >
                        <div class="d-flex items-center gap-2">
                            @svg('heroicon-o-document-text', 'w-4 h-4')
                            <span>Print Jobs</span>
                        </div>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Perspektive-spezifische Statistiken -->
    @if(($perspective ?? 'overview') === 'overview')
        <!-- Übersicht-Perspektive -->
        <div class="mb-4">
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="d-flex items-center gap-2 mb-2">
                    @svg('heroicon-o-printer', 'w-5 h-5 text-blue-600')
                    <h3 class="text-lg font-semibold text-blue-900">Printing Service Übersicht</h3>
                </div>
                <p class="text-blue-700 text-sm">Verwaltung von Druckern, Gruppen und Print Jobs im System.</p>
            </div>
        </div>
    @else
        <!-- Jobs-Perspektive -->
        <div class="mb-4">
            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <div class="d-flex items-center gap-2 mb-2">
                    @svg('heroicon-o-document-text', 'w-5 h-5 text-green-600')
                    <h3 class="text-lg font-semibold text-green-900">Print Jobs Übersicht</h3>
                </div>
                <p class="text-green-700 text-sm">Verwaltung und Überwachung aller Print Jobs.</p>
            </div>
        </div>
    @endif

    <!-- Wichtige Statistiken (2er Grid) -->
    <div class="grid grid-cols-2 gap-4 mb-8">
        <!-- Aktive Drucker -->
        <x-ui-dashboard-tile
            title="Aktive Drucker"
            :count="$activePrinters"
            subtitle="von {{ $totalPrinters }} Druckern"
            icon="printer"
            variant="primary"
            size="lg"
        />
        
        <!-- Wartende Jobs -->
        <x-ui-dashboard-tile
            title="Wartende Jobs"
            :count="$pendingJobs"
            subtitle="bereit zum Drucken"
            icon="clock"
            variant="warning"
            size="lg"
        />
    </div>

    <!-- Drucker-Status (3er Grid) -->
    <div class="grid grid-cols-3 gap-4 mb-8">
        <!-- Bereite Drucker -->
        <x-ui-dashboard-tile
            title="Bereit"
            :count="$printerStatus['ready']"
            subtitle="Drucker verfügbar"
            icon="check-circle"
            variant="success"
            size="lg"
        />
        
        <!-- Beschäftigte Drucker -->
        <x-ui-dashboard-tile
            title="Beschäftigt"
            :count="$printerStatus['busy']"
            subtitle="Drucker arbeiten"
            icon="clock"
            variant="warning"
            size="lg"
        />
        
        <!-- Fehlerhafte Drucker -->
        <x-ui-dashboard-tile
            title="Fehler"
            :count="$printerStatus['error']"
            subtitle="Drucker mit Problemen"
            icon="exclamation-triangle"
            variant="danger"
            size="lg"
        />
    </div>

    <!-- Job-Statistiken (4er Grid) -->
    <div class="grid grid-cols-4 gap-4 mb-8">
        <!-- Gesamt Jobs -->
        <x-ui-dashboard-tile
            title="Gesamt Jobs"
            :count="$totalJobs"
            subtitle="alle Print Jobs"
            icon="document-text"
            variant="neutral"
            size="lg"
        />
        
        <!-- Abgeschlossene Jobs -->
        <x-ui-dashboard-tile
            title="Abgeschlossen"
            :count="$completedJobs"
            subtitle="erfolgreich gedruckt"
            icon="check-circle"
            variant="success"
            size="lg"
        />
        
        <!-- Fehlgeschlagene Jobs -->
        <x-ui-dashboard-tile
            title="Fehlgeschlagen"
            :count="$failedJobs"
            subtitle="mit Fehlern"
            icon="x-circle"
            variant="danger"
            size="lg"
        />
        
        <!-- Gruppen -->
        <x-ui-dashboard-tile
            title="Drucker-Gruppen"
            :count="$activeGroups"
            subtitle="von {{ $totalGroups }} Gruppen"
            icon="user-group"
            variant="info"
            size="lg"
        />
    </div>

    <!-- Neueste Print Jobs -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="p-6 border-b border-gray-200">
            <div class="d-flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Neueste Print Jobs</h3>
                    <p class="text-sm text-gray-600 mt-1">Zuletzt erstellte oder verarbeitete Jobs</p>
                </div>
                <a href="{{ route('printing.jobs.index') }}" 
                   class="inline-flex items-center gap-2 px-3 py-2 bg-primary text-on-primary rounded-md hover:bg-primary-dark transition text-sm"
                   wire:navigate>
                    <div class="d-flex items-center gap-2">
                        @svg('heroicon-o-arrow-right', 'w-4 h-4')
                        <span>Alle Jobs</span>
                    </div>
                </a>
            </div>
        </div>
        
        <div class="p-6">
            @if($recentJobs->count() > 0)
                <div class="space-y-4">
                    @foreach($recentJobs as $job)
                        <div class="d-flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                            <div class="d-flex items-center gap-4">
                                <div class="w-10 h-10 rounded-lg d-flex items-center justify-center
                                    @if($job->status === 'completed') bg-green-100 text-green-600
                                    @elseif($job->status === 'failed') bg-red-100 text-red-600
                                    @elseif($job->status === 'processing') bg-blue-100 text-blue-600
                                    @elseif($job->status === 'pending') bg-yellow-100 text-yellow-600
                                    @else bg-gray-100 text-gray-600
                                    @endif">
                                    @if($job->status === 'completed')
                                        @svg('heroicon-o-check-circle', 'w-5 h-5')
                                    @elseif($job->status === 'failed')
                                        @svg('heroicon-o-x-circle', 'w-5 h-5')
                                    @elseif($job->status === 'processing')
                                        @svg('heroicon-o-clock', 'w-5 h-5')
                                    @elseif($job->status === 'pending')
                                        @svg('heroicon-o-clock', 'w-5 h-5')
                                    @else
                                        @svg('heroicon-o-document-text', 'w-5 h-5')
                                    @endif
                                </div>
                                <div>
                                    <h4 class="font-medium text-gray-900">{{ $job->description }}</h4>
                                    <p class="text-sm text-gray-600">
                                        {{ $job->status_description }} • 
                                        @if($job->printer)
                                            Drucker: {{ $job->printer->name }}
                                        @elseif($job->printerGroup)
                                            Gruppe: {{ $job->printerGroup->name }}
                                        @endif
                                        • {{ $job->created_at->diffForHumans() }}
                                    </p>
                                </div>
                            </div>
                            <div class="d-flex items-center gap-2">
                                <x-ui-badge :variant="$job->status_color" size="sm">
                                    {{ $job->status_description }}
                                </x-ui-badge>
                                <a href="{{ route('printing.jobs.show', $job) }}" 
                                   class="inline-flex items-center gap-2 px-3 py-2 bg-primary text-on-primary rounded-md hover:bg-primary-dark transition text-sm"
                                   wire:navigate>
                                    <div class="d-flex items-center gap-2">
                                        @svg('heroicon-o-arrow-right', 'w-4 h-4')
                                        <span>Anzeigen</span>
                                    </div>
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8">
                    @svg('heroicon-o-document-text', 'w-12 h-12 text-gray-400 mx-auto mb-4')
                    <h4 class="text-lg font-medium text-gray-900 mb-2">Keine Print Jobs vorhanden</h4>
                    <p class="text-gray-600">Es wurden noch keine Print Jobs erstellt.</p>
                </div>
            @endif
        </div>
    </div>
</div>

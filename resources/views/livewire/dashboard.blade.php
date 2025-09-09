<div>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Printing Dashboard</h1>
        <p class="text-sm text-gray-600">Übersicht über alle Drucker und Print Jobs</p>
    </div>

    <!-- Statistiken -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Drucker</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ $totalPrinters }}</dd>
                            <dd class="text-sm text-gray-500">{{ $activePrinters }} aktiv</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Gruppen</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ $totalGroups }}</dd>
                            <dd class="text-sm text-gray-500">{{ $activeGroups }} aktiv</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <!-- Icon entfernt -->
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Print Jobs</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ $totalJobs }}</dd>
                            <dd class="text-sm text-gray-500">{{ $pendingJobs }} wartend</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Abgeschlossen</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ $completedJobs }}</dd>
                            <dd class="text-sm text-gray-500">{{ $failedJobs }} fehlgeschlagen</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Drucker Status -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Bereit</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ $printerStatus['ready'] }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Beschäftigt</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ $printerStatus['busy'] }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Fehler</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ $printerStatus['error'] }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Neueste Jobs -->
    <div class="bg-white shadow overflow-hidden sm:rounded-md">
        <div class="px-4 py-5 sm:px-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Neueste Print Jobs</h3>
            <p class="mt-1 max-w-2xl text-sm text-gray-500">Die letzten 10 Print Jobs</p>
        </div>
        <ul class="divide-y divide-gray-200">
            @forelse($recentJobs as $job)
                <li>
                    <div class="px-4 py-4 flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                @switch($job->status)
                                    @case('pending')
                                        
                                        @break
                                    @case('processing')
                                        
                                        @break
                                    @case('completed')
                                        
                                        @break
                                    @case('failed')
                                        
                                        @break
                                    @case('cancelled')
                                        
                                        @break
                                @endswitch
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-medium text-gray-900">
                                    {{ $job->template }}
                                </div>
                                <div class="text-sm text-gray-500">
                                    {{ $job->printable_type }} #{{ $job->printable_id }}
                                    @if($job->printer)
                                        • {{ $job->printer->name }}
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                @if($job->status === 'pending') bg-yellow-100 text-yellow-800
                                @elseif($job->status === 'processing') bg-blue-100 text-blue-800
                                @elseif($job->status === 'completed') bg-green-100 text-green-800
                                @elseif($job->status === 'failed') bg-red-100 text-red-800
                                @else bg-gray-100 text-gray-800
                                @endif">
                                {{ ucfirst($job->status) }}
                            </span>
                            <span class="text-xs text-gray-500">
                                {{ $job->created_at->diffForHumans() }}
                            </span>
                        </div>
                    </div>
                </li>
            @empty
                <li class="px-4 py-8 text-center text-gray-500">
                    Keine Print Jobs gefunden
                </li>
            @endforelse
        </ul>
    </div>
</div>
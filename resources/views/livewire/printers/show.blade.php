<div>
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $printer->name }}</h1>
                <p class="text-sm text-gray-600">{{ $printer->location }}</p>
            </div>
            <div class="flex items-center space-x-2">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $printer->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                    {{ $printer->is_active ? 'Aktiv' : 'Inaktiv' }}
                </span>
            </div>
        </div>
    </div>

    <!-- Statistiken -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <!-- Icon entfernt -->
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Gesamt Jobs</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ $stats['total'] }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        @svg('heroicons-clock', 'h-6 w-6 text-yellow-400')
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Wartend</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ $stats['pending'] }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        @svg('heroicons-check-circle', 'h-6 w-6 text-green-400')
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Abgeschlossen</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ $stats['completed'] }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        @svg('heroicons-x-circle', 'h-6 w-6 text-red-400')
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Fehlgeschlagen</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ $stats['failed'] }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter -->
    <div class="mb-4">
        <select wire:model.live="statusFilter" class="block w-full max-w-xs rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            <option value="all">Alle Status</option>
            <option value="pending">Wartend</option>
            <option value="processing">Verarbeitung</option>
            <option value="completed">Abgeschlossen</option>
            <option value="failed">Fehlgeschlagen</option>
            <option value="cancelled">Abgebrochen</option>
        </select>
    </div>

    <!-- Jobs Tabelle -->
    <div class="bg-white shadow overflow-hidden sm:rounded-md">
        <ul class="divide-y divide-gray-200">
            @forelse($jobs as $job)
                <li>
                    <div class="px-4 py-4 flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                @switch($job->status)
                                    @case('pending')
                                        @svg('heroicons-clock', 'h-5 w-5 text-yellow-400')
                                        @break
                                    @case('processing')
                                        @svg('heroicons-arrow-path', 'h-5 w-5 text-blue-400')
                                        @break
                                    @case('completed')
                                        @svg('heroicons-check-circle', 'h-5 w-5 text-green-400')
                                        @break
                                    @case('failed')
                                        @svg('heroicons-x-circle', 'h-5 w-5 text-red-400')
                                        @break
                                    @case('cancelled')
                                        @svg('heroicons-x-mark', 'h-5 w-5 text-gray-400')
                                        @break
                                @endswitch
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-medium text-gray-900">
                                    {{ $job->template }}
                                </div>
                                <div class="text-sm text-gray-500">
                                    {{ $job->printable_type }} #{{ $job->printable_id }}
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
                            @if($job->status === 'failed')
                                <button wire:click="retryJob({{ $job->id }})" class="text-indigo-600 hover:text-indigo-900 text-sm">
                                    Wiederholen
                                </button>
                            @endif
                            @if(in_array($job->status, ['pending', 'processing']))
                                <button wire:click="cancelJob({{ $job->id }})" class="text-red-600 hover:text-red-900 text-sm">
                                    Abbrechen
                                </button>
                            @endif
                        </div>
                    </div>
                </li>
            @empty
                <li class="px-4 py-8 text-center text-gray-500">
                    Keine Jobs gefunden
                </li>
            @endforelse
        </ul>
    </div>

    {{ $jobs->links() }}
</div>

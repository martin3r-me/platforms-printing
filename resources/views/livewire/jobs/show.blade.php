<div>
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Print Job Details</h1>
                <p class="text-sm text-gray-600">Job #{{ $job->id }}</p>
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
                    <button wire:click="retryJob" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Wiederholen
                    </button>
                @endif
                @if(in_array($job->status, ['pending', 'processing']))
                    <button wire:click="cancelJob" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-red-700 bg-red-100 hover:bg-red-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                        Abbrechen
                    </button>
                @endif
            </div>
        </div>
    </div>

    <!-- Job Details -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Basic Info -->
        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Job Informationen</h3>
                <p class="mt-1 max-w-2xl text-sm text-gray-500">Grundlegende Job-Details</p>
            </div>
            <div class="border-t border-gray-200">
                <dl>
                    <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Template</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ $job->template }}</dd>
                    </div>
                    <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">UUID</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2 font-mono">{{ $job->uuid }}</dd>
                    </div>
                    <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Erstellt</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ $job->created_at->format('d.m.Y H:i:s') }}</dd>
                    </div>
                    @if($job->printed_at)
                    <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Gedruckt</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ $job->printed_at->format('d.m.Y H:i:s') }}</dd>
                    </div>
                    @endif
                </dl>
            </div>
        </div>

        <!-- Target Info -->
        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Ziel-Informationen</h3>
                <p class="mt-1 max-w-2xl text-sm text-gray-500">Drucker und verknüpfte Objekte</p>
            </div>
            <div class="border-t border-gray-200">
                <dl>
                    <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Drucker</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            @if($job->printer)
                                {{ $job->printer->name }}
                            @elseif($job->printerGroup)
                                Gruppe: {{ $job->printerGroup->name }}
                            @else
                                Nicht zugewiesen
                            @endif
                        </dd>
                    </div>
                    <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Objekt-Typ</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ $job->printable_type }}</dd>
                    </div>
                    <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Objekt-ID</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ $job->printable_id }}</dd>
                    </div>
                    @if($job->user)
                    <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Erstellt von</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ $job->user->name }}</dd>
                    </div>
                    @endif
                </dl>
            </div>
        </div>
    </div>

    <!-- Job Data -->
    @if($job->data)
    <div class="mt-6 bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="px-4 py-5 sm:px-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Job-Daten</h3>
            <p class="mt-1 max-w-2xl text-sm text-gray-500">Die Daten, die an den Drucker gesendet werden</p>
        </div>
        <div class="border-t border-gray-200">
            <div class="px-4 py-5 sm:px-6">
                <pre class="bg-gray-50 p-4 rounded-md text-sm overflow-x-auto">{{ json_encode($job->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            </div>
        </div>
    </div>
    @endif

    <!-- Error Message -->
    @if($job->error_message)
    <div class="mt-6 bg-red-50 border border-red-200 rounded-md p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                @svg('heroicons-exclamation-triangle', 'h-5 w-5 text-red-400')
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-red-800">Fehlermeldung</h3>
                <div class="mt-2 text-sm text-red-700">
                    <p>{{ $job->error_message }}</p>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Actions -->
    <div class="mt-6 flex justify-end space-x-3">
        <button wire:click="generateContent" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            @svg('heroicons-arrow-path', 'h-4 w-4 mr-2')
            Inhalt generieren
        </button>
        <a href="{{ route('printing.jobs.index') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            Zurück zur Übersicht
        </a>
    </div>
</div>
<div class="h-full overflow-y-auto p-6">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Printing Service</h1>
        <p class="text-gray-600">Drucker und Print Jobs verwalten</p>
    </div>

    <!-- Navigation -->
    <div class="space-y-2">
        <a href="{{ route('printing.dashboard') }}" 
           class="d-flex items-center gap-3 px-4 py-3 rounded-lg text-gray-700 hover:bg-gray-100 transition {{ request()->routeIs('printing.dashboard') ? 'bg-primary text-on-primary' : '' }}"
           wire:navigate>
            @svg('heroicon-o-printer', 'w-5 h-5')
            <span>Dashboard</span>
        </a>

        <a href="{{ route('printing.printers.index') }}" 
           class="d-flex items-center gap-3 px-4 py-3 rounded-lg text-gray-700 hover:bg-gray-100 transition {{ request()->routeIs('printing.printers.*') ? 'bg-primary text-on-primary' : '' }}"
           wire:navigate>
            @svg('heroicon-o-printer', 'w-5 h-5')
            <span>Drucker</span>
        </a>

        <a href="{{ route('printing.groups.index') }}" 
           class="d-flex items-center gap-3 px-4 py-3 rounded-lg text-gray-700 hover:bg-gray-100 transition {{ request()->routeIs('printing.groups.*') ? 'bg-primary text-on-primary' : '' }}"
           wire:navigate>
            @svg('heroicon-o-user-group', 'w-5 h-5')
            <span>Gruppen</span>
        </a>

        <a href="{{ route('printing.jobs.index') }}" 
           class="d-flex items-center gap-3 px-4 py-3 rounded-lg text-gray-700 hover:bg-gray-100 transition {{ request()->routeIs('printing.jobs.*') ? 'bg-primary text-on-primary' : '' }}"
           wire:navigate>
            @svg('heroicon-o-document-text', 'w-5 h-5')
            <span>Print Jobs</span>
        </a>
    </div>

    <!-- Schnellaktionen -->
    <div class="mt-8">
        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">Schnellaktionen</h3>
        <div class="space-y-2">
            <button 
                wire:click="showCreateModal"
                class="w-full d-flex items-center gap-3 px-4 py-3 rounded-lg bg-primary text-on-primary hover:bg-primary-dark transition">
                @svg('heroicon-o-plus', 'w-5 h-5')
                <span>Neuer Drucker</span>
            </button>

            <a href="{{ route('printing.groups.index') }}" 
               class="w-full d-flex items-center gap-3 px-4 py-3 rounded-lg bg-success text-on-success hover:bg-success-dark transition"
               wire:navigate>
                @svg('heroicon-o-user-group', 'w-5 h-5')
                <span>Neue Gruppe</span>
            </a>
        </div>
    </div>
</div>

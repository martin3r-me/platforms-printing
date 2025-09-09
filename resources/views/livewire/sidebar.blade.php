<div>
    <nav class="space-y-1">
        <a href="{{ route('printing.dashboard') }}" class="group flex items-center px-2 py-2 text-sm font-medium rounded-md {{ request()->routeIs('printing.dashboard') ? 'bg-gray-100 text-gray-900' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
            @svg('heroicons-home', 'mr-3 h-5 w-5')
            Dashboard
        </a>

        <a href="{{ route('printing.printers.index') }}" class="group flex items-center px-2 py-2 text-sm font-medium rounded-md {{ request()->routeIs('printing.printers.*') ? 'bg-gray-100 text-gray-900' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
            @svg('heroicons-cake', 'mr-3 h-5 w-5')
            Drucker
        </a>

        <a href="{{ route('printing.groups.index') }}" class="group flex items-center px-2 py-2 text-sm font-medium rounded-md {{ request()->routeIs('printing.groups.*') ? 'bg-gray-100 text-gray-900' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
            @svg('heroicons-user-group', 'mr-3 h-5 w-5')
            Gruppen
        </a>

        <a href="{{ route('printing.jobs.index') }}" class="group flex items-center px-2 py-2 text-sm font-medium rounded-md {{ request()->routeIs('printing.jobs.*') ? 'bg-gray-100 text-gray-900' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
            <!-- Icon entfernt -->
            Print Jobs
        </a>
    </nav>
</div>
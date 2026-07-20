<div>
    {{-- Modul Header --}}
    <x-sidebar-module-header module-name="Printing" />

    {{-- Abschnitt: Allgemein --}}
    <x-ui-sidebar-list label="Allgemein">
        <x-ui-sidebar-item :href="route('printing.dashboard')" :active="request()->routeIs('printing.dashboard')">
            @svg('heroicon-o-chart-bar', 'w-5 h-5 shrink-0')
            <span class="ml-2 text-sm truncate">Dashboard</span>
        </x-ui-sidebar-item>

        <x-ui-sidebar-item :href="route('printing.groups.index')" :active="request()->routeIs('printing.groups.*')">
            @svg('heroicon-o-folder', 'w-5 h-5 shrink-0')
            <span class="ml-2 text-sm truncate">Gruppen</span>
        </x-ui-sidebar-item>

        <x-ui-sidebar-item :href="route('printing.printers.index')" :active="request()->routeIs('printing.printers.*')">
            @svg('heroicon-o-printer', 'w-5 h-5 shrink-0')
            <span class="ml-2 text-sm truncate">Drucker</span>
        </x-ui-sidebar-item>

        <x-ui-sidebar-item :href="route('printing.jobs.index')" :active="request()->routeIs('printing.jobs.*')">
            @svg('heroicon-o-document-text', 'w-5 h-5 shrink-0')
            <span class="ml-2 text-sm truncate">Print Jobs</span>
        </x-ui-sidebar-item>
    </x-ui-sidebar-list>

    {{-- Collapsed: Icons-only --}}
    <div x-show="collapsed" class="px-2 py-2 border-b border-[var(--ui-border)]">
        <div class="flex flex-col gap-2">
            <a href="{{ route('printing.dashboard') }}" wire:navigate
               class="flex items-center justify-center p-2 rounded-md {{ request()->routeIs('printing.dashboard') ? 'bg-[rgb(var(--ui-primary-rgb))] text-[var(--ui-on-primary)]' : 'text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)]' }}">
                @svg('heroicon-o-chart-bar', 'w-5 h-5')
            </a>
            <a href="{{ route('printing.groups.index') }}" wire:navigate
               class="flex items-center justify-center p-2 rounded-md {{ request()->routeIs('printing.groups.*') ? 'bg-[rgb(var(--ui-primary-rgb))] text-[var(--ui-on-primary)]' : 'text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)]' }}">
                @svg('heroicon-o-folder', 'w-5 h-5')
            </a>
            <a href="{{ route('printing.printers.index') }}" wire:navigate
               class="flex items-center justify-center p-2 rounded-md {{ request()->routeIs('printing.printers.*') ? 'bg-[rgb(var(--ui-primary-rgb))] text-[var(--ui-on-primary)]' : 'text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)]' }}">
                @svg('heroicon-o-printer', 'w-5 h-5')
            </a>
            <a href="{{ route('printing.jobs.index') }}" wire:navigate
               class="flex items-center justify-center p-2 rounded-md {{ request()->routeIs('printing.jobs.*') ? 'bg-[rgb(var(--ui-primary-rgb))] text-[var(--ui-on-primary)]' : 'text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)]' }}">
                @svg('heroicon-o-document-text', 'w-5 h-5')
            </a>
        </div>
    </div>
</div>

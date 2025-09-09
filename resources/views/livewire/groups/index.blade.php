<div>
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Drucker-Gruppen</h1>
                <p class="text-sm text-gray-600">Verwalten Sie Drucker-Gruppen</p>
            </div>
            <button wire:click="showCreateModal" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                @svg('heroicons-plus', 'h-4 w-4 mr-2')
                Neue Gruppe
            </button>
        </div>
    </div>

    <!-- Filter -->
    <div class="mb-4 flex space-x-4">
        <div class="flex-1">
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Gruppen suchen..." class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
        </div>
        <div>
            <select wire:model.live="statusFilter" class="block w-full max-w-xs rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                <option value="all">Alle Status</option>
                <option value="active">Aktiv</option>
                <option value="inactive">Inaktiv</option>
            </select>
        </div>
    </div>

    <!-- Gruppen Tabelle -->
    <div class="bg-white shadow overflow-hidden sm:rounded-md">
        <ul class="divide-y divide-gray-200">
            @forelse($groups as $group)
                <li>
                    <div class="px-4 py-4 flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                @svg('heroicons-user-group', 'h-8 w-8 text-gray-400')
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-medium text-gray-900">
                                    <a href="{{ route('printing.groups.show', $group) }}" class="hover:text-indigo-600">
                                        {{ $group->name }}
                                    </a>
                                </div>
                                <div class="text-sm text-gray-500">
                                    {{ $group->description }}
                                </div>
                                <div class="text-xs text-gray-400">
                                    {{ $group->printers->count() }} Drucker
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $group->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $group->is_active ? 'Aktiv' : 'Inaktiv' }}
                            </span>
                            <button wire:click="toggleActive({{ $group->id }})" class="text-indigo-600 hover:text-indigo-900 text-sm">
                                {{ $group->is_active ? 'Deaktivieren' : 'Aktivieren' }}
                            </button>
                            <button wire:click="deleteGroup({{ $group->id }})" class="text-red-600 hover:text-red-900 text-sm">
                                Löschen
                            </button>
                        </div>
                    </div>
                </li>
            @empty
                <li class="px-4 py-8 text-center text-gray-500">
                    Keine Gruppen gefunden
                </li>
            @endforelse
        </ul>
    </div>

    {{ $groups->links() }}

    <!-- Create Modal -->
    @if($showCreateModal)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Neue Gruppe</h3>
                    <form wire:submit.prevent="createGroup">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Name</label>
                            <input wire:model="name" type="text" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Beschreibung</label>
                            <textarea wire:model="description" rows="3" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                            @error('description') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div class="flex justify-end space-x-2">
                            <button type="button" wire:click="hideCreateModal" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                                Abbrechen
                            </button>
                            <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                                Erstellen
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
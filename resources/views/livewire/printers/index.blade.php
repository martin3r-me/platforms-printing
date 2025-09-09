<div class="h-full overflow-y-auto p-6">
    <!-- Header -->
    <div class="mb-6">
        <div class="d-flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Drucker</h1>
                <p class="text-gray-600">Verwaltung aller Drucker im System</p>
            </div>
            <button 
                wire:click="showCreateModal"
                class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-on-primary rounded-md hover:bg-primary-dark transition">
                @svg('heroicon-o-plus', 'w-4 h-4')
                <span>Neuer Drucker</span>
            </button>
        </div>
    </div>

    <!-- Filter -->
    <div class="mb-6">
        <div class="d-flex items-center gap-4">
            <div class="flex-1">
                <input 
                    type="text" 
                    wire:model.live.debounce.300ms="search"
                    placeholder="Drucker suchen..."
                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-transparent">
            </div>
            <select 
                wire:model.live="statusFilter"
                class="px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-transparent">
                <option value="all">Alle Status</option>
                <option value="active">Aktiv</option>
                <option value="inactive">Inaktiv</option>
            </select>
        </div>
    </div>

    <!-- Drucker-Liste -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        @if($printers->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Standort</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Username</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jobs</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aktionen</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($printers as $printer)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="d-flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg d-flex items-center justify-center
                                            @if($printer->status === 'ready') bg-green-100 text-green-600
                                            @elseif($printer->status === 'busy') bg-yellow-100 text-yellow-600
                                            @elseif($printer->status === 'error') bg-red-100 text-red-600
                                            @else bg-gray-100 text-gray-600
                                            @endif">
                                            @svg('heroicon-o-printer', 'w-4 h-4')
                                        </div>
                                        <div>
                                            <div class="font-medium text-gray-900">{{ $printer->name }}</div>
                                            <div class="text-sm text-gray-500">{{ $printer->status_description }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    {{ $printer->location ?? 'Nicht angegeben' }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <code class="bg-gray-100 px-2 py-1 rounded text-xs">{{ $printer->username }}</code>
                                </td>
                                <td class="px-6 py-4">
                                    <x-ui-badge 
                                        :variant="$printer->is_active ? 'success' : 'neutral'" 
                                        size="sm">
                                        {{ $printer->is_active ? 'Aktiv' : 'Inaktiv' }}
                                    </x-ui-badge>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <div class="d-flex items-center gap-2">
                                        <span class="text-yellow-600">{{ $printer->pending_jobs_count }} wartend</span>
                                        <span class="text-green-600">{{ $printer->completed_jobs_count }} erledigt</span>
                                        @if($printer->failed_jobs_count > 0)
                                            <span class="text-red-600">{{ $printer->failed_jobs_count }} fehler</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="d-flex items-center gap-2">
                                        <a href="{{ route('printing.printers.show', $printer) }}" 
                                           class="inline-flex items-center gap-1 px-3 py-1 bg-primary text-on-primary rounded-md hover:bg-primary-dark transition text-sm"
                                           wire:navigate>
                                            @svg('heroicon-o-eye', 'w-4 h-4')
                                            <span>Anzeigen</span>
                                        </a>
                                        
                                        <button 
                                            wire:click="showEditModal({{ $printer->id }})"
                                            class="inline-flex items-center gap-1 px-3 py-1 bg-warning text-on-warning rounded-md hover:bg-warning-dark transition text-sm">
                                            @svg('heroicon-o-pencil', 'w-4 h-4')
                                            <span>Bearbeiten</span>
                                        </button>
                                        
                                        <button 
                                            wire:click="toggleActive({{ $printer->id }})"
                                            class="inline-flex items-center gap-1 px-3 py-1 {{ $printer->is_active ? 'bg-danger text-on-danger hover:bg-danger-dark' : 'bg-success text-on-success hover:bg-success-dark' }} rounded-md transition text-sm">
                                            @svg($printer->is_active ? 'heroicon-o-pause' : 'heroicon-o-play', 'w-4 h-4')
                                            <span>{{ $printer->is_active ? 'Deaktivieren' : 'Aktivieren' }}</span>
                                        </button>
                                        
                                        <button 
                                            wire:click="deletePrinter({{ $printer->id }})"
                                            wire:confirm="Drucker wirklich löschen?"
                                            class="inline-flex items-center gap-1 px-3 py-1 bg-danger text-on-danger rounded-md hover:bg-danger-dark transition text-sm">
                                            @svg('heroicon-o-trash', 'w-4 h-4')
                                            <span>Löschen</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $printers->links() }}
            </div>
        @else
            <div class="text-center py-12">
                @svg('heroicon-o-printer', 'w-12 h-12 text-gray-400 mx-auto mb-4')
                <h3 class="text-lg font-medium text-gray-900 mb-2">Keine Drucker vorhanden</h3>
                <p class="text-gray-600 mb-4">Erstelle deinen ersten Drucker, um mit dem Drucken zu beginnen.</p>
                <button 
                    wire:click="showCreateModal"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-on-primary rounded-md hover:bg-primary-dark transition">
                    @svg('heroicon-o-plus', 'w-4 h-4')
                    <span>Ersten Drucker erstellen</span>
                </button>
            </div>
        @endif
    </div>

    <!-- Create Modal -->
    @if($showCreateModal)
        <x-ui-modal wire:model="showCreateModal">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Neuen Drucker erstellen</h3>
                
                <form wire:submit="createPrinter">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Name</label>
                            <input 
                                type="text" 
                                wire:model="name"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-transparent"
                                placeholder="z.B. Küchendrucker 1">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Standort</label>
                            <input 
                                type="text" 
                                wire:model="location"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-transparent"
                                placeholder="z.B. Hauptküche">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Username</label>
                            <input 
                                type="text" 
                                wire:model="username"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-transparent"
                                placeholder="Wird automatisch generiert">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                            <input 
                                type="text" 
                                wire:model="password"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-transparent"
                                placeholder="Wird automatisch generiert">
                        </div>
                    </div>
                    
                    <div class="d-flex justify-end gap-3 mt-6">
                        <button 
                            type="button"
                            wire:click="hideCreateModal"
                            class="px-4 py-2 text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 transition">
                            Abbrechen
                        </button>
                        <button 
                            type="submit"
                            class="px-4 py-2 bg-primary text-on-primary rounded-md hover:bg-primary-dark transition">
                            Erstellen
                        </button>
                    </div>
                </form>
            </div>
        </x-ui-modal>
    @endif
</div>

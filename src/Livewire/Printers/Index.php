<?php

namespace Platform\Printing\Livewire\Printers;

use Livewire\Component;
use Livewire\WithPagination;
use Platform\Printing\Models\Printer;
use Platform\Printing\Models\PrinterGroup;

class Index extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = 'all';
    public $showCreateModal = false;
    public $showEditModal = false;
    public $editingPrinter = null;

    // CRM-konformes Modal-Flag
    public $modalShow = false;
    public $deleteModalShow = false;
    public $printerToDeleteId = null;
    
    // Form fields for creating printer
    public $name = '';
    public $location = '';
    public $username = '';
    public $password = '';
    public $mac_address = '';
    public $group_id = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => 'all'],
    ];

    public function render()
    {
        $printers = Printer::currentTeam()
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('location', 'like', '%' . $this->search . '%')
                      ->orWhere('username', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->statusFilter !== 'all', function ($query) {
                if ($this->statusFilter === 'active') {
                    $query->where('is_active', true);
                } elseif ($this->statusFilter === 'inactive') {
                    $query->where('is_active', false);
                }
            })
            ->with(['groups', 'printJobs'])
            ->orderBy('name')
            ->paginate(20);

        $groups = PrinterGroup::where('is_active', true)->orderBy('name')->get();

        return view('printing::livewire.printers.index', [
            'printers' => $printers,
            'groups' => $groups,
        ])->layout('platform::layouts.app');
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedStatusFilter()
    {
        $this->resetPage();
    }

    public function toggleActive(Printer $printer)
    {
        $printer->update(['is_active' => !$printer->is_active]);
        
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => $printer->is_active ? 'Drucker aktiviert' : 'Drucker deaktiviert'
        ]);
    }

    public function openDeleteModal($printerId)
    {
        $this->printerToDeleteId = $printerId;
        $this->deleteModalShow = true;
    }

    public function closeDeleteModal()
    {
        $this->deleteModalShow = false;
        $this->printerToDeleteId = null;
    }

    /** Für die Anzeige im Bestätigungs-Dialog */
    public function getPrinterToDeleteProperty(): ?Printer
    {
        return $this->printerToDeleteId ? Printer::find($this->printerToDeleteId) : null;
    }

    public function confirmDeletePrinter()
    {
        $printer = $this->printerToDelete;

        $this->closeDeleteModal();

        if ($printer) {
            $this->deletePrinter($printer);
        }
    }

    public function deletePrinter(Printer $printer)
    {
        if ($printer->printJobs()->count() > 0) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Drucker kann nicht gelöscht werden, da noch Print Jobs vorhanden sind'
            ]);
            return;
        }

        $printer->delete();
        
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Drucker gelöscht'
        ]);
    }

    // CRM-konform: Open/Close
    public function openCreateModal()
    {
        $this->modalShow = true;
    }

    public function closeCreateModal()
    {
        $this->modalShow = false;
    }

    // Rückwärtskompatibilität
    public function showCreateModal()
    {
        $this->openCreateModal();
    }

    public function hideCreateModal()
    {
        $this->closeCreateModal();
    }

    public function showEditModal(Printer $printer)
    {
        $this->editingPrinter = $printer;
        $this->showEditModal = true;
    }

    public function hideEditModal()
    {
        $this->editingPrinter = null;
        $this->showEditModal = false;
    }

    public function createPrinter()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'username' => 'nullable|string|max:255|unique:printers,username',
            'password' => 'nullable|string|max:255',
            'mac_address' => 'nullable|string|max:255|unique:printers,mac_address',
            'group_id' => 'nullable|exists:printer_groups,id',
        ]);

        $data = [
            'name' => $this->name,
            'location' => $this->location,
            'team_id' => auth()->user()->currentTeam->id,
        ];

        if ($this->username) {
            $data['username'] = $this->username;
        }

        if ($this->password) {
            $data['password'] = $this->password;
        }

        if ($this->mac_address) {
            $data['mac_address'] = $this->mac_address;
        }

        if ($this->group_id) {
            $data['printer_group_id'] = $this->group_id;
        }

        Printer::create($data);

        $this->closeCreateModal();
        $this->reset(['name', 'location', 'username', 'password', 'mac_address', 'group_id']);

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Drucker erfolgreich erstellt'
        ]);
    }
}

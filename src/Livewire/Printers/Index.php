<?php

namespace Platform\Printing\Livewire\Printers;

use Livewire\Component;
use Livewire\WithPagination;
use Platform\Printing\Models\Printer;

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
    public $editModalShow = false;
    
    // Form fields for creating printer
    public $name = '';
    public $location = '';
    public $username = '';
    public $password = '';
    public $group_id = null;

    // Form fields for editing printer
    public $edit_name = '';
    public $edit_location = '';
    public $edit_username = '';
    public $edit_password = '';
    public $editingPrinterId = null;

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

    // Edit Modal
    public function openEditModal($printerId)
    {
        $printer = Printer::find($printerId);
        if ($printer) {
            $this->editingPrinterId = $printerId;
            $this->edit_name = $printer->name;
            $this->edit_location = $printer->location;
            $this->edit_username = $printer->username;
            $this->edit_password = '';
            $this->editModalShow = true;
        }
    }

    public function closeEditModal()
    {
        $this->editModalShow = false;
        $this->editingPrinterId = null;
        $this->reset(['edit_name', 'edit_location', 'edit_username', 'edit_password']);
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

        if ($this->group_id) {
            $data['printer_group_id'] = $this->group_id;
        }

        Printer::create($data);

        $this->closeCreateModal();
        $this->reset(['name', 'location', 'username', 'password', 'group_id']);

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Drucker erfolgreich erstellt'
        ]);
    }

    public function updatePrinter()
    {
        $this->validate([
            'edit_name' => 'required|string|max:255',
            'edit_location' => 'nullable|string|max:255',
            'edit_username' => 'nullable|string|max:255|unique:printers,username,' . $this->editingPrinterId,
            'edit_password' => 'nullable|string|max:255',
        ]);

        $printer = Printer::find($this->editingPrinterId);
        if ($printer) {
            $data = [
                'name' => $this->edit_name,
                'location' => $this->edit_location,
            ];

            if ($this->edit_username) {
                $data['username'] = $this->edit_username;
            }

            if ($this->edit_password) {
                $data['password'] = $this->edit_password;
            }

            $printer->update($data);

            $this->closeEditModal();

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Drucker erfolgreich aktualisiert'
            ]);
        }
    }
}

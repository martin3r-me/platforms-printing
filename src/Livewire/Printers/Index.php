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

        return view('printing::livewire.printers.index', [
            'printers' => $printers,
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

    public function showCreateModal()
    {
        $this->showCreateModal = true;
    }

    public function hideCreateModal()
    {
        $this->showCreateModal = false;
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
}

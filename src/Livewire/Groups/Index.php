<?php

namespace Platform\Printing\Livewire\Groups;

use Livewire\Component;
use Livewire\WithPagination;
use Platform\Printing\Models\PrinterGroup;

class Index extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = 'all';
    public $showCreateModal = false;
    public $showEditModal = false;
    public $editingGroup = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => 'all'],
    ];

    public function render()
    {
        $groups = PrinterGroup::currentTeam()
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('description', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->statusFilter !== 'all', function ($query) {
                if ($this->statusFilter === 'active') {
                    $query->where('is_active', true);
                } elseif ($this->statusFilter === 'inactive') {
                    $query->where('is_active', false);
                }
            })
            ->with(['printers', 'printJobs'])
            ->orderBy('name')
            ->paginate(20);

        return view('printing::livewire.groups.index', [
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

    public function toggleActive(PrinterGroup $group)
    {
        $group->update(['is_active' => !$group->is_active]);
        
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => $group->is_active ? 'Gruppe aktiviert' : 'Gruppe deaktiviert'
        ]);
    }

    public function deleteGroup(PrinterGroup $group)
    {
        if ($group->printJobs()->count() > 0) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Gruppe kann nicht gelöscht werden, da noch Print Jobs vorhanden sind'
            ]);
            return;
        }

        $group->delete();
        
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Gruppe gelöscht'
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

    public function showEditModal(PrinterGroup $group)
    {
        $this->editingGroup = $group;
        $this->showEditModal = true;
    }

    public function hideEditModal()
    {
        $this->editingGroup = null;
        $this->showEditModal = false;
    }
}

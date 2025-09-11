<?php

namespace Platform\Printing\Livewire\Groups;

use Livewire\Component;
use Livewire\WithPagination;
use Platform\Printing\Models\PrinterGroup;
use Platform\Printing\Models\PrintJob;
use Platform\Printing\Models\Printer;

class Show extends Component
{
    use WithPagination;

    public PrinterGroup $group;
    public $statusFilter = 'all';
    public $isDirty = false;
    public $printerAssignmentModalShow = false;
    public $selectedPrinterId = null;
    
    // Separate properties for form binding
    public $group_name = '';
    public $group_description = '';
    public $group_is_active = false;

    protected $queryString = [
        'statusFilter' => ['except' => 'all'],
    ];

    protected $listeners = [
        'groupUpdated' => '$refresh',
    ];

    public function mount(PrinterGroup $group)
    {
        $this->group = $group;
        $this->group_name = $group->name;
        $this->group_description = $group->description;
        $this->group_is_active = $group->is_active;
    }

    public function updated($propertyName)
    {
        if (in_array($propertyName, ['group_name', 'group_description', 'group_is_active'])) {
            $this->isDirty = true;
        }
    }

    public function render()
    {
        // Refresh group data
        $this->group = $this->group->fresh(['printers', 'activities']);
        
        $jobs = PrintJob::where('printer_group_id', $this->group->id)
            ->when($this->statusFilter !== 'all', function ($query) {
                $query->where('status', $this->statusFilter);
            })
            ->with(['printable', 'user', 'printer'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $stats = [
            'total' => PrintJob::where('printer_group_id', $this->group->id)->count(),
            'pending' => PrintJob::where('printer_group_id', $this->group->id)->pending()->count(),
            'completed' => PrintJob::where('printer_group_id', $this->group->id)->completed()->count(),
            'failed' => PrintJob::where('printer_group_id', $this->group->id)->failed()->count(),
        ];

        $availablePrinters = Printer::where('is_active', true)
            ->where('team_id', auth()->user()->currentTeam->id)
            ->whereNotIn('id', $this->group->printers->pluck('id'))
            ->orderBy('name')
            ->get();

        return view('printing::livewire.groups.show', [
            'jobs' => $jobs,
            'stats' => $stats,
            'availablePrinters' => $availablePrinters,
        ])->layout('platform::layouts.app');
    }



    public function updatedStatusFilter()
    {
        $this->resetPage();
    }

    public function retryJob(PrintJob $job)
    {
        if ($job->status !== 'failed') {
            return;
        }

        $job->update([
            'status' => 'pending',
            'error_message' => null,
        ]);

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Job wird erneut versucht'
        ]);
    }

    public function cancelJob(PrintJob $job)
    {
        if (!in_array($job->status, ['pending', 'processing'])) {
            return;
        }

        $job->markAsCancelled();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Job abgebrochen'
        ]);
    }

    public function save()
    {
        $this->validate([
            'group_name' => 'required|string|max:255',
            'group_description' => 'nullable|string|max:255',
        ]);

        $this->group->update([
            'name' => $this->group_name,
            'description' => $this->group_description,
            'is_active' => $this->group_is_active,
        ]);
        
        $this->isDirty = false;

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Gruppe erfolgreich gespeichert'
        ]);
    }

    public function addPrinter()
    {
        $this->printerAssignmentModalShow = true;
    }

    public function closePrinterAssignmentModal()
    {
        $this->printerAssignmentModalShow = false;
        $this->selectedPrinterId = null;
    }

    public function assignPrinter()
    {
        if (!$this->selectedPrinterId) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Bitte wählen Sie einen Drucker aus'
            ]);
            return;
        }

        $printer = Printer::find($this->selectedPrinterId);
        if ($printer) {
            $this->group->addPrinter($printer);
            $this->closePrinterAssignmentModal();

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Drucker wurde der Gruppe zugewiesen'
            ]);
        }
    }

    public function editPrinter($printerId)
    {
        // TODO: Implement printer editing
        $this->dispatch('notify', [
            'type' => 'info',
            'message' => 'Drucker-Bearbeitung wird implementiert'
        ]);
    }

    public function removePrinter($printerId)
    {
        $printer = Printer::find($printerId);
        if ($printer) {
            $this->group->removePrinter($printer);
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Drucker wurde entfernt'
            ]);
        }
    }
}

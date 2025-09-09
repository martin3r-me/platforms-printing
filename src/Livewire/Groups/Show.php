<?php

namespace Platform\Printing\Livewire\Groups;

use Livewire\Component;
use Livewire\WithPagination;
use Platform\Printing\Models\PrinterGroup;
use Platform\Printing\Models\PrintJob;

class Show extends Component
{
    use WithPagination;

    public PrinterGroup $group;
    public $statusFilter = 'all';

    protected $queryString = [
        'statusFilter' => ['except' => 'all'],
    ];

    public function mount(PrinterGroup $group)
    {
        $this->group = $group;
    }

    public function render()
    {
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

        return view('printing::livewire.groups.show', [
            'jobs' => $jobs,
            'stats' => $stats,
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
}

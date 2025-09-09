<?php

namespace Platform\Printing\Livewire\Jobs;

use Livewire\Component;
use Livewire\WithPagination;
use Platform\Printing\Models\PrintJob;

class Index extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = 'all';
    public $printableTypeFilter = 'all';

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => 'all'],
        'printableTypeFilter' => ['except' => 'all'],
    ];

    public function render()
    {
        $jobs = PrintJob::currentTeam()
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('template', 'like', '%' . $this->search . '%')
                      ->orWhere('uuid', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->statusFilter !== 'all', function ($query) {
                $query->where('status', $this->statusFilter);
            })
            ->when($this->printableTypeFilter !== 'all', function ($query) {
                $query->where('printable_type', $this->printableTypeFilter);
            })
            ->with(['printable', 'printer', 'printerGroup', 'user'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $stats = [
            'total' => PrintJob::currentTeam()->count(),
            'pending' => PrintJob::currentTeam()->pending()->count(),
            'completed' => PrintJob::currentTeam()->completed()->count(),
            'failed' => PrintJob::currentTeam()->failed()->count(),
        ];

        return view('printing::livewire.jobs.index', [
            'jobs' => $jobs,
            'stats' => $stats,
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

    public function updatedPrintableTypeFilter()
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

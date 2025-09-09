<?php

namespace Platform\Printing\Livewire\Printers;

use Livewire\Component;
use Livewire\WithPagination;
use Platform\Printing\Models\Printer;
use Platform\Printing\Models\PrintJob;

class Show extends Component
{
    use WithPagination;

    public Printer $printer;
    public $statusFilter = 'all';

    protected $queryString = [
        'statusFilter' => ['except' => 'all'],
    ];

    public function mount(Printer $printer)
    {
        $this->printer = $printer;
    }

    public function render()
    {
        $jobs = PrintJob::where('printer_id', $this->printer->id)
            ->when($this->statusFilter !== 'all', function ($query) {
                $query->where('status', $this->statusFilter);
            })
            ->with(['printable', 'user'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $stats = [
            'total' => PrintJob::where('printer_id', $this->printer->id)->count(),
            'pending' => PrintJob::where('printer_id', $this->printer->id)->pending()->count(),
            'completed' => PrintJob::where('printer_id', $this->printer->id)->completed()->count(),
            'failed' => PrintJob::where('printer_id', $this->printer->id)->failed()->count(),
        ];

        return view('printing::livewire.printers.show', [
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

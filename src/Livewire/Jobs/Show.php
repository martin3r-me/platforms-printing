<?php

namespace Platform\Printing\Livewire\Jobs;

use Livewire\Component;
use Platform\Printing\Models\PrintJob;
use Platform\Printing\Services\PrintingService;

class Show extends Component
{
    public PrintJob $job;

    public function mount(PrintJob $job)
    {
        $this->job = $job;
    }

    public function render()
    {
        return view('printing::livewire.jobs.show', [
            'job' => $this->job,
        ])->layout('platform::layouts.app');
    }



    public function retryJob()
    {
        if ($this->job->status !== 'failed') {
            return;
        }

        $this->job->update([
            'status' => 'pending',
            'error_message' => null,
        ]);

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Job wird erneut versucht'
        ]);
    }

    public function cancelJob()
    {
        if (!in_array($this->job->status, ['pending', 'processing'])) {
            return;
        }

        $this->job->markAsCancelled();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Job abgebrochen'
        ]);
    }

    public function generateContent()
    {
        $content = app(PrintingService::class)->generateJobContent($this->job);
        
        $this->dispatch('notify', [
            'type' => 'info',
            'message' => 'Job-Inhalt generiert (siehe Logs)'
        ]);
    }
}

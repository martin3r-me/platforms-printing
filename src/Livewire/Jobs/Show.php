<?php

namespace Platform\Printing\Livewire\Jobs;

use Livewire\Component;
use Platform\Printing\Models\PrintJob;
use Platform\Printing\Services\PrintingService;

class Show extends Component
{
    public PrintJob $job;

    /** Gerenderte Druck-Vorschau (Inhalt, der an den Drucker geht) */
    public ?string $preview = null;

    /** Fehlermeldung, falls die Vorschau nicht erzeugt werden konnte */
    public ?string $previewError = null;

    public function mount(PrintJob $job)
    {
        $this->job = $job;
        $this->buildPreview();
    }

    public function render()
    {
        return view('printing::livewire.jobs.show', [
            'job' => $this->job,
        ])->layout('platform::layouts.app');
    }

    /**
     * Erzeugt die Druck-Vorschau aus Template + Job-Daten.
     */
    protected function buildPreview(): void
    {
        $this->preview = null;
        $this->previewError = null;

        try {
            $this->preview = app(PrintingService::class)->generateJobContent($this->job);
        } catch (\Throwable $e) {
            $this->previewError = $e->getMessage();
        }
    }

    public function reloadPreview()
    {
        $this->buildPreview();

        $this->dispatch('notify', [
            'type' => $this->previewError ? 'error' : 'success',
            'message' => $this->previewError
                ? 'Vorschau konnte nicht erzeugt werden'
                : 'Vorschau aktualisiert',
        ]);
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
        $this->job->logActivity('Erneut in Warteschlange gestellt');

        $this->buildPreview();

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
}

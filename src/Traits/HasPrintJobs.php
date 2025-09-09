<?php

namespace Platform\Printing\Traits;

use Platform\Printing\Models\PrintJob;
use Platform\Printing\Services\PrintingService;

trait HasPrintJobs
{
    /**
     * Beziehung zu Print Jobs
     */
    public function printJobs()
    {
        return $this->morphMany(PrintJob::class, 'printable');
    }

    /**
     * Erstellt einen Print Job für dieses Model
     */
    public function createPrintJob(
        string $template = 'default',
        array $data = [],
        ?int $printerId = null,
        ?int $printerGroupId = null
    ): PrintJob {
        return app(PrintingService::class)->createJob(
            $this,
            $template,
            $data,
            $printerId,
            $printerGroupId
        );
    }

    /**
     * Erstellt Print Jobs für alle Drucker in einer Gruppe
     */
    public function createPrintJobsForGroup(
        int $printerGroupId,
        string $template = 'default',
        array $data = []
    ): array {
        return app(PrintingService::class)->createJobsForGroup(
            $this,
            $printerGroupId,
            $template,
            $data
        );
    }

    /**
     * Gibt die Anzahl der Print Jobs zurück
     */
    public function getPrintJobsCountAttribute(): int
    {
        return $this->printJobs()->count();
    }

    /**
     * Gibt die Anzahl der wartenden Print Jobs zurück
     */
    public function getPendingPrintJobsCountAttribute(): int
    {
        return $this->printJobs()->pending()->count();
    }

    /**
     * Gibt die Anzahl der abgeschlossenen Print Jobs zurück
     */
    public function getCompletedPrintJobsCountAttribute(): int
    {
        return $this->printJobs()->completed()->count();
    }

    /**
     * Gibt die Anzahl der fehlgeschlagenen Print Jobs zurück
     */
    public function getFailedPrintJobsCountAttribute(): int
    {
        return $this->printJobs()->failed()->count();
    }

    /**
     * Prüft ob Print Jobs vorhanden sind
     */
    public function hasPrintJobs(): bool
    {
        return $this->print_jobs_count > 0;
    }

    /**
     * Prüft ob wartende Print Jobs vorhanden sind
     */
    public function hasPendingPrintJobs(): bool
    {
        return $this->pending_print_jobs_count > 0;
    }

    /**
     * Gibt die neuesten Print Jobs zurück
     */
    public function getRecentPrintJobs(int $limit = 5)
    {
        return $this->printJobs()
            ->with(['printer', 'printerGroup'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}

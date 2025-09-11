<?php

namespace Platform\Printing\Contracts;

use Illuminate\Database\Eloquent\Model;

interface PrintingServiceInterface
{
    /**
     * Erstellt einen Print Job für ein Model
     */
    public function createJob(
        Model $printable,
        string $template = 'default',
        array $data = [],
        ?int $printerId = null,
        ?int $printerGroupId = null
    ): \Platform\Printing\Models\PrintJob;

    /**
     * Erstellt Print Jobs für alle Drucker in einer Gruppe
     */
    public function createJobsForGroup(
        Model $printable,
        int $printerGroupId,
        string $template = 'default',
        array $data = []
    ): array;

    /**
     * Holt den nächsten wartenden Job für einen Drucker
     */
    public function getNextJobForPrinter(int $printerId): ?\Platform\Printing\Models\PrintJob;

    /**
     * Markiert einen Job als abgeschlossen
     */
    public function markJobAsCompleted(int $jobId): bool;

    /**
     * Markiert einen Job als fehlgeschlagen
     */
    public function markJobAsFailed(int $jobId, string $errorMessage = null): bool;

    /**
     * Generiert den Inhalt für einen Print Job
     */
    public function generateJobContent(\Platform\Printing\Models\PrintJob $job): string;

    /**
     * Validiert die Drucker-Anmeldedaten
     */
    public function validatePrinterCredentials(string $username, string $password): ?\Platform\Printing\Models\Printer;

    /**
     * Listet Drucker für Auswahl-UI auf
     */
    public function listPrinters(?bool $onlyActive = true, ?int $teamId = null): \Illuminate\Support\Collection;

    /**
     * Listet Drucker-Gruppen für Auswahl-UI auf
     */
    public function listPrinterGroups(?bool $onlyActive = true, ?int $teamId = null): \Illuminate\Support\Collection;
}

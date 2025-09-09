<?php

namespace Platform\Printing\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Platform\Printing\Contracts\PrintingServiceInterface;
use Platform\Printing\Models\PrintJob;
use Platform\Printing\Models\Printer;
use Platform\Printing\Models\PrinterGroup;

class PrintingService implements PrintingServiceInterface
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
    ): PrintJob {
        $printJob = PrintJob::create([
            'printable_type' => get_class($printable),
            'printable_id' => $printable->id,
            'template' => $template,
            'data' => $data,
            'printer_id' => $printerId,
            'printer_group_id' => $printerGroupId,
            'user_id' => auth()->id(),
            'team_id' => auth()->user()->currentTeam->id,
        ]);

        Log::info('Print Job erstellt', [
            'job_id' => $printJob->id,
            'printable_type' => $printable::class,
            'printable_id' => $printable->id,
            'template' => $template,
            'printer_id' => $printerId,
            'printer_group_id' => $printerGroupId,
        ]);

        return $printJob;
    }

    /**
     * Erstellt Print Jobs für alle Drucker in einer Gruppe
     */
    public function createJobsForGroup(
        Model $printable,
        int $printerGroupId,
        string $template = 'default',
        array $data = []
    ): array {
        $group = PrinterGroup::findOrFail($printerGroupId);
        $jobs = [];

        foreach ($group->printers()->active()->get() as $printer) {
            $jobs[] = $this->createJob(
                $printable,
                $template,
                $data,
                $printer->id,
                $printerGroupId
            );
        }

        Log::info('Print Jobs für Gruppe erstellt', [
            'group_id' => $printerGroupId,
            'jobs_count' => count($jobs),
            'printable_type' => $printable::class,
            'printable_id' => $printable->id,
        ]);

        return $jobs;
    }

    /**
     * Holt den nächsten wartenden Job für einen Drucker
     */
    public function getNextJobForPrinter(int $printerId): ?PrintJob
    {
        $printer = Printer::findOrFail($printerId);

        // Suche nach Jobs für diesen spezifischen Drucker
        $job = PrintJob::where('printer_id', $printerId)
            ->where('status', 'pending')
            ->orderBy('created_at', 'asc')
            ->first();

        if ($job) {
            $job->markAsProcessing();
            return $job;
        }

        // Suche nach Jobs für Gruppen, in denen der Drucker ist
        $groupIds = $printer->groups()->pluck('printer_groups.id');
        
        if ($groupIds->isNotEmpty()) {
            $job = PrintJob::whereIn('printer_group_id', $groupIds)
                ->where('status', 'pending')
                ->orderBy('created_at', 'asc')
                ->first();

            if ($job) {
                // Setze den spezifischen Drucker für diesen Job
                $job->update(['printer_id' => $printerId]);
                $job->markAsProcessing();
                return $job;
            }
        }

        return null;
    }

    /**
     * Markiert einen Job als abgeschlossen
     */
    public function markJobAsCompleted(int $jobId): bool
    {
        $job = PrintJob::find($jobId);
        
        if (!$job) {
            return false;
        }

        $job->markAsCompleted();

        Log::info('Print Job abgeschlossen', [
            'job_id' => $jobId,
            'printable_type' => $job->printable_type,
            'printable_id' => $job->printable_id,
        ]);

        return true;
    }

    /**
     * Markiert einen Job als fehlgeschlagen
     */
    public function markJobAsFailed(int $jobId, string $errorMessage = null): bool
    {
        $job = PrintJob::find($jobId);
        
        if (!$job) {
            return false;
        }

        $job->markAsFailed($errorMessage);

        Log::warning('Print Job fehlgeschlagen', [
            'job_id' => $jobId,
            'error_message' => $errorMessage,
            'retry_count' => $job->retry_count,
        ]);

        return true;
    }

    /**
     * Generiert den Inhalt für einen Print Job
     */
    public function generateJobContent(PrintJob $job): string
    {
        $template = $job->template;
        $data = $job->data;
        $printable = $job->printable;

        // Basis-Daten für alle Templates
        $baseData = [
            'job' => $job,
            'printable' => $printable,
            'created_at' => $job->created_at->format('d.m.Y H:i'),
            'team' => $job->team,
        ];

        // Template-spezifische Daten
        $templateData = array_merge($baseData, $data);

        // Hier würde normalerweise ein Template-Engine verwendet werden
        // Für jetzt geben wir einfachen Text zurück
        return $this->renderTemplate($template, $templateData);
    }

    /**
     * Validiert die Drucker-Anmeldedaten
     */
    public function validatePrinterCredentials(string $username, string $password): ?Printer
    {
        return Printer::where('username', $username)
            ->where('password', $password)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Rendert ein Template (vereinfachte Version)
     */
    protected function renderTemplate(string $template, array $data): string
    {
        // Hier würde normalerweise Blade oder eine andere Template-Engine verwendet
        // Für jetzt geben wir einfachen Text zurück
        
        $content = "=== PRINT JOB ===\n";
        $content .= "Template: {$template}\n";
        $content .= "Erstellt: {$data['created_at']}\n";
        $content .= "Team: {$data['team']->name}\n\n";

        if ($data['printable']) {
            $content .= "Inhalt:\n";
            $content .= "Typ: {$data['printable']::class}\n";
            $content .= "ID: {$data['printable']->id}\n";
            
            // Spezifische Daten je nach Model
            if (method_exists($data['printable'], 'title')) {
                $content .= "Titel: {$data['printable']->title}\n";
            }
            
            if (method_exists($data['printable'], 'description')) {
                $content .= "Beschreibung: {$data['printable']->description}\n";
            }
        }

        $content .= "\n=== ENDE ===";
        
        return $content;
    }
}

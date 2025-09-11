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
        string $template = null,
        array $data = [],
        ?int $printerId = null,
        ?int $printerGroupId = null
    ): PrintJob {
        // Intelligente Template-Auswahl wenn keins angegeben
        if ($template === null) {
            $template = $this->getDefaultTemplateForModel($printable);
        }
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

        // Versuche zuerst ein Blade-Template zu finden
        $bladeTemplate = $this->findBladeTemplate($printable, $template);
        if ($bladeTemplate) {
            return view($bladeTemplate, $templateData)->render();
        }

        // Fallback: Einfache Text-Generierung
        return $this->renderSimpleTemplate($printable, $templateData);
    }

    /**
     * Findet das passende Blade-Template für ein Model
     */
    private function findBladeTemplate(Model $printable, string $template): ?string
    {
        $modelName = class_basename($printable);
        $moduleName = $this->getModuleName($printable);
        
        // Verschiedene Template-Pfade versuchen (modul-spezifisch zuerst!)
        $templatePaths = [
            // 1. Im Modul selbst (LOOSE COUPLING!)
            "{$moduleName}::printing.{$template}",
            "{$moduleName}::printing.{$modelName}.{$template}",
            
            // 2. Im Printing-Service als Fallback
            "printing::templates.{$moduleName}.{$modelName}.{$template}",
            "printing::templates.{$moduleName}.{$template}",
            "printing::templates.{$modelName}.{$template}",
            "printing::templates.{$template}",
        ];

        foreach ($templatePaths as $path) {
            if (view()->exists($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Ermittelt den Modul-Namen aus der Model-Klasse
     */
    private function getModuleName(Model $model): string
    {
        $className = get_class($model);
        
        // Platform\Helpdesk\Models\HelpdeskTicket -> helpdesk
        if (preg_match('/Platform\\\\([^\\\\]+)\\\\Models/', $className, $matches)) {
            return strtolower($matches[1]);
        }
        
        return 'default';
    }

    /**
     * Einfache Text-Template-Generierung als Fallback
     */
    private function renderSimpleTemplate(Model $printable, array $data): string
    {
        $modelName = class_basename($printable);
        $moduleName = $this->getModuleName($printable);
        
        $content = "=== {$moduleName} - {$modelName} ===\n\n";
        
        // Generische Felder basierend auf Model-Attributen
        foreach ($printable->getAttributes() as $key => $value) {
            if (in_array($key, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                continue;
            }
            
            $label = ucfirst(str_replace('_', ' ', $key));
            $content .= "{$label}: {$value}\n";
        }
        
        // Zusätzliche Daten hinzufügen
        if (!empty($data)) {
            $content .= "\n--- Zusätzliche Daten ---\n";
            foreach ($data as $key => $value) {
                if (is_array($value)) {
                    $value = json_encode($value, JSON_PRETTY_PRINT);
                }
                $content .= "{$key}: {$value}\n";
            }
        }
        
        $content .= "\n" . str_repeat('=', 40) . "\n";
        $content .= "Gedruckt am: " . now()->format('d.m.Y H:i:s') . "\n";
        
        return $content;
    }

    /**
     * Ermittelt das Standard-Template für ein Model
     */
    private function getDefaultTemplateForModel(Model $printable): string
    {
        $modelName = class_basename($printable);
        $moduleName = $this->getModuleName($printable);
        
        // Konvention: {modul}-{model} (z.B. helpdesk-ticket)
        return strtolower($moduleName . '-' . kebab_case($modelName));
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
     * Listet Drucker für Auswahl-UI auf
     */
    public function listPrinters(?bool $onlyActive = true, ?int $teamId = null): \Illuminate\Support\Collection
    {
        $query = Printer::query();
        if ($onlyActive) {
            $query->where('is_active', true);
        }
        if ($teamId) {
            $query->where('team_id', $teamId);
        } elseif (auth()->check() && auth()->user()->currentTeam) {
            $query->where('team_id', auth()->user()->currentTeam->id);
        }
        return $query->orderBy('name')->get(['id','name']);
    }

    /**
     * Listet Drucker-Gruppen für Auswahl-UI auf
     */
    public function listPrinterGroups(?bool $onlyActive = true, ?int $teamId = null): \Illuminate\Support\Collection
    {
        $query = PrinterGroup::query();
        if ($onlyActive) {
            $query->where('is_active', true);
        }
        if ($teamId) {
            $query->where('team_id', $teamId);
        } elseif (auth()->check() && auth()->user()->currentTeam) {
            $query->where('team_id', auth()->user()->currentTeam->id);
        }
        return $query->orderBy('name')->get(['id','name']);
    }

}

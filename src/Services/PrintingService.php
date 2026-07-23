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
        ?string $template = null,
        array $data = [],
        ?int $printerId = null,
        ?int $printerGroupId = null
    ): PrintJob {
        // Intelligente Template-Auswahl wenn keins angegeben
        if ($template === null) {
            $template = $this->getDefaultTemplateForModel($printable);
        }

        // Wenn eine Gruppe angegeben ist, erstelle Jobs für alle aktiven Drucker der Gruppe
        if ($printerGroupId && !$printerId) {
            $jobs = $this->createJobsForGroup($printable, $printerGroupId, $template, $data);
            return $jobs[0]; // Rückgabe des ersten Jobs für Kompatibilität
        }

        // Einzelner Drucker-Job
        $printJob = PrintJob::create([
            'printable_type' => get_class($printable),
            'printable_id' => $printable->id,
            'template' => $template,
            'data' => $data,
            'printer_id' => $printerId,
            'printer_group_id' => null, // Keine Gruppen-Jobs mehr
            'user_id' => auth()->id(),
            'team_id' => auth()->user()->currentTeam->id,
        ]);

        Log::info('Print Job erstellt', [
            'job_id' => $printJob->id,
            'printable_type' => $printable::class,
            'printable_id' => $printable->id,
            'template' => $template,
            'printer_id' => $printerId,
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
        $group = PrinterGroup::find($printerGroupId);
        if (!$group) {
            throw new \InvalidArgumentException("Drucker-Gruppe mit ID {$printerGroupId} nicht gefunden");
        }

        $activePrinters = $group->printers()->where('is_active', true)->get();
        
        if ($activePrinters->isEmpty()) {
            throw new \InvalidArgumentException("Keine aktiven Drucker in der Gruppe '{$group->name}' gefunden");
        }

        $jobs = [];
        foreach ($activePrinters as $printer) {
            $job = PrintJob::create([
                'printable_type' => get_class($printable),
                'printable_id' => $printable->id,
                'template' => $template,
                'data' => $data,
                'printer_id' => $printer->id,
                'printer_group_id' => null, // Keine Gruppen-Jobs mehr
                'user_id' => auth()->id(),
                'team_id' => auth()->user()->currentTeam->id,
            ]);

            $jobs[] = $job;

            Log::info('Print Job für Gruppe erstellt', [
                'job_id' => $job->id,
                'printable_type' => $printable::class,
                'printable_id' => $printable->id,
                'template' => $template,
                'printer_id' => $printer->id,
                'printer_name' => $printer->name,
                'group_id' => $printerGroupId,
                'group_name' => $group->name,
            ]);
        }

        Log::info('Gruppen-Print Jobs erstellt', [
            'group_id' => $printerGroupId,
            'group_name' => $group->name,
            'job_count' => count($jobs),
            'printer_count' => $activePrinters->count(),
        ]);

        return $jobs;
    }


    /**
     * Holt den nächsten wartenden Job für einen Drucker
     */
    public function getNextJobForPrinter(int $printerId): ?PrintJob
    {
        // Suche nach Jobs für diesen spezifischen Drucker
        $job = PrintJob::where('printer_id', $printerId)
            ->where('status', 'pending')
            ->orderBy('created_at', 'asc')
            ->first();

        if ($job) {
            $job->markAsProcessing();
            return $job;
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
    public function markJobAsFailed(int $jobId, ?string $errorMessage = null): bool
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
            \Illuminate\Support\Facades\Log::info('PrintingService: Blade-Template gefunden', [
                'job_id' => $job->id,
                'job_uuid' => $job->uuid,
                'template' => $template,
                'blade' => $bladeTemplate,
                'module' => $this->getModuleName($printable),
                'model' => class_basename($printable),
            ]);
            return $this->normalizeContent(view($bladeTemplate, $templateData)->render());
        }

        // Fallback: Einfache Text-Generierung
        \Illuminate\Support\Facades\Log::warning('PrintingService: Kein Blade-Template gefunden, Fallback aktiv', [
            'job_id' => $job->id,
            'job_uuid' => $job->uuid,
            'template' => $template,
            'module' => $this->getModuleName($printable),
            'model' => class_basename($printable),
        ]);
        return $this->normalizeContent($this->renderSimpleTemplate($printable, $templateData));
    }

    /**
     * Normalisiert gerenderten Template-Inhalt für den Bon-Druck.
     *
     * Blade escaped in {{ }} die Zeichen & < > " ' als HTML-Entities
     * (z. B. & -> &amp;). Auf einem Text-Bon würden diese wörtlich erscheinen,
     * daher hier zurück in reine Zeichen wandeln. Das Ergebnis bleibt UTF-8
     * (für die Web-Vorschau); die Codepage-Umwandlung für den Drucker erfolgt
     * erst beim Ausliefern (siehe encodeForPrinter()).
     */
    public function normalizeContent(string $content): string
    {
        return html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Wandelt den UTF-8-Inhalt in die Zeichentabelle (Codepage) des Druckers um.
     *
     * Star/Epson CloudPRNT-Drucker drucken text/plain in ihrer eingestellten
     * Zeichentabelle, nicht in UTF-8. Ohne Umwandlung werden Umlaute/ß falsch
     * dargestellt. Die Ziel-Codepage ist über printing.encoding.codepage
     * konfigurierbar und muss zur Drucker-Einstellung passen.
     */
    public function encodeForPrinter(string $content): string
    {
        // Typografische Sonderzeichen (Middot, Bullet, Gedankenstriche,
        // typografische Anführungszeichen, Ellipse …) auf sicheres ASCII
        // abbilden – Bondrucker haben diese in ihrer Codepage oft nicht bzw.
        // an abweichenden Positionen (z. B. · -> ø auf manchen Star-Geräten).
        $content = $this->sanitizeForPrint($content);

        $codepage = config('printing.encoding.codepage', 'CP1252');

        if (strtoupper($codepage) !== 'UTF-8') {
            $encoded = @iconv('UTF-8', $codepage . '//TRANSLIT//IGNORE', $content);
            if ($encoded !== false) {
                $content = $encoded;
            }
        }

        // Steuerbefehl (rohe Bytes) voranstellen, um den Drucker auf die
        // passende Zeichentabelle zu setzen. Muss NACH der Codepage-Umwandlung
        // passieren, da es sich um rohe Control-Bytes handelt.
        return $this->setupCommand() . $content;
    }

    /**
     * Bildet typografische Sonderzeichen auf druckersicheres ASCII ab.
     * Wird nur im Druck-Pfad angewandt (die Web-Vorschau bleibt UTF-8/hübsch).
     */
    protected function sanitizeForPrint(string $content): string
    {
        return strtr($content, [
            // ß rendert dieser Bondrucker an CP850 0xE1 nicht als ß -> deutsche
            // Standard-Ersatzschreibung. (Umlaute ä/ö/ü liegen im 0x80-Block
            // und drucken korrekt, daher bleiben sie.)
            "\u{00DF}" => 'ss',   // ß
            "\u{1E9E}" => 'SS',   // ẞ (großes ß)
            "\u{00B7}" => '-',    // · Middot (Feld-Trenner)
            "\u{2022}" => '-',    // • Bullet
            "\u{2219}" => '-',    // ∙ Bullet operator
            "\u{00A0}" => ' ',    // geschütztes Leerzeichen
            "\u{2013}" => '-',    // – En-Dash
            "\u{2014}" => '-',    // — Em-Dash
            "\u{2212}" => '-',    // − Minus
            "\u{2026}" => '...',  // … Ellipse
            "\u{2018}" => "'",    // ‘
            "\u{2019}" => "'",    // ’
            "\u{201A}" => "'",    // ‚
            "\u{201C}" => '"',    // “
            "\u{201D}" => '"',    // ”
            "\u{201E}" => '"',    // „
            "\u{2039}" => '<',    // ‹
            "\u{203A}" => '>',    // ›
        ]);
    }

    /**
     * Rohe Steuer-Bytes, die jedem Druckauftrag vorangestellt werden, um den
     * Drucker auf einen definierten Zeichensatz zu setzen (z. B. Star/Epson
     * "International Character Set = USA" + Codepage Windows-1252). Ohne das
     * druckt ein auf "Deutschland" stehender Drucker @ als § und Umlaute falsch.
     *
     * Konfigurierbar als Hex-String über printing.encoding.setup_command_hex.
     */
    protected function setupCommand(): string
    {
        $hex = preg_replace('/[^0-9A-Fa-f]/', '', (string) config('printing.encoding.setup_command_hex', ''));

        if ($hex === '' || strlen($hex) % 2 !== 0) {
            return '';
        }

        return (string) hex2bin($hex);
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
        // Wenn der Model-Name bereits mit dem Modul-Präfix beginnt (z.B. planner-task), nutze ihn direkt
        $kebabModelName = \Illuminate\Support\Str::kebab($modelName);

        if (str_starts_with($kebabModelName, $moduleName . '-')) {
            return $kebabModelName;
        }

        // Standard: {modul}-{model}
        return strtolower($moduleName . '-' . $kebabModelName);
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

<?php

namespace Platform\Printing\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Platform\Printing\Contracts\PrintingServiceInterface;
use Platform\Printing\Exceptions\PrintableMissingException;
use Platform\Printing\Models\PrintJob;
use Platform\Printing\Models\Printer;
use Platform\Printing\Models\PrinterGroup;

class PrintingService implements PrintingServiceInterface
{
    /**
     * Zeichentabellen, die keine Standard-Codepage sind und die iconv daher
     * nicht kennt. Sie werden ausschließlich über codepageMap() abgebildet.
     */
    protected const DEVICE_CODEPAGES = ['STAR-DE'];


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
            'team_id' => $this->teamFor($printable, $printerId),
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
     * Zu welchem Team gehört der Druckauftrag?
     *
     * Bis hierher kam die Antwort aus auth(): der aktuelle Benutzer, sein Team.
     * Das trägt nur, solange ein Mensch auf einen Knopf drückt. Löst ein Vorgang
     * den Druck aus – eine eingehende Zahlung über einen Webhook, ein geplanter
     * Auftrag –, ist niemand angemeldet, und "auth()->user()->currentTeam" brach
     * mit einem Fehler auf null ab. Der Aufrufer fing ihn ab und protokollierte
     * ihn; sichtbar war nur, dass kein Auftrag entstand.
     *
     * Gefragt wird deshalb in dieser Reihenfolge:
     *
     *   1. Der DRUCKER. Er ist einem Team fest zugeordnet und ist die
     *      verlässlichste Quelle – gedruckt wird auf seinem Papier.
     *   2. Das Druckobjekt, falls es ein Team führt (Buchung, Aufgabe …).
     *   3. Der angemeldete Benutzer, wie gehabt.
     *
     * Bleibt alles leer, wird das laut gesagt statt still ein Feld leer zu
     * lassen: Ohne Team gehört der Auftrag niemandem.
     */
    protected function teamFor(Model $printable, ?int $printerId = null): int
    {
        if ($printerId) {
            $team = Printer::find($printerId)?->team_id;

            if ($team) {
                return (int) $team;
            }
        }

        // getAttribute statt ->team_id: Nicht jedes Druckobjekt hat die Spalte.
        $team = $printable->getAttribute('team_id');

        if ($team) {
            return (int) $team;
        }

        $team = auth()->user()?->currentTeam?->id;

        if ($team) {
            return (int) $team;
        }

        throw new \RuntimeException(
            'Kein Team für den Druckauftrag: weder am Drucker, noch am Druckobjekt, noch am angemeldeten Benutzer.'
        );
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
                'team_id' => $printer->team_id ?: $this->teamFor($printable, $printer->id),
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
     * Holt den nächsten wartenden Job für einen Drucker.
     *
     * Beantwortet nur die Frage "was steht als Nächstes an?" und ändert dabei
     * NICHTS am Zustand. Auf "processing" setzt den Auftrag erst der Download –
     * also der Moment, in dem der Drucker ihn tatsächlich abholt.
     *
     * Vorgeschichte: Der Poll hat den Auftrag früher sofort beansprucht. Das
     * trägt genau so lange, wie immer nur ein Auftrag in der Warteschlange
     * steht – dann sind Beanspruchen und Abholen derselbe Zyklus. Liegen
     * mehrere an (etwa alle Bons einer Veranstaltung auf einmal), pollt der
     * Drucker weiter, während er noch am ersten druckt. Dieser Poll hat den
     * zweiten Auftrag auf "processing" gesetzt, obwohl der Drucker ihn – weil
     * beschäftigt – nie abgeholt hat. Da die Abfrage nur nach "pending" sucht,
     * wurde er danach nie wieder angeboten: ein Bon blieb für immer im Status
     * "Wird gedruckt" liegen, ohne dass Papier kam.
     *
     * Ohne Beanspruchen wird derselbe Auftrag beim nächsten Poll einfach
     * erneut angeboten, bis der Drucker ihn wirklich holt. Doppelt gedruckt
     * werden kann dadurch nichts: Sobald der Download läuft, steht der Auftrag
     * auf "processing" und fällt aus dieser Abfrage heraus.
     */
    public function getNextJobForPrinter(int $printerId): ?PrintJob
    {
        // created_at ist sekundengenau. Werden mehrere Aufträge in derselben
        // Sekunde erzeugt - bei einem Stapeldruck der Normalfall -, ist die
        // Reihenfolge ohne zweites Kriterium unbestimmt. Die ID entscheidet
        // dann, damit der Papierstapel der Anlage-Reihenfolge folgt.
        return PrintJob::where('printer_id', $printerId)
            ->where('status', 'pending')
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->first();
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
        // Roh-Job: der Inhalt liegt fertig im Job, kein Template nötig
        if ($job->isRaw()) {
            return $job->rawContent();
        }

        $template = $job->template;
        $data = $job->data;
        $printable = $job->printable;

        // Ist der Datensatz gelöscht, ist die polymorphe Beziehung null. Ohne
        // diese Prüfung lief das in einen TypeError in findBladeTemplate() –
        // in der Vorschau als unlesbarer Stacktrace, im CloudPRNT-Download als
        // HTTP 500, wodurch der Drucker den Job endlos erneut anfragte.
        if (!$printable) {
            throw PrintableMissingException::forJob($job);
        }

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
    public function encodeForPrinter(string $content, ?Printer $printer = null): string
    {
        // Typografische Sonderzeichen (Middot, Bullet, Gedankenstriche,
        // typografische Anführungszeichen, Ellipse …) auf sicheres ASCII
        // abbilden – Bondrucker haben diese in ihrer Codepage oft nicht bzw.
        // an abweichenden Positionen (z. B. · -> ø auf manchen Star-Geräten).
        $content = $this->sanitizeForPrint($content);

        // Codepage und Steuerbefehl kommen bevorzugt vom Drucker selbst –
        // verschiedene Geräte drucken verschiedene Tabellen. Ohne Drucker
        // (z. B. Web-Vorschau) gelten die Config-Werte.
        $codepage = $printer
            ? $printer->codepage()
            : strtoupper((string) config('printing.encoding.codepage', 'CP1252'));

        if ($codepage !== 'UTF-8') {
            $content = $this->toCodepage($content, $codepage);
        }

        // Steuerbefehl (rohe Bytes) voranstellen, um den Drucker auf die
        // passende Zeichentabelle zu setzen. Muss NACH der Codepage-Umwandlung
        // passieren, da es sich um rohe Control-Bytes handelt.
        return $this->setupCommand($printer) . $content;
    }

    /**
     * Wandelt UTF-8 in die Ziel-Codepage des Druckers um.
     *
     * iconv ist dafür allein nicht verlässlich: je nach libc der
     * Laufzeitumgebung fehlen die DOS-Codepages komplett (musl/Alpine kennt
     * kein CP850/CP437) oder die Suffixe //TRANSLIT und //IGNORE werden nicht
     * akzeptiert. Vorher wurde ein fehlgeschlagenes iconv still verworfen –
     * dann ging der Text als UTF-8 an den Drucker (Umlaute als zwei Zeichen),
     * während das Log weiterhin "codepage: CP850" meldete. Der Fehler war so
     * praktisch nicht auffindbar.
     *
     * Deshalb: mehrere iconv-Varianten versuchen und erst danach über eine
     * eigene Byte-Tabelle abbilden – nie stillschweigend UTF-8 ausliefern.
     */
    protected function toCodepage(string $content, string $codepage): string
    {
        // Gerätetabellen sind keine Standard-Codepages – iconv kennt sie nicht.
        // Direkt über die Byte-Tabelle abbilden, ohne den iconv-Umweg (der
        // sonst bei jedem Druck einen Fehler ins Log schreiben würde).
        if (in_array($codepage, self::DEVICE_CODEPAGES, true)) {
            return $this->mapToCodepageBytes($content, $codepage);
        }

        foreach (['//TRANSLIT//IGNORE', '//IGNORE', ''] as $suffix) {
            $encoded = @iconv('UTF-8', $codepage . $suffix, $content);

            if ($encoded !== false) {
                return $encoded;
            }
        }

        Log::error('PrintingService: iconv kann nicht in die Drucker-Codepage wandeln', [
            'codepage' => $codepage,
            'iconv_impl' => ICONV_IMPL,
            'iconv_version' => ICONV_VERSION,
            'fallback' => $this->codepageMap($codepage) === null
                ? 'keiner (Codepage unbekannt)'
                : 'eigene Byte-Tabelle',
        ]);

        return $this->mapToCodepageBytes($content, $codepage);
    }

    /**
     * Bildet Nicht-ASCII-Zeichen über die Byte-Tabelle der Codepage ab.
     *
     * Ein Durchlauf über alle Nicht-ASCII-Zeichen: bekannte werden auf ihr
     * Codepage-Byte gesetzt, alle anderen nach ASCII transliteriert (é -> e).
     * Die eingesetzten Bytes werden dabei nicht erneut betrachtet.
     */
    protected function mapToCodepageBytes(string $content, string $codepage): string
    {
        $map = $this->codepageMap($codepage);

        if ($map === null) {
            return $content;
        }

        $mapped = preg_replace_callback(
            '/[\x{0080}-\x{10FFFF}]/u',
            fn (array $m) => $map[$m[0]] ?? $this->toAscii($m[0]),
            $content
        );

        // preg_* mit /u liefert null, wenn der Input kein gültiges UTF-8 ist
        return $mapped ?? $content;
    }

    /**
     * Letzter Ausweg für ein Zeichen, das die Zeichentabelle nicht enthält:
     * nach ASCII transliterieren (é -> e, € -> EUR). Auf einem Bon bleibt das
     * lesbar – deutlich besser, als das Zeichen ersatzlos zu verschlucken oder
     * ein Byte aus einer nur vermuteten Position zu drucken.
     */
    protected function toAscii(string $char): string
    {
        // Bewusst eine feste Tabelle statt iconv('ASCII//TRANSLIT'): dessen
        // Ergebnis hängt an der libc und ist auf einem Bon unbrauchbar –
        // macOS macht aus "Café Crème" ein "Caf'e Cr`eme" und aus "°C" ein
        // "^0C". Umlaute werden nach deutscher Konvention aufgelöst; das
        // greift nur, wenn die Zeichentabelle sie nicht selbst enthält.
        static $fold = [
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'ae', 'å' => 'a', 'æ' => 'ae',
            'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
            'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
            'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'oe', 'ø' => 'o',
            'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'ue',
            'ç' => 'c', 'ñ' => 'n', 'ý' => 'y', 'ÿ' => 'y', 'ß' => 'ss',
            'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'Ae', 'Å' => 'A', 'Æ' => 'Ae',
            'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E',
            'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I',
            'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'Oe', 'Ø' => 'O',
            'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'Ue',
            'Ç' => 'C', 'Ñ' => 'N',
            '€' => 'EUR', '£' => 'GBP', '§' => 'S', 'µ' => 'u',
            '°' => '', '²' => '2', '³' => '3', '½' => '1/2', '¼' => '1/4',
        ];

        return $fold[$char] ?? '';
    }

    /**
     * Byte-Positionen der gängigen Nicht-ASCII-Zeichen je Codepage.
     * Alle Werte gegen iconv verifiziert. CP437/CP850/CP858 teilen die
     * Positionen dieser Zeichen; CP850/CP858 können zusätzlich § und ³,
     * CP858 zusätzlich €.
     */
    protected function codepageMap(string $codepage): ?array
    {
        $dos = [
            'ä' => "\x84", 'ö' => "\x94", 'ü' => "\x81",
            'Ä' => "\x8E", 'Ö' => "\x99", 'Ü' => "\x9A", 'ß' => "\xE1",
            'é' => "\x82", 'è' => "\x8A", 'ê' => "\x88", 'É' => "\x90",
            'à' => "\x85", 'â' => "\x83", 'ç' => "\x87",
            'î' => "\x8C", 'ï' => "\x8B", 'ô' => "\x93", 'û' => "\x96", 'ù' => "\x97",
            'ñ' => "\xA4", 'Ñ' => "\xA5",
            '°' => "\xF8", '£' => "\x9C", 'µ' => "\xE6", '²' => "\xFD",
        ];

        return match ($codepage) {
            /*
             * Tabelle des Star-CloudPRNT-Geräts, per printing:test-codepage am
             * echten Drucker ausgelesen. Das Gerät läuft in KEINER DOS- oder
             * Windows-Codepage: dort läge ä auf 84, ö auf 94, ü auf 81 – hier
             * liegen an diesen Stellen Block- und Linienzeichen. Genau deshalb
             * kam "Minibrötchen" als "Minibr•tchen" und "Erdnüsse" als
             * "Erdnlsse" heraus.
             *
             * Bewusst nur die am Bon zweifelsfrei abgelesenen Zeichen. Alles
             * andere wird nach ASCII transliteriert (é -> e), was auf einem Bon
             * lesbar bleibt – besser als ein falsches Zeichen aus einer nur
             * vermuteten Position. Weitere Positionen lassen sich jederzeit per
             * Testdruck ergänzen.
             */
            'STAR-DE' => [
                'Ä' => "\xA0", 'Ö' => "\xA1", 'Ü' => "\xA2", 'ß' => "\xA3",
                'ö' => "\xB9", 'ü' => "\xBE", 'ä' => "\xCD",
                // Am Bon abgelesen. B2 fehlt bewusst: dort war unklar, ob ë
                // (Trema) oder ē (Makron) steht – bei Zweifel lieber die
                // ASCII-Auflösung als ein falsches Zeichen auf dem Bon.
                'é' => "\xB0", 'è' => "\xB1", 'ê' => "\xB3",
            ],
            'CP437' => $dos,
            'CP850' => $dos + ['§' => "\xF5", '³' => "\xFC"],
            'CP858' => $dos + ['§' => "\xF5", '³' => "\xFC", '€' => "\xD5"],
            'CP1252', 'WINDOWS-1252' => [
                'ä' => "\xE4", 'ö' => "\xF6", 'ü' => "\xFC",
                'Ä' => "\xC4", 'Ö' => "\xD6", 'Ü' => "\xDC", 'ß' => "\xDF",
                'é' => "\xE9", 'è' => "\xE8", 'ê' => "\xEA", 'É' => "\xC9",
                'à' => "\xE0", 'â' => "\xE2", 'ç' => "\xE7",
                'î' => "\xEE", 'ï' => "\xEF", 'ô' => "\xF4", 'û' => "\xFB", 'ù' => "\xF9",
                'ñ' => "\xF1", 'Ñ' => "\xD1",
                '°' => "\xB0", '£' => "\xA3", 'µ' => "\xB5", '²' => "\xB2",
                '§' => "\xA7", '³' => "\xB3", '€' => "\x80",
            ],
            default => null,
        };
    }

    /**
     * Beschreibt die tatsächlich an den Drucker gehenden Bytes – damit im Log
     * nachvollziehbar ist, ob die Codepage-Umwandlung wirklich stattgefunden
     * hat, statt nur den konfigurierten Wunschwert zu protokollieren.
     *
     * looks_like_utf8 = true bei Nicht-ASCII-Inhalt bedeutet: es wurde NICHT
     * umgewandelt, der Drucker erhält UTF-8 (Umlaute werden zwei Zeichen).
     */
    public function describeEncoding(string $encoded): array
    {
        preg_match_all('/[\x80-\xFF]/', $encoded, $matches);

        $highBytes = array_values(array_unique(array_map(
            fn (string $b) => strtoupper(bin2hex($b)),
            $matches[0]
        )));
        sort($highBytes);

        return [
            'high_bytes' => implode(' ', $highBytes),
            'has_non_ascii' => $highBytes !== [],
            'looks_like_utf8' => $highBytes !== [] && mb_check_encoding($encoded, 'UTF-8'),
        ];
    }

    /**
     * Bildet typografische Sonderzeichen auf druckersicheres ASCII ab.
     * Wird nur im Druck-Pfad angewandt (die Web-Vorschau bleibt UTF-8/hübsch).
     */
    protected function sanitizeForPrint(string $content): string
    {
        return strtr($content, [
            // ß wird NICHT mehr zu "ss" ersetzt. Dass es an CP850 0xE1 falsch
            // kam, lag nicht am Zeichen, sondern daran, dass das Gerät gar
            // nicht in CP850 läuft (siehe STAR-DE in codepageMap): dort liegt
            // ß auf 0xA3. Mit der richtigen Tabelle druckt es korrekt.
            "\u{1E9E}" => 'SS',   // ẞ (großes ß) – in keiner der Tabellen enthalten
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
    protected function setupCommand(?Printer $printer = null): string
    {
        $configured = $printer
            ? $printer->setupCommandHex()
            : (string) config('printing.encoding.setup_command_hex', '');

        $hex = preg_replace('/[^0-9A-Fa-f]/', '', $configured);

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

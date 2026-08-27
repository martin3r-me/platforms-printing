<?php

namespace Platform\Printing\Http;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Platform\Printing\Models\PrintJob;
use Platform\Printing\Models\Printer;
use Platform\Printing\Services\PrintingService;

/**
 * Ausliefern und Abschließen eines Druckauftrags.
 *
 * Liegt hier, weil es zwei Wege zum selben Vorgang gibt und beide dasselbe
 * tun müssen:
 *
 *   1. Der Standardweg - GET und DELETE auf DIE URL, die der Drucker ohnehin
 *      pollt. So sieht CloudPRNT es vor; jobGetUrl und jobConfirmationUrl
 *      heißen in der Spezifikation ausdrücklich "alternative URL".
 *   2. Der Umweg über /job/{uuid} und /confirm/{uuid}.
 *
 * Bis hierher gab es nur den Umweg, und ein GET auf die Poll-URL lief in ein
 * "405 Method Not Allowed". Der Drucker versucht aber zuerst den Standardweg,
 * fällt auf die Nase und nimmt erst nach einer Wartezeit den Umweg - gemessen
 * rund 28 Sekunden zwischen "gemeldet" und "abgeholt", bei einem Poll-Takt
 * von 5,4 Sekunden. Der Umweg bleibt bestehen, er schadet nicht.
 */
class CloudPrntJobResponder
{
    /**
     * Den Auftrag ausliefern, den der Drucker gerade abholen will.
     *
     * Ab hier holt er ihn wirklich ab - das ist der Moment, in dem der Auftrag
     * "processing" wird. Der Poll davor lässt den Zustand bewusst unangetastet
     * (siehe getNextJobForPrinter).
     */
    public function ausliefern(Request $request, Printer $printer, PrintJob $job)
    {
        // markAsProcessing() statt rohem update: es schreibt zugleich den
        // Protokolleintrag "An Drucker gesendet" und den Zeitstempel, an dem
        // sich später ablesen lässt, wo eine Verzögerung lag.
        if ($job->status === 'pending') {
            $job->markAsProcessing();
        }

        $service = app(PrintingService::class);

        try {
            // Roh-Jobs (Diagnose-Drucke) gehen unverändert raus – sie sollen ja
            // gerade bestimmte Bytes testen.
            $content = $job->isRaw()
                ? $job->rawContent()
                : $service->encodeForPrinter($service->generateJobContent($job), $printer);
        } catch (\Throwable $e) {
            // Fehler nicht als HTTP 500 durchreichen: der Drucker würde den Job
            // endlos erneut anfragen und er bliebe für immer auf "processing".
            // Stattdessen als fehlgeschlagen markieren – so verlässt er die
            // Warteschlange und ist im Backend samt Grund sichtbar.
            $service->markJobAsFailed($job->id, $e->getMessage());

            Log::error('CloudPRNT Job Download - Content konnte nicht erzeugt werden', [
                'job_id'     => $job->id,
                'job_uuid'   => $job->uuid,
                'printer_id' => $printer->id,
                'exception'  => $e::class,
                'error'      => $e->getMessage(),
            ]);

            return response('', 404);
        }

        Log::info('CloudPRNT Job Download - Content generiert', array_merge([
            'job_id'         => $job->id,
            'job_uuid'       => $job->uuid,
            'content_length' => strlen($content),
            'codepage'       => $printer->codepage(),
            'raw'            => $job->isRaw(),
        ], $service->describeEncoding($content)));

        // Rohe Bytes in der Drucker-Codepage, daher bewusst OHNE charset=utf-8
        // (Drucker druckt Bytes 1:1).
        //
        // Content-Length ist hier NICHT optional, auch wenn HTTP sie nicht
        // verlangt. Ohne sie liefert nginx die Antwort über HTTP/1.1 als
        // "Transfer-Encoding: chunked" aus, der Drucker erfährt nirgends, wie
        // lang der Bon ist, und wartet bis in seinen eigenen Timeout.
        //
        // strlen() zählt BYTES. Der Inhalt liegt bereits in der Codepage des
        // Druckers vor; mb_strlen würde den Bon abschneiden.
        return Response::make($content, 200, [
            'Content-Type'   => 'text/plain',
            'Content-Length' => (string) strlen($content),
        ]);
    }

    /**
     * Den Auftrag abschließen.
     *
     * Die Bestätigung sagt nicht nur DASS der Drucker fertig ist, sondern auch
     * WIE es ausging: "code" trägt den Druckerstatus (Star-Spezifikation,
     * 3-4 Stellen, optional mit Text). Alles mit führender 2 ist Erfolg
     * (200 OK, 211 Papier niedrig), alles andere ein Fehler - 410 Out of
     * paper, 411 Paper jam, 420 Cover open.
     *
     * Fehlt der Code ganz, gilt der Auftrag als gedruckt: Nicht jeder Client
     * schickt ihn, und aus einem fehlenden Feld einen Fehler zu machen wäre
     * schlimmer als die Lücke.
     */
    public function abschliessen(Request $request, Printer $printer, PrintJob $job)
    {
        $code         = trim((string) $request->input('code', ''));
        $erfolgreich  = $code === '' || str_starts_with($code, '2');

        if (! $erfolgreich) {
            app(PrintingService::class)->markJobAsFailed($job->id, 'Drucker meldet: ' . $code);

            Log::warning('CloudPRNT Job Confirmation - Drucker meldet Fehler', [
                'job_id'     => $job->id,
                'job_uuid'   => $job->uuid,
                'printer_id' => $printer->id,
                'code'       => $code,
            ]);

            return response()->noContent(); // 204
        }

        if (app(PrintingService::class)->markJobAsCompleted($job->id)) {
            Log::info('CloudPRNT Job Confirmation - Erfolgreich', [
                'job_id'     => $job->id,
                'job_uuid'   => $job->uuid,
                'printer_id' => $job->printer_id,
                'code'       => $code !== '' ? $code : null,
            ]);
        } else {
            Log::error('CloudPRNT Job Confirmation - Fehlgeschlagen', [
                'job_id'   => $job->id,
                'job_uuid' => $job->uuid,
            ]);
        }

        return response()->noContent(); // 204
    }

    /**
     * Der Auftrag, den der Drucker auf dem Standardweg meint.
     *
     * Dort nennt er keine Kennung - er fragt schlicht "gib mir den Auftrag".
     * Gemeint ist der, den ihm der Poll gerade gemeldet hat: der älteste
     * wartende. Fragt er erneut, ohne bestätigt zu haben, ist der schon
     * "processing"; dann ist genau der gemeint und nicht der nächste.
     */
    public function aktuellerAuftrag(Printer $printer, string $status = 'pending'): ?PrintJob
    {
        return PrintJob::where('printer_id', $printer->id)
            ->when($status === 'pending',
                fn ($q) => $q->whereIn('status', ['pending', 'processing']),
                fn ($q) => $q->where('status', 'processing'))
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->first();
    }
}

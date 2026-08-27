<?php

use Illuminate\Support\Facades\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Platform\Printing\Models\Printer;
use Platform\Printing\Models\PrintJob;
use Platform\Printing\Services\PrintingService;

// API-Routen (Prefix und Middleware werden vom ServiceProvider gesetzt)
Route::group([], function () {

    // CloudPRNT Poll Endpoint
    Route::post('/poll', function (Request $request) {
        // Detailliertes Request-Logging direkt in der Route
        \Illuminate\Support\Facades\Log::info('CloudPRNT Poll - Detailliert', [
            'timestamp' => now()->toDateTimeString(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'all_input' => $request->all(),
            'headers' => $request->headers->all(),
            'content_type' => $request->header('Content-Type'),
            'raw_content' => $request->getContent(),
            'username' => $request->input('username'),
            'password' => $request->has('password') ? '[HIDDEN]' : null,
        ]);

        // Drucker ist bereits durch Middleware validiert
        $printer = $request->attributes->get('printer');

        // Hole nächsten Job für diesen Drucker
        $job = app(PrintingService::class)->getNextJobForPrinter($printer->id);

        if (!$job) {
            Log::info('CloudPRNT Poll - Keine Jobs verfügbar', [
                'printer_id' => $printer->id,
                'printer_name' => $printer->name,
            ]);
            return response()->json(['jobReady' => false], 200);
        }

        Log::info('CloudPRNT Poll - Job gefunden', [
            'printer_id' => $printer->id,
            'job_id' => $job->id,
            'job_uuid' => $job->uuid,
        ]);

        // CloudPRNT-kompatible Antwort
        return response()->json([
            'jobReady' => true,
            'mediaTypes' => ['text/plain'],
            'jobToken' => $job->uuid,
            'jobGetUrl' => route('printing.api.job.download', ['uuid' => $job->uuid]),
            'deleteMethod' => 'DELETE',
            'jobConfirmationUrl' => route('printing.api.job.confirm', ['uuid' => $job->uuid]),
        ]);
    })->name('printing.api.poll');

    // Job Download Endpoint
    Route::get('/job/{uuid}', function (Request $request, string $uuid) {
        Log::info('CloudPRNT Job Download - Start', [
            'timestamp' => now()->toDateTimeString(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'job_uuid' => $uuid,
            'headers' => $request->headers->all(),
        ]);

        // Drucker ist bereits durch Middleware validiert
        $printer = $request->attributes->get('printer');
        
        if (!$printer) {
            Log::warning('CloudPRNT Job Download - Kein Drucker in Request', [
                'job_uuid' => $uuid,
                'ip' => $request->ip(),
            ]);
            return response()->json(['error' => 'Drucker nicht authentifiziert'], 401);
        }

        $job = PrintJob::where('uuid', $uuid)
            ->where('printer_id', $printer->id)
            ->whereIn('status', ['pending', 'processing'])
            ->first();

        if (!$job) {
            Log::warning('CloudPRNT Job Download - Job nicht gefunden', [
                'job_uuid' => $uuid,
                'printer_id' => $printer->id,
            ]);
            return response('', 404);
        }

        // Ab hier holt der Drucker den Auftrag wirklich ab - das ist der
        // Moment, in dem er "processing" wird. Der Poll davor lässt den
        // Zustand bewusst unangetastet (siehe getNextJobForPrinter).
        // markAsProcessing() statt rohem update: es schreibt zugleich den
        // Protokolleintrag "An Drucker gesendet".
        if ($job->status === 'pending') {
            $job->markAsProcessing();
        }

        // Generiere Job-Content (UTF-8) und wandle in die Drucker-Codepage um
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
                'job_id' => $job->id,
                'job_uuid' => $job->uuid,
                'printer_id' => $printer->id,
                'exception' => $e::class,
                'error' => $e->getMessage(),
            ]);

            return response('', 404);
        }

        // Neben der konfigurierten Codepage auch protokollieren, was tatsächlich
        // rausgeht (siehe describeEncoding): looks_like_utf8=true verrät, dass
        // die Umwandlung nicht griff und der Drucker UTF-8 bekommt.
        Log::info('CloudPRNT Job Download - Content generiert', array_merge([
            'job_id' => $job->id,
            'job_uuid' => $job->uuid,
            'content_length' => strlen($content),
            'codepage' => $printer->codepage(),
            'raw' => $job->isRaw(),
        ], $service->describeEncoding($content)));

        // CloudPRNT-kompatible Antwort: rohe Bytes in der Drucker-Codepage,
        // daher bewusst OHNE charset=utf-8 (Drucker druckt Bytes 1:1).
        //
        // Content-Length ist hier NICHT optional, auch wenn HTTP sie nicht
        // verlangt. Ohne sie liefert nginx die Antwort als "Transfer-Encoding:
        // chunked" ueber eine keep-alive-Verbindung aus. Der Drucker erfaehrt
        // dann nirgends, wie lang der Bon ist, und wartet auf das Ende der
        // Uebertragung - bis sein eigener HTTP Response Timeout zuschlaegt.
        // Genau das kostete jeden einzelnen Bon 60 Sekunden: abgeholt war er
        // sofort, gedruckt wurde er erst beim Timeout.
        //
        // strlen() statt mb_strlen(): Gebraucht wird die Laenge in BYTES. Der
        // Inhalt liegt bereits in der Codepage des Druckers vor, und Umlaute
        // sind dort ein Byte - mb_strlen zaehlte Zeichen und wuerde die
        // Antwort abschneiden.
        return Response::make($content, 200, [
            'Content-Type'   => 'text/plain',
            'Content-Length' => (string) strlen($content),
        ]);
    })->name('printing.api.job.download');

    // Job Confirmation Endpoint
    Route::delete('/confirm/{uuid}', function (Request $request, string $uuid) {
        Log::info('CloudPRNT Job Confirmation - Start', [
            'timestamp' => now()->toDateTimeString(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'job_uuid' => $uuid,
            'headers' => $request->headers->all(),
        ]);

        // Drucker ist bereits durch Middleware validiert
        $printer = $request->attributes->get('printer');
        
        if (!$printer) {
            Log::warning('CloudPRNT Job Confirmation - Kein Drucker in Request', [
                'job_uuid' => $uuid,
                'ip' => $request->ip(),
            ]);
            return response()->json(['error' => 'Drucker nicht authentifiziert'], 401);
        }

        $job = PrintJob::where('uuid', $uuid)
            ->where('printer_id', $printer->id)
            ->where('status', 'processing')
            ->first();

        if (!$job) {
            Log::warning('CloudPRNT Job Confirmation - Job nicht gefunden', [
                'job_uuid' => $uuid,
                'printer_id' => $printer->id,
            ]);
            return response()->json(['error' => 'Job nicht gefunden'], 404);
        }

        // Die Bestaetigung sagt nicht nur DASS der Drucker fertig ist, sondern
        // auch WIE es ausging: "code" traegt den Druckerstatus (Star-Spezifikation,
        // 3-4 Stellen, optional mit Text). Alles mit fuehrender 2 ist Erfolg
        // (200 OK, 211 Papier niedrig), alles andere ein Fehler - 410 Out of
        // paper, 411 Paper jam, 420 Cover open.
        //
        // Bis hier wurde der Code ignoriert und jeder Auftrag als "Gedruckt"
        // verbucht. Bei leerem Papier stand der Bon damit als gedruckt in der
        // Liste, obwohl nie einer herauskam - und genau dann muss man ja
        // erkennen koennen, welcher fehlt.
        //
        // Fehlt der Code ganz, gilt der Auftrag wie bisher als gedruckt: Nicht
        // jeder Client schickt ihn, und aus einem fehlenden Feld einen Fehler
        // zu machen waere schlimmer als die Luecke.
        $code = trim((string) $request->input('code', ''));
        $erfolgreich = $code === '' || str_starts_with($code, '2');

        if (! $erfolgreich) {
            app(PrintingService::class)->markJobAsFailed($job->id, 'Drucker meldet: ' . $code);

            Log::warning('CloudPRNT Job Confirmation - Drucker meldet Fehler', [
                'job_id' => $job->id,
                'job_uuid' => $job->uuid,
                'printer_id' => $job->printer_id,
                'code' => $code,
            ]);

            return response()->noContent(); // 204
        }

        $success = app(PrintingService::class)->markJobAsCompleted($job->id);

        if ($success) {
            Log::info('CloudPRNT Job Confirmation - Erfolgreich', [
                'job_id' => $job->id,
                'job_uuid' => $job->uuid,
                'printer_id' => $job->printer_id,
                'code' => $code !== '' ? $code : null,
            ]);
        } else {
            Log::error('CloudPRNT Job Confirmation - Fehlgeschlagen', [
                'job_id' => $job->id,
                'job_uuid' => $job->uuid,
            ]);
        }

        return response()->noContent(); // 204
    })->name('printing.api.job.confirm');

    // Job Error Endpoint
    Route::post('/error/{uuid}', function (Request $request, string $uuid) {
        Log::info('CloudPRNT Job Error', [
            'timestamp' => now()->toDateTimeString(),
            'ip' => $request->ip(),
            'job_uuid' => $uuid,
            'error_message' => $request->input('error_message'),
        ]);

        // Drucker ist bereits durch Middleware validiert
        $printer = $request->attributes->get('printer');

        $job = PrintJob::where('uuid', $uuid)
            ->where('printer_id', $printer->id)
            ->whereIn('status', ['processing', 'pending'])
            ->first();

        if (!$job) {
            Log::warning('CloudPRNT Job Error - Job nicht gefunden', [
                'job_uuid' => $uuid,
                'printer_id' => $printer->id,
            ]);
            return response()->json(['error' => 'Job nicht gefunden'], 404);
        }

        $errorMessage = $request->input('error_message', 'Unbekannter Fehler');
        $success = app(PrintingService::class)->markJobAsFailed($job->id, $errorMessage);

        if ($success) {
            Log::info('CloudPRNT Job Error - Als fehlgeschlagen markiert', [
                'job_id' => $job->id,
                'job_uuid' => $job->uuid,
                'printer_id' => $job->printer_id,
                'error_message' => $errorMessage,
            ]);
        } else {
            Log::error("CloudPRNT Job-Fehler-Markierung fehlgeschlagen", [
                'job_id' => $job->id,
                'job_uuid' => $job->uuid,
            ]);
        }

        return response()->noContent(); // 204
    })->name('printing.api.job.error');

}); // Ende der Middleware-Gruppe
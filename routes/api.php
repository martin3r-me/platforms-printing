<?php

use Illuminate\Support\Facades\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Platform\Printing\Models\Printer;
use Platform\Printing\Models\PrintJob;
use Platform\Printing\Http\CloudPrntJobResponder;
use Platform\Printing\Services\PrintingService;
use Platform\Printing\Support\PrinterSelfReport;

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

        // Festhalten, was der Drucker von sich aus mitschickt - und Antworten
        // auf frühere Rückfragen, falls welche dabei sind.
        PrinterSelfReport::mitschreiben($request, $printer);

        // Hole nächsten Job für diesen Drucker
        $job = app(PrintingService::class)->getNextJobForPrinter($printer->id);

        if (!$job) {
            // Nichts zu drucken - der richtige Moment, das Gerät einmalig nach
            // seinem echten Poll-Takt und seinen Formaten zu fragen. Eine
            // clientAction unterdrückt das Drucken in derselben Runde, deshalb
            // ausschließlich hier. Danach pollt das Gerät sofort erneut.
            if ($fragen = PrinterSelfReport::fragen($printer)) {
                Log::info('CloudPRNT Poll - Rückfrage an den Drucker gestellt', [
                    'printer_id' => $printer->id,
                    'fragen' => array_column($fragen, 'request'),
                ]);

                return response()->json([
                    'jobReady' => false,
                    'clientAction' => $fragen,
                ], 200);
            }

            Log::info('CloudPRNT Poll - Keine Jobs verfügbar', [
                'printer_id' => $printer->id,
                'printer_name' => $printer->name,
            ]);
            return response()->json(['jobReady' => false], 200);
        }

        // Wann wurde dieser Auftrag dem Drucker ZUERST gemeldet?
        //
        // Zwischen Anlegen und Abholen vergehen rund 33 Sekunden, obwohl das
        // Geraet alle 5,4 Sekunden fragt. Zwei Erklaerungen sind moeglich, und
        // nur diese Zahl trennt sie: Wird hier sofort gemeldet, laesst sich der
        // Drucker Zeit. Wird hier spaet gemeldet, liegt der Fehler bei uns.
        //
        // Abgelegt im vorhandenen data-Feld statt in einer eigenen Spalte: Es
        // ist eine Diagnose, die wieder verschwinden darf, und so braucht das
        // Ausrollen keine Migration - eine fehlende Spalte wuerde hier sonst
        // den gesamten Druck lahmlegen.
        if (empty(($job->data ?? [])['angeboten_um'])) {
            $job->forceFill([
                'data' => array_merge($job->data ?? [], ['angeboten_um' => now()->toDateTimeString()]),
            ])->save();
        }

        Log::info('CloudPRNT Poll - Job gefunden', [
            'printer_id' => $printer->id,
            'job_id' => $job->id,
            'job_uuid' => $job->uuid,
        ]);

        // CloudPRNT-kompatible Antwort.
        //
        // Ohne jobGetUrl/jobConfirmationUrl holt der Drucker den Auftrag ueber
        // DIE URL, die er ohnehin pollt - den Standardweg, den es seit dem
        // Ergaenzen von GET und DELETE auf /poll gibt.
        //
        // Warum das ueberhaupt eine Rolle spielt: Zwischen "gemeldet" und
        // "abgeholt" liegen rund 28 Sekunden, und im Verkehr ist zu sehen,
        // dass der Drucker in dieser Zeit die alternative URL ansteuert. Der
        // Verdacht ist, dass ihn genau deren Verarbeitung aufhaelt. Bleibt nur
        // der Standardweg, faellt das weg.
        //
        // Ueber printing.api.cloudprnt.alternative_urls wieder einschaltbar,
        // falls ein anderes Geraet die Alternative braucht.
        $antwort = [
            'jobReady'     => true,
            'mediaTypes'   => ['text/plain'],
            'jobToken'     => $job->uuid,
            'deleteMethod' => 'DELETE',
        ];

        if (config('printing.api.cloudprnt.alternative_urls', false)) {
            $antwort['jobGetUrl']          = route('printing.api.job.download', ['uuid' => $job->uuid]);
            $antwort['jobConfirmationUrl'] = route('printing.api.job.confirm', ['uuid' => $job->uuid]);
        }

        return response()->json($antwort);
    })->name('printing.api.poll');

    // --- Der Standardweg -----------------------------------------------
    //
    // CloudPRNT holt den Auftrag ueber DIESELBE URL, die der Drucker pollt.
    // jobGetUrl und jobConfirmationUrl heissen in der Spezifikation
    // ausdruecklich "alternative URL" - sie sind der Umweg, nicht der Weg.
    //
    // Bis hierher gab es nur den Umweg, und ein GET auf die Poll-URL lief in
    // ein "405 Method Not Allowed". Der Drucker versucht aber zuerst den
    // Standardweg: gemessen lagen zwischen "gemeldet" und "abgeholt" rund 28
    // Sekunden, bei einem Poll-Takt von 5,4 Sekunden.

    Route::get('/poll', function (Request $request) {
        $printer   = $request->attributes->get('printer');
        $responder = app(CloudPrntJobResponder::class);
        $job       = $responder->aktuellerAuftrag($printer);

        Log::info('CloudPRNT Job Download (Standardweg)', [
            'printer_id' => $printer?->id,
            'job_id'     => $job?->id,
            'type'       => $request->query('type'),
        ]);

        if (! $job) {
            return response('', 404);
        }

        return $responder->ausliefern($request, $printer, $job);
    })->name('printing.api.poll.job');

    Route::delete('/poll', function (Request $request) {
        $printer   = $request->attributes->get('printer');
        $responder = app(CloudPrntJobResponder::class);
        $job       = $responder->aktuellerAuftrag($printer, 'processing');

        Log::info('CloudPRNT Job Confirmation (Standardweg)', [
            'printer_id' => $printer?->id,
            'job_id'     => $job?->id,
            'code'       => $request->input('code'),
        ]);

        if (! $job) {
            return response()->noContent();
        }

        return $responder->abschliessen($request, $printer, $job);
    })->name('printing.api.poll.confirm');

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

        return app(CloudPrntJobResponder::class)->ausliefern($request, $printer, $job);
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

        return app(CloudPrntJobResponder::class)->abschliessen($request, $printer, $job);
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
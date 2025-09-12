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
            ->where('status', 'pending')
            ->first();

        if (!$job) {
            Log::warning('CloudPRNT Job Download - Job nicht gefunden', [
                'job_uuid' => $uuid,
                'printer_id' => $printer->id,
            ]);
            return response('', 404);
        }

        // Markiere Job als verarbeitet
        $job->update(['status' => 'processing']);

        // Generiere Job-Content
        $content = app(PrintingService::class)->generateJobContent($job);

        Log::info('CloudPRNT Job Download - Content generiert', [
            'job_id' => $job->id,
            'job_uuid' => $job->uuid,
            'content_length' => strlen($content),
        ]);

        // CloudPRNT-kompatible Antwort (roher Text, kein JSON)
        return Response::make($content, 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
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

        $success = app(PrintingService::class)->markJobAsCompleted($job->id);

        if ($success) {
            Log::info('CloudPRNT Job Confirmation - Erfolgreich', [
                'job_id' => $job->id,
                'job_uuid' => $job->uuid,
                'printer_id' => $job->printer_id,
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
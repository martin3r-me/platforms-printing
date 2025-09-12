<?php

use Illuminate\Support\Facades\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Platform\Printing\Models\Printer;
use Platform\Printing\Models\PrintJob;
use Platform\Printing\Services\PrintingService;

// API-Routen mit Config-basiertem Prefix und Middleware
Route::prefix(config('printing.api.prefix', 'api/printing'))
    ->middleware(array_merge(config('printing.api.middleware', ['api']), ['verify.printer.basic']))
    ->group(function () {

    // CloudPRNT Poll Endpoint
    Route::post('/poll', function (Request $request) {
        Log::info('CloudPRNT Poll', [
            'timestamp' => now()->toDateTimeString(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'username' => $request->input('username'),
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
            return response()->json(['status' => 'no_job']);
        }

        Log::info('CloudPRNT Poll - Job gefunden', [
            'printer_id' => $printer->id,
            'job_id' => $job->id,
            'job_uuid' => $job->uuid,
        ]);

        return response()->json([
            'status' => 'job_available',
            'job_uuid' => $job->uuid,
            'job_url' => route('printing.api.job.download', $job->uuid),
            'confirm_url' => route('printing.api.job.confirm', $job->uuid),
        ]);
    })->name('printing.api.poll');

    // Job Download Endpoint
    Route::get('/job/{uuid}', function (Request $request, string $uuid) {
        Log::info('CloudPRNT Job Download', [
            'timestamp' => now()->toDateTimeString(),
            'ip' => $request->ip(),
            'job_uuid' => $uuid,
        ]);

        // Drucker ist bereits durch Middleware validiert
        $printer = $request->attributes->get('printer');

        $job = PrintJob::where('uuid', $uuid)
            ->where('printer_id', $printer->id)
            ->where('status', 'pending')
            ->first();

        if (!$job) {
            Log::warning('CloudPRNT Job Download - Job nicht gefunden', [
                'job_uuid' => $uuid,
                'printer_id' => $printer->id,
            ]);
            return response()->json(['error' => 'Job nicht gefunden'], 404);
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

        return response($content)
            ->header('Content-Type', 'text/plain; charset=utf-8')
            ->header('Content-Length', strlen($content));
    })->name('printing.api.job.download');

    // Job Confirmation Endpoint
    Route::delete('/confirm/{uuid}', function (Request $request, string $uuid) {
        Log::info('CloudPRNT Job Confirmation', [
            'timestamp' => now()->toDateTimeString(),
            'ip' => $request->ip(),
            'job_uuid' => $uuid,
        ]);

        // Drucker ist bereits durch Middleware validiert
        $printer = $request->attributes->get('printer');

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
<?php

use Illuminate\Support\Facades\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Platform\Printing\Models\Printer;
use Platform\Printing\Models\PrintJob;
use Platform\Printing\Services\PrintingService;

// CloudPRNT Poll Endpoint
Route::post('/poll', function (Request $request) {
    Log::info('CloudPRNT Poll', [
        'timestamp' => now()->toDateTimeString(),
        'ip' => $request->ip(),
        'user_agent' => $request->userAgent(),
        'username' => $request->input('username'),
    ]);

    // Validiere Drucker-Anmeldedaten
    $username = $request->input('username');
    $password = $request->input('password');

    if (!$username || !$password) {
        Log::warning('CloudPRNT Poll ohne Anmeldedaten', [
            'ip' => $request->ip(),
        ]);
        return response()->json(['error' => 'Anmeldedaten erforderlich'], 401);
    }

    $printer = app(PrintingService::class)->validatePrinterCredentials($username, $password);
    
    if (!$printer) {
        Log::warning('CloudPRNT Poll mit ungültigen Anmeldedaten', [
            'username' => $username,
            'ip' => $request->ip(),
        ]);
        return response()->json(['error' => 'Ungültige Anmeldedaten'], 401);
    }

    // Hole nächsten Job für diesen Drucker
    $job = app(PrintingService::class)->getNextJobForPrinter($printer->id);

    if (!$job) {
        return response()->json(['jobReady' => false], 200);
    }

    Log::info('CloudPRNT Job bereitgestellt', [
        'printer_id' => $printer->id,
        'printer_name' => $printer->name,
        'job_id' => $job->id,
        'job_uuid' => $job->uuid,
    ]);

    // Für den Testfall die UUID als Token verwenden
    return response()->json([
        'jobReady' => true,
        'mediaTypes' => ['text/plain'],
        'jobToken' => $job->uuid,
        'jobGetUrl' => url("api/printing/job/{$job->uuid}"),
        'deleteMethod' => 'DELETE',
        'jobConfirmationUrl' => url("api/printing/confirm/{$job->uuid}"),
    ]);
});

// CloudPRNT Job Download
Route::get('/job/{uuid}', function (string $uuid) {
    Log::info("CloudPRNT GET job", ['job_uuid' => $uuid]);

    $job = PrintJob::where('uuid', $uuid)->first();

    if (!$job) {
        Log::warning("CloudPRNT Job nicht gefunden", ['job_uuid' => $uuid]);
        return response('', 404);
    }

    // Generiere Job-Inhalt
    $content = app(PrintingService::class)->generateJobContent($job);

    Log::info("CloudPRNT Job bereitgestellt", [
        'job_id' => $job->id,
        'job_uuid' => $job->uuid,
        'printer_id' => $job->printer_id,
        'content_length' => strlen($content),
    ]);

    // Rohdaten (kein JSON/Base64), muss zu mediaTypes passen
    return Response::make($content, 200, [
        'Content-Type' => 'text/plain',
    ]);
});

// CloudPRNT Job Confirmation
Route::delete('/confirm/{uuid}', function (string $uuid) {
    Log::info("CloudPRNT confirm job", ['job_uuid' => $uuid]);

    $job = PrintJob::where('uuid', $uuid)->first();

    if (!$job) {
        Log::warning("CloudPRNT confirm für unbekannten Job", ['job_uuid' => $uuid]);
        return response()->json(['error' => 'Job nicht gefunden'], 404);
    }

    // Markiere Job als abgeschlossen
    $success = app(PrintingService::class)->markJobAsCompleted($job->id);

    if ($success) {
        Log::info("CloudPRNT Job bestätigt", [
            'job_id' => $job->id,
            'job_uuid' => $job->uuid,
            'printer_id' => $job->printer_id,
            'completed_at' => now()->toDateTimeString(),
        ]);
    } else {
        Log::error("CloudPRNT Job-Bestätigung fehlgeschlagen", [
            'job_id' => $job->id,
            'job_uuid' => $job->uuid,
        ]);
    }

    return response()->noContent(); // 204
});

// CloudPRNT Job Error
Route::post('/error/{uuid}', function (Request $request, string $uuid) {
    Log::info("CloudPRNT job error", [
        'job_uuid' => $uuid,
        'error' => $request->input('error'),
    ]);

    $job = PrintJob::where('uuid', $uuid)->first();

    if (!$job) {
        Log::warning("CloudPRNT error für unbekannten Job", ['job_uuid' => $uuid]);
        return response()->json(['error' => 'Job nicht gefunden'], 404);
    }

    $errorMessage = $request->input('error', 'Unbekannter Fehler');
    
    // Markiere Job als fehlgeschlagen
    $success = app(PrintingService::class)->markJobAsFailed($job->id, $errorMessage);

    if ($success) {
        Log::info("CloudPRNT Job als fehlgeschlagen markiert", [
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
});

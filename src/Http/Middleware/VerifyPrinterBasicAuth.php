<?php

namespace Platform\Printing\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Platform\Printing\Models\Printer;

class VerifyPrinterBasicAuth
{
    public function handle(Request $request, Closure $next)
    {
        // Detailliertes Request-Logging (temporär Standard-Log)
        \Illuminate\Support\Facades\Log::info('CloudPRNT API Request - Detailliert', [
            'timestamp' => now()->toDateTimeString(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'all_input' => $request->all(),
            'headers' => $request->headers->all(),
            'content_type' => $request->header('Content-Type'),
            'username' => $request->input('username'),
            'password' => $request->has('password') ? '[HIDDEN]' : null,
            'raw_content' => $request->getContent(),
        ]);

        // CloudPRNT verwendet MAC-Adresse für Authentifizierung
        $macAddress = $request->header('x-star-mac') ?? $request->input('printerMAC');
        
        \Illuminate\Support\Facades\Log::info('CloudPRNT API - MAC-basierte Authentifizierung', [
            'ip' => $request->ip(),
            'mac_address' => $macAddress,
            'serial_number' => $request->header('x-star-serial-number'),
        ]);

        if (!$macAddress) {
            \Illuminate\Support\Facades\Log::warning('CloudPRNT API - Keine MAC-Adresse', [
                'ip' => $request->ip(),
            ]);
            
            // Für Test: Erstelle einen Dummy-Drucker
            $dummyPrinter = new Printer();
            $dummyPrinter->id = 0;
            $dummyPrinter->name = 'Test-Drucker';
            $dummyPrinter->mac_address = '00:11:62:34:d9:64';
            $dummyPrinter->is_active = true;

            $request->attributes->set('printer', $dummyPrinter);
            return $next($request);
        }

        // Suche Drucker anhand der MAC-Adresse
        $printer = Printer::where('mac_address', $macAddress)
            ->where('is_active', true)
            ->first();

        if (!$printer) {
            \Illuminate\Support\Facades\Log::warning('CloudPRNT API - Drucker nicht gefunden', [
                'ip' => $request->ip(),
                'mac_address' => $macAddress,
            ]);
            return response()->json(['error' => 'Drucker nicht registriert'], 401);
        }

        \Illuminate\Support\Facades\Log::info('CloudPRNT API - Drucker authentifiziert', [
            'printer_id' => $printer->id,
            'printer_name' => $printer->name,
            'mac_address' => $macAddress,
        ]);

        // Setze den Drucker in der Request für weitere Verwendung
        $request->attributes->set('printer', $printer);

        $response = $next($request);
        
        // Debug-Headers für Test
        $response->header('X-Debug-IP', $request->ip());
        $response->header('X-Debug-URL', $request->fullUrl());
        $response->header('X-Debug-Printer', $printer->name ?? 'Unknown');
        
        return $response;
    }
}

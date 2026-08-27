<?php

namespace Platform\Printing\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Platform\Printing\Models\Printer;
use Platform\Printing\Support\PrinterSelfReport;

class VerifyPrinterBasicAuth
{
    public function handle(Request $request, Closure $next)
    {
        // Vollprotokoll jeder Anfrage - nur bei eingeschalteter Diagnose.
        // Der Drucker fragt alle paar Sekunden an; liefe das mit, schriebe
        // jede Anfrage saemtliche Header und den Rohinhalt ins Log.
        if (PrinterSelfReport::diagnose()) {
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
        }

        // CloudPRNT verwendet MAC-Adresse für Authentifizierung
        $macAddress = $request->header('x-star-mac') ?? $request->input('printerMAC');

        if (!$macAddress) {
            \Illuminate\Support\Facades\Log::warning('CloudPRNT API - Keine MAC-Adresse', [
                'ip' => $request->ip(),
            ]);
            return response()->json(['error' => 'MAC-Adresse erforderlich'], 401);
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

        // Setze den Drucker in der Request für weitere Verwendung
        $request->attributes->set('printer', $printer);

        $response = $next($request);

        // Jede Anfrage des Geraets mitschreiben - sichtbar auf der
        // Drucker-Seite. Nur so laesst sich sehen, was der Drucker zwischen
        // "gemeldet" und "abgeholt" tut: scheitern dort Versuche, oder
        // passiert schlicht nichts?
        PrinterSelfReport::verkehr(
            $printer,
            $request->method(),
            $request->getPathInfo() . ($request->getQueryString() ? '?' . $request->getQueryString() : ''),
            $response->getStatusCode(),
        );

        // Debug-Headers für Test
        $response->header('X-Debug-IP', $request->ip());
        $response->header('X-Debug-URL', $request->fullUrl());
        $response->header('X-Debug-Printer', $printer->name ?? 'Unknown');
        
        return $response;
    }
}

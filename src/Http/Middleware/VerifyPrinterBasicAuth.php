<?php

namespace Platform\Printing\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Platform\Printing\Models\Printer;

class VerifyPrinterBasicAuth
{
    public function handle(Request $request, Closure $next)
    {
        // Logging für jeden API-Aufruf
        Log::info('CloudPRNT API Request', [
            'timestamp' => now()->toDateTimeString(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'username' => $request->input('username'),
            'has_password' => $request->has('password'),
        ]);

        $username = $request->input('username');
        $password = $request->input('password');

        // Wenn keine Anmeldedaten vorhanden, erlaube trotzdem (für Test)
        if (!$username || !$password) {
            Log::warning('CloudPRNT API - Keine Anmeldedaten', [
                'ip' => $request->ip(),
                'username' => $username,
            ]);
            
            // Für Test: Erstelle einen Dummy-Drucker
            $dummyPrinter = new Printer();
            $dummyPrinter->id = 0;
            $dummyPrinter->name = 'Test-Drucker';
            $dummyPrinter->username = 'test';
            $dummyPrinter->password = 'test';
            $dummyPrinter->is_active = true;
            
            $request->attributes->set('printer', $dummyPrinter);
            return $next($request);
        }

        $printer = Printer::where('username', $username)
            ->where('password', $password)
            ->where('is_active', true)
            ->first();

        if (!$printer) {
            Log::warning('CloudPRNT API - Ungültige Anmeldedaten', [
                'ip' => $request->ip(),
                'username' => $username,
            ]);
            return response()->json(['error' => 'Ungültige Anmeldedaten'], 401);
        }

        Log::info('CloudPRNT API - Drucker authentifiziert', [
            'printer_id' => $printer->id,
            'printer_name' => $printer->name,
            'username' => $username,
        ]);

        // Setze den Drucker in der Request für weitere Verwendung
        $request->attributes->set('printer', $printer);

        return $next($request);
    }
}

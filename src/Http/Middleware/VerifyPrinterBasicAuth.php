<?php

namespace Platform\Printing\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Platform\Printing\Models\Printer;

class VerifyPrinterBasicAuth
{
    public function handle(Request $request, Closure $next)
    {
        $username = $request->input('username');
        $password = $request->input('password');

        if (!$username || !$password) {
            return response()->json(['error' => 'Anmeldedaten erforderlich'], 401);
        }

        $printer = Printer::where('username', $username)
            ->where('password', $password)
            ->where('is_active', true)
            ->first();

        if (!$printer) {
            return response()->json(['error' => 'Ungültige Anmeldedaten'], 401);
        }

        // Setze den Drucker in der Request für weitere Verwendung
        $request->attributes->set('printer', $printer);

        return $next($request);
    }
}

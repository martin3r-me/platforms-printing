<?php

namespace Platform\Printing\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Setzt Content-Length auf allen CloudPRNT-Antworten.
 *
 * Ohne den Header liefert nginx die Antwort über HTTP/1.1 als
 * "Transfer-Encoding: chunked" über eine keep-alive-Verbindung aus. Der
 * Drucker erfährt damit nirgends, wie lang die Antwort ist, und wartet auf
 * das Ende der Übertragung – bis sein eigener HTTP Response Timeout
 * zuschlägt. Der steht ab Werk auf 60 Sekunden.
 *
 * Das betraf beide Richtungen und kostete zweimal je eine Minute: den Poll
 * (der Drucker fragte dadurch effektiv im Minutentakt statt alle fünf
 * Sekunden) und den Job-Download (der Bon lag im Gerät und wurde erst beim
 * Timeout gedruckt).
 *
 * Über HTTP/2 fällt das nicht auf, weil dort das Ende des Streams die Länge
 * ersetzt. Der Drucker spricht aber HTTP/1.1.
 *
 * Als Middleware und nicht je Route, damit es auch für Endpunkte gilt, die
 * später dazukommen – die Anforderung stammt vom Gerät, nicht von der
 * einzelnen Route.
 */
class EnsureContentLength
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Gestreamte Antworten haben absichtlich keine im Voraus bekannte
        // Länge; sie hier zu erzwingen hieße, den Body im Speicher zu halten.
        if ($response instanceof StreamedResponse || $response instanceof BinaryFileResponse) {
            return $response;
        }

        // Antworten ohne Body (204 der Bestätigung, 304, 1xx) dürfen laut
        // HTTP keine Content-Length tragen.
        if ($response->isInformational() || in_array($response->getStatusCode(), [204, 304], true)) {
            return $response;
        }

        if (! $response->headers->has('Content-Length')) {
            // strlen() zählt BYTES, nicht Zeichen. Der Bon liegt bereits in
            // der Codepage des Druckers vor; mb_strlen würde ihn abschneiden.
            $response->headers->set('Content-Length', (string) strlen((string) $response->getContent()));
        }

        return $response;
    }
}

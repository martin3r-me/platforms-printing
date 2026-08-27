<?php

namespace Platform\Printing\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Platform\Printing\Models\Printer;

/**
 * Hält fest, was das Gerät über sich preisgibt.
 *
 * Zwei Quellen, bewusst getrennt gespeichert:
 *
 *   1. Der Schnappschuss - was der Drucker bei JEDEM Poll von sich aus
 *      mitschickt (Status, Kennung, Firmware, je nach Gerät mehr). Den gibt
 *      es garantiert, denn ohne Poll passiert hier gar nichts.
 *
 *   2. Die Selbstauskunft - Antworten auf gezielte Rückfragen per
 *      "clientAction". Die sind optional: Laut Spezifikation unterstützt
 *      nicht jedes Gerät jede Anfrage. Bleiben sie aus, ist auch das eine
 *      Auskunft.
 *
 * Gefragt wird nach zwei Dingen, die man dem Gerät sonst nicht ansieht:
 *
 *   GetPollInterval - In welchem Takt fragt der Drucker wirklich? In der
 *       Weboberfläche kann "5" stehen und intern etwas anderes gelten.
 *   Encodings - Welche Formate versteht er? Davon hängt ab, ob mehrere Bons
 *       mit je eigenem Schnitt in EINEN Auftrag passen: Das geht nur mit
 *       application/vnd.star.line, denn text/plain schneidet laut
 *       Spezifikation genau einmal, am Ende.
 *
 * Eine clientAction unterdrückt das Drucken in derselben Runde ("a server
 * should not set a clientAction request and expect printing at the same
 * time"). Gefragt wird deshalb nur, wenn ohnehin nichts ansteht. Teuer ist
 * das nicht - nach einer clientAction pollt das Gerät sofort erneut statt
 * das Intervall abzuwarten.
 */
class PrinterSelfReport
{
    private const TAKT          = 'poll_takt';
    private const VERKEHR       = 'verkehr';
    private const SCHNAPPSCHUSS = 'poll_snapshot';
    private const AUSKUNFT      = 'self_report';

    /**
     * Alles aus einem eingehenden Poll mitnehmen, was uns weiterhilft.
     *
     * Beide Speicher füllen sich höchstens einmal - danach kostet der Aufruf
     * nur noch zwei Array-Zugriffe, keinen Schreibvorgang.
     */
    public static function mitschreiben(Request $request, Printer $printer): void
    {
        if (! self::diagnose()) {
            self::aufraeumen($printer);
        }

        $settings = $printer->settings ?? [];
        $fertig   = ! empty($settings[self::SCHNAPPSCHUSS] ?? null)
                 && ! empty($settings[self::AUSKUNFT] ?? null);

        // Im Regelfall gibt es hier nichts zu tun: Beide Einmal-Erfassungen
        // stehen, die Diagnose ist aus. Dann auch nicht den Rumpf dekodieren -
        // das liefe sonst bei jedem Poll, den ganzen Tag.
        if ($fertig && ! self::diagnose()) {
            return;
        }

        $daten = self::rumpf($request);

        // Einmalig und damit dauerhaft billig: beides schreibt nur, solange
        // noch nichts gespeichert ist.
        self::schnappschuss($printer, $daten, $request);
        self::auskunft($printer, $daten);

        // Laufende Messung dagegen kostet je Anfrage einen Schreibvorgang -
        // bei einem Poll alle fuenf Sekunden den ganzen Tag lang. Nur bei
        // eingeschalteter Diagnose.
        if (! self::diagnose()) {
            return;
        }

        self::takt($printer);
    }

    /**
     * Den TATSAECHLICHEN Abstand zwischen zwei Polls messen.
     *
     * GetPollInterval liefert nur den eingestellten Wert. Ob das Geraet ihn
     * auch einhaelt, steht auf einem anderen Blatt: Haengt eine Antwort, kann
     * der eingestellte Takt 5 Sekunden betragen und der wirksame eine Minute.
     * Gemessen wird deshalb hier, wo die Anfragen ankommen.
     *
     * Gehalten werden die letzten zehn Abstaende - genug, um einen Takt zu
     * erkennen, und wenig genug, um in einem JSON-Feld nicht zu stoeren.
     */
    /**
     * Aufzeichnungen wegraeumen, wenn die Diagnose aus ist.
     *
     * Sonst blieben Verkehrsprotokoll und Taktmessung stehen und saehen auf
     * der Drucker-Seite aus wie laufende Messwerte, obwohl seit dem
     * Abschalten nichts mehr dazukommt. Alte Zahlen, die man fuer aktuelle
     * haelt, sind schlechter als gar keine.
     *
     * Schreibt hoechstens einmal: Ist nichts mehr da, passiert nichts.
     */
    private static function aufraeumen(Printer $printer): void
    {
        $settings = $printer->settings ?? [];

        if (! isset($settings[self::VERKEHR]) && ! isset($settings[self::TAKT])) {
            return;
        }

        unset($settings[self::VERKEHR], $settings[self::TAKT]);
        $printer->forceFill(['settings' => $settings])->save();
    }

    /** Laeuft die Diagnose? Siehe config/printing.php. */
    public static function diagnose(): bool
    {
        return (bool) config('printing.api.cloudprnt.diagnose', false);
    }

    private static function takt(Printer $printer): void
    {
        $settings = $printer->settings ?? [];
        $takt     = $settings[self::TAKT] ?? [];

        $jetzt   = now();
        $zuletzt = ! empty($takt['zuletzt']) ? \Illuminate\Support\Carbon::parse($takt['zuletzt']) : null;

        $abstaende = $takt['abstaende'] ?? [];

        if ($zuletzt) {
            // abs(): diffInSeconds() ist vorzeichenbehaftet, und $zuletzt
            // liegt in der Vergangenheit - sonst stuenden dort negative Werte.
            $abstaende[] = round(abs($jetzt->diffInSeconds($zuletzt)), 1);
            $abstaende   = array_slice($abstaende, -10);
        }

        $settings[self::TAKT] = [
            'zuletzt'   => $jetzt->toDateTimeString(),
            'abstaende' => array_values($abstaende),
        ];

        $printer->forceFill(['settings' => $settings])->save();
    }

    /**
     * Jede Anfrage des Druckers mitschreiben - Zeit, Methode, Pfad, Status.
     *
     * Der Grund: Zwischen "gemeldet" und "abgeholt" liegen rund 29 Sekunden,
     * obwohl das Geraet alle 5,4 Sekunden pollt. Was es in dieser Zeit
     * versucht, war bisher nirgends abzulesen - im Log schon, aber dort kommt
     * nicht jeder hin, und ohne diese Spur bleibt jede Erklaerung geraten.
     * Fehlgeschlagene Versuche (404, 405, 401) sind hier genauso zu sehen wie
     * eine Pause, in der das Geraet gar nichts tut. Das unterscheidet
     * "der Drucker probiert etwas und scheitert" von "der Drucker wartet".
     *
     * Ringpuffer ueber die letzten 25 Anfragen. Das ist eine Diagnose und
     * kostet einen Schreibvorgang je Anfrage; wenn die Ursache gefunden ist,
     * darf das wieder verschwinden.
     */
    public static function verkehr(Printer $printer, string $methode, string $pfad, int $status): void
    {
        if (! self::diagnose()) {
            return;
        }

        $settings = $printer->settings ?? [];
        $eintrag  = [
            'zeit'    => now()->format('H:i:s'),
            'methode' => $methode,
            'pfad'    => $pfad,
            'status'  => $status,
        ];

        $settings[self::VERKEHR] = array_slice(
            array_merge($settings[self::VERKEHR] ?? [], [$eintrag]),
            -25
        );

        $printer->forceFill(['settings' => $settings])->save();
    }

    /**
     * Der Rumpf des Polls als Array.
     *
     * Nicht über $request->input(): Das trägt nur, wenn das Gerät einen
     * Content-Type schickt, den Laravel als JSON erkennt. Verlassen kann man
     * sich darauf bei einem eingebetteten Client nicht, deshalb wird der rohe
     * Inhalt selbst dekodiert und $request->all() nur als Rückfallebene
     * genutzt.
     *
     * @return array<string, mixed>
     */
    private static function rumpf(Request $request): array
    {
        $dekodiert = json_decode((string) $request->getContent(), true);

        return is_array($dekodiert) ? $dekodiert : $request->all();
    }

    /** @param array<string, mixed> $daten */
    private static function schnappschuss(Printer $printer, array $daten, Request $request): void
    {
        $settings = $printer->settings ?? [];

        if (! empty($settings[self::SCHNAPPSCHUSS] ?? null)) {
            return;
        }

        $settings[self::SCHNAPPSCHUSS] = [
            'rumpf'      => $daten,
            'geraet'     => $request->userAgent(),
            'protokoll'  => $request->getProtocolVersion(),
            'erfasst_am' => now()->toDateTimeString(),
        ];

        $printer->forceFill(['settings' => $settings])->save();

        Log::info('CloudPRNT - erster Poll festgehalten', [
            'printer_id' => $printer->id,
            'rumpf'      => $daten,
        ]);
    }

    /** @param array<string, mixed> $daten */
    private static function auskunft(Printer $printer, array $daten): void
    {
        $settings = $printer->settings ?? [];

        if (! empty($settings[self::AUSKUNFT] ?? null)) {
            return;
        }

        // Die Firmware legt die Antworten je nach Version unterschiedlich ab.
        // Deshalb wird nichts erzwungen: Der erste Schlüssel, unter dem etwas
        // steht, gewinnt, und gespeichert wird er roh.
        $antwort = $daten['clientAction'] ?? $daten['clientActionResult'] ?? $daten['clientActions'] ?? null;

        if (empty($antwort)) {
            return;
        }

        $settings[self::AUSKUNFT] = [
            'antwort'    => $antwort,
            'erfasst_am' => now()->toDateTimeString(),
        ];

        $printer->forceFill(['settings' => $settings])->save();

        Log::info('CloudPRNT - Drucker hat auf die Rückfrage geantwortet', [
            'printer_id' => $printer->id,
            'antwort'    => $antwort,
        ]);
    }

    /**
     * Die Fragen für die Poll-Antwort - oder null, wenn schon beantwortet.
     *
     * Wird weiter gefragt, solange keine Antwort vorliegt: Ein Gerät, das die
     * Anfrage nicht kennt, überliest sie folgenlos, und ein einzelner
     * verschluckter Poll soll die Auskunft nicht dauerhaft verhindern.
     *
     * @return array<int, array{request: string, options: string}>|null
     */
    public static function fragen(Printer $printer): ?array
    {
        if (! empty(($printer->settings ?? [])[self::AUSKUNFT] ?? null)) {
            return null;
        }

        return [
            ['request' => 'GetPollInterval', 'options' => ''],
            ['request' => 'Encodings',       'options' => ''],
        ];
    }
}

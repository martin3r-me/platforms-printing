<?php

namespace Platform\Printing\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Platform\Printing\Models\Printer;

/**
 * Fragt das Gerät, was es über sich selbst weiß.
 *
 * CloudPRNT kennt dafür "clientAction": Der Server stellt im Poll eine Frage,
 * das Gerät beantwortet sie im nächsten Poll. Zwei Fragen sind hier
 * interessant, und beide beantworten etwas, das man dem Gerät sonst nicht
 * ansieht:
 *
 *   GetPollInterval - In welchem Takt fragt der Drucker wirklich? In der
 *       Weboberfläche kann "5" stehen und intern etwas anderes gelten.
 *   Encodings - Welche Formate versteht er? Davon hängt ab, ob mehrere Bons
 *       mit je eigenem Schnitt in EINEN Auftrag passen: Das geht nur mit
 *       application/vnd.star.line, denn text/plain schneidet laut
 *       Spezifikation genau einmal, am Ende.
 *
 * Wichtig: Eine clientAction unterdrückt das Drucken in derselben Runde
 * ("a server should not set a clientAction request and expect printing at the
 * same time"). Gefragt wird deshalb nur, wenn ohnehin nichts ansteht. Teuer
 * ist das nicht - nach einer clientAction pollt das Gerät sofort erneut statt
 * das Intervall abzuwarten.
 *
 * Gefragt wird einmal je Drucker. Die Antwort landet in printer.settings und
 * bleibt dort; wer neu fragen will, löscht sie dort.
 */
class PrinterSelfReport
{
    private const SCHLUESSEL = 'self_report';

    /** Liegt für diesen Drucker schon eine Antwort vor? */
    public static function vorhanden(Printer $printer): bool
    {
        return ! empty(($printer->settings ?? [])[self::SCHLUESSEL] ?? null);
    }

    /**
     * Die Fragen für die Poll-Antwort - oder null, wenn schon beantwortet.
     *
     * @return array<int, array{request: string, options: string}>|null
     */
    public static function fragen(Printer $printer): ?array
    {
        if (self::vorhanden($printer)) {
            return null;
        }

        return [
            ['request' => 'GetPollInterval', 'options' => ''],
            ['request' => 'Encodings',       'options' => ''],
        ];
    }

    /**
     * Antworten aus einem eingehenden Poll übernehmen, falls welche dabei sind.
     *
     * Das Gerät hängt sie an seinen nächsten Poll an. Das Format ist je nach
     * Firmware unterschiedlich, deshalb wird hier nichts erzwungen: Was
     * ankommt, wird roh abgelegt und protokolliert. Lesen kann man es danach
     * am Drucker-Datensatz.
     */
    public static function einsammeln(Request $request, Printer $printer): void
    {
        $antworten = $request->input('clientAction');

        if (empty($antworten)) {
            return;
        }

        $settings = $printer->settings ?? [];
        $settings[self::SCHLUESSEL] = [
            'antwort'    => $antworten,
            'erfasst_am' => now()->toDateTimeString(),
        ];

        $printer->forceFill(['settings' => $settings])->save();

        Log::info('CloudPRNT - Drucker hat über sich Auskunft gegeben', [
            'printer_id'   => $printer->id,
            'printer_name' => $printer->name,
            'antwort'      => $antworten,
        ]);
    }
}

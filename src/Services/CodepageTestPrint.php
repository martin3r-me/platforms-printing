<?php

namespace Platform\Printing\Services;

use Platform\Printing\Models\PrintJob;
use Platform\Printing\Models\Printer;

/**
 * Erzeugt einen Diagnose-Bon, der die Zeichentabelle des Geräts ausdruckt.
 *
 * Vorgeschichte: die Codepage wurde über mehrere Anläufe geraten (CP1252 ->
 * CP437 -> CP850) und aus dem Druckbild zurückgeschlossen. Das ist unnötig –
 * der Drucker kann selbst sagen, welches Byte welches Zeichen ergibt. Dieser
 * Bon druckt jedes Byte von 0x80 bis 0xFF mit seinem Hex-Wert daneben.
 *
 * Der Job läuft als Roh-Job an encodeForPrinter() vorbei, sonst würden genau
 * die zu testenden Bytes umgeschrieben.
 */
class CodepageTestPrint
{
    /**
     * Reiht den Testdruck für einen Drucker ein.
     *
     * @param  string|null  $setupHex  Setup-Bytes überschreiben; '' = keine
     */
    public function queueFor(
        Printer $printer,
        int $userId,
        ?string $setupHex = null,
        int $width = 42
    ): PrintJob {
        $content = $this->build($printer, $setupHex, $width);

        // printable_type/-_id sind nicht nullable. Der Drucker selbst ist hier
        // der passende Bezug: der Datensatz existiert, also räumt
        // printing:cleanup den Testdruck nicht als Waise weg.
        return PrintJob::create([
            'printable_type' => Printer::class,
            'printable_id' => $printer->id,
            'template' => 'codepage-test',
            'data' => [
                'raw' => true,
                'content_base64' => base64_encode($content),
            ],
            'printer_id' => $printer->id,
            'user_id' => $userId,
            'team_id' => $printer->team_id,
        ]);
    }

    /**
     * Baut den Bon: Kopf mit der aktuellen Konfiguration, danach jedes Byte von
     * 0x80 bis 0xFF mit seinem Hex-Wert. Alle Beschriftungen bewusst in reinem
     * ASCII, damit sie unabhängig von der Zeichentabelle lesbar bleiben.
     */
    public function build(Printer $printer, ?string $setupHex = null, int $width = 42): string
    {
        $width = max(24, $width);
        $setup = $this->hexToBytes($setupHex ?? $printer->setupCommandHex());

        $lines = [
            'CODEPAGE-TEST',
            str_repeat('=', $width),
            'Drucker     : ' . $printer->name,
            'Konfiguriert: ' . $printer->codepage(),
            'Setup-Bytes : ' . ($setup === '' ? '(keine)' : strtoupper(implode(' ', str_split(bin2hex($setup), 2)))),
            str_repeat('-', $width),
            'Welches Byte ergibt ae, oe, ue?',
            '',
        ];

        // "XX=Y " braucht 5 Zeichen; so viele Paare pro Zeile wie passen
        $perLine = max(1, intdiv($width, 5));
        $cells = [];

        for ($byte = 0x80; $byte <= 0xFF; $byte++) {
            $cells[] = sprintf('%02X=%s', $byte, chr($byte));
        }

        foreach (array_chunk($cells, $perLine) as $chunk) {
            $lines[] = implode(' ', $chunk);
        }

        $lines[] = '';
        $lines[] = str_repeat('-', $width);
        $lines[] = 'CP850/437: 84=ae 94=oe 81=ue E1=ss';
        $lines[] = 'CP1252   : E4=ae F6=oe FC=ue DF=ss';
        $lines[] = str_repeat('=', $width);
        $lines[] = '';
        $lines[] = '';
        $lines[] = '';

        return $setup . implode("\n", $lines);
    }

    protected function hexToBytes(string $hex): string
    {
        $hex = preg_replace('/[^0-9A-Fa-f]/', '', $hex);

        if ($hex === '' || strlen($hex) % 2 !== 0) {
            return '';
        }

        return (string) hex2bin($hex);
    }
}

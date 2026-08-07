<?php

namespace Platform\Printing\Console;

use Illuminate\Console\Command;
use Platform\Printing\Models\PrintJob;
use Platform\Printing\Models\Printer;

/**
 * Druckt die Zeichentabelle des Geräts aus, statt sie zu raten.
 *
 * Vorgeschichte: mehrere Anläufe haben nacheinander CP1252, CP437 und CP850
 * probiert und aus dem Ergebnis auf die Codepage zurückgeschlossen. Das ist
 * unnötig – der Drucker kann selbst sagen, welches Byte welches Zeichen
 * ergibt. Dieser Bon druckt jedes Byte von 0x80 bis 0xFF mit seinem Hex-Wert
 * daneben. Danach liest man ab, welches Byte ä, ö und ü ergibt, und setzt
 * PRINTING_CODEPAGE auf die Tabelle, die dazu passt.
 *
 * Der Job läuft bewusst als Roh-Job an encodeForPrinter() vorbei – sonst
 * würden genau die Bytes umgeschrieben, die getestet werden sollen.
 */
class TestCodepageCommand extends Command
{
    protected $signature = 'printing:test-codepage
                            {printer : ID oder Name des Druckers}
                            {--user= : User-ID für den Job (sonst automatisch aus dem Team)}
                            {--setup= : Setup-Bytes als Hex, z. B. "1B 52 00". "none" = keine}
                            {--width=42 : Zeichen pro Zeile des Bons}';

    protected $description = 'Testdruck: zeigt, welches Byte der Drucker als welches Zeichen druckt';

    public function handle(): int
    {
        $printer = $this->resolvePrinter();

        if (!$printer) {
            $this->error('Drucker nicht gefunden.');

            return self::FAILURE;
        }

        $userId = $this->option('user') ?: $this->guessUserId($printer);

        if (!$userId) {
            $this->error('Kein User für den Job gefunden – bitte --user=<id> angeben.');

            return self::FAILURE;
        }

        $content = $this->buildContent();

        // printable_type/-_id sind nicht nullable. Der Drucker selbst ist hier
        // der passende Bezug: der Datensatz existiert, also räumt
        // printing:cleanup den Testdruck nicht als Waise weg.
        $job = PrintJob::create([
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

        $this->info("Testdruck für '{$printer->name}' eingereiht (Job #{$job->id}, {$job->uuid}).");
        $this->line('Der Drucker holt ihn beim nächsten Poll ab.');
        $this->newLine();
        $this->line('Danach auf dem Bon ablesen, welches Byte ä, ö und ü ergibt:');
        $this->line('  84=ä 94=ö 81=ü  -> CP850 bzw. CP437 (PRINTING_CODEPAGE=CP850)');
        $this->line('  E4=ä F6=ö FC=ü  -> CP1252          (PRINTING_CODEPAGE=CP1252)');
        $this->line('Passt keine der beiden, sind die gefundenen Positionen die Antwort.');

        return self::SUCCESS;
    }

    protected function resolvePrinter(): ?Printer
    {
        $needle = (string) $this->argument('printer');

        return is_numeric($needle)
            ? Printer::find((int) $needle)
            : Printer::where('name', $needle)->first();
    }

    /**
     * Der Job braucht eine user_id (nicht nullable). Im Konsolenkontext gibt es
     * keinen angemeldeten User, daher einen aus dem Team des Druckers nehmen.
     */
    protected function guessUserId(Printer $printer): ?int
    {
        $users = \Platform\Core\Models\User::query();

        return $users->where('current_team_id', $printer->team_id)->value('id')
            ?? \Platform\Core\Models\User::query()->value('id');
    }

    /**
     * Baut den Bon: Kopf mit der aktuellen Konfiguration, danach jedes Byte
     * von 0x80 bis 0xFF mit seinem Hex-Wert. Alle Beschriftungen bewusst in
     * reinem ASCII, damit sie unabhängig von der Zeichentabelle lesbar sind.
     */
    protected function buildContent(): string
    {
        $width = max(24, (int) $this->option('width'));
        $codepage = (string) config('printing.encoding.codepage', 'CP1252');

        $setupHex = $this->option('setup') ?? (string) config('printing.encoding.setup_command_hex', '');
        $setupHex = strtolower($setupHex) === 'none' ? '' : $setupHex;
        $setup = $this->hexToBytes($setupHex);

        $lines = [
            'CODEPAGE-TEST',
            str_repeat('=', $width),
            'Konfiguriert: ' . $codepage,
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

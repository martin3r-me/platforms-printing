<?php

namespace Platform\Printing\Console;

use Illuminate\Console\Command;
use Platform\Printing\Models\Printer;
use Platform\Printing\Services\CodepageTestPrint;

/**
 * Konsolen-Variante des Testdrucks. Dieselbe Funktion steht im Backend auf der
 * Drucker-Detailseite als Button bereit – beide nutzen CodepageTestPrint.
 */
class TestCodepageCommand extends Command
{
    protected $signature = 'printing:test-codepage
                            {printer : ID oder Name des Druckers}
                            {--user= : User-ID für den Job (sonst automatisch aus dem Team)}
                            {--setup= : Setup-Bytes als Hex, z. B. "1B 52 00". "none" = keine}
                            {--width=42 : Zeichen pro Zeile des Bons}';

    protected $description = 'Testdruck: zeigt, welches Byte der Drucker als welches Zeichen druckt';

    public function handle(CodepageTestPrint $testPrint): int
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

        $setup = $this->option('setup');
        $setup = $setup !== null && strtolower($setup) === 'none' ? '' : $setup;

        $job = $testPrint->queueFor($printer, (int) $userId, $setup, (int) $this->option('width'));

        $this->info("Testdruck für '{$printer->name}' eingereiht (Job #{$job->id}, {$job->uuid}).");
        $this->line('Der Drucker holt ihn beim nächsten Poll ab.');
        $this->newLine();
        $this->line('Danach auf dem Bon ablesen, welches Byte ä, ö und ü ergibt:');
        $this->line('  84=ä 94=ö 81=ü  -> CP850 bzw. CP437');
        $this->line('  E4=ä F6=ö FC=ü  -> CP1252');
        $this->line('Die passende Codepage dann am Drucker im Backend hinterlegen.');

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
        return \Platform\Core\Models\User::query()
                ->where('current_team_id', $printer->team_id)
                ->value('id')
            ?? \Platform\Core\Models\User::query()->value('id');
    }
}

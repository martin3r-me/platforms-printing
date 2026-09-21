<?php

namespace Platform\Printing\Livewire\Jobs;

use Livewire\Component;
use Platform\Printing\Models\PrintJob;
use Platform\Printing\Services\PrintingService;

class Show extends Component
{
    public PrintJob $job;

    /** Gerenderte Druck-Vorschau (Inhalt, der an den Drucker geht) */
    public ?string $preview = null;

    /**
     * Die Vorschau in einzelne Belege zerlegt - einer je Schnittbefehl.
     *
     * Ein Sammelauftrag enthaelt alle Bons einer Veranstaltung am Stueck,
     * getrennt durch den Schnittbefehl des Druckers. Aus dem Geraet kommen
     * dadurch einzelne Belege; die Vorschau zeigt sie genauso.
     */
    public array $belege = [];

    /** Fehlermeldung, falls die Vorschau nicht erzeugt werden konnte */
    public ?string $previewError = null;

    public function mount(PrintJob $job)
    {
        $this->job = $job;
        $this->buildPreview();
    }

    public function render()
    {
        return view('printing::livewire.jobs.show', [
            'job' => $this->job,
        ])->layout('platform::layouts.app');
    }

    /**
     * Erzeugt die Druck-Vorschau aus Template + Job-Daten.
     */
    protected function buildPreview(): void
    {
        $this->preview = null;
        $this->belege = [];
        $this->previewError = null;

        try {
            $this->preview = app(PrintingService::class)->generateJobContent($this->job);
            $this->belege  = $this->alsBelege($this->preview);
        } catch (\Throwable $e) {
            $this->previewError = $e->getMessage();
        }
    }

    /**
     * Schnittbefehle, an denen der Drucker den Bon abtrennt.
     *
     * Star kennt ESC d n (Teilschnitt mit Vorschub) sowie ESC i / ESC m,
     * Epson-kompatible Geraete GS V. Welche Folge ein Auftrag benutzt, steht
     * in der Konfiguration des jeweiligen Moduls - deshalb stehen hier alle
     * gebraeuchlichen.
     */
    protected const SCHNITT = '/\x1b\x64.|\x1b[\x69\x6d]|\x1dV[\x41\x42].|\x1dV[\x00\x01]/s';

    /**
     * Zerlegt den Druckinhalt in die Belege, die aus dem Geraet kommen.
     *
     * Der Schnittbefehl ist eine Folge aus Steuerzeichen: ESC d 3. Der Drucker
     * verschluckt sie und schneidet, ein Browser zeigt vom ESC nichts und vom
     * Rest die beiden sichtbaren Zeichen - in der Vorschau stand deshalb
     * mitten im Sammelbon ein rätselhaftes "d3". Jetzt steht dort, was es
     * bedeutet: hier faengt der naechste Beleg an.
     *
     * Nur Darstellung. Gedruckt wird weiterhin, was das Template liefert.
     */
    protected function alsBelege(string $inhalt): array
    {
        $breite = $this->bonbreite($inhalt);

        $belege = [];
        foreach (preg_split(self::SCHNITT, $inhalt) ?: [$inhalt] as $stueck) {
            $stueck = $this->aufBonbreite($stueck, $breite);

            // Leerzeilen am Rand sind Papiervorschub, kein Inhalt: Der Bon
            // endet mit ein paar Zeilen Vorlauf, damit der Schnitt nicht in
            // die letzte Textzeile faellt.
            $stueck = trim($stueck, "\n");

            if (trim($stueck) !== '') {
                $belege[] = $stueck;
            }
        }

        return $belege;
    }

    /**
     * Die Bonbreite, wie sie im Bon selbst steht.
     *
     * Die Trennlinien laufen ueber die volle Breite - laenger als sie wird
     * keine Zeile gedruckt. Findet sich keine, meldet die Methode 0 und der
     * Inhalt bleibt unangetastet: lieber ungekuerzt als nach falschem Mass
     * umbrochen. Unter 20 Zeichen ist es keine Trennlinie mehr, sondern ein
     * Gedankenstrich im Text.
     */
    protected function bonbreite(string $inhalt): int
    {
        $breite = 0;

        foreach (explode("\n", $inhalt) as $zeile) {
            $zeile = rtrim($zeile);
            if ($zeile !== '' && preg_match('/^[=-]+$/', $zeile) === 1) {
                $breite = max($breite, mb_strlen($zeile));
            }
        }

        return $breite >= 20 ? $breite : 0;
    }

    /**
     * Bricht zu lange Zeilen fuer die Anzeige dort um, wo auch der Drucker
     * umbricht: an der Bonbreite.
     *
     * Auf die Rolle passt nur eine feste Zahl Zeichen; was darueber steht,
     * setzt der Drucker linksbuendig in die naechste Zeile. Die Vorschau
     * zeigte solche Zeilen dagegen ungekuerzt und machte das Papier breiter
     * als den Bon - eine zu lange Fusszeile sah damit in der Vorschau
     * ordentlich aus und fiel erst auf, als der Bon aus dem Geraet kam.
     *
     * Das Mass liefert bonbreite(); 0 heisst "nicht erkannt" und laesst den
     * Inhalt unangetastet.
     *
     * Nur Darstellung. Gedruckt wird weiterhin, was das Template liefert.
     */
    protected function aufBonbreite(string $inhalt, int $breite): string
    {
        if ($breite < 1) {
            return $inhalt;
        }

        $umbrochen = [];
        foreach (explode("\n", $inhalt) as $zeile) {
            $zeile = rtrim($zeile);

            if (mb_strlen($zeile) <= $breite) {
                $umbrochen[] = $zeile;
                continue;
            }

            // Hart trennen, nicht an Wortgrenzen: Der Drucker zaehlt Zeichen,
            // nicht Woerter.
            foreach (mb_str_split($zeile, $breite) as $stueck) {
                $umbrochen[] = $stueck;
            }
        }

        return implode("\n", $umbrochen);
    }

    public function reloadPreview()
    {
        $this->buildPreview();

        $this->dispatch('notify', [
            'type' => $this->previewError ? 'error' : 'success',
            'message' => $this->previewError
                ? 'Vorschau konnte nicht erzeugt werden'
                : 'Vorschau aktualisiert',
        ]);
    }

    /**
     * Auftrag erneut in die Warteschlange stellen.
     *
     * Auch aus "processing" heraus: Bleibt ein Auftrag dort liegen, weil der
     * Drucker ihn angeboten bekam, aber nie abgeholt hat, war er bis hier
     * nicht mehr einzuholen - er wurde weder erneut angeboten noch liess er
     * sich wiederholen. Genau dieser Fall ist der Grund, warum man den Knopf
     * ueberhaupt sucht.
     *
     * Laeuft der Auftrag wirklich noch, kann das ein zweites Exemplar
     * bedeuten. Das ist bewusst in Kauf genommen: Der Status steht daneben,
     * die Entscheidung trifft ein Mensch, und ein Bon zu viel ist besser als
     * einer zu wenig.
     */
    public function retryJob()
    {
        if (! in_array($this->job->status, ['failed', 'processing'], true)) {
            return;
        }

        $this->job->update([
            'status' => 'pending',
            'error_message' => null,
        ]);
        $this->job->logActivity('Erneut in Warteschlange gestellt');

        $this->buildPreview();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Job wird erneut versucht'
        ]);
    }

    public function cancelJob()
    {
        if (!in_array($this->job->status, ['pending', 'processing'])) {
            return;
        }

        $this->job->markAsCancelled();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Job abgebrochen'
        ]);
    }
}

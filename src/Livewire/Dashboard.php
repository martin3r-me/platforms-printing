<?php

namespace Platform\Printing\Livewire;

use Livewire\Component;
use Platform\Printing\Models\Printer;
use Platform\Printing\Models\PrinterGroup;
use Platform\Printing\Models\PrintJob;

/**
 * Die Startseite des Moduls: Drucker, Gruppen, Auftraege auf einen Blick.
 *
 * Die frueher hier sitzende Umschaltung "Persoenlich / Team" ist weg. Sie sah
 * aus wie ein Filter, war aber keiner: Jede Zahl auf dieser Seite kam aus
 * currentTeam() und blieb beim Umschalten gleich - nur die Unterzeile im Kopf
 * wechselte den Text. Ein Schalter, der nichts schaltet, ist schlimmer als
 * keiner, denn er laesst einen die falschen Zahlen fuer die richtigen halten.
 */
class Dashboard extends Component
{
    public function render()
    {
        // Statistiken für Dashboard
        $totalPrinters = Printer::currentTeam()->count();
        $activePrinters = Printer::currentTeam()->active()->count();
        $totalGroups = PrinterGroup::currentTeam()->count();
        $activeGroups = PrinterGroup::currentTeam()->active()->count();
        
        $totalJobs = PrintJob::currentTeam()->count();
        $pendingJobs = PrintJob::currentTeam()->pending()->count();
        $completedJobs = PrintJob::currentTeam()->completed()->count();
        $failedJobs = PrintJob::currentTeam()->failed()->count();
        
        // Neueste Jobs
        $recentJobs = PrintJob::with(['printable', 'printer', 'printerGroup'])
            ->currentTeam()
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        // Drucker-Status
        $printerStatus = [
            'ready' => Printer::currentTeam()->active()->whereDoesntHave('printJobs', function($q) {
                $q->whereIn('status', ['pending', 'processing']);
            })->count(),
            'busy' => Printer::currentTeam()->active()->whereHas('printJobs', function($q) {
                $q->whereIn('status', ['pending', 'processing']);
            })->count(),
            'error' => Printer::currentTeam()->active()->whereHas('printJobs', function($q) {
                $q->where('status', 'failed');
            })->count(),
        ];

        return view('printing::livewire.dashboard', [
            'totalPrinters' => $totalPrinters,
            'activePrinters' => $activePrinters,
            'totalGroups' => $totalGroups,
            'activeGroups' => $activeGroups,
            'totalJobs' => $totalJobs,
            'pendingJobs' => $pendingJobs,
            'completedJobs' => $completedJobs,
            'failedJobs' => $failedJobs,
            'recentJobs' => $recentJobs,
            'printerStatus' => $printerStatus,
        ])->layout('platform::layouts.app');
    }
}

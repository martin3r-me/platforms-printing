<?php

namespace Platform\Printing\Livewire;

use Livewire\Component;
use Platform\Printing\Models\Printer;
use Platform\Printing\Models\PrinterGroup;
use Platform\Printing\Models\PrintJob;

class Dashboard extends Component
{
    public $perspective = 'overview';

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
            'perspective' => $this->perspective,
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

<?php

namespace Platform\Printing\Livewire;

use Livewire\Component;
use Platform\ActivityLog\Models\ActivityLogActivity;
use Platform\Printing\Models\PrintJob;

/**
 * Team-weiter Aktivitäten-Feed für die Printing-Übersichtsseiten
 * (Dashboard, Listen), die an kein einzelnes Model gebunden sind.
 */
class ActivityFeed extends Component
{
    public int $limit = 20;

    public function render()
    {
        $activities = ActivityLogActivity::query()
            ->where('activityable_type', PrintJob::class)
            ->whereIn('activityable_id', PrintJob::currentTeam()->select('id'))
            ->with('user')
            ->latest()
            ->limit($this->limit)
            ->get();

        return view('printing::livewire.activity-feed', [
            'activities' => $activities,
        ]);
    }
}

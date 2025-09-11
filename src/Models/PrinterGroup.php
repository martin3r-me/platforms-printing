<?php

namespace Platform\Printing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Platform\ActivityLog\Traits\LogsActivity;

class PrinterGroup extends Model
{
    use LogsActivity;
    protected $fillable = [
        'name',
        'description',
        'is_active',
        'team_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Beziehung zum Team
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\Team::class);
    }

    /**
     * Beziehung zu Printers (Many-to-Many)
     */
    public function printers(): BelongsToMany
    {
        return $this->belongsToMany(
            Printer::class,
            'printer_group_members',
            'printer_group_id',
            'printer_id'
        );
    }

    /**
     * Beziehung zu Print Jobs
     */
    public function printJobs(): HasMany
    {
        return $this->hasMany(PrintJob::class);
    }

    /**
     * Scope für aktive Gruppen
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope für Team-spezifische Gruppen
     */
    public function scopeForTeam($query, $teamId = null)
    {
        $teamId = $teamId ?? auth()->user()?->currentTeam?->id;
        
        if ($teamId) {
            return $query->where('team_id', $teamId);
        }
        
        return $query;
    }

    /**
     * Scope für aktuelle Team-Gruppen
     */
    public function scopeCurrentTeam($query)
    {
        return $this->scopeForTeam($query);
    }

    /**
     * Gibt die Anzahl der Drucker in der Gruppe zurück
     */
    public function getPrintersCountAttribute(): int
    {
        return $this->printers()->count();
    }

    /**
     * Gibt die Anzahl der aktiven Drucker in der Gruppe zurück
     */
    public function getActivePrintersCountAttribute(): int
    {
        return $this->printers()->where('is_active', true)->count();
    }

    /**
     * Gibt die Anzahl der wartenden Jobs zurück
     */
    public function getPendingJobsCountAttribute(): int
    {
        return $this->printJobs()->where('status', 'pending')->count();
    }

    /**
     * Gibt die Anzahl der fehlgeschlagenen Jobs zurück
     */
    public function getFailedJobsCountAttribute(): int
    {
        return $this->printJobs()->where('status', 'failed')->count();
    }

    /**
     * Gibt die Anzahl der erfolgreichen Jobs zurück
     */
    public function getCompletedJobsCountAttribute(): int
    {
        return $this->printJobs()->where('status', 'completed')->count();
    }

    /**
     * Gibt den Status der Gruppe zurück
     */
    public function getStatusAttribute(): string
    {
        if (!$this->is_active) {
            return 'inactive';
        }

        $activePrinters = $this->active_printers_count;
        $failedJobs = $this->failed_jobs_count;

        if ($activePrinters === 0) {
            return 'no_printers';
        }

        if ($failedJobs > 0) {
            return 'error';
        }

        return 'ready';
    }

    /**
     * Gibt eine lesbare Status-Beschreibung zurück
     */
    public function getStatusDescriptionAttribute(): string
    {
        return match ($this->status) {
            'inactive' => 'Inaktiv',
            'no_printers' => 'Keine Drucker',
            'error' => 'Fehler',
            'ready' => 'Bereit',
            default => 'Unbekannt',
        };
    }

    /**
     * Fügt einen Drucker zur Gruppe hinzu
     */
    public function addPrinter(Printer $printer): void
    {
        if (!$this->printers()->where('printer_id', $printer->id)->exists()) {
            $this->printers()->attach($printer->id);
        }
    }

    /**
     * Entfernt einen Drucker aus der Gruppe
     */
    public function removePrinter(Printer $printer): void
    {
        $this->printers()->detach($printer->id);
    }

    /**
     * Gibt alle aktiven Print Jobs für diese Gruppe zurück
     */
    public function getActiveJobs()
    {
        return $this->printJobs()
            ->whereIn('status', ['pending', 'processing'])
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Prüft ob die Gruppe Drucker hat
     */
    public function hasPrinters(): bool
    {
        return $this->printers_count > 0;
    }

    /**
     * Prüft ob die Gruppe aktive Drucker hat
     */
    public function hasActivePrinters(): bool
    {
        return $this->active_printers_count > 0;
    }
}

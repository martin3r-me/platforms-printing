<?php

namespace Platform\Printing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Printer extends Model
{
    protected $fillable = [
        'name',
        'location',
        'username',
        'password',
        'printer_id',
        'is_active',
        'settings',
        'team_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
    ];

    protected $hidden = [
        'password',
    ];

    /**
     * Beziehung zum Team
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\Team::class);
    }

    /**
     * Beziehung zu Printer Groups (Many-to-Many)
     */
    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(
            PrinterGroup::class,
            'printer_group_members',
            'printer_id',
            'printer_group_id'
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
     * Scope für aktive Drucker
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope für Team-spezifische Drucker
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
     * Scope für aktuelle Team-Drucker
     */
    public function scopeCurrentTeam($query)
    {
        return $this->scopeForTeam($query);
    }

    /**
     * Generiert automatisch Username und Password
     */
    public static function generateCredentials(): array
    {
        return [
            'username' => 'printer_' . Str::random(8),
            'password' => Str::random(12),
        ];
    }

    /**
     * Prüft ob der Drucker in einer bestimmten Gruppe ist
     */
    public function isInGroup(PrinterGroup $group): bool
    {
        return $this->groups()->where('printer_group_id', $group->id)->exists();
    }

    /**
     * Fügt den Drucker zu einer Gruppe hinzu
     */
    public function addToGroup(PrinterGroup $group): void
    {
        if (!$this->isInGroup($group)) {
            $this->groups()->attach($group->id);
        }
    }

    /**
     * Entfernt den Drucker aus einer Gruppe
     */
    public function removeFromGroup(PrinterGroup $group): void
    {
        $this->groups()->detach($group->id);
    }

    /**
     * Gibt alle aktiven Print Jobs für diesen Drucker zurück
     */
    public function getActiveJobs()
    {
        return $this->printJobs()
            ->whereIn('status', ['pending', 'processing'])
            ->orderBy('created_at', 'asc')
            ->get();
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
     * Gibt den Status des Druckers zurück
     */
    public function getStatusAttribute(): string
    {
        if (!$this->is_active) {
            return 'inactive';
        }

        $pendingJobs = $this->pending_jobs_count;
        $failedJobs = $this->failed_jobs_count;

        if ($failedJobs > 0) {
            return 'error';
        }

        if ($pendingJobs > 0) {
            return 'busy';
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
            'error' => 'Fehler',
            'busy' => 'Beschäftigt',
            'ready' => 'Bereit',
            default => 'Unbekannt',
        };
    }

    /**
     * Boot-Methode für automatische UUID-Generierung
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($printer) {
            if (empty($printer->username)) {
                $credentials = self::generateCredentials();
                $printer->username = $credentials['username'];
                $printer->password = $credentials['password'];
            }
        });
    }
}

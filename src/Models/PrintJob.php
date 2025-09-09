<?php

namespace Platform\Printing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class PrintJob extends Model
{
    protected $fillable = [
        'uuid',
        'printable_type',
        'printable_id',
        'template',
        'data',
        'status',
        'retry_count',
        'error_message',
        'printed_at',
        'user_id',
        'team_id',
        'printer_id',
        'printer_group_id',
    ];

    protected $casts = [
        'data' => 'array',
        'printed_at' => 'datetime',
    ];

    /**
     * Beziehung zum User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\User::class);
    }

    /**
     * Beziehung zum Team
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\Team::class);
    }

    /**
     * Beziehung zum Printer
     */
    public function printer(): BelongsTo
    {
        return $this->belongsTo(Printer::class);
    }

    /**
     * Beziehung zur Printer Group
     */
    public function printerGroup(): BelongsTo
    {
        return $this->belongsTo(PrinterGroup::class);
    }

    /**
     * Polymorphe Beziehung zum printable Model
     */
    public function printable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope für wartende Jobs
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope für verarbeitende Jobs
     */
    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    /**
     * Scope für abgeschlossene Jobs
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope für fehlgeschlagene Jobs
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope für Team-spezifische Jobs
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
     * Scope für aktuelle Team-Jobs
     */
    public function scopeCurrentTeam($query)
    {
        return $this->scopeForTeam($query);
    }

    /**
     * Scope für Jobs eines bestimmten Druckers
     */
    public function scopeForPrinter($query, $printerId)
    {
        return $query->where('printer_id', $printerId);
    }

    /**
     * Scope für Jobs einer bestimmten Gruppe
     */
    public function scopeForGroup($query, $groupId)
    {
        return $query->where('printer_group_id', $groupId);
    }

    /**
     * Gibt den Status in deutscher Sprache zurück
     */
    public function getStatusDescriptionAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'Wartend',
            'processing' => 'Wird gedruckt',
            'completed' => 'Gedruckt',
            'failed' => 'Fehlgeschlagen',
            'cancelled' => 'Abgebrochen',
            default => 'Unbekannt',
        };
    }

    /**
     * Gibt die Farbe für den Status zurück
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'warning',
            'processing' => 'info',
            'completed' => 'success',
            'failed' => 'danger',
            'cancelled' => 'neutral',
            default => 'neutral',
        };
    }

    /**
     * Prüft ob der Job wartend ist
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Prüft ob der Job verarbeitet wird
     */
    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    /**
     * Prüft ob der Job abgeschlossen ist
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Prüft ob der Job fehlgeschlagen ist
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Prüft ob der Job abgebrochen wurde
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Markiert den Job als verarbeitend
     */
    public function markAsProcessing(): void
    {
        $this->update(['status' => 'processing']);
    }

    /**
     * Markiert den Job als abgeschlossen
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'printed_at' => now(),
        ]);
    }

    /**
     * Markiert den Job als fehlgeschlagen
     */
    public function markAsFailed(string $errorMessage = null): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'retry_count' => $this->retry_count + 1,
        ]);
    }

    /**
     * Markiert den Job als abgebrochen
     */
    public function markAsCancelled(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    /**
     * Gibt den Namen des printable Models zurück
     */
    public function getPrintableNameAttribute(): string
    {
        if (!$this->printable) {
            return 'Unbekannt';
        }

        return match ($this->printable_type) {
            \Platform\Sales\Models\SalesDeal::class => 'Sales Deal',
            \Platform\Helpdesk\Models\HelpdeskTicket::class => 'Helpdesk Ticket',
            default => class_basename($this->printable_type),
        };
    }

    /**
     * Gibt eine Beschreibung des Jobs zurück
     */
    public function getDescriptionAttribute(): string
    {
        $printableName = $this->printable_name;
        $template = config("printing.templates.available.{$this->template}", $this->template);
        
        return "{$printableName} - {$template}";
    }

    /**
     * Boot-Methode für automatische UUID-Generierung
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($printJob) {
            if (empty($printJob->uuid)) {
                $printJob->uuid = Str::uuid();
            }
        });
    }
}

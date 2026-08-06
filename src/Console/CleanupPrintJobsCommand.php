<?php

namespace Platform\Printing\Console;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\SoftDeletes;
use Platform\Printing\Models\PrintJob;

/**
 * Räumt die print_jobs-Tabelle auf.
 *
 * Die Config kannte timeout_minutes, max_retries und cleanup_after_days schon
 * lange, es gab aber nichts, was sie angewendet hat: hängende Jobs blieben für
 * immer auf "processing" und alte Jobs sammelten sich unbegrenzt an.
 */
class CleanupPrintJobsCommand extends Command
{
    protected $signature = 'printing:cleanup
                            {--dry-run : Nur berichten, nichts ändern}';

    protected $description = 'Print Jobs aufräumen: hängende zurückholen, verwaiste und alte entfernen';

    /** Hängende Jobs, die die Retry-Grenze erreicht haben (von reclaimStuckJobs gesetzt) */
    protected int $stuckFailed = 0;

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->comment('Trockenlauf – es wird nichts geändert.');
        }

        $requeued = $this->reclaimStuckJobs($dryRun);
        $orphaned = $this->removeOrphanedJobs($dryRun);
        $old = $this->removeOldJobs($dryRun);

        $this->table(['Aktion', 'Anzahl'], [
            ['Hängende Jobs zurückgeholt', $requeued],
            ['Hängende Jobs endgültig fehlgeschlagen', $this->stuckFailed],
            ['Verwaiste Jobs entfernt', $orphaned],
            ['Alte Jobs entfernt', $old],
        ]);

        return self::SUCCESS;
    }

    /**
     * Holt Jobs zurück, die im Status "processing" hängen.
     *
     * Der Drucker holt einen Job ab (-> processing), bestätigt ihn aber nie:
     * Papierstau, Gerät aus, Netzwerkabbruch. Ohne Zutun bleibt der Job für
     * immer in diesem Status und blockiert die Nachvollziehbarkeit.
     */
    protected function reclaimStuckJobs(bool $dryRun): int
    {
        $timeout = (int) config('printing.jobs.timeout_minutes', 30);
        $maxRetries = (int) config('printing.jobs.max_retries', 3);

        // updated_at wird beim Wechsel auf "processing" gesetzt
        $stuck = PrintJob::where('status', 'processing')
            ->where('updated_at', '<', now()->subMinutes($timeout))
            ->get();

        $requeued = 0;

        foreach ($stuck as $job) {
            // Bei jedem Zurückholen den Zähler erhöhen, sonst würde ein
            // dauerhaft defekter Drucker den Job endlos wiederholen.
            if ($job->retry_count < $maxRetries) {
                $requeued++;

                if (!$dryRun) {
                    $job->update([
                        'status' => 'pending',
                        'retry_count' => $job->retry_count + 1,
                    ]);
                    $job->logActivity('Nach Zeitüberschreitung erneut in Warteschlange', [
                        'timeout_minutes' => $timeout,
                        'retry_count' => $job->retry_count,
                    ]);
                }

                continue;
            }

            $this->stuckFailed++;

            if (!$dryRun) {
                $job->markAsFailed(
                    "Zeitüberschreitung: vom Drucker nicht innerhalb von {$timeout} Minuten bestätigt"
                );
            }
        }

        return $requeued;
    }

    /**
     * Entfernt Jobs, deren Datensatz nicht mehr existiert.
     *
     * Eine polymorphe Beziehung kann keinen Fremdschlüssel haben – wird der
     * Datensatz gelöscht, bleibt sein Job verwaist zurück. Neue Waisen
     * verhindert das Delete-Event im ServiceProvider; dies räumt die
     * Altlasten auf.
     */
    protected function removeOrphanedJobs(bool $dryRun): int
    {
        $removed = 0;

        $types = PrintJob::query()->distinct()->pluck('printable_type');

        foreach ($types as $type) {
            // printable_type kann ein FQCN (so schreibt es createJob) oder ein
            // Alias aus einer Relation::morphMap sein – beides auflösen.
            $class = Relation::getMorphedModel($type) ?? $type;

            if (!class_exists($class) || !is_subclass_of($class, Model::class)) {
                // Bewusst NICHT löschen: eine nicht ladbare Klasse kann auch
                // ein nur vorübergehend deaktiviertes Modul bedeuten. Die Jobs
                // deswegen zu entfernen wäre nicht wiederherstellbar.
                $this->warn("Typ {$type} ist nicht ladbar – übersprungen (Modul deaktiviert?).");
                continue;
            }

            $referenced = PrintJob::where('printable_type', $type)
                ->distinct()
                ->pluck('printable_id');

            if ($referenced->isEmpty()) {
                continue;
            }

            $keyName = (new $class())->getKeyName();

            // Soft-deleted Datensätze existieren weiter und können
            // wiederhergestellt werden -> ihre Jobs sind keine Waisen.
            $query = in_array(SoftDeletes::class, class_uses_recursive($class), true)
                ? $class::withTrashed()
                : $class::query();

            $existing = collect();

            foreach ($referenced->chunk(1000) as $chunk) {
                $existing = $existing->merge(
                    $query->clone()->whereIn($keyName, $chunk->all())->pluck($keyName)
                );
            }

            $orphans = $referenced->diff($existing);

            foreach ($orphans->chunk(1000) as $chunk) {
                $removed += $dryRun
                    ? $chunk->count()
                    : PrintJob::where('printable_type', $type)
                        ->whereIn('printable_id', $chunk->all())
                        ->delete();
            }
        }

        return $removed;
    }

    /**
     * Entfernt abgeschlossene Jobs nach der konfigurierten Aufbewahrungsfrist.
     * Wartende und laufende Jobs bleiben unabhängig vom Alter unberührt.
     */
    protected function removeOldJobs(bool $dryRun): int
    {
        $days = (int) config('printing.jobs.cleanup_after_days', 30);

        $query = PrintJob::whereIn('status', ['completed', 'cancelled', 'failed'])
            ->where('created_at', '<', now()->subDays($days));

        return $dryRun ? $query->count() : $query->delete();
    }
}

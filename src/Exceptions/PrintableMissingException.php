<?php

namespace Platform\Printing\Exceptions;

use Platform\Printing\Models\PrintJob;
use RuntimeException;

/**
 * Der zu druckende Datensatz existiert nicht mehr.
 *
 * print_jobs.printable_type/-_id sind nicht nullable, jeder Job hängt also an
 * einem Datensatz. Eine polymorphe Beziehung kann aber keinen Fremdschlüssel
 * haben: wird z. B. eine Reservierung gelöscht, bleibt ihr Print Job verwaist
 * zurück und $job->printable ist null.
 */
class PrintableMissingException extends RuntimeException
{
    public static function forJob(PrintJob $job): self
    {
        return new self(sprintf(
            'Der zu druckende Datensatz existiert nicht mehr (%s #%s).',
            class_basename($job->printable_type) ?: $job->printable_type,
            $job->printable_id
        ));
    }
}

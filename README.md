# Printing Service

Ein modulares Printing-System für die Platform mit CloudPRNT-Unterstützung.

## Features

- **Drucker-Management**: Verwaltung von Druckern mit Benutzername/Passwort-Authentifizierung
- **Drucker-Gruppen**: Gruppierung von Druckern für gemeinsame Print Jobs
- **Print Jobs**: Modulübergreifende Print Job-Verwaltung
- **CloudPRNT API**: Kompatible API für CloudPRNT-Drucker
- **Loose Coupling**: Keine direkten Abhängigkeiten zwischen Modulen

## Installation

1. Service Provider in `composer.json` registrieren
2. Migrations ausführen: `php artisan migrate`
3. Config veröffentlichen: `php artisan vendor:publish --tag=printing-config`

## Verwendung

### In anderen Modulen

```php
use Platform\Printing\Traits\HasPrintJobs;

class SalesDeal extends Model
{
    use HasPrintJobs;
    
    // ...
}

// Print Job erstellen
$deal->createPrintJob('deal_details', ['show_pricing' => true]);

// Print Jobs für Gruppe erstellen
$deal->createPrintJobsForGroup(1, 'deal_details');
```

### CloudPRNT API

Die API ist unter `/api/printing/` verfügbar:

- `POST /poll` - Drucker fragt nach Jobs
- `GET /job/{uuid}` - Job-Inhalt abrufen
- `DELETE /confirm/{uuid}` - Job als abgeschlossen markieren
- `POST /error/{uuid}` - Job-Fehler melden

## Datenmodell

### Printers
- `name`: Name des Druckers
- `location`: Standort
- `username`: CloudPRNT-Benutzername
- `password`: CloudPRNT-Passwort
- `is_active`: Aktiv/Inaktiv

### Printer Groups
- `name`: Name der Gruppe
- `description`: Beschreibung
- `is_active`: Aktiv/Inaktiv

### Print Jobs
- `printable_type`: Model-Klasse (polymorph)
- `printable_id`: Model-ID (polymorph)
- `template`: Template-Name
- `data`: Template-Daten (JSON)
- `status`: Job-Status
- `printer_id`: Spezifischer Drucker (optional)
- `printer_group_id`: Drucker-Gruppe (optional)

## Templates

Templates werden in der Config definiert:

```php
'templates' => [
    'default' => 'Standard',
    'deal_details' => 'Deal Details',
    'ticket_summary' => 'Ticket Zusammenfassung',
],
```

## Workflow

1. **User erstellt Print Job** für ein Model (z.B. SalesDeal)
2. **Service erstellt PrintJob** in der Datenbank
3. **Drucker fragt** via CloudPRNT API nach Jobs
4. **Service antwortet** mit Job-Details
5. **Drucker holt** Job-Inhalt ab
6. **Drucker druckt** und bestätigt
7. **Service markiert** Job als abgeschlossen

## Sicherheit

- Drucker-Authentifizierung via Username/Password
- Team-Isolation (Drucker sind team-spezifisch)
- UUID-basierte Job-Identifikation
- Logging aller API-Aufrufe

## Erweiterung

### Neue Templates

1. Template in Config hinzufügen
2. `generateJobContent()` in `PrintingService` erweitern
3. Template-Logik implementieren

### Neue Printable Models

1. `HasPrintJobs` Trait hinzufügen
2. Template-Daten in `createPrintJob()` übergeben
3. Template-Logik in `PrintingService` erweitern

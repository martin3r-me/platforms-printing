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

## Zeichensatz

Die Zeichentabelle wird **pro Drucker** gepflegt (Detailseite, Panel
„Zeichensatz“) – verschiedene Geräte drucken verschiedene Tabellen. Ist am
Drucker nichts hinterlegt, gelten die Config-Werte `printing.encoding.*` bzw.
`PRINTING_CODEPAGE`/`PRINTING_SETUP_COMMAND`.

Druckt ein Bon Umlaute falsch, muss die Tabelle nicht geraten werden – der
Drucker kann sie selbst ausgeben. Auf der Drucker-Detailseite auf **Testdruck**
klicken, oder:

```bash
php artisan printing:test-codepage 3                # Drucker-ID oder -Name
php artisan printing:test-codepage 3 --setup=none   # ohne Setup-Bytes testen
```

Der Bon zeigt jedes Byte von `0x80` bis `0xFF` mit seinem Hex-Wert. Auf dem
Ausdruck ablesen, welches Byte `ä`, `ö` und `ü` ergibt:

| Gefunden | Bedeutung |
|---|---|
| `84=ä 94=ö 81=ü` | CP850/CP437/CP858 |
| `E4=ä F6=ö FC=ü` | CP1252 |
| `CD=ä B9=ö BE=ü` | `STAR-DE` – Gerätetabelle des Star-CloudPRNT (siehe unten) |
| etwas anderes | wieder eine eigene Tabelle – die gefundenen Positionen in `codepageMap()` in `PrintingService` als neuen Eintrag ergänzen und in `DEVICE_CODEPAGES` aufnehmen |

### STAR-DE

Die getesteten Star-CloudPRNT-Geräte laufen in **keiner** Standard-Codepage.
Per Testdruck ermittelt: `Ä=A0 Ö=A1 Ü=A2 ß=A3 ö=B9 ü=BE ä=CD`. Auf `84/94/81`
– wo CP850 die Umlaute hätte – liegen dort Block- und Linienzeichen; daher kam
„Minibrötchen“ als „Minibr•tchen“ heraus.

`STAR-DE` enthält bewusst nur die am Bon zweifelsfrei abgelesenen Zeichen.
Alles andere wird nach ASCII aufgelöst (`é` → `e`, `Café` → `Cafe`) – lesbar,
statt ein Byte aus einer nur vermuteten Position zu drucken. Weitere Positionen
lassen sich jederzeit per Testdruck ergänzen.

Der Testdruck läuft bewusst an der Codepage-Umwandlung vorbei, sonst würden
genau die zu testenden Bytes umgeschrieben.

## Aufräumen

`printing:cleanup` wendet die `jobs`-Config an und läuft stündlich automatisch
(abschaltbar über `PRINTING_CLEANUP_SCHEDULED=false`):

```bash
php artisan printing:cleanup --dry-run   # nur berichten
php artisan printing:cleanup
```

1. **Hängende Jobs** – der Drucker hat den Job geholt, aber nie bestätigt
   (Papierstau, Gerät aus). Nach `timeout_minutes` zurück in die Warteschlange,
   nach `max_retries` Versuchen endgültig als fehlgeschlagen.
2. **Verwaiste Jobs** – der zugehörige Datensatz existiert nicht mehr. Neue
   Waisen verhindert das Model-Event im ServiceProvider; hier werden Altlasten
   entfernt. Soft-deleted Datensätze zählen nicht als gelöscht, und Jobs eines
   nicht ladbaren Typs (deaktiviertes Modul) werden bewusst übersprungen.
3. **Alte Jobs** – abgeschlossen/abgebrochen/fehlgeschlagen und älter als
   `cleanup_after_days`. Wartende und laufende Jobs bleiben unberührt.

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

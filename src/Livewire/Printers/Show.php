<?php

namespace Platform\Printing\Livewire\Printers;

use Livewire\Component;
use Livewire\WithPagination;
use Platform\Printing\Models\Printer;
use Platform\Printing\Models\PrintJob;
use Platform\Printing\Models\PrinterGroup;
use Platform\Printing\Services\CodepageTestPrint;

class Show extends Component
{
    use WithPagination;

    public Printer $printer;
    public $statusFilter = 'all';
    public $isDirty = false;
    public $groupAssignmentModalShow = false;
    public $selectedGroupId = null;
    public $removeGroupModalShow = false;
    public $groupToRemoveId = null;
    
    // Separate properties for form binding
    public $printer_name = '';
    public $printer_location = '';
    public $printer_username = '';
    public $printer_password = '';
    public $printer_mac_address = '';
    public $printer_codepage = '';
    public $printer_setup_hex = '';
    public $printer_is_active = false;
    public $showPassword = false;
    public $passwordModalShow = false;
    public $newPassword = '';
    public $confirmPassword = '';

    protected $queryString = [
        'statusFilter' => ['except' => 'all'],
    ];

    protected $listeners = [
        'printerUpdated' => '$refresh',
    ];

    public function mount(Printer $printer)
    {
        $this->printer = $printer;
        $this->printer_name = $printer->name;
        $this->printer_location = $printer->location;
        $this->printer_username = $printer->username;
        $this->printer_password = '';
        $this->printer_mac_address = $printer->mac_address;
        $this->printer_codepage = $printer->codepage();
        $this->printer_setup_hex = $printer->setupCommandHex();
        $this->printer_is_active = $printer->is_active;
        $this->showPassword = false;
    }

    public function updated($propertyName)
    {
        if (in_array($propertyName, ['printer_name', 'printer_location', 'printer_username', 'printer_password', 'printer_mac_address', 'printer_codepage', 'printer_setup_hex', 'printer_is_active'])) {
            $this->isDirty = true;
        }
    }

    public function render()
    {
        // Refresh printer data
        $this->printer = $this->printer->fresh(['groups', 'activities']);
        
        $jobs = PrintJob::where('printer_id', $this->printer->id)
            ->when($this->statusFilter !== 'all', function ($query) {
                $query->where('status', $this->statusFilter);
            })
            ->with(['printable', 'user'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $stats = [
            'total' => PrintJob::where('printer_id', $this->printer->id)->count(),
            'pending' => PrintJob::where('printer_id', $this->printer->id)->pending()->count(),
            'completed' => PrintJob::where('printer_id', $this->printer->id)->completed()->count(),
            'failed' => PrintJob::where('printer_id', $this->printer->id)->failed()->count(),
        ];

        $availableGroups = PrinterGroup::where('is_active', true)
            ->where('team_id', auth()->user()->currentTeam->id)
            ->whereNotIn('id', $this->printer->groups->pluck('id'))
            ->orderBy('name')
            ->get();

        return view('printing::livewire.printers.show', [
            'jobs' => $jobs,
            'stats' => $stats,
            'availableGroups' => $availableGroups,
        ])->layout('platform::layouts.app');
    }



    public function updatedStatusFilter()
    {
        $this->resetPage();
    }

    /**
     * Auftrag erneut in die Warteschlange stellen.
     *
     * Auch aus "processing" heraus: Bleibt ein Auftrag dort liegen, weil der
     * Drucker ihn angeboten bekam, aber nie abgeholt hat, war er bis hier
     * nicht mehr einzuholen - er wurde weder erneut angeboten noch liess er
     * sich wiederholen. Genau dieser Fall ist der Grund, warum man den Knopf
     * ueberhaupt sucht.
     *
     * Laeuft der Auftrag wirklich noch, kann das ein zweites Exemplar
     * bedeuten. Das ist bewusst in Kauf genommen: Der Status steht daneben,
     * die Entscheidung trifft ein Mensch, und ein Bon zu viel ist besser als
     * einer zu wenig.
     */
    public function retryJob(PrintJob $job)
    {
        if (! in_array($job->status, ['failed', 'processing'], true)) {
            return;
        }

        $job->update([
            'status' => 'pending',
            'error_message' => null,
        ]);

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Job wird erneut versucht'
        ]);
    }

    public function cancelJob(PrintJob $job)
    {
        if (!in_array($job->status, ['pending', 'processing'])) {
            return;
        }

        $job->markAsCancelled();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Job abgebrochen'
        ]);
    }

    public function save()
    {
        $this->validate([
            'printer_name' => 'required|string|max:255',
            'printer_location' => 'nullable|string|max:255',
            'printer_username' => 'nullable|string|max:255|unique:printers,username,' . $this->printer->id,
            'printer_password' => 'nullable|string|max:255',
            'printer_mac_address' => 'nullable|string|max:255|unique:printers,mac_address,' . $this->printer->id,
            'printer_codepage' => 'required|string|in:' . implode(',', array_keys($this->codepageOptions())),
            // Nur Hex-Ziffern und Trenner, und immer vollständige Bytes
            'printer_setup_hex' => ['nullable', 'string', 'regex:/^\s*([0-9A-Fa-f]{2}[\s,]*)*$/'],
        ], [
            'printer_setup_hex.regex' => 'Bitte Bytes als Hex-Paare angeben, z. B. "1B 52 00".',
        ]);

        $data = [
            'name' => $this->printer_name,
            'location' => $this->printer_location,
            'username' => $this->printer_username,
            'mac_address' => $this->printer_mac_address ?: null,
            'is_active' => $this->printer_is_active,
            'settings' => array_merge($this->printer->settings ?? [], [
                'codepage' => $this->printer_codepage,
                'setup_command_hex' => $this->printer_setup_hex,
            ]),
        ];

        if ($this->printer_password) {
            $data['password'] = $this->printer_password;
        }

        $this->printer->update($data);
        $this->isDirty = false;

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Drucker erfolgreich gespeichert'
        ]);
    }

    /**
     * Auswahl für die Zeichentabelle. CP437/CP850/CP858 legen ä/ö/ü an
     * dieselben Positionen (84/94/81) und unterscheiden sich nur im übrigen
     * oberen Bereich; CP1252 nutzt E4/F6/FC.
     */
    public function codepageOptions(): array
    {
        return [
            'STAR-DE' => 'Gerätetabelle Star (ä=CD ö=B9 ü=BE Ä=A0 Ö=A1 Ü=A2 ß=A3)',
            'CP850' => 'CP850 – DOS Westeuropa (ä=84 ö=94 ü=81)',
            'CP437' => 'CP437 – DOS US (ä=84 ö=94 ü=81)',
            'CP858' => 'CP858 – wie CP850, zusätzlich € (ä=84 ö=94 ü=81)',
            'CP1252' => 'CP1252 – Windows Westeuropa (ä=E4 ö=F6 ü=FC)',
            'UTF-8' => 'UTF-8 – nur wenn der Drucker echtes UTF-8 kann',
        ];
    }

    /**
     * Reiht einen Testdruck ein, der die Zeichentabelle des Geräts ausdruckt.
     * Damit lässt sich die richtige Codepage ablesen statt sie zu raten.
     */
    public function testPrint()
    {
        // Der Testdruck nutzt die GESPEICHERTE Einstellung des Druckers. Ohne
        // diesen Hinweis testet man nach einer Umstellung ahnungslos noch die
        // alte Tabelle und hält das Ergebnis für aussagekräftig.
        if ($this->isDirty) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Erst speichern – sonst druckt der Test noch mit der bisher gespeicherten Tabelle.',
            ]);

            return;
        }

        if (!$this->printer->is_active) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Der Drucker ist inaktiv und holt keine Jobs ab.',
            ]);

            return;
        }

        $job = app(CodepageTestPrint::class)->queueFor($this->printer, (int) auth()->id());

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => "Testdruck eingereiht (Job #{$job->id}) – der Drucker holt ihn beim nächsten Poll ab.",
        ]);
    }

    public function addGroup()
    {
        $this->groupAssignmentModalShow = true;
    }

    public function closeGroupAssignmentModal()
    {
        $this->groupAssignmentModalShow = false;
        $this->selectedGroupId = null;
    }

    public function assignGroup()
    {
        if (!$this->selectedGroupId) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Bitte wählen Sie eine Gruppe aus'
            ]);
            return;
        }

        $group = PrinterGroup::find($this->selectedGroupId);
        if ($group) {
            $this->printer->addToGroup($group);
            $this->closeGroupAssignmentModal();

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Drucker wurde der Gruppe zugewiesen'
            ]);
        }
    }

    public function editGroup($groupId)
    {
        // TODO: Implement group editing
        $this->dispatch('notify', [
            'type' => 'info',
            'message' => 'Gruppen-Bearbeitung wird implementiert'
        ]);
    }

    public function removeGroup($groupId)
    {
        // kept for backward compatibility if called directly
        $this->openRemoveGroupModal($groupId);
    }

    public function openRemoveGroupModal($groupId)
    {
        $this->groupToRemoveId = $groupId;
        $this->removeGroupModalShow = true;
    }

    public function closeRemoveGroupModal()
    {
        $this->removeGroupModalShow = false;
        $this->groupToRemoveId = null;
    }

    public function confirmRemoveGroup()
    {
        $group = $this->groupToRemoveId ? PrinterGroup::find($this->groupToRemoveId) : null;
        if ($group) {
            $this->printer->removeFromGroup($group);
        }
        $this->closeRemoveGroupModal();
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Gruppe wurde entfernt'
        ]);
    }

    public function togglePasswordVisibility()
    {
        $this->showPassword = !$this->showPassword;
    }

    public function openPasswordModal()
    {
        $this->passwordModalShow = true;
        $this->newPassword = '';
        $this->confirmPassword = '';
    }

    public function closePasswordModal()
    {
        $this->passwordModalShow = false;
        $this->newPassword = '';
        $this->confirmPassword = '';
    }

    public function updatePassword()
    {
        $this->validate([
            'newPassword' => 'required|string|min:6|max:255',
            'confirmPassword' => 'required|same:newPassword',
        ]);

        $this->printer->update([
            'password' => $this->newPassword
        ]);

        $this->closePasswordModal();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Passwort wurde erfolgreich geändert'
        ]);
    }

    public function getCurrentPasswordProperty()
    {
        return $this->showPassword ? $this->printer->password : str_repeat('•', 8);
    }

    /**
     * URLs der CloudPRNT-Endpunkte für die Anzeige.
     *
     * Aus den registrierten Routen abgeleitet und nicht von Hand
     * zusammengesetzt – vorher stand hier /api/printing/... statt
     * /printing/api/..., also genau die Segmente vertauscht.
     *
     * route() taugt dafür nicht: die Platzhalter müssten als Parameterwert
     * übergeben werden ('uuid' => '{uuid}'), und Laravel wirft dann eine
     * UrlGenerationException – nach dem Ersetzen stehen weiter geschweifte
     * Klammern in der URI, was wie ein fehlender Parameter aussieht. Daher
     * direkt die URI der Route nehmen.
     */
    public function getApiEndpointsProperty(): array
    {
        $routes = app('router')->getRoutes();

        $uri = function (string $name) use ($routes): string {
            $route = $routes->getByName($name);

            return $route ? url($route->uri()) : '–';
        };

        return [
            'poll' => $uri('printing.api.poll'),
            'download' => $uri('printing.api.job.download'),
            'confirm' => $uri('printing.api.job.confirm'),
        ];
    }

    public function getBasicAuthHeaderProperty()
    {
        if ($this->printer->username && $this->printer->password) {
            return 'Basic ' . base64_encode($this->printer->username . ':' . $this->printer->password);
        }
        return null;
    }
}

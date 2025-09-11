<?php

namespace Platform\Printing\Livewire\Printers;

use Livewire\Component;
use Livewire\WithPagination;
use Platform\Printing\Models\Printer;
use Platform\Printing\Models\PrintJob;
use Platform\Printing\Models\PrinterGroup;

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
        $this->printer_is_active = $printer->is_active;
        $this->showPassword = false;
    }

    public function updated($propertyName)
    {
        if (in_array($propertyName, ['printer_name', 'printer_location', 'printer_username', 'printer_password', 'printer_is_active'])) {
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

    public function retryJob(PrintJob $job)
    {
        if ($job->status !== 'failed') {
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
        ]);

        $data = [
            'name' => $this->printer_name,
            'location' => $this->printer_location,
            'username' => $this->printer_username,
            'is_active' => $this->printer_is_active,
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

    public function getBasicAuthHeaderProperty()
    {
        if ($this->printer->username && $this->printer->password) {
            return 'Basic ' . base64_encode($this->printer->username . ':' . $this->printer->password);
        }
        return null;
    }
}

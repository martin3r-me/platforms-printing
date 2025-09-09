<?php

namespace Platform\Printing\Livewire;

use Livewire\Component;

class Sidebar extends Component
{
    public function render()
    {
        return view('printing::livewire.sidebar')->layout('platform::layouts.app');
    }
}

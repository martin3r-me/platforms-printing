<?php

use Illuminate\Support\Facades\Route;
use Platform\Printing\Livewire\Dashboard;
use Platform\Printing\Livewire\Sidebar;
use Platform\Printing\Livewire\Printers\Index as PrintersIndex;
use Platform\Printing\Livewire\Printers\Show as PrintersShow;
use Platform\Printing\Livewire\Groups\Index as GroupsIndex;
use Platform\Printing\Livewire\Groups\Show as GroupsShow;
use Platform\Printing\Livewire\Jobs\Index as JobsIndex;
use Platform\Printing\Livewire\Jobs\Show as JobsShow;

// Dashboard
Route::get('/', Dashboard::class)->name('printing.dashboard');

// Printers
Route::get('/printers', PrintersIndex::class)->name('printing.printers.index');
Route::get('/printers/{printer}', PrintersShow::class)->name('printing.printers.show');

// Groups
Route::get('/groups', GroupsIndex::class)->name('printing.groups.index');
Route::get('/groups/{group}', GroupsShow::class)->name('printing.groups.show');

// Jobs
Route::get('/jobs', JobsIndex::class)->name('printing.jobs.index');
Route::get('/jobs/{job}', JobsShow::class)->name('printing.jobs.show');

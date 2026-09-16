<?php

use Illuminate\Support\Facades\Route;

Route::view('/about', 'welcome')->name('home');

Route::livewire('/', 'tasks.index')->name('tasks.index');

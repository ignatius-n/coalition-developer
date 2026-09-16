<?php

use Illuminate\Support\Facades\Route;

Route::view('/about', 'welcome')->name('home');

Route::livewire('/', 'pages::task.index')->name('tasks.index');

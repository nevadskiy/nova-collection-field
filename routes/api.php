<?php

use Illuminate\Support\Facades\Route;
use Nevadskiy\Nova\Collection\Http\Controllers\AttachableResourceController;

Route::get('/{resource}/{field}/{attachable}', [AttachableResourceController::class, 'index']);

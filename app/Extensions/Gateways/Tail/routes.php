<?php

use Illuminate\Support\Facades\Route;

Route::post('/tebex/webhook', [App\Extensions\Gateways\Tail\Tail::class, 'webhook']);

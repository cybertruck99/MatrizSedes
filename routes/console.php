<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('sedes:info', function () {
    $this->info('MatrizSedes instalado correctamente.');
});

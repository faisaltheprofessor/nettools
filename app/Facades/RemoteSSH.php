<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class RemoteSSH extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'remote.ssh';
    }
}

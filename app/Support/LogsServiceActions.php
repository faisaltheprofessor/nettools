<?php

namespace App\Support;

use App\Models\ServiceAction;

trait LogsServiceActions
{
    protected function logAction(string $service, string $action, ?string $target = null, array $meta = []): void
    {
        ServiceAction::log($service, $action, $target, $meta);
    }
}

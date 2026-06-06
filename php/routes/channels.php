<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('tenant.{tenantId}', function ($user, $tenantId) {
    return $user->tenant_id == $tenantId;
});

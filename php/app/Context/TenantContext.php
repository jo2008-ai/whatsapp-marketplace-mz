<?php

namespace App\Context;

use App\Models\Tenant;
use Illuminate\Http\Request;

class TenantContext
{
    private ?Tenant $tenant = null;
    private ?Request $request = null;

    public static function make(Tenant $tenant, ?Request $request = null): static
    {
        $context = new static();
        $context->tenant = $tenant;
        $context->request = $request;

        return $context;
    }

    public static function empty(): static
    {
        return new static();
    }

    public function tenant(): Tenant
    {
        if (!$this->tenant) {
            throw new \RuntimeException('No tenant set in context.');
        }

        return $this->tenant;
    }

    public function tenantId(): int
    {
        return $this->tenant()->id;
    }

    public function hasTenant(): bool
    {
        return $this->tenant !== null;
    }

    public function request(): ?Request
    {
        return $this->request;
    }

    public function config(string $key, mixed $default = null): mixed
    {
        return data_get($this->tenant, $key, $default);
    }

    public function __get(string $name): mixed
    {
        return $this->tenant?->{$name};
    }
}

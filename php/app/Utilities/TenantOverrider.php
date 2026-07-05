<?php

namespace App\Utilities;

use App\Models\Tenant;

class TenantOverrider
{
    public static function load(Tenant $tenant): void
    {
        self::loadTimezone($tenant);
        self::loadLocale($tenant);
        self::loadCurrency($tenant);
        self::loadCachePrefix($tenant);
        self::loadFilesystem($tenant);
    }

    private static function loadTimezone(Tenant $tenant): void
    {
        $timezone = $tenant->timezone ?? config('app.timezone');
        config(['app.timezone' => $timezone]);
        date_default_timezone_set($timezone);
    }

    private static function loadLocale(Tenant $tenant): void
    {
        $locale = $tenant->idioma ?? config('app.locale');
        app()->setLocale($locale);
    }

    private static function loadCurrency(Tenant $tenant): void
    {
        $moeda = $tenant->moeda ?? 'MZN';
        config(['money.defaults.currency' => $moeda]);
    }

    private static function loadCachePrefix(Tenant $tenant): void
    {
        config(['cache.prefix' => "tenant_{$tenant->id}_" . env('CACHE_PREFIX', 'marketplace_cache')]);
    }

    private static function loadFilesystem(Tenant $tenant): void
    {
        $defaultDisk = config('filesystems.default');
        $currentUrl = config("filesystems.disks.{$defaultDisk}.url");

        if ($currentUrl) {
            config([
                "filesystems.disks.{$defaultDisk}.url" => $currentUrl . '/' . $tenant->id,
            ]);
        }
    }
}

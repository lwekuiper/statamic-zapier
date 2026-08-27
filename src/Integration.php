<?php

declare(strict_types=1);

namespace Lwekuiper\StatamicZapier;

use Statamic\Facades\Addon;

final class Integration
{
    public const HANDLE = 'zapier';

    public const PACKAGE = 'lwekuiper/statamic-zapier';

    public const LABEL = 'Zapier';

    public static function edition(): string
    {
        return (string) Addon::get(self::PACKAGE)->edition();
    }

    public static function isPro(): bool
    {
        return self::edition() === 'pro';
    }

    public static function multisite(): bool
    {
        return self::isPro() && self::hasMultisite();
    }

    public static function hasMultisite(): bool
    {
        return class_exists(__NAMESPACE__.'\\Http\\Controllers\\AddonConfigController');
    }

    public static function route(string $name, array $params = []): string
    {
        return cp_route(self::HANDLE.'.'.$name, $params);
    }

    public static function storeKey(): string
    {
        return self::HANDLE.'-form-configs';
    }

    public static function view(string $page): string
    {
        return self::HANDLE.'::'.$page;
    }

    public static function storeDirectory(): string
    {
        return self::config('store_directory', base_path('resources/'.self::HANDLE));
    }

    public static function config(string $key, mixed $default = null): mixed
    {
        return config('statamic.'.self::HANDLE.'.'.$key) ?? $default;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

#[Fillable(['key', 'value'])]
class Setting extends Model
{
    /**
     * The cache key used for the settings key/value map.
     */
    public const CACHE_KEY = 'settings.all';

    /**
     * Get every setting as a key => value map, cached until a setting changes.
     *
     * @return array<string, string|null>
     */
    public static function allCached(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function (): array {
            return self::query()->pluck('value', 'key')->all();
        });
    }

    /**
     * Get a single setting value.
     */
    public static function get(string $key, ?string $default = null): ?string
    {
        return self::allCached()[$key] ?? $default;
    }

    /**
     * Create or update a single setting and flush the cached map.
     */
    public static function set(string $key, ?string $value): void
    {
        self::query()->updateOrCreate(['key' => $key], ['value' => $value]);

        Cache::forget(self::CACHE_KEY);
    }
}

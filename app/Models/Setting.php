<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class Setting extends Model
{
    protected $fillable = ['group', 'key', 'value', 'type', 'label', 'description', 'is_public'];

    protected function casts(): array
    {
        return ['is_public' => 'boolean'];
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $settings = Cache::remember('settings.all', 3600, fn() =>
            static::all()->keyBy('key')->toArray()
        );

        if (!isset($settings[$key])) return $default;

        $setting = $settings[$key];
        return match($setting['type']) {
            'boolean'   => (bool) $setting['value'],
            'json'      => json_decode($setting['value'], true),
            'encrypted' => static::decryptValue($setting['value'], $default),
            default     => $setting['value'] ?? $default,
        };
    }

    public static function set(string $key, mixed $value, string $group = 'general'): void
    {
        static::updateOrCreate(['key' => $key], [
            'group' => $group,
            'value' => is_array($value) ? json_encode($value) : (string) $value,
        ]);
        Cache::forget('settings.all');
    }

    public static function setEncrypted(string $key, string $value, string $group = 'integrations'): void
    {
        static::updateOrCreate(['key' => $key], [
            'group' => $group,
            'value' => Crypt::encryptString($value),
            'type'  => 'encrypted',
        ]);
        Cache::forget('settings.all');
    }

    public static function isEncrypted(string $key): bool
    {
        $settings = Cache::remember('settings.all', 3600, fn() =>
            static::all()->keyBy('key')->toArray()
        );
        return ($settings[$key]['type'] ?? '') === 'encrypted';
    }

    private static function decryptValue(mixed $value, mixed $default): mixed
    {
        if (empty($value)) return $default;
        try {
            return Crypt::decryptString($value);
        } catch (\Throwable) {
            return $default;
        }
    }

    public static function flush(): void
    {
        Cache::forget('settings.all');
    }
}

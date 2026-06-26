<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    /**
     * Get a setting value by key.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = self::where('key', $key)->first();

        if (!$setting) {
            return $default;
        }

        return self::shouldEncrypt($key)
            ? self::decryptValue($setting->value, $default)
            : $setting->value;
    }

    /**
     * Set a setting value.
     */
    public static function set(string $key, mixed $value): void
    {
        self::updateOrCreate(
            ['key' => $key],
            ['value' => self::shouldEncrypt($key) && filled($value) ? self::encryptValue((string) $value) : $value]
        );
    }

    protected static function shouldEncrypt(string $key): bool
    {
        return str_ends_with($key, '_password');
    }

    protected static function encryptValue(string $value): string
    {
        return 'encrypted:'.Crypt::encryptString($value);
    }

    protected static function decryptValue(mixed $value, mixed $default = null): mixed
    {
        if (!is_string($value) || !str_starts_with($value, 'encrypted:')) {
            return $value;
        }

        try {
            return Crypt::decryptString(substr($value, strlen('encrypted:')));
        } catch (\Throwable) {
            return $default;
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Réglage métier clé/valeur. Lecture mise en cache (les réglages changent rarement mais
 * sont lus souvent) ; le cache est invalidé à chaque écriture.
 *
 * @property string $key
 * @property string|null $value
 */
class Setting extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    /** @var list<string> */
    protected $fillable = ['key', 'value'];

    private const CACHE_PREFIX = 'setting:';

    public static function get(string $key, ?string $default = null): ?string
    {
        return Cache::rememberForever(
            self::CACHE_PREFIX.$key,
            fn (): ?string => self::query()->whereKey($key)->value('value') ?? $default,
        );
    }

    public static function put(string $key, ?string $value): void
    {
        self::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget(self::CACHE_PREFIX.$key);
    }

    /**
     * À appeler après une écriture directe pour purger le cache d'une clé.
     */
    public static function forget(string $key): void
    {
        Cache::forget(self::CACHE_PREFIX.$key);
    }
}

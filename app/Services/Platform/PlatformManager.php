<?php

declare(strict_types=1);

namespace App\Services\Platform;

use App\Enums\PlatformMode;
use App\Models\Setting;
use Throwable;

/**
 * Point de vérité unique du mode de plateforme et des capacités.
 *
 * Le reste de l'application ne teste JAMAIS le mode directement : il interroge une
 * capacité via allows(). Cela découple les fonctionnalités du modèle économique et rend
 * l'ajout d'un futur mode aussi simple qu'un nouveau préréglage (config/platform.php).
 */
class PlatformManager
{
    private const SETTING_KEY = 'platform_mode';

    private ?PlatformMode $resolved = null;

    public function mode(): PlatformMode
    {
        if ($this->resolved instanceof PlatformMode) {
            return $this->resolved;
        }

        $value = $this->storedMode() ?? (string) config('platform.default_mode');

        return $this->resolved = PlatformMode::tryFrom($value) ?? PlatformMode::Enterprise;
    }

    public function isCommercial(): bool
    {
        return $this->mode() === PlatformMode::Commercial;
    }

    public function isEnterprise(): bool
    {
        return $this->mode() === PlatformMode::Enterprise;
    }

    /**
     * La capacité demandée est-elle active dans le mode courant ?
     */
    public function allows(string $capability): bool
    {
        /** @var array<string, list<string>> $presets */
        $presets = config('platform.presets', []);

        return in_array($capability, $presets[$this->mode()->value] ?? [], strict: true);
    }

    /**
     * Change le mode de l'instance et purge le cache de résolution.
     */
    public function setMode(PlatformMode $mode): void
    {
        Setting::put(self::SETTING_KEY, $mode->value);
        $this->resolved = null;
    }

    /**
     * Lecture du mode stocké, tolérante à l'absence de la table (installation, migrations).
     */
    private function storedMode(): ?string
    {
        try {
            return Setting::get(self::SETTING_KEY);
        } catch (Throwable) {
            return null;
        }
    }
}

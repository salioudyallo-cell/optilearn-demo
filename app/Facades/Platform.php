<?php

declare(strict_types=1);

namespace App\Facades;

use App\Enums\PlatformMode;
use App\Services\Platform\PlatformManager;
use Illuminate\Support\Facades\Facade;

/**
 * @method static PlatformMode mode()
 * @method static bool isCommercial()
 * @method static bool isEnterprise()
 * @method static bool allows(string $capability)
 * @method static void setMode(PlatformMode $mode)
 *
 * @see PlatformManager
 */
class Platform extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return PlatformManager::class;
    }
}

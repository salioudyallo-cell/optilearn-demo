<?php

declare(strict_types=1);

namespace App\Facades;

use App\Models\User;
use App\Services\Tracking\Tracker;
use Illuminate\Support\Facades\Facade;

/**
 * @method static void event(string $name, array<string, mixed> $properties = [], ?User $user = null)
 *
 * @see Tracker
 */
class Track extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return Tracker::class;
    }
}

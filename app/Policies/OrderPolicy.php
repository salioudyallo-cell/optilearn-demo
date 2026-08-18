<?php

declare(strict_types=1);

namespace App\Policies;

use App\Facades\Platform;
use App\Models\Order;
use App\Models\User;

/**
 * Gestion des commandes : administrateur, et uniquement quand la capacité « cart » est
 * active (mode Commercial). Invisible en mode Entreprise.
 */
class OrderPolicy
{
    private function enabled(User $user): bool
    {
        return $user->isAdmin() && Platform::allows('cart');
    }

    public function viewAny(User $user): bool
    {
        return $this->enabled($user);
    }

    public function view(User $user, Order $order): bool
    {
        return $this->enabled($user);
    }
}

<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\PlatformMode;
use App\Facades\Platform;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Configuration de la plateforme : choix du mode (Commercial / Entreprise).
 *
 * Le mode pilote automatiquement, dans toute l'application, les fonctionnalités visibles
 * (prix, panier, paiement… en commercial ; organisations, groupes, affectation… en
 * entreprise). Réservé à l'administrateur.
 */
class PlatformSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static string|UnitEnum|null $navigationGroup = 'Administration';

    protected static ?string $navigationLabel = 'Configuration';

    protected static ?int $navigationSort = 90;

    protected static ?string $title = 'Configuration de la plateforme';

    protected string $view = 'filament.pages.platform-settings';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->isAdmin();
    }

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('activateEnterprise')
                ->label('Activer le mode Entreprise')
                ->icon(Heroicon::OutlinedBuildingOffice2)
                ->color('info')
                ->visible(fn (): bool => Platform::isCommercial())
                ->requiresConfirmation()
                ->modalHeading('Passer en mode Entreprise ?')
                ->modalDescription('Les prix, le panier, le paiement et les promotions seront masqués dans toute l’application. Les apprenants ne verront que les formations qui leur sont attribuées.')
                ->action(fn () => $this->switchTo(PlatformMode::Enterprise)),

            Action::make('activateCommercial')
                ->label('Activer le mode Commercial')
                ->icon(Heroicon::OutlinedShoppingBag)
                ->color('warning')
                ->visible(fn (): bool => Platform::isEnterprise())
                ->requiresConfirmation()
                ->modalHeading('Passer en mode Commercial ?')
                ->modalDescription('Le catalogue public, les tarifs, le panier, le paiement en ligne et les promotions seront activés.')
                ->action(fn () => $this->switchTo(PlatformMode::Commercial)),
        ];
    }

    private function switchTo(PlatformMode $mode): void
    {
        Platform::setMode($mode);

        Notification::make()
            ->success()
            ->title('Mode de plateforme mis à jour')
            ->body('La plateforme est désormais en '.$mode->label().'.')
            ->send();
    }
}

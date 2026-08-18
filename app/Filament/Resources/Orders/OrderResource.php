<?php

declare(strict_types=1);

namespace App\Filament\Resources\Orders;

use App\Enums\OrderStatus;
use App\Facades\Platform;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Models\Order;
use App\Services\Payment\OrderFulfiller;
use App\Support\Money;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static ?string $navigationLabel = 'Commandes';

    protected static ?string $modelLabel = 'commande';

    protected static ?string $pluralModelLabel = 'commandes';

    protected static string|\UnitEnum|null $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 80;

    public static function shouldRegisterNavigation(): bool
    {
        return Platform::allows('cart');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('provider_ref')->label('Référence')->searchable(),
                TextColumn::make('user.name')->label('Acheteur')->searchable(),
                TextColumn::make('course.title')->label('Formation')->searchable()->limit(30),
                TextColumn::make('amount_fcfa')->label('Montant')->formatStateUsing(fn (int $state): string => Money::fcfa($state)),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn (OrderStatus $state): string => $state->label())
                    ->color(fn (OrderStatus $state): string => match ($state) {
                        OrderStatus::Paid => 'success',
                        OrderStatus::Pending => 'warning',
                        OrderStatus::Failed => 'danger',
                        OrderStatus::Refunded => 'gray',
                    }),
                TextColumn::make('created_at')->label('Créée le')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Statut')->options(OrderStatus::class),
            ])
            ->recordActions([
                Action::make('confirmPayment')
                    ->label('Confirmer le paiement')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Order $record): bool => $record->status === OrderStatus::Pending)
                    ->requiresConfirmation()
                    ->modalDescription('Le paiement sera marqué comme reçu et l’accès à la formation activé pour l’acheteur.')
                    ->action(function (Order $record): void {
                        app(OrderFulfiller::class)->markPaid($record);
                        Notification::make()->success()->title('Paiement confirmé, accès activé')->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
        ];
    }
}

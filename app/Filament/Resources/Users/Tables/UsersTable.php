<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Tables;

use App\Enums\UserRole;
use App\Facades\Platform;
use App\Models\User;
use App\Services\InvitationService;
use App\Services\UserDataExporter;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label('E-mail')
                    ->searchable(),

                TextColumn::make('role')
                    ->label('Rôle')
                    ->badge()
                    ->color(fn (UserRole $state): string => match ($state) {
                        UserRole::Admin => 'danger',
                        UserRole::Instructor => 'warning',
                        UserRole::Learner => 'gray',
                    }),

                TextColumn::make('company')
                    ->label('Entreprise')
                    ->toggleable()
                    ->placeholder('—'),

                TextColumn::make('country')
                    ->label('Pays')
                    ->toggleable(),

                IconColumn::make('email_verified_at')
                    ->label('Vérifié')
                    ->boolean()
                    ->state(fn ($record): bool => $record->email_verified_at !== null),

                IconColumn::make('suspended_at')
                    ->label('Suspendu')
                    ->boolean()
                    ->trueIcon('heroicon-o-no-symbol')
                    ->trueColor('danger')
                    ->falseIcon('heroicon-o-check-circle')
                    ->falseColor('success')
                    ->state(fn (User $record): bool => $record->isSuspended()),

                TextColumn::make('created_at')
                    ->label('Inscrit le')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label('Rôle')
                    ->options(UserRole::class),
            ])
            ->recordActions([
                // Export RGPD : telecharge toutes les donnees personnelles en JSON.
                Action::make('exportData')
                    ->label('Exporter les données')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->action(function (User $record): StreamedResponse {
                        $exporter = app(UserDataExporter::class);
                        $json = $exporter->toJson($record);
                        $name = $exporter->filename($record);

                        return response()->streamDownload(function () use ($json): void {
                            echo $json;
                        }, $name, ['Content-Type' => 'application/json']);
                    }),

                // Invitation : renvoie un lien pour definir le mot de passe (mode Entreprise).
                Action::make('sendInvitation')
                    ->label('Envoyer l’invitation')
                    ->icon('heroicon-o-envelope')
                    ->color('info')
                    ->visible(fn (User $record): bool => Platform::allows('invitations') && $record->role === UserRole::Learner)
                    ->requiresConfirmation()
                    ->modalDescription('Un e-mail contenant un lien pour définir le mot de passe sera envoyé à cet apprenant.')
                    ->action(function (User $record): void {
                        app(InvitationService::class)->send($record);

                        Notification::make()
                            ->success()
                            ->title('Invitation envoyée')
                            ->body("Un lien a été envoyé à {$record->email}.")
                            ->send();
                    }),

                // Suspension réversible : bloque connexion et back-office sans effacer.
                Action::make('suspend')
                    ->label('Suspendre')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->visible(fn (User $record): bool => ! $record->isSuspended() && $record->getKey() !== auth()->id())
                    ->requiresConfirmation()
                    ->modalDescription('Ce compte ne pourra plus se connecter jusqu’à sa réactivation. Aucune donnée n’est supprimée.')
                    ->action(function (User $record): void {
                        $record->forceFill(['suspended_at' => now()])->save();
                        Notification::make()->success()->title('Compte suspendu')->send();
                    }),

                Action::make('reactivate')
                    ->label('Réactiver')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (User $record): bool => $record->isSuspended())
                    ->action(function (User $record): void {
                        $record->forceFill(['suspended_at' => null])->save();
                        Notification::make()->success()->title('Compte réactivé')->send();
                    }),

                EditAction::make(),

                // Suppression de compte (droit a l'effacement). L'admin ne peut pas se
                // supprimer lui-meme (UserPolicy).
                DeleteAction::make()
                    ->label('Supprimer le compte')
                    ->modalHeading('Supprimer ce compte')
                    ->modalDescription('Toutes les données personnelles de cet utilisateur seront définitivement effacées. Cette action est irréversible.'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}

<?php

declare(strict_types=1);

namespace App\Filament\Resources\Groups\Pages;

use App\Facades\Platform;
use App\Filament\Resources\Groups\GroupResource;
use App\Models\Group;
use App\Services\CourseAssigner;
use App\Services\GroupProgressReport;
use App\Services\Import\LearnerImporter;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EditGroup extends EditRecord
{
    protected static string $resource = GroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->importAction(),
            $this->exportProgressAction(),
            // Filet de sécurité : réinscrit tous les membres à toutes les formations
            // affectées, au cas où une inscription manquerait.
            Action::make('syncAccess')
                ->label('Synchroniser les accès')
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('gray')
                ->action(function (): void {
                    /** @var Group $group */
                    $group = $this->getRecord();
                    $created = app(CourseAssigner::class)->reconcileGroup($group);

                    Notification::make()
                        ->success()
                        ->title('Accès synchronisés')
                        ->body($created > 0 ? "{$created} inscription(s) créée(s)." : 'Tout était déjà à jour.')
                        ->send();
                }),

            DeleteAction::make(),
        ];
    }

    /**
     * Import CSV d'apprenants dans l'organisation du groupe (et rattachement au groupe).
     */
    private function importAction(): Action
    {
        return Action::make('importLearners')
            ->label('Importer des apprenants (CSV)')
            ->icon(Heroicon::OutlinedArrowUpTray)
            ->visible(fn (): bool => Platform::allows('bulk_import'))
            ->schema([
                FileUpload::make('file')
                    ->label('Fichier CSV')
                    ->helperText('Colonnes attendues : « nom » et « email ». Une ligne par apprenant.')
                    ->acceptedFileTypes(['text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel'])
                    ->storeFiles(false)
                    ->required(),

                Toggle::make('invite')
                    ->label('Envoyer une invitation par e-mail')
                    ->helperText('Chaque nouvel apprenant reçoit un lien pour définir son mot de passe.')
                    ->default(Platform::allows('invitations')),
            ])
            ->action(function (array $data): void {
                /** @var Group $group */
                $group = $this->getRecord();

                $file = $data['file'];
                if (is_array($file)) {
                    $file = reset($file);
                }

                $summary = app(LearnerImporter::class)->import(
                    (string) $file->get(),
                    $group->organization,
                    $group,
                    (bool) ($data['invite'] ?? false) && Platform::allows('invitations'),
                );

                $body = "{$summary->created} créé(s), {$summary->existing} déjà présent(s), "
                    ."{$summary->attached} rattaché(s) au groupe, {$summary->invited} invitation(s) envoyée(s).";

                $notification = Notification::make()->title('Import terminé')->body($body);

                if ($summary->errors !== []) {
                    $notification->warning()->body($body.' Lignes ignorées : '.implode(' ', $summary->errors));
                } else {
                    $notification->success();
                }

                $notification->send();
            });
    }

    /**
     * Export CSV de la progression des membres (tableau de bord RH).
     */
    private function exportProgressAction(): Action
    {
        return Action::make('exportProgress')
            ->label('Exporter la progression (CSV)')
            ->icon(Heroicon::OutlinedArrowDownTray)
            ->visible(fn (): bool => Platform::allows('hr_dashboards'))
            ->action(function (): StreamedResponse {
                /** @var Group $group */
                $group = $this->getRecord();
                $rows = app(GroupProgressReport::class)->forGroup($group);
                $filename = 'progression-'.str($group->name)->slug()->value().'.csv';

                return response()->streamDownload(function () use ($rows): void {
                    $out = fopen('php://output', 'wb');
                    fputcsv($out, ['Nom', 'E-mail', 'Formations affectées', 'Formations terminées', 'Progression (%)', 'Certificats'], ';');
                    foreach ($rows as $row) {
                        fputcsv($out, [
                            $row['user']->name,
                            $row['user']->email,
                            $row['courses_total'],
                            $row['courses_done'],
                            $row['percent'],
                            $row['certificates'],
                        ], ';');
                    }
                    fclose($out);
                }, $filename, ['Content-Type' => 'text/csv']);
            });
    }
}

<?php

declare(strict_types=1);

namespace App\Services\Import;

use App\Enums\UserRole;
use App\Models\Group;
use App\Models\Organization;
use App\Models\User;
use App\Services\CourseAssigner;
use App\Services\InvitationService;
use Illuminate\Support\Str;
use League\Csv\Reader;

/**
 * Import en masse d'apprenants à partir d'un CSV (mode Entreprise).
 *
 * Colonnes attendues (en-tête, insensible à la casse et aux accents) : « nom » et
 * « email ». Chaque apprenant est rattaché à l'organisation, éventuellement au groupe,
 * et (au choix) reçoit une invitation pour définir son mot de passe.
 *
 * Idempotent : un e-mail déjà présent n'est pas dupliqué ; l'apprenant est simplement
 * rattaché. Traitement synchrone, adapté à des lots de quelques dizaines à centaines de
 * lignes (l'usage réel d'une cohorte).
 */
class LearnerImporter
{
    public function __construct(
        private readonly CourseAssigner $assigner,
        private readonly InvitationService $invitations,
    ) {}

    public function import(string $csv, Organization $organization, ?Group $group, bool $invite): ImportSummary
    {
        $summary = new ImportSummary;

        $reader = Reader::createFromString($csv);
        $reader->setHeaderOffset(0);

        $line = 1;
        foreach ($reader->getRecords() as $record) {
            $line++;
            $row = $this->normalizeKeys($record);

            $email = trim((string) ($row['email'] ?? ''));
            $name = trim((string) ($row['nom'] ?? $row['name'] ?? ''));

            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $summary->addError($line, 'adresse e-mail invalide ou absente.');

                continue;
            }

            $user = User::query()->where('email', $email)->first();

            if ($user === null) {
                $user = new User;
                $user->name = $name !== '' ? $name : Str::before($email, '@');
                $user->email = $email;
                $user->password = Str::random(40); // remplacé par l'apprenant via l'invitation
                $user->role = UserRole::Learner;
                $user->organization_id = $organization->getKey();
                $user->save();
                $summary->created++;

                if ($invite) {
                    $this->invitations->send($user);
                    $summary->invited++;
                }
            } else {
                // Rattache à l'organisation si l'apprenant n'en avait pas.
                if ($user->organization_id === null) {
                    $user->organization_id = $organization->getKey();
                    $user->save();
                }
                $summary->existing++;
            }

            if ($group !== null && ! $group->members()->whereKey($user->getKey())->exists()) {
                $group->members()->attach($user->getKey());
                $summary->attached++;
            }
        }

        // Une seule réconciliation en fin d'import : inscrit tous les nouveaux membres
        // aux formations déjà affectées au groupe.
        if ($group !== null) {
            $this->assigner->reconcileGroup($group);
        }

        return $summary;
    }

    /**
     * @param  array<string, string|null>  $record
     * @return array<string, string|null>
     */
    private function normalizeKeys(array $record): array
    {
        $normalized = [];
        foreach ($record as $key => $value) {
            $clean = Str::of((string) $key)->lower()->ascii()->trim()->value();
            $clean = match ($clean) {
                'e-mail', 'courriel', 'mail', 'adresse email', 'adresse e-mail' => 'email',
                'name', 'prenom nom', 'nom complet', 'nom et prenom' => 'nom',
                default => $clean,
            };
            $normalized[$clean] = $value;
        }

        return $normalized;
    }
}

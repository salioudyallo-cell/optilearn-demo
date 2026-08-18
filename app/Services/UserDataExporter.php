<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;

/**
 * Export RGPD : rassemble toutes les données personnelles d'un utilisateur en une
 * structure exportable (JSON). Sert à la portabilité des données et au droit d'accès.
 */
final class UserDataExporter
{
    /**
     * @return array<string, mixed>
     */
    public function export(User $user): array
    {
        $user->loadMissing([
            'enrollments.course:id,title,slug',
            'lessonProgress.lesson:id,title',
            'quizAttempts.quiz.lesson:id,title',
            'certificates.course:id,title',
            'accessCodeRedemptions.accessCode:id,code,label',
        ]);

        return [
            'exporte_le' => now()->toIso8601String(),
            'profil' => [
                'nom' => $user->name,
                'email' => $user->email,
                'telephone' => $user->phone,
                'entreprise' => $user->company,
                'pays' => $user->country,
                'role' => $user->role->value,
                'email_verifie_le' => $user->email_verified_at?->toIso8601String(),
                'inscrit_le' => $user->created_at?->toIso8601String(),
            ],
            'inscriptions' => $user->enrollments->map(fn ($e) => [
                'formation' => $e->course?->title,
                'statut' => $e->status->value,
                'origine' => $e->source->value,
                'inscrit_le' => $e->enrolled_at->toIso8601String(),
                'expire_le' => $e->expires_at?->toIso8601String(),
            ])->all(),
            'progression' => $user->lessonProgress->map(fn ($p) => [
                'lecon' => $p->lesson?->title,
                'position_secondes' => $p->last_position_seconds,
                'terminee_le' => $p->completed_at?->toIso8601String(),
            ])->all(),
            'tentatives_quiz' => $user->quizAttempts->map(fn ($a) => [
                'lecon' => $a->quiz?->lesson?->title,
                'score_pct' => $a->score_pct,
                'reussi' => $a->passed,
                'passe_le' => $a->attempted_at->toIso8601String(),
            ])->all(),
            'certificats' => $user->certificates->map(fn ($c) => [
                'formation' => $c->course?->title,
                'numero_serie' => $c->serial,
                'delivre_le' => $c->issued_at->toIso8601String(),
            ])->all(),
            'codes_actives' => $user->accessCodeRedemptions->map(fn ($r) => [
                'code' => $r->accessCode?->code,
                'libelle' => $r->accessCode?->label,
                'active_le' => $r->redeemed_at->toIso8601String(),
            ])->all(),
        ];
    }

    public function toJson(User $user): string
    {
        return (string) json_encode(
            $this->export($user),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        );
    }

    public function filename(User $user): string
    {
        return 'donnees-'.$user->getKey().'-'.now()->format('Y-m-d').'.json';
    }
}

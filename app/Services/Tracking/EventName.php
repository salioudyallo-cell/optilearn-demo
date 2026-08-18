<?php

declare(strict_types=1);

namespace App\Services\Tracking;

/**
 * Noms d'événements produit. Centralisés pour éviter les fautes de frappe et documenter
 * le plan de suivi.
 */
final class EventName
{
    public const AccountCreated = 'compte_cree';

    public const CodeActivated = 'code_active';

    public const LessonCompleted = 'lecon_terminee';

    public const QuizPassed = 'quiz_reussi';

    public const CourseCompleted = 'formation_terminee';

    public const CertificateObtained = 'certificat_obtenu';
}

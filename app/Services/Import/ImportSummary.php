<?php

declare(strict_types=1);

namespace App\Services\Import;

/**
 * Résultat d'un import d'apprenants.
 */
class ImportSummary
{
    public int $created = 0;

    public int $existing = 0;

    public int $invited = 0;

    public int $attached = 0;

    /** @var list<string> */
    public array $errors = [];

    public function addError(int $line, string $message): void
    {
        $this->errors[] = "Ligne {$line} : {$message}";
    }

    public function total(): int
    {
        return $this->created + $this->existing;
    }
}

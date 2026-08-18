<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

/**
 * Emission d'un certificat : numero de serie unique, rendu PDF (dompdf, pur PHP —
 * compatible mutualise, ni Chromium ni binaire externe), stockage sur le disque prive.
 * Le fichier n'est jamais accessible par URL directe ; il est servi par un controleur
 * apres autorisation (§7.4).
 */
final class CertificateGenerator
{
    public function issue(User $user, Course $course): Certificate
    {
        $serial = $this->generateSerial();

        $certificate = Certificate::query()->create([
            'user_id' => $user->getKey(),
            'course_id' => $course->getKey(),
            'serial' => $serial,
            'issued_at' => now(),
        ]);

        $path = $this->renderPdf($certificate, $user, $course);
        $certificate->update(['pdf_path' => $path]);

        return $certificate->refresh();
    }

    /**
     * Format OPT-YYYY-XXXXXX (alphanumerique majuscule), unique.
     */
    public function generateSerial(): string
    {
        $prefix = (string) config('lms.certificates.serial_prefix');
        $year = now()->year;

        do {
            $random = strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
            $serial = sprintf('%s-%s-%s', $prefix, $year, $random);
        } while (Certificate::query()->where('serial', $serial)->exists());

        return $serial;
    }

    private function renderPdf(Certificate $certificate, User $user, Course $course): string
    {
        $pdf = Pdf::loadView('pdf.certificate', [
            'learnerName' => $user->name,
            'courseTitle' => $course->title,
            'serial' => $certificate->serial,
            'issuedAt' => $certificate->issued_at,
            'verifyUrl' => route('certificate.verify', $certificate->serial),
        ])->setPaper('a4', 'landscape');

        $path = 'certificates/'.$certificate->serial.'.pdf';
        Storage::disk(config('lms.certificates.disk'))->put($path, $pdf->output());

        return $path;
    }
}

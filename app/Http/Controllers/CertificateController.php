<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Certificate;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CertificateController extends Controller
{
    /**
     * Liste des certificats de l'apprenant connecte.
     */
    public function index(): View
    {
        $user = auth()->user();
        abort_if($user === null, 403);

        $certificates = $user->certificates()->with('course')->latest('issued_at')->get();

        return view('learn.certificates', [
            'certificates' => $certificates,
        ]);
    }

    /**
     * Telechargement du PDF, apres verification d'autorisation (CertificatePolicy).
     * Le fichier est stocke sur le disque prive, jamais accessible par URL directe.
     */
    public function download(Certificate $certificate): StreamedResponse
    {
        $this->authorize('download', $certificate);

        abort_if(blank($certificate->pdf_path), 404);

        $disk = Storage::disk(config('lms.certificates.disk'));
        abort_unless($disk->exists($certificate->pdf_path), 404);

        return $disk->download($certificate->pdf_path, $certificate->serial.'.pdf', [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /**
     * Verification publique par numero de serie. N'expose que le nom, la formation et la
     * date — jamais le PDF ni de donnee sensible.
     */
    public function verify(string $serial): View
    {
        $certificate = Certificate::query()
            ->where('serial', $serial)
            ->with(['user', 'course'])
            ->first();

        return view('public.verify', [
            'serial' => $serial,
            'certificate' => $certificate,
        ]);
    }
}

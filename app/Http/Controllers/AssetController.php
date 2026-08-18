<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Services\Video\VideoManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssetController extends Controller
{
    /**
     * Sert le PDF d'une lecon depuis le disque prive, apres verification de
     * l'autorisation. Le fichier n'est jamais accessible par une URL directe (§7.4).
     */
    public function pdf(Lesson $lesson): StreamedResponse
    {
        $this->authorize('download', $lesson);

        abort_if(blank($lesson->asset_path), 404);

        $disk = Storage::disk(config('lms.courses.assets_disk'));
        abort_unless($disk->exists($lesson->asset_path), 404);

        $filename = str($lesson->title)->slug()->value().'.pdf';

        return $disk->download($lesson->asset_path, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /**
     * Renvoie la source de lecture (iframe Bunny ou fichier auto-heberge) apres
     * autorisation. Ni le bunny_video_id ni le chemin du fichier ne transitent vers le
     * client : il ne recoit qu'une URL a duree de vie limitee.
     */
    public function videoUrl(Lesson $lesson, VideoManager $videos): JsonResponse
    {
        $this->authorize('view', $lesson);

        abort_unless($videos->isConfigured(), 503, 'Le lecteur vidéo n’est pas encore configuré.');
        abort_unless($videos->hasVideo($lesson), 404);

        return response()->json($videos->sourceFor($lesson)->toArray());
    }

    /**
     * Sert le fichier video auto-heberge. La route est signee (lien temporaire), mais on
     * re-verifie l'autorisation : une URL signee partagee ne doit pas ouvrir l'acces a
     * quelqu'un qui n'est pas inscrit (§7.4).
     *
     * Selon la configuration, les octets sont envoyes par PHP (compatible partout,
     * requetes Range supportees pour l'avance/recul) ou delegues au serveur web, qui
     * sert alors le fichier en statique et libere immediatement le worker PHP.
     */
    public function stream(Lesson $lesson): Response
    {
        $this->authorize('view', $lesson);

        abort_if(blank($lesson->video_path), 404);

        $disk = Storage::disk(config('lms.video.disk'));
        abort_unless($disk->exists($lesson->video_path), 404);

        $absolutePath = $disk->path($lesson->video_path);

        if (config('lms.video.delivery') === 'sendfile') {
            $header = (string) config('lms.video.sendfile_header');

            return response('', 200, [
                'Content-Type' => $disk->mimeType($lesson->video_path) ?: 'video/mp4',
                'Content-Disposition' => 'inline',
                $header => $absolutePath,
            ]);
        }

        // BinaryFileResponse gere les requetes Range (206) : la barre de progression du
        // lecteur reste utilisable sans charger toute la video.
        return new BinaryFileResponse($absolutePath, 200, [
            'Content-Type' => $disk->mimeType($lesson->video_path) ?: 'video/mp4',
            'Content-Disposition' => 'inline',
        ], public: false, autoLastModified: true);
    }
}

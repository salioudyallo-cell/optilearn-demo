<?php

declare(strict_types=1);

use App\Http\Controllers\AssetController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LearnController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SitemapController;
use App\Livewire\ActivateCode;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/robots.txt', [SitemapController::class, 'robots'])->name('robots');

// Catalogue public et fiche formation (URLs en francais).
Route::get('/formations', [CatalogController::class, 'index'])->name('catalog.index');
Route::get('/formations/{course}', [CatalogController::class, 'show'])->name('catalog.show');

// Pages legales.
Route::get('/conditions-generales', [LegalController::class, 'terms'])->name('legal.terms');
Route::get('/mentions-legales', [LegalController::class, 'notice'])->name('legal.notice');
Route::get('/confidentialite', [LegalController::class, 'privacy'])->name('legal.privacy');

// Verification publique d'un certificat par numero de serie.
Route::get('/verifier/{serial}', [CertificateController::class, 'verify'])->name('certificate.verify');

/*
 * Lecteur d'une lecon. La lecon d'essai reste accessible sans authentification :
 * l'autorisation fine est portee par LessonPolicy, pas par le middleware, sinon la
 * preview publique de la fiche formation serait impossible.
 */
Route::get('/apprendre/lecons/{lesson}', [LearnController::class, 'lesson'])
    ->name('learn.lesson');

// PDF protege et URL video signee : servis apres verification d'autorisation.
Route::get('/apprendre/lecons/{lesson}/pdf', [AssetController::class, 'pdf'])->name('learn.pdf');
Route::get('/apprendre/lecons/{lesson}/video-url', [AssetController::class, 'videoUrl'])->name('learn.video-url');

// Lecture d'une video auto-hebergee : lien signe et temporaire, autorisation re-verifiee
// dans le controleur (une URL partagee n'ouvre rien a un non-inscrit).
Route::get('/apprendre/lecons/{lesson}/video', [AssetController::class, 'stream'])
    ->middleware('signed')
    ->name('learn.video-stream');

// Avis sur une formation : apprenant authentifie, uniquement en mode Commercial.
Route::post('/formations/{course}/avis', [ReviewController::class, 'store'])
    ->middleware(['auth', 'feature:reviews'])
    ->name('reviews.store');

// Achat d'une formation : mode Commercial uniquement (capacite panier).
Route::middleware(['auth', 'feature:cart'])->group(function (): void {
    Route::post('/formations/{course}/acheter', [CheckoutController::class, 'store'])->name('checkout.store');
    Route::get('/commandes/{order}', [CheckoutController::class, 'show'])->name('checkout.show');
});

// Espace apprenant : authentifie et email verifie.
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/mon-espace', [LearnController::class, 'dashboard'])->name('dashboard');
    Route::get('/activer', ActivateCode::class)->name('activate');
    Route::get('/apprendre/{course}', [LearnController::class, 'course'])->name('learn.course');

    Route::get('/certificats', [CertificateController::class, 'index'])->name('certificates.index');
    Route::get('/certificats/{certificate}/telecharger', [CertificateController::class, 'download'])->name('certificate.download');
});

Route::middleware('auth')->group(function () {
    Route::get('/profil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profil', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profil', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

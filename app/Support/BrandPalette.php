<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Genere une echelle de couleurs complete (50..900) a partir de quelques couleurs de
 * marque, pour re-thematiser l'instance sans recompiler le CSS. Les variables produites
 * portent exactement les memes noms que celles compilees dans app.css (@theme), et sont
 * injectees APRES la feuille compilee (voir <x-brand-theme />) : elles la surchargent.
 *
 * Aucune dependance a color-mix() cote navigateur : chaque teinte est calculee ici et
 * emise en hexadecimal explicite, donc compatible avec les webviews Android anciens.
 */
final class BrandPalette
{
    /**
     * Fractions de melange vers le blanc (positif) ou le noir (negatif) par palier, la
     * couleur de base occupant le palier 600 pour la teinte principale.
     *
     * @var array<int, float>
     */
    private const PRIMARY_STEPS = [
        50 => 0.93, 100 => 0.85, 200 => 0.70, 300 => 0.50, 400 => 0.27,
        500 => 0.12, 600 => 0.0, 700 => -0.12, 800 => -0.26, 900 => -0.45,
    ];

    /**
     * Echelle d'accent (CTA), la base occupant le palier 500.
     *
     * @var array<int, float>
     */
    private const ACCENT_STEPS = [
        50 => 0.90, 100 => 0.80, 200 => 0.62, 300 => 0.42, 400 => 0.20,
        500 => 0.0, 600 => -0.10, 700 => -0.24,
    ];

    /**
     * Construit la liste des variables CSS a surcharger.
     *
     * @return array<string, string>
     */
    public static function fromConfig(): array
    {
        /** @var array{primary: string, accent: string, highlight: string} $colors */
        $colors = [
            'primary' => (string) config('brand.colors.primary'),
            'accent' => (string) config('brand.colors.accent'),
            'highlight' => (string) config('brand.colors.highlight'),
        ];

        return self::build($colors['primary'], $colors['accent'], $colors['highlight']);
    }

    /**
     * @return array<string, string>
     */
    public static function build(string $primary, string $accent, string $highlight): array
    {
        $vars = [];

        foreach (self::PRIMARY_STEPS as $step => $ratio) {
            $vars["--color-brand-blue-{$step}"] = self::shade($primary, $ratio);
        }
        foreach (self::ACCENT_STEPS as $step => $ratio) {
            $vars["--color-brand-orange-{$step}"] = self::shade($accent, $ratio);
        }

        // Aliases historiques utilises directement dans les vues et app.css.
        $vars['--color-brand-blue'] = self::shade($primary, -0.12);
        $vars['--color-brand-navy'] = self::shade($primary, -0.45);
        $vars['--color-brand-orange'] = $accent;
        $vars['--color-brand-orange-dark'] = self::shade($accent, -0.10);
        $vars['--color-brand-yellow'] = $highlight;
        $vars['--color-brand-yellow-soft'] = self::shade($highlight, 0.75);
        $vars['--color-brand-cream'] = self::shade($highlight, 0.92);

        return $vars;
    }

    /**
     * Assemble la declaration CSS prete a injecter dans un <style>.
     */
    public static function css(): string
    {
        $lines = [];
        foreach (self::fromConfig() as $name => $value) {
            $lines[] = "{$name}:{$value};";
        }

        return ':root{'.implode('', $lines).'}';
    }

    /**
     * Eclaircit (ratio > 0, vers le blanc) ou assombrit (ratio < 0, vers le noir) une
     * couleur hexadecimale.
     */
    private static function shade(string $hex, float $ratio): string
    {
        [$r, $g, $b] = self::toRgb($hex);

        $target = $ratio >= 0 ? 255 : 0;
        $weight = abs($ratio);

        $mix = static fn (int $channel): int => (int) round($channel + ($target - $channel) * $weight);

        return sprintf('#%02x%02x%02x', $mix($r), $mix($g), $mix($b));
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    private static function toRgb(string $hex): array
    {
        $hex = ltrim(trim($hex), '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        if (strlen($hex) !== 6 || ! ctype_xdigit($hex)) {
            // Repli neutre si la valeur .env est invalide : gris moyen, jamais une erreur.
            return [128, 128, 128];
        }

        return [
            (int) hexdec(substr($hex, 0, 2)),
            (int) hexdec(substr($hex, 2, 2)),
            (int) hexdec(substr($hex, 4, 2)),
        ];
    }
}

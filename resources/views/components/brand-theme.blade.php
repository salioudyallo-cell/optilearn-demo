{{-- Re-theme de l'instance : surcharge les variables de couleur compilees dans app.css.
     Injecte APRES @vite (donc prioritaire), et seulement si l'instance definit une couleur
     principale (BRAND_COLOR_PRIMARY) — sinon la charte OptiLeads compilee est conservee. --}}
@if (config('brand.colors.custom'))
    <style>{!! \App\Support\BrandPalette::css() !!}</style>
@endif

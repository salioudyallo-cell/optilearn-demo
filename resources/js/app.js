import collapse from '@alpinejs/collapse';
import lessonVideo from './lesson-video';

// Alpine est fourni par Livewire (chargé sur toutes les pages via le layout). On ne
// démarre donc PAS une seconde instance ici — cela éviterait le conflit « multiple
// instances of Alpine ». On se contente d'enregistrer plugins et composants à l'init.
document.addEventListener('alpine:init', () => {
    window.Alpine.plugin(collapse);
    window.Alpine.data('lessonVideo', lessonVideo);
});

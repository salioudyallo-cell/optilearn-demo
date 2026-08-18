/**
 * Composant Alpine du lecteur vidéo.
 *
 * Règles (cf. CLAUDE.md §6) :
 *  - la position est sauvegardée au maximum toutes les 15 s (débounce), jamais en continu ;
 *  - tolérance aux coupures : si l'appel Livewire échoue, la position est mise en file
 *    dans localStorage et rejouée à la reconnexion (événement `online` + à l'init).
 *
 * L'intégration temps réel avec le lecteur Bunny (postMessage / player.js) se branche sur
 * `onTimeUpdate` ; sans identifiants Bunny configurés, l'URL signée n'est pas renvoyée et
 * le lecteur affiche un message d'attente.
 */
export default function lessonVideo({ lessonId, startAt, videoUrlEndpoint, guest = false }) {
    return {
        // Mode invité (aperçu public) : lecture seule, sans suivi de progression.
        // Aucun appel Livewire n'est possible, l'apprenant n'a pas de compte.
        guest,
        playerUrl: null,
        // 'iframe' (Bunny Stream) ou 'file' (vidéo auto-hébergée, balise <video>).
        playerKind: null,
        statusMessage: 'Chargement de la vidéo…',
        lastSaved: startAt || 0,
        currentPosition: startAt || 0,
        pendingSync: false,
        _timer: null,

        init() {
            this.loadPlayerUrl();

            // En mode invité, il n'y a rien à sauvegarder : on s'arrête là.
            if (this.guest) return;

            this.flushQueue();
            window.addEventListener('online', () => this.flushQueue());

            // Débounce dur : au plus une sauvegarde toutes les 15 s.
            this._timer = setInterval(() => this.persistIfMoved(), 15000);
            window.addEventListener('beforeunload', () => this.persistIfMoved());
        },

        async loadPlayerUrl() {
            try {
                const res = await fetch(videoUrlEndpoint, { headers: { Accept: 'application/json' } });
                if (res.ok) {
                    const data = await res.json();
                    this.playerUrl = data.url;
                    this.playerKind = data.kind || 'iframe';
                } else if (res.status === 503) {
                    this.statusMessage = 'La vidéo sera disponible une fois le lecteur configuré.';
                } else {
                    this.statusMessage = 'Vidéo indisponible pour le moment.';
                }
            } catch (e) {
                this.statusMessage = 'Vidéo indisponible (connexion).';
            }
        },

        // À brancher sur les événements de temps du lecteur Bunny.
        onTimeUpdate(seconds) {
            this.currentPosition = Math.floor(seconds);
        },

        /**
         * Vidéo auto-hébergée : branche la balise <video> native.
         * Reprend la lecture là où l'apprenant s'était arrêté et marque la leçon
         * terminée à la fin. Le débounce de 15 s reste géré par le timer commun.
         */
        bindNativeVideo(el) {
            if (!el) return;

            const resume = () => {
                if (this.lastSaved > 0 && el.currentTime < 1) {
                    // Certains navigateurs refusent le seek avant d'avoir les métadonnées.
                    try { el.currentTime = this.lastSaved; } catch (e) { /* noop */ }
                }
            };

            el.addEventListener('timeupdate', () => this.onTimeUpdate(el.currentTime));

            if (this.guest) return;

            el.addEventListener('loadedmetadata', resume, { once: true });
            el.addEventListener('ended', () => {
                this.persistIfMoved();
                this.$wire.markCompleted();
            });
        },

        persistIfMoved() {
            if (this.guest) return;
            if (this.currentPosition === this.lastSaved) return;
            this.save(this.currentPosition);
        },

        save(seconds) {
            this.lastSaved = seconds;
            // @this est fourni par Livewire dans le scope du composant.
            this.$wire.savePosition(seconds)
                .then(() => { this.pendingSync = false; })
                .catch(() => this.queue(seconds));
        },

        queue(seconds) {
            this.pendingSync = true;
            try {
                localStorage.setItem(this.storageKey(), String(seconds));
            } catch (e) { /* stockage indisponible : on ignore */ }
        },

        flushQueue() {
            let queued;
            try { queued = localStorage.getItem(this.storageKey()); } catch (e) { return; }
            if (queued === null) return;

            this.$wire.savePosition(parseInt(queued, 10))
                .then(() => {
                    try { localStorage.removeItem(this.storageKey()); } catch (e) { /* noop */ }
                    this.pendingSync = false;
                })
                .catch(() => { this.pendingSync = true; });
        },

        storageKey() {
            return `lesson-progress:${lessonId}`;
        },
    };
}

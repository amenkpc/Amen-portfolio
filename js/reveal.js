// ============================================================
// REVEAL.JS — révélation discrète au scroll pour les éléments
// portant la classe .reveal (fondu + léger déplacement vers le haut).
// Vanilla JS, aucune dépendance, respecte prefers-reduced-motion.
// ============================================================
(function () {
    "use strict";

    var targets = document.querySelectorAll(".reveal");
    if (!targets.length) {
        return;
    }

    // Si l'utilisateur préfère moins de mouvement, tout est affiché
    // directement (le CSS gère déjà ce cas, on évite ici de créer
    // un observer inutilement).
    var prefersReducedMotion = window.matchMedia(
        "(prefers-reduced-motion: reduce)"
    ).matches;

    if (prefersReducedMotion || !("IntersectionObserver" in window)) {
        targets.forEach(function (el) {
            el.classList.add("in-view");
        });
        return;
    }

    var observer = new IntersectionObserver(
        function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add("in-view");
                    observer.unobserve(entry.target);
                }
            });
        },
        { threshold: 0.15, rootMargin: "0px 0px -40px 0px" }
    );

    targets.forEach(function (el) {
        observer.observe(el);
    });
})();

// ================================ Écran de chargement =======================================
// Stratégie : afficher le loader UNIQUEMENT lors de la navigation sortante (clic sur lien interne).
// L'activation au DOMContentLoaded + beforeunload provoquait un double affichage à chaque page.

document.addEventListener("DOMContentLoaded", () => {
    const loadingScreen = document.getElementById("loadingScreen");

    // S'assurer que le loader est caché au chargement initial de la page
    if (loadingScreen) {
        loadingScreen.classList.remove("active");
    }

    // Afficher le loader lors d'un clic sur un lien interne (navigation vers une autre page)
    document.addEventListener("click", (event) => {
        const link = event.target.closest("a");

        // Ignorer les liens non valides, téléchargements et cibles externes
        if (!link || !link.href || link.download || link.target === "_blank") {
            return;
        }

        const href = link.getAttribute("href");

        // Ignorer les ancres, les liens mailto et tel
        if (!href || href.startsWith("#") || href.startsWith("mailto:") || href.startsWith("tel:")) {
            return;
        }

        const linkUrl = new URL(link.href, window.location.href);
        const currentUrl = new URL(window.location.href);

        // Ignorer les liens vers des domaines externes
        if (linkUrl.origin !== currentUrl.origin) {
            return;
        }

        const isInternalPage = /\.(html|php)$/i.test(linkUrl.pathname);

        // Afficher le loader uniquement pour les pages internes différentes de la page actuelle
        if (isInternalPage && linkUrl.pathname !== currentUrl.pathname) {
            event.preventDefault();

            if (loadingScreen) {
                loadingScreen.classList.add("active");
            }

            // Rediriger après un court délai pour laisser l'animation s'afficher
            setTimeout(() => {
                window.location.href = link.href;
            }, 400);
        }
    });
});

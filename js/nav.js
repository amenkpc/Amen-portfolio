// ================================ Navigation mobile =======================================
document.addEventListener("DOMContentLoaded", () => {
    const aside = document.getElementById("siteAside");
    const toggler = document.getElementById("navToggler");
    const overlay = document.getElementById("navOverlay");
    const navLinks = document.querySelectorAll("#siteNav a");

    // Si les éléments requis sont absents, on ne fait rien
    if (!aside || !toggler) {
        return;
    }

    // Fermer le menu
    const closeMenu = () => {
        aside.classList.remove("open");
        toggler.setAttribute("aria-expanded", "false");
        toggler.setAttribute("aria-label", "Ouvrir le menu");

        if (overlay) {
            overlay.hidden = true;
        }
    };

    // Ouvrir le menu
    const openMenu = () => {
        aside.classList.add("open");
        toggler.setAttribute("aria-expanded", "true");
        toggler.setAttribute("aria-label", "Fermer le menu");

        if (overlay) {
            overlay.hidden = false;
        }
    };

    // Basculer l'état du menu au clic sur le bouton hamburger
    toggler.addEventListener("click", () => {
        if (aside.classList.contains("open")) {
            closeMenu();
        } else {
            openMenu();
        }
    });

    // Fermer le menu en cliquant sur l'overlay de fond
    if (overlay) {
        overlay.addEventListener("click", closeMenu);
    }

    // Fermer le menu sur mobile lors d'un clic sur un lien de navigation
    navLinks.forEach((link) => {
        link.addEventListener("click", () => {
            if (window.innerWidth <= 1199) {
                closeMenu();
            }
        });
    });

    // Fermer le menu si la fenêtre est redimensionnée vers un grand écran
    window.addEventListener("resize", () => {
        if (window.innerWidth > 1199) {
            closeMenu();
        }
    });
});

/* ================================ Ouverture/fermeture du sélecteur de style ======================================= */
const styleSwitcherToggle = document.querySelector(".style-switcher-toggler");

if (styleSwitcherToggle) {
    styleSwitcherToggle.addEventListener("click", () => {
        document.querySelector(".style-switcher")?.classList.toggle("open");
    });
}

// Fermer le sélecteur lors d'un défilement de page
window.addEventListener("scroll", () => {
    const switcher = document.querySelector(".style-switcher");

    if (switcher?.classList.contains("open")) {
        switcher.classList.remove("open");
    }
});
/* ================================ Couleurs du thème ======================================= */
const alternateStyles = document.querySelectorAll(".alternate-style");

// Ajouter une transition fluide lors du changement de couleur
function addTransitionEffect() {
    document.body.style.transition = "background-color 1s ease, color 1s ease";
    setTimeout(() => {
        document.body.style.transition = "";
    }, 500);
}

// Activer la feuille de style correspondant à la couleur choisie
function setActiveStyle(color)
{
    addTransitionEffect();
    alternateStyles.forEach((style) => {
        if(color === style.getAttribute("title"))
        {
            style.removeAttribute("disabled");
        }
        else
        {
            style.setAttribute("disabled","true");
        }
    })
    // Sauvegarder la couleur sélectionnée dans localStorage
    localStorage.setItem("selectedColor", color);
}
/* ================================ Mode clair / Mode sombre ======================================= */
const dayNight = document.querySelector(".day-night");

if (dayNight) {
    dayNight.addEventListener("click", () => {
        document.body.style.transition = "background-color 1s ease, color 1s ease";

        dayNight.querySelector("i")?.classList.toggle("fa-sun");
        dayNight.querySelector("i")?.classList.toggle("fa-moon");
        document.body.classList.toggle("dark");

        localStorage.setItem("theme", document.body.classList.contains("dark") ? "dark" : "light");

        setTimeout(() => {
            document.body.style.transition = "";
        }, 500);
    });
}

window.addEventListener("load", () => {
    const savedTheme = localStorage.getItem("theme");
    const dayNightIcon = dayNight?.querySelector("i");

    if (savedTheme === "dark") {
        document.body.classList.add("dark");
        dayNightIcon?.classList.remove("fa-moon");
        dayNightIcon?.classList.add("fa-sun");
    } else {
        document.body.classList.remove("dark");
        dayNightIcon?.classList.remove("fa-sun");
        dayNightIcon?.classList.add("fa-moon");
    }

    const savedColor = localStorage.getItem("selectedColor");

    if (savedColor) {
        setActiveStyle(savedColor);
    }
});
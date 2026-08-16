// ================================ Animation de texte (typed.js) =======================================
document.addEventListener("DOMContentLoaded", () => {
    const typedTarget = document.querySelector(".text");

    // Initialiser l'effet de frappe si la bibliothèque Typed.js est disponible
    if (typeof Typed !== "undefined" && typedTarget) {
        const stringsAttr = typedTarget.getAttribute("data-typed-strings");
        const typedStrings = stringsAttr ? JSON.parse(stringsAttr) : [
            "web designer",
            "développeur web",
            "designer graphique",
            "expert cybersécurité",
            "créateur digital"
        ];

        new Typed(".text", {
            strings: typedStrings,
            typeSpeed: 100,   // Vitesse de frappe (ms par caractère)
            backSpeed: 60,    // Vitesse d'effacement (ms par caractère)
            loop: true        // Boucle infinie
        });
    }
});

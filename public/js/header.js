/**
 * Gestion du menu utilisateur dans l'en-tête
 * Gère la visibilité du menu déroulant utilisateur
 */

function initialiserMenuUtilisateur() {
    const btn = document.getElementById('user-menu-btn');
    const dropdown = document.getElementById('user-menu-dropdown');

    if (!btn || !dropdown) {
        console.debug('header.js : éléments de menu introuvables');
        return false;
    }

    btn.setAttribute('aria-expanded', 'false');

    btn.addEventListener('click', function(event) {
        event.preventDefault();
        event.stopPropagation();

        const ouvert = dropdown.classList.toggle('open');
        btn.setAttribute('aria-expanded', ouvert ? 'true' : 'false');
        console.debug('header.js : menu utilisateur togglé', ouvert);
    });

    document.addEventListener('click', function(event) {
        if (!dropdown.classList.contains('open')) return;
        if (btn.contains(event.target) || dropdown.contains(event.target)) return;

        dropdown.classList.remove('open');
        btn.setAttribute('aria-expanded', 'false');
        console.debug('header.js : menu utilisateur fermé en dehors');
    });

    return true;
}

function demarrerMenuUtilisateur() {
    if (!initialiserMenuUtilisateur()) {
        // Re-essayer si le DOM n'est pas complètement construit
        setTimeout(initialiserMenuUtilisateur, 50);
    }
}

if (document.readyState === 'complete' || document.readyState === 'interactive') {
    demarrerMenuUtilisateur();
} else {
    document.addEventListener('DOMContentLoaded', demarrerMenuUtilisateur);
}


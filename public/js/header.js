/**
 * Gestion du menu utilisateur dans l'en-tête
 * Gère la visibilité du menu déroulant utilisateur
 */

document.addEventListener('DOMContentLoaded', function() {
    const btn = document.getElementById('user-menu-btn');
    const dropdown = document.getElementById('user-menu-dropdown');

    if (!btn || !dropdown) return;

    btn.setAttribute('aria-expanded', 'false');

    /**
     * Bascule l'affichage du menu déroulant utilisateur
     */
    btn.addEventListener('click', function(event) {
        event.preventDefault();
        event.stopPropagation();
        const isOpen = dropdown.classList.toggle('open');
        btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });

    /**
     * Ferme le menu déroulant en cliquant à l'extérieur
     */
    document.addEventListener('click', function(event) {
        if (!dropdown.classList.contains('open')) return;
        if (btn.contains(event.target) || dropdown.contains(event.target)) return;
        dropdown.classList.remove('open');
        btn.setAttribute('aria-expanded', 'false');
    });
});

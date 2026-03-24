/**
 * Gestion du formulaire de création de compte
 * Gère le changement de rôle et la visibilité des formulaires pour la création de compte
 */

document.addEventListener('DOMContentLoaded', function() {
    const registerRoleSwitch = document.querySelector('.register-role-switch');
    const roleButtons = registerRoleSwitch?.querySelectorAll('.register-role-btn');
    const roleInput = document.getElementById('register-role-input');
    const sections = document.querySelectorAll('.form-section');

    if (!roleButtons || !roleInput) return;

    /**
     * Met à jour la visibilité des champs de formulaire selon le rôle sélectionné
     * @param {string} selectedRole - Le rôle sélectionné (eleve, pilote, ou entreprise)
     */
    function updateFormFields(selectedRole) {
        sections.forEach(sec => {
            const isActive = sec.dataset.role === selectedRole;
            sec.style.display = isActive ? 'block' : 'none';
            const inputs = sec.querySelectorAll('input, textarea');
            inputs.forEach(input => {
                input.disabled = !isActive;
                input.required = isActive;
            });
        });
    }

    /**
     * Ajoute les écouteurs d'événements de clic aux boutons de rôle
     */
    roleButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            roleButtons.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            roleInput.value = btn.dataset.role;
            updateFormFields(btn.dataset.role);
        });
    });

    // Initialise le formulaire avec la valeur de rôle actuelle
    updateFormFields(roleInput.value);

    // Gère le lien de redirection vers la connexion
    const loginLink = document.getElementById('open-login-inline');
    if (loginLink) {
        loginLink.addEventListener('click', (e) => {
            e.preventDefault();
            window.location.href = 'index.php';
        });
    }
});

// JS vérification fichiers et gestion message pour postuler

document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('.postuler-form');
    if (!form) return;
    const cvInput = document.getElementById('cv');
    const lmInput = document.getElementById('lm');
    const messageDiv = document.getElementById('form-message');
    const maxSize = 2 * 1024 * 1024; // 2 Mo

    form.addEventListener('submit', function(e) {
        let errors = [];
        // Vérif CV
        if (cvInput.files.length === 0) {
            errors.push('Veuillez sélectionner un CV.');
        } else {
            const file = cvInput.files[0];
            if (file.type !== 'application/pdf') {
                errors.push('Le CV doit être un fichier PDF.');
            }
            if (file.size > maxSize) {
                errors.push('Le CV ne doit pas dépasser 2 Mo.');
            }
        }
        // Vérif LM
        if (lmInput.files.length === 0) {
            errors.push('Veuillez sélectionner une lettre de motivation.');
        } else {
            const file = lmInput.files[0];
            if (file.type !== 'application/pdf') {
                errors.push('La lettre de motivation doit être un fichier PDF.');
            }
            if (file.size > maxSize) {
                errors.push('La lettre de motivation ne doit pas dépasser 2 Mo.');
            }
        }
        if (errors.length > 0) {
            e.preventDefault();
            showMessage(errors.join('<br>'), false);
            return false;
        }
        // Si tout est OK, on laisse le formulaire se soumettre normalement (pas d'AJAX)
    });

    function showMessage(msg, success) {
        messageDiv.innerHTML = msg;
        messageDiv.style.display = 'block';
        messageDiv.className = 'form-message ' + (success ? 'success' : 'error');
        window.scrollTo({top: messageDiv.offsetTop - 30, behavior: 'smooth'});
    }
});

document.addEventListener("DOMContentLoaded", function () {

    const tryInit = () => {
        const modal = document.getElementById("login-modal");
        const openBtn = document.getElementById("open-login");
        const closeBtn = document.querySelector(".close");

        if (!modal) {
            setTimeout(tryInit, 50);
            return;
        }

        console.log("modal trouvé", modal);

        // ouverture bouton
        if (openBtn) {
            openBtn.addEventListener("click", function(e){
                e.preventDefault();
                modal.style.display = "flex";
            });
        }

        // fermeture
        if (closeBtn) {
            closeBtn.addEventListener("click", function(){
                modal.style.display = "none";
            });
        }

        window.addEventListener("click", function(e){
            if(e.target === modal){
                modal.style.display = "none";
            }
        });

        // ouverture auto si erreur
        if (modal.dataset.error == "true") {
             modal.style.display = "flex";
            }

        // Gestion des boutons de rôle
        const roleBtns = modal.querySelectorAll('.role-btn');
        const roleInput = modal.querySelector('#role-input');

        roleBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                // Retirer la classe active de tous les boutons
                roleBtns.forEach(b => b.classList.remove('active'));
                // Ajouter la classe active au bouton cliqué
                this.classList.add('active');
                // Mettre à jour la valeur du champ caché
                const role = this.textContent.toLowerCase();
                if (role === 'élève') {
                    roleInput.value = 'eleve';
                } else if (role === 'pilote') {
                    roleInput.value = 'pilote';
                } else if (role === 'entreprise') {
                    roleInput.value = 'entreprise';
                }
            });
        });
    };

    tryInit();

});
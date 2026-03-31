document.addEventListener("DOMContentLoaded", function () {

    const tryInit = () => {
        const modal = document.getElementById("login-modal");
        const openBtn = document.getElementById("open-login");
        const closeBtn = document.querySelector(".close");

        if (!modal) {
            setTimeout(tryInit, 50);
            return;
        }


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
                roleBtns.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                roleInput.value = this.dataset.role || 'eleve';
            });
        });
    };

    tryInit();

});
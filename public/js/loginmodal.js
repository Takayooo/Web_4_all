document.addEventListener("DOMContentLoaded", function () {

    const tryInit = () => {
        const modal = document.getElementById("login-modal");
        const openBtn = document.getElementById("open-login");
        const closeBtn = document.querySelector(".close");

        if (!modal) {
            // ⏳ on réessaie jusqu’à ce que le DOM soit prêt
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
    };

    tryInit();

});
const modal = document.getElementById("login-modal");
const openBtn = document.getElementById("open-login");
const closeBtn = document.querySelector(".close");

if (openBtn && modal) {

    openBtn.onclick = function(e){
        e.preventDefault();
        modal.style.display = "flex";
    }

    closeBtn.onclick = function(){
        modal.style.display = "none";
    }

    window.onclick = function(e){
        if(e.target === modal){
            modal.style.display = "none";
        }
    }
}

// gestion des rôles
const roleButtons = document.querySelectorAll(".role-btn");

roleButtons.forEach(btn => {
    btn.addEventListener("click", () => {
        roleButtons.forEach(b => b.classList.remove("active"));
        btn.classList.add("active");
    });
});
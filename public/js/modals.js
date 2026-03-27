/**
 * Fonctions de gestion des modals
 * Gère l'ouverture et la fermeture des modals pour l'édition de formulaires
 */

/**
 * Ouvre la modal d'édition et la remplit avec les données de l'étudiant
 * @param {number} id - Identifiant de l'étudiant
 * @param {string} nom - Nom de famille
 * @param {string} prenom - Prénom
 * @param {string} email - Adresse email
 */
function editEleve(id, nom, prenom, email) {
    document.getElementById('editId').value = id;
    document.getElementById('nom').value = nom;
    document.getElementById('prenom').value = prenom;
    document.getElementById('email').value = email;
    document.getElementById('editModal').style.display = 'flex';
}

/**
 * Ouvre la modal d'édition et la remplit avec les données de l'entreprise
 * @param {number} id - Identifiant de l'entreprise
 * @param {string} nom - Nom de l'entreprise
 * @param {string} secteur - Secteur d'activité
 * @param {string} ville - Ville
 * @param {string} email - Adresse email
 */
function editEntreprise(id, nom, secteur, ville, email) {
    document.getElementById('editId').value = id;
    document.getElementById('nom').value = nom;
    document.getElementById('secteur').value = secteur;
    document.getElementById('ville').value = ville;
    document.getElementById('email').value = email;
    document.getElementById('editModal').style.display = 'flex';
}

/**
 * Ferme la modal d'édition
 */
function closeModal() {
    document.getElementById('editModal').style.display = 'none';
}

/**
 * Ferme la modal en cliquant en dehors du contenu de la modal
 */
window.onclick = function(event) {
    const modal = document.getElementById('editModal');
    if (modal && event.target === modal) {
        modal.style.display = 'none';
    }
};

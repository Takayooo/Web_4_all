function editAccount(id, accountType, nom, prenom, email, secteur, ville) {
    const modal = document.getElementById('editModal');
    const prenomField = document.getElementById('prenomField');
    const secteurField = document.getElementById('secteurField');
    const villeField = document.getElementById('villeField');
    const prenomInput = document.getElementById('prenom');
    const secteurInput = document.getElementById('secteur');
    const villeInput = document.getElementById('ville');
    const modalTitle = document.getElementById('editModalTitle');

    document.getElementById('editId').value = id;
    document.getElementById('nom').value = nom || '';
    document.getElementById('email').value = email || '';

    if (accountType === 'entreprise') {
        modalTitle.textContent = 'Modifier l\'entreprise';
        prenomField.style.display = 'none';
        secteurField.style.display = 'block';
        villeField.style.display = 'block';
        prenomInput.required = false;
        prenomInput.value = '';
        secteurInput.required = true;
        villeInput.required = true;
        secteurInput.value = secteur || '';
        villeInput.value = ville || '';
    } else {
        modalTitle.textContent = 'Modifier le compte';
        prenomField.style.display = 'block';
        secteurField.style.display = 'none';
        villeField.style.display = 'none';
        prenomInput.required = true;
        prenomInput.value = prenom || '';
        secteurInput.required = false;
        villeInput.required = false;
        secteurInput.value = '';
        villeInput.value = '';
    }

    modal.style.display = 'flex';
}

function editEleve(id, nom, prenom, email) {
    editAccount(id, 'standard', nom, prenom, email, '', '');
}

function editEntreprise(id, nom, secteur, ville, email) {
    editAccount(id, 'entreprise', nom, '', email, secteur, ville);
}

function closeModal() {
    document.getElementById('editModal').style.display = 'none';
}

window.onclick = function(event) {
    const modal = document.getElementById('editModal');
    if (modal && event.target === modal) {
        modal.style.display = 'none';
    }
};

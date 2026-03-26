<?php
require 'data_helpers.php';

$offres = [
    ['id' => 1, 'titre' => 'Stage - Développeur Web', 'entreprise_id' => 1, 'contrat' => 'stage', 'statut' => 'active', 'description' => 'Développement d\'applications web modernes.'],
    ['id' => 2, 'titre' => 'Stage - Designer UI/UX', 'entreprise_id' => 2, 'contrat' => 'stage', 'statut' => 'active', 'description' => 'Conception d\'interfaces utilisateur intuitives.'],
    ['id' => 3, 'titre' => 'Alternance - Ingénieur Logiciel', 'entreprise_id' => 3, 'contrat' => 'alternance', 'statut' => 'active', 'description' => 'Développement de logiciels innovants.'],
    ['id' => 4, 'titre' => 'Alternance - Médecin Généraliste', 'entreprise_id' => 4, 'contrat' => 'alternance', 'statut' => 'active', 'description' => 'Pratique médicale généraliste.']
];

$result = enregistrer_offres($offres);
echo $result ? 'Fichier offres.json créé avec succès.' : 'Erreur lors de la création du fichier.';
?>
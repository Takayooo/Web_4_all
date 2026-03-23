<?php

/* ------------------------
   ENTREPRISES
------------------------ */

$entreprises = [
    1 => ['id' => 1, 'nom' => 'TechCorp', 'note' => 4.0, 'secteur' => 'Technologie', 'ville' => 'Paris'],
    2 => ['id' => 2, 'nom' => 'FinSoft', 'note' => 4.2, 'secteur' => 'Finance', 'ville' => 'Londres'],
    3 => ['id' => 3, 'nom' => 'GreenEnergy', 'note' => 4.1, 'secteur' => 'Énergie', 'ville' => 'Berlin'],
    4 => ['id' => 4, 'nom' => 'HealthPlus', 'note' => 4.3, 'secteur' => 'Santé', 'ville' => 'Madrid'],
];

/* ------------------------
   OFFRES (liées aux entreprises)
------------------------ */

$offres = [
    ['id' => 1, 'titre' => 'Stage - Développeur Web', 'entreprise_id' => 1, 'contrat' => 'stage', 'statut' => 'active'],
    ['id' => 2, 'titre' => 'Stage - Designer UI/UX', 'entreprise_id' => 2, 'contrat' => 'stage', 'statut' => 'active'],
    ['id' => 3, 'titre' => 'Alternance - Ingénieur Logiciel', 'entreprise_id' => 3, 'contrat' => 'alternance', 'statut' => 'active'],
    ['id' => 4, 'titre' => 'Alternance - Médecin Généraliste', 'entreprise_id' => 4, 'contrat' => 'alternance', 'statut' => 'active'],
];

/* Génération jusqu’à 50 offres */
$contracts = ['stage', 'alternance'];
for ($i = 5; $i <= 50; $i++) {
    $offres[] = [
        'id' => $i,
        'titre' => "Stage - Poste $i",
        'entreprise_id' => rand(1, 4),
        'contrat' => $contracts[array_rand($contracts)],
        'statut' => 'active' // par défaut inactives pour tester le filtre
    ];
}

/* ------------------------
   RECHERCHE
------------------------ */

$search = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_SPECIAL_CHARS);
$contrat = filter_input(INPUT_GET, 'contrat', FILTER_SANITIZE_SPECIAL_CHARS);
$secteur = filter_input(INPUT_GET, 'secteur', FILTER_SANITIZE_SPECIAL_CHARS);
$ville = filter_input(INPUT_GET, 'ville', FILTER_SANITIZE_SPECIAL_CHARS);

// garder uniquement les offres actives
$offresActives = array_filter($offres, function ($offre) {
    return isset($offre['statut']) && $offre['statut'] === 'active';
});

// filtre recherche/contract/secteur/ville
$offresFiltrees = array_filter($offresActives, function ($offre) use ($search, $contrat, $secteur, $ville, $entreprises) {
    $entreprise = $entreprises[$offre['entreprise_id']];

    if ($search) {
        $mot = stripos($offre['titre'], $search) !== false ||
               stripos($entreprise['nom'], $search) !== false ||
               stripos($entreprise['secteur'], $search) !== false ||
               stripos($entreprise['ville'], $search) !== false;
        if (!$mot) {
            return false;
        }
    }

    if ($contrat && $offre['contrat'] !== $contrat) {
        return false;
    }

    if ($secteur && stripos($entreprise['secteur'], $secteur) === false) {
        return false;
    }

    if ($ville && stripos($entreprise['ville'], $ville) === false) {
        return false;
    }

    return true;
});

// tri sur l'ID croissant (pour pagination) pour offres page
usort($offresFiltrees, function ($a, $b) {
    return $a['id'] <=> $b['id'];
});

$offres = array_values($offresFiltrees);

/* ------------------------
   PAGINATION
------------------------ */

$offresParPage = 12;

$totalOffres = count($offres);
$totalPages = ceil($totalOffres / $offresParPage);

$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1]
]);

if (!$page) {
    $page = 1;
}

if ($page > $totalPages) {
    $page = $totalPages;
}

$debut = ($page - 1) * $offresParPage;

$offresPage = array_slice($offres, $debut, $offresParPage);
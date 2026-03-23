<?php

/* ------------------------
   ENTREPRISES
------------------------ */

$entreprises = [
    1 => ['nom' => 'TechCorp', 'note' => 4.0, 'secteur' => 'Technologie', 'ville' => 'Paris'],
    2 => ['nom' => 'FinSoft', 'note' => 4.2, 'secteur' => 'Finance', 'ville' => 'Londres'],
    3 => ['nom' => 'GreenEnergy', 'note' => 4.1, 'secteur' => 'Énergie', 'ville' => 'Berlin'],
    4 => ['nom' => 'HealthPlus', 'note' => 4.3, 'secteur' => 'Santé', 'ville' => 'Madrid'],
];

/* ------------------------
   OFFRES (liées aux entreprises)
------------------------ */

$offres = [
    ['titre' => 'Stage - Développeur Web', 'entreprise_id' => 1],
    ['titre' => 'Stage - Designer UI/UX', 'entreprise_id' => 2],
    ['titre' => 'Stage - Ingénieur Logiciel', 'entreprise_id' => 3],
    ['titre' => 'Stage - Médecin Généraliste', 'entreprise_id' => 4],
];

/* Génération jusqu’à 50 offres */
for ($i = 5; $i <= 50; $i++) {
    $offres[] = [
        'titre' => "Stage - Poste $i",
        'entreprise_id' => rand(1, 4) // lien aléatoire
    ];
}

/* ------------------------
   RECHERCHE
------------------------ */

$search = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_SPECIAL_CHARS);

if ($search) {

    usort($offres, function ($a, $b) use ($search, $entreprises) {

        $entrepriseA = $entreprises[$a['entreprise_id']];
        $entrepriseB = $entreprises[$b['entreprise_id']];

        $aMatch =
            stripos($a['titre'], $search) !== false ||
            stripos($entrepriseA['nom'], $search) !== false ||
            stripos($entrepriseA['secteur'], $search) !== false ||
            stripos($entrepriseA['ville'], $search) !== false;

        $bMatch =
            stripos($b['titre'], $search) !== false ||
            stripos($entrepriseB['nom'], $search) !== false ||
            stripos($entrepriseB['secteur'], $search) !== false ||
            stripos($entrepriseB['ville'], $search) !== false;

        if ($aMatch && !$bMatch) return -1;
        if (!$aMatch && $bMatch) return 1;

        return 0;
    });
}

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
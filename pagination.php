<?php

// Tableau de 50 entreprises
$entreprises = [
    ['nom' => 'TechCorp', 'secteur' => 'Technologie', 'ville' => 'Paris'],
    ['nom' => 'FinSoft', 'secteur' => 'Finance', 'ville' => 'Londres'],
    ['nom' => 'GreenEnergy', 'secteur' => 'Énergie', 'ville' => 'Berlin'],
    ['nom' => 'HealthPlus', 'secteur' => 'Santé', 'ville' => 'Madrid'],
    ['nom' => 'BuildPro', 'secteur' => 'Construction', 'ville' => 'Rome'],
    ['nom' => 'FoodExpress', 'secteur' => 'Agroalimentaire', 'ville' => 'Bruxelles'],
    ['nom' => 'AutoDrive', 'secteur' => 'Automobile', 'ville' => 'Munich'],
    ['nom' => 'SkyNet', 'secteur' => 'Télécommunications', 'ville' => 'Amsterdam'],
    ['nom' => 'EduSmart', 'secteur' => 'Éducation', 'ville' => 'Dublin'],
    ['nom' => 'SecureIT', 'secteur' => 'Cybersécurité', 'ville' => 'Zurich'],
];

// Génération jusqu’à 50 entreprises
for ($i = 11; $i <= 50; $i++) {
    $entreprises[] = [
        'nom' => "Entreprise$i",
        'secteur' => "Secteur$i",
        'ville' => "Ville$i"
    ];
}

$annoncesParPage = 10;

$totalAnnonces = count($entreprises);
$totalPages = ceil($totalAnnonces / $annoncesParPage);

$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1]
]);

if (!$page) {
    $page = 1;
}

if ($page > $totalPages) {
    $page = $totalPages;
}

$debut = ($page - 1) * $annoncesParPage;

$annoncesPage = array_slice($entreprises, $debut, $annoncesParPage);

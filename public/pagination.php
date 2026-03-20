<?php

// Tableau de 50 entreprises
$entreprises = [
    ['titre' => 'Stage - Développeur Web', 'nom' => 'TechCorp','note' => 4.0, 'secteur' => 'Technologie', 'ville' => 'Paris'],
    ['titre' => 'Stage - Designer UI/UX', 'nom' => 'FinSoft', 'note' => 4.2, 'secteur' => 'Finance', 'ville' => 'Londres'],
    ['titre' => 'Stage - Ingénieur Logiciel', 'nom' => 'GreenEnergy', 'note' => 4.1, 'secteur' => 'Énergie', 'ville' => 'Berlin'],
    ['titre' => 'Stage - Médecin Généraliste', 'nom' => 'HealthPlus', 'note' => 4.3, 'secteur' => 'Santé', 'ville' => 'Madrid'],
    ['titre' => 'Stage - Architecte Logiciel', 'nom' => 'BuildPro', 'note' => 4.0, 'secteur' => 'Construction', 'ville' => 'Rome'],
    ['titre' => 'Stage - Chef de Projet Agroalimentaire', 'nom' => 'FoodExpress', 'note' => 4.2, 'secteur' => 'Agroalimentaire', 'ville' => 'Bruxelles'],
    ['titre' => 'Stage - Ingénieur en Systèmes', 'nom' => 'AutoDrive', 'note' => 4.1, 'secteur' => 'Automobile', 'ville' => 'Munich'],
    ['titre' => 'Stage - Consultant Télécommunications', 'nom' => 'SkyNet', 'note' => 4.2, 'secteur' => 'Télécommunications', 'ville' => 'Amsterdam'],
    ['titre' => 'Stage - Formateur en Éducation', 'nom' => 'EduSmart', 'note' => 4.0, 'secteur' => 'Éducation', 'ville' => 'Dublin'],
    ['titre' => 'Stage - Analyste en Cybersécurité', 'nom' => 'SecureIT', 'note' => 4.3, 'secteur' => 'Cybersécurité', 'ville' => 'Zurich'],
];

// Génération jusqu’à 50 entreprises
for ($i = 11; $i <= 50; $i++) {
    $entreprises[] = [
        'titre' => "Stage - Poste $i",
        'nom' => "Entreprise$i",
        'secteur' => "Secteur$i",
        'ville' => "Ville$i",
        'note' => 4.0
    ];
}

$annoncesParPage = 12;

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

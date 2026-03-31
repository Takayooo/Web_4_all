<?php
require_once 'data_helpers.php';

/* ------------------------
   ENTREPRISES
------------------------ */

$entreprises = get_entreprises_map();

/* ------------------------
   OFFRES (liées aux entreprises)
------------------------ */

$offres = charger_offres();

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
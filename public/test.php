<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require 'pagination.php';
    echo "Pagination chargé avec succès\n";
    echo "Nombre d'offres: " . count($offres) . "\n";
    echo "Nombre d'entreprises: " . count($entreprises) . "\n";
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
    echo "Fichier: " . $e->getFile() . " Ligne: " . $e->getLine() . "\n";
}
?>
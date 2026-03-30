<?php
require_once __DIR__ . '/../config/database.php';

echo "<h2>Test connexion BDD – Offres</h2>";

try {
    // Requête sur la table des offres
    $stmt = $pdo->query("SELECT * FROM offre");
    $offers = $stmt->fetchAll();

    echo "<h3>Liste des offres :</h3>";

    foreach ($offers as $offer) {
        echo "<strong>" . htmlspecialchars($offer['titre']) . "</strong><br>";
        echo htmlspecialchars($offer['description']) . "<br><br>";
    }

    echo "<br>Connexion et requête OK";

} catch (Exception $e) {
    echo "Erreur : " . $e->getMessage();
}
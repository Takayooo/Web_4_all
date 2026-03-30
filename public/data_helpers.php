<?php

function charger_json_fichier(string $chemin, $parDefaut = []) {
    if (!file_exists($chemin)) {
        return $parDefaut;
    }

    $contenu = file_get_contents($chemin);
    $donnees = json_decode($contenu, true);
    return is_array($donnees) ? $donnees : $parDefaut;
}

function enregistrer_json_fichier(string $chemin, array $donnees): bool {
    $dossier = dirname($chemin);
    if (!is_dir($dossier)) {
        mkdir($dossier, 0755, true);
    }
    return file_put_contents($chemin, json_encode($donnees, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;
}

function fichier_candidatures(): string {
    return __DIR__ . '/applications.json';
}

function fichier_favoris(): string {
    return __DIR__ . '/wishlists.json';
}

function fichier_offres(): string {
    return __DIR__ . '/offres.json';
}

function charger_candidatures(): array {
    return charger_json_fichier(fichier_candidatures(), []);
}

function enregistrer_candidatures(array $candidatures): bool {
    return enregistrer_json_fichier(fichier_candidatures(), $candidatures);
}

function charger_favoris(): array {
    return charger_json_fichier(fichier_favoris(), []);
}

function enregistrer_favoris(array $favoris): bool {
    return enregistrer_json_fichier(fichier_favoris(), $favoris);
}

function charger_offres(): array {
    return charger_json_fichier(fichier_offres(), []);
}

function enregistrer_offres(array $offres): bool {
    return enregistrer_json_fichier(fichier_offres(), $offres);
}

function ajouter_candidature(int $utilisateurId, int $offreId, string $cvChemin, ?string $lmChemin = null): bool {
    $candidatures = charger_candidatures();
    $cleUtilisateur = (string)$utilisateurId;

    if (!isset($candidatures[$cleUtilisateur])) {
        $candidatures[$cleUtilisateur] = [];
    }

    foreach ($candidatures[$cleUtilisateur] as &$entree) {
        if ($entree['offre_id'] === $offreId) {
            $entree['date'] = date('Y-m-d H:i:s');
            $entree['cv'] = $cvChemin;
            $entree['lm'] = $lmChemin;
            return enregistrer_candidatures($candidatures);
        }
    }

    $candidatures[$cleUtilisateur][] = [
        'offre_id' => $offreId,
        'date' => date('Y-m-d H:i:s'),
        'cv' => $cvChemin,
        'lm' => $lmChemin
    ];

    return enregistrer_candidatures($candidatures);
}

function supprimer_candidature(int $utilisateurId, int $offreId): bool {
    $candidatures = charger_candidatures();
    $cleUtilisateur = (string)$utilisateurId;

    if (!isset($candidatures[$cleUtilisateur])) {
        return false;
    }

    $candidatures[$cleUtilisateur] = array_values(array_filter($candidatures[$cleUtilisateur], function ($entree) use ($offreId) {
        return $entree['offre_id'] !== $offreId;
    }));

    return enregistrer_candidatures($candidatures);
}

function get_candidatures_utilisateur(int $utilisateurId): array {
    $candidatures = charger_candidatures();
    $cleUtilisateur = (string)$utilisateurId;
    return $candidatures[$cleUtilisateur] ?? [];
}

function ajouter_favori(int $utilisateurId, int $offreId): bool {
    $favoris = charger_favoris();
    $cleUtilisateur = (string)$utilisateurId;

    if (!isset($favoris[$cleUtilisateur])) {
        $favoris[$cleUtilisateur] = [];
    }

    if (!in_array($offreId, $favoris[$cleUtilisateur], true)) {
        $favoris[$cleUtilisateur][] = $offreId;
    }

    return enregistrer_favoris($favoris);
}

function supprimer_favori(int $utilisateurId, int $offreId): bool {
    $favoris = charger_favoris();
    $cleUtilisateur = (string)$utilisateurId;

    if (!isset($favoris[$cleUtilisateur])) {
        return false;
    }

    $favoris[$cleUtilisateur] = array_values(array_filter($favoris[$cleUtilisateur], function ($id) use ($offreId) {
        return $id !== $offreId;
    }));

    return enregistrer_favoris($favoris);
}

function get_favoris_utilisateur(int $utilisateurId): array {
    $favoris = charger_favoris();
    $cleUtilisateur = (string)$utilisateurId;
    return $favoris[$cleUtilisateur] ?? [];
}

function creer_offre(int $entrepriseId, string $titre, string $description = '', string $contrat = 'stage'): int {
    $offres = charger_offres();
    $nouvelId = empty($offres) ? 1 : max(array_column($offres, 'id')) + 1;
    $offre = [
        'id' => $nouvelId,
        'entreprise_id' => $entrepriseId,
        'titre' => $titre,
        'description' => $description,
        'contrat' => $contrat,
        'statut' => 'active',
        'date_creation' => date('Y-m-d H:i:s')
    ];
    $offres[] = $offre;
    enregistrer_offres($offres);
    return $nouvelId;
}

function modifier_offre(int $offreId, int $entrepriseId, string $titre, string $description = '', string $contrat = 'stage'): bool {
    $offres = charger_offres();
    if (!is_array($offres)) {
        return false;
    }

    foreach ($offres as $index => $offre) {
        if (isset($offre['id']) && isset($offre['entreprise_id']) &&
            $offre['id'] === $offreId && $offre['entreprise_id'] === $entrepriseId) {
            $offres[$index]['titre'] = $titre;
            $offres[$index]['description'] = $description;
            $offres[$index]['contrat'] = $contrat;
            $offres[$index]['date_modification'] = date('Y-m-d H:i:s');
            return enregistrer_offres($offres);
        }
    }

    return false;
}

function changer_statut_offre(int $offreId, int $entrepriseId): ?string {
    $offres = charger_offres();
    if (!is_array($offres)) {
        return null;
    }

    foreach ($offres as $index => $offre) {
        if (isset($offre['id']) && isset($offre['entreprise_id']) &&
            $offre['id'] === $offreId && $offre['entreprise_id'] === $entrepriseId) {
            $statutActuel = $offre['statut'] ?? 'active';
            $offres[$index]['statut'] = $statutActuel === 'active' ? 'inactive' : 'active';
            enregistrer_offres($offres);
            return $offres[$index]['statut'];
        }
    }
    return null;
}


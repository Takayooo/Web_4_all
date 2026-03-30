<?php
require 'data_helpers.php';
require 'pagination.php';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="Contactez l'équipe Web4All pour toute question sur les offres, les candidatures ou votre compte.">
<title>Contact</title>
<link rel="stylesheet" href="style.css?v=16">
</head>

<body>

<?php include 'header.php'; ?>

<main>


<section class="contact-content">
    <div class="containercontact">
        <div class="contact-form">
            <h2>Envoyez un message</h2>
            <form action="https://formspree.io/f/mnjgonba" method="POST" novalidate>
                <div class="form-group">
                    <label for="name">Nom</label>
                    <input type="text" id="name" name="name" autocomplete="name" minlength="2" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" autocomplete="email" required>
                </div>
                <div class="form-group">
                    <label for="message">Message</label>
                    <textarea id="message" name="message" rows="5" minlength="10" required></textarea>
                </div>
                <button type="submit" class="button">Envoyer</button>
            </form>
        </div>

        <div class="contact-info">
            <h2>Mes coordonnées</h2>
            <div class="info-card">
                <h3>Informations personnelles</h3>
                <p><strong>Nom de Société :</strong> Web4All.</p>
                <p><strong>Numéro de SIRET :</strong> 123 456 789 00012</p>
                <p><strong>Téléphone :</strong> 01 23 45 67 89</p>
                <p><strong>Email :</strong> contact@web4all.fr</p>
                <p><strong>Adresse :</strong> Villeurbanne (69100)</p>
            </div>
            <div class="info-card">
                <h3>Réseaux sociaux</h3>
                <p>Pour suivre notre communication sur les réseaux :</p>
                <a href="https://www.facebook.com/" target="_blank" rel="noopener noreferrer" class="social-link">Facebook</a>
                <a href="https://www.linkedin.com/" target="_blank" rel="noopener noreferrer" class="social-link">LinkedIn</a>
                <a href="https://www.twitter.com/" target="_blank" rel="noopener noreferrer" class="social-link">Twitter</a>
                <a href="https://www.github.com/Takayooo/Web_4_All/" target="_blank" rel="noopener noreferrer" class="social-link">GitHub</a>
            </div>
        </div>
    </div>
</section>

</main>

<?php include 'footer.php'; ?>

</body>
</html>
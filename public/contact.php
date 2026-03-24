<?php require 'pagination.php'; ?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Contact</title>
<link rel="stylesheet" href="style.css">
</head>

<body>

<?php include 'header.php'; ?>


<section class="contact-content">
    <div class="container">
        <div class="contact-form">
            <h2>Envoyez un message</h2>
            <form action="https://formspree.io/f/mnjgonba" method="POST">
                <div class="form-group">
                    <label for="name">Nom</label>
                    <input type="text" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="message">Message</label>
                    <textarea id="message" name="message" rows="5" required></textarea>
                </div>
                <button type="submit" class="button">Envoyer</button>
            </form>
        </div>

        <div class="contact-info">
            <h2>Mes coordonnées</h2>
            <div class="info-card">
                <h3>Informations personnelles</h3>
                <p><strong>Nom :</strong> Nathan Bouget</p>
                <p><strong>Téléphone :</strong> 06.65.53.26.08</p>
                <p><strong>Email :</strong> nbouget0106@gmail.com</p>
                <p><strong>Adresse :</strong> Saint Maurice de Gourdans (01800)</p>
            </div>
            <div class="info-card">
                <h3>Réseaux sociaux</h3>
                <p>Pour voir mes projets et mon CV, n'hésitez pas à visiter les autres pages de ce portfolio. Vous pouvez également me contacter via :</p>
                <a href="https://www.linkedin.com/in/nathan-bouget-9b1a4b1b6/" target="_blank" class="social-link">LinkedIn</a>
                <a href="https://github.com/nathanbouget" target="_blank" class="social-link">GitHub</a>
            </div>
        </div>
    </div>
</section>

<?php include 'footer.php'; ?>

</body>
</html>
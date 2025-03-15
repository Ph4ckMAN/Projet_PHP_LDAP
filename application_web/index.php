<!DOCTYPE html>
<html lang="fr">
<head>
    <!-- CSS files -->
    <link rel='stylesheet' type='text/css' href='./css/Index.css' media='screen' />
    <link rel='stylesheet' type='text/css' href='./css/form-style.css' media='screen' />
    <link rel='stylesheet' type='text/css' href='./css/02_fonts.css' media='screen' />
    <link rel='stylesheet' type='text/css' href='./css/03_icons.css' media='screen' />

    <!-- JS files -->
    <script type='text/javascript' src='./js/jquery-3.7.0.min.js'></script>
    <script type='text/javascript' src='./js/jquery-ui.min.js'></script>
    <!-- <script type='text/javascript' src='./js/web.js'></script> -->

    <!-- UTF8 encoding -->
    <meta charset='UTF-8'>
    <title>Connexion LDAP</title>
</head>
<body>

    <nav class="navbar">
            <div class="navbar-content">
                <h1>LDAP Server</h1>
            </div>
        </nav>

        <div class="logo-container">
            <img src="./design/image.png" alt="Logo" class="logo">
        </div>

        <div class="container">
    <h2>Connexion LDAP</h2>

    <!-- Affichage des messages d'erreur -->
    <?php
    session_start();  
    if (isset($_SESSION['error'])) {
        echo '<p class="message error">' . $_SESSION['error'] . '</p>';
        unset($_SESSION['error']);  
    }
    ?>

    <!-- Formulaire de connexion -->
        <form method="POST" action="ldapAuth.php">
            <label for="username">Nom d'utilisateur ou Uid :</label>
            <input type="text" id="username" name="username" class="input-style" required><br><br>

            <label for="password">Mot de passe :</label>
            <input type="password" id="password" name="password" class="input-style" required><br><br>

            <button type="submit" class="submit-button" >Se connecter</button>
        </form>

    <br>

        <form method="POST" action="ldapAuth.php">
            <button type="submit" name="anonymous"  class="anonymousConnection" value="1">Connexion Anonyme</button>
        </form>

    </div>

</body>
</html>

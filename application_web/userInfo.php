<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'user') {
    $_SESSION['error'] = "Vous devez être connecté en tant qu'utilisateur.";
    header("Location: index.php");
    exit();
}

// Vérifier si le mot de passe est dans la session
if (!isset($_SESSION['user_password'])) {
    $_SESSION['error'] = "Mot de passe non trouvé.";
    header("Location: index.php");
    exit();
}

// Se connecter à LDAP
$ldap_con = ldap_connect("ldap://<IP Serveur LDAP>");

if (!$ldap_con) {
    $_SESSION['error'] = "Impossible de se connecter au serveur LDAP.";
    header("Location: index.php");
    exit();
}

// Configurer le protocole LDAP
ldap_set_option($ldap_con, LDAP_OPT_PROTOCOL_VERSION, 3);

// Récupérer l'UID de l'utilisateur connecté et son mot de passe
$username = $_SESSION['username'];
$password = $_SESSION['user_password'];  

// Construire le DN de l'utilisateur
$escaped_username = ldap_escape($username, "", LDAP_ESCAPE_FILTER);
$dn = "uid=$escaped_username,ou=People,dc=iut6-kourou,dc=fr";

// Tenter la liaison avec le serveur LDAP
$bind = @ldap_bind($ldap_con, $dn, $password);

if (!$bind) {
    // Si la liaison échoue, nous récupérons le code d'erreur LDAP
    $error_code = ldap_errno($ldap_con);
    $error_message = ldap_error($ldap_con);
    
    $_SESSION['error'] = "Échec de la liaison LDAP. Code d'erreur: $error_code, Message: $error_message.";
    header("Location: index.php");
    exit();
}

// Rechercher les informations de l'utilisateur
$base_dn = "ou=People,dc=iut6-kourou,dc=fr";
$filter = "(uid=$escaped_username)";
$search = ldap_search($ldap_con, $base_dn, $filter);

// Récupérer les résultats de la recherche
$entries = ldap_get_entries($ldap_con, $search);

// Vérifier si des résultats ont été trouvés
if ($entries['count'] > 0) {
    // Récupérer les informations de l'utilisateur
    $cn = $entries[0]["cn"][0];
    $sn = $entries[0]["sn"][0];
    $givenName = isset($entries[0]["givenname"][0]) ? $entries[0]["givenname"][0] : "Non renseigné";
    $mail = $entries[0]["mail"][0];
    $mobile = isset($entries[0]["mobile"][0]) ? $entries[0]["mobile"][0] : "Non renseigné";
    
    // Vérifier si le mot de passe est disponible
    if (isset($entries[0]["userpassword"][0])) {
        $userPassword = $entries[0]["userpassword"][0];  // Récupérer le mot de passe chiffré
    } else {
        $userPassword = "Non disponible";
    }
    
    $dn = $entries[0]["dn"];
} else {
    $_SESSION['error'] = "Aucun utilisateur trouvé.";
    header("Location: index.php");
    exit();
}

ldap_unbind($ldap_con);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <!-- CSS files -->
    <link rel="stylesheet" type="text/css" href="./css/userInfo.css" media="screen" />
    <link rel="stylesheet" type="text/css" href="./css/form-style.css" media="screen" />
    <link rel="stylesheet" type="text/css" href="./css/02_fonts.css" media="screen" />
    <link rel="stylesheet" type="text/css" href="./css/03_icons.css" media="screen" />

    <!-- JS files -->
    <script type="text/javascript" src="./js/jquery-3.7.0.min.js"></script>
    <script type="text/javascript" src="./js/jquery-ui.min.js"></script>
    <!-- <script type="text/javascript" src="./js/web.js"></script> -->

    <!-- UTF8 encoding -->
    <meta charset="UTF-8">
    <title>Info Utilisateur</title>
</head>
<body>

    <nav class="navbar">
        <div class="navbar-content">
            <h1>LDAP Server</h1>
        </div>
    </nav>

    <div class="user-info">
        <h2>Informations de l'utilisateur <?php echo htmlspecialchars($username); ?></h2>
        <p><strong>cn :</strong> <?php echo htmlspecialchars($cn); ?></p>
        <p><strong>givenName :</strong> <?php echo htmlspecialchars($givenName); ?></p>
        <p><strong>sn :</strong> <?php echo htmlspecialchars($sn); ?></p>
        <p><strong>dn :</strong> <?php echo htmlspecialchars($dn); ?></p>
        <p><strong>mail :</strong> <?php echo htmlspecialchars($mail); ?></p>
        <p><strong>mobile :</strong> <?php echo htmlspecialchars($mobile); ?></p>
        <p><strong>userPassword :</strong> <?php echo htmlspecialchars($userPassword); ?></p>
    </div>

    <div>
        <a href="logout.php" class="logout-link">Se déconnecter</a>
    </div>

</body>
</html>

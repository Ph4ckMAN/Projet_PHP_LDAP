<?php
session_start();

if (!isset($_SESSION['username'])) {
    header('Location: index.php');
    exit();
}

if (!isset($_SESSION['admin_dn']) || !isset($_SESSION['admin_password'])) {
    $_SESSION['error'] = "Erreur : Informations d'authentification administrateur manquantes ou session expirée.";
    header('Location: index.php');
    exit();
}

$ldap_con = ldap_connect("ldap://<IP Serveur LDAP>");

if (!$ldap_con) {
    die("Impossible de se connecter au serveur LDAP.");
}

ldap_set_option($ldap_con, LDAP_OPT_PROTOCOL_VERSION, 3);

$base_dn_groups = "ou=Groups,dc=iut6-kourou,dc=fr";
$group_filter = "(objectClass=posixGroup)";
$group_result = ldap_search($ldap_con, $base_dn_groups, $group_filter);
$group_entries = ldap_get_entries($ldap_con, $group_result);

// Fonction pour générer un mot de passe SSHA
function generateSSHA($userPassword) {
    // Génération d'un sel aléatoire de 8 octets
    $salt = bin2hex(random_bytes(8));  
    
    // Créer le hachage SHA-1 du mot de passe + sel
    $sha_hash = sha1($userPassword . hex2bin($salt), true);  // SHA-1 binaire
    
    // Combine le hachage et le salt
    $hashed_pass = $sha_hash . hex2bin($salt);
    
    // Encoder en Base64
    $encoded_pass = base64_encode($hashed_pass);
    
    // Mot de passe final au format SSHA
    return "{SSHA}" . $encoded_pass;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $givenName = $_POST['givenName'];
    $sn = $_POST['sn'];
    $mobile = $_POST['mobile'];
    $userPassword = $_POST['password'];  // Mot de passe saisi par l'utilisateur
    $selectedGroup = $_POST['group'];

    // Générer le mot de passe haché en SSHA
    $ssha_password = generateSSHA($userPassword);

    $cn = ucfirst(strtolower($givenName)) . ' ' . strtoupper($sn);
    $sn = strtoupper($sn);
    $uid = strtolower($givenName) . '.' . strtolower($sn);

    // Génération de l'email basé sur le groupe sélectionné
    $email = ($selectedGroup === "Administratif" || $selectedGroup === "Profs")
        ? strtolower($givenName) . '.' . strtolower($sn) . '@iut6-kourou.fr'
        : strtolower($givenName) . '.' . strtolower($sn) . '@etu.iut6-kourou.fr';

    // Vérification du groupe sélectionné : Si le groupe sélectionné est différent de "Administratif" ou "Profs", l'utilisateur doit aussi être ajouté au groupe "Etudiants"
    $addToEtudiants = !in_array($selectedGroup, ["Administratif", "Profs"]);

    if (ldap_bind($ldap_con, $_SESSION['admin_dn'], $_SESSION['admin_password'])) {
        $base_dn = "ou=People,dc=iut6-kourou,dc=fr";
        $filter = "(uid=$uid)";
        $result = ldap_search($ldap_con, $base_dn, $filter);
        $entries = ldap_get_entries($ldap_con, $result);

        if ($entries['count'] > 0) {
            $_SESSION['error'] = "Erreur : Un utilisateur avec l'UID $uid existe déjà.";
            header('Location: addEntry.php');
            exit();
        } else {
            $entry = [];
            $entry["cn"] = $cn;
            $entry["sn"] = $sn;
            $entry["givenName"] = $givenName;
            $entry["mail"] = $email;
            $entry["mobile"] = $mobile;
            $entry["uid"] = $uid;
            $entry["userPassword"] = $ssha_password;  // Utiliser le mot de passe haché en SSHA
            $entry["objectClass"] = ["top", "person", "inetOrgPerson"];
            $dn = "uid=$uid,$base_dn";

            if (ldap_add($ldap_con, $dn, $entry)) {
                // Ajouter l'utilisateur dans le groupe sélectionné
                if ($selectedGroup) {
                    $group_dn = "cn=$selectedGroup,$base_dn_groups";
                    $group_entry = [];
                    $group_entry["memberUid"] = $uid;
                    if (ldap_mod_add($ldap_con, $group_dn, $group_entry)) {
                        $_SESSION['success'] = "L'utilisateur $uid a été ajouté au groupe $selectedGroup";
                    } else {
                        $_SESSION['error'] = "Erreur lors de l'ajout de l'utilisateur au groupe : " . ldap_error($ldap_con);
                    }
                }

                // Si l'utilisateur n'est pas dans les groupes "Administratif" ou "Profs", l'ajouter aussi au groupe "Etudiants"
                if ($addToEtudiants) {
                    $group_dn_etudiants = "cn=Etudiants,$base_dn_groups";
                    $group_entry_etudiants = [];
                    $group_entry_etudiants["memberUid"] = $uid;
                    if (ldap_mod_add($ldap_con, $group_dn_etudiants, $group_entry_etudiants)) {
                        $_SESSION['success'] .= " et également au groupe Etudiants.";
                    } else {
                        $_SESSION['error'] = "Erreur lors de l'ajout de l'utilisateur au groupe Etudiants : " . ldap_error($ldap_con);
                    }
                }

                header('Location: addEntry.php');
                exit();
            }
        }
    } else {
        $_SESSION['error'] = "Échec de l'authentification au serveur LDAP : " . ldap_error($ldap_con);
        header('Location: addEntry.php');
        exit();
    }
}

ldap_close($ldap_con);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ajouter une entrée</title>

    <!-- CSS files -->
    <link rel="stylesheet" type="text/css" href="./css/pageAddEntry.css" media="screen" />
    <link rel="stylesheet" type="text/css" href="./css/02_fonts.css" media="screen" />
    <link rel="stylesheet" type="text/css" href="./css/03_icons.css" media="screen" />

    <!-- JS files -->
    <script type="text/javascript" src="./js/jquery-3.7.0.min.js"></script>
    <script type="text/javascript" src="./js/jquery-ui.min.js"></script>
    <!-- <script type="text/javascript" src="./js/web.js"></script> -->

    <!-- UTF8 encoding -->
    <meta charset="UTF-8">

    <!-- Icon -->
    <link rel="icon" type="image/png" href="./design/image.png" />
</head>
<body>

        <nav class="navbar">
            <div class="navbar-content">
                <h1>LDAP Server</h1>
            </div>
        </nav>
            <a href="controlPanel.php" class='backToPanel'>Retour</a>


        <div class="container">
        <h2>Ajouter un Utilisateur</h2>

        <!-- Affichage des messages d'erreur ou de succès -->
        <?php
        if (isset($_SESSION['error'])) {
            echo "<p class='message error'>" . $_SESSION['error'] . "</p>";
            unset($_SESSION['error']); // Supprimer après affichage
        }
        if (isset($_SESSION['success'])) {
            echo "<p class='message success'>" . $_SESSION['success'] . "</p>";
            unset($_SESSION['success']); // Supprimer après affichage
        }
        ?>

            <div class="form-box">
        <!-- Formulaire -->
                <form method="post" action="addEntry.php">
                    <label for="givenName">Prénom :</label>
                    <input type="text" id="givenName" name="givenName" class='input-style' placeholder='Prénom' required><br>

                    <label for="sn">Nom :</label>
                    <input type="text" id="sn" name="sn" class='input-style' placeholder='Nom' required><br>

                    <label for="mobile">Numéro de Téléphone :</label>
                    <input type="text" id="mobile" name="mobile" pattern="^\d{10}$" class='input-style' placeholder='10 chiffres' required><br>

                    <label for="password">Mot de Passe :</label>
                    <input type="password" id="password" name="password" pattern=".{10,}" class='input-style' placeholder='Minimum 10 caractères' required><br>

                    <label for="group">Groupe :</label>
                <select id="group" name="group" class="select-style" required>
                        <option value="">Sélectionner un groupe</option>
            <?php
                for ($i = 0; $i < $group_entries['count']; $i++) {
                    $group_name = htmlspecialchars($group_entries[$i]['cn'][0]);
                            
                    // Exclure le groupe "Etudiants" de la liste déroulante
                    if ($group_name !== "Etudiants") {
                                    echo "<option value=\"$group_name\">$group_name</option>";
                    }
                }
            ?>
                </select><br>
                    
                    <input type="submit" class='submit-button' value="Ajouter">
                </form>
            </div>
    </div>
</body>
</html>

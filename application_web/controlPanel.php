<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['username'])) {
    header('Location: index.php');
    exit();
}

// Récupérer le pseudonyme et le rôle de la session
$username = $_SESSION['username'];
$role = $_SESSION['role'] ?? 'anonymous'; // Par défaut : anonyme

// Connexion LDAP
$ldap_con = ldap_connect("ldap://<IP Serveur LDAP>");

if (!$ldap_con) {
    $_SESSION['error'] = "Impossible de se connecter au serveur LDAP.";
    header('Location: index.php');
    exit();
}

ldap_set_option($ldap_con, LDAP_OPT_PROTOCOL_VERSION, 3);

// Vérifier si un filtre de recherche a été soumis
$search_filter = isset($_POST['search']) ? htmlspecialchars($_POST['search']) : '';
$base_dn_people = "ou=People,dc=iut6-kourou,dc=fr";
$base_dn_groups = "ou=Groups,dc=iut6-kourou,dc=fr";

// Initialiser la variable pour stocker les résultats
$entries = [];

// Si un filtre est fourni, on cherche d'abord dans "People"
if ($search_filter) {
    // Filtre pour rechercher dans People
    $people_filter = "(&(objectClass=person)(|(cn=$search_filter*)(givenName=$search_filter*)(sn=$search_filter*)(uid=$search_filter*)))";
    
    // Recherche dans "People"
    $result = ldap_search($ldap_con, $base_dn_people, $people_filter);
    $entries = ldap_get_entries($ldap_con, $result);

    // Si aucun résultat dans "People", chercher dans "Groups"
    if ($entries['count'] == 0) {
        // Filtre pour rechercher dans Groups
        $group_filter = "(cn=$search_filter*)";
        
        // Recherche dans "Groups"
        $result = ldap_search($ldap_con, $base_dn_groups, $group_filter);
        $entries = ldap_get_entries($ldap_con, $result);
    }
} else {
    // Si aucun filtre n'est donné, chercher tous les utilisateurs dans People
    $people_filter = "(objectClass=person)";
    $result = ldap_search($ldap_con, $base_dn_people, $people_filter);
    $entries = ldap_get_entries($ldap_con, $result);
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <!-- CSS files -->
    <link rel='stylesheet' type='text/css' href='./css/pageControlPanel.css' media='screen' />
    <link rel='stylesheet' type='text/css' href='./css/02_fonts.css' media='screen' />
    <link rel='stylesheet' type='text/css' href='./css/03_icons.css' media='screen' />

    <!-- JS files -->
    <script type='text/javascript' src='./js/jquery-3.7.0.min.js'></script>
    <script type='text/javascript' src='./js/jquery-ui.min.js'></script>
    <script type='text/javascript' src='./js/controlPanel.js'></script>
    
    <!-- UTF8 encoding -->
    <meta charset='UTF-8'>

    <!-- Icon -->
    <link rel='icon' type='image/png' href='./design/image.png' />

    <!-- Title -->
    <title>Panneau de contrôle</title>
</head>

<body>

<nav class="navbar">
    <div class="welcome-message">
        Bienvenue <?php echo htmlspecialchars($username); ?>
    </div>
    
    <!-- Vérifie si l'utilisateur est un admin avant d'afficher la recherche et le bouton "Ajouter un utilisateur" -->
    <?php if ($role == 'admin'): ?>
    <div class="navbar-center">
        <form method="POST" class="search-form" action="controlPanel.php">
            <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search_filter); ?>" placeholder="Rechercher...">
            <button type="submit">Rechercher</button>
        </form>
    </div>

    <div>
        <form method="POST" class="add-user-form" action="addEntry.php">
            <button type="submit" name="add_user">Ajouter un utilisateur</button>
        </form>
    </div>
    <?php endif; ?>

    <!-- Lien de déconnexion accessible à tous -->
    <div>
        <a href="logout.php" class="logout-link">Se déconnecter</a>
    </div>
</nav>

<?php
    if (isset($_SESSION['error'])) {
        echo "<p class='message error'>" . $_SESSION['error'] . "</p>";
        unset($_SESSION['error']);
    }

    if (isset($_SESSION['success'])) {
        echo "<p class='message success'>" . $_SESSION['success'] . "</p>";
        unset($_SESSION['success']);
    }
?>

<h3>Liste des résultats :</h3>

<?php
    if ($entries['count'] == 0) {
        echo "<p class='message noFound'>Aucun résultat trouvé.</p>";
    } else {
        echo "<table>
                <tr>
                    <th>Nom</th>
                    <th>UID</th>
                    <th>Mobile</th>
                    <th>Mail</th>";

        // Afficher la colonne Groupes uniquement si l'utilisateur est un admin
        if ($role == 'admin') {
            echo "<th>Groupes</th>";
            echo "<th>Actions</th>";
        }

        echo "</tr>";

        // Parcourir les résultats
        for ($i = 0; $i < $entries['count']; $i++) {
            // Si l'entrée correspond à un groupe
            if (isset($entries[$i]['cn']) && isset($entries[$i]['memberuid'])) {
                // Affichage des groupes et de leurs membres
                $cn = htmlspecialchars($entries[$i]['cn'][0] ?? 'N/A');
                
                // Affichage des membres du groupe
                if (isset($entries[$i]['memberuid'])) {
                    $member_uids = $entries[$i]['memberuid'];
                    foreach ($member_uids as $uid) {
                        // Recherche d'utilisateur par uid dans People
                        $user_filter = "(uid=$uid)";
                        $user_result = ldap_search($ldap_con, $base_dn_people, $user_filter);
                        $user_entries = ldap_get_entries($ldap_con, $user_result);

                        if ($user_entries['count'] > 0) {
                            $user = $user_entries[0];
                            $user_cn = htmlspecialchars($user['cn'][0] ?? 'N/A');
                            $user_uid = htmlspecialchars($user['uid'][0] ?? 'N/A');
                            $user_mobile = htmlspecialchars($user['mobile'][0] ?? 'N/A');
                            $user_mail = htmlspecialchars($user['mail'][0] ?? 'N/A');

                            echo "<tr>
                                    <td>$user_cn</td>
                                    <td>$user_uid</td>
                                    <td>$user_mobile</td>
                                    <td>$user_mail</td>";

                            if ($role == 'admin') {
                                echo "<td>$cn</td>
                                      <td>
                                          <div class='delete-user-form'>
                                              <form method='POST' action='deleteUser.php' style='display: inline-block;'>
                                                  <input type='hidden' name='uid' value='$user_uid' />
                                                  <button type='submit'>Supprimer</button>
                                              </form>
                                          </div>
                                          <button class='editButton' data-uid='$user_uid'>Modifier</button>
                                          <form class='editForm' method='POST' action='editPhoneNumber.php' style='display: none; margin-top: 10px;' data-uid='$user_uid'>
                                              <input type='hidden' name='uid' value='$user_uid' />
                                              <label for='mobile'>Nouveau numéro de téléphone :</label>
                                              <input type='text' name='mobile' required pattern='[0-9]{10}' placeholder='10 chiffres' />
                                              <button type='submit'>Modifier</button>
                                          </form>
                                      </td>";
                            }

                            echo "</tr>";
                        }
                    }
                }
            } else {
                // Si l'entrée est un utilisateur (pas un groupe)
                $cn = htmlspecialchars($entries[$i]['cn'][0] ?? 'N/A');
                $uid = htmlspecialchars($entries[$i]['uid'][0] ?? 'N/A');
                $mobile = htmlspecialchars($entries[$i]['mobile'][0] ?? 'N/A');
                $mail = htmlspecialchars($entries[$i]['mail'][0] ?? 'N/A');

                echo "<tr>
                        <td>$cn</td>
                        <td>$uid</td>
                        <td>$mobile</td>
                        <td>$mail</td>";

                if ($role == 'admin') {
                    // Chercher les groupes de cet utilisateur
                    $group_filter = "(memberUid=$uid)";
                    $group_result = ldap_search($ldap_con, $base_dn_groups, $group_filter, ["cn"]);
                    $group_entries = ldap_get_entries($ldap_con, $group_result);

                    $group_names = [];
                    for ($j = 0; $j < $group_entries['count']; $j++) {
                        $group_names[] = htmlspecialchars($group_entries[$j]['cn'][0] ?? '');
                    }
                    echo "<td>" . implode(", ", $group_names) . "</td>";
                }

                if ($role == 'admin') {
                    echo "<td>
                            <div class='delete-user-form'>
                                <form method='POST' action='deleteUser.php' style='display: inline-block;'>
                                    <input type='hidden' name='uid' value='$uid' />
                                    <button type='submit'>Supprimer</button>
                                </form>
                            </div>
                            <button class='editButton' data-uid='$uid'>Modifier</button>
                            <form class='editForm' method='POST' action='editPhoneNumber.php' style='display: none; margin-top: 10px;' data-uid='$uid'>
                                <input type='hidden' name='uid' value='$uid' />
                                <label for='mobile'>Nouveau numéro de téléphone :</label>
                                <input type='text' name='mobile' required pattern='[0-9]{10}' placeholder='10 chiffres' />
                                <button type='submit'>Modifier</button>
                            </form>
                          </td>";
                }

                echo "</tr>";
            }
        }

        echo "</table>";
    }

    ldap_close($ldap_con);
?>

</body>
</html>

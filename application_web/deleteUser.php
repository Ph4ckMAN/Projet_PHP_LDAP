<?php
session_start();

$ldap_con = ldap_connect("ldap://<IP Serveur LDAP>");
ldap_set_option($ldap_con, LDAP_OPT_PROTOCOL_VERSION, 3);

if (!$ldap_con) {
    $_SESSION['error'] = "Impossible de se connecter au serveur LDAP.";
    header("Location: controlPanel.php");
    exit();
}

if (!isset($_SESSION['admin_dn']) || !isset($_SESSION['admin_password'])) {
    $_SESSION['error'] = "Erreur : Authentification administrateur manquante ou session expirée.";
    header("Location: index.php");
    exit();
}

// Vérification du paramètre 'uid'
$uid = $_POST['uid'] ?? null;

if (!$uid) {
    $_SESSION['error'] = "Erreur : UID manquant pour la suppression.";
    header("Location: controlPanel.php");
    exit();
}

if (!preg_match('/^[a-zA-Z0-9._-]+$/', $uid)) {
    $_SESSION['error'] = "Erreur : UID invalide.";
    header("Location: controlPanel.php");
    exit();
}

// Authentification et suppression
if (ldap_bind($ldap_con, $_SESSION['admin_dn'], $_SESSION['admin_password'])) {
    $base_dn = "ou=People,dc=iut6-kourou,dc=fr";
    $dn = "uid=$uid,$base_dn";

    // Suppression de l'utilisateur des groupes
    $base_dn_groups = "ou=Groups,dc=iut6-kourou,dc=fr";
    $filter_groups = "(memberUid=$uid)";
    $group_result = ldap_search($ldap_con, $base_dn_groups, $filter_groups);
    $group_entries = ldap_get_entries($ldap_con, $group_result);

    // Suppression de l'utilisateur de chaque groupe
    for ($i = 0; $i < $group_entries['count']; $i++) {
        $group_dn = $group_entries[$i]["dn"];
        
        // Retirer l'uid du groupe
        $group_entry = [];
        $group_entry["memberUid"] = $uid;

        if (ldap_mod_del($ldap_con, $group_dn, $group_entry)) {
            $_SESSION['success'] = "L'utilisateur a été supprimé des groupes.";
        } else {
            $_SESSION['error'] = "Erreur lors de la suppression de l'utilisateur du groupe " . $group_entries[$i]["cn"][0] . ": " . ldap_error($ldap_con);
        }
    }

    // Supprimer l'utilisateur de l'annuaire LDAP
    if (ldap_delete($ldap_con, $dn)) {
        $_SESSION['success'] = "L'utilisateur $uid a été supprimé avec succès.";
    } else {
        $_SESSION['error'] = "Erreur lors de la suppression de l'utilisateur $uid. Raison : " . ldap_error($ldap_con);
    }
} else {
    $_SESSION['error'] = "Erreur d'authentification au serveur LDAP.";
}

ldap_close($ldap_con);
header("Location: controlPanel.php");
exit();
?>

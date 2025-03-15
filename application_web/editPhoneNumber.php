<?php
session_start();

// Vérifier si l'UID est passé en paramètre
if (!isset($_POST['uid']) || !isset($_POST['mobile'])) {
    $_SESSION['error'] = "Erreur : UID ou nouveau numéro de téléphone manquant.";
    header("Location: controlPanel.php");
    exit();
}

// Récupérer l'UID et le nouveau numéro de téléphone
$uid = $_POST['uid'];
$new_mobile = $_POST['mobile'];

// Vérification du format du numéro de téléphone
if (!preg_match("/^[0-9]{10}$/", $new_mobile)) {
    $_SESSION['error'] = "Le numéro de téléphone doit être constitué de 10 chiffres.";
    header("Location: editPhoneNumber.php?uid=$uid");
    exit();
}

// Connexion LDAP
$ldap_con = ldap_connect("ldap://<IP Serveur LDAP>");
ldap_set_option($ldap_con, LDAP_OPT_PROTOCOL_VERSION, 3);

if (!$ldap_con) {
    $_SESSION['error'] = "Impossible de se connecter au serveur LDAP.";
    header("Location: controlPanel.php");
    exit();
}

// Vérification de l'authentification de l'administrateur
if (!isset($_SESSION['admin_dn']) || !isset($_SESSION['admin_password'])) {
    $_SESSION['error'] = "Erreur : Authentification administrateur manquante ou session expirée.";
    header("Location: index.php");
    exit();
}

// Authentifier l'admin sur LDAP
if (ldap_bind($ldap_con, $_SESSION['admin_dn'], $_SESSION['admin_password'])) {
    // Base DN pour les utilisateurs
    $base_dn = "ou=People,dc=iut6-kourou,dc=fr";
    $dn = "uid=$uid,$base_dn";

    // Mettre à jour le numéro de téléphone
    $entry = [];
    $entry["mobile"] = $new_mobile;

    // Mise à jour dans LDAP
    if (ldap_mod_replace($ldap_con, $dn, $entry)) {
        $_SESSION['success'] = "Le numéro de téléphone de l'utilisateur $uid a été mis à jour avec succès.";
    } else {
        $_SESSION['error'] = "Erreur lors de la mise à jour du numéro de téléphone : " . ldap_error($ldap_con);
    }
} else {
    $_SESSION['error'] = "Erreur d'authentification au serveur LDAP.";
}

ldap_close($ldap_con);

// Rediriger vers le panneau de contrôle
header("Location: controlPanel.php");
exit();
?>

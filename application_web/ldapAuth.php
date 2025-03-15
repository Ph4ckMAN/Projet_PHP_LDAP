<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérifier si l'utilisateur a choisi la connexion anonyme
    if (isset($_POST['anonymous']) && $_POST['anonymous'] === '1') {
        $ldap_con = ldap_connect("ldap://<IP Serveur LDAP>");

        if (!$ldap_con) {
            $_SESSION['error'] = "Impossible de se connecter au serveur LDAP.";
            header("Location: index.php");
            exit();
        }

        // Protocole LDAP
        ldap_set_option($ldap_con, LDAP_OPT_PROTOCOL_VERSION, 3);

        // Liaison anonyme
        if (ldap_bind($ldap_con)) {
            $_SESSION['username'] = "anonymous";
            $_SESSION['role'] = "anonymous"; // Attribuer un rôle explicite 

            // Rediriger vers le panneau de contrôle 
            header('Location: controlPanel.php');
            exit();
        } else {
            $_SESSION['error'] = "Connexion anonyme impossible.";
            header("Location: index.php");
            exit();
        }
    }

    // Connexion nom d'utilisateur + mot de passe
    if (isset($_POST['username']) && isset($_POST['password'])) {
        $username = $_POST['username'];
        $password = $_POST['password'];

        $ldap_con = ldap_connect("ldap://<IP Serveur LDAP>");

        if (!$ldap_con) {
            $_SESSION['error'] = "Impossible de se connecter au serveur LDAP.";
            header("Location: index.php");
            exit();
        }

        
        ldap_set_option($ldap_con, LDAP_OPT_PROTOCOL_VERSION, 3);

        // Vérifier si c'est l'administrateur
        if ($username === "admin") {
            $ldap_dn = "cn=admin,dc=iut6-kourou,dc=fr";
        } else {
            $ldap_dn = "uid=" . $username . ",ou=People,dc=iut6-kourou,dc=fr";
        }

        // Tenter la liaison avec le serveur LDAP
        if (@ldap_bind($ldap_con, $ldap_dn, $password)) {
            $_SESSION['username'] = $username;
            $_SESSION['role'] = ($username === "admin") ? "admin" : "user";

            // Si l'utilisateur est administrateur, stocker ses informations dans la session
            if ($username === "admin") {
                $_SESSION['admin_dn'] = $ldap_dn;        // Stocker le DN de l'administrateur
                $_SESSION['admin_password'] = $password; // Stocker le mot de passe de l'administrateur

                header('Location: controlPanel.php');
                exit();
            }

            // Si ce n'est pas l'administrateur, rediriger vers la page utilisateur
            if ($username !== "admin") {
                $_SESSION['role'] = "user";
                $_SESSION['user_password'] = $password;  // Rôle utilisateur
                // Rediriger vers la page des informations utilisateur
                header('Location: userInfo.php');
                exit();
            }
        } else {
            $_SESSION['error'] = "Nom d'utilisateur ou mot de passe incorrect.";
            header("Location: index.php");
            exit();
        }
    }
}

header("Location: index.php");
exit();
?>

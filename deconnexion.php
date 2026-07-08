<?php
session_start();
// Destructuration complète de la session
$_SESSION = array();
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

// Redirection immédiate vers la page de connexion
header("Location: connexion.php");
exit;
<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
    $pwd = $_POST['password'];

    try {
        $stmt = $db->prepare("SELECT * FROM agent WHERE email_agent = :email");
        $stmt->execute([':email' => $email]);
        $agent = $stmt->fetch();

        if ($agent && password_verify($pwd, $agent['pwd_agent'])) {
            
            // ANALYSE DU STATUT REÇU
            if ((int)$agent['statut_banni'] === 2) {
                die("Accès refusé. Votre compte a été créé avec succès mais est toujours en attente d'approbation par l'administrateur.");
            }
            
            if ((int)$agent['statut_banni'] === 1) {
                die("Accès refusé. Ce compte a été banni ou suspendu par la direction.");
            }

            // Si statut_banni == 0, l'accès au Dashboard est validé
            $_SESSION['id_agent'] = $agent['id_agent'];
            $_SESSION['nom_agent'] = $agent['nom_agent'];
            $_SESSION['id_dept'] = $agent['id_dept'];
            
            echo "Connexion réussie ! Redirection vers votre espace...";
        } else {
            echo "Identifiants de connexion incorrectes.";
        }
    } catch (PDOException $e) {
        die("Erreur d'authentification : " . $e->getMessage());
    }
}
?>
<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = htmlspecialchars(trim($_POST['nom_agent']), ENT_QUOTES, 'UTF-8');
    $email = filter_var(trim($_POST['email_agent']), FILTER_VALIDATE_EMAIL);
    $pwd = password_hash($_POST['pwd_agent'], PASSWORD_BCRYPT);
    $id_dept = (int)$_POST['id_dept'];

    if (!$email || empty($nom)) {
        die("Données de formulaire invalides.");
    }

    try {
        // Sécurité maximale : 'statut_banni' est forcé à 2 (État : En attente d'approbation)
        $stmt = $db->prepare("
            INSERT INTO agent (nom_agent, email_agent, pwd_agent, sal_base_agent, statut_banni, id_dept)
            VALUES (:nom, :email, :pwd, 0.00, 2, :id_dept)
        ");
        
        $stmt->execute([
            ':nom'     => $nom,
            ':email'   => $email,
            ':pwd'     => $pwd,
            ':id_dept' => $id_dept
        ]);

        echo "Votre inscription a bien été reçue. Elle est actuellement en attente de validation par l'administration.";
    } catch (PDOException $e) {
        die("Erreur lors de l'inscription : " . $e->getMessage());
    }
}
?>
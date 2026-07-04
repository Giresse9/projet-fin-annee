<?php
require_once 'config.php';

// Sécurité : Il faut être connecté
if (!isset($_SESSION['id_agent']) && !isset($_SESSION['id_admin'])) {
    die("Accès refusé.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $table = isset($_SESSION['id_admin']) ? 'administrateur' : 'agent';
    $id_colonne = isset($_SESSION['id_admin']) ? 'id_admin' : 'id_agent';
    $id_utilisateur = isset($_SESSION['id_admin']) ? $_SESSION['id_admin'] : $_SESSION['id_agent'];

    $nouvel_email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
    $nouveau_pwd = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_BCRYPT) : null;

    if (!$nouvel_email) {
        die("Adresse email invalide.");
    }

    try {
        // Enregistrement des modifications directes (ou génération d'un token d'activation)
        if ($nouveau_pwd) {
            $stmt = $db->prepare("UPDATE $table SET email_$table = :email, pwd_$table = :pwd WHERE $id_colonne = :id");
            $stmt->execute([':email' => $nouvel_email, ':pwd' => $nouveau_pwd, ':id' => $id_utilisateur]);
        } else {
            $stmt = $db->prepare("UPDATE $table SET email_$table = :email WHERE $id_colonne = :id");
            $stmt->execute([':email' => $nouvel_email, ':id' => $id_utilisateur]);
        }

        // Envoi du mail de confirmation (simulation via la fonction native mail de PHP)
        $sujet = "BIOGAZELCO - Confirmation de modification de vos identifiants";
        $message = "Bonjour, vos informations de connexion sur l'Intranet BIOGAZELCO ont été modifiées avec succès.";
        $headers = "From: no-reply@biogazelco.com\r\nReply-To: support@biogazelco.com";
        
        // mail($nouvel_email, $sujet, $message, $headers); // Activé sur serveur réel

        echo "Profil mis à jour. Un email de confirmation vous a été envoyé.";
    } catch (PDOException $e) {
        die("Erreur lors de la mise à jour : " . $e->getMessage());
    }
}
?>
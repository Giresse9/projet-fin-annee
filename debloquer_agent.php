<?php
require_once 'config.php';

// Sécurité stricte : Seul l'administrateur peut exécuter ce script
if (!isset($_SESSION['id_admin'])) {
    die("Accès refusé. Réservé à l'administrateur.");
}

if (isset($_GET['id_agent'])) {
    $id_agent = (int)$_GET['id_agent'];
    
    // Génération d'un mot de passe temporaire par défaut pour l'agent en détresse
    $pwd_provisoire_brut = "Biogaz2026!"; 
    $pwd_provisoire_hache = password_hash($pwd_provisoire_brut, PASSWORD_BCRYPT);

    try {
        // Le script passe 'statut_banni' à 0 et lui attribue un mot de passe temporaire
        $stmt = $db->prepare("
            UPDATE agent 
            SET statut_banni = 0, pwd_agent = :pwd 
            WHERE id_agent = :id
        ");
        $stmt->execute([
            ':pwd' => $pwd_provisoire_hache,
            ':id'  => $id_agent
        ]);

        echo "Le compte de l'agent a été débloqué avec succès ! Son mot de passe provisoire est : <strong>$pwd_provisoire_brut</strong> (Il devra le modifier dès sa première connexion).";
    } catch (PDOException $e) {
        die("Erreur lors du déblocage : " . $e->getMessage());
    }
}
?>
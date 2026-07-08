<?php
require_once 'config.php';

// Sécurité : Seul l'administrateur peut valider ou débloquer un agent
if (!isset($_SESSION['id_admin'])) {
    die("Accès refusé. Autorisation administrative requise.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_agent'])) {
    $id_agent = (int)$_POST['id_agent'];
    $action = isset($_POST['action']) ? $_POST['action'] : 'approuver';

    try {
        // Détermination du statut_banni à appliquer
        // 0 = Actif / Approuvé, 1 = Banni / Bloqué
        $nouveau_statut = ($action === 'bloquer') ? 1 : 0;

        $stmt = $db->prepare("UPDATE agent SET statut_banni = :statut WHERE id_agent = :id");
        $stmt->execute([
            ':statut' => $nouveau_statut,
            ':id'     => $id_agent
        ]);

        // Redirection vers le cockpit admin après modification
        header("Location: dashboard_admin.php");
        exit;

    } catch (PDOException $e) {
        die("Erreur lors de la modification du statut de l'agent : " . $e->getMessage());
    }
} else {
    header("Location: dashboard_admin.php");
    exit;
}
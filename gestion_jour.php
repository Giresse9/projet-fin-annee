<?php
require_once 'config.php';

// Sécurité : On vérifie que c'est bien l'administrateur qui fait l'action
// (Supposons que $_SESSION['role'] est défini lors de la connexion)
if (!isset($_SESSION['id_admin'])) {
    die("Accès refusé. Vous devez être connecté en tant qu'administrateur.");
}

// Récupération de la date (soit du jour même, soit choisie via un calendrier)
$date_action = isset($_POST['date_jour']) ? $_POST['date_jour'] : date('Y-m-d');

// Si la case "est_ouvrable" est cochée dans le formulaire, vaut 1, sinon 0
$est_ouvrable = isset($_POST['est_ouvrable']) ? 1 : 0;

try {
    // DÉBUT DE LA TRANSACTION : Tout réussit ou tout s'annule
    $db->beginTransaction();

    // 1. On insère ou on met à jour le statut de la journée dans la table 'journee'
    $stmtJour = $db->prepare("
        INSERT INTO journee (date_jour, est_ouvrable) 
        VALUES (:date_jour, :est_ouvrable)
        ON DUPLICATE KEY UPDATE est_ouvrable = :est_ouvrable
    ");
    $stmtJour->execute([
        ':date_jour'    => $date_action,
        ':est_ouvrable' => $est_ouvrable
    ]);

    // 2. LOGIQUE MÉTIER : Si le jour devient NON OUVRABLE, on purge les présences associées
    if ($est_ouvrable === 0) {
        $stmtPurge = $db->prepare("DELETE FROM presence WHERE date_jour = :date_jour");
        $stmtPurge->execute([':date_jour' => $date_action]);
        $message_log = "Journée définie comme NON OUVRABLE. Toutes les présences de ce jour ont été purgées.";
    } else {
        $message_log = "Journée définie comme OUVRABLE. Prête pour le pointage des agents.";
    }

    // Validation définitive de la transaction en BDD
    $db->commit();
    
    echo json_encode(["status" => "success", "message" => $message_log]);

} catch (PDOException $e) {
    // En cas de bug, on annule tout (Rollback) pour éviter les incohérences de données
    $db->rollBack();
    echo json_encode(["status" => "error", "message" => "Échec de l'opération : " . $e->getMessage()]);
}
?>
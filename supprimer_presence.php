<?php
session_start();

// Sécurité : Seul l'administrateur peut exécuter ce script
if (!isset($_SESSION['id_admin'])) {
    header("Location: connexion.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_presence'])) {
    $id_presence = intval($_POST['id_presence']);
    $mois_retour = htmlspecialchars($_POST['mois_retour']);

    // Connexion DB
    try {
        $db = new PDO('mysql:host=localhost;dbname=biogaz;charset=utf8', 'root', '');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (Exception $e) {
        die('Erreur : ' . $e->getMessage());
    }

    // Requête de suppression physique du pointage de la table `presence`
    $sql = "DELETE FROM presence WHERE id_presence = :id_presence";
    $stmt = $db->prepare($sql);
    $success = $stmt->execute([':id_presence' => $id_presence]);

    if ($success) {
        // Redirection vers la page de gestion avec conservation du filtre de mois
        header("Location: gestion_presences.php?mois=" . $mois_retour . "&success=1");
        exit();
    }
} else {
    // Si tentative d'accès direct sans POST
    header("Location: gestion_presences.php");
    exit();
}
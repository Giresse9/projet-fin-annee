<?php
// Initialisation de la session sécurisée pour l'Intranet
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Paramètres de connexion à la base de données "biogaz"
$host = 'localhost';
$dbname = 'biogaz';
$username = 'root';
$password = '';

try {
    // Connexion via l'API PDO avec activation des erreurs sous forme d'exceptions
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // En production, ne jamais afficher le message brut (sécurité), mais parfait pour le projet académique
    die("Erreur critique de connexion à la base de données : " . $e->getMessage());
}
?>
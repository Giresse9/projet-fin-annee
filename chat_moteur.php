<?php
session_start();
// Connexion à ta base de données (A adapter selon ton fichier de config)
try {
    $db = new PDO('mysql:host=localhost;dbname=biogaz;charset=utf8', 'root', '');
} catch(Exception $e) {
    die('Erreur : '.$e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contenu = trim($_POST['contenu_message'] ?? '');
    $type_dest = $_POST['type_destinataire'] ?? 'tous';
    $cible_dept = !empty($_POST['cible_departement']) ? $_POST['cible_departement'] : null;
    $cible_agent = !empty($_POST['cible_agent']) ? intval($_POST['cible_agent']) : null;
    
    // Remplacer par l'ID réel de l'admin connecté en session
    $id_admin = $_SESSION['id_admin'] ?? 1; 

    if (!empty($contenu)) {
        $stmt = $db->prepare("
            INSERT INTO message (texte_msg, id_admin, type_destinataire, cible_departement, cible_agent) 
            VALUES (:texte, :admin, :type_dest, :cible_dept, :cible_agent)
        ");
        
        $stmt->execute([
            ':texte'        => $contenu,
            ':admin'        => $id_admin,
            ':type_dest'    => $type_dest,
            ':cible_dept'   => $cible_dept,
            ':cible_agent'  => $cible_agent
        ]);

        // Redirection vers le dashboard admin avec un succès
        header('Location: dashboard_admin.php?success=1');
        exit();
    }
}
header('Location: dashboard_admin.php');
exit();
?>
<?php
require_once 'config.php';

// Sécurité : On s'assure qu'un utilisateur est authentifié pour accéder au chat
if (!isset($_SESSION['id_agent']) && !isset($_SESSION['id_admin'])) {
    die(json_encode(["status" => "error", "message" => "Accès refusé. Authentification requise."]));
}

// Définition de l'identité de l'expéditeur courant
$id_expediteur = isset($_SESSION['id_admin']) ? $_SESSION['id_admin'] : $_SESSION['id_agent'];

// =================================================================
// ACTION 1 : TRAITEMENT DE L'ENVOI D'UN MESSAGE
// =================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'envoyer') {
    
    // Nettoyage et sécurisation des entrées contre les injections de scripts (Failles XSS)
    $contenu_msg = htmlspecialchars(trim($_POST['contenu_msg']), ENT_QUOTES, 'UTF-8');
    
    // Forcer la portée en minuscules pour s'aligner sur la BDD ('tous', 'departement', 'solo')
    $portee_msg  = strtolower(trim($_POST['portee_msg'])); 
    
    // Initialisation des variables de routage optionnelles
    $id_dept_destinataire = (!empty($_POST['id_dept_destinataire']) && $portee_msg === 'departement') ? (int)$_POST['id_dept_destinataire'] : null;
    $id_agent_destinataire = (!empty($_POST['id_agent_destinataire']) && $portee_msg === 'solo') ? (int)$_POST['id_agent_destinataire'] : null;

    if (empty($contenu_msg)) {
        die(json_encode(["status" => "error", "message" => "Le contenu du message ne peut pas être vide."]));
    }

    try {
        $stmtInsert = $db->prepare("
            INSERT INTO message (contenu_msg, date_envoi_msg, portee_msg, id_agent_expediteur, id_dept_destinataire, id_agent_destinataire)
            VALUES (:contenu, NOW(), :portee, :expediteur, :dept_dest, :agent_dest)
        ");
        
        $stmtInsert->execute([
            ':contenu'    => $contenu_msg,
            ':portee'     => $portee_msg,
            ':expediteur' => $id_expediteur,
            ':dept_dest'  => $id_dept_destinataire,
            ':agent_dest' => $id_agent_destinataire
        ]);

        echo json_encode(["status" => "success", "message" => "Message envoyé avec succès."]);
        exit;

    } catch (PDOException $e) {
        die(json_encode(["status" => "error", "message" => "Échec de l'envoi : " . $e->getMessage()]));
    }
}

// =================================================================
// ACTION 2 : RÉCUPÉRATION FLUX DE MESSAGES FILTRÉS
// =================================================================
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // Si c'est l'Administrateur, il a le droit de voir l'historique complet
        if (isset($_SESSION['id_admin'])) {
            $stmtFetch = $db->query("
                SELECT m.*, a.nom_agent AS nom_expediteur 
                FROM message m
                JOIN agent a ON m.id_agent_expediteur = a.id_agent
                ORDER BY m.id_msg ASC
            ");
            $messages = $stmtFetch->fetchAll();
        } 
        // Si c'est un Agent, il ne voit QUE les messages globaux ('tous'), 
        // ceux de son propre département, ou les messages 'solo' qui le concernent (émis ou reçus)
        else {
            $id_dept_agent = (int)$_SESSION['id_dept']; // Récupéré lors de sa connexion
            
            $stmtFetch = $db->prepare("
                SELECT m.*, a.nom_agent AS nom_expediteur 
                FROM message m
                JOIN agent a ON m.id_agent_expediteur = a.id_agent
                WHERE m.portee_msg = 'tous'
                   OR (m.portee_msg = 'departement' AND m.id_dept_destinataire = :id_dept)
                   OR (m.portee_msg = 'solo' AND (m.id_agent_destinataire = :id_current OR m.id_agent_expediteur = :id_current))
                ORDER BY m.id_msg ASC
            ");
            
            $stmtFetch->execute([
                ':id_dept'    => $id_dept_agent,
                ':id_current' => $id_expediteur
            ]);
            $messages = $stmtFetch->fetchAll();
        }

        // Pour que l'interface puisse lire les messages en AJAX
        echo json_encode($messages);
        exit;

    } catch (PDOException $e) {
        die(json_encode(["status" => "error", "message" => "Erreur de récupération : " . $e->getMessage()]));
    }
}
?>
<?php
require_once 'config.php';

$id_agent = isset($_SESSION['id_agent']) ? (int)$_SESSION['id_agent'] : null;
$id_admin = isset($_SESSION['id_admin']) ? (int)$_SESSION['id_admin'] : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // =================================================================
    // CAS 1 : L'AGENT SIGNALER SA PRÉSENCE
    // =================================================================
    if ($action === 'pointer' && $id_agent) {
        try {
            // ÉTAPE A : Vérifier si la journée a été ouverte par l'administration
            $checkJour = $db->prepare("SELECT COUNT(*) FROM journee WHERE date_jour = CURDATE()");
            $checkJour->execute();
            $journee_ouverte = $checkJour->fetchColumn();

            if ($journee_ouverte == 0) {
                // Si la journée n'est pas ouverte, on stoppe proprement pour éviter la violation de clé étrangère
                die("<script>
                    alert('Opération impossible : La direction n\'a pas encore activé la journée de travail pour aujourd\'hui.');
                    window.location.href='dashboard_agent.php';
                </script>");
            }

            // ÉTAPE B : Vérifier si l'agent a déjà pointé
            $checkPres = $db->prepare("SELECT COUNT(*) FROM presence WHERE id_agent = :id AND date_jour = CURDATE()");
            $checkPres->execute([':id' => $id_agent]);
            $deja_pointe = $checkPres->fetchColumn();
            
            if ($deja_pointe == 0) {
                $stmt = $db->prepare("INSERT INTO presence (id_agent, date_jour) VALUES (:id, CURDATE())");
                $stmt->execute([':id' => $id_agent]);
            }
            
            header("Location: dashboard_agent.php");
            exit;
        } catch (PDOException $e) {
            die("Erreur technique de pointage : " . $e->getMessage());
        }
    }

    // =================================================================
    // CAS 2 : L'ADMINISTRATEUR ACTIVE OU PURGE LA JOURNÉE
    // =================================================================
    if ($id_admin) {
        try {
            if ($action === 'ouvrir') {
                $stmt = $db->prepare("INSERT IGNORE INTO journee (date_jour, est_ouvrable) VALUES (CURDATE(), 1)");
                $stmt->execute();
            } elseif ($action === 'purger') {
                $db->query("DELETE FROM presence");
                $db->query("DELETE FROM journee");
            }
            header("Location: dashboard_admin.php");
            exit;
        } catch (PDOException $e) {
            die("Erreur d'exploitation administrative : " . $e->getMessage());
        }
    }
}

echo "Action non autorisée.";
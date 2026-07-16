<?php
session_start();
require_once 'config.php';

// 1. Sécurité : On vérifie que c'est bien un agent connecté
if (!isset($_SESSION['id_agent']) || $_SESSION['role'] !== 'agent') {
    header('Location: connexion.php');
    exit();
}

$id_agent = $_SESSION['id_agent'];
$message_succes = "";
$message_erreur = "";

// 2. Traitement du Pointage (Action POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pointer_presence'])) {
    try {
        // Vérification souple du statut de la journée
        $stmtJour = $db->prepare("SELECT est_ouvrable FROM journee WHERE date_jour = CURDATE() OR est_ouvrable = 1 ORDER BY date_jour DESC LIMIT 1");
        $stmtJour->execute();
        $journee = $stmtJour->fetch(PDO::FETCH_ASSOC);

        if ($journee && $journee['est_ouvrable'] == 1) {
            // On vérifie si l'agent n'a pas déjà pointé aujourd'hui
            $stmtCheckPres = $db->prepare("SELECT COUNT(*) as deja_pointe FROM presence WHERE id_agent = :id_agent AND date_jour = CURDATE()");
            $stmtCheckPres->execute([':id_agent' => $id_agent]);
            $deja_pointe = $stmtCheckPres->fetch(PDO::FETCH_ASSOC)['deja_pointe'] ?? 0;

            if ($deja_pointe == 0) {
                $stmtInsert = $db->prepare("INSERT INTO presence (id_agent, date_jour, est_present) VALUES (:id_agent, CURDATE(), 1)");
                $stmtInsert->execute([':id_agent' => $id_agent]);
                $message_succes = "Votre présence a été signalée avec succès pour aujourd'hui !";
            } else {
                $message_erreur = "Vous avez déjà enregistré votre présence pour aujourd'hui.";
            }
        } else {
            $message_erreur = "Impossible de pointer : l'administrateur n'a pas encore ouvert la journée de travail.";
        }
    } catch (PDOException $e) {
        $message_erreur = "Erreur lors du pointage : " . $e->getMessage();
    }
}

// 3. Récupération des informations de l'agent connecté
try {
    $stmtAgent = $db->prepare("SELECT a.*, d.nom_dept FROM agent a LEFT JOIN departement d ON a.id_dept = d.id_dept WHERE a.id_agent = :id");
    $stmtAgent->execute([':id' => $id_agent]);
    $agent_info = $stmtAgent->fetch(PDO::FETCH_ASSOC);

    // On récupère le statut de la dernière journée enregistrée
    $stmtStatus = $db->prepare("SELECT est_ouvrable FROM journee ORDER BY date_jour DESC LIMIT 1");
    $stmtStatus->execute();
    $statut_du_jour = $stmtStatus->fetch(PDO::FETCH_ASSOC)['est_ouvrable'] ?? 0;

    // Vérification si l'agent a déjà pointé aujourd'hui
    $stmtPointeToday = $db->prepare("SELECT COUNT(*) as checked FROM presence WHERE id_agent = :id AND date_jour = CURDATE()");
    $stmtPointeToday->execute([':id' => $id_agent]);
    $a_deja_pointe_aujourdhui = $stmtPointeToday->fetch(PDO::FETCH_ASSOC)['checked'] ?? 0;

    // Récupération des 5 dernières directives de l'admin
    $directives = $db->prepare("
        SELECT * FROM message 
        WHERE id_agent_destinataire = :id_agent 
        OR id_dept_destinataire = :id_dept 
        OR type_destinataire = 'tous'
        ORDER BY id_msg DESC 
        LIMIT 5
    ");
    $directives->execute([
        ':id_agent' => $id_agent,
        ':id_dept'  => $agent_info['id_dept'] // Assure-toi d'avoir l'id_dept de l'agent en session
    ]);
    $liste_directives = $directives->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur de chargement du profil : " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BIOGAZELCO — Espace Collaborateur</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f3eee7; font-family: 'Plus Jakarta Sans', sans-serif; margin: 0; padding: 25px; color: #0f172a; }
        .dashboard-container { max-width: 1100px; margin: 0 auto; }
        
        /* En-tête */
        .welcome-header { background: #ede7de; border-radius: 24px; padding: 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 10px 30px rgba(0,0,0,0.01); margin-bottom: 25px; }
        .welcome-header h1 { margin: 0; font-size: 24px; font-weight: 700; }
        .welcome-header h1 span { color: #10b981; }
        .btn-logout { background: #ef4444; color: white; border: none; padding: 12px 20px; font-weight: 600; border-radius: 12px; cursor: pointer; text-decoration: none; font-size: 14px; }
        .btn-logout:hover { background: #dc2626; }

        /* Grille des fonctionnalités */
        .grid-layout { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-bottom: 25px; }
        .card { background: #ede7de; padding: 30px; border-radius: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.01); box-sizing: border-box; }
        .card h3 { margin: 0 0 15px 0; font-size: 18px; font-weight: 700; display: flex; align-items: center; gap: 10px; }
        
        /* Boutons d'action */
        .btn-action { width: 100%; padding: 15px; border: none; border-radius: 12px; font-size: 15px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; text-decoration: none; box-sizing: border-box; transition: background 0.2s; }
        .btn-green { background: #52b788; color: white; }
        .btn-green:hover { background: #40916c; }
        .btn-blue { background: #4cc9f0; color: white; }
        .btn-blue:hover { background: #3ab7de; }
        .btn-disabled { background: #b0a89f; color: #f3eee7; cursor: not-allowed; }

        /* Alertes */
        .alert { padding: 12px 15px; border-radius: 10px; font-size: 14px; margin-bottom: 15px; display: flex; align-items: center; gap: 8px; }
        .alert-success { background: #d1fae5; color: #065f46; }
        .alert-danger { background: #fee2e2; color: #991b1b; }

        /* Fil d'actu / Directives */
        .directive-box { background: #e5ded4; padding: 15px; border-radius: 14px; margin-bottom: 12px; font-size: 14px; line-height: 1.5; }
        .directive-meta { font-size: 11px; color: #64748b; margin-top: 5px; display: block; text-align: right; }
    </style>
</head>
<body>

<div class="dashboard-container">

    <div class="welcome-header">
        <div>
            <h1>Bonjour, <span><?php echo htmlspecialchars($agent_info['nom_agent']); ?></span></h1>
            <p style="color: #64748b; margin: 5px 0 0; font-size: 14px;">Identifiant : <strong><?php echo htmlspecialchars($agent_info['code_agent']); ?></strong> | Département : <strong><?php echo htmlspecialchars($agent_info['nom_dept'] ?? 'Non assigné'); ?></strong></p>
        </div>
        <a href="deconnexion.php" class="btn-logout"><i class="fa-solid fa-power-off"></i> Déconnexion</a>
    </div>

    <?php if (!empty($message_succes)): ?>
        <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?php echo $message_succes; ?></div>
    <?php endif; ?>
    <?php if (!empty($message_erreur)): ?>
        <div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i> <?php echo $message_erreur; ?></div>
    <?php endif; ?>

    <div class="grid-layout">
        
        <div class="card">
            <h3><i class="fa-regular fa-clock" style="color: #52b788;"></i> Pointage de Présence</h3>
            <p style="color: #64748b; font-size: 14px; margin-bottom: 25px; line-height: 1.4;">
                Signalez votre présence quotidienne d'un simple clic pour valider vos heures d'activité.
            </p>
            
            <form method="POST" action="dashboard_agent.php">
                <?php if ($statut_du_jour != 1): ?>
                    <!-- Utilisation d'une comparaison souple != 1 -->
                    <button type="button" class="btn-action btn-disabled" disabled>
                        <i class="fa-solid fa-lock"></i> Pointage fermé par la direction
                    </button>
                <?php elseif ($a_deja_pointe_aujourdhui > 0): ?>
                    <button type="button" class="btn-action btn-disabled" style="background: #a7c957; color: white;" disabled>
                        <i class="fa-solid fa-circle-check"></i> Présence enregistrée pour aujourd'hui
                    </button>
                <?php else: ?>
                    <button type="submit" name="pointer_presence" class="btn-action btn-green">
                        <i class="fa-solid fa-fingerprint"></i> Signaler ma présence
                    </button>
                <?php endif; ?>
            </form>
        </div>

        <div class="card">
            <h3><i class="fa-solid fa-wallet" style="color: #4cc9f0;"></i> Rémunération</h3>
            <p style="color: #64748b; font-size: 14px; margin-bottom: 25px; line-height: 1.4;">
                Votre salaire de base est configuré à : <strong><?php echo number_format($agent_info['sal_base_agent'], 2, '.', ' '); ?> USD</strong>.
            </p>
            <a href="fiche_paie.php" class="btn-action btn-blue">
                <i class="fa-solid fa-file-invoice-dollar"></i> Consulter ma fiche de paie
            </a>
        </div>

    </div>

    <div class="card" style="margin-bottom: 25px;">
        <h3><i class="fa-solid fa-bullhorn" style="color: #e63946;"></i> Fil de Discussion Sécurisé (Directives Direction)</h3>
        <p style="color: #64748b; font-size: 14px; margin-bottom: 20px;">Prenez note des dernières directives officielles de la Direction Générale :</p>
        
        <div style="margin-top: 15px;">
            <?php if (empty($liste_directives)): ?>
                <div class="directive-box" style="text-align: center; color: #64748b; font-style: italic; background: #e5ded4; padding: 15px; border-radius: 14px;">
                    Aucune directive ne vous est adressée actuellement.
                </div>
            <?php else: ?>
                <?php foreach ($liste_directives as $dir): ?>
                    <div class="directive-box" style="background: #e5ded4; padding: 15px; border-radius: 14px; margin-bottom: 12px; font-size: 14px; line-height: 1.5;">
                        <strong><i class="fa-solid fa-user-shield"></i> Direction :</strong>
                        <p style="margin: 8px 0 0 0; color: #0f172a; font-weight: 500;"><?php echo htmlspecialchars($dir['contenu_msg']); ?></p>
                        <span class="directive-meta" style="font-size: 11px; color: #64748b; margin-top: 5px; display: block; text-align: right;">Émis le : <?php echo $dir['date_envoi_msg']; ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

</div>

</body>
</html>
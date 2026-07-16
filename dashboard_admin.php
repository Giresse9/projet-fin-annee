<?php
session_start();
require_once 'config.php';

// Sécurité : On vérifie que c'est bien l'admin qui est connecté
if (!isset($_SESSION['id_admin'])) {
    header('Location: connexion.php');
    exit();
}

$message_succes = "";
$message_erreur = "";

// 1. Récupération des départements
$liste_depts = $db->query("SELECT id_dept, nom_dept FROM departement ORDER BY nom_dept ASC")->fetchAll(PDO::FETCH_ASSOC);

// 2. Récupération des agents (on prend id_agent, nom_agent et le code_agent)
// Ajuste 'statut_agent' ou 'statut' si ta colonne s'appelle autrement, ou retire le WHERE pour tester
$liste_agents = $db->query("SELECT id_agent, nom_agent, code_agent FROM agent ORDER BY nom_agent ASC")->fetchAll(PDO::FETCH_ASSOC);

// Gestion de la suppression d'une directive
if (isset($_POST['supprimer_directive'])) {
    $id_msg_a_supprimer = (int)$_POST['id_msg_suppr'];
    try {
        $stmtDelete = $db->prepare("DELETE FROM message WHERE id_msg = :id");
        $stmtDelete->execute([':id' => $id_msg_a_supprimer]);
        $message_succes = "La directive a été supprimée avec succès !";
    } catch (PDOException $e) {
        $message_erreur = "Erreur lors de la suppression : " . $e->getMessage();
    }
}

// --- ACTIONS POST (Activer journée, Bloquer/Débloquer, Envoyer directive) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Gestion de l'action Bloquer / Débloquer un agent
    if (isset($_POST['action_statut'], $_POST['id_agent'])) {
        $id_agent = (int)$_POST['id_agent'];
        $nouveau_statut = ($_POST['action_statut'] === 'Bloquer') ? 'Inactif' : 'Actif';
        try {
            $stmt = $db->prepare("UPDATE agent SET statut_agent = :statut WHERE id_agent = :id");
            $stmt->execute([':statut' => $nouveau_statut, ':id' => $id_agent]);
        } catch (PDOException $e) {
            $message_erreur = "Erreur modification statut : " . $e->getMessage();
        }
    }

    // --- ACTIONS POST (Mises à jour) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. GESTION : Activer / Réactiver la Journée (Passe à '1')
    if (isset($_POST['activer_journee'])) {
        try {
            // On vérifie si la journée existe déjà pour aujourd'hui
            $stmtCheck = $db->prepare("SELECT COUNT(*) as existe FROM journee WHERE date_jour = CURDATE()");
            $stmtCheck->execute();
            $deja_existante = $stmtCheck->fetch(PDO::FETCH_ASSOC)['existe'] ?? 0;

            if ($deja_existante == 0) {
                // Si elle n'existe pas, on la crée avec le statut à '1' (Ouvrable)
                $stmtIns = $db->prepare("INSERT INTO journee (date_jour, est_ouvrable) VALUES (CURDATE(), '1')");
                $stmtIns->execute();
                $message_succes = "La journée a été créée et activée (Ouverte au pointage) !";
            } else {
                // Si elle existe déjà (par exemple après une purge), on la réactive en passant à '1'
                $stmtUp = $db->prepare("UPDATE journee SET est_ouvrable = '1' WHERE date_jour = CURDATE()");
                $stmtUp->execute();
                $message_succes = "La journée d'aujourd'hui a été réactivée (Ouverte au pointage) !";
            }

        } catch (PDOException $e) {
            $message_erreur = "Erreur lors de l'activation : " . $e->getMessage();
        }
    }

    // 2. GESTION : Purger les données (Passe à '0' et supprime les présences)
    if (isset($_POST['purger_donnees'])) {
        try {
            // Étape A : On supprime les présences enregistrées pour aujourd'hui
            $stmtPurgePresence = $db->prepare("DELETE FROM presence WHERE DATE(date_jour) = CURDATE()");
            $stmtPurgePresence->execute();
            
            // Étape B : On fait passer le statut de la journée actuelle à '0' (Non ouvrable / Fermée)
            $stmtCloseJournee = $db->prepare("UPDATE journee SET est_ouvrable = '0' WHERE date_jour = CURDATE()");
            $stmtCloseJournee->execute();
            
            $message_succes = "Purge effectuée ! Les pointages d'aujourd'hui ont été réinitialisés et la journée est désormais fermée (0).";
            
            // Redondance optionnelle : Rafraîchir les compteurs du tableau de bord
            $stmtCount = $db->query("SELECT COUNT(*) as total FROM agent WHERE statut_agent = 'Actif'");
            $total_actifs = $stmtCount->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
            $stmtMasse = $db->query("SELECT SUM(sal_base_agent) as masse FROM agent WHERE statut_agent = 'Actif'");
            $masse_salariale = $stmtMasse->fetch(PDO::FETCH_ASSOC)['masse'] ?? 0;

        } catch (PDOException $e) {
            $message_erreur = "Erreur lors de la purge : " . $e->getMessage();
        }
    }
    
    // (Conserve ici le reste de tes traitements POST : action_statut, envoyer_directive...)
}
    
    
    // Gestion de l'envoi selon la structure de ta table message
    if (isset($_POST['envoyer_directive'])) {
        $texte = htmlspecialchars(trim($_POST['texte_message']), ENT_QUOTES, 'UTF-8');
        
        // On récupère l'id ou le code de l'admin connecté (expéditeur)
        $id_admin_expediteur = $_SESSION['id_admin'] ?? $_SESSION['id_agent'] ?? 1; 
        
        $type_etendue = $_POST['etendue'] ?? 'tous'; // Correspond aux valeurs de ton ENUM

        // Initialisation des destinations par défaut à NULL
        $id_dept_destinataire = null;
        $id_agent_destinataire = null;

        if ($type_etendue === 'departement') {
            $id_dept_destinataire = !empty($_POST['select_dept']) ? (int)$_POST['select_dept'] : null;
        } elseif ($type_etendue === 'individuel') {
            $id_agent_destinataire = !empty($_POST['select_agent']) ? (int)$_POST['select_agent'] : null;
        }

        if (!empty($texte)) {
            try {
                // Requête basée exactement sur tes colonnes de BDD
                $stmt = $db->prepare("
                    INSERT INTO message (contenu_msg, date_envoi_msg, portee_msg, id_agent_expediteur, id_dept_destinataire, id_agent_destinataire, type_destinataire) 
                    VALUES (:texte, NOW(), :portee, :id_expediteur, :id_dept, :id_agent_dest, :type_dest)
                ");
                
                $stmt->execute([
                    ':texte'          => $texte,
                    ':portee'         => substr($type_etendue, 0, 15), // portee_msg varchar(15)
                    ':id_expediteur'  => $id_admin_expediteur,         // id_agent_expediteur int
                    ':id_dept'        => $id_dept_destinataire,        // id_dept_destinataire int
                    ':id_agent_dest'  => $id_agent_destinataire,       // id_agent_destinataire int
                    ':type_dest'      => $type_etendue                 // type_destinataire enum('tous','departement','individuel')
                ]);
                
                $message_succes = "Directive envoyée avec succès selon l'étendue choisie !";
            } catch (PDOException $e) {
                $message_erreur = "Erreur envoi : " . $e->getMessage();
            }
        }
    }
}
// --- 4. RÉCUPÉRATION DES DONNÉES POUR L'AFFICHAGE ---
try {
    // Liste complète des agents pour le tableau principal
    $agents = $db->query("SELECT a.*, d.nom_dept FROM agent a LEFT JOIN departement d ON a.id_dept = d.id_dept ORDER BY a.id_agent DESC")->fetchAll(PDO::FETCH_ASSOC);

    // Statistiques pour les compteurs du haut
    $stmtCount = $db->query("SELECT COUNT(*) as total FROM agent WHERE statut_agent = 'Actif'");
    $total_actifs = $stmtCount->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    $stmtMasse = $db->query("SELECT SUM(sal_base_agent) as masse FROM agent WHERE statut_agent = 'Actif'");
    $masse_salariale = $stmtMasse->fetch(PDO::FETCH_ASSOC)['masse'] ?? 0;

    // Correction de l'historique des messages (évite le bug SQL)
    $historique_messages = $db->query("SELECT m.*, a.nom_admin FROM message m LEFT JOIN administrateur a ON m.id_agent_expediteur = a.id_admin ORDER BY m.id_msg DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Erreur de chargement des données : " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BIOGAZELCO — Cockpit Direction</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f3eee7; font-family: 'Plus Jakarta Sans', sans-serif; margin: 0; padding: 25px; color: #0f172a; }
        
        /* En-tête principal */
        .main-header { background: #ede7de; border-radius: 20px; padding: 25px 35px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 20px rgba(0,0,0,0.01); margin-bottom: 25px; }
        .main-header h1 { margin: 0; font-size: 24px; font-weight: 700; }
        .main-header p { margin: 5px 0 0; font-size: 14px; color: #64748b; }
        .btn-logout { background: #ef4444; color: white; border: none; padding: 12px 24px; font-weight: 600; border-radius: 12px; cursor: pointer; text-decoration: none; font-size: 14px; }
        .btn-logout:hover { background: #dc2626; }

        /* Grille des compteurs (Enrichis) */
        .stats-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px; }
        .stat-card { background: #ede7de; padding: 25px; border-radius: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.01); }
        .stat-card label { font-size: 12px; text-transform: uppercase; color: #64748b; font-weight: 700; letter-spacing: 0.5px; }
        .stat-card h2 { margin: 8px 0 0; font-size: 28px; font-weight: 700; color: #0f172a; }

        /* Organisation en deux colonnes pour le bas */
        .dashboard-layout { display: grid; grid-template-columns: 320px 1fr; gap: 25px; }

        /* Colonne Gauche (Contrôles) */
        .sidebar-controls { display: flex; flex-direction: column; gap: 25px; }
        .control-card { background: #ede7de; padding: 25px; border-radius: 20px; }
        .control-card h3 { margin: 0 0 15px 0; font-size: 16px; font-weight: 700; }
        .control-card p { font-size: 13px; color: #64748b; margin: 0 0 15px; }
        
        .btn-action-green { width: 100%; background: #52b788; color: white; border: none; padding: 12px; font-weight: 600; border-radius: 10px; cursor: pointer; font-size: 14px; margin-bottom: 10px; }
        .btn-action-purple { width: 100%; background: #7a73d1; color: white; border: none; padding: 12px; font-weight: 600; border-radius: 10px; cursor: pointer; font-size: 14px; text-align: center; text-decoration: none; display: block; box-sizing: border-box; }
        .btn-action-grey { width: 100%; background: #b0a89f; color: white; border: none; padding: 12px; font-weight: 600; border-radius: 10px; cursor: pointer; font-size: 14px; }

        /* Colonne Droite (Tableaux et Supervision) */
        .main-content-area { display: flex; flex-direction: column; gap: 25px; }
        
        /* Tableaux */
        .table-card { background: #ede7de; padding: 25px; border-radius: 20px; }
        .table-card h3 { margin: 0 0 20px; font-size: 18px; font-weight: 700; }
        
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { font-size: 13px; color: #64748b; font-weight: 600; padding: 12px; border-bottom: 2px solid #dcd6cb; }
        td { padding: 14px 12px; font-size: 14px; border-bottom: 1px solid #dcd6cb; vertical-align: middle; }
        
        .agent-name { font-weight: 600; color: #0f172a; }
        .agent-email { font-size: 12px; color: #64748b; font-weight: 400; display: block; margin-top: 2px; }
        
        /* Badges de Statut */
        .badge { padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; display: inline-block; }
        .badge-actif { background: #d1fae5; color: #065f46; }
        .badge-bloque { background: #fee2e2; color: #991b1b; }

        /* Boutons de modification à la volée */
        .btn-table-action { border: none; padding: 6px 14px; font-weight: 600; border-radius: 8px; cursor: pointer; font-size: 13px; }
        .btn-block { background: #ef4444; color: white; }
        .btn-unblock { background: #52b788; color: white; }

        /* Zone de Supervision (Messages) */
        .supervision-card { background: #ede7de; padding: 25px; border-radius: 20px; }
        .supervision-card h3 { margin: 0 0 15px; font-size: 16px; font-weight: 700; }
        textarea { width: 100%; height: 80px; padding: 12px; border: none; border-radius: 12px; background: #e5ded4; color: #0f172a; font-family: inherit; font-size: 14px; box-sizing: border-box; resize: none; margin-bottom: 15px; }
        
        .supervision-footer { display: flex; justify-content: space-between; align-items: center; }
        select { padding: 10px 15px; background: #e5ded4; border: none; border-radius: 10px; font-size: 14px; font-weight: 500; color: #0f172a; }
        .btn-send { background: #52b788; color: white; border: none; padding: 10px 20px; font-weight: 600; border-radius: 10px; cursor: pointer; font-size: 14px; }
        
        /* Liste historique directives */
        .directive-list { margin-top: 20px; font-size: 13px; color: #64748b; }
        .directive-item { background: #e5ded4; padding: 10px; border-radius: 8px; margin-bottom: 8px; color: #0f172a; }

        /* Style de base pour le bouton */
.btn-gestion-presence {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px; /* Espace précis de 8px entre l'icône et le texte */
    background-color: #2e7d32; /* Vert forêt professionnel */
    color: #ffffff; /* Texte blanc */
    padding: 10px 20px;
    font-size: 14px;
    font-weight: 600;
    text-decoration: none; /* Enlève le soulignement */
    border-radius: 4px; /* Coins légèrement arrondis */
    border: none;
    transition: background-color 0.3s ease, transform 0.2s ease;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    cursor: pointer;
}

/* Style spécifique pour ajuster la taille de l'icône */
.btn-gestion-presence .btn-icon {
    width: 18px;
    height: 18px;
    display: inline-block;
    vertical-align: middle;
}

/* Effet au survol (Hover) */
.btn-gestion-presence:hover {
    background-color: #1b5e20; /* Vert plus sombre et intense */
    transform: translateY(-1px); /* Léger effet de soulèvement */
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.15);
}

/* Effet au clic (Active) */
.btn-gestion-presence:active {
    transform: translateY(1px);
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}
    </style>
</head>
<body>

    <!-- En-tête principal de pilotage -->
    <div class="main-header">
        <div>
            <h1>Direction Général — BIOGAZELCO</h1>
            <p>Connecté en tant que : <strong><?php echo htmlspecialchars($_SESSION['nom_admin'] ?? 'Administrateur'); ?></strong></p>
        </div>
        <a href="deconnexion.php" class="btn-logout"><i class="fa-solid fa-power-off"></i> Déconnexion</a>
    </div>

    <!-- Section des compteurs de performance du haut -->
    <div class="stats-grid">
        <div class="stat-card">
            <label>Effectifs Gérés</label>
            <h2><?php echo $total_actifs; ?> Agents actifs</h2>
        </div>
        <div class="stat-card">
            <label>Masse Salariale Mensuelle</label>
            <h2><?php echo number_format($masse_salariale, 2, '.', ' '); ?> USD</h2>
        </div>
    </div>

    <!-- Grille Principale inférieure -->
    <div class="dashboard-layout">
        
        <!-- Panneau de Gauche : Actions Instantanées -->
        <div class="sidebar-controls">
            <div class="control-card">
            <h3>Contrôle Opérationnel</h3>
            <p>Activez la journée pour permettre aux équipes de pointer leur présence ou nettoyez le système.</p>
            
            <form method="POST" action="dashboard_admin.php">
                <button type="submit" name="activer_journee" class="btn-action-green">
                    <i class="fa-solid fa-play" style="margin-right: 5px;"></i> Activer la Journée
                </button>
                
                <button type="submit" name="purger_donnees" class="btn-action-grey" style="margin-top: 8px;" onclick="return confirm('Êtes-vous sûr de vouloir purger les données de supervision ? Cette action est irréversible.');">
                    <i class="fa-solid fa-trash-can" style="margin-right: 5px;"></i> Purger
                </button>
            </form>

            <a href="gestion_catalogue.php" class="btn-action-purple" style="margin-top: 15px;">
                <i class="fa-solid fa-box-open"></i> Gérer le Catalogue Produits
            </a>

            <a href="gestion_departement.php" class="btn-action btn-purple" style="display: block; text-align: center; text-decoration: none; background-color: #7209b7; color: white; padding: 10px; border-radius: 8px; font-weight: 600; margin-top: 10px;">
                <i class="fa-solid fa-building"></i> Gérer les Départements
            </a><br>

            <a href="gestion_presences.php" class="btn-gestion-presence">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="btn-icon">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                    <polyline points="9 16 11 18 15 14"></polyline>
                </svg>
                Gérer les Présences
            </a>
        </div>
        </div>

        <!-- Panneau de Droite : Tableaux et Échanges -->
        <div class="main-content-area">
            
            <!-- Tableau de Gestion des Comptes Collaborateurs -->
            <div class="table-card">
                <h3>Gestion des Comptes Collaborateurs</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Nom de l'Agent</th>
                            <th>Code</th>
                            <th>Mot de passe</th>
                            <th>Département</th>
                            <th>Salaire de Base</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($agents)): ?>
                            <tr><td colspan="5" style="text-align: center; color: #64748b;">Aucun agent enregistré pour le moment.</td></tr>
                        <?php else: ?>
                            <?php foreach ($agents as $row): ?>
                                <tr>
                                    <td>
                                        <span class="agent-name"><?php echo htmlspecialchars($row['nom_agent']); ?></span>
                                        <span class="agent-email"><?php echo htmlspecialchars(strtolower(str_replace(' ', '.', $row['nom_agent']))); ?>@biogazelco.com</span>
                                    </td>
                                    <td>
                                        <code style="background: #e5ded4; padding: 3px 6px; border-radius: 4px; font-weight: 600; color: #0f172a;">
                                            <?= htmlspecialchars($row['code_agent']); ?>
                                        </code>
                                    </td>
                                    <td>
                                        <span style="font-family: monospace; background: #fee2e2; color: #991b1b; padding: 2px 6px; border-radius: 4px; font-size: 13px;">
                                            <?= htmlspecialchars($row['mdp_agent']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['nom_dept'] ?? 'Non assigné'); ?></td>
                                    <td><?php echo number_format($row['sal_base_agent'], 2, '.', ' '); ?> USD</td>
                                    <td>
                                        <?php if ($row['statut_agent'] === 'Actif'): ?>
                                            <span class="badge badge-actif">Actif</span>
                                        <?php else: ?>
                                            <span class="badge badge-bloque">Bloqué</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <form method="POST" style="margin:0;">
                                            <input type="hidden" name="id_agent" value="<?php echo $row['id_agent']; ?>">
                                            <?php if ($row['statut_agent'] === 'Actif'): ?>
                                                <button type="submit" name="action_statut" value="Bloquer" class="btn-table-action btn-block">Bloquer</button>
                                            <?php else: ?>
                                                <button type="submit" name="action_statut" value="Débloquer" class="btn-table-action btn-unblock">Débloquer</button>
                                            <?php endif; ?>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="supervision-card">
                <h3>Supervision et Échanges (Directives Administrateur)</h3>
                
                <form method="POST" action="dashboard_admin.php">
                    <textarea name="texte_message" placeholder="Écrire une directive officielle ou une note de service ciblée..." required></textarea>
                    
                    <div style="display: flex; flex-direction: column; gap: 10px; margin-top: 15px;">
                        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                            <span style="font-size: 13px; color: #64748b;">Étendue du message :</span>
                            
                            <!-- Sélecteur Principal aligné sur ton ENUM -->
                            <select name="etendue" id="etendueSelector" onchange="toggleTargetSelects()" style="padding: 6px; border-radius: 6px; background: #fff; border: 1px solid #cbd5e1;">
                                <option value="tous">Tous les agents</option>
                                <option value="departement">Un département spécifique</option>
                                <option value="individuel">Un agent bien précis</option>
                            </select>

                            <!-- Liste des Départements -->
                            <select name="select_dept" id="deptSelector" style="display: none; padding: 6px; border-radius: 6px; background: #fff; border: 1px solid #cbd5e1;">
                                <option value="">-- Choisir un département --</option>
                                <?php foreach ($liste_depts as $dept): ?>
                                    <option value="<?= $dept['id_dept']; ?>"><?= htmlspecialchars($dept['nom_dept']); ?></option>
                                <?php endforeach; ?>
                            </select>

                            <!-- Liste des Agents -->
                            <select name="select_agent" id="agentSelector" style="display: none; padding: 6px; border-radius: 6px; background: #fff; border: 1px solid #cbd5e1;">
                                <option value="">-- Choisir un agent --</option>
                                <?php foreach ($liste_agents as $ag): ?>
                                    <option value="<?= $ag['id_agent']; ?>"><?= htmlspecialchars($ag['nom_agent']); ?> (<?= htmlspecialchars($ag['code_agent']); ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <button type="submit" name="envoyer_directive" class="btn-send" style="align-self: flex-end; padding: 10px 20px; background-color: #52b788; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">Envoyer la directive</button>
                    </div>
                </form>
            </div>

            <div class="directive-list">
                <strong style="display: block; margin-bottom: 10px;">Dernières directives émises :</strong>
                <div style="margin-top: 8px;">
                    <?php 
                    $historique_admin = $db->query("SELECT * FROM message ORDER BY id_msg DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
                    
                    if (empty($historique_admin)): ?>
                        <p style="font-size: 13px; color: #64748b; font-style: italic;">Aucune directive émise pour le moment.</p>
                    <?php else: ?>
                        <?php foreach ($historique_admin as $msg): ?>
                            <div class="directive-item" style="background: #e5ded4; padding: 12px; border-radius: 10px; margin-bottom: 8px; line-height: 1.4; position: relative;">
                                <strong>Type de cible : <span style="color:#10b981;"><?= htmlspecialchars($msg['type_destinataire']); ?></span></strong> 
                                
                                <form method="POST" action="dashboard_admin.php" style="position: absolute; top: 10px; right: 10px;" onsubmit="return confirm('Voulez-vous vraiment supprimer cette directive ?');">
                                    <input type="hidden" name="id_msg_suppr" value="<?= $msg['id_msg']; ?>">
                                    <button type="submit" name="supprimer_directive" style="background: #ef4444; color: white; border: none; padding: 4px 8px; border-radius: 6px; font-size: 12px; cursor: pointer;">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>

                                <p style="margin: 5px 40px 5px 0;"><?= htmlspecialchars($msg['contenu_msg']); ?></p> 
                                <span style="font-size:11px; color:#64748b; display: block; text-align: right;"><?= $msg['date_envoi_msg']; ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <script>
            function toggleTargetSelects() {
                const etendue = document.getElementById('etendueSelector').value;
                const deptSel = document.getElementById('deptSelector');
                const agentSel = document.getElementById('agentSelector');

                deptSel.style.display = 'none';
                agentSel.style.display = 'none';
                deptSel.required = false;
                agentSel.required = false;

                if (etendue === 'departement') {
                    deptSel.style.display = 'inline-block';
                    deptSel.required = true;
                } else if (etendue === 'individuel') {
                    agentSel.style.display = 'inline-block';
                    agentSel.required = true;
                }
            }
            </script>

        </div>
    </div>

</body>
</html>
<?php
require_once 'config.php';

// Sécurité : On s'assure que c'est bien l'administrateur qui est connecté
if (!isset($_SESSION['id_admin'])) {
    header("Location: connexion.php");
    exit;
}

try {
    // 1. Vérifier si la journée du jour est déjà ouverte
    $stmtJour = $db->prepare("SELECT COUNT(*) FROM journee WHERE date_jour = CURDATE()");
    $stmtJour->execute();
    $jour_actif = ($stmtJour->fetchColumn() > 0) ? true : false;

    // 2. Récupérer tous les agents avec le nom de leur département
    $stmtAgents = $db->query("
        SELECT a.*, d.nom_dept 
        FROM agent a 
        LEFT JOIN departement d ON a.id_dept = d.id_dept 
        ORDER BY a.nom_agent ASC
    ");
    $agents = $stmtAgents->fetchAll();

    // 3. Récupérer l'historique complet des messages pour la supervision
    $stmtMessages = $db->query("
        SELECT m.*, a.nom_agent AS expediteur 
        FROM message m
        JOIN agent a ON m.id_agent_expediteur = a.id_agent
        ORDER BY m.id_msg DESC 
        LIMIT 30
    ");
    $messages = $stmtMessages->fetchAll();

} catch (PDOException $e) {
    die("Erreur de chargement du cockpit administratif : " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>BIOGAZELCO — Cockpit Direction</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --admin-primary: #3b82f6;
            --admin-dark: #0f172a;
            --bg-light: #f8fafc;
            --border-color: #e2e8f0;
            --success: #10b981;
            --danger: #ef4444;
        }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: var(--bg-light); margin: 0; padding: 20px; color: var(--admin-dark); }
        .admin-container { max-width: 1300px; margin: 0 auto; display: grid; grid-template-columns: 1fr 2fr; gap: 25px; margin-top: 20px; }
        .header-panel { background: white; padding: 20px; border-radius: 12px; display: flex; justify-content: space-between; align-items: center; border: 1px solid var(--border-color); }
        .card { background: white; padding: 25px; border-radius: 12px; border: 1px solid var(--border-color); margin-bottom: 25px; }
        .btn { display: inline-block; padding: 10px 20px; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; text-decoration: none; text-align: center; }
        .btn-success { background: var(--success); color: white; }
        .btn-danger { background: var(--danger); color: white; }
        .btn-secondary { background: #cbd5e1; color: #334155; }
        .btn-catalogue { background: #8b5cf6; color: white; width: 100%; box-sizing: border-box; text-align: center; margin-top: 15px; }
        .btn-disabled { background: #e2e8f0; color: #94a3b8; cursor: not-allowed; }
        
        /* Tableaux admin */
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid var(--border-color); font-size: 14px; }
        th { background: #f1f5f9; font-weight: 600; }
        
        /* Badges de statut */
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 700; display: inline-block; }
        .badge-active { background: #dcfce7; color: #15803d; }
        .badge-banned { background: #fee2e2; color: #b91c1c; }

        .message-item { background: #f8fafc; padding: 12px; border-radius: 6px; margin-bottom: 10px; border-left: 3px solid var(--admin-primary); }
        .message-meta { display: flex; justify-content: space-between; font-size: 11px; color: #64748b; margin-bottom: 4px; }
    </style>
</head>
<body>

<div class="header-panel">
    <div>
        <h2 style="margin: 0;">Direction Générale — BIOGAZELCO</h2>
        <small style="color: #64748b;">Espace de Supervision et de Pilotage</small>
    </div>
    <a href="deconnexion.php" class="btn btn-danger">Déconnexion</a>
</div>

<div class="admin-container">
    <div>
        <div class="card">
            <h3>Contrôle Opérationnel</h3>
            <p style="font-size: 14px; color: #64748b;">Activez la journée pour permettre aux équipes de pointer leur présence.</p>
            
            <div style="display: flex; gap: 10px; margin-top: 15px;">
                <form action="gestion_jour.php" method="POST" style="flex: 1;">
                    <input type="hidden" name="action" value="ouvrir">
                    <?php if ($jour_actif): ?>
                        <button type="button" class="btn btn-disabled" style="width: 100%;" disabled>✓ Journée Déjà Active</button>
                    <?php else: ?>
                        <button type="submit" class="btn btn-success" style="width: 100%;">Activer la Journée</button>
                    <?php endif; ?>
                </form>

                <form action="gestion_jour.php" method="POST">
                    <input type="hidden" name="action" value="purger">
                    <button type="submit" class="btn btn-secondary" onclick="return confirm('Voulez-vous réinitialiser les présences pour vos tests ?');">Purger</button>
                </form>
            </div>

            <a href="gestion_catalogue.php" class="btn btn-catalogue">📦 Gérer le Catalogue Produits</a>
        </div>

        <div class="card">
            <h3>Supervision et Échanges</h3>
            
            <form action="chat_moteur.php" method="POST" style="margin-bottom: 20px;">
                <input type="hidden" name="action" value="envoyer">
                <div style="display: flex; gap: 10px;">
                    <textarea name="contenu_msg" placeholder="Écrire une directive ou un message officiel..." rows="2" style="flex: 1; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px; resize: none; font-family: inherit;" required></textarea>
                    <div style="display: flex; flex-direction: column; gap: 5px;">
                        <select name="portee_msg" style="padding: 5px; border: 1px solid var(--border-color); border-radius: 6px; font-family: inherit;">
                            <option value="tous">Tous</option>
                        </select>
                        <button type="submit" class="btn btn-success" style="padding: 6px 12px; font-size: 13px;">Envoyer</button>
                    </div>
                </div>
            </form>

            <hr style="border: 0; border-top: 1px solid var(--border-color); margin: 15px 0;">

            <div style="max-height: 350px; overflow-y: auto;">
                <?php if (empty($messages)): ?>
                    <p style="text-align: center; color: #64748b; font-size: 13px;">Aucun échange enregistré.</p>
                <?php else: ?>
                    <?php foreach ($messages as $msg): ?>
                        <div class="message-item">
                            <div class="message-meta">
                                <strong><?php echo htmlspecialchars($msg['expediteur']); ?></strong>
                                <span>Portée : <?php echo htmlspecialchars($msg['portee_msg']); ?></span>
                            </div>
                            <div style="font-size: 13px;"><?php echo htmlspecialchars($msg['contenu_msg']); ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="card">
        <h3>Gestion des Comptes Collaborateurs</h3>
        <table>
            <thead>
                <tr>
                    <th>Nom de l'Agent</th>
                    <th>Département</th>
                    <th>Salaire de Base</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($agents)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center; color: #64748b;">Aucun collaborateur inscrit pour le moment.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($agents as $ag): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($ag['nom_agent']); ?></strong><br><small style="color:#64748b;"><?php echo htmlspecialchars($ag['email_agent']); ?></small></td>
                            <td><?php echo htmlspecialchars($ag['nom_dept'] ?? 'Non assigné'); ?></td>
                            <td><?php echo number_format($ag['sal_base_agent'], 2, ',', ' '); ?> USD</td>
                            <td>
                                <?php if ((int)$ag['statut_banni'] === 1): ?>
                                    <span class="badge badge-banned">Bloqué</span>
                                <?php else: ?>
                                    <span class="badge badge-active">Actif</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form action="valider_agent.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="id_agent" value="<?php echo $ag['id_agent']; ?>">
                                    <?php if ((int)$ag['statut_banni'] === 1): ?>
                                        <input type="hidden" name="action" value="approuver">
                                        <button type="submit" class="btn btn-success" style="padding: 5px 10px; font-size: 12px;">Débloquer</button>
                                    <?php else: ?>
                                        <input type="hidden" name="action" value="bloquer">
                                        <button type="submit" class="btn btn-danger" style="padding: 5px 10px; font-size: 12px;">Bloquer</button>
                                    <?php endif; ?>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
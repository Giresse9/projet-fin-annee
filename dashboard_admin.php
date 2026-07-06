<?php
require_once 'config.php';

// Sécurité : Vérification de la session Administrateur
if (!isset($_SESSION['id_admin'])) {
    header("Location: connexion.php");
    exit;
}

try {
    // 1. Récupération des agents en attente d'approbation (Statut = 2)
    $stmtAttente = $db->query("SELECT a.*, d.nom_dept FROM agent a JOIN departement d ON a.id_dept = d.id_dept WHERE a.statut_banni = 2 ORDER BY a.id_agent DESC");
    $agentsEnAttente = $stmtAttente->fetchAll();

    // 2. Récupération de la liste globale des agents actifs ou bloqués (Statut 0 ou 1)
    $stmtMembres = $db->query("SELECT a.*, d.nom_dept FROM agent a JOIN departement d ON a.id_dept = d.id_dept WHERE a.statut_banni IN (0,1) ORDER BY d.nom_dept ASC, a.nom_agent ASC");
    $listeMembres = $stmtMembres->fetchAll();

    // 3. Récupération des 10 dernières connexions (Piste d'audit)
    $stmtLogs = $db->query("SELECT h.*, a.nom_agent, a.email_agent FROM historique_connexion h JOIN agent a ON h.id_agent = a.id_agent ORDER BY h.id_log DESC LIMIT 10");
    $historiqueConnexions = $stmtLogs->fetchAll();

} catch (PDOException $e) {
    die("Erreur de chargement du panneau d'administration : " . $e->getMessage());
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
        :root {
            --brand-primary: #0f172a;
            --brand-accent: #10b981;
            --danger-color: #ef4444;
            --bg-muted: #f8fafc;
            --border-color: #e2e8f0;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-muted);
            color: #1e293b;
            margin: 0;
            padding: 0;
        }

        /* Topbar Admin */
        .admin-navbar {
            background-color: var(--brand-primary);
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }

        .btn-logout {
            color: #94a3b8;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .btn-logout:hover { color: white; }

        /* Grid Layout */
        .admin-container {
            max-width: 1340px;
            margin: 40px auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }

        @media (max-width: 1024px) {
            .admin-container { grid-template-columns: 1fr; }
        }

        .card {
            background: white;
            border-radius: 20px;
            border: 1px solid var(--border-color);
            padding: 25px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.01);
            margin-bottom: 30px;
        }

        .card-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--brand-primary);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Outils d'exploitation */
        .ops-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .btn-action-admin {
            padding: 14px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 14px;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
            width: 100%;
            box-sizing: border-box;
        }

        .btn-accent { background-color: var(--brand-accent); color: white; }
        .btn-accent:hover { background-color: #059669; }
        .btn-secondary { background-color: #e2e8f0; color: var(--brand-primary); }
        .btn-secondary:hover { background-color: #cbd5e1; }
        .btn-danger { background-color: rgba(239, 68, 68, 0.1); color: var(--danger-color); }
        .btn-danger:hover { background-color: var(--danger-color); color: white; }

        /* Structures de Tables Modernes */
        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 14px;
        }

        th {
            background-color: #f1f5f9;
            color: #64748b;
            padding: 12px 16px;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.5px;
        }

        td {
            padding: 16px;
            border-bottom: 1px solid var(--border-color);
            color: #334155;
        }

        tr:last-child td { border-bottom: none; }

        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-waiting { background-color: #fef3c7; color: #d97706; }
        .badge-active { background-color: #dcfce7; color: #15803d; }
        .badge-blocked { background-color: #fef2f2; color: #ef4444; }

        .action-inline-group {
            display: flex;
            gap: 8px;
        }

        .btn-table-action {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            text-decoration: none;
        }
    </style>
</head>
<body>

    <!-- Topbar Direction -->
    <header class="admin-navbar">
        <h2 style="font-size: 18px; margin: 0; font-weight: 700; letter-spacing: -0.5px;"><i class="fa-solid fa-screwdriver-wrench"></i> BIOGAZELCO — Administration</h2>
        <a href="deconnexion.php" class="btn-logout"><i class="fa-solid fa-arrow-right-from-bracket"></i> Quitter le cockpit</a>
    </header>

    <div class="admin-container">
        
        <!-- SECTION GAUCHE : Gestion opérationnelle du personnel -->
        <div>
            <!-- Demandes d'inscription en attente -->
            <div class="card">
                <h3 class="card-title" style="color: #d97706;"><i class="fa-solid fa-user-clock"></i> Inscriptions en attente d'approbation</h3>
                <div class="table-responsive">
                    <?php if (empty($agentsEnAttente)): ?>
                        <p style="color: #64748b; font-size: 14px; padding: 10px 0;">Aucune demande d'inscription en attente.</p>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Collaborateur</th>
                                    <th>Département</th>
                                    <th>Statut de sécurité</th>
                                    <th>Décision administrative</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($agentsEnAttente as $att): ?>
                                    <tr>
                                        <td>
                                            <div style="font-weight: 600;"><?php echo htmlspecialchars($att['nom_agent']); ?></div>
                                            <div style="font-size: 12px; color: #64748b;"><?php echo htmlspecialchars($att['email_agent']); ?></div>
                                        </td>
                                        <td><?php echo htmlspecialchars($att['nom_dept']); ?></td>
                                        <td><span class="badge badge-waiting">En attente (2)</span></td>
                                        <td>
                                            <a href="valider_agent.php?id_agent=<?php echo $att['id_agent']; ?>" class="btn-table-action btn-accent" style="background-color: var(--brand-accent); color: white;">Approuver</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Liste complète de l'effectif -->
            <div class="card">
                <h3 class="card-title"><i class="fa-solid fa-users"></i> Gestion globale du personnel</h3>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Collaborateur</th>
                                <th>Département</th>
                                <th>État</th>
                                <th>Actions de sécurité</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($listeMembres as $mbr): ?>
                                <tr>
                                    <td>
                                        <div style="font-weight: 600;"><?php echo htmlspecialchars($mbr['nom_agent']); ?></div>
                                        <div style="font-size: 12px; color: #64748b;"><?php echo htmlspecialchars($mbr['email_agent']); ?></div>
                                    </td>
                                    <td><?php echo htmlspecialchars($mbr['nom_dept']); ?></td>
                                    <td>
                                        <?php if ((int)$mbr['statut_banni'] === 0): ?>
                                            <span class="badge badge-active">Actif (0)</span>
                                        <?php else: ?>
                                            <span class="badge badge-blocked">Suspendu (1)</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-inline-group">
                                            <?php if ((int)$mbr['statut_banni'] === 0): ?>
                                                <a href="supprimer_produit.php?bannir=<?php echo $mbr['id_agent']; ?>" class="btn-table-action" style="background-color: #fee2e2; color: var(--danger-color);">Bloquer</a>
                                            <?php else: ?>
                                                <a href="debloquer_agent.php?id_agent=<?php echo $mbr['id_agent']; ?>" class="btn-table-action" style="background-color: #dcfce7; color: #15803d;">Réactiver</a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- SECTION DROITE : Pilotage de l'exploitation & Piste d'audit -->
        <div>
            <!-- Actions Système -->
            <div class="card">
                <h3 class="card-title"><i class="fa-solid fa-gears"></i> Contrôle d'Exploitation</h3>
                <div class="ops-grid">
                    <form action="gestion_jour.php" method="POST" style="grid-column: 1/-1;">
                        <input type="hidden" name="action" value="ouvrir">
                        <button type="submit" class="btn-action-admin btn-accent"><i class="fa-solid fa-play"></i> Activer Journée</button>
                    </form>
                    
                    <form action="gestion_jour.php" method="POST" style="grid-column: 1/-1;">
                        <input type="hidden" name="action" value="purger">
                        <button type="submit" class="btn-action-admin btn-danger"><i class="fa-solid fa-trash-can"></i> Purger Présences</button>
                    </form>
                    
                    <a href="calcul_salaires.php" class="btn-action-admin btn-secondary" style="grid-column: 1/-1;"><i class="fa-solid fa-calculator"></i> Calculer les Salaires</a>
                </div>
            </div>

            <!-- Historique des Connexions (Piste d'audit) -->
            <div class="card">
                <h3 class="card-title"><i class="fa-solid fa-shield-halved"></i> Surveillance de Sécurité</h3>
                <div class="table-responsive">
                    <table style="font-size: 13px;">
                        <thead>
                            <tr>
                                <th>Agent</th>
                                <th>Horodatage & IP</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($historiqueConnexions)): ?>
                                <tr><td colspan="2" style="color: #64748b; text-align: center;">Aucun log disponible.</td></tr>
                            <?php else: ?>
                                <?php foreach ($historiqueConnexions as $log): ?>
                                    <tr>
                                        <td>
                                            <div style="font-weight: 600;"><?php echo htmlspecialchars($log['nom_agent']); ?></div>
                                        </td>
                                        <td style="color: #64748b; font-size: 12px;">
                                            <div><i class="fa-regular fa-clock"></i> <?php echo date('d/m/Y H:i', strtotime($log['date_connexion'])); ?></div>
                                            <div><i class="fa-solid fa-network-wired"></i> IP: <?php echo htmlspecialchars($log['adresse_ip']); ?></div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

</body>
</html>
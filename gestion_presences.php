<?php
session_start();
// Sécurité : Seul l'administrateur a accès à cette page
if (!isset($_SESSION['id_admin'])) {
    header("Location: connexion.php");
    exit();
}

// Connexion à la base de données
try {
    $db = new PDO('mysql:host=localhost;dbname=biogaz;charset=utf8', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die('Erreur : ' . $e->getMessage());
}

// Gestion du filtre par mois (par défaut, le mois en cours)
$mois_filtre = isset($_GET['mois']) ? $_GET['mois'] : date('Y-m');

// Requête SQL pour récupérer l'historique des présences du mois sélectionné
$sql = "SELECT p.id_presence, p.date_jour, p.est_present, a.nom_agent, d.nom_dept 
        FROM presence p
        JOIN agent a ON p.id_agent = a.id_agent
        JOIN departement d ON a.id_dept = d.id_dept
        WHERE DATE_FORMAT(p.date_jour, '%Y-%m') = :mois_filtre
        ORDER BY p.date_jour DESC, a.nom_agent ASC";

$stmt = $db->prepare($sql);
$stmt->execute([':mois_filtre' => $mois_filtre]);
$presences = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Présences - BIOGAZELCO</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; margin: 20px; }
        .container { max-width: 1000px; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        h1 { color: #333; }
        .filter-box { margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #4CAF50; color: white; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .status-present { color: green; font-weight: bold; }
        .status-absent { color: red; font-weight: bold; }
        .btn-delete { background-color: #f44336; color: white; padding: 6px 12px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; font-size: 13px; }
        .btn-delete:hover { background-color: #da190b; }
        .alert-success { background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 15px; }
    </style>
</head>
<body>

<div class="container">
    <a href="dashboard_admin.php" style="text-decoration: none; color: #555;">← Retour au panneau de contrôle</a>
    
    <h1>Suivi & Contrôle des Présences</h1>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert-success">Le pointage frauduleux a été supprimé avec succès.</div>
    <?php endif; ?>

    <div class="filter-box">
        <form method="GET" action="">
            <label for="mois">Choisir un mois : </label>
            <input type="month" id="mois" name="mois" value="<?php echo htmlspecialchars($mois_filtre); ?>" onchange="this.form.submit()">
        </form>
    </div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Nom de l'Agent</th>
                <th>Département</th>
                <th>Statut de Présence</th>
                <th>Action Opérationnelle</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($presences)): ?>
                <tr>
                    <td colspan="5" style="text-align: center;">Aucun enregistrement de présence pour ce mois.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($presences as $p): ?>
                    <tr>
                        <td><?php echo date('d/m/Y', strtotime($p['date_jour'])); ?></td>
                        <td><?php echo htmlspecialchars($p['nom_agent']); ?></td>
                        <td><?php echo htmlspecialchars($p['nom_dept']); ?></td>
                        <td>
                            <?php if ($p['est_present'] == 1): ?>
                                <span class="status-present">● Disponible (Présent)</span>
                            <?php else: ?>
                                <span class="status-absent">○ Non disponible (Absent)</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($p['est_present'] == 1): ?>
                                <form action="supprimer_presence.php" method="POST" onsubmit="return confirm('Voulez-vous vraiment annuler la présence de cet agent pour cette journée ?');" style="display:inline;">
                                    <input type="hidden" name="id_presence" value="<?php echo $p['id_presence']; ?>">
                                    <input type="hidden" name="mois_retour" value="<?php echo $mois_filtre; ?>">
                                    <button type="submit" class="btn-delete">Supprimer Présence</button>
                                </form>
                            <?php else: ?>
                                <span style="color: #999; font-style: italic;">Aucune action</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>
<?php
require_once 'config.php';

// Vérification de la session de l'agent
if (!isset($_SESSION['id_agent'])) {
    header("Location: connexion.php");
    exit;
}

$id_agent = (int)$_SESSION['id_agent'];
$mois_actuel = date('m');
$annee_actuelle = date('Y');

try {
    // 1. Récupérer les informations de salaire fixe de l'agent
    $stmtAgent = $db->prepare("SELECT nom_agent, sal_base_agent FROM agent WHERE id_agent = :id");
    $stmtAgent->execute([':id' => $id_agent]);
    $agent = $stmtAgent->fetch();

    // 2. Compter le nombre de journées ouvrables ce mois-ci
    $stmtTotalJours = $db->prepare("SELECT COUNT(*) FROM journee WHERE MONTH(date_jour) = :mois AND YEAR(date_jour) = :annee AND est_ouvrable = 1");
    $stmtTotalJours->execute([':mois' => $mois_actuel, ':annee' => $annee_actuelle]);
    $total_jours_ouvrables = (int)$stmtTotalJours->fetchColumn();

    // 3. Compter le nombre de présences réelles de l'agent ce mois-ci
    $stmtPresences = $db->prepare("SELECT COUNT(*) FROM presence WHERE id_agent = :id AND MONTH(date_jour) = :mois AND YEAR(date_jour) = :annee");
    $stmtPresences->execute([':id' => $id_agent, ':mois' => $mois_actuel, ':annee' => $annee_actuelle]);
    $presences_agent = (int)$stmtPresences->fetchColumn();

    // 4. Calcul du prorata
    $salaire_final = 0;
    if ($total_jours_ouvrables > 0) {
        $salaire_final = ($agent['sal_base_agent'] / $total_jours_ouvrables) * $presences_agent;
    }

} catch (PDOException $e) {
    die("Erreur de calcul des émoluments : " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>BIOGAZELCO — Bulletin de Paie</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f8fafc; color: #0f172a; padding: 40px; }
        .invoice-box { max-width: 600px; margin: auto; background: white; padding: 30px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 12px rgba(0,0,0,0.02); }
        .header { display: flex; justify-content: space-between; border-bottom: 2px solid #10b981; padding-bottom: 20px; margin-bottom: 20px; }
        .meta-table { width: 100%; margin-top: 20px; border-collapse: collapse; }
        .meta-table td { padding: 12px; border-bottom: 1px solid #edf2f7; }
        .total-row { font-weight: bold; color: #10b981; font-size: 18px; }
        .btn-back { display: inline-block; margin-top: 20px; padding: 10px 20px; background: #0f172a; color: white; text-decoration: none; border-radius: 6px; font-size: 14px; }
    </style>
</head>
<body>

<div class="invoice-box">
    <div class="header">
        <div>
            <h2 style="margin:0; color:#0f172a;">BIOGAZELCO SARLU</h2>
            <small>Kinshasa, RDC</small>
        </div>
        <div style="text-align: right;">
            <strong>Fiche de Paie</strong><br>
            Période : <?php echo date('m/Y'); ?>
        </div>
    </div>

    <p><strong>Collaborateur :</strong> <?php echo htmlspecialchars($agent['nom_agent']); ?></p>

    <?php if ($total_jours_ouvrables === 0): ?>
        <p style="color: #64748b; text-align: center; padding: 20px;">Aucune activité ouvrable n'a été enregistrée pour cette période.</p>
    <?php else: ?>
        <table class="meta-table">
            <tr>
                <td>Salaire Mensuel de Base Brut</td>
                <td style="text-align: right;"><?php echo number_format($agent['sal_base_agent'], 2, ',', ' '); ?> USD</td>
            </tr>
            <tr>
                <td>Jours Ouvrés Actifs de l'Entreprise</td>
                <td style="text-align: right;"><?php echo $total_jours_ouvrables; ?> jours</td>
            </tr>
            <tr>
                <td>Vos Présences Effectives Enregistrées</td>
                <td style="text-align: right; text-weight: 600; color: #15803d;"><?php echo $presences_agent; ?> jours</td>
            </tr>
            <tr class="total-row">
                <td>Net À Percevoir (Prorata)</td>
                <td style="text-align: right;"><?php echo number_format($salaire_final, 2, ',', ' '); ?> USD</td>
            </tr>
        </table>
    <?php endif; ?>

    <a href="dashboard_agent.php" class="btn-back">Retour au Tableau de Bord</a>
</div>

</body>
</html>
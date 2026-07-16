<?php
session_start();
require_once 'config.php';

// 1. Sécurité : On vérifie que c'est bien un agent connecté
if (!isset($_SESSION['id_agent']) || $_SESSION['role'] !== 'agent') {
    header('Location: connexion.php');
    exit();
}

$id_agent = $_SESSION['id_agent'];

try {
    // 2. Récupération des données de l'agent et de son département
    $stmt = $db->prepare("SELECT a.*, d.nom_dept FROM agent a LEFT JOIN departement d ON a.id_dept = d.id_dept WHERE a.id_agent = :id");
    $stmt->execute([':id' => $id_agent]);
    $agent = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$agent) {
        die("Erreur : Impossible de charger les informations de l'agent.");
    }

    // 3. Calculs financiers basés sur le salaire de base
    $salaire_base = (float)$agent['sal_base_agent'];
    
    // Simulations de retenues et charges (à adapter selon tes besoins)
    $retenue_cnss = $salaire_base * 0.05; // 5% CNSS / Sécurité sociale
    $impot_revenu = $salaire_base * 0.10; // 10% Impôt sur le revenu
    $total_retenues = $retenue_cnss + $impot_revenu;
    
    // Primes éventuelles
    $prime_transport = 50.00; // Fixe pour l'exemple
    $salaire_net = ($salaire_base + $prime_transport) - $total_retenues;

} catch (PDOException $e) {
    die("Erreur de base de données : " . $e->getMessage());
}


// Configuration de la date actuelle au format français moderne
$date_courante = new DateTime();
$formatter = new IntlDateFormatter(
    'fr_FR',
    IntlDateFormatter::FULL,
    IntlDateFormatter::NONE,
    'Africa/Kinshasa',
    IntlDateFormatter::GREGORIAN,
    'MMMM yyyy'
);

$periode_actuelle = ucfirst($formatter->format($date_courante)); 
// Affichera proprement : "Juillet 2026" (ou le mois actuel)
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BIOGAZELCO — Bulletin de Paie</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f3eee7; font-family: 'Plus Jakarta Sans', sans-serif; margin: 0; padding: 40px 20px; color: #0f172a; }
        .payslip-container { max-width: 800px; margin: 0 auto; background: #ede7de; border-radius: 24px; padding: 40px; box-shadow: 0 10px 30px rgba(0,0,0,0.02); }
        
        /* Zone actions de navigation haut */
        .no-print-actions { display: flex; justify-content: space-between; margin-bottom: 30px; }
        .btn-nav { text-decoration: none; background: #e5ded4; color: #64748b; padding: 10px 18px; border-radius: 10px; font-weight: 600; font-size: 14px; display: inline-flex; align-items: center; gap: 8px; border: none; cursor: pointer; }
        .btn-nav:hover { background: #dcd6cb; color: #0f172a; }
        .btn-print { background: #52b788; color: white; }
        .btn-print:hover { background: #40916c; color: white; }

        /* Header du Bulletin */
        .payslip-header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px dashed #dcd6cb; padding-bottom: 25px; margin-bottom: 25px; }
        .company-logo h1 { margin: 0; font-size: 24px; font-weight: 700; letter-spacing: -0.5px; }
        .company-logo h1 span { color: #10b981; }
        .company-logo p { margin: 5px 0 0; font-size: 12px; color: #64748b; }
        .payslip-title { text-align: right; }
        .payslip-title h2 { margin: 0; font-size: 20px; color: #0f172a; font-weight: 700; }
        .payslip-title p { margin: 5px 0 0; font-size: 14px; font-weight: 600; color: #10b981; background: #e5ded4; padding: 4px 12px; border-radius: 6px; display: inline-block; }

        /* Blocs d'informations (Employeur / Salarié) */
        .details-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-bottom: 35px; }
        .details-block h4 { margin: 0 0 10px 0; font-size: 12px; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px; }
        .details-block p { margin: 4px 0; font-size: 14px; font-weight: 500; }

        /* Tableau des éléments de rémunération */
        table { width: 100%; border-collapse: collapse; margin-bottom: 35px; }
        th { background: #e5ded4; color: #64748b; font-size: 12px; font-weight: 700; text-transform: uppercase; padding: 12px; text-align: left; }
        th.text-right, td.text-right { text-align: right; }
        td { padding: 14px 12px; font-size: 14px; border-bottom: 1px solid #dcd6cb; font-weight: 500; }
        .gain-val { color: #15803d; }
        .retenue-val { color: #b91c1c; }

        /* Bloc Total Net */
        .net-pay-block { background: #e5ded4; border-radius: 16px; padding: 20px 25px; display: flex; justify-content: space-between; align-items: center; }
        .net-pay-block span { font-size: 15px; font-weight: 700; text-transform: uppercase; color: #64748b; }
        .net-pay-block h3 { margin: 0; font-size: 26px; font-weight: 700; color: #10b981; }

        /* Mentions bas de page */
        .payslip-footer { text-align: center; margin-top: 40px; font-size: 12px; color: #64748b; border-top: 1px solid #dcd6cb; padding-top: 20px; }

        /* Styles dédiés à l'impression */
        @media print {
            body { background: white; padding: 0; }
            .payslip-container { box-shadow: none; padding: 0; background: white; max-width: 100%; }
            .no-print-actions { display: none; }
            th { background: #f1f5f9 !important; }
            .net-pay-block { background: #f1f5f9 !important; }
        }
    </style>
</head>
<body>

<div class="payslip-container">

    <div class="no-print-actions">
        <a href="dashboard_agent.php" class="btn-nav">
            <i class="fa-solid fa-arrow-left"></i> Retour au Dashboard
        </a>
        <button onclick="window.print();" class="btn-nav btn-print">
            <i class="fa-solid fa-print"></i> Imprimer le Bulletin
        </button>
    </div>

    <div class="payslip-header">
        <div class="company-logo">
            <h1>BIOGAZELCO<span>.com</span></h1>
            <p>Solutions Énergétiques Innovantes & Durables<br>Kinshasa, RD Congo</p>
        </div>
        <div class="payslip-title">
            <h2>BULLETIN DE PAIE</h2>
            <p>Période : <?php echo $periode_actuelle; ?></p>
        </div>
    </div>

    <div class="details-grid">
        <div class="details-block">
            <h4>Émetteur / Employeur</h4>
            <p><strong>BIOGAZELCO S.A.R.L</strong></p>
            <p>Id. Nationale : Kid/098-B/2024</p>
            <p>Direction des Ressources Humaines</p>
        </div>
        <div class="details-block" style="border-left: 2px solid #dcd6cb; padding-left: 40px;">
            <h4>Bénéficiaire / Collaborateur</h4>
            <p>Nom Complet : <strong><?php echo htmlspecialchars($agent['nom_agent']); ?></strong></p>
            <p>Matricule : <strong><?php echo htmlspecialchars($agent['code_agent']); ?></strong></p>
            <p>Département : <strong><?php echo htmlspecialchars($agent['nom_dept'] ?? 'Général'); ?></strong></p>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Désignation des Éléments</th>
                <th class="text-right">Base / Assiette</th>
                <th class="text-right">Part Gains (+)</th>
                <th class="text-right">Part Retenues (-)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Salaire de base contractuel</td>
                <td class="text-right"><?php echo number_format($salaire_base, 2, '.', ' '); ?></td>
                <td class="text-right gain-val">+<?php echo number_format($salaire_base, 2, '.', ' '); ?></td>
                <td class="text-right">-</td>
            </tr>
            <tr>
                <td>Indemnité forfaitaire de transport</td>
                <td class="text-right">-</td>
                <td class="text-right gain-val">+<?php echo number_format($prime_transport, 2, '.', ' '); ?></td>
                <td class="text-right">-</td>
            </tr>
            <tr>
                <td>Cotisation Sociale Obligatoire (CNSS)</td>
                <td class="text-right"><?php echo number_format($salaire_base, 2, '.', ' '); ?></td>
                <td class="text-right">-</td>
                <td class="text-right retenue-val">-<?php echo number_format($retenue_cnss, 2, '.', ' '); ?></td>
            </tr>
            <tr>
                <td>Impôt Professionnel sur les Revenus (IPR)</td>
                <td class="text-right"><?php echo number_format($salaire_base, 2, '.', ' '); ?></td>
                <td class="text-right">-</td>
                <td class="text-right retenue-val">-<?php echo number_format($impot_revenu, 2, '.', ' '); ?></td>
            </tr>
        </tbody>
    </table>

    <div class="net-pay-block">
        <span>Net à percevoir (USD) :</span>
        <h3><?php echo number_format($salaire_net, 2, '.', ' '); ?> USD</h3>
    </div>

    <div class="payslip-footer">
        <p>Ce bulletin de paie est un document officiel généré par votre espace sécurisé BIOGAZELCO.</p>
        <p style="font-size: 10px; margin-top: 5px; color: #b0a89f;">Pour faire valoir ce que de droit — Édité le <?php echo date('d/m/Y à H:i'); ?></p>
    </div>

</div>

</body>
</html>
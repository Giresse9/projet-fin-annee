<?php
require_once 'config.php';

// Sécurité : On s'assure qu'un agent est connecté
if (!isset($_SESSION['id_agent'])) {
    header("Location: connexion.php");
    exit;
}

$id_agent = (int)$_SESSION['id_agent'];
$nom_agent = $_SESSION['nom_agent'];
$id_dept = (int)$_SESSION['id_dept'];

try {
    // 1. Récupérer le nom du département de l'agent
    $stmtDept = $db->prepare("SELECT nom_dept FROM departement WHERE id_dept = :id_dept");
    $stmtDept->execute([':id_dept' => $id_dept]);
    $nom_dept = $stmtDept->fetchColumn();

    // 2. Récupérer le salaire fixe de base pour l'affichage
    $stmtSal = $db->prepare("SELECT sal_base_agent FROM agent WHERE id_agent = :id_agent");
    $stmtSal->execute([':id_agent' => $id_agent]);
    $salaire_base = $stmtSal->fetchColumn();

    // 3. Vérification si l'agent a déjà émargé aujourd'hui (sans id_pres)
    $stmtCheckPresence = $db->prepare("SELECT COUNT(*) FROM presence WHERE id_agent = :id AND date_jour = CURDATE()");
    $stmtCheckPresence->execute([':id' => $id_agent]);
    $deja_pointe = ($stmtCheckPresence->fetchColumn() > 0) ? true : false;

    // 4. Récupération des messages selon le filtrage de vos tables réelles
    $stmtMessages = $db->prepare("
        SELECT m.*, a.nom_agent as expediteur 
        FROM message m 
        JOIN agent a ON m.id_agent_expediteur = a.id_agent 
        WHERE m.portee_msg = 'tous' 
           OR (m.portee_msg = 'departement' AND m.id_dept_destinataire = :id_dept)
           OR (m.portee_msg = 'solo' AND m.id_agent_destinataire = :id_agent)
        ORDER BY m.id_msg DESC 
        LIMIT 20
    ");
    $stmtMessages->execute([
        ':id_dept'  => $id_dept,
        ':id_agent' => $id_agent
    ]);
    $messages = $stmtMessages->fetchAll();

} catch (PDOException $e) {
    die("Erreur de chargement du tableau de bord : " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>BIOGAZELCO — Espace Agent</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --brand-primary: #10b981;
            --brand-dark: #0f172a;
            --bg-light: #f8fafc;
            --border-color: #e2e8f0;
        }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: var(--bg-light); margin: 0; padding: 20px; color: var(--brand-dark); }
        .dashboard-container { max-width: 1200px; margin: 0 auto; display: grid; grid-template-columns: 1fr 2fr; gap: 25px; margin-top: 20px; }
        .header-panel { background: white; padding: 20px; border-radius: 12px; display: flex; justify-content: space-between; align-items: center; border: 1px solid var(--border-color); }
        .card { background: white; padding: 25px; border-radius: 12px; border: 1px solid var(--border-color); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.01); }
        .btn { display: inline-block; width: 100%; padding: 12px; text-align: center; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; text-decoration: none; transition: all 0.2s; }
        .btn-primary { background: var(--brand-primary); color: white; }
        .btn-secondary { background: #edf2f7; color: var(--brand-dark); border: 1px solid var(--border-color); }
        .btn-disabled { background: #cbd5e1; color: #64748b; cursor: not-allowed; }
        .btn-danger { background: #ef4444; color: white; width: auto; padding: 8px 16px; }
        .message-item { background: #f1f5f9; padding: 15px; border-radius: 8px; margin-bottom: 15px; border-left: 4px solid #94a3b8; }
        .message-item.dept { border-left-color: #3b82f6; background: #eff6ff; }
        .message-item.solo { border-left-color: #a855f7; background: #faf5ff; }
        .message-meta { display: flex; justify-content: space-between; font-size: 12px; color: #64748b; margin-bottom: 5px; }
        .chat-input-box { display: flex; gap: 10px; margin-bottom: 20px; }
        textarea { flex: 1; padding: 12px; border: 1px solid var(--border-color); border-radius: 8px; resize: none; font-family: inherit; }
        select { padding: 12px; border: 1px solid var(--border-color); border-radius: 8px; font-family: inherit; }
    </style>
</head>
<body>

<div class="header-panel">
    <div>
        <h2 style="margin: 0;"><?php echo htmlspecialchars($nom_agent); ?></h2>
        <small style="color: #64748b; font-weight: 500;"><?php echo htmlspecialchars($nom_dept); ?></small>
    </div>
    <a href="deconnexion.php" class="btn btn-danger">Déconnexion</a>
</div>

<div class="dashboard-container">
    <div style="display: flex; flex-direction: column; gap: 25px;">
        <div class="card">
            <h3>Pointage Quotidien</h3>
            <form action="gestion_jour.php" method="POST">
                <input type="hidden" name="action" value="pointer">
                <?php if ($deja_pointe): ?>
                    <button type="button" class="btn btn-disabled" disabled>✓ Présence signalée aujourd'hui</button>
                <?php else: ?>
                    <button type="submit" class="btn btn-primary">Signaler ma présence</button>
                <?php endif; ?>
            </form>
        </div>

        <div class="card">
            <h3>Situation Financière</h3>
            <p style="font-size: 14px; color: #64748b; margin-bottom: 5px;">SALAIRE DE BASE FIXE</p>
            <strong style="font-size: 24px;"><?php echo number_format($salaire_base, 2, ',', ' '); ?> USD</strong>
            <div style="margin-top: 20px;">
                <a href="calcul_salaires.php" class="btn btn-secondary">Consulter ma fiche de paie</a>
            </div>
        </div>
    </div>

    <div class="card">
        <h3>Fil de Discussion Sécurisé</h3>
        
        <form action="chat_moteur.php" method="POST">
            <input type="hidden" name="action" value="envoyer">
            <div class="chat-input-box">
                <textarea name="contenu_msg" placeholder="Écrivez votre message interne ici..." rows="2" required></textarea>
                <select name="portee_msg">
                    <option value="tous">Tous</option>
                    <option value="departement">Mon Département</option>
                </select>
                <button type="submit" class="btn btn-primary" style="width: auto; padding: 0 20px;">Envoyer</button>
            </div>
        </form>

        <hr style="border: 0; border-top: 1px solid var(--border-color); margin: 20px 0;">

        <div class="message-list">
            <?php if (empty($messages)): ?>
                <p style="text-align: center; color: #64748b; font-size: 14px; padding: 20px;">Aucun message dans votre flux actuel.</p>
            <?php else: ?>
                <?php foreach ($messages as $msg): ?>
                    <?php 
                        $classe_portee = '';
                        if ($msg['portee_msg'] === 'departement') $classe_portee = 'dept';
                        if ($msg['portee_msg'] === 'solo') $classe_portee = 'solo';
                    ?>
                    <div class="message-item <?php echo $classe_portee; ?>">
                        <div class="message-meta">
                            <span style="font-weight: 700; color: #10b981;"><?php echo htmlspecialchars($msg['expediteur']); ?></span>
                            <span><?php echo date('d/m H:i', strtotime($msg['date_envoi_msg'])); ?></span>
                        </div>
                        <div class="message-text"><?php echo htmlspecialchars($msg['contenu_msg']); ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>
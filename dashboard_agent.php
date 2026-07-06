<?php
require_once 'config.php';

// Sécurité : Vérification de la session agent
if (!isset($_SESSION['id_agent'])) {
    header("Location: connexion.php");
    exit;
}

$id_agent = $_SESSION['id_agent'];
$nom_agent = $_SESSION['nom_agent'];
$id_dept = $_SESSION['id_dept'];

$message_action = "";

try {
    // 1. Récupération des informations financières et personnelles de l'agent
    $stmtAgent = $db->prepare("SELECT a.*, d.nom_dept FROM agent a JOIN departement d ON a.id_dept = d.id_dept WHERE a.id_agent = :id");
    $stmtAgent->execute([':id' => $id_agent]);
    $agentInfo = $stmtAgent->fetch();

    // 2. Vérification si l'agent a déjà pointé aujourd'hui
    $stmtCheckPresence = $db->prepare("SELECT * FROM presence WHERE id_agent = :id AND date_jour = CURDATE()");
    $stmtCheckPresence->execute([':id' => $id_agent]);
    $deja_pointe = $stmtCheckPresence->fetch() ? true : false;

    // 3. Récupération des messages selon la portée réelle de vos tables
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BIOGAZELCO — Espace Agent</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --brand-primary: #0f172a;
            --brand-accent: #10b981;
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

        /* Topbar Privée */
        .dashboard-navbar {
            background-color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border-color);
        }

        .user-profile-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .avatar-circle {
            width: 40px;
            height: 40px;
            background-color: #e2e8f0;
            color: var(--brand-primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }

        .btn-logout {
            color: #ef4444;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* Layout Grid */
        .dashboard-container {
            max-width: 1240px;
            margin: 40px auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 30px;
        }

        @media (max-width: 968px) {
            .dashboard-container { grid-template-columns: 1fr; }
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

        /* Widgets de données */
        .stat-box {
            background: #f1f5f9;
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 15px;
        }

        .stat-label { font-size: 12px; color: #64748b; font-weight: 600; text-transform: uppercase; }
        .stat-value { font-size: 20px; font-weight: 700; color: var(--brand-primary); margin-top: 5px; }

        /* Module de pointage */
        .btn-attendance {
            width: 100%;
            padding: 14px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 15px;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-attendance.success { background-color: rgba(16, 185, 129, 0.1); color: var(--brand-accent); cursor: not-allowed; }
        .btn-attendance.action { background-color: var(--brand-accent); color: white; }
        .btn-attendance.action:hover { background-color: #059669; }

        /* Messagerie Interne */
        .chat-input-area {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
        }

        .chat-input {
            flex-grow: 1;
            padding: 12px 16px;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            font-size: 14px;
        }

        .chat-input:focus { outline: none; border-color: var(--brand-accent); }

        .chat-select {
            padding: 12px;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            background: white;
            font-size: 14px;
        }

        .btn-send {
            background-color: var(--brand-primary);
            color: white;
            border: none;
            padding: 0 20px;
            border-radius: 12px;
            cursor: pointer;
        }

        .message-list {
            max-height: 400px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .message-item {
            background: #f8fafc;
            padding: 15px;
            border-radius: 14px;
            border-left: 4px solid #cbd5e1;
        }

        .message-item.dept { border-left-color: #3b82f6; background: #f0f7ff; }
        .message-item.solo { border-left-color: #a855f7; background: #faf5ff; }

        .message-meta {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: #64748b;
            margin-bottom: 6px;
            font-weight: 500;
        }

        .message-text { font-size: 14px; line-height: 1.5; color: #334155; }
    </style>
</head>
<body>

    <!-- Barre de Navigation Privée -->
    <header class="dashboard-navbar">
        <div class="user-profile-info">
            <div class="avatar-circle"><?php echo strtoupper(substr($nom_agent, 0, 2)); ?></div>
            <div>
                <h2 style="font-size: 16px; margin: 0; font-weight: 700;"><?php echo htmlspecialchars($nom_agent); ?></h2>
                <span style="font-size: 12px; color: #64748b; font-weight: 500;"><?php echo htmlspecialchars($agentInfo['nom_dept']); ?></span>
            </div>
        </div>
        <a href="deconnexion.php" class="btn-logout"><i class="fa-solid fa-arrow-right-from-bracket"></i> Déconnexion</a>
    </header>

    <div class="dashboard-container">
        
        <!-- COLONNE GAUCHE : Statuts & Pointage -->
        <div>
            <div class="card">
                <h3 class="card-title"><i class="fa-solid fa-user-check"></i> Pointage Quotidien</h3>
                <?php if ($deja_pointe): ?>
                    <button class="btn-attendance success" disabled>
                        <i class="fa-solid fa-circle-check"></i> Présence enregistrée aujourd'hui
                    </button>
                <?php else: ?>
                    <form action="gestion_jour.php" method="POST">
                        <input type="hidden" name="action" value="pointer">
                        <button type="submit" class="btn-attendance action">
                            <i class="fa-solid fa-fingerprint"></i> Signaler ma présence
                        </button>
                    </form>
                <?php endif; ?>
            </div>

            <div class="card">
                <h3 class="card-title"><i class="fa-solid fa-wallet"></i> Situation Financière</h3>
                <div class="stat-box">
                    <div class="stat-label">Salaire de Base Fixe</div>
                    <div class="stat-value"><?php echo number_format($agentInfo['sal_base_agent'], 2, ',', ' '); ?> USD</div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">Salaire au Prorata Actuel</div>
                    <!-- Calculé et actualisé dynamiquement par le script de calcul -->
                    <div class="stat-value" style="color: var(--brand-accent);">
                        <a href="calcul_salaires.php" style="text-decoration: none; color: inherit;">Consulter ma fiche de paie</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- COLONNE DROITE : Messagerie d'Entreprise Filtrée -->
        <div class="card">
            <h3 class="card-title"><i class="fa-solid fa-comments"></i> Fil de Discussion Sécurisé</h3>
            
            <!-- Formulaire d'envoi de message -->
            <form action="chat_moteur.php" method="POST" class="chat-input-area">
                <input type="text" name="texte_msg" class="chat-input" placeholder="Écrivez votre message interne ici..." required>
                <select name="portee_msg" class="chat-select">
                    <option value="tous">Tous</option>
                    <option value="departement">Mon Département</option>
                </select>
                <button type="submit" class="btn-send"><i class="fa-regular fa-paper-plane"></i></button>
            </form>

            <!-- Liste des messages reçus -->
            <div class="message-list">
                <?php if (empty($messages)): ?>
                    <p style="text-align: center; color: #64748b; font-size: 14px; padding: 20px;">Aucun message dans votre flux actuel.</p>
                <?php else: ?>
                    <?php foreach ($messages as $msg): ?>
                        <?php 
                            // Classe de style en fonction de la portée du message
                            $classe_portee = '';
                            if ($msg['portee_msg'] === 'departement') $classe_portee = 'dept';
                            if ($msg['portee_msg'] === 'solo') $classe_portee = 'solo';
                        ?>
                        <div class="message-item <?php echo $classe_portee; ?>">
                            <div class="message-meta">
                                <span style="font-weight: 700; color: var(--brand-primary);"><?php echo htmlspecialchars($msg['expediteur']); ?></span>
                                <span><?php echo date('d/m H:i', strtotime($msg['date_envoi'])); ?></span>
                            </div>
                            <div class="message-text"><?php echo htmlspecialchars($msg['texte_msg']); ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </div>

</body>
</html>
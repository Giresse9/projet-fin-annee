<?php
session_start();
require_once 'config.php';

$message_erreur = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = htmlspecialchars(trim($_POST['code_agent']), ENT_QUOTES, 'UTF-8');
    $mdp = trim($_POST['mdp_agent'] ?? '');

    if (!empty($code) && !empty($mdp)) {
        try {
            // 1. On cherche d'abord dans la table administrateur
            $stmtAdmin = $db->prepare("SELECT * FROM administrateur WHERE code_admin = :code");
            $stmtAdmin->execute([':code' => $code]);
            $admin = $stmtAdmin->fetch(PDO::FETCH_ASSOC);

            if ($admin && $admin['mdp_admin'] === $mdp) {
                $_SESSION['id_admin'] = $admin['id_admin'];
                $_SESSION['nom_admin'] = $admin['nom_admin'];
                $_SESSION['code_admin'] = $admin['code_admin'];
                $_SESSION['role'] = 'admin';
                
                header('Location: dashboard_admin.php');
                exit();
            }

            // 2. Si pas trouvé chez l'admin, on cherche dans la table agent
            $stmtAgent = $db->prepare("
                SELECT a.*, d.nom_dept 
                FROM agent a
                LEFT JOIN departement d ON a.id_dept = d.id_dept
                WHERE a.code_agent = :code
            ");
            $stmtAgent->execute([':code' => $code]);
            $agent = $stmtAgent->fetch(PDO::FETCH_ASSOC);

            if ($agent && $agent['mdp_agent'] === $mdp) {
                if ($agent['statut_agent'] === 'Actif') {
                    $_SESSION['id_agent'] = $agent['id_agent'];
                    $_SESSION['nom_agent'] = $agent['nom_agent'];
                    $_SESSION['code_agent'] = $agent['code_agent'];
                    $_SESSION['id_dept'] = $agent['id_dept'];
                    $_SESSION['departement'] = $agent['nom_dept'];
                    $_SESSION['role'] = 'agent';
                    
                    header('Location: dashboard_agent.php');
                    exit();
                } else {
                    $message_erreur = "Votre compte est 'Inactif'. Veuillez attendre la validation de la Direction.";
                }
            } else {
                $message_erreur = "Code identifiant ou mot de passe incorrect.";
            }

        } catch (PDOException $e) {
            $message_erreur = "Erreur serveur : " . $e->getMessage();
        }
    } else {
        $message_erreur = "Veuillez remplir tous les champs.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BIOGAZELCO — Connexion Intranet</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f3eee7; font-family: 'Plus Jakarta Sans', sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
        .login-wrapper { background: #ede7de; width: 100%; max-width: 420px; border-radius: 24px; padding: 40px; box-shadow: 0 10px 30px rgba(0,0,0,0.02); }
        .brand-header { text-align: center; margin-bottom: 30px; }
        .brand-header h1 { color: #0f172a; font-size: 24px; font-weight: 700; }
        .brand-header h1 span { color: #10b981; }
        .alert { padding: 12px; background: #fef2f2; color: #ef4444; border-radius: 10px; font-size: 14px; margin-bottom: 20px; display: flex; align-items: center; gap: 8px; }
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #0f172a; }
        .input-icon-wrapper { position: relative; }
        .input-icon-wrapper i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #64748b; }
        .form-control { width: 100%; padding: 12px 12px 12px 40px; border: none; border-radius: 10px; background: #e5ded4; color: #0f172a; font-size: 14px; box-sizing: border-box; }
        .btn-submit { width: 100%; background: #52b788; color: white; border: none; padding: 12px; font-weight: 600; border-radius: 10px; cursor: pointer; margin-top: 10px; font-size: 14px; }
        .btn-submit:hover { background: #40916c; }
        
        /* Footer de navigation */
        .form-footer { text-align: center; margin-top: 20px; font-size: 13px; color: #64748b; display: flex; flex-direction: column; gap: 10px; }
        .form-footer a { color: #10b981; text-decoration: none; font-weight: 600; }
        .form-footer a:hover { text-decoration: underline; }
        .btn-link-modify { color: #64748b !important; font-weight: 500 !important; font-size: 12px; margin-top: 5px; }
        .btn-link-modify:hover { color: #0f172a !important; }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="brand-header">
            <h1>Espace <span>Connexion</span></h1>
            <p style="color: #64748b; font-size:13px; margin-top:5px;">Portail Intranet BIOGAZELCO</p>
        </div>

        <?php if (!empty($message_erreur)): ?>
            <div class="alert"><i class="fa-solid fa-circle-exclamation"></i><div><?php echo $message_erreur; ?></div></div>
        <?php endif; ?>

        <form action="connexion.php" method="POST">
            <div class="form-group">
                <label>Code Identifiant (Agent ou Admin)</label>
                <div class="input-icon-wrapper">
                    <i class="fa-regular fa-id-badge"></i>
                    <input type="text" name="code_agent" class="form-control" placeholder="Ex: AG-2026-01" required>
                </div>
            </div>
            <div class="form-group">
                <label>Mot de passe</label>
                <div class="input-icon-wrapper">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" name="mdp_agent" class="form-control" placeholder="••••••••" required>
                </div>
            </div>
            <button type="submit" class="btn-submit">Se connecter</button>
        </form>
        
        <div class="form-footer">
            <div>Pas encore inscrit ? <a href="inscription.php">Créer un compte</a></div>
            <!-- Nouveau lien vers la modification demandé -->
            <a href="profil_agent.php" class="btn-link-modify">
                <i class="fa-regular fa-pen-to-square"></i> Modifier mes informations
            </a>
        </div>
    </div>
</body>
</html>
<?php
require_once 'config.php';

$message_succes = "";
$message_erreur = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Nettoyage des entrées
    $code = htmlspecialchars(trim($_POST['code_agent']), ENT_QUOTES, 'UTF-8');
    $mdp_actuel = trim($_POST['mdp_actuel'] ?? '');
    $nouveau_nom = htmlspecialchars(trim($_POST['nouveau_nom']), ENT_QUOTES, 'UTF-8');
    $nouveau_mdp = trim($_POST['nouveau_mdp'] ?? '');

    if (!empty($code) && !empty($mdp_actuel) && !empty($nouveau_nom) && !empty($nouveau_mdp)) {
        try {
            // 1. Vérifier si l'agent existe avec ce code et ce mot de passe actuel
            $stmtCheck = $db->prepare("SELECT id_agent FROM agent WHERE code_agent = :code AND mdp_agent = :mdp");
            $stmtCheck->execute([
                ':code' => $code,
                ':mdp'  => $mdp_actuel
            ]);
            $agent = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            if ($agent) {
                // 2. Mettre à jour le nom et le mot de passe (sans hachage, en texte clair comme demandé)
                $stmtUpdate = $db->prepare("
                    UPDATE agent 
                    SET nom_agent = :nom, mdp_agent = :mdp, statut_agent ='Inactif' 
                    WHERE id_agent = :id
                ");
                $stmtUpdate->execute([
                    ':nom' => $nouveau_nom,
                    ':mdp' => $nouveau_mdp,
                    ':id'  => $agent['id_agent']
                ]);

                $message_succes = "Vos informations ont été mises à jour avec succès !";
            } else {
                $message_erreur = "Le code identifiant ou le mot de passe actuel est incorrect.";
            }
        } catch (PDOException $e) {
            $message_erreur = "Erreur lors de la modification : " . $e->getMessage();
        }
    } else {
        $message_erreur = "Veuillez remplir tous les champs du formulaire.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BIOGAZELCO — Modification Profil</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { 
            background-color: #f3eee7; 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            min-height: 100vh; 
            margin: 0; 
            padding: 20px 0;
        }
        .modify-wrapper { 
            background: #ede7de; 
            width: 100%; 
            max-width: 460px; 
            border-radius: 24px; 
            padding: 35px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.02); 
        }
        .brand-header { 
            text-align: center; 
            margin-bottom: 25px; 
        }
        .brand-header h1 { 
            color: #0f172a; 
            font-size: 24px; 
            font-weight: 700; 
            margin: 0; 
        }
        .brand-header h1 span { 
            color: #10b981; 
        }
        .alert-danger { 
            padding: 12px; 
            background: #fef2f2; 
            color: #ef4444; 
            border-radius: 10px; 
            font-size: 14px; 
            margin-bottom: 20px; 
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .alert-success { 
            padding: 12px; 
            background: #f0fdf4; 
            color: #15803d; 
            border-radius: 10px; 
            font-size: 14px; 
            margin-bottom: 20px; 
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .section-title {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            color: #64748b;
            margin: 20px 0 10px 0;
            border-bottom: 1px solid #dcd6cb;
            padding-bottom: 5px;
            letter-spacing: 0.5px;
        }
        .form-group { 
            margin-bottom: 16px; 
        }
        .form-group label { 
            display: block; 
            font-size: 13px; 
            font-weight: 600; 
            margin-bottom: 6px; 
            color: #0f172a; 
        }
        .form-control { 
            width: 100%; 
            padding: 12px; 
            border: none; 
            border-radius: 10px; 
            background: #e5ded4; 
            color: #0f172a; 
            font-size: 14px; 
            box-sizing: border-box; 
        }
        .form-control:focus {
            outline: 2px solid #52b788;
        }
        .btn-submit { 
            width: 100%; 
            background: #52b788; 
            color: white; 
            border: none; 
            padding: 13px; 
            font-weight: 600; 
            border-radius: 10px; 
            cursor: pointer; 
            margin-top: 15px; 
            font-size: 14px; 
        }
        .btn-submit:hover { 
            background: #40916c; 
        }
        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #64748b;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 20px;
            background: #e5ded4;
            padding: 8px 14px;
            border-radius: 8px;
        }
        .btn-back:hover {
            background: #dcd6cb;
            color: #0f172a;
        }
    </style>
</head>
<body>
    <div class="modify-wrapper">
        <!-- Bouton Retour -->
        <a href="connexion.php" class="btn-back">
            <i class="fa-solid fa-arrow-left"></i> Retour à la connexion
        </a>

        <div class="brand-header">
            <h1>Mise à jour <span>Profil</span></h1>
        </div>

        <?php if (!empty($message_erreur)): ?>
            <div class="alert-danger"><i class="fa-solid fa-circle-exclamation"></i> <?php echo $message_erreur; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($message_succes)): ?>
            <div class="alert-success"><i class="fa-solid fa-circle-check"></i> <?php echo $message_succes; ?></div>
        <?php endif; ?>

        <form action="profil_agent.php" method="POST">
            
            <div class="section-title">1. Identification Sécurisée</div>
            
            <div class="form-group">
                <label>Votre Code Identifiant unique</label>
                <input type="text" name="code_agent" class="form-control" placeholder="Ex: AG-2026-02" required>
            </div>
            
            <div class="form-group">
                <label>Mot de passe actuel</label>
                <input type="password" name="mdp_actuel" class="form-control" placeholder="••••••••" required>
            </div>

            <div class="section-title">2. Nouvelles Informations</div>

            <div class="form-group">
                <label>Nouveau nom complet</label>
                <input type="text" name="nouveau_nom" class="form-control" placeholder="Ex: Jean Paul" required>
            </div>

            <div class="form-group">
                <label>Nouveau mot de passe</label>
                <input type="password" name="nouveau_mdp" class="form-control" placeholder="Minimum 4 caractères" required>
            </div>

            <button type="submit" class="btn-submit">
                <i class="fa-regular fa-square-check"></i> Appliquer les modifications
            </button>
        </form>
    </div>
</body>
</html>
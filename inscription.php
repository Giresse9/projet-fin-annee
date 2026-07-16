<?php
require_once 'config.php';

$message_succes = "";
$message_erreur = "";

try {
    $queryDept = $db->query("SELECT id_dept, nom_dept FROM departement ORDER BY nom_dept ASC");
    $departements = $queryDept->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message_erreur = "Erreur départements : " . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = htmlspecialchars(trim($_POST['nom_agent']), ENT_QUOTES, 'UTF-8');
    $code = htmlspecialchars(trim($_POST['code_agent']), ENT_QUOTES, 'UTF-8');
    $pwd = trim($_POST['pwd_agent'] ?? '');
    $id_dept = isset($_POST['id_dept']) ? (int)$_POST['id_dept'] : 0;
    $salaire = floatval($_POST['salaire_base_agent'] ?? 0);

    if (empty($code) || empty($nom) || empty($pwd) || $id_dept === 0 || $salaire <= 0) {
        $message_erreur = "Veuillez remplir correctement tous les champs.";
    } else {
        try {
            $stmtCheck = $db->prepare("SELECT id_agent FROM agent WHERE code_agent = :code");
            $stmtCheck->execute([':code' => $code]);
            
            if ($stmtCheck->fetch()) {
                $message_erreur = "Ce code identifiant appartient déjà à un agent.";
            } else {
                $stmt = $db->prepare("
                    INSERT INTO agent (nom_agent, code_agent, mdp_agent, sal_base_agent, statut_agent, id_dept)
                    VALUES (:nom, :code_agent, :pwd, :salaire, 'Inactif', :id_dept)
                ");
                $stmt->execute([
                    ':nom' => $nom,
                    ':code_agent' => $code,
                    ':pwd' => $pwd,
                    ':salaire' => $salaire,
                    ':id_dept' => $id_dept
                ]);
                $message_succes = "Inscription réussie ! En attente d'activation par la direction.";
            }
        } catch (PDOException $e) {
            $message_erreur = "Erreur d'insertion : " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BIOGAZELCO — Inscription</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f3eee7; font-family: 'Plus Jakarta Sans', sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 20px 0;}
        .register-wrapper { background: #ede7de; width: 100%; max-width: 480px; border-radius: 24px; padding: 35px; box-shadow: 0 10px 30px rgba(0,0,0,0.02); position: relative; }
        .brand-header { text-align: center; margin-bottom: 25px; }
        .brand-header h1 { color: #0f172a; font-size: 24px; font-weight: 700; margin: 0; }
        .brand-header h1 span { color: #10b981; }
        .alert-danger { padding: 12px; background: #fef2f2; color: #ef4444; border-radius: 10px; font-size: 14px; margin-bottom: 20px; }
        .alert-success { padding: 12px; background: #f0fdf4; color: #15803d; border-radius: 10px; font-size: 14px; margin-bottom: 20px; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #0f172a; }
        .form-control { width: 100%; padding: 12px; border: none; border-radius: 10px; background: #e5ded4; color: #0f172a; font-size: 14px; box-sizing: border-box; }
        .btn-submit { width: 100%; background: #52b788; color: white; border: none; padding: 12px; font-weight: 600; border-radius: 10px; cursor: pointer; margin-top: 10px; font-size: 14px; }
        .btn-submit:hover { background: #40916c; }
        
        /* Bouton Retour Connexion */
        .btn-back-login {
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
        .btn-back-login:hover {
            background: #dcd6cb;
            color: #0f172a;
        }
    </style>
</head>
<body>
    <div class="register-wrapper">
        <a href="connexion.php" class="btn-back-login">
            <i class="fa-solid fa-arrow-left"></i> Retour à la connexion
        </a>

        <div class="brand-header">
            <h1>Demande d'<span>Inscription</span></h1>
        </div>

        <?php if (!empty($message_erreur)): ?><div class="alert-danger"><?php echo $message_erreur; ?></div><?php endif; ?>
        <?php if (!empty($message_succes)): ?><div class="alert-success"><?php echo $message_succes; ?></div><?php endif; ?>

        <form action="inscription.php" method="POST">
            <div class="form-group"><label>Nom complet</label><input type="text" name="nom_agent" class="form-control" required></div>
            <div class="form-group"><label>Code Identifiant unique</label><input type="text" name="code_agent" class="form-control" placeholder="Ex: AG-2026-02" required></div>
            <div class="form-group"><label>Mot de passe</label><input type="password" name="pwd_agent" class="form-control" required></div>
            <div class="form-group"><label>Salaire mensuel souhaité (USD)</label><input type="number" step="0.01" name="salaire_base_agent" class="form-control" required></div>
            <div class="form-group">
                <label>Département</label>
                <select name="id_dept" class="form-control" required>
                    <option value="" disabled selected>Choisir...</option>
                    <?php foreach ($departements as $dept): ?>
                        <option value="<?php echo $dept['id_dept']; ?>"><?php echo htmlspecialchars($dept['nom_dept']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn-submit">Soumettre la demande</button>
        </form>
    </div>
</body>
</html>
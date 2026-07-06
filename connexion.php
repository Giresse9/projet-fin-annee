<?php
require_once 'config.php';

$message_erreur = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
    $pwd = $_POST['password'];

    if (!$email || empty($pwd)) {
        $message_erreur = "Veuillez remplir tous les champs correctement.";
    } else {
        try {
            // 1. Vérification d'abord dans la table Administrateur
            $stmtAdmin = $db->prepare("SELECT * FROM administrateur WHERE email_admin = :email");
            $stmtAdmin->execute([':email' => $email]);
            $admin = $stmtAdmin->fetch();

            if ($admin && $pwd === $admin['pwd_admin']) {
                $_SESSION['id_admin'] = $admin['id_admin'];
                $_SESSION['nom_admin'] = "Administrateur";
                header("Location: dashboard_admin.php");
                exit;
            }

            // 2. Si ce n'est pas un admin, vérification dans la table Agent
            $stmtAgent = $db->prepare("SELECT * FROM agent WHERE email_agent = :email");
            $stmtAgent->execute([':email' => $email]);
            $agent = $stmtAgent->fetch();

            if ($agent && $pwd === $agent['pwd_agent']) {
                
                // Analyse sécuritaire du statut du compte
                $statut = (int)$agent['statut_banni'];
                if ($statut === 2) {
                    $message_erreur = "Accès refusé. Votre compte est créé mais en attente d'approbation par la direction.";
                } elseif ($statut === 1) {
                    $message_erreur = "Accès refusé. Ce compte a été suspendu ou bloqué.";
                } else {
                    // Compte actif (0) : Enregistrement dans l'historique des connexions
                    $stmtLog = $db->prepare("INSERT INTO historique_connexion (id_agent, date_connexion, adresse_ip) VALUES (:id, NOW(), :ip)");
                    $stmtLog->execute([
                        ':id' => $agent['id_agent'],
                        ':ip' => $_SERVER['REMOTE_ADDR']
                    ]);

                    // Initialisation des variables de session
                    $_SESSION['id_agent'] = $agent['id_agent'];
                    $_SESSION['nom_agent'] = $agent['nom_agent'];
                    $_SESSION['id_dept']  = $agent['id_dept'];
                    
                    header("Location: dashboard_agent.php");
                    exit;
                }
            } else {
                $message_erreur = "Identifiants de connexion incorrects.";
            }
        } catch (PDOException $e) {
            $message_erreur = "Erreur système : " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BIOGAZELCO — Connexion Intranet</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --brand-primary: #0f172a;
            --brand-accent: #10b981;
            --text-muted: #64748b;
            --input-border: #cbd5e1;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Plus Jakarta Sans', sans-serif;
            transition: all 0.2s ease;
        }

        body {
            background-color: #f8fafc;
            display: flex;
            min-height: 100vh;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-wrapper {
            background: #ffffff;
            width: 100%;
            max-width: 450px;
            border-radius: 24px;
            box-shadow: 0 20px 40px -15px rgba(0,0,0,0.08);
            border: 1px solid #e2e8f0;
            padding: 40px;
        }

        .brand-header {
            text-align: center;
            margin-bottom: 35px;
        }

        .brand-header h1 {
            color: var(--brand-primary);
            font-size: 28px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .brand-header h1 span {
            color: var(--brand-accent);
        }

        .brand-header p {
            color: var(--text-muted);
            font-size: 14px;
            margin-top: 8px;
            font-weight: 500;
        }

        .alert {
            background-color: #fef2f2;
            border: 1px solid #fee2e2;
            color: #ef4444;
            padding: 14px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--brand-primary);
            margin-bottom: 8px;
        }

        .input-icon-wrapper {
            position: relative;
        }

        .input-icon-wrapper i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 16px;
        }

        .form-control {
            width: 100%;
            padding: 14px 16px 14px 45px;
            font-size: 15px;
            border: 1px solid var(--input-border);
            border-radius: 12px;
            color: var(--brand-primary);
            background-color: #fcffff;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--brand-accent);
            box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1);
            background-color: #ffffff;
        }

        .btn-submit {
            width: 100%;
            background-color: var(--brand-primary);
            color: white;
            border: none;
            padding: 14px;
            font-size: 15px;
            font-weight: 600;
            border-radius: 12px;
            cursor: pointer;
            margin-top: 10px;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.1);
        }

        .btn-submit:hover {
            background-color: #1e293b;
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(15, 23, 42, 0.15);
        }

        .form-footer {
            text-align: center;
            margin-top: 30px;
            font-size: 14px;
            color: var(--text-muted);
        }

        .form-footer a {
            color: var(--brand-accent);
            text-decoration: none;
            font-weight: 600;
        }

        .form-footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <div class="login-wrapper">
        <div class="brand-header">
            <h1>BIOGAZELCO <span>Intranet</span></h1>
            <p>Espace de Gestion et d'Accès Sécurisé</p>
        </div>

        <?php if (!empty($message_erreur)): ?>
            <div class="alert">
                <i class="fa-solid fa-circle-exclamation"></i>
                <?php echo htmlspecialchars($message_erreur); ?>
            </div>
        <?php endif; ?>

        <form action="connexion.php" method="POST">
            <div class="form-group">
                <label for="email">Adresse email professionnelle</label>
                <div class="input-icon-wrapper">
                    <i class="fa-regular fa-envelope"></i>
                    <input type="email" name="email" id="email" class="form-control" placeholder="nom.prenom@biogazelco.com" required>
                </div>
            </div>

            <div class="form-group">
                <label for="password">Mot de passe</label>
                <div class="input-icon-wrapper">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required>
                </div>
            </div>

            <button type="submit" class="btn-submit">Se connecter à l'espace</button>
        </form>

        <div class="form-footer">
            Nouveau collaborateur ? <a href="inscription.php">Créer un compte</a>
        </div>
    </div>

</body>
</html>
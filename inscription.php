<?php
require_once 'config.php';

$message_succes = "";
$message_erreur = "";

// Récupération des départements existants pour le menu déroulant
try {
    $queryDept = $db->query("SELECT * FROM departement ORDER BY nom_dept ASC");
    $departements = $queryDept->fetchAll();
} catch (PDOException $e) {
    $message_erreur = "Erreur de chargement des départements : " . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = htmlspecialchars(trim($_POST['nom_agent']), ENT_QUOTES, 'UTF-8');
    $email = filter_var(trim($_POST['email_agent']), FILTER_VALIDATE_EMAIL);
    $pwd = !empty($_POST['pwd_agent']) ? trim($_POST['pwd_agent']) : null;
    $id_dept = isset($_POST['id_dept']) ? (int)$_POST['id_dept'] : 0;

    if (!$email || empty($nom) || !$pwd || $id_dept === 0) {
        $message_erreur = "Veuillez remplir correctement tous les champs requis.";
    } else {
        try {
            // Vérification si l'email existe déjà
            $stmtCheck = $db->prepare("SELECT id_agent FROM agent WHERE email_agent = :email");
            $stmtCheck->execute([':email' => $email]);
            
            if ($stmtCheck->fetch()) {
                $message_erreur = "Cette adresse email professionnelle est déjà associée à un compte.";
            } else {
                // Insertion sécurisée : Le statut_banni est forcé à 2 (En attente d'approbation)
                $stmt = $db->prepare("
                    INSERT INTO agent (nom_agent, email_agent, pwd_agent, sal_base_agent, statut_banni, id_dept)
                    VALUES (:nom, :email, :pwd, 0.00, 2, :id_dept)
                ");
                
                $stmt->execute([
                    ':nom'     => $nom,
                    ':email'   => $email,
                    ':pwd'     => $pwd,
                    ':id_dept' => $id_dept
                ]);

                $message_succes = "Votre demande d'inscription a été enregistrée avec succès ! Votre compte est actuellement en attente d'approbation par la direction avant de pouvoir accéder à l'Intranet.";
            }
        } catch (PDOException $e) {
            $message_erreur = "Erreur lors de la création du compte : " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BIOGAZELCO — Inscription Collaborateur</title>
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
            min-height: 100vh;
        }

        .register-container {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }

        .register-wrapper {
            background: #ffffff;
            width: 100%;
            max-width: 500px;
            border-radius: 24px;
            box-shadow: 0 20px 40px -15px rgba(0,0,0,0.08);
            border: 1px solid #e2e8f0;
            padding: 40px;
        }

        .brand-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .brand-header h1 {
            color: var(--brand-primary);
            font-size: 26px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .brand-header h1 span {
            color: var(--brand-accent);
        }

        .brand-header p {
            color: var(--text-muted);
            font-size: 14px;
            margin-top: 6px;
        }

        .alert {
            padding: 14px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 25px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            line-height: 1.4;
        }

        .alert-danger {
            background-color: #fef2f2;
            border: 1px solid #fee2e2;
            color: #ef4444;
        }

        .alert-success {
            background-color: #f0fdf4;
            border: 1px solid #dcfce7;
            color: #15803d;
        }

        .alert i {
            margin-top: 2px;
        }

        .form-group {
            margin-bottom: 20px;
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

        select.form-control {
            appearance: none;
            cursor: pointer;
        }

        .btn-submit {
            width: 100%;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
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
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(15, 23, 42, 0.15);
        }

        .form-footer {
            text-align: center;
            margin-top: 25px;
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

    <?php include_once 'menu.php'; ?>

    <div class="register-container">
        <div class="register-wrapper">
            <div class="brand-header">
                <h1>Demande d'<span>Inscription</span></h1>
                <p>Rejoignez l'Intranet BIOGAZELCO SARLU</p>
            </div>

            <?php if (!empty($message_erreur)): ?>
                <div class="alert alert-danger">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <div><?php echo htmlspecialchars($message_erreur); ?></div>
                </div>
            <?php endif; ?>

            <?php if (!empty($message_succes)): ?>
                <div class="alert alert-success">
                    <i class="fa-solid fa-circle-check"></i>
                    <div><?php echo htmlspecialchars($message_succes); ?></div>
                </div>
            <?php endif; ?>

            <form action="inscription.php" method="POST">
                <div class="form-group">
                    <label for="nom_agent">Nom complet</label>
                    <div class="input-icon-wrapper">
                        <i class="fa-regular fa-user"></i>
                        <input type="text" name="nom_agent" id="nom_agent" class="form-control" placeholder="Ex: Giresse Mbuyi" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="email_agent">Adresse email professionnelle</label>
                    <div class="input-icon-wrapper">
                        <i class="fa-regular fa-envelope"></i>
                        <input type="email" name="email_agent" id="email_agent" class="form-control" placeholder="nom.prenom@biogazelco.com" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="pwd_agent">Mot de passe</label>
                    <div class="input-icon-wrapper">
                        <i class="fa-solid fa-lock"></i>
                        <input type="password" name="pwd_agent" id="pwd_agent" class="form-control" placeholder="••••••••" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="id_dept">Département d'affectation</label>
                    <div class="input-icon-wrapper">
                        <i class="fa-solid fa-sitemap"></i>
                        <select name="id_dept" id="id_dept" class="form-control" required>
                            <option value="" disabled selected>Sélectionnez votre département...</option>
                            <?php foreach ($departements as $dept): ?>
                                <option value="<?php echo $dept['id_dept']; ?>">
                                    <?php echo htmlspecialchars($dept['nom_dept']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <button type="submit" class="btn-submit">Soumettre ma demande d'accès</button>
            </form>

            <div class="form-footer">
                Déjà inscrit ? <a href="connexion.php">Se connecter</a>
            </div>
        </div>
    </div>

</body>
</html>
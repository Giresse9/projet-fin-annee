<?php
session_start();
// Remplace par ton fichier de connexion réel à ta base de données si nécessaire
try {
    $db = new PDO('mysql:host=localhost;dbname=biogaz;charset=utf8', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die('Erreur : ' . $e->getMessage());
}

$message_succes = "";
$message_erreur = "";

// 1. Action : Ajouter un département
if (isset($_POST['ajouter_dept'])) {
    $nom_dept = htmlspecialchars(trim($_POST['nom_dept']), ENT_QUOTES, 'UTF-8');
    if (!empty($nom_dept)) {
        try {
            $stmt = $db->prepare("INSERT INTO departement (nom_dept) VALUES (:nom)");
            $stmt->execute([':nom' => $nom_dept]);
            $message_succes = "Département '$nom_dept' ajouté avec succès !";
        } catch (PDOException $e) {
            $message_erreur = "Erreur lors de l'ajout : " . $e->getMessage();
        }
    }
}

// 2. Action : Supprimer un département
if (isset($_POST['supprimer_dept'])) {
    $id_dept = (int)$_POST['id_dept'];
    try {
        $stmt = $db->prepare("DELETE FROM departement WHERE id_dept = :id");
        $stmt->execute([':id' => $id_dept]);
        $message_succes = "Département supprimé avec succès !";
    } catch (PDOException $e) {
        $message_erreur = "Impossible de supprimer ce département (des agents y sont peut-être encore affectés).";
    }
}

// 3. Récupération de la liste à jour
$departements = $db->query("SELECT * FROM departement ORDER BY nom_dept ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>BIOGAZELCO — Gestion des Départements</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: sans-serif; background-color: #f1f5f9; color: #0f172a; padding: 30px; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); }
        h2 { margin-top: 0; color: #7209b7; }
        .btn { padding: 8px 14px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; color: white; }
        .btn-add { background-color: #10b981; }
        .btn-danger { background-color: #ef4444; padding: 5px 10px; }
        .input-text { padding: 8px; border-radius: 6px; border: 1px solid #cbd5e1; width: 70%; margin-right: 10px; }
        .alert { padding: 10px; border-radius: 6px; margin-bottom: 15px; font-size: 14px; }
        .alert-success { background: #d1fae5; color: #065f46; }
        .alert-danger { background: #fee2e2; color: #991b1b; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { text-align: left; padding: 12px; border-bottom: 1px solid #e2e8f0; }
        th { background: #f8fafc; }
        .back-link { display: inline-block; margin-bottom: 20px; text-decoration: none; color: #64748b; font-weight: 500; }
    </style>
</head>
<body>

<div class="container">
    <a href="dashboard_admin.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Retour au Dashboard</a>
    
    <h2><i class="fa-solid fa-building"></i> Gestion des Départements</h2>
    
    <?php if(!empty($message_succes)): ?>
        <div class="alert alert-success"><?= $message_succes; ?></div>
    <?php endif; ?>
    <?php if(!empty($message_erreur)): ?>
        <div class="alert alert-danger"><?= $message_erreur; ?></div>
    <?php endif; ?>

    <form method="POST" action="" style="margin-bottom: 30px; display: flex;">
        <input type="text" name="nom_dept" placeholder="Nom du nouveau département (ex: Logistique)" class="input-text" required>
        <button type="submit" name="ajouter_dept" class="btn btn-add"><i class="fa-solid fa-plus"></i> Ajouter</button>
    </form>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nom du Département</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($departements as $d): ?>
            <tr>
                <td><?= $d['id_dept']; ?></td>
                <td><strong><?= htmlspecialchars($d['nom_dept']); ?></strong></td>
                <td>
                    <form method="POST" action="" onsubmit="return confirm('Supprimer ce département ?');">
                        <input type="hidden" name="id_dept" value="<?= $d['id_dept']; ?>">
                        <button type="submit" name="supprimer_dept" class="btn btn-danger">
                            <i class="fa-solid fa-trash"></i> Supprimer
                        </button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

</body>
</html>
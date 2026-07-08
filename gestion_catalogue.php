<?php
require_once 'config.php';

if (!isset($_SESSION['id_admin'])) {
    header("Location: connexion.php");
    exit;
}

$id_admin_connecte = $_SESSION['id_admin'];

$dir_photos = 'uploads/photos/';
$dir_videos = 'uploads/videos/';

if (!is_dir($dir_photos)) mkdir($dir_photos, 0777, true);
if (!is_dir($dir_videos)) mkdir($dir_videos, 0777, true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ajouter') {
    $nom_prod  = htmlspecialchars(trim($_POST['nom_prod']), ENT_QUOTES, 'UTF-8');
    $desc_prod = htmlspecialchars(trim($_POST['desc_prod']), ENT_QUOTES, 'UTF-8');
    
    $path_photo = '';
    $path_video = '';

    if (isset($_FILES['file_photo']) && $_FILES['file_photo']['error'] === UPLOAD_ERR_OK) {
        $ext_photo = pathinfo($_FILES['file_photo']['name'], PATHINFO_EXTENSION);
        $nom_photo = 'img_' . time() . '_' . uniqid() . '.' . $ext_photo;
        $cible_photo = $dir_photos . $nom_photo;
        if (move_uploaded_file($_FILES['file_photo']['tmp_name'], $cible_photo)) {
            $path_photo = $cible_photo;
        }
    }

    if (isset($_FILES['file_video']) && $_FILES['file_video']['error'] === UPLOAD_ERR_OK) {
        $ext_video = pathinfo($_FILES['file_video']['name'], PATHINFO_EXTENSION);
        $nom_video = 'vid_' . time() . '_' . uniqid() . '.' . $ext_video;
        $cible_video = $dir_videos . $nom_video;
        if (move_uploaded_file($_FILES['file_video']['tmp_name'], $cible_video)) {
            $path_video = $cible_video;
        }
    }

    try {
        $stmt = $db->prepare("INSERT INTO catalogue (nom_prod, desc_prod, prix_prod, img_prod, video_prod, type_catalogue, id_admin) VALUES (:nom, :descr, 0.00, :img, :vid, 'Vitrine', :id_admin)");
        $stmt->execute([
            ':nom'      => $nom_prod,
            ':descr'    => $desc_prod,
            ':img'      => $path_photo,
            ':vid'      => $path_video,
            ':id_admin' => $id_admin_connecte
        ]);
        header("Location: gestion_catalogue.php");
        exit;
    } catch (PDOException $e) {
        die("Erreur lors de l'ajout : " . $e->getMessage());
    }
}

if (isset($_GET['supprimer'])) {
    $id_prod = (int)$_GET['supprimer'];
    try {
        $stmtSelect = $db->prepare("SELECT img_prod, video_prod FROM catalogue WHERE id_prod = :id");
        $stmtSelect->execute([':id' => $id_prod]);
        $prod = $stmtSelect->fetch();

        if ($prod) {
            if (!empty($prod['img_prod']) && file_exists($prod['img_prod'])) unlink($prod['img_prod']);
            if (!empty($prod['video_prod']) && file_exists($prod['video_prod'])) unlink($prod['video_prod']);
        }

        $stmtDelete = $db->prepare("DELETE FROM catalogue WHERE id_prod = :id");
        $stmtDelete->execute([':id' => $id_prod]);
        header("Location: gestion_catalogue.php");
        exit;
    } catch (PDOException $e) {
        die("Erreur de suppression : " . $e->getMessage());
    }
}

try {
    $produits = $db->query("SELECT * FROM catalogue ORDER BY id_prod DESC")->fetchAll();
} catch (PDOException $e) {
    die("Erreur : " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>BIOGAZELCO — Gestion Catalogue</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --brand: #10b981; --dark: #0f172a; --border: #e2e8f0; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f8fafc; padding: 30px; margin: 0; color: var(--dark); }
        .container { max-width: 1200px; margin: auto; display: grid; grid-template-columns: 1fr 1.2fr; gap: 30px; }
        .card { background: white; padding: 25px; border-radius: 12px; border: 1px solid var(--border); box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 6px; font-weight: 600; font-size: 13px; color: #475569; }
        input[type="text"], textarea { width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 6px; box-sizing: border-box; font-family: inherit; }
        .drop-zone { border: 2px dashed #cbd5e1; padding: 15px; text-align: center; border-radius: 8px; background: #f8fafc; cursor: pointer; font-size: 13px; color: #64748b; }
        .drop-zone.hover { border-color: var(--brand); background: #ecfdf5; }
        .drop-zone span { font-weight: 600; color: var(--brand); }
        .btn { display: inline-block; padding: 11px 20px; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; text-decoration: none; text-align: center; }
        .btn-success { background: var(--brand); color: white; width: 100%; }
        .btn-danger { background: #ef4444; color: white; padding: 6px 12px; font-size: 12px; border-radius: 4px; }
        .grid-produits { display: flex; flex-direction: column; gap: 15px; margin-top: 15px; }
        .prod-row { display: flex; gap: 15px; background: #f8fafc; padding: 12px; border-radius: 8px; border: 1px solid var(--border); align-items: center; justify-content: space-between; }
        .prod-info { display: flex; align-items: center; gap: 15px; }
        .prod-img { width: 60px; height: 60px; object-fit: cover; border-radius: 6px; border: 1px solid var(--border); }
    </style>
</head>
<body>

<div style="display:flex; justify-content: space-between; max-width:1200px; margin: 0 auto 20px auto; align-items:center;">
    <h2>Espace Catalogue d'Exposition — BIOGAZELCO</h2>
    <a href="dashboard_admin.php" style="color: #3b82f6; text-decoration:none; font-weight:600;">← Retour au Cockpit Admin</a>
</div>

<div class="container">
    <div class="card">
        <h3>Nouvel Article Vitrine</h3>
        <form action="gestion_catalogue.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="ajouter">
            <div class="form-group">
                <label>Nom du Produit / Service</label>
                <input type="text" name="nom_prod" required placeholder="Ex: Kit Biogaz Domestique F2">
            </div>
            <div class="form-group">
                <label>Description Exposition</label>
                <textarea name="desc_prod" rows="3" required placeholder="Détails techniques..."></textarea>
            </div>
            <div class="form-group">
                <label>Photo du produit</label>
                <div class="drop-zone" id="drop-zone-photo">
                    Glissez-déposez, collez (Ctrl+V) ou <span>cliquez ici</span>
                    <input type="file" name="file_photo" id="file_photo" accept="image/*" style="display:none;" required>
                    <div id="preview-photo" style="margin-top: 5px; font-weight:600; color:var(--brand);"></div>
                </div>
            </div>
            <div class="form-group">
                <label>Vidéo d'explications</label>
                <div class="drop-zone" id="drop-zone-video">
                    Glissez-déposez ou <span>cliquez ici</span>
                    <input type="file" name="file_video" id="file_video" accept="video/*" style="display:none;">
                    <div id="preview-video" style="margin-top: 5px; font-weight:600; color:#3b82f6;"></div>
                </div>
            </div>
            <button type="submit" class="btn btn-success">Publier sur le Site Vitrine</button>
        </form>
    </div>

    <div class="card">
        <h3>Offres actuellement exposées (Admin)</h3>
        <div class="grid-produits">
            <?php if (empty($produits)): ?>
                <p style="color: #64748b;">Le catalogue d'exposition est vide.</p>
            <?php else: ?>
                <?php foreach ($produits as $p): ?>
                    <div class="prod-row">
                        <div class="prod-info">
                            <img class="prod-img" src="<?php echo !empty($p['img_prod']) ? htmlspecialchars($p['img_prod']) : 'https://via.placeholder.com/60?text=No+Img'; ?>">
                            <div>
                                <h4 style="margin:0; font-size:14px;"><?php echo htmlspecialchars($p['nom_prod']); ?></h4>
                            </div>
                        </div>
                        <a href="gestion_catalogue.php?supprimer=<?php echo $p['id_prod']; ?>" class="btn btn-danger" onclick="return confirm('Retirer ce produit ?');">Supprimer</a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function configurerZone(zoneId, inputId, previewId) {
    const zone = document.getElementById(zoneId);
    const input = document.getElementById(inputId);
    const preview = document.getElementById(previewId);
    zone.addEventListener('click', () => input.click());
    zone.addEventListener('dragover', (e) => { e.preventDefault(); zone.classList.add('hover'); });
    zone.addEventListener('dragleave', () => zone.classList.remove('hover'));
    zone.addEventListener('drop', (e) => {
        e.preventDefault(); zone.classList.remove('hover');
        if (e.dataTransfer.files.length > 0) { input.files = e.dataTransfer.files; preview.innerText = "📁 " + e.dataTransfer.files[0].name; }
    });
    input.addEventListener('change', () => { if (input.files.length > 0) preview.innerText = "📁 " + input.files[0].name; });
}
configurerZone('drop-zone-photo', 'file_photo', 'preview-photo');
configurerZone('drop-zone-video', 'file_video', 'preview-video');

document.getElementById('drop-zone-photo').addEventListener('paste', (e) => {
    const items = (e.clipboardData || e.originalEvent.clipboardData).items;
    for (let index in items) {
        const item = items[index];
        if (item.kind === 'file' && item.type.indexOf('image/') !== -1) {
            const blob = item.getAsFile();
            const dataTransfer = new DataTransfer(); dataTransfer.items.add(blob);
            document.getElementById('file_photo').files = dataTransfer.files;
            document.getElementById('preview-photo').innerText = "📋 Image collée !";
        }
    }
});
</script>
</body>
</html>
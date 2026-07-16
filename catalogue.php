<?php
require_once 'config.php';

$num_whatsapp_admin = "243856321879"; // Ton numéro admin WhatsApp

try {
    $produits = $db->query("SELECT * FROM catalogue ORDER BY id_prod DESC")->fetchAll();
} catch (PDOException $e) {
    die("Erreur de chargement du catalogue : " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>BIOGAZELCO SARLU — Catalogue</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --brand: #10b981; --brand-hover: #059669; --whatsapp: #25D366; --dark: #1e293b; --border: #e2e8f0; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f8fafc; color: var(--dark); margin: 0; padding: 0; }
        
        /* Conteneur principal */
        .content-container { padding: 50px 20px; }
        
        /* Grid des cartes vitrines */
        .products-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 30px; max-width: 1200px; margin: auto; }
        .product-card { background: white; border-radius: 16px; border: 1px solid var(--border); overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.03); transition: transform 0.2s; }
        .product-card:hover { transform: translateY(-4px); }
        
        .card-img { width: 100%; height: 210px; object-fit: cover; background: #e2e8f0; }
        .card-body { padding: 22px; }
        .card-title { font-size: 19px; margin: 0 0 8px 0; font-weight: 700; color: #0f172a; }
        .card-text { font-size: 14px; color: #64748b; line-height: 1.6; margin: 0 0 22px 0; }
        
        .btn { display: block; width: 100%; padding: 13px; border: none; border-radius: 10px; font-weight: 600; font-size: 14px; cursor: pointer; text-decoration: none; text-align: center; box-sizing: border-box; margin-bottom: 12px; transition: all 0.2s; }
        .btn-outline { background: #f1f5f9; border: 1px solid #cbd5e1; color: #334155; }
        .btn-outline:hover { background: #e2e8f0; }
        .btn-wa { background: var(--whatsapp); color: white; margin-bottom: 0; }
        .btn-wa:hover { background: #22c55e; }

        /* Fenêtre Modale de Visionnage Épurée (Vidéo seule) */
        .modal { display: none; position: fixed; top:0; left:0; width:100%; height:100%; background: rgba(15,23,42,0.6); z-index:2000; justify-content:center; align-items:center; backdrop-filter: blur(5px); }
        .modal-content { background: white; padding: 30px; border-radius: 20px; max-width: 640px; width: 90%; position: relative; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.15); }
        .close-modal { position: absolute; top: 15px; right: 20px; font-size: 26px; cursor: pointer; color: #94a3b8; transition: color 0.2s; }
        .close-modal:hover { color: #475569; }
        .modal-video { width: 100%; border-radius: 12px; margin-top: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); background: #000; display: block; }
    </style>
</head>
<body>

<!-- Inclusion dynamique et économique de ton menu global -->
<?php include 'menu.php'; ?>

<div class="content-container">
    <div class="products-grid">
        <?php if (empty($produits)): ?>
            <p style="grid-column: 1/-1; text-align: center; color: #64748b; padding: 40px;">Aucun produit n'est exposé pour le moment.</p>
        <?php else: ?>
            <?php foreach ($produits as $p): ?>
                <?php 
                    $texte_wa = urlencode("Bonjour BIOGAZELCO, je souhaite obtenir les détails de prix pour : " . $p['nom_prod']);
                    $lien_whatsapp = "https://wa.me/" . $num_whatsapp_admin . "?text=" . $texte_wa;
                ?>
                <div class="product-card">
                    <img class="card-img" src="<?php echo !empty($p['img_prod']) ? htmlspecialchars($p['img_prod']) : 'https://via.placeholder.com/350x210?text=BIOGAZELCO'; ?>" alt="Aperçu">
                    <div class="card-body">
                        <h3 class="card-title"><?php echo htmlspecialchars($p['nom_prod']); ?></h3>
                        <p class="card-text"><?php echo nl2br(htmlspecialchars($p['desc_prod'])); ?></p>
                        
                        <button type="button" class="btn btn-outline" onclick='ouvrirVisionnage(<?php echo json_encode($p); ?>, "<?php echo $lien_whatsapp; ?>")'>⊙ Présentation vidéo</button>
                        <a href="<?php echo $lien_whatsapp; ?>" target="_blank" class="btn btn-wa">💬 Obtenir le prix via WhatsApp</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- POP-UP : UNIQUEMENT LA VIDÉO ET LE BOUTON WHATSAPP -->
<div class="modal" id="modalVisionnage">
    <div class="modal-content">
        <span class="close-modal" onclick="fermerVisionnage()">&times;</span>
        
        <!-- Bloc Vidéo Unique (Sans titre, sans description) -->
        <div id="blocVid" style="display:none; margin-top: 10px;">
            <video id="modalVideo" class="modal-video" controls controlsList="nodownload"></video>
        </div>
        
        <div style="margin-top: 25px; text-align: center;">
            <a id="modalLienWA" href="" target="_blank" class="btn btn-wa" style="display:inline-block; width:auto; padding: 12px 30px;">💬 Obtenir le prix via WhatsApp</a>
        </div>
    </div>
</div>

<script>
function ouvrirVisionnage(p, lienWA) {
    document.getElementById('modalLienWA').href = lienWA;

    const blocVid = document.getElementById('blocVid');
    const video = document.getElementById('modalVideo');
    
    if (p.video_prod && p.video_prod.trim() !== '') {
        video.src = p.video_prod;
        blocVid.style.display = 'block';
    } else {
        blocVid.style.display = 'none';
        video.src = '';
    }

    document.getElementById('modalVisionnage').style.display = 'flex';
}

function fermerVisionnage() {
    document.getElementById('modalVisionnage').style.display = 'none';
    const video = document.getElementById('modalVideo');
    video.pause();
    video.src = ''; 
}
</script>

</body>
</html>
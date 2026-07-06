<?php
require_once 'config.php';

try {
    $query = $db->query("SELECT * FROM catalogue ORDER BY id_prod DESC");
    $catalogue = $query->fetchAll();
} catch (PDOException $e) {
    die("Erreur de chargement du catalogue : " . $e->getMessage());
}

$numero_whatsapp = "243856321879"; 
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BIOGAZELCO SARLU — Solutions Énergétiques</title>
    <!-- Intégration de polices modernes et d'icônes -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --brand-primary: #0f172a;    /* Bleu ardoise très sombre, ultra moderne */
            --brand-accent: #10b981;     /* Vert émeraude éclatant (Énergie Propre) */
            --brand-whatsapp: #22c55e;   /* Vert WhatsApp officiel */
            --bg-gradient: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            --card-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.05), 0 5px 15px -8px rgba(0, 0, 0, 0.05);
            --card-shadow-hover: 0 20px 40px -5px rgba(16, 185, 129, 0.1), 0 10px 20px -8px rgba(0, 0, 0, 0.08);
        }

        * {
            box-sizing: border-box;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--bg-gradient);
            color: #334155;
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }

        /* Hero Header haut de gamme */
        header {
            background: linear-gradient(180deg, var(--brand-primary) 0%, #1e293b 100%);
            color: white;
            padding: 60px 20px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -20%;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(16, 185, 129, 0.15) 0%, transparent 70%);
            border-radius: 50%;
        }

        header h1 {
            margin: 0;
            font-size: 36px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        header h1 span {
            color: var(--brand-accent);
        }

        header p {
            margin: 10px 0 0 0;
            font-size: 16px;
            color: #94a3b8;
            font-weight: 500;
        }

        /* Conteneur principal */
        .container {
            max-width: 1240px;
            margin: -40px auto 60px auto;
            padding: 0 20px;
            position: relative;
            z-index: 10;
        }

        /* Grille de cartes */
        .catalogue-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 30px;
        }

        /* Design de la Carte Produit */
        .product-card {
            background: #ffffff;
            border-radius: 20px;
            border: 1px solid rgba(226, 232, 240, 0.8);
            box-shadow: var(--card-shadow);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            position: relative;
        }

        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--card-shadow-hover);
            border-color: rgba(16, 185, 129, 0.3);
        }

        /* Zone image avec overlay subtil au survol */
        .image-wrapper {
            position: relative;
            width: 100%;
            height: 240px;
            overflow: hidden;
            background: #e2e8f0;
        }

        .product-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .product-card:hover .product-image {
            transform: scale(1.05);
        }

        .product-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(8px);
            color: var(--brand-accent);
            font-size: 12px;
            font-weight: 600;
            padding: 6px 14px;
            border-radius: 30px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        /* Corps de la carte */
        .product-content {
            padding: 25px;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }

        .product-title {
            margin: 0 0 12px 0;
            font-size: 22px;
            font-weight: 600;
            color: var(--brand-primary);
            letter-spacing: -0.5px;
        }

        .product-description {
            font-size: 14px;
            color: #64748b;
            line-height: 1.6;
            margin-bottom: 20px;
            flex-grow: 1;
        }

        /* Zone Prix */
        .price-tag {
            display: flex;
            align-items: baseline;
            gap: 6px;
            margin-bottom: 20px;
            padding-top: 15px;
            border-top: 1px solid #f1f5f9;
        }

        .price-amount {
            font-size: 26px;
            font-weight: 700;
            color: var(--brand-primary);
        }

        .price-currency {
            font-size: 14px;
            font-weight: 600;
            color: #64748b;
        }

        /* Boutons CTA Stylisés */
        .btn-group {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 14px;
            text-decoration: none;
            font-weight: 600;
            font-size: 15px;
            border-radius: 12px;
            cursor: pointer;
        }

        .btn-video {
            background-color: #f1f5f9;
            color: var(--brand-primary);
            border: 1px solid #e2e8f0;
        }

        .btn-video:hover {
            background-color: var(--brand-primary);
            color: white;
            border-color: var(--brand-primary);
        }

        .btn-whatsapp {
            background-color: var(--brand-whatsapp);
            color: white;
            box-shadow: 0 4px 12px rgba(34, 197, 94, 0.2);
        }

        .btn-whatsapp:hover {
            background-color: #16a34a;
            transform: scale(1.02);
            box-shadow: 0 6px 20px rgba(34, 197, 94, 0.3);
        }

        /* État vide */
        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: 60px;
            background: white;
            border-radius: 20px;
            box-shadow: var(--card-shadow);
            color: #64748b;
        }

        .empty-state i {
            font-size: 48px;
            color: #cbd5e1;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <!-- Inclusion du menu global -->
    <?php include_once 'menu.php'; ?>
    
    <header>
        <h1>BIOGAZELCO <span>SARLU</span></h1>
        <p>Solutions technologiques et éco-responsables en République Démocratique du Congo</p>
    </header>

    <div class="container">
        <div class="catalogue-grid">
            <?php if (empty($catalogue)): ?>
                <div class="empty-state">
                    <i class="fa-solid fa-leaf"></i>
                    <p>Notre catalogue d'innovations biogaz arrive très bientôt.</p>
                </div>
            <?php else: ?>
                <?php foreach ($catalogue as $item): ?>
                    <div class="product-card">
                        <div class="image-wrapper">
                            <span class="product-badge"><?php echo htmlspecialchars($item['type_catalogue']); ?></span>
                            <img class="product-image" src="<?php echo htmlspecialchars($item['img_prod']); ?>" alt="<?php echo htmlspecialchars($item['nom_prod']); ?>">
                        </div>
                        
                        <div class="product-content">
                            <h2 class="product-title"><?php echo htmlspecialchars($item['nom_prod']); ?></h2>
                            <p class="product-description"><?php echo htmlspecialchars($item['desc_prod']); ?></p>
                            
                            <div class="price-tag">
                                <span class="price-amount"><?php echo number_format($item['prix_prod'], 0, '.', ' '); ?></span>
                                <span class="price-currency">USD</span>
                            </div>

                            <div class="btn-group">
                                <a href="lecteur_video.php?id_prod=<?php echo $item['id_prod']; ?>" class="btn btn-video">
                                    <i class="fa-regular fa-circle-play"></i> Présentation vidéo
                                </a>

                                <?php 
                                $texte_message = "Bonjour BIOGAZELCO, je souhaite obtenir des informations concernant : " . $item['nom_prod'];
                                $url_whatsapp = "https://wa.me/" . $numero_whatsapp . "?text=" . rawurlencode($texte_message);
                                ?>
                                <a href="<?php echo $url_whatsapp; ?>" target="_blank" class="btn btn-whatsapp">
                                    <i class="fa-brands fa-whatsapp"></i> Commander l'offre
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>
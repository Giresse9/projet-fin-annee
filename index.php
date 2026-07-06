<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BIOGAZELCO SARLU — Accueil</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            margin: 0;
            background: #f8fafc;
            color: #1e293b;
        }
        .hero-section {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: white;
            padding: 100px 20px;
            text-align: center;
        }
        .hero-content {
            max-width: 800px;
            margin: 0 auto;
        }
        .hero-content h2 {
            font-size: 42px;
            font-weight: 700;
            margin-bottom: 20px;
        }
        .hero-content h2 span {
            color: #10b981;
        }
        .hero-content p {
            font-size: 18px;
            color: #94a3b8;
            margin-bottom: 40px;
            line-height: 1.6;
        }
        .cta-container {
            display: flex;
            justify-content: center;
            gap: 20px;
        }
        .btn-main {
            padding: 15px 30px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 12px;
            text-decoration: none;
            transition: all 0.2s;
        }
        .btn-primary {
            background: #10b981;
            color: white;
        }
        .btn-primary:hover {
            background: #059669;
        }
        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: 1px solid rgba(255,255,255,0.2);
        }
        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.2);
        }
    </style>
</head>
<body>

    <?php include_once 'menu.php'; ?>

    <section class="hero-section">
        <div class="hero-content">
            <h2>L'Énergie Verte pour un Avenir Durable en <span>RDC</span></h2>
            <p>BIOGAZELCO SARLU transforme vos déchets organiques en opportunités énergétiques. Découvrez nos installations de biogaz et nos services d'ingénierie sur-mesure pour les industries, fermes et ménages.</p>
            <div class="cta-container">
                <a href="catalogue.php" class="btn-main btn-primary"><i class="fa-solid fa-basket-shopping"></i> Explorer notre catalogue</a>
                <a href="a_propos.php" class="btn-main btn-secondary">En savoir plus</a>
            </div>
        </div>
    </section>

</body>
</html>
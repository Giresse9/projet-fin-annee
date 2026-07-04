<?php
require_once 'config.php';

// Récupération de tous les éléments du catalogue
$query = $db->query("SELECT * FROM catalogue ORDER BY id_prod DESC");
$catalogue = $query->fetchAll();

// Configuration du numéro WhatsApp de l'entreprise (Format international sans le +)
// Exemple pour la RDC : 243XXXXXXXXX
$numero_whatsapp = "243856321879"; 
?>

<div class="catalogue-container" style="display: flex; gap: 20px; flex-wrap: wrap;">
    <?php foreach ($catalogue as $item): ?>
        <div class="produit-card" style="border: 1px solid #ddd; padding: 15px; width: 300px; border-radius: 8px;">
            <!-- Affichage de l'image issue du dossier photos-produit/ -->
            <img src="<?php echo $item['img_prod']; ?>" alt="<?php echo $item['nom_prod']; ?>" style="width: 100%; height: 200px; object-fit: cover; border-radius: 4px;">
            
            <h3><?php echo $item['nom_prod']; ?></h3>
            <p><?php echo $item['desc_prod']; ?></p>
            <p><strong>Prix : </strong><?php echo number_format($item['prix_prod'], 2); ?> USD</p>
            
            <div class="actions-buttons" style="display: flex; flex-direction: column; gap: 10px; margin-top: 15px;">
                <!-- Bouton 1 : Redirection vers le lecteur vidéo -->
                <a href="lecteur_video.php?id_prod=<?php echo $item['id_prod']; ?>" style="background-color: #1a365d; color: white; text-align: center; padding: 8px; text-decoration: none; border-radius: 4px;">
                    ▶ Voir la vidéo explicative
                </a>

                <!-- Bouton 2 : Redirection vers le WhatsApp de l'Admin avec message pré-rempli -->
                <?php 
                $message_whatsapp = rawurlencode("Bonjour BIOGAZELCO, je suis intéressé par votre " . $item['type_catalogue'] . " : " . $item['nom_prod']);
                $url_whatsapp = "https://wa.me/" . $numero_whatsapp . "?text=" . $message_whatsapp;
                ?>
                <a href="<?php echo $url_whatsapp; ?>" target="_blank" style="background-color: #25D366; color: white; text-align: center; padding: 8px; text-decoration: none; border-radius: 4px; font-weight: bold;">
                    💬 Commander sur WhatsApp
                </a>
            </div>
        </div>

        
</div>

        <?php
        $lien_whatsapp_brut = "https://wa.me/" . $numero_whatsapp . "?text=" . rawurlencode("Intérêt pour : " . $item['nom_prod']);
        $url_qrcode = "https://chart.googleapis.com/chart?chs=150x150&cht=qr&chl=" . urlencode($lien_whatsapp_brut);
        ?>
        <!-- Affichage dans la carte du produit -->
        <div style="text-align: center; margin: 10px 0;">
            <img src="<?php echo $url_qrcode; ?>" alt="QR Code Commande" style="width: 100px; height: 100px;">
            <p style="font-size: 11px; color: #666;">Scannez pour commander</p>
    <?php endforeach; ?>
</div>
<?php
require_once 'config.php';

if (!isset($_SESSION['id_admin'])) {
    die("Accès refusé.");
}

if (isset($_GET['id_prod'])) {
    $id_prod = (int)$_GET['id_prod'];

    try {
        // 1. Récupérer d'abord les chemins des fichiers pour pouvoir les supprimer du disque
        $stmtSelect = $db->prepare("SELECT img_prod, video_prod FROM catalogue WHERE id_prod = :id");
        $stmtSelect->execute([':id' => $id_prod]);
        $produit = $stmtSelect->fetch();

        if ($produit) {
            // Supprimer le fichier image du dossier s'il existe
            if (file_exists($produit['img_prod'])) {
                unlink($produit['img_prod']);
            }
            // Supprimer le fichier vidéo du dossier s'il existe
            if (file_exists($produit['video_prod'])) {
                unlink($produit['video_prod']);
            }

            // 2. Supprimer la ligne de données dans la table MySQL
            $stmtDelete = $db->prepare("DELETE FROM catalogue WHERE id_prod = :id");
            $stmtDelete->execute([':id' => $id_prod]);

            echo "Produit et fichiers médias associés supprimés définitivement.";
        }
    } catch (PDOException $e) {
        die("Erreur de suppression : " . $e->getMessage());
    }
}
?>
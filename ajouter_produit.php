<?php
require_once 'config.php';

// Sécurité : Seul l'administrateur peut manipuler le catalogue
if (!isset($_SESSION['id_admin'])) {
    die("Accès refusé. Authentification requise.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Récupération et assainissement des champs textuels
    $nom_prod = htmlspecialchars(trim($_POST['nom_prod']), ENT_QUOTES, 'UTF-8');
    $desc_prod = htmlspecialchars(trim($_POST['desc_prod']), ENT_QUOTES, 'UTF-8');
    $prix_prod = (float)$_POST['prix_prod'];
    $type_catalogue = htmlspecialchars(trim($_POST['type_catalogue']), ENT_QUOTES, 'UTF-8'); // 'Produit' ou 'Service'
    $id_admin = $_SESSION['id_admin'];

    // Définition des répertoires de stockage physiques
    $dossier_photo = "photos-produit/";
    $dossier_video = "video-produit/";

    // Création automatique des dossiers s'ils n'existent pas encore
    if (!is_dir($dossier_photo)) mkdir($dossier_photo, 0755, true);
    if (!is_dir($dossier_video)) mkdir($dossier_video, 0755, true);

    $chemin_photo = "";
    $chemin_video = "";

    // 2. Traitement du fichier Image
    if (isset($_FILES['photo_file']) && $_FILES['photo_file']['error'] === UPLOAD_ERR_OK) {
        $ext_photo = pathinfo($_FILES['photo_file']['name'], PATHINFO_EXTENSION);
        // Génération d'un nom unique pour éviter les écrasements
        $nom_photo_unique = "img_" . uniqid() . "." . $ext_photo;
        $chemin_photo = $dossier_photo . $nom_photo_unique;
        
        move_uploaded_file($_FILES['photo_file']['tmp_name'], $chemin_photo);
    }

    // 3. Traitement du fichier Vidéo
    if (isset($_FILES['video_file']) && $_FILES['video_file']['error'] === UPLOAD_ERR_OK) {
        $ext_video = pathinfo($_FILES['video_file']['name'], PATHINFO_EXTENSION);
        $nom_video_unique = "vid_" . uniqid() . "." . $ext_video;
        $chemin_video = $dossier_video . $nom_video_unique;
        
        move_uploaded_file($_FILES['video_file']['tmp_name'], $chemin_video);
    }

    // 4. Insertion des métadonnées dans la base de données
    try {
        $stmt = $db->prepare("
            INSERT INTO catalogue (nom_prod, desc_prod, prix_prod, img_prod, video_prod, type_catalogue, id_admin)
            VALUES (:nom, :descr, :prix, :img, :vid, :type_cat, :admin)
        ");
        
        $stmt->execute([
            ':nom'      => $nom_prod,
            ':descr'    => $desc_prod,
            ':prix'     => $prix_prod,
            ':img'      => $chemin_photo,
            ':vid'      => $chemin_video,
            ':type_cat' => $type_catalogue,
            ':admin'    => $id_admin
        ]);

        echo "Le produit/service a été ajouté avec succès dans le catalogue !";
    } catch (PDOException $e) {
        die("Erreur lors de l'enregistrement en base de données : " . $e->getMessage());
    }
}
?>
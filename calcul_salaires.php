<?php
require_once 'config.php';

// Sécurité : Vérifier que l'utilisateur est connecté (Admin ou Agent)
if (!isset($_SESSION['id_admin']) && !isset($_SESSION['id_agent'])) {
    die("Accès refusé. Veuillez vous connecter.");
}

// Détermination de la période à calculer (par défaut le mois et l'année en cours)
$mois = isset($_GET['mois']) ? str_pad($_GET['mois'], 2, "0", STR_PAD_LEFT) : date('m');
$annee = isset($_GET['annee']) ? $_GET['annee'] : date('Y');

try {
    // 1. COMPTER LE NOMBRE TOTAL DE JOURS OUVRABLES DANS LE MOIS
    $stmtOuvrables = $db->prepare("
        SELECT COUNT(*) AS total_ouvrables 
        FROM journee 
        WHERE MONTH(date_jour) = :mois 
          AND YEAR(date_jour) = :annee 
          AND est_ouvrable = 1
    ");
    $stmtOuvrables->execute([':mois' => $mois, ':annee' => $annee]);
    $resOuvrables = $stmtOuvrables->fetch();
    $J_ouvrables = (int)$resOuvrables['total_ouvrables'];

    // S'il n'y a pas de jours ouvrables enregistrés, on arrête le script proprement
    if ($J_ouvrables === 0) {
        echo "<p>Aucune activité ouvrable n'a été enregistrée pour la période : $mois/$annee.</p>";
        exit;
    }

    // 2. PRÉPARER LA REQUÊTE DE SÉLECTION DU PERSONNEL
    // Si c'est l'Admin, il voit tout le monde. Si c'est un Agent, il ne voit que son salaire.
    if (isset($_SESSION['id_admin'])) {
        $stmtAgents = $db->query("SELECT id_agent, nom_agent, sal_base_agent FROM agent WHERE statut_banni = 0");
        $liste_agents = $stmtAgents->fetchAll();
    } else {
        $stmtAgents = $db->prepare("SELECT id_agent, nom_agent, sal_base_agent FROM agent WHERE id_agent = :id AND statut_banni = 0");
        $stmtAgents->execute([':id' => $_SESSION['id_agent']]);
        $liste_agents = $stmtAgents->fetchAll();
    }

    // 3. CALCUL ET TRAITEMENT LOGIQUE POUR CHAQUE AGENT
    $fiche_paie_mensuelle = [];

    foreach ($liste_agents as $agent) {
        $id_agent = $agent['id_agent'];
        $nom_agent = $agent['nom_agent'];
        $S_base = (float)$agent['sal_base_agent'];

        // Compter les présences effectives de l'agent sur les jours ouvrables du mois
        $stmtPresents = $db->prepare("
            SELECT COUNT(*) AS total_presents 
            FROM presence p
            JOIN journee j ON p.date_jour = j.date_jour
            WHERE p.id_agent = :id_agent 
              AND MONTH(p.date_jour) = :mois 
              AND YEAR(p.date_jour) = :annee 
              AND p.est_present = 1
              AND j.est_ouvrable = 1
        ");
        $stmtPresents->execute([
            ':id_agent' => $id_agent,
            ':mois'     => $mois,
            ':annee'    => $annee
        ]);
        $resPresents = $stmtPresents->fetch();
        $J_presents = (int)$resPresents['total_presents'];

        // Application de la formule : Taux de participation (T)
        $T = ($J_presents / $J_ouvrables) * 100;

        // Logique de régulation et attribution de la prime d'assiduité (2%)
        $prime = 0.00;
        $prime_accordee = false;

        if ($J_presents === $J_ouvrables) {
            $prime = $S_base * 0.02; // Calcul des 2% de prime d'assiduité
            $prime_accordee = true;
        }

        // Formule finale du Salaire Réel calculé au prorata
        $S_final = ($S_base * ($J_presents / $J_ouvrables)) + $prime;

        // Structure des données prêtes pour l'affichage Front-End (HTML/Vue)
        $fiche_paie_mensuelle[] = [
            'id_agent'       => $id_agent,
            'nom_agent'      => $nom_agent,
            'salaire_base'   => $S_base,
            'jours_presents' => $J_presents,
            'jours_ouvrables'=> $J_ouvrables,
            'taux_assiduite' => round($T, 2),
            'prime'          => $prime,
            'prime_badge'    => $prime_accordee,
            'salaire_final'  => round($S_final, 2)
        ];
    }

    // Pour des besoins de débogage ou d'API asynchrone (Fetch/AJAX), on peut retourner du JSON
    // Si ce script est inclus directement dans une page de rapport, la variable $fiche_paie_mensuelle est exploitable.
    
} catch (PDOException $e) {
    die("Erreur lors du calcul des fiches de paie : " . $e->getMessage());
}
?>
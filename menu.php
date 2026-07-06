<nav class="main-navbar">
    <div class="nav-container">
        <a href="index.php" class="nav-logo">BIOGAZELCO <span>SARLU</span></a>
        
        <!-- Bouton Burger visible uniquement sur mobile -->
        <button class="burger-menu-btn" aria-label="Ouvrir le menu" onclick="toggleMobileMenu()">
            <span></span>
            <span></span>
            <span></span>
        </button>

        <div class="nav-links" id="navLinks">
            <a href="index.php" class="nav-item"><i class="fa-solid fa-house"></i> Accueil</a>
            <a href="catalogue.php" class="nav-item"><i class="fa-solid fa-book-open"></i> Catalogue</a>
            <a href="a_propos.php" class="nav-item"><i class="fa-solid fa-circle-info"></i> À Propos</a>
            <a href="connexion.php" class="nav-btn-intranet"><i class="fa-solid fa-lock"></i> Intranet</a>
        </div>
    </div>
</nav>

<style>
    /* Styles de base de la Navbar */
    .main-navbar {
        background: #0f172a;
        padding: 15px 0;
        position: sticky;
        top: 0;
        z-index: 1000;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(8px);
    }
    .nav-container {
        max-width: 1240px;
        margin: 0 auto;
        padding: 0 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .nav-logo {
        color: white;
        text-decoration: none;
        font-size: 22px;
        font-weight: 700;
        letter-spacing: -0.5px;
    }
    .nav-logo span {
        color: #10b981;
    }
    .nav-links {
        display: flex;
        align-items: center;
        gap: 25px;
        transition: all 0.3s ease-in-out;
    }
    .nav-item {
        color: #94a3b8;
        text-decoration: none;
        font-size: 15px;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: color 0.2s ease;
    }
    .nav-item:hover {
        color: white;
    }
    .nav-btn-intranet {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        text-decoration: none;
        font-size: 14px;
        font-weight: 600;
        padding: 10px 20px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
        transition: all 0.2s ease;
    }
    .nav-btn-intranet:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 20px rgba(16, 185, 129, 0.3);
    }

    /* Style du Bouton Burger (Masqué par défaut sur PC) */
    .burger-menu-btn {
        display: none;
        flex-direction: column;
        justify-content: space-between;
        width: 24px;
        height: 18px;
        background: transparent;
        border: none;
        cursor: pointer;
        padding: 0;
        z-index: 1001;
    }
    .burger-menu-btn span {
        width: 100%;
        height: 2px;
        background-color: white;
        border-radius: 2px;
        transition: all 0.3s ease;
    }

    /* Animation du bouton Burger quand il est actif (X) */
    .burger-menu-btn.active span:nth-child(1) {
        transform: translateY(8px) rotate(45deg);
    }
    .burger-menu-btn.active span:nth-child(2) {
        opacity: 0;
    }
    .burger-menu-btn.active span:nth-child(3) {
        transform: translateY(-8px) rotate(-45deg);
    }

    /* ==========================================
       MEDIA QUERY : COMPORTEMENT MOBILE & TABLETTE 
       ========================================== */
    @media (max-width: 768px) {
        .burger-menu-btn {
            display: flex; /* Affichage du bouton burger */
        }

        .nav-links {
            position: fixed;
            top: 0;
            right: -100%; /* Caché à droite par défaut */
            width: 70%;
            max-width: 300px;
            height: 100vh;
            background-color: #0f172a;
            border-left: 1px solid rgba(255, 255, 255, 0.1);
            flex-direction: column;
            align-items: flex-start;
            padding: 80px 30px;
            gap: 30px;
            box-shadow: -10px 0 30px rgba(0, 0, 0, 0.5);
        }

        /* Quand la classe active est ajoutée via JS, le menu glisse vers l'intérieur */
        .nav-links.active {
            right: 0;
        }

        .nav-btn-intranet {
            width: 100%;
            justify-content: center;
        }
    }
</style>

<script>
    function toggleMobileMenu() {
        const navLinks = document.getElementById('navLinks');
        const burgerBtn = document.querySelector('.burger-menu-btn');
        
        // Alterne les classes pour afficher le menu et transformer le bouton en X
        navLinks.classList.toggle('active');
        burgerBtn.classList.toggle('active');
    }
</script>
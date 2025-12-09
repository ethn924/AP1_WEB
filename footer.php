    <footer class="site-footer">
        <div class="footer-content">
            <div class="footer-section">
                <h4>📚 Portail de Stage</h4>
                <p>Plateforme complète de gestion des comptes rendus de stage pour étudiants et enseignants. Suivez, évaluez et validez facilement tous les comptes rendus.</p>
            </div>
            <div class="footer-section">
                <h4>🔗 Navigation rapide</h4>
                <ul>
                    <li><a href="accueil.php">🏠 Accueil</a></li>
                    <li><a href="tutoriel.php">📚 Tutoriel</a></li>
                    <li><a href="perso.php">⚙️ Mon profil</a></li>
                    <li><a href="notifications.php">🔔 Notifications</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h4>✨ Fonctionnalités</h4>
                <ul>
                    <li><a href="liste_cr.php">📋 Comptes rendus</a></li>
                    <li><a href="recherche_cr.php">🔍 Recherche</a></li>
                    <li><a href="mon_stage.php">🏢 Mon stage</a></li>
                    <li><a href="export_cr.php">📥 Exporter</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-divider"></div>
        <div class="footer-bottom">
            <p>&copy; 2025 Portail de Gestion des Comptes Rendus de Stage</p>
            <p style="font-size: 0.8em; opacity: 0.8; margin-top: 8px;">Tous droits réservés | Plateforme éducative</p>
        </div>
    </footer>
    <style>
        .site-footer {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 60px 20px 40px;
            margin-top: 80px;
            border-top: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.15);
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto 40px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 50px;
        }

        .footer-section h4 {
            color: rgba(255, 255, 255, 0.95);
            margin-bottom: 20px;
            margin-top: 0;
            font-size: 1.15em;
            font-weight: 700;
            letter-spacing: 0.5px;
            padding-bottom: 10px;
            border-bottom: 2px solid rgba(255, 255, 255, 0.25);
        }

        .footer-section p {
            font-size: 0.9em;
            line-height: 1.8;
            opacity: 0.95;
            margin: 0;
        }

        .footer-section ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .footer-section ul li {
            margin-bottom: 12px;
        }

        .footer-section ul li a {
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-size: 0.95em;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .footer-section ul li a:hover {
            color: #fff;
            transform: translateX(6px);
            text-decoration: underline;
        }

        .footer-divider {
            height: 1px;
            background: rgba(255, 255, 255, 0.2);
            margin: 40px 0 30px;
        }

        .footer-bottom {
            max-width: 1200px;
            margin: 0 auto;
            text-align: center;
            font-size: 0.88em;
            opacity: 0.95;
        }

        .footer-bottom p {
            margin: 0;
            font-weight: 500;
            line-height: 1.6;
        }

        @media (max-width: 768px) {
            .footer-content {
                grid-template-columns: 1fr;
                gap: 30px;
            }

            .site-footer {
                padding: 50px 16px 35px;
                margin-top: 60px;
            }

            .footer-section h4 {
                font-size: 1.05em;
                margin-bottom: 16px;
            }
        }
    </style>
</body>
</html>

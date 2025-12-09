<?php
// Header et Navigation pour toutes les pages
// À inclure dans le <body> de chaque page

if (!isset($type)) {
    $type = isset($_SESSION['Stype']) ? $_SESSION['Stype'] : null;
}

if (!isset($bdd)) {
    include '_conf.php';
    $bdd = mysqli_connect($serveurBDD, $userBDD, $mdpBDD, $nomBDD);
}

$current_page = basename($_SERVER['PHP_SELF']);

$notif_count = 0;
$dernières_notifs = [];

if (isset($_SESSION['Sid']) && $bdd) {
    $user_id = intval($_SESSION['Sid']);
    
    $count_query = "SELECT COUNT(*) as cnt FROM notifications WHERE utilisateur_id = $user_id AND lue = 0";
    $count_result = mysqli_query($bdd, $count_query);
    if ($count_result) {
        $count_row = mysqli_fetch_assoc($count_result);
        $notif_count = $count_row['cnt'] ?? 0;
    }
    
    $notifs_query = "SELECT * FROM notifications WHERE utilisateur_id = $user_id ORDER BY date_creation DESC LIMIT 5";
    $notifs_result = mysqli_query($bdd, $notifs_query);
    if ($notifs_result) {
        while ($notif = mysqli_fetch_assoc($notifs_result)) {
            $dernières_notifs[] = $notif;
        }
    }
}
?>

<nav style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 12px rgba(0,0,0,0.15); margin-bottom: 20px; border-radius: 8px; flex-wrap: wrap; gap: 15px;">
    
    <!-- Logo/Title -->
    <div style="color: white; font-size: 1.3em; font-weight: bold; display: flex; align-items: center; gap: 10px;">
        <a href="accueil.php" style="color: white; text-decoration: none; display: flex; align-items: center; gap: 10px; transition: opacity 0.3s;">
            📚 Portail Étudiant
        </a>
    </div>
    
    <!-- Navigation Menu -->
    <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
        <!-- Menu Étudiant -->
        <?php if ($type == 0): ?>
            <a href="accueil.php" style="<?php echo ($current_page == 'accueil.php' ? 'background: rgba(255,255,255,0.3);' : ''); ?> color: white; padding: 8px 15px; border-radius: 5px; text-decoration: none; font-weight: 500; transition: all 0.3s;">📝 CRs</a>
            <a href="editer_cr.php" style="<?php echo ($current_page == 'editer_cr.php' ? 'background: rgba(255,255,255,0.3);' : ''); ?> color: white; padding: 8px 15px; border-radius: 5px; text-decoration: none; font-weight: 500; transition: all 0.3s;">✏️ Nouveau</a>
            <a href="liste_cr.php" style="<?php echo ($current_page == 'liste_cr.php' ? 'background: rgba(255,255,255,0.3);' : ''); ?> color: white; padding: 8px 15px; border-radius: 5px; text-decoration: none; font-weight: 500; transition: all 0.3s;">📋 Mes CRs</a>
            <a href="recherche_cr.php" style="<?php echo ($current_page == 'recherche_cr.php' ? 'background: rgba(255,255,255,0.3);' : ''); ?> color: white; padding: 8px 15px; border-radius: 5px; text-decoration: none; font-weight: 500; transition: all 0.3s;">🔍 Recherche</a>
            <a href="tableau_bord_eleve.php" style="<?php echo ($current_page == 'tableau_bord_eleve.php' ? 'background: rgba(255,255,255,0.3);' : ''); ?> color: white; padding: 8px 15px; border-radius: 5px; text-decoration: none; font-weight: 500; transition: all 0.3s;">📊 Tableau</a>
        
        <!-- Menu Enseignant -->
        <?php else: ?>
            <a href="accueil.php" style="<?php echo ($current_page == 'accueil.php' ? 'background: rgba(255,255,255,0.3);' : ''); ?> color: white; padding: 8px 15px; border-radius: 5px; text-decoration: none; font-weight: 500; transition: all 0.3s;">🏠 Accueil</a>
            <a href="liste_cr_prof.php" style="<?php echo ($current_page == 'liste_cr_prof.php' ? 'background: rgba(255,255,255,0.3);' : ''); ?> color: white; padding: 8px 15px; border-radius: 5px; text-decoration: none; font-weight: 500; transition: all 0.3s;">📋 Réviser</a>
            <a href="validations_cr.php" style="<?php echo ($current_page == 'validations_cr.php' ? 'background: rgba(255,255,255,0.3);' : ''); ?> color: white; padding: 8px 15px; border-radius: 5px; text-decoration: none; font-weight: 500; transition: all 0.3s;">✅ Valider</a>
            <a href="gestion_groupes.php" style="<?php echo ($current_page == 'gestion_groupes.php' ? 'background: rgba(255,255,255,0.3);' : ''); ?> color: white; padding: 8px 15px; border-radius: 5px; text-decoration: none; font-weight: 500; transition: all 0.3s;">👥 Groupes</a>
            <a href="gestion_rappels.php" style="<?php echo ($current_page == 'gestion_rappels.php' ? 'background: rgba(255,255,255,0.3);' : ''); ?> color: white; padding: 8px 15px; border-radius: 5px; text-decoration: none; font-weight: 500; transition: all 0.3s;">🔔 Rappels</a>
            <a href="tableau_bord_prof.php" style="<?php echo ($current_page == 'tableau_bord_prof.php' ? 'background: rgba(255,255,255,0.3);' : ''); ?> color: white; padding: 8px 15px; border-radius: 5px; text-decoration: none; font-weight: 500; transition: all 0.3s;">🎓 Tableau</a>
        <?php endif; ?>
    </div>
    
    <!-- Menus Communs -->
    <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
        <div style="position: relative; display: inline-block;">
            <button onclick="toggleNotificationsDropdown()" style="background: rgba(255,255,255,0.2); color: white; padding: 8px 15px; border-radius: 5px; border: none; font-weight: 500; cursor: pointer; transition: all 0.3s; display: flex; align-items: center; gap: 8px;">
                🔔 Notifs
                <?php if ($notif_count > 0): ?>
                    <span style="background: #ff4444; color: white; border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-size: 0.75em; font-weight: bold;"><?php echo min($notif_count, 9); ?><?php echo $notif_count > 9 ? '+' : ''; ?></span>
                <?php endif; ?>
            </button>
            
            <div id="notificationsDropdown" style="display: none; position: absolute; top: 45px; right: 0; background: white; border: 1px solid #ddd; border-radius: 8px; box-shadow: 0 8px 24px rgba(0,0,0,0.15); min-width: 320px; max-width: 420px; z-index: 1000;">
                <div style="padding: 12px 16px; border-bottom: 1px solid #eee; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 8px 8px 0 0; font-weight: 600;">
                    📬 Notifications récentes
                </div>
                
                <div style="max-height: 400px; overflow-y: auto;">
                    <?php if (!empty($dernières_notifs)): ?>
                        <?php foreach ($dernières_notifs as $notif): ?>
                            <div style="padding: 12px 16px; border-bottom: 1px solid #eee; cursor: pointer; transition: background 0.3s;" onmouseover="this.style.background='#f9f9f9'" onmouseout="this.style.background='white'" onclick="<?php echo $notif['lien'] ? 'window.location.href=\'' . htmlspecialchars($notif['lien']) . '\'' : 'window.location.href=\'marquer_lue.php?id=' . $notif['id'] . '\''; ?>">
                                <div style="display: flex; gap: 8px; align-items: flex-start;">
                                    <span style="font-size: 1.2em;">
                                        <?php 
                                            if (strpos($notif['titre'], 'CR') !== false) echo '📋';
                                            elseif (strpos($notif['titre'], 'validé') !== false || strpos($notif['titre'], 'Validé') !== false) echo '✅';
                                            elseif (strpos($notif['titre'], 'commentaire') !== false || strpos($notif['titre'], 'Commentaire') !== false) echo '💬';
                                            else echo '📢';
                                        ?>
                                    </span>
                                    <div style="flex: 1;">
                                        <div style="font-weight: 600; color: #333; font-size: 0.95em; margin-bottom: 4px;"><?php echo htmlspecialchars(substr($notif['titre'], 0, 40)); ?><?php echo strlen($notif['titre']) > 40 ? '...' : ''; ?></div>
                                        <div style="color: #666; font-size: 0.85em; margin-bottom: 4px;"><?php echo htmlspecialchars(substr($notif['message'], 0, 60)); ?><?php echo strlen($notif['message']) > 60 ? '...' : ''; ?></div>
                                        <div style="color: #999; font-size: 0.8em;"><?php echo date('d/m/Y H:i', strtotime($notif['date_creation'])); ?></div>
                                    </div>
                                    <?php if (!$notif['lue']): ?>
                                        <div style="width: 8px; height: 8px; background: #667eea; border-radius: 50%; margin-top: 4px;"></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="padding: 20px 16px; text-align: center; color: #999; font-size: 0.9em;">
                            <div style="font-size: 2em; margin-bottom: 8px;">📭</div>
                            Aucune notification
                        </div>
                    <?php endif; ?>
                </div>
                
                <div style="padding: 12px 16px; border-top: 1px solid #eee; text-align: center;">
                    <a href="notifications.php" style="color: #667eea; text-decoration: none; font-weight: 600; font-size: 0.9em; transition: color 0.3s;" onmouseover="this.style.color='#764ba2'" onmouseout="this.style.color='#667eea';">→ Voir toutes les notifications</a>
                </div>
            </div>
        </div>
        <a href="tutoriel.php" style="<?php echo ($current_page == 'tutoriel.php' ? 'background: rgba(255,255,255,0.3);' : ''); ?> color: white; padding: 8px 15px; border-radius: 5px; text-decoration: none; font-weight: 500; transition: all 0.3s;">📚 Tutoriel</a>
        <a href="perso.php" style="<?php echo ($current_page == 'perso.php' ? 'background: rgba(255,255,255,0.3);' : ''); ?> color: white; padding: 8px 15px; border-radius: 5px; text-decoration: none; font-weight: 500; transition: all 0.3s;">⚙️ Profil</a>
        <a href="logout.php" style="color: white; padding: 8px 15px; border-radius: 5px; text-decoration: none; font-weight: 500; transition: all 0.3s; background: rgba(220, 53, 69, 0.8);">🚪 Déco</a>
    </div>
</nav>

<script>
function toggleNotificationsDropdown() {
    const dropdown = document.getElementById('notificationsDropdown');
    if (dropdown) {
        if (dropdown.style.display === 'none') {
            dropdown.style.display = 'block';
        } else {
            dropdown.style.display = 'none';
        }
    }
}

document.addEventListener('click', function(event) {
    const dropdown = document.getElementById('notificationsDropdown');
    const notifButton = event.target.closest('button');
    
    if (dropdown && notifButton === null && !dropdown.contains(event.target)) {
        dropdown.style.display = 'none';
    }
});
</script>
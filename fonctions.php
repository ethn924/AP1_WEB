<?php
global $bdd;

function genererToken($l = 32) { return bin2hex(random_bytes($l)); }
function validerEmail($e) { return filter_var($e, FILTER_VALIDATE_EMAIL) !== false; }
function q($v) { global $bdd; return mysqli_real_escape_string($bdd, $v); }
function i($v) { return intval($v); }

function execQuery($sql) { global $bdd; return $bdd ? mysqli_query($bdd, $sql) : false; }
function fetchOne($sql) { $r = execQuery($sql); return $r ? mysqli_fetch_assoc($r) : null; }
function fetchAll($sql) { $r = execQuery($sql); $arr = []; while ($row = mysqli_fetch_assoc($r ?? new stdClass())) $arr[] = $row; return $arr; }

function creerNotification($uid, $type, $titre, $msg, $lien = null) {
    $uid = i($uid);
    $type = q($type);
    $titre = q($titre);
    $msg = q($msg);
    $lien = $lien ? "'" . q($lien) . "'" : 'NULL';
    return execQuery("INSERT INTO notifications (utilisateur_id, type, titre, message, lien) VALUES ($uid, '$type', '$titre', '$msg', $lien)");
}

function getNotificationsNonLues($uid) {
    return fetchAll("SELECT * FROM notifications WHERE utilisateur_id = " . i($uid) . " AND lue = 0 ORDER BY date_creation DESC LIMIT 10");
}

function marquerNotificationLue($nid) {
    return execQuery("UPDATE notifications SET lue = 1 WHERE id = " . i($nid));
}

function formaterTailleFichier($o) {
    $u = ['o', 'Ko', 'Mo', 'Go'];
    $o = max($o, 0);
    $p = floor(($o ? log($o) : 0) / log(1024));
    $p = min($p, count($u) - 1);
    $o /= pow(1024, $p);
    return round($o, 2) . ' ' . $u[$p];
}

function detecterTypeMime($c) {
    $f = finfo_open(FILEINFO_MIME_TYPE);
    $m = finfo_file($f, $c);
    finfo_close($f);
    return $m ?: 'application/octet-stream';
}

function sauvegarderFichier($f, $cid) {
    global $dossier_upload, $taille_max_fichier, $types_autorises;
    if ($f['error'] !== UPLOAD_ERR_OK) throw new Exception("Erreur upload: " . $f['error']);
    if ($f['size'] > $taille_max_fichier) throw new Exception("Fichier trop volumineux");
    
    $tm = detecterTypeMime($f['tmp_name']);
    if (!in_array($tm, $types_autorises)) throw new Exception("Type non autorisé: " . $tm);
    
    $nf = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9\._-]/', '_', $f['name']);
    $cp = $dossier_upload . $nf;
    
    if (!is_dir($dossier_upload)) mkdir($dossier_upload, 0755, true);
    if (!move_uploaded_file($f['tmp_name'], $cp)) throw new Exception("Erreur déplacement fichier");
    
    $cid = i($cid);
    $nf_e = q($f['name']);
    $tm_e = q($tm);
    $t = i($f['size']);
    $nf_e2 = q($nf);
    
    if (!execQuery("INSERT INTO pieces_jointes (cr_id, nom_fichier, type_mime, taille, donnees) VALUES ($cid, '$nf_e', '$tm_e', $t, '$nf_e2')")) {
        if (file_exists($cp)) unlink($cp);
        throw new Exception("Erreur sauvegarde BDD");
    }
    global $bdd;
    return mysqli_insert_id($bdd);
}

function getPiecesJointes($cid) { return fetchAll("SELECT * FROM pieces_jointes WHERE cr_id = " . i($cid) . " ORDER BY date_upload DESC"); }

function supprimerPieceJointe($pid) {
    global $dossier_upload;
    $pid = i($pid);
    $p = fetchOne("SELECT donnees FROM pieces_jointes WHERE id = $pid");
    if ($p) {
        if (file_exists($dossier_upload . $p['donnees'])) unlink($dossier_upload . $p['donnees']);
        return execQuery("DELETE FROM pieces_jointes WHERE id = $pid");
    }
    return false;
}

function getCommentaires($cid) {
    return fetchAll("SELECT c.*, u.nom, u.prenom FROM commentaires c JOIN utilisateur u ON c.professeur_id = u.num WHERE c.cr_id = " . i($cid) . " ORDER BY c.date_creation ASC");
}

function ajouterCommentaire($cid, $pid, $cmnt) {
    $cid = i($cid);
    $pid = i($pid);
    $cmnt = q($cmnt);
    return execQuery("INSERT INTO commentaires (cr_id, professeur_id, commentaire) VALUES ($cid, $pid, '$cmnt')");
}

function getStatutCR($cid) { return fetchOne("SELECT * FROM statuts_cr WHERE cr_id = " . i($cid) . " LIMIT 1"); }

function ajouterSauvegardeAuto($cid, $uid, $html, $desc = '') {
    $cid = i($cid);
    $uid = i($uid);
    $html = q($html);
    $desc = q($desc);
    return execQuery("INSERT INTO sauvegardes_auto (cr_id, utilisateur_id, contenu_html, description) VALUES ($cid, $uid, '$html', '$desc')");
}

function calculerAnalyticsGroupe($gid = null) {
    $gc = $gid ? " AND c.groupe_id = " . i($gid) : "";
    $row = fetchOne("SELECT COUNT(*) as total, SUM(CASE WHEN s.statut = 'soumis' THEN 1 ELSE 0 END) as soumis, SUM(CASE WHEN s.statut = 'evalue' THEN 1 ELSE 0 END) as evalues, SUM(CASE WHEN s.statut = 'approuve' THEN 1 ELSE 0 END) as approuves FROM cr c LEFT JOIN statuts_cr s ON c.num = s.cr_id WHERE c.archivé = 0 $gc");
    $t = $row['total'] ?? 0;
    return [
        'total_cr' => $t,
        'cr_soumis' => $row['soumis'] ?? 0,
        'cr_evalues' => $row['evalues'] ?? 0,
        'cr_approuves' => $row['approuves'] ?? 0,
        'taux_soumission' => ($t > 0) ? round(($row['soumis'] / $t) * 100, 1) : 0,
        'taux_evaluation' => ($t > 0) ? round(($row['evalues'] / $t) * 100, 1) : 0
    ];
}

function getMembresGroupe($gid) {
    return fetchAll("SELECT u.num, u.nom, u.prenom FROM membres_groupe mg JOIN utilisateur u ON mg.utilisateur_id = u.num WHERE mg.groupe_id = " . i($gid) . " ORDER BY u.nom, u.prenom");
}

function restaurerVersionCR($vid, $cid) {
    $v = fetchOne("SELECT * FROM versions_cr WHERE id = " . i($vid) . " AND cr_id = " . i($cid));
    if (!$v) return false;
    $html = q($v['contenu_html']);
    return execQuery("UPDATE cr SET contenu_html = '$html' WHERE num = " . i($cid));
}

function getVersionsCR($cid) { return fetchAll("SELECT * FROM versions_cr WHERE cr_id = " . i($cid) . " ORDER BY date_creation DESC"); }

function rechercherCR($f) {
    $cond = ["c.archivé = 0"];
    if (!empty($f['titre'])) $cond[] = "c.titre LIKE '%" . q($f['titre']) . "%'";
    if (!empty($f['statut'])) $cond[] = "s.statut = '" . q($f['statut']) . "'";
    if (!empty($f['groupe_id'])) $cond[] = "c.groupe_id = " . i($f['groupe_id']);
    if (!empty($f['utilisateur_id'])) $cond[] = "c.num_utilisateur = " . i($f['utilisateur_id']);
    if (!empty($f['professeur_id'])) $cond[] = "s.professeur_id = " . i($f['professeur_id']);
    if (!empty($f['date_debut'])) $cond[] = "c.date >= '" . q($f['date_debut']) . "'";
    if (!empty($f['date_fin'])) $cond[] = "c.date <= '" . q($f['date_fin']) . "'";
    
    $w = implode(" AND ", $cond);
    return fetchAll("SELECT DISTINCT c.* FROM cr c LEFT JOIN statuts_cr s ON c.num = s.cr_id WHERE $w ORDER BY c.date DESC");
}

function formatDateFrench($d) {
    if (!$d) return "Aucun";
    $eng_m = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    $fr_m = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
    $eng_d = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
    $fr_d = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
    $ds = date('l d F Y', strtotime($d));
    return str_replace($eng_d, $fr_d, str_replace($eng_m, $fr_m, $ds));
}

function formatDateTimeFrench($dt) {
    if (!$dt) return "Aucun";
    $eng_m = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    $fr_m = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
    $eng_d = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
    $fr_d = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
    $ds = date('l d F Y à H\hi', strtotime($dt));
    return str_replace($eng_d, $fr_d, str_replace($eng_m, $fr_m, $ds));
}

function afficherHeaderPage($e, $t, $d) {
    $typ = $_SESSION['Stype'] ?? 1;
    $nm = ($_SESSION['Sprenom'] ?? '') . ' ' . ($_SESSION['Snom'] ?? '');
    $page = $typ == 0 ? 'tableau_bord_eleve.php' : 'tableau_bord_prof.php';
    echo "<div class='header-page'>
        <h1>$e " . htmlspecialchars($t) . "</h1>
        <p>" . htmlspecialchars($d) . "</p>
        <div class='user-info'>
            <span><strong>Connecté :</strong> " . htmlspecialchars($nm) . " " . ($typ == 0 ? '(Étudiant)' : '(Enseignant)') . "</span>
            <a href='$page' class='retour-btn'>📊 Tableau</a>
        </div>
    </div>";
}

function validerCR($cr_id, $professeur_id, $valide, $commentaire = '') {
    $cr_id = i($cr_id);
    $professeur_id = i($professeur_id);
    $valide = i($valide);
    $commentaire = q($commentaire);
    return execQuery("INSERT INTO validations_cr (cr_id, professeur_id, valide, commentaire_validation) VALUES ($cr_id, $professeur_id, $valide, '$commentaire') ON DUPLICATE KEY UPDATE valide = $valide, commentaire_validation = '$commentaire', date_validation = NOW()");
}

function getValidationCR($cr_id) { return fetchOne("SELECT * FROM validations_cr WHERE cr_id = " . i($cr_id) . " LIMIT 1"); }

function afficherNavigation() {
    if (!isset($_SESSION['Stype'])) return;
    $db = $_SESSION['Stype'] == 0 ? 'tableau_bord_eleve.php' : 'tableau_bord_prof.php';
    $ic = $_SESSION['Stype'] == 0 ? '📊' : '👨‍🏫';
    echo "<div style='margin-bottom: 20px; border-bottom: 2px solid #ddd; padding: 15px; background: #f8f9fa; border-radius: 4px;'>
        <p style='margin: 5px 0;'><a href='accueil.php' style='color: #007bff; text-decoration: none; font-weight: bold;'>← Retour</a></p>
        <p style='margin: 5px 0;'><a href='$db' style='color: #28a745; text-decoration: none; font-weight: bold;'>$ic Tableau</a></p>
    </div>";
}

function afficherHeaderNavigation() {
    $typ = $_SESSION['Stype'] ?? 1;
    $nm = htmlspecialchars(substr(($_SESSION['Sprenom'] ?? '') . ' ' . ($_SESSION['Snom'] ?? ''), 0, 20));
    
    $notif_count = 0;
    $dernières_notifs = [];
    
    if (isset($_SESSION['Sid'])) {
        $user_id = intval($_SESSION['Sid']);
        
        require_once __DIR__ . '/_conf.php';
        $conn = @mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($conn) {
            $count_query = "SELECT COUNT(*) as cnt FROM notifications WHERE utilisateur_id = $user_id AND lue = 0";
            $count_result = mysqli_query($conn, $count_query);
            if ($count_result) {
                $count_row = mysqli_fetch_assoc($count_result);
                $notif_count = $count_row['cnt'] ?? 0;
            }
            
            $notifs_query = "SELECT * FROM notifications WHERE utilisateur_id = $user_id ORDER BY date_creation DESC LIMIT 5";
            $notifs_result = mysqli_query($conn, $notifs_query);
            if ($notifs_result) {
                while ($notif = mysqli_fetch_assoc($notifs_result)) {
                    $dernières_notifs[] = $notif;
                }
            }
            mysqli_close($conn);
        }
    }
    ?>
    <nav class="global-header">
        <div class="nav-container">
            <div class="nav-left">
                <a href="accueil.php" class="nav-logo">🏠</a>
                <div class="nav-links">
                    <?php if ($typ == 0): ?>
                        <a href="editer_cr.php">📝 Créer CR</a>
                        <a href="liste_cr.php">📋 Mes CRs</a>
                        <a href="mon_stage.php">🏢 Stage</a>
                        <a href="tutoriel.php">📚 Tutoriel</a>
                        <a href="perso.php">⚙️ Profil</a>
                    <?php else: ?>
                        <a href="liste_cr_prof.php">📋 CRs</a>
                        <a href="validations_cr.php">✅ Validations</a>
                        <a href="gestion_groupes.php">👥 Groupes</a>
                        <a href="liste_eleves.php">👨‍🎓 Élèves</a>
                        <a href="tutoriel.php">📚 Tutoriel</a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="nav-right">
                <div style="position: relative; display: inline-block;">
                    <button onclick="toggleNotificationsDropdown()" style="background: transparent; border: none; color: white; padding: 8px 15px; border-radius: 5px; cursor: pointer; transition: all 0.3s; display: flex; align-items: center; gap: 8px; font-size: 1.1em;">
                        🔔
                        <?php if ($notif_count > 0): ?>
                            <span style="background: #ff4444; color: white; border-radius: 50%; width: 18px; height: 18px; display: flex; align-items: center; justify-content: center; font-size: 0.65em; font-weight: bold;"><?php echo min($notif_count, 9); ?><?php echo $notif_count > 9 ? '+' : ''; ?></span>
                        <?php endif; ?>
                    </button>
                    
                    <div id="notificationsDropdown" style="display: none; position: absolute; top: 45px; right: 0; background: white; border: 1px solid #ddd; border-radius: 8px; box-shadow: 0 8px 24px rgba(0,0,0,0.15); min-width: 320px; max-width: 420px; z-index: 1000;">
                        <div style="padding: 12px 16px; border-bottom: 1px solid #eee; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 8px 8px 0 0; font-weight: 600; font-size: 0.95em;">
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
                            <a href="notifications.php" style="color: #667eea; text-decoration: none; font-weight: 600; font-size: 0.9em; transition: color 0.3s;" onmouseover="this.style.color='#764ba2'" onmouseout="this.style.color='#667eea';">→ Voir toutes</a>
                        </div>
                    </div>
                </div>
                <span class="user-display">👤 <?php echo $nm; ?></span>
                <a href="logout.php" class="logout-btn">🚪</a>
            </div>
        </div>
    </nav>
    
    <script>
    function toggleNotificationsDropdown() {
        const dropdown = document.getElementById('notificationsDropdown');
        if (dropdown) {
            dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
        }
    }
    
    document.addEventListener('click', function(event) {
        const dropdown = document.getElementById('notificationsDropdown');
        if (dropdown && !event.target.closest('button') && !dropdown.contains(event.target)) {
            dropdown.style.display = 'none';
        }
    });
    </script>
    <?php
}
?>

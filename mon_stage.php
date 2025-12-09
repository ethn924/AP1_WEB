<?php
session_start();
include '_conf.php';
include 'fonctions.php';

isset($_SESSION['Sid']) && $_SESSION['Stype'] == 0 or die(header("Location: index.php"));
$bdd = mysqli_connect($serveurBDD, $userBDD, $mdpBDD, $nomBDD) or die("Erreur connexion BDD");
$uid = intval($_SESSION['Sid']);
$msg = $err = '';

$q = "SELECT s.*, t.num as tnum, t.nom as tn, t.prenom as tp, t.tel as tt, t.email as te FROM utilisateur u LEFT JOIN stage s ON u.num_stage = s.num LEFT JOIN tuteur t ON s.num_tuteur = t.num WHERE u.num = $uid";
$cd = mysqli_fetch_assoc(mysqli_query($bdd, $q));

$sn = $cd['nom'] ?? ''; $sa = $cd['adresse'] ?? ''; $scp = $cd['CP'] ?? ''; $sv = $cd['ville'] ?? ''; $st = $cd['tel'] ?? ''; $sl = $cd['libelleStage'] ?? ''; $se = $cd['email'] ?? '';
$tn = $cd['tn'] ?? ''; $tp = $cd['tp'] ?? ''; $tt = $cd['tt'] ?? ''; $te = $cd['te'] ?? '';

function ve($e) { return filter_var($e, FILTER_VALIDATE_EMAIL) !== false; }
function vp($p) { $p = preg_replace('/[^0-9]/', '', $p); return preg_match('/^0[1-9][0-9]{8}$/', $p); }
function vpc($p) { return preg_match('/^[0-9]{5}$/', $p); }
function vn($n) { return !empty(trim($n)) && preg_match('/^[a-zA-ZÀ-ÿ\s\-\.\']+$/', $n); }
function va($a) { return !empty(trim($a)) && strlen($a) >= 5; }
function vt($t) { return !empty(trim($t)) && strlen($t) >= 10; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sn = trim($_POST['stage_nom'] ?? ''); $sa = trim($_POST['stage_adresse'] ?? ''); $scp = trim($_POST['stage_cp'] ?? ''); $sv = trim($_POST['stage_ville'] ?? '');
    $st = trim($_POST['stage_tel'] ?? ''); $sl = trim($_POST['stage_libelle'] ?? ''); $se = trim($_POST['stage_email'] ?? '');
    $tn = trim($_POST['tuteur_nom'] ?? ''); $tp = trim($_POST['tuteur_prenom'] ?? ''); $tt = trim($_POST['tuteur_tel'] ?? ''); $te = trim($_POST['tuteur_email'] ?? '');

    $e = [];
    !vn($sn) && ($e[] = "Nom d'entreprise invalide");
    !va($sa) && ($e[] = "Adresse: minimum 5 caractères");
    !vpc($scp) && ($e[] = "Code postal: exactement 5 chiffres");
    !vn($sv) && ($e[] = "Nom de ville invalide");
    !vp($st) && ($e[] = "Téléphone entreprise invalide");
    !ve($se) && ($e[] = "Email entreprise invalide");
    !vt($sl) && ($e[] = "Libellé stage: minimum 10 caractères");
    !vn($tn) && ($e[] = "Nom du tuteur invalide");
    !vn($tp) && ($e[] = "Prénom du tuteur invalide");
    !vp($tt) && ($e[] = "Téléphone tuteur invalide");
    !ve($te) && ($e[] = "Email tuteur invalide");

    if (!empty($e)) {
        $err = "Erreurs : " . implode(", ", $e);
    } else {
        $sn = q($sn); $sa = q($sa); $scp = q($scp); $sv = q($sv);
        $st = q($st); $sl = q($sl); $se = q($se);
        $tn = q($tn); $tp = q($tp); $tt = q($tt); $te = q($te);

        mysqli_begin_transaction($bdd);
        try {
            if ($cd && ($cd['num'] ?? 0) && ($cd['tnum'] ?? 0)) {
                mysqli_query($bdd, "UPDATE tuteur SET nom = '$tn', prenom = '$tp', tel = '$tt', email = '$te' WHERE num = " . $cd['tnum']);
                mysqli_query($bdd, "UPDATE stage SET nom = '$sn', adresse = '$sa', CP = '$scp', ville = '$sv', tel = '$st', libelleStage = '$sl', email = '$se' WHERE num = " . $cd['num']);
                $msg = "Stage mis à jour avec succès !";
            } elseif ($cd && ($cd['num'] ?? 0) && !($cd['tnum'] ?? 0)) {
                mysqli_query($bdd, "INSERT INTO tuteur (nom, prenom, tel, email) VALUES ('$tn', '$tp', '$tt', '$te')");
                $tid = mysqli_insert_id($bdd);
                mysqli_query($bdd, "UPDATE stage SET nom = '$sn', adresse = '$sa', CP = '$scp', ville = '$sv', tel = '$st', libelleStage = '$sl', email = '$se', num_tuteur = $tid WHERE num = " . $cd['num']);
                $msg = "Stage mis à jour avec succès !";
            } else {
                mysqli_query($bdd, "INSERT INTO tuteur (nom, prenom, tel, email) VALUES ('$tn', '$tp', '$tt', '$te')");
                $tid = mysqli_insert_id($bdd);
                mysqli_query($bdd, "INSERT INTO stage (nom, adresse, CP, ville, tel, libelleStage, email, num_tuteur) VALUES ('$sn', '$sa', '$scp', '$sv', '$st', '$sl', '$se', $tid)");
                $sid = mysqli_insert_id($bdd);
                mysqli_query($bdd, "UPDATE utilisateur SET num_stage = $sid WHERE num = $uid");
                $msg = "Stage enregistré avec succès !";
            }
            mysqli_commit($bdd);
            $cd = mysqli_fetch_assoc(mysqli_query($bdd, $q));
            $sn = $cd['nom'] ?? ''; $sa = $cd['adresse'] ?? ''; $scp = $cd['CP'] ?? ''; $sv = $cd['ville'] ?? ''; $st = $cd['tel'] ?? ''; $sl = $cd['libelleStage'] ?? ''; $se = $cd['email'] ?? '';
            $tn = $cd['tn'] ?? ''; $tp = $cd['tp'] ?? ''; $tt = $cd['tt'] ?? ''; $te = $cd['te'] ?? '';
        } catch (Exception $e) {
            mysqli_rollback($bdd);
            $err = "Erreur : " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Stage</title>
    <link rel="stylesheet" href="global-header.css">
    <link rel="stylesheet" href="common.css">
    <script>
        function formatPhone(input) {
            let value = input.value.replace(/[^0-9]/g, '').substring(0, 10);
            if (value.length > 0) {
                let formatted = '';
                for (let i = 0; i < value.length; i++) {
                    if (i > 0 && i % 2 === 0) formatted += ' ';
                    formatted += value[i];
                }
                input.value = formatted;
            }
        }

        function limitPostalCode(input) {
            input.value = input.value.replace(/[^0-9]/g, '').substring(0, 5);
        }

        function restrictInput(event, isNumeric) {
            const key = event.key;
            if (isNumeric && !/[\d]|Backspace|Delete|Tab|Escape|Enter/.test(key)) {
                event.preventDefault();
            }
        }
    </script>
</head>
<body>
    <?php afficherHeaderNavigation(); ?>
    <?php afficherHeaderPage('🏢', 'Mon Stage', 'Gérez vos informations de stage et votre tuteur'); ?>

    <div class="container">
        <?php if ($msg): ?><div class="message-box message-success">✅ <?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
        <?php if ($err): ?><div class="message-box message-error">❌ <?php echo htmlspecialchars($err); ?></div><?php endif; ?>

        <div style="text-align: center; color: white; margin-bottom: 20px; font-size: 0.95em;">
            <strong>*</strong> Tous les champs sont obligatoires.
        </div>

        <form method="POST">
            <div class="profile-grid-2">
                <div class="profile-section">
                    <h3>🏢 Informations de l'entreprise</h3>
                    <div class="form-group">
                        <label for="stage_nom">Nom de l'entreprise <span style="color: #dc3545;">*</span></label>
                        <input type="text" id="stage_nom" name="stage_nom" value="<?php echo htmlspecialchars($sn); ?>" required placeholder="Nom de l'entreprise">
                    </div>
                    <div class="form-group">
                        <label for="stage_adresse">Adresse <span style="color: #dc3545;">*</span></label>
                        <input type="text" id="stage_adresse" name="stage_adresse" value="<?php echo htmlspecialchars($sa); ?>" required minlength="5" placeholder="Adresse complète">
                    </div>
                    <div class="form-group">
                        <label for="stage_cp">Code postal <span style="color: #dc3545;">*</span></label>
                        <input type="text" id="stage_cp" name="stage_cp" value="<?php echo htmlspecialchars($scp); ?>" required pattern="[0-9]{5}" oninput="limitPostalCode(this)" maxlength="5" placeholder="75000">
                    </div>
                    <div class="form-group">
                        <label for="stage_ville">Ville <span style="color: #dc3545;">*</span></label>
                        <input type="text" id="stage_ville" name="stage_ville" value="<?php echo htmlspecialchars($sv); ?>" required placeholder="Ville">
                    </div>
                    <div class="form-group">
                        <label for="stage_tel">Téléphone <span style="color: #dc3545;">*</span></label>
                        <input type="text" id="stage_tel" name="stage_tel" value="<?php echo htmlspecialchars($st); ?>" required pattern="0[1-9][0-9 .-]{7,}" oninput="formatPhone(this)" placeholder="01 23 45 67 89">
                    </div>
                    <div class="form-group">
                        <label for="stage_email">Email <span style="color: #dc3545;">*</span></label>
                        <input type="email" id="stage_email" name="stage_email" value="<?php echo htmlspecialchars($se); ?>" required placeholder="entreprise@domaine.fr">
                    </div>
                </div>

                <div class="profile-section">
                    <h3>👤 Informations du tuteur</h3>
                    <div class="form-group">
                        <label for="tuteur_nom">Nom <span style="color: #dc3545;">*</span></label>
                        <input type="text" id="tuteur_nom" name="tuteur_nom" value="<?php echo htmlspecialchars($tn); ?>" required placeholder="Nom du tuteur">
                    </div>
                    <div class="form-group">
                        <label for="tuteur_prenom">Prénom <span style="color: #dc3545;">*</span></label>
                        <input type="text" id="tuteur_prenom" name="tuteur_prenom" value="<?php echo htmlspecialchars($tp); ?>" required placeholder="Prénom du tuteur">
                    </div>
                    <div class="form-group">
                        <label for="tuteur_tel">Téléphone <span style="color: #dc3545;">*</span></label>
                        <input type="text" id="tuteur_tel" name="tuteur_tel" value="<?php echo htmlspecialchars($tt); ?>" required pattern="0[1-9][0-9 .-]{7,}" oninput="formatPhone(this)" placeholder="01 23 45 67 89">
                    </div>
                    <div class="form-group">
                        <label for="tuteur_email">Email <span style="color: #dc3545;">*</span></label>
                        <input type="email" id="tuteur_email" name="tuteur_email" value="<?php echo htmlspecialchars($te); ?>" required placeholder="tuteur@domaine.fr">
                    </div>
                </div>
            </div>

            <div class="profile-section full-width">
                <h3>📝 Libellé du stage / Mission</h3>
                <div class="form-group">
                    <label for="stage_libelle">Décrivez votre mission <span style="color: #dc3545;">*</span></label>
                    <textarea id="stage_libelle" name="stage_libelle" required minlength="10" placeholder="Décrivez votre mission, vos responsabilités et les tâches que vous réalisiez..." style="min-height: 150px;"></textarea>
                </div>
            </div>

            <div class="link-group">
                <button type="submit" class="btn-update">💾 Enregistrer</button>
                <a href="accueil.php" class="retour-btn">← Retour à l'accueil</a>
            </div>
        </form>
    </div>
    <?php include 'footer.php'; ?>

<?php
include 'auth.php';
include 'connexion.php';

$message = "";
$message_type = "";

// ================= 1. AJOUT UTILISATEUR =================
if(isset($_POST['ajouter'])){
    $nom_utilisateur = trim($_POST['nom_utilisateur']);
    $mot_de_passe = password_hash($_POST['mot_de_passe'], PASSWORD_DEFAULT);
    $role = $_POST['role'];

    $stmt = $conn->prepare("INSERT INTO utilisateur (nom_utilisateur, mot_de_passe, role) VALUES(?,?,?)");
    if($stmt){
        $stmt->bind_param("sss", $nom_utilisateur, $mot_de_passe, $role);
        if($stmt->execute()){
            $message = "Utilisateur ajouté avec succès !";
            $message_type = "success";
        } else {
            $message = "Erreur lors de l'ajout de l'utilisateur.";
            $message_type = "danger";
        }
    }
}

// ================= 1.2. MODIFICATION UTILISATEUR PAR L'ADMIN =================
if(isset($_POST['modifier_utilisateur'])){
    $id_user = intval($_POST['id_utilisateur']);
    $nom_utilisateur = trim($_POST['nom_utilisateur']);
    $role = $_POST['role'];
    $nouveau_pass = $_POST['mot_de_passe'];

    if(!empty($nouveau_pass)){
        // Mise à jour avec nouveau mot de passe
        $pass_hash = password_hash($nouveau_pass, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE utilisateur SET nom_utilisateur=?, role=?, mot_de_passe=? WHERE id_utilisateur=?");
        $stmt->bind_param("sssi", $nom_utilisateur, $role, $pass_hash, $id_user);
    } else {
        // Mise à jour sans toucher au mot de passe
        $stmt = $conn->prepare("UPDATE utilisateur SET nom_utilisateur=?, role=? WHERE id_utilisateur=?");
        $stmt->bind_param("ssi", $nom_utilisateur, $role, $id_user);
    }

    if($stmt && $stmt->execute()){
        $message = "Utilisateur modifié avec succès !";
        $message_type = "success";
    } else {
        $message = "Erreur lors de la modification de l'utilisateur.";
        $message_type = "danger";
    }
}

// Récupération des données pour l'édition d'un utilisateur
$user_to_edit = null;
if(isset($_GET['editer'])){
    $id_edit = intval($_GET['editer']);
    $stmt_edit = $conn->prepare("SELECT * FROM utilisateur WHERE id_utilisateur = ?");
    $stmt_edit->bind_param("i", $id_edit);
    $stmt_edit->execute();
    $user_to_edit = $stmt_edit->get_result()->fetch_assoc();
}

// ================= 2. SUPPRESSION UTILISATEUR AVEC CORBEILLE =================
if(isset($_GET['supprimer'])){
    $id = intval($_GET['supprimer']);
    
    $stmt_get = $conn->prepare("SELECT nom_utilisateur FROM utilisateur WHERE id_utilisateur = ?");
    $stmt_get->bind_param("i", $id);
    $stmt_get->execute();
    $res_user = $stmt_get->get_result()->fetch_assoc();

    if($res_user){
        $type_element = "Utilisateur";
        $nom_element = $res_user['nom_utilisateur'];
        $date_actuelle = date('Y-m-d H:i:s');

        $stmt_corbeille = $conn->prepare("INSERT INTO corbeille (type_element, nom_element, date_suppression) VALUES (?, ?, ?)");
        $stmt_corbeille->bind_param("sss", $type_element, $nom_element, $date_actuelle);
        $stmt_corbeille->execute();

        $stmt_del = $conn->prepare("DELETE FROM utilisateur WHERE id_utilisateur = ?");
        $stmt_del->bind_param("i", $id);
        $stmt_del->execute();

        $message = "Utilisateur déplacé dans la corbeille.";
        $message_type = "warning";
    }
}

// ================= 3. RESTAURATION DEPUIS LA CORBEILLE =================
if(isset($_GET['restaurer'])){
    $id_corbeille = intval($_GET['restaurer']);

    $stmt_get = $conn->prepare("SELECT * FROM corbeille WHERE id_corbeille = ?");
    $stmt_get->bind_param("i", $id_corbeille);
    $stmt_get->execute();
    $res_item = $stmt_get->get_result()->fetch_assoc();

    if($res_item){
        $restored = false;

        switch($res_item['type_element']){
            case 'Utilisateur':
                $nom = $res_item['nom_element'];
                $pass_defaut = password_hash("123456", PASSWORD_DEFAULT);
                $role_defaut = "Gestionnaire";

                $stmt_ins = $conn->prepare("INSERT INTO utilisateur (nom_utilisateur, mot_de_passe, role) VALUES (?, ?, ?)");
                $stmt_ins->bind_param("sss", $nom, $pass_defaut, $role_defaut);
                $restored = $stmt_ins->execute();
                break;
        }

        if($restored){
            $stmt_del_corb = $conn->prepare("DELETE FROM corbeille WHERE id_corbeille = ?");
            $stmt_del_corb->bind_param("i", $id_corbeille);
            $stmt_del_corb->execute();

            $message = "Élément restauré avec succès ! (Mot de passe temporaire : 123456)";
            $message_type = "success";
        } else {
            $message = "Impossible de restaurer cet élément.";
            $message_type = "danger";
        }
    }
}

// ================= 4. MISE À JOUR DU PROFIL PERSONNEL =================
if(isset($_POST['update_profile'])){
    $nouveau_nom = trim($_POST['mon_nom']);
    $ancien_pass = $_POST['ancien_pass'];
    $nouveau_pass = $_POST['nouveau_pass'];
    $user_id = $_SESSION['user_id'] ?? 1;

    if(!empty($nouveau_pass)){
        $stmt = $conn->prepare("SELECT mot_de_passe FROM utilisateur WHERE id_utilisateur=?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();

        if($res && password_verify($ancien_pass, $res['mot_de_passe'])){
            $pass_hash = password_hash($nouveau_pass, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE utilisateur SET nom_utilisateur=?, mot_de_passe=? WHERE id_utilisateur=?");
            $stmt->bind_param("ssi", $nouveau_nom, $pass_hash, $user_id);
            $stmt->execute();
            $message = "Profil et mot de passe mis à jour avec succès !";
            $message_type = "success";
        } else {
            $message = "L'ancien mot de passe est incorrect !";
            $message_type = "danger";
        }
    } else {
        $stmt = $conn->prepare("UPDATE utilisateur SET nom_utilisateur=? WHERE id_utilisateur=?");
        $stmt->bind_param("si", $nouveau_nom, $user_id);
        $stmt->execute();
        $message = "Nom d'utilisateur mis à jour !";
        $message_type = "success";
    }
}

// ================= 5. ENREGISTRER CONFIGURATION GÉNÉRALE =================
if(isset($_POST['save_config'])){
    $_SESSION['config_nom_entreprise'] = $_POST['nom_entreprise'] ?? 'Gestion Immobilière';
    $_SESSION['config_devise'] = $_POST['devise'] ?? '$';
    $_SESSION['config_format_date'] = $_POST['format_date'] ?? 'd/m/Y';

    $message = "Configuration du système sauvegardée !";
    $message_type = "success";
}

// ================= 6. VIDER LA CORBEILLE =================
if(isset($_POST['vider_corbeille'])){
    $conn->query("TRUNCATE TABLE corbeille");
    $message = "La corbeille a été entièrement vidée !";
    $message_type = "warning";
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Paramètres du Système</title>

<style>
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    font-family:Arial, sans-serif;
    min-height:100vh;
    padding:30px;
    color:white;
    display:flex;
    flex-direction:column;
    align-items:center;
    background:
    linear-gradient(rgba(0,0,0,0.45), rgba(0,0,0,0.45)),
    url('https://images.unsplash.com/photo-1516321318423-f06f85e504b3?q=80&w=2070&auto=format&fit=crop')
    no-repeat center center/cover fixed;
}

body::before{
    content:"";
    position:fixed;
    inset:0;
    backdrop-filter:blur(8px);
    z-index:-1;
}

.top-bar {
    width: 100%;
    max-width: 1200px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.btn-back {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 22px;
    border-radius: 15px;
    background: rgba(255, 255, 255, 0.12);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: white;
    text-decoration: none;
    font-weight: bold;
    font-size: 15px;
    transition: 0.3s ease;
}

.btn-back:hover {
    background: rgba(255, 255, 255, 0.25);
    transform: translateX(-5px);
}

h1{
    text-align:center;
    font-size:36px;
    margin-bottom: 25px;
}

.alert {
    width: 100%;
    max-width: 1200px;
    padding: 15px;
    border-radius: 12px;
    margin-bottom: 20px;
    text-align: center;
    font-weight: bold;
}
.alert-success { background: rgba(76, 175, 80, 0.85); border: 1px solid #4caf50; }
.alert-danger { background: rgba(244, 67, 54, 0.85); border: 1px solid #f44336; }
.alert-warning { background: rgba(255, 152, 0, 0.85); border: 1px solid #ff9800; }

.container{
    width:100%;
    max-width:1200px;
    display:grid;
    grid-template-columns: 1fr 1fr;
    gap:30px;
    align-items:start;
}

.box{
    padding:28px;
    border-radius:25px;
    background:rgba(255,255,255,0.08);
    backdrop-filter:blur(15px);
    border:1px solid rgba(255,255,255,0.15);
    box-shadow:0 8px 30px rgba(0,0,0,0.25);
    margin-bottom: 25px;
}

.box h2{
    margin-bottom:20px;
    font-size:22px;
    color: #ffd54f;
    border-bottom: 1px solid rgba(255,255,255,0.15);
    padding-bottom: 10px;
}

label {
    display: block;
    margin-top: 12px;
    font-size: 13px;
    font-weight: bold;
    color: #e0e0e0;
}

input, select{
    width:100%;
    padding:12px 15px;
    margin-top:6px;
    border:none;
    outline:none;
    border-radius:12px;
    background:rgba(255,255,255,0.12);
    color:white;
    font-size:14px;
}

input::placeholder{ color:#bbb; }
select option{ color:black; }

button, .btn-action {
    width:100%;
    padding:12px;
    margin-top:15px;
    border:none;
    border-radius:12px;
    background:linear-gradient(45deg,#ff9800,#ff5722);
    color:white;
    font-size:15px;
    font-weight:bold;
    cursor:pointer;
    transition:0.3s;
    text-align: center;
    text-decoration: none;
    display: inline-block;
}

button:hover, .btn-action:hover{
    transform:scale(1.02);
    box-shadow:0 0 15px rgba(255,87,34,0.5);
}

.btn-secondary { background: linear-gradient(45deg, #2196f3, #00bcd4); }
.btn-danger { background: linear-gradient(45deg, #f44336, #e91e63); }

table{
    width:100%;
    margin-top:15px;
    border-collapse:collapse;
    border-radius:12px;
    overflow:hidden;
}

th{
    padding:12px;
    background:linear-gradient(45deg,#ff9800,#ff5722);
    color:white;
    font-size:14px;
}

td{
    padding:10px 12px;
    text-align:center;
    background:rgba(255,255,255,0.05);
    border-bottom:1px solid rgba(255,255,255,0.1);
    font-size: 13px;
}

tr:hover td{ background:rgba(255,255,255,0.1); }

.delete{
    padding:6px 12px;
    border-radius:8px;
    background:#f44336;
    color:white;
    text-decoration:none;
    font-weight:bold;
    font-size:12px;
}

.edit{
    padding:6px 12px;
    border-radius:8px;
    background:#2196f3;
    color:white;
    text-decoration:none;
    font-weight:bold;
    font-size:12px;
    margin-right: 5px;
}

.restore{
    padding:6px 12px;
    border-radius:8px;
    background:#4caf50;
    color:white;
    text-decoration:none;
    font-weight:bold;
    font-size:12px;
}

.badge{ padding:4px 10px; border-radius:10px; font-size:12px; font-weight:bold; }
.badge-admin{ background:#4caf50; }
.badge-user{ background:#2196f3; }
.badge-caissier{ background:#ab47bc; }

.update-box{
    margin-top:15px;
    padding:15px;
    border-radius:14px;
    background:rgba(255,255,255,0.06);
}

.version{
    font-size:16px;
    margin-top:5px;
    color:#ffd54f;
    font-weight:bold;
}

@media(max-width:900px){
    .container{ grid-template-columns:1fr; }
    body{ padding:15px; }
}
</style>
</head>

<body>

<div class="top-bar">
    <a href="dashboard.php" class="btn-back">⬅ Retour au tableau de bord</a>
</div>

<h1>⚙️ Paramètres du Système</h1>

<?php if(!empty($message)): ?>
    <div class="alert alert-<?= $message_type ?>">
        <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>

<div class="container">

    <!-- ================= COLONNE GAUCHE ================= -->
    <div>

        <!-- 👥 GESTION DES UTILISATEURS -->
        <div class="box">
            <h2>👥 <?= $user_to_edit ? '✏️ Modifier l\'Utilisateur' : '➕ Ajouter un Utilisateur' ?></h2>

            <?php if($user_to_edit): ?>
                <!-- Formulaire de modification -->
                <form method="POST" action="parametre.php">
                    <input type="hidden" name="id_utilisateur" value="<?= $user_to_edit['id_utilisateur'] ?>">

                    <label>Nom d'utilisateur :</label>
                    <input type="text" name="nom_utilisateur" value="<?= htmlspecialchars($user_to_edit['nom_utilisateur']) ?>" required>

                    <label>Nouveau mot de passe (laisser vide si inchangé) :</label>
                    <input type="password" name="mot_de_passe" placeholder="Changer le mot de passe...">

                    <label>Rôle :</label>
                    <select name="role" required>
                        <option value="Administrateur" <?= $user_to_edit['role'] == 'Administrateur' ? 'selected' : '' ?>>Administrateur</option>
                        <option value="Caissier" <?= $user_to_edit['role'] == 'Caissier' ? 'selected' : '' ?>>Caissier</option>
                        <option value="Gestionnaire" <?= $user_to_edit['role'] == 'Gestionnaire' ? 'selected' : '' ?>>Gestionnaire</option>
                    </select>

                    <button type="submit" name="modifier_utilisateur">💾 Enregistrer la modification</button>
                    <a href="parametre.php" class="btn-action btn-secondary" style="margin-top:8px;">❌ Annuler</a>
                </form>
            <?php else: ?>
                <!-- Formulaire d'ajout -->
                <form method="POST">
                    <label>Nom d'utilisateur :</label>
                    <input type="text" name="nom_utilisateur" placeholder="Entrer le nom" required>

                    <label>Mot de passe :</label>
                    <input type="password" name="mot_de_passe" placeholder="Entrer le mot de passe" required>

                    <label>Rôle :</label>
                    <select name="role" required>
                        <option value="">-- Choisir un rôle --</option>
                        <option value="Administrateur">Administrateur</option>
                        <option value="Caissier">Caissier</option>
                        <option value="Gestionnaire">Gestionnaire</option>
                    </select>

                    <button type="submit" name="ajouter">➕ Ajouter l'utilisateur</button>
                </form>
            <?php endif; ?>

            <table>
                <tr>
                    <th>ID</th>
                    <th>Utilisateur</th>
                    <th>Rôle</th>
                    <th>Action</th>
                </tr>
                <?php
                $res = $conn->query("SELECT * FROM utilisateur ORDER BY id_utilisateur DESC");
                if($res && $res->num_rows > 0){
                    while($u = $res->fetch_assoc()){
                        $badge = "badge-user";
                        if($u['role'] == 'Administrateur') $badge = "badge-admin";
                        if($u['role'] == 'Caissier') $badge = "badge-caissier";

                        echo "<tr>
                            <td>{$u['id_utilisateur']}</td>
                            <td>".htmlspecialchars($u['nom_utilisateur'])."</td>
                            <td><span class='badge $badge'>{$u['role']}</span></td>
                            <td>
                                <a class='edit' href='parametre.php?editer={$u['id_utilisateur']}'>✏️</a>
                                <a class='delete' href='parametre.php?supprimer={$u['id_utilisateur']}' onclick=\"return confirm('Supprimer cet utilisateur ?')\">🗑</a>
                            </td>
                        </tr>";
                    }
                } else {
                    echo "<tr><td colspan='4'>Aucun utilisateur trouvé.</td></tr>";
                }
                ?>
            </table>
        </div>

        <!-- 🔑 SÉCURITÉ & MON PROFIL -->
        <div class="box">
            <h2>🔐 Mon Profil & Sécurité</h2>
            <form method="POST">
                <label>Mon nom d'utilisateur :</label>
                <input type="text" name="mon_nom" value="<?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?>" required>

                <label>Ancien mot de passe :</label>
                <input type="password" name="ancien_pass" placeholder="Obligatoire si changement">

                <label>Nouveau mot de passe :</label>
                <input type="password" name="nouveau_pass" placeholder="Laisser vide si inchangé">

                <button type="submit" name="update_profile" class="btn-secondary">💾 Mettre à jour mon profil</button>
            </form>
        </div>

        <!-- 🏢 CONFIGURATION GÉNÉRALE -->
        <div class="box">
            <h2>🏢 Configuration du Logiciel</h2>
            <form method="POST">
                <label>Nom de l'Entreprise / Agence :</label>
                <input type="text" name="nom_entreprise" value="<?= htmlspecialchars($_SESSION['config_nom_entreprise'] ?? 'Gestion Immobilière Pro') ?>">

                <label>Devise Principale :</label>
                <select name="devise">
                    <option value="$" <?= (($_SESSION['config_devise'] ?? '$') == '$') ? 'selected' : '' ?>>Dollar ($)</option>
                    <option value="FC" <?= (($_SESSION['config_devise'] ?? '') == 'FC') ? 'selected' : '' ?>>Franc Congolais (FC)</option>
                    <option value="€" <?= (($_SESSION['config_devise'] ?? '') == '€') ? 'selected' : '' ?>>Euro (€)</option>
                    <option value="CFA" <?= (($_SESSION['config_devise'] ?? '') == 'CFA') ? 'selected' : '' ?>>Franc CFA</option>
                </select>

                <label>Format des Dates :</label>
                <select name="format_date">
                    <option value="d/m/Y">JJ/MM/AAAA (ex: 15/08/2026)</option>
                    <option value="Y-m-d">AAAA-MM-JJ (ex: 2026-08-15)</option>
                </select>

                <button type="submit" name="save_config">💾 Sauvegarder la configuration</button>
            </form>
        </div>

    </div>

    <!-- ================= COLONNE DROITE ================= -->
    <div>

        <!-- 🛠️ MAINTENANCE & SAUVEGARDE -->
        <div class="box">
            <h2>📦 Maintenance & Base de Données</h2>
            <div class="update-box">
                <h3>💾 Sauvegarde BDD</h3>
                <p style="font-size:13px; margin-top:5px;">Téléchargez une copie SQL de secours.</p>
                <a href="export_bdd.php" class="btn-action btn-secondary">📥 Exporter la BDD (.sql)</a>
            </div>

            <div class="update-box">
                <h3>🧹 Cache Système</h3>
                <p style="font-size:13px; margin-top:5px;">Nettoyez les fichiers temporaires.</p>
                <button type="button" onclick="alert('Cache réinitialisé avec succès !')" class="btn-secondary">⚡ Vider le cache</button>
            </div>
        </div>

        <!-- 🗑 CORBEILLE SYSTÈME -->
        <div class="box">
            <h2>🗑 Corbeille Système</h2>
            <p style="font-size:13px; color:#ddd;">Les récents éléments supprimés de votre base de données.</p>

            <table>
                <tr>
                    <th>Type</th>
                    <th>Nom</th>
                    <th>Date de suppression</th>
                    <th>Action</th>
                </tr>
                <?php
                $resCorbeille = $conn->query("SELECT * FROM corbeille ORDER BY id_corbeille DESC LIMIT 5");
                if($resCorbeille && $resCorbeille->num_rows > 0){
                    while($c = $resCorbeille->fetch_assoc()){
                        echo "<tr>
                            <td>".htmlspecialchars($c['type_element'])."</td>
                            <td>".htmlspecialchars($c['nom_element'])."</td>
                            <td>".htmlspecialchars($c['date_suppression'])."</td>
                            <td>
                                <a class='restore' href='parametre.php?restaurer={$c['id_corbeille']}' onclick=\"return confirm('Restaurer cet élément ?')\">↩️ Restaurer</a>
                            </td>
                        </tr>";
                    }
                } else {
                    echo "<tr><td colspan='4'>Aucun élément supprimé</td></tr>";
                }
                ?>
            </table>

            <form method="POST" onsubmit="return confirm('Voulez-vous vraiment vider définitivement la corbeille ?');" style="margin-top: 15px;">
                <button type="submit" name="vider_corbeille" class="btn-danger">🔥 Vider toute la corbeille</button>
            </form>
        </div>

        <!-- 🛡️ MATRICE DES ROLES ET PERMISSIONS -->
        <div class="box">
            <h2>🛡️ Matrice des Rôles</h2>
            <table>
                <tr>
                    <th>Fonctionnalité</th>
                    <th>Admin</th>
                    <th>Gestionnaire</th>
                    <th>Caissier</th>
                </tr>
                <tr>
                    <td>Encaissement / Reçus</td>
                    <td>✅</td>
                    <td>✅</td>
                    <td>✅</td>
                </tr>
                <tr>
                    <td>Gestion Biens/Locataires</td>
                    <td>✅</td>
                    <td>✅</td>
                    <td>❌</td>
                </tr>
                <tr>
                    <td>Configuration & Users</td>
                    <td>✅</td>
                    <td>❌</td>
                    <td>❌</td>
                </tr>
            </table>
        </div>

        <!-- 🖥 INFORMATIONS SYSTÈME -->
        <div class="box">
            <h2>🖥 Informations Système</h2>

            <div class="update-box">
                <h3>📦 Version du Logiciel</h3>
                <p class="version">Gestion Immobilière Pro v2.1</p>
            </div>

            <div class="update-box">
                <h3>🔄 Mise à Jour</h3>
                <p style="margin-top:5px; font-size:13px;">Dernière vérification : <?= date('d/m/Y H:i') ?></p>
                <button type="button" onclick="alert('Votre système est à jour !')" class="btn-secondary">🚀 Vérifier les mises à jour</button>
            </div>

            <div class="update-box">
                <h3>🖥️ Connexion Serveur</h3>
                <p style="font-size:13px; margin-top:5px;">Base : <b>gestion_immobiliere</b></p>
                <p style="font-size:13px;">Serveur : <b>localhost</b></p>
                <p style="font-size:13px; color:#4caf50;">Statut : Connecté ✅</p>
            </div>
        </div>

    </div>

</div>

</body>
</html>
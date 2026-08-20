<?php
include 'auth.php';
include 'connexion.php';

// Variables pour le mode modification
$edit_mode = false;
$edit_id = 0;
$edit_id_locataire = "";
$edit_montant = "";
$edit_mois = "";
$edit_date_paiement = "";

// ================= RECUPERATION POUR MODIFICATION =================

if(isset($_GET['modifier'])){
    $edit_id = intval($_GET['modifier']);
    
    $stmt_edit = $conn->prepare("SELECT * FROM paiement WHERE id_paiement = ?");
    $stmt_edit->bind_param("i", $edit_id);
    $stmt_edit->execute();
    $res_edit = $stmt_edit->get_result();

    if($row_edit = $res_edit->fetch_assoc()){
        $edit_mode = true;
        $edit_id_locataire = $row_edit['id_locataire'];
        $edit_montant = $row_edit['montant'];
        $edit_mois = $row_edit['mois'];
        $edit_date_paiement = $row_edit['date_paiement'];
    }
}


// ================= TRAITEMENT MODIFICATION =================

if(isset($_POST['modifier_action'])){
    $id_paiement = intval($_POST['id_paiement']);
    $id_locataire = $_POST['id_locataire'];
    $montant = $_POST['montant'];
    $mois = $_POST['mois'];
    $date_paiement = $_POST['date_paiement'];

    $stmt_up = $conn->prepare("
        UPDATE paiement 
        SET id_locataire=?, montant=?, mois=?, date_paiement=?
        WHERE id_paiement=?
    ");
    $stmt_up->bind_param("idssi", $id_locataire, $montant, $mois, $date_paiement, $id_paiement);
    $stmt_up->execute();

    header("Location: paiement.php");
    exit();
}


// ================= AJOUT PAIEMENT =================

if(isset($_POST['ajouter'])){

    $id_locataire = $_POST['id_locataire'];
    $montant = $_POST['montant'];
    $mois = $_POST['mois'];
    $date_paiement = $_POST['date_paiement'];

    $stmt = $conn->prepare("
        INSERT INTO paiement
        (id_locataire, montant, mois, date_paiement)
        VALUES(?,?,?,?)
    ");

    $stmt->bind_param("idss", $id_locataire, $montant, $mois, $date_paiement);
    $stmt->execute();

    header("Location: paiement.php");
    exit();
}


// ================= SUPPRESSION =================

if(isset($_GET['supprimer'])){

    $id = intval($_GET['supprimer']);

    $stmt = $conn->prepare("
        DELETE FROM paiement
        WHERE id_paiement=?
    ");

    $stmt->bind_param("i", $id);
    $stmt->execute();

    header("Location: paiement.php");
    exit();
}


// ================= FILTRE + ACTION =================

$action = $_GET['action'] ?? "afficher";
$filtre_locataire = $_GET['locataire_filter'] ?? "";

?>

<!DOCTYPE html>
<html lang="fr">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Gestion des paiements</title>

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

    position:relative;
    background:url('https://images.unsplash.com/photo-1554224155-6726b3ff858f?q=80&w=2070&auto=format&fit=crop')
    no-repeat center center/cover;
}

body::before{
    content:"";
    position:fixed;
    inset:0;
    background:inherit;
    filter:blur(12px);
    transform:scale(1.1);
    z-index:-2;
}

body::after{
    content:"";
    position:fixed;
    inset:0;
    background:rgba(0,0,0,0.4);
    z-index:-1;
}

/* ===== BOUTON RETOUR ===== */
.btn-back {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 20px;
    border-radius: 15px;
    background: rgba(255, 255, 255, 0.12);
    backdrop-filter: blur(15px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: white;
    text-decoration: none;
    font-weight: bold;
    font-size: 15px;
    transition: 0.3s ease;
    margin-bottom: 20px;
}

.btn-back:hover {
    background: rgba(255, 255, 255, 0.25);
    transform: translateX(-4px);
}

.form-box{
    width:60%;
    margin:auto;
    padding:25px;
    border-radius:20px;
    background:rgba(255,255,255,0.08);
    backdrop-filter: blur(15px);
    border: 1px solid rgba(255, 255, 255, 0.15);
    margin-bottom:20px;
}

input, select{
    width:100%;
    padding:12px;
    margin-top:10px;
    border:none;
    outline:none;
    border-radius:10px;
    background: rgba(255, 255, 255, 0.15);
    color: white;
    font-size: 14px;
}

input::placeholder{
    color: #ddd;
}

select option{
    color: black;
}

button, .btn-cancel{
    padding:12px 18px;
    margin-top:15px;
    border:none;
    border-radius:10px;
    background:linear-gradient(45deg,#00c853,#64dd17);
    color:white;
    font-weight:bold;
    cursor:pointer;
    font-size:14px;
    transition:0.3s;
    display:inline-block;
    text-decoration:none;
    text-align:center;
}

.btn-update {
    background: linear-gradient(45deg, #2196f3, #42a5f5);
}

.btn-cancel {
    background: linear-gradient(45deg, #ff9800, #ffb74d);
    margin-left: 10px;
}

button:hover, .btn-cancel:hover{
    transform:scale(1.03);
    filter:brightness(1.1);
}

.btn-group{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
    margin-top:10px;
}

.table-box{
    margin-top:20px;
    padding:20px;
    background:rgba(255,255,255,0.08);
    backdrop-filter: blur(15px);
    border-radius:20px;
    overflow-x: auto;
}

table{
    width:100%;
    border-collapse:collapse;
}

th{
    background:linear-gradient(45deg,#00c853,#64dd17);
    padding:12px;
}

td{
    text-align:center;
    padding:12px;
    border-bottom:1px solid rgba(255,255,255,0.2);
    background:rgba(255,255,255,0.05);
}

tr:hover td {
    background:rgba(255,255,255,0.1);
}

.delete{
    background:#f44336;
    padding:8px 12px;
    color:white;
    border-radius:8px;
    text-decoration:none;
    font-weight:bold;
    font-size:12px;
    display:inline-block;
    margin:2px;
}

.edit{
    background:#ff9800;
    padding:8px 12px;
    color:white;
    border-radius:8px;
    text-decoration:none;
    font-weight:bold;
    font-size:12px;
    display:inline-block;
    margin:2px;
}

@media(max-width:900px){
    .form-box{
        width:100%;
    }
}
</style>

</head>

<body>

<!-- ===== BOUTON RETOUR DYNAMIQUE ===== -->
<?php if($edit_mode): ?>
    <a href="paiement.php" class="btn-back">⬅ Annuler la modification</a>
<?php else: ?>
    <a href="dashboard.php" class="btn-back">⬅ Retour au tableau de bord</a>
<?php endif; ?>

<h1 style="text-align:center; margin-bottom: 25px;">Gestion des Paiements</h1>

<!-- ================= FORMULAIRE (AJOUT / MODIFICATION) ================= -->

<div class="form-box">

<form method="POST">

<?php if($edit_mode): ?>
    <input type="hidden" name="id_paiement" value="<?= $edit_id ?>">
    <h3 style="text-align:center; color:#42a5f5; margin-bottom:10px;">✏️ Modifier le Paiement #<?= $edit_id ?></h3>
<?php endif; ?>

<select name="id_locataire" required>
<option value="">Choisir locataire</option>

<?php
$res = $conn->query("SELECT id_locataire, nom, postnom, prenom FROM locataire");

while($l = $res->fetch_assoc()){
    $selected = ($edit_mode && $edit_id_locataire == $l['id_locataire']) ? "selected" : "";
    echo "<option value='{$l['id_locataire']}' $selected>{$l['nom']} {$l['postnom']} {$l['prenom']}</option>";
}
?>

</select>

<input type="number" step="0.01" name="montant" placeholder="Montant ($)" value="<?= htmlspecialchars($edit_montant) ?>" required>
<input type="month" name="mois" value="<?= htmlspecialchars($edit_mois) ?>" required>
<input type="date" name="date_paiement" value="<?= htmlspecialchars($edit_date_paiement) ?>" required>

<?php if($edit_mode): ?>
    <button type="submit" name="modifier_action" class="btn-update">💾 Enregistrer les modifications</button>
    <a href="paiement.php" class="btn-cancel">❌ Annuler</a>
<?php else: ?>
    <button type="submit" name="ajouter">➕ Ajouter paiement</button>
<?php endif; ?>

</form>

</div>


<!-- ================= FILTRE ================= -->

<div class="form-box">

<form method="GET">

<select name="locataire_filter">
<option value="">Tous les locataires</option>

<?php
$res = $conn->query("SELECT id_locataire, nom, postnom, prenom FROM locataire");

while($l = $res->fetch_assoc()){
    $selected = ($filtre_locataire == $l['id_locataire']) ? "selected" : "";
    echo "<option value='{$l['id_locataire']}' $selected>{$l['nom']} {$l['postnom']} {$l['prenom']}</option>";
}
?>

</select>

<div class="btn-group">

<button type="submit" name="action" value="afficher">
🔎 Afficher
</button>

<button type="submit" name="action" value="tout">
👥 Tout
</button>

<button type="submit" name="action" value="cacher">
🙈 Cacher
</button>

</div>
</form>

</div>


<!-- ================= TABLE ================= -->

<?php if($action != "cacher"): ?>

<div class="table-box">

<table>

<tr>
<th>ID</th>
<th>Locataire</th>
<th>Appartement</th>
<th>Maison</th>
<th>Mois</th>
<th>Montant</th>
<th>Date</th>
<th>Actions</th>
</tr>

<?php

$sql = "
SELECT paiement.*, nom, postnom, prenom, numero_appartement, adresse
FROM paiement
INNER JOIN locataire ON paiement.id_locataire = locataire.id_locataire
LEFT JOIN location ON locataire.id_locataire = location.id_locataire
LEFT JOIN appartement ON location.id_appartement = appartement.id_appartement
LEFT JOIN maison ON appartement.id_maison = maison.id_maison
";

if($action == "afficher" && $filtre_locataire != ""){
    $sql .= " WHERE paiement.id_locataire = $filtre_locataire ";
}

$sql .= " ORDER BY paiement.id_paiement DESC";

$res = $conn->query($sql);

if($res && $res->num_rows > 0){

while($p = $res->fetch_assoc()){
echo "
<tr>
<td>{$p['id_paiement']}</td>
<td>{$p['nom']} {$p['postnom']} {$p['prenom']}</td>
<td>" . ($p['numero_appartement'] ?? 'N/A') . "</td>
<td>" . ($p['adresse'] ?? 'N/A') . "</td>
<td>{$p['mois']}</td>
<td>{$p['montant']} $</td>
<td>{$p['date_paiement']}</td>
<td>
<a class='edit' href='paiement.php?modifier={$p['id_paiement']}'>✏️ Modifier</a>
<a class='delete' href='paiement.php?supprimer={$p['id_paiement']}' onclick=\"return confirm('Supprimer ce paiement ?')\">🗑 Supprimer</a>
</td>
</tr>
";
}

}else{
echo "<tr><td colspan='8'>Aucun paiement trouvé</td></tr>";
}

?>

</table>

</div>

<?php endif; ?>

</body>
</html>
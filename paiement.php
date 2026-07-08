<?php
include 'auth.php';
include 'connexion.php';


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
body{
    font-family:Arial;
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
.form-box{
    width:60%;
    margin:auto;
    padding:25px;
    border-radius:20px;
    background:rgba(255,255,255,0.08);
    margin-bottom:20px;
}

input, select{
    width:100%;
    padding:12px;
    margin-top:10px;
    border:none;
    border-radius:10px;
}

button{
    padding:10px 14px;
    margin-top:10px;
    width:auto;
    border:none;
    border-radius:10px;
    background:linear-gradient(45deg,#00c853,#64dd17);
    color:white;
    font-weight:bold;
    cursor:pointer;
    font-size:13px;
    transition:0.3s;
    display:inline-block;
}

button:hover{
    transform:scale(1.05);
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
    border-radius:20px;
}

table{
    width:100%;
    border-collapse:collapse;
}

th{
    background:#00c853;
    padding:10px;
}

td{
    text-align:center;
    padding:10px;
    border-bottom:1px solid rgba(255,255,255,0.2);
}

.delete{
    background:red;
    padding:8px;
    color:white;
    border-radius:8px;
    text-decoration:none;
}
</style>

</head>

<body>

<h1 style="text-align:center;">Gestion des Paiements</h1>

<!-- ================= FORM AJOUT ================= -->

<div class="form-box">

<form method="POST">

<select name="id_locataire" required>
<option value="">Choisir locataire</option>

<?php
$res = $conn->query("SELECT id_locataire, nom, postnom, prenom FROM locataire");

while($l = $res->fetch_assoc()){
    echo "<option value='{$l['id_locataire']}'>{$l['nom']} {$l['postnom']} {$l['prenom']}</option>";
}
?>

</select>

<input type="number" step="0.01" name="montant" placeholder="Montant" required>
<input type="month" name="mois" required>
<input type="date" name="date_paiement" required>

<button type="submit" name="ajouter">Ajouter paiement</button>

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
<th>Action</th>
</tr>

<?php

$sql = "
SELECT paiement.*, nom, postnom, prenom, numero_appartement, adresse
FROM paiement
INNER JOIN locataire ON paiement.id_locataire = locataire.id_locataire
INNER JOIN location ON locataire.id_locataire = location.id_locataire
INNER JOIN appartement ON location.id_appartement = appartement.id_appartement
INNER JOIN maison ON appartement.id_maison = maison.id_maison
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
<td>{$p['numero_appartement']}</td>
<td>{$p['adresse']}</td>
<td>{$p['mois']}</td>
<td>{$p['montant']} $</td>
<td>{$p['date_paiement']}</td>
<td>
<a class='delete' href='paiement.php?supprimer={$p['id_paiement']}' onclick=\"return confirm('Supprimer ?')\">Supprimer</a>
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
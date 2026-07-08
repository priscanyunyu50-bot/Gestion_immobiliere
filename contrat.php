<?php

include 'auth.php';
include 'connexion.php';


// ================= AJOUT CONTRAT =================

if(isset($_POST['ajouter'])){

    $id_locataire = $_POST['id_locataire'];
    $id_appartement = $_POST['id_appartement'];
    $date_debut = $_POST['date_debut'];
    $date_fin = $_POST['date_fin'];
    $montant_loyer = $_POST['montant_loyer'];
    $caution = $_POST['caution'];
    $conditions = $_POST['conditions'];

    // ================= STATUT =================
    $today = date('Y-m-d');

    if($date_debut > $today){
        $statut = "FUTUR";
    }
    elseif($date_fin < $today){
        $statut = "TERMINE";
    }
    else{
        $statut = "ACTIF";
    }

    $stmt = $conn->prepare("
        INSERT INTO contrat
        (id_locataire, id_appartement, date_debut, date_fin, montant_loyer, caution, statut, conditions)
        VALUES(?,?,?,?,?,?,?,?)
    ");

    $stmt->bind_param(
        "iissddss",
        $id_locataire,
        $id_appartement,
        $date_debut,
        $date_fin,
        $montant_loyer,
        $caution,
        $statut,
        $conditions
    );

    $stmt->execute();

    header("Location: contrat.php");
    exit();
}


// ================= SUPPRESSION =================

if(isset($_GET['supprimer'])){

    $id = intval($_GET['supprimer']);

    $stmt = $conn->prepare("
        DELETE FROM contrat
        WHERE id_contrat=?
    ");

    $stmt->bind_param("i",$id);

    $stmt->execute();

    header("Location: contrat.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="fr">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Gestion des Contrats</title>

<style>

/* ================= GLOBAL ================= */

body{
    font-family:Arial;
    padding:30px;
    color:white;
    min-height:100vh;

    background:
    linear-gradient(rgba(0,0,0,0.45),rgba(0,0,0,0.45)),
    url('https://images.unsplash.com/photo-1554995207-c18c203602cb?q=80&w=2070&auto=format&fit=crop')
    no-repeat center center/cover;
}

h1{
    text-align:center;
    margin-bottom:20px;
}

/* ================= FORM ================= */

.form-box{
    width:70%;
    margin:auto;
    padding:20px;
    border-radius:15px;
    background:rgba(255,255,255,0.10);
    backdrop-filter:blur(15px);
}

input, select, textarea{
    width:100%;
    padding:10px;
    margin-top:10px;
    border:none;
    border-radius:10px;
    background:rgba(255,255,255,0.15);
    color:white;
}

textarea{
    resize:none;
}

select option{
    color:black;
}

/* ================= BUTTON ================= */

button{
    width:100%;
    padding:12px;
    margin-top:15px;
    border:none;
    border-radius:10px;
    background:#4caf50;
    color:white;
    font-weight:bold;
    cursor:pointer;
}

/* ================= SMALL PRINT BUTTON ================= */

.print-btn-small{
    position:fixed;
    top:20px;
    right:20px;

    width:42px;
    height:42px;

    border-radius:50%;
    border:none;

    background:#2196f3;
    color:white;

    font-size:18px;
    cursor:pointer;

    display:flex;
    align-items:center;
    justify-content:center;

    box-shadow:0 4px 10px rgba(0,0,0,0.3);

    transition:0.3s;
}

.print-btn-small:hover{
    transform:scale(1.1);
    background:#1976d2;
}

/* ================= TABLE ================= */

.table-box{
    margin-top:25px;
    padding:20px;
    border-radius:15px;
    background:rgba(255,255,255,0.08);
    backdrop-filter:blur(15px);
    overflow-x:auto;
}

table{
    width:100%;
    border-collapse:collapse;
}

th{
    background:#4caf50;
    padding:10px;
}

td{
    text-align:center;
    padding:10px;
    border-bottom:1px solid rgba(255,255,255,0.2);
}

/* ================= STATUS ================= */

.badge{
    padding:6px 10px;
    border-radius:10px;
    font-weight:bold;
    color:white;
}

.actif{background:#4caf50;}
.termine{background:#f44336;}
.futur{background:#ff9800;}

.delete{
    padding:6px 10px;
    background:#f44336;
    color:white;
    text-decoration:none;
    border-radius:8px;
    font-weight:bold;
}

/* ================= PRINT MODE ================= */

@media print {

    body{
        background:white !important;
        color:black !important;
    }

    .form-box,
    .print-btn-small,
    .delete{
        display:none !important;
    }

    th{
        background:#ddd !important;
        color:black !important;
    }

    .table-box{
        background:white !important;
    }
}

@media(max-width:900px){
    .form-box{width:100%;}
}

</style>

</head>

<body>

<h1> Gestion des Contrats</h1>

<!-- ================= PRINT BUTTON ================= -->

<button class="print-btn-small" onclick="window.print()">
🖨
</button>

<!-- ================= FORM ================= -->

<div class="form-box">

<form method="POST">

<select name="id_locataire" required>
<option value="">👤 Locataire</option>

<?php
$res = $conn->query("SELECT * FROM locataire ORDER BY nom ASC");
while($l = $res->fetch_assoc()){
echo "<option value='{$l['id_locataire']}'>{$l['nom']} {$l['postnom']} {$l['prenom']}</option>";
}
?>
</select>

<select name="id_appartement" required>
<option value="">🏢 Appartement</option>

<?php
$res = $conn->query("SELECT * FROM appartement ORDER BY numero_appartement ASC");
while($a = $res->fetch_assoc()){
echo "<option value='{$a['id_appartement']}'>Appartement {$a['numero_appartement']}</option>";
}
?>
</select>

<input type="date" name="date_debut" required>
<input type="date" name="date_fin" required>

<input type="number" step="0.01" name="montant_loyer" placeholder="Loyer" required>
<input type="number" step="0.01" name="caution" placeholder="Caution" required>

<textarea name="conditions" rows="4" placeholder="🧾 Conditions du contrat"></textarea>

<button type="submit" name="ajouter">➕ Créer Contrat</button>

</form>

</div>

<!-- ================= TABLE ================= -->

<div class="table-box">

<table>

<tr>
<th>ID</th>
<th>Locataire</th>
<th>Appartement</th>
<th>Début</th>
<th>Fin</th>
<th>Loyer</th>
<th>Caution</th>
<th>Conditions</th>
<th>Statut</th>
<th>Action</th>
</tr>

<?php

$res = $conn->query("
SELECT contrat.*, locataire.nom, locataire.postnom, locataire.prenom, appartement.numero_appartement
FROM contrat
INNER JOIN locataire ON contrat.id_locataire = locataire.id_locataire
INNER JOIN appartement ON contrat.id_appartement = appartement.id_appartement
ORDER BY contrat.id_contrat DESC
");

while($c = $res->fetch_assoc()){

$statusClass = ($c['statut']=='ACTIF') ? 'actif' :
(($c['statut']=='TERMINE') ? 'termine' : 'futur');

echo '
<tr>

<td>'.$c['id_contrat'].'</td>

<td>'.$c['nom'].' '.$c['postnom'].' '.$c['prenom'].'</td>

<td>'.$c['numero_appartement'].'</td>

<td>'.$c['date_debut'].'</td>

<td>'.$c['date_fin'].'</td>

<td>'.$c['montant_loyer'].' $</td>

<td>'.$c['caution'].' $</td>

<td style="max-width:180px; font-size:12px;">
'.substr($c['conditions'],0,80).'
</td>

<td>
<span class="badge '.$statusClass.'">'.$c['statut'].'</span>
</td>

<td>
<a class="delete" href="contrat.php?supprimer='.$c['id_contrat'].'" onclick="return confirm(\'Supprimer ce contrat ?\')">
🗑
</a>
</td>

</tr>
';
}

?>

</table>

</div>

</body>
</html>
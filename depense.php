<?php
include 'auth.php';
include 'connexion.php';


// ================= AJOUT DEPENSE =================

if(isset($_POST['ajouter'])){

    $designation = $_POST['designation'];
    $montant = $_POST['montant'];
    $date_depense = $_POST['date_depense'];

    $stmt = $conn->prepare("
        INSERT INTO depense(designation, montant, date_depense)
        VALUES(?,?,?)
    ");

    $stmt->bind_param(
        "sds",
        $designation,
        $montant,
        $date_depense
    );

    $stmt->execute();

    header("Location: depense.php");
    exit();
}


// ================= MODIFICATION =================

if(isset($_POST['modifier'])){

    $id = intval($_POST['id_depense']);

    $designation = $_POST['designation'];
    $montant = $_POST['montant'];
    $date_depense = $_POST['date_depense'];

    $stmt = $conn->prepare("
        UPDATE depense
        SET
        designation=?,
        montant=?,
        date_depense=?
        WHERE id_depense=?
    ");

    $stmt->bind_param(
        "sdsi",
        $designation,
        $montant,
        $date_depense,
        $id
    );

    $stmt->execute();

    header("Location: depense.php");
    exit();
}


// ================= SUPPRESSION =================

if(isset($_GET['supprimer'])){

    $id = intval($_GET['supprimer']);

    $stmt = $conn->prepare("
        DELETE FROM depense
        WHERE id_depense=?
    ");

    $stmt->bind_param("i", $id);

    $stmt->execute();

    header("Location: depense.php");
    exit();
}


// ================= RECUPERATION =================

$modifier = null;

if(isset($_GET['edit'])){

    $id = intval($_GET['edit']);

    $stmt = $conn->prepare("
        SELECT * FROM depense
        WHERE id_depense=?
    ");

    $stmt->bind_param("i", $id);

    $stmt->execute();

    $res = $stmt->get_result();

    $modifier = $res->fetch_assoc();
}

?>

<!DOCTYPE html>
<html lang="fr">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>Gestion Dépenses</title>

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{

    font-family:Arial;
    min-height:100vh;

    padding:30px;

    color:white;

    background:
    linear-gradient(rgba(0,0,0,0.35), rgba(0,0,0,0.35)),
    url('https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?q=80&w=2070&auto=format&fit=crop')
    no-repeat center center/cover;
}

/* FLOU */

body::before{

    content:"";

    position:fixed;

    inset:0;

    backdrop-filter:blur(8px);

    z-index:-1;
}

/* EN-TÊTE ET BOUTON RETOUR */

.top-bar{

    display:flex;

    align-items:center;

    justify-content:space-between;

    margin-bottom:30px;
}

.btn-retour{

    padding:12px 20px;

    border-radius:12px;

    background:rgba(255,255,255,0.12);

    backdrop-filter:blur(10px);

    border:1px solid rgba(255,255,255,0.2);

    color:white;

    text-decoration:none;

    font-weight:bold;

    transition:0.3s;
}

.btn-retour:hover{

    background:rgba(255,255,255,0.25);

    transform:translateX(-3px);
}

h1{

    text-align:center;

    font-size:38px;

    flex:1;
}

/* FORMULAIRE */

.form-box{

    width:500px;

    margin:auto;

    padding:30px;

    border-radius:25px;

    background:rgba(255,255,255,0.08);

    backdrop-filter:blur(15px);

    border:1px solid rgba(255,255,255,0.1);
}

/* INPUT */

input{

    width:100%;

    padding:15px;

    margin-top:15px;

    border:none;

    border-radius:15px;

    outline:none;

    background:rgba(255,255,255,0.10);

    color:white;

    font-size:15px;
}

input::placeholder{

    color:#ddd;
}

/* BOUTON */

button{

    width:100%;

    padding:15px;

    margin-top:20px;

    border:none;

    border-radius:15px;

    background:linear-gradient(45deg,#ff416c,#ff4b2b);

    color:white;

    font-size:16px;

    font-weight:bold;

    cursor:pointer;

    transition:0.3s;
}

button:hover{

    transform:scale(1.03);
}

/* TABLE */

.table-box{

    margin-top:40px;

    padding:25px;

    border-radius:25px;

    background:rgba(255,255,255,0.08);

    backdrop-filter:blur(15px);

    overflow-x:auto;
}

table{

    width:100%;

    border-collapse:collapse;
}

th{

    padding:15px;

    background:linear-gradient(45deg,#ff416c,#ff4b2b);
}

td{

    padding:15px;

    text-align:center;

    background:rgba(255,255,255,0.05);

    border-bottom:1px solid rgba(255,255,255,0.08);
}

tr:hover td{

    background:rgba(255,255,255,0.10);
}

/* ACTIONS */

.actions{

    display:flex;

    justify-content:center;

    gap:10px;
}

.edit{

    text-decoration:none;

    padding:10px 14px;

    border-radius:10px;

    background:#2196f3;

    color:white;

    font-weight:bold;
}

.delete{

    text-decoration:none;

    padding:10px 14px;

    border-radius:10px;

    background:#ff3d3d;

    color:white;

    font-weight:bold;
}

@media(max-width:700px){

    .top-bar{

        flex-direction:column;

        gap:15px;

        text-align:center;
    }

    .form-box{

        width:100%;
    }

}

</style>

</head>

<body>

<!-- ================= EN-TÊTE AVEC BOUTON RETOUR ================= -->

<div class="top-bar">

<a href="dashboard.php" class="btn-retour">
⬅ Retour au Dashboard
</a>

<h1>Gestion des Dépenses</h1>

<div style="width:180px;"></div>

</div>

<!-- ================= FORMULAIRE ================= -->

<div class="form-box">

<form method="POST">

<input
type="hidden"
name="id_depense"
value="<?= $modifier['id_depense'] ?? '' ?>"
>

<input
type="text"
name="designation"
placeholder="Désignation"
required
value="<?= $modifier['designation'] ?? '' ?>"
>

<input
type="number"
step="0.01"
name="montant"
placeholder="Montant"
required
value="<?= $modifier['montant'] ?? '' ?>"
>

<input
type="date"
name="date_depense"
required
value="<?= $modifier['date_depense'] ?? '' ?>"
>

<?php if($modifier){ ?>

<button type="submit" name="modifier">

✏ Modifier Dépense

</button>

<?php } else { ?>

<button type="submit" name="ajouter">

➕ Ajouter Dépense

</button>

<?php } ?>

</form>

</div>

<!-- ================= TABLE ================= -->

<div class="table-box">

<table>

<tr>

<th>ID</th>
<th>Désignation</th>
<th>Montant</th>
<th>Date</th>
<th>Actions</th>

</tr>

<?php

$res = $conn->query("
    SELECT *
    FROM depense
    ORDER BY id_depense DESC
");

if($res && $res->num_rows > 0){

while($d = $res->fetch_assoc()){

echo "

<tr>

<td>".$d['id_depense']."</td>

<td>".$d['designation']."</td>

<td>".$d['montant']." $</td>

<td>".$d['date_depense']."</td>

<td>

<div class='actions'>

<a
class='edit'
href='depense.php?edit=".$d['id_depense']."'
>
✏ Modifier
</a>

<a
class='delete'
href='depense.php?supprimer=".$d['id_depense']."'
onclick=\"return confirm('Supprimer cette dépense ?')\"
>
🗑 Supprimer
</a>

</div>

</td>

</tr>

";

}

}else{

echo "

<tr>

<td colspan='5'>

Aucune dépense enregistrée

</td>

</tr>

";

}

?>

</table>

</div>

</body>
</html>
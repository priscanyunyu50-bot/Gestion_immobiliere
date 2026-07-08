<?php
include 'auth.php';
include 'connexion.php';


// ================= AJOUT MAISON =================

if(isset($_POST['ajouter'])){

    $adresse = $_POST['adresse'];
    $quartier = $_POST['quartier'];

    $stmt = $conn->prepare("
        INSERT INTO maison
        (adresse, quartier)
        VALUES(?,?)
    ");

    $stmt->bind_param(
        "ss",
        $adresse,
        $quartier
    );

    $stmt->execute();

    header("Location: maison.php");
    exit();
}


// ================= MODIFICATION =================

if(isset($_POST['modifier'])){

    $id_maison = $_POST['id_maison'];

    $adresse = $_POST['adresse'];

    $quartier = $_POST['quartier'];

    $stmt = $conn->prepare("
        UPDATE maison
        SET
        adresse=?,
        quartier=?
        WHERE id_maison=?
    ");

    $stmt->bind_param(
        "ssi",
        $adresse,
        $quartier,
        $id_maison
    );

    $stmt->execute();

    header("Location: maison.php");
    exit();
}


// ================= SUPPRESSION =================

if(isset($_GET['supprimer'])){

    $id = intval($_GET['supprimer']);

    // Vérifier appartements liés

    $check = $conn->query("
        SELECT *
        FROM appartement
        WHERE id_maison='$id'
    ");

    if($check->num_rows > 0){

        echo "
        <script>
        alert('Impossible de supprimer cette maison car elle contient des appartements');
        window.location='maison.php';
        </script>
        ";

        exit();
    }

    $stmt = $conn->prepare("
        DELETE FROM maison
        WHERE id_maison=?
    ");

    $stmt->bind_param("i", $id);

    $stmt->execute();

    header("Location: maison.php");
    exit();
}


// ================= RECUPERATION =================

$modifier = null;

if(isset($_GET['edit'])){

    $id = intval($_GET['edit']);

    $stmt = $conn->prepare("
        SELECT *
        FROM maison
        WHERE id_maison=?
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

<title>Gestion des maisons</title>

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
    linear-gradient(rgba(0,0,0,0.35),
    rgba(0,0,0,0.35)),
    url('https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?q=80&w=2070&auto=format&fit=crop')
    no-repeat center center/cover;
}

body::before{

    content:"";

    position:fixed;

    inset:0;

    backdrop-filter:blur(8px);

    z-index:-1;
}

h1{

    text-align:center;

    margin-bottom:30px;

    font-size:40px;
}

.form-box{

    width:60%;

    margin:auto;

    padding:30px;

    border-radius:25px;

    background:rgba(255,255,255,0.08);

    backdrop-filter:blur(15px);

    border:1px solid rgba(255,255,255,0.15);
}

input{

    width:100%;

    padding:15px;

    margin-top:15px;

    border:none;

    outline:none;

    border-radius:15px;

    background:rgba(255,255,255,0.12);

    color:white;

    font-size:15px;
}

input::placeholder{
    color:#ddd;
}

button{

    width:100%;

    padding:15px;

    margin-top:20px;

    border:none;

    border-radius:15px;

    background:linear-gradient(45deg,#2196f3,#42a5f5);

    color:white;

    font-size:16px;

    font-weight:bold;

    cursor:pointer;

    transition:0.3s;
}

button:hover{
    transform:scale(1.02);
}

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

    background:linear-gradient(45deg,#2196f3,#42a5f5);
}

td{

    padding:15px;

    text-align:center;

    background:rgba(255,255,255,0.05);

    border-bottom:1px solid rgba(255,255,255,0.1);
}

tr:hover td{
    background:rgba(255,255,255,0.1);
}

.actions{

    display:flex;

    justify-content:center;

    gap:10px;
}

.edit,
.delete{

    padding:10px 15px;

    border-radius:10px;

    color:white;

    text-decoration:none;

    font-weight:bold;
}

.edit{
    background:#2196f3;
}

.delete{
    background:#f44336;
}

.badge{

    padding:6px 12px;

    border-radius:12px;

    background:#00c853;

    font-size:13px;

    font-weight:bold;
}

@media(max-width:900px){

    .form-box{
        width:100%;
    }
}

</style>

</head>

<body>

<h1> Gestion des Maisons</h1>


<!-- ================= FORMULAIRE ================= -->

<div class="form-box">

<form method="POST">

<input
type="hidden"
name="id_maison"
value="<?= $modifier['id_maison'] ?? '' ?>"
>

<input
type="text"
name="adresse"
placeholder="📍 Adresse de la maison"
required
value="<?= $modifier['adresse'] ?? '' ?>"
>

<input
type="text"
name="quartier"
placeholder="🏘 Quartier"
required
value="<?= $modifier['quartier'] ?? '' ?>"
>

<?php if($modifier): ?>

<button type="submit" name="modifier">
✏ Modifier Maison
</button>

<?php else: ?>

<button type="submit" name="ajouter">
➕ Ajouter Maison
</button>

<?php endif; ?>

</form>

</div>


<!-- ================= TABLE ================= -->

<div class="table-box">

<table>

<tr>

<th>ID</th>
<th>Adresse</th>
<th>Quartier</th>
<th>Appartements</th>
<th>Actions</th>

</tr>

<?php

$sql = "

SELECT

maison.*,

COUNT(appartement.id_appartement)
AS total_appartements

FROM maison

LEFT JOIN appartement
ON maison.id_maison = appartement.id_maison

GROUP BY maison.id_maison

ORDER BY maison.id_maison DESC

";

$res = $conn->query($sql);

if($res && $res->num_rows > 0){

while($m = $res->fetch_assoc()){

echo "

<tr>

<td>
{$m['id_maison']}
</td>

<td>
{$m['adresse']}
</td>

<td>
{$m['quartier']}
</td>

<td>
<span class='badge'>
{$m['total_appartements']} Appartement(s)
</span>
</td>

<td>

<div class='actions'>

<a
class='edit'
href='maison.php?edit={$m['id_maison']}'
>
✏ Modifier
</a>

<a
class='delete'
href='maison.php?supprimer={$m['id_maison']}'
onclick=\"return confirm('Supprimer cette maison ?')\"
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
Aucune maison trouvée
</td>

</tr>

";

}

?>

</table>

</div>

</body>
</html>
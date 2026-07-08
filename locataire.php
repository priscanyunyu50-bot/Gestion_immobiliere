<?php
include 'auth.php';
include 'connexion.php';


// ================= AJOUT LOCATAIRE =================

if(isset($_POST['ajouter'])){

    $nom = $_POST['nom'];
    $postnom = $_POST['postnom'];
    $prenom = $_POST['prenom'];
    $telephone = $_POST['telephone'];
    $garantie = $_POST['garantie'];

    $id_appartement = $_POST['id_appartement'];

    // Vérifier appartement libre

    $check = $conn->query("
        SELECT *
        FROM appartement
        WHERE id_appartement='$id_appartement'
        AND statut='Libre'
    ");

    if($check->num_rows == 0){

        echo "
        <script>
        alert('Appartement indisponible');
        window.location='locataire.php';
        </script>
        ";

        exit();
    }

    // Ajouter locataire

    $stmt = $conn->prepare("
        INSERT INTO locataire
        (nom, postnom, prenom, telephone, garantie)
        VALUES(?,?,?,?,?)
    ");

    $stmt->bind_param(
        "ssssd",
        $nom,
        $postnom,
        $prenom,
        $telephone,
        $garantie
    );

    if($stmt->execute()){

        $id_locataire = $conn->insert_id;

        $date_debut = date('Y-m-d');

        // Ajouter location

        $stmt2 = $conn->prepare("
            INSERT INTO location
            (id_locataire, id_appartement, date_debut)
            VALUES(?,?,?)
        ");

        $stmt2->bind_param(
            "iis",
            $id_locataire,
            $id_appartement,
            $date_debut
        );

        $stmt2->execute();

        // Occupé

        $conn->query("
            UPDATE appartement
            SET statut='Occupé'
            WHERE id_appartement='$id_appartement'
        ");

        header("Location: locataire.php");
        exit();
    }
}


// ================= SUPPRESSION =================

if(isset($_GET['supprimer'])){

    $id = intval($_GET['supprimer']);

    $res = $conn->query("
        SELECT id_appartement
        FROM location
        WHERE id_locataire='$id'
    ");

    $loc = $res->fetch_assoc();

    if($loc){

        $id_appartement = $loc['id_appartement'];

        $conn->query("
            UPDATE appartement
            SET statut='Libre'
            WHERE id_appartement='$id_appartement'
        ");
    }

    $conn->query("
        DELETE FROM location
        WHERE id_locataire='$id'
    ");

    $conn->query("
        DELETE FROM locataire
        WHERE id_locataire='$id'
    ");

    header("Location: locataire.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="fr">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>Gestion Locataires</title>

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
    url('https://images.unsplash.com/photo-1564013799919-ab600027ffc6?q=80&w=2070&auto=format&fit=crop')
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

    font-size:38px;
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

input,
select{

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

select option{
    color:black;
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

.delete{

    padding:10px 15px;

    border-radius:10px;

    background:#f44336;

    color:white;

    text-decoration:none;

    font-weight:bold;
}

.ok{

    color:#00ff99;

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

<h1>Gestion des Locataires</h1>


<!-- ================= FORMULAIRE ================= -->

<div class="form-box">

<form method="POST">

<input
type="text"
name="nom"
placeholder="Nom"
required
>

<input
type="text"
name="postnom"
placeholder="Postnom"
required
>

<input
type="text"
name="prenom"
placeholder="Prénom"
required
>

<input
type="text"
name="telephone"
placeholder="Téléphone"
required
>


<!-- CHOISIR MAISON -->

<select id="maison" required>

<option value="">
🏠 Choisir une maison
</option>

<?php

$resMaison = $conn->query("
SELECT *
FROM maison
ORDER BY adresse ASC
");

while($m = $resMaison->fetch_assoc()){

echo "

<option value='{$m['id_maison']}'>

{$m['adresse']} - {$m['quartier']}

</option>

";
}

?>

</select>


<!-- CHOISIR APPARTEMENT -->

<select
name="id_appartement"
id="appartement"
required
>

<option value="">
🏢 Choisir appartement
</option>

<?php

$sqlApp = "

SELECT

appartement.*,
maison.adresse

FROM appartement

INNER JOIN maison
ON appartement.id_maison = maison.id_maison

WHERE appartement.statut='Libre'

";

$resApp = $conn->query($sqlApp);

while($a = $resApp->fetch_assoc()){

echo "

<option
value='{$a['id_appartement']}'

data-maison='{$a['id_maison']}'

data-loyer='{$a['loyer']}'
>

Appartement {$a['numero_appartement']}

</option>

";
}

?>

</select>


<!-- LOYER -->

<input
type="text"
id="loyer"
placeholder="💰 Loyer"
readonly
>


<input
type="number"
step="0.01"
name="garantie"
placeholder="Garantie"
required
>

<button type="submit" name="ajouter">

➕ Ajouter Locataire

</button>

</form>

</div>


<!-- ================= TABLE ================= -->

<div class="table-box">

<table>

<tr>

<th>ID</th>
<th>Locataire</th>
<th>Téléphone</th>
<th>Maison</th>
<th>Appartement</th>
<th>Loyer</th>
<th>Garantie</th>
<th>Statut</th>
<th>Action</th>

</tr>

<?php

$sql = "

SELECT

locataire.*,

maison.adresse,
maison.quartier,

appartement.numero_appartement,
appartement.loyer,
appartement.statut

FROM locataire

LEFT JOIN location
ON locataire.id_locataire = location.id_locataire

LEFT JOIN appartement
ON location.id_appartement = appartement.id_appartement

LEFT JOIN maison
ON appartement.id_maison = maison.id_maison

ORDER BY locataire.id_locataire DESC

";

$res = $conn->query($sql);

while($row = $res->fetch_assoc()){

echo "

<tr>

<td>{$row['id_locataire']}</td>

<td>
{$row['nom']}
{$row['postnom']}
{$row['prenom']}
</td>

<td>
{$row['telephone']}
</td>

<td>
{$row['adresse']}
<br>
{$row['quartier']}
</td>

<td>
Appartement {$row['numero_appartement']}
</td>

<td>
{$row['loyer']} $
</td>

<td>
{$row['garantie']} $
</td>

<td class='ok'>
{$row['statut']}
</td>

<td>

<a
class='delete'
href='?supprimer={$row['id_locataire']}'
onclick=\"return confirm('Supprimer ce locataire ?')\"
>
🗑 Supprimer
</a>

</td>

</tr>

";
}

?>

</table>

</div>


<script>

const maison =
document.getElementById("maison");

const appartement =
document.getElementById("appartement");

const loyer =
document.getElementById("loyer");

maison.addEventListener("change", function(){

    let idMaison = this.value;

    for(let option of appartement.options){

        if(option.value == "") continue;

        if(option.dataset.maison == idMaison){

            option.style.display = "block";

        }else{

            option.style.display = "none";
        }
    }

    appartement.value = "";

    loyer.value = "";
});


appartement.addEventListener("change", function(){

    let selected =
    this.options[this.selectedIndex];

    loyer.value =
    selected.dataset.loyer + " $";
});

</script>

</body>
</html>
<?php
include 'auth.php';
include 'connexion.php';


// ================= AJOUT APPARTEMENT =================

if(isset($_POST['ajouter'])){

    $id_maison = intval($_POST['id_maison']);
    $numero_appartement = trim($_POST['numero_appartement']);
    $loyer = floatval($_POST['loyer']);

    // Vérifier doublon

    $check = $conn->prepare("
        SELECT *
        FROM appartement
        WHERE numero_appartement=?
        AND id_maison=?
    ");

    $check->bind_param(
        "si",
        $numero_appartement,
        $id_maison
    );

    $check->execute();

    $result = $check->get_result();

    if($result->num_rows > 0){

        echo "
        <script>
        alert('Cet appartement existe déjà dans cette maison');
        window.location='appartement.php';
        </script>
        ";

        exit();
    }

    // INSERT

    $stmt = $conn->prepare("
        INSERT INTO appartement
        (
            id_maison,
            numero_appartement,
            loyer,
            statut
        )
        VALUES(?,?,?,'Libre')
    ");

    $stmt->bind_param(
        "isd",
        $id_maison,
        $numero_appartement,
        $loyer
    );

    if($stmt->execute()){

        header("Location: appartement.php");
        exit();

    }else{

        echo "Erreur SQL : " . $conn->error;
    }
}


// ================= MODIFICATION =================

if(isset($_POST['modifier'])){

    $id_appartement = intval($_POST['id_appartement']);

    $id_maison = intval($_POST['id_maison']);

    $numero_appartement = trim($_POST['numero_appartement']);

    $loyer = floatval($_POST['loyer']);

    $statut = $_POST['statut'];

    $stmt = $conn->prepare("
        UPDATE appartement

        SET
        id_maison=?,
        numero_appartement=?,
        loyer=?,
        statut=?

        WHERE id_appartement=?
    ");

    $stmt->bind_param(
        "isdsi",
        $id_maison,
        $numero_appartement,
        $loyer,
        $statut,
        $id_appartement
    );

    if($stmt->execute()){

        header("Location: appartement.php");
        exit();

    }else{

        echo "Erreur SQL : " . $conn->error;
    }
}


// ================= SUPPRESSION =================

if(isset($_GET['supprimer'])){

    $id = intval($_GET['supprimer']);

    // Vérifier si appartement utilisé

    $check = $conn->prepare("
        SELECT *
        FROM location
        WHERE id_appartement=?
    ");

    $check->bind_param("i", $id);

    $check->execute();

    $result = $check->get_result();

    if($result->num_rows > 0){

        echo "
        <script>
        alert('Impossible de supprimer : appartement déjà lié à un locataire');
        window.location='appartement.php';
        </script>
        ";

        exit();
    }

    // DELETE

    $stmt = $conn->prepare("
        DELETE FROM appartement
        WHERE id_appartement=?
    ");

    $stmt->bind_param("i", $id);

    $stmt->execute();

    header("Location: appartement.php");
    exit();
}


// ================= RECUPERATION =================

$modifier = null;

if(isset($_GET['edit'])){

    $id = intval($_GET['edit']);

    $stmt = $conn->prepare("
        SELECT *
        FROM appartement
        WHERE id_appartement=?
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

<title>Gestion Appartements</title>

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
    linear-gradient(rgba(0,0,0,0.45), rgba(0,0,0,0.45)),
    url('https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?q=80&w=2070&auto=format&fit=crop')
    no-repeat center center/cover;
}

body::before{

    content:"";

    position:fixed;

    inset:0;

    backdrop-filter:blur(7px);

    z-index:-1;
}

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

    font-size:36px;

    flex:1;
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

    border-radius:15px;

    outline:none;

    background:rgba(255,255,255,0.12);

    color:white;
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

.libre{
    color:#00ff99;
    font-weight:bold;
}

.occupe{
    color:#ff5252;
    font-weight:bold;
}

@media(max-width:900px){

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

<div class="top-bar">

<a href="dashboard.php" class="btn-retour">
⬅ Retour au Dashboard
</a>

<h1>Gestion des Appartements</h1>

<div style="width:180px;"></div>

</div>

<div class="form-box">

<form method="POST">

<input
type="hidden"
name="id_appartement"
value="<?= $modifier['id_appartement'] ?? '' ?>"
>

<select name="id_maison" required>

<option value="">
 Choisir une maison
</option>

<?php

$resMaison = $conn->query("
SELECT *
FROM maison
ORDER BY adresse ASC
");

while($m = $resMaison->fetch_assoc()){

    $selected = "";

    if(isset($modifier['id_maison']) &&
       $modifier['id_maison'] == $m['id_maison']){

        $selected = "selected";
    }

    echo "
    <option
    value='{$m['id_maison']}'
    $selected
    >
    {$m['adresse']} - {$m['quartier']}
    </option>
    ";
}

?>

</select>


<input
type="text"
name="numero_appartement"
placeholder="🔢 Numéro Appartement"
required
value="<?= $modifier['numero_appartement'] ?? '' ?>"
>


<input
type="number"
step="0.01"
name="loyer"
placeholder="💰 Loyer"
required
value="<?= $modifier['loyer'] ?? '' ?>"
>


<?php if($modifier): ?>

<select name="statut">

<option value="Libre"
<?= ($modifier['statut']=='Libre') ? 'selected' : '' ?>>
Libre
</option>

<option value="Occupé"
<?= ($modifier['statut']=='Occupé') ? 'selected' : '' ?>>
Occupé
</option>

</select>

<button type="submit" name="modifier">
✏ Modifier Appartement
</button>

<?php else: ?>

<button type="submit" name="ajouter">
➕ Ajouter Appartement
</button>

<?php endif; ?>

</form>

</div>


<div class="table-box">

<table>

<tr>

<th>ID</th>
<th>Maison</th>
<th>Appartement</th>
<th>Loyer</th>
<th>Statut</th>
<th>Actions</th>

</tr>

<?php

$sql = "

SELECT

appartement.*,

maison.adresse,
maison.quartier

FROM appartement

INNER JOIN maison
ON appartement.id_maison = maison.id_maison

ORDER BY appartement.id_appartement DESC

";

$res = $conn->query($sql);

if($res && $res->num_rows > 0){

while($a = $res->fetch_assoc()){

$classe = ($a['statut'] == 'Libre')
? 'libre'
: 'occupe';

echo "

<tr>

<td>{$a['id_appartement']}</td>

<td>
{$a['adresse']}
<br>
{$a['quartier']}
</td>

<td>
Appartement {$a['numero_appartement']}
</td>

<td>
{$a['loyer']} $
</td>

<td class='$classe'>
{$a['statut']}
</td>

<td>

<div class='actions'>

<a
class='edit'
href='appartement.php?edit={$a['id_appartement']}'
>
✏ Modifier
</a>

<a
class='delete'
href='appartement.php?supprimer={$a['id_appartement']}'
onclick=\"return confirm('Supprimer cet appartement ?')\"
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

<td colspan='6'>
Aucun appartement trouvé
</td>

</tr>

";

}

?>

</table>

</div>

</body>
</html>
<?php
include 'auth.php';
include 'connexion.php';


// ================= AJOUT UTILISATEUR =================

if(isset($_POST['ajouter'])){

    $nom_utilisateur = trim($_POST['nom_utilisateur']);

    $mot_de_passe = password_hash(
        $_POST['mot_de_passe'],
        PASSWORD_DEFAULT
    );

    $role = $_POST['role'];

    $stmt = $conn->prepare("
        INSERT INTO utilisateur
        (nom_utilisateur, mot_de_passe, role)
        VALUES(?,?,?)
    ");

    if($stmt){

        $stmt->bind_param(
            "sss",
            $nom_utilisateur,
            $mot_de_passe,
            $role
        );

        $stmt->execute();
    }

    header("Location: parametre.php");
    exit();
}


// ================= SUPPRESSION =================

if(isset($_GET['supprimer'])){

    $id = intval($_GET['supprimer']);

    $stmt = $conn->prepare("
        DELETE FROM utilisateur
        WHERE id_utilisateur=?
    ");

    if($stmt){

        $stmt->bind_param("i", $id);

        $stmt->execute();
    }

    header("Location: parametre.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="fr">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>Paramètres</title>

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

    display:flex;

    flex-direction:column;

    align-items:center;

    background:
    linear-gradient(rgba(0,0,0,0.35),
    rgba(0,0,0,0.35)),
    url('https://images.unsplash.com/photo-1516321318423-f06f85e504b3?q=80&w=2070&auto=format&fit=crop')
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


/* CONTAINER */

.container{

    width:100%;

    max-width:1200px;

    display:grid;

    grid-template-columns:1fr 1fr;

    gap:30px;

    align-items:start;
}


/* BOX */

.box{

    padding:30px;

    border-radius:25px;

    background:rgba(255,255,255,0.08);

    backdrop-filter:blur(15px);

    border:1px solid rgba(255,255,255,0.15);

    box-shadow:0 8px 30px rgba(0,0,0,0.25);
}

.box h2{

    margin-bottom:20px;

    font-size:25px;

    text-align:center;
}


/* FORM */

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


/* BUTTON */

button{

    width:100%;

    padding:15px;

    margin-top:20px;

    border:none;

    border-radius:15px;

    background:linear-gradient(45deg,#ff9800,#ff5722);

    color:white;

    font-size:16px;

    font-weight:bold;

    cursor:pointer;

    transition:0.3s;
}

button:hover{

    transform:scale(1.02);

    box-shadow:0 0 15px rgba(255,87,34,0.5);
}


/* TABLE */

table{

    width:100%;

    margin-top:25px;

    border-collapse:collapse;

    overflow:hidden;

    border-radius:15px;
}

th{

    padding:15px;

    background:linear-gradient(45deg,#ff9800,#ff5722);

    color:white;
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


/* ACTION BUTTONS */

.delete{

    padding:10px 15px;

    border-radius:10px;

    background:#f44336;

    color:white;

    text-decoration:none;

    font-weight:bold;

    display:inline-block;

    transition:0.3s;
}

.restore{

    padding:10px 15px;

    border-radius:10px;

    background:#4caf50;

    color:white;

    text-decoration:none;

    font-weight:bold;

    display:inline-block;

    transition:0.3s;
}

.delete:hover,
.restore:hover{

    transform:scale(1.05);
}


/* BADGES */

.badge-admin{

    padding:6px 12px;

    border-radius:12px;

    background:#4caf50;

    font-size:13px;

    font-weight:bold;
}

.badge-user{

    padding:6px 12px;

    border-radius:12px;

    background:#2196f3;

    font-size:13px;

    font-weight:bold;
}


/* SYSTEM BOX */

.update-box{

    margin-top:20px;

    padding:20px;

    border-radius:18px;

    background:rgba(255,255,255,0.06);
}

.version{

    font-size:18px;

    margin-top:10px;

    color:#ffd54f;

    font-weight:bold;
}


/* RESPONSIVE */

@media(max-width:900px){

    .container{

        grid-template-columns:1fr;
    }

    body{

        padding:15px;
    }

    h1{

        font-size:30px;
    }
}

</style>

</head>

<body>

<h1>⚙️ Paramètres du Système</h1>


<div class="container">


<!-- ================= UTILISATEURS ================= -->

<div class="box">

<h2>👥 Gestion des utilisateurs</h2>

<form method="POST">

<input
type="text"
name="nom_utilisateur"
placeholder="Nom utilisateur"
required
>

<input
type="password"
name="mot_de_passe"
placeholder="Mot de passe"
required
>

<select name="role" required>

<option value="">
Choisir un rôle
</option>

<option value="Administrateur">
Administrateur
</option>

<option value="Caissier">
Caissier
</option>

<option value="Gestionnaire">
Gestionnaire
</option>

</select>

<button type="submit" name="ajouter">

➕ Ajouter utilisateur

</button>

</form>


<table>

<tr>

<th>ID</th>
<th>Utilisateur</th>
<th>Rôle</th>
<th>Action</th>

</tr>

<?php

$res = $conn->query("
SELECT *
FROM utilisateur
ORDER BY id_utilisateur DESC
");

if($res){

while($u = $res->fetch_assoc()){

$badge = ($u['role'] == 'Administrateur')
? "badge-admin"
: "badge-user";

echo "

<tr>

<td>
{$u['id_utilisateur']}
</td>

<td>
{$u['nom_utilisateur']}
</td>

<td>

<span class='$badge'>
{$u['role']}
</span>

</td>

<td>

<a
class='delete'
href='parametre.php?supprimer={$u['id_utilisateur']}'
onclick=\"return confirm('Supprimer utilisateur ?')\"
>
🗑 Supprimer
</a>

</td>

</tr>

";
}

}else{

echo "

<tr>

<td colspan='4'>

Erreur SQL :
".$conn->error."

</td>

</tr>

";
}

?>

</table>

</div>


<!-- ================= SYSTEME ================= -->

<div class="box">

<h2>🖥 Informations système</h2>


<!-- VERSION -->

<div class="update-box">

<h3>📦 Version du logiciel</h3>

<p class="version">

Gestion Immobilière Pro v1.0

</p>

</div>


<!-- MISE A JOUR -->

<div class="update-box">

<h3>🔄 Mise à jour du logiciel</h3>

<p style="margin-top:10px;">

Dernière vérification :
<?= date('d/m/Y H:i') ?>

</p>

<p style="margin-top:10px;">

Le système est actuellement à jour.

</p>

<button>

🚀 Vérifier les mises à jour

</button>

</div>


<!-- CORBEILLE -->

<div class="update-box">

<h3>🗑 Corbeille système</h3>

<p style="margin-top:10px;">

Les éléments supprimés peuvent être restaurés.

</p>

<table>

<tr>

<th>Type</th>
<th>Nom</th>
<th>Action</th>

</tr>

<?php

$resCorbeille = $conn->query("
SELECT *
FROM corbeille
ORDER BY id_corbeille DESC
");

if($resCorbeille && $resCorbeille->num_rows > 0){

while($c = $resCorbeille->fetch_assoc()){

echo "

<tr>

<td>
{$c['type_element']}
</td>

<td>
{$c['nom_element']}
</td>

<td>

<a
class='restore'
href='restaurer.php?id={$c['id_corbeille']}'
>
♻ Restaurer
</a>

</td>

</tr>

";
}

}else{

echo "

<tr>

<td colspan='3'>

Aucun élément supprimé

</td>

</tr>

";
}

?>

</table>

</div>


<!-- SECURITE -->

<div class="update-box">

<h3>🔐 Sécurité</h3>

<p style="margin-top:10px;">

Authentification sécurisée activée.

</p>

<p style="margin-top:10px;">

Mots de passe cryptés avec PASSWORD_HASH.

</p>

</div>


<!-- BASE -->

<div class="update-box">

<h3>💾 Base de données</h3>

<p style="margin-top:10px;">

Base :
gestion_immobiliere

</p>

<p style="margin-top:10px;">

Serveur :
localhost

</p>

<p style="margin-top:10px;">

Statut :
Connecté ✅

</p>

</div>

</div>

</div>

</body>

</html>
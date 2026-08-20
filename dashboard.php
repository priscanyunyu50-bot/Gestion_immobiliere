<?php
include 'auth.php';
include 'connexion.php';

// Récupération du rôle
$role = $_SESSION['role'] ?? 'Caissier';

// Initialisation des variables
$total_maisons = 0;
$total_appartements = 0;
$total_locataires = 0;
$total_paiement = 0;
$total_depense = 0;
$benefice = 0;

// Requêtes pour Administrateur et Gestionnaire
if ($role === 'Administrateur' || $role === 'Gestionnaire') {
    $res1 = $conn->query("SELECT COUNT(*) AS total FROM maison");
    if ($res1) { $total_maisons = $res1->fetch_assoc()['total']; }

    $res2 = $conn->query("SELECT COUNT(*) AS total FROM appartement");
    if ($res2) { $total_appartements = $res2->fetch_assoc()['total']; }

    $res3 = $conn->query("SELECT COUNT(*) AS total FROM locataire");
    if ($res3) { $total_locataires = $res3->fetch_assoc()['total']; }
}

// Requêtes pour Administrateur et Caissier
if ($role === 'Administrateur' || $role === 'Caissier') {
    $res4 = $conn->query("
    SELECT SUM(montant) AS total
    FROM paiement
    ");
    if ($res4) { $total_paiement = $res4->fetch_assoc()['total'] ?? 0; }

    $res5 = $conn->query("
    SELECT SUM(montant) AS total
    FROM depense
    ");
    if ($res5) { $total_depense = $res5->fetch_assoc()['total'] ?? 0; }

    $benefice = $total_paiement - $total_depense;
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>Dashboard</title>

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{

    font-family:Arial;

    display:flex;

    min-height:100vh;

    color:white;

    background:
    linear-gradient(rgba(0,0,0,0.35), rgba(0,0,0,0.35)),
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

/* SIDEBAR */

.sidebar{

    width:260px;

    padding:25px;

    background:rgba(255,255,255,0.08);

    backdrop-filter:blur(15px);

    border-right:1px solid rgba(255,255,255,0.1);
}

.sidebar h2{

    text-align:center;

    margin-bottom:30px;

    color:#fff;
}

.sidebar a{

    display:block;

    padding:15px;

    margin-bottom:12px;

    text-decoration:none;

    color:white;

    border-radius:12px;

    background:rgba(255,255,255,0.05);

    transition:0.3s;
}

.sidebar a:hover{

    background:#6a5af9;

    transform:translateX(5px);
}

/* MAIN */

.main{

    flex:1;

    padding:30px;
}

/* HEADER */

.header{

    padding:30px;

    border-radius:20px;

    background:rgba(255,255,255,0.08);

    backdrop-filter:blur(15px);

    border:1px solid rgba(255,255,255,0.1);
}

.header h1{

    margin-bottom:10px;
}

.header p{

    color:#ddd;
}

/* CARDS */

.cards{

    margin-top:25px;

    display:grid;

    grid-template-columns:
    repeat(auto-fit,minmax(220px,1fr));

    gap:20px;
}

.card{

    padding:25px;

    border-radius:18px;

    background:rgba(255,255,255,0.08);

    backdrop-filter:blur(15px);

    border:1px solid rgba(255,255,255,0.1);

    text-align:center;

    transition:0.3s;
}

.card:hover{

    transform:translateY(-5px);

    background:rgba(255,255,255,0.12);
}

.card h3{

    margin-bottom:15px;

    font-size:18px;
}

.card p{

    font-size:30px;

    font-weight:bold;

    color:#ffcf8b;
}

/* RESPONSIVE */

@media(max-width:900px){

    body{
        flex-direction:column;
    }

    .sidebar{

        width:100%;

        display:flex;

        flex-wrap:wrap;

        gap:10px;

        justify-content:center;
    }

    .sidebar a{

        width:45%;
        text-align:center;
    }

}

@media(max-width:600px){

    .sidebar a{

        width:100%;
    }

    .header h1{

        font-size:25px;
    }

}

</style>

</head>

<body>

<!-- SIDEBAR -->

<div class="sidebar">

<h2>🏢 ImmoPro</h2>

<a href="dashboard.php">
📊 Dashboard
</a>

<?php if ($role === 'Administrateur' || $role === 'Gestionnaire'): ?>
<a href="maison.php">
🏠 Maisons
</a>

<a href="appartement.php">
🏢 Appartements
</a>

<a href="locataire.php">
👤 Locataires
</a>
<?php endif; ?>

<?php if ($role === 'Administrateur' || $role === 'Caissier'): ?>
<a href="paiement.php">
💰 Paiements
</a>

<a href="depense.php">
💸 Dépenses
</a>

<a href="rapport.php">
📄 Rapports
</a>
<?php endif; ?>

<?php if ($role === 'Administrateur' || $role === 'Gestionnaire'): ?>
<a href="contrat.php">
📄 contrat
</a>
<?php endif; ?>

<?php if ($role === 'Administrateur'): ?>
<a href="parametre.php">
⚙ Paramètres
</a>
<?php endif; ?>

<a href="logout.php">
🚪 Déconnexion
</a>

</div>

<!-- MAIN -->

<div class="main">

<div class="header">

<h1>📊 Tableau de Bord</h1>

<p>
Bienvenue
<b><?= $_SESSION['username'] ?></b>
dans votre système de gestion immobilière
</p>

</div>

<!-- CARDS -->

<div class="cards">

<?php if ($role === 'Administrateur' || $role === 'Gestionnaire'): ?>
<div class="card">

<h3>🏠 Maisons</h3>

<p>
<?= $total_maisons ?>
</p>

</div>

<div class="card">

<h3>🏢 Appartements</h3>

<p>
<?= $total_appartements ?>
</p>

</div>

<div class="card">

<h3>👤 Locataires</h3>

<p>
<?= $total_locataires ?>
</p>

</div>
<?php endif; ?>

<?php if ($role === 'Administrateur' || $role === 'Caissier'): ?>
<div class="card">

<h3>💰 Paiements</h3>

<p>
<?= number_format($total_paiement,2) ?> $
</p>

</div>

<div class="card">

<h3>💸 Dépenses</h3>

<p>
<?= number_format($total_depense,2) ?> $
</p>

</div>

<div class="card">

<h3>📈 Bénéfice</h3>

<p>
<?= number_format($benefice,2) ?> $
</p>

</div>
<?php endif; ?>

</div>

</div>

</body>
</html>
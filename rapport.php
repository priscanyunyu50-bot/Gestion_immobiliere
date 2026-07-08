<?php
include 'auth.php';
include 'connexion.php';

$id_locataire = $_GET['id_locataire'] ?? null;

$rapport = null;
$paiements = [];
$total_paye = 0;
$statut = "";


// ================= STATS =================
$total_locataires = $conn->query("SELECT COUNT(*) AS total FROM locataire")->fetch_assoc()['total'];
$total_maisons = $conn->query("SELECT COUNT(*) AS total FROM maison")->fetch_assoc()['total'];
$total_appartements = $conn->query("SELECT COUNT(*) AS total FROM appartement")->fetch_assoc()['total'];

$total_occupes = $conn->query("SELECT COUNT(*) AS total FROM appartement WHERE statut='Occupé'")->fetch_assoc()['total'];
$total_libres = $conn->query("SELECT COUNT(*) AS total FROM appartement WHERE statut='Libre'")->fetch_assoc()['total'];

$total_paiements = $conn->query("SELECT SUM(montant) AS total FROM paiement")->fetch_assoc()['total'];
$total_depenses = $conn->query("SELECT SUM(montant) AS total FROM depense")->fetch_assoc()['total'];

$solde = $total_paiements - $total_depenses;


// ================= RAPPORT LOCATAIRE =================
if(!empty($id_locataire)){

$id = intval($id_locataire);

$res = $conn->query("
SELECT
locataire.nom,
locataire.postnom,
locataire.prenom,
appartement.numero_appartement,
appartement.loyer,
maison.adresse,
maison.quartier
FROM locataire
LEFT JOIN location ON locataire.id_locataire = location.id_locataire
LEFT JOIN appartement ON location.id_appartement = appartement.id_appartement
LEFT JOIN maison ON appartement.id_maison = maison.id_maison
WHERE locataire.id_locataire = $id
");

$rapport = $res->fetch_assoc();

$resP = $conn->query("
SELECT * FROM paiement
WHERE id_locataire = $id
ORDER BY date_paiement DESC
");

while($p = $resP->fetch_assoc()){
    $paiements[] = $p;
    $total_paye += $p['montant'];
}

if($rapport){
    $statut = ($total_paye >= $rapport['loyer']) ? "OK" : "NON OK";
}

}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Rapports</title>

<style>

/* ================= GENERAL ================= */
body{
    font-family:Arial;
    padding:30px;
    color:white;
    min-height:100vh;

    background:
    linear-gradient(rgba(0,0,0,0.4),rgba(0,0,0,0.4)),
    url('https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?q=80&w=2070&auto=format&fit=crop')
    no-repeat center center/cover;
}

h1{
    text-align:center;
    margin-bottom:15px;
}

/* ================= STATS ================= */
.cards{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(180px,1fr));
    gap:15px;
    margin-bottom:15px;
}

.card{
    background:rgba(255,255,255,0.08);
    padding:12px;
    border-radius:12px;
    text-align:center;
}

.card p{
    font-size:20px;
    font-weight:bold;
    color:#ffd54f;
}

/* ================= FORM ================= */
.form-box{
    background:rgba(255,255,255,0.08);
    padding:12px;
    border-radius:12px;
    margin-bottom:10px;
}

select, button{
    padding:8px;
    width:100%;
    margin-top:6px;
    border:none;
    border-radius:8px;
}

/* ================= SMALL BUTTONS ================= */
.print-btn{
    background:#2196f3;
    color:white;
    padding:6px 10px;
    border:none;
    border-radius:8px;
    font-size:12px;
    font-weight:bold;
    cursor:pointer;
    margin:6px 0;
    width:auto;
}

/* ================= REPORT ================= */
.report-box{
    background:rgba(255,255,255,0.10);
    padding:12px;
    border-radius:12px;
    margin-top:10px;
}

.ok{color:#00e676;font-weight:bold;}
.no{color:#ff5252;font-weight:bold;}

/* ================= TABLE ================= */
.table-box{
    margin-top:10px;
    background:rgba(255,255,255,0.08);
    padding:12px;
    border-radius:12px;
}

table{
    width:100%;
    border-collapse:collapse;
}

th{
    background:#3f51b5;
    padding:8px;
}

td{
    text-align:center;
    padding:8px;
    border-bottom:1px solid rgba(255,255,255,0.2);
}

/* ================= PRINT MODE ================= */
body.print-stats .locataire-section{display:none;}
body.print-locataire .cards{display:none;}

/* ================= PRINT STYLE ================= */
@media print {

    body{
        background:white !important;
        color:black !important;
    }

    .form-box,
    .print-btn,
    button,
    a{
        display:none !important;
    }

    .report-box,
    .table-box,
    .card{
        background:white !important;
        color:black !important;
    }

    th{
        background:#ddd !important;
        color:black !important;
    }
}

</style>

</head>

<body>

<h1> Rapports</h1>

<!-- ================= STATS ================= -->
<div class="cards">

<div class="card"><h2>Locataires</h2><p><?= $total_locataires ?></p></div>
<div class="card"><h2>Maisons</h2><p><?= $total_maisons ?></p></div>
<div class="card"><h2>Appartements</h2><p><?= $total_appartements ?></p></div>
<div class="card"><h2>Libres</h2><p><?= $total_libres ?></p></div>
<div class="card"><h2>Occupés</h2><p><?= $total_occupes ?></p></div>
<div class="card"><h2>Paiements</h2><p><?= number_format($total_paiements,2) ?> $</p></div>
<div class="card"><h2>Dépenses</h2><p><?= number_format($total_depenses,2) ?> $</p></div>
<div class="card"><h2>Solde</h2><p><?= number_format($solde,2) ?> $</p></div>

</div>

<!-- ================= PRINT STATS ================= -->
<button class="print-btn" onclick="printStats()">🖨 imprimer Stats</button>

<!-- ================= FILTRE ================= -->
<div class="form-box">

<form method="GET">

<select name="id_locataire">

<option value="">👤 Choisir un locataire</option>

<?php
$resL = $conn->query("SELECT * FROM locataire");
while($l = $resL->fetch_assoc()){
echo "<option value='{$l['id_locataire']}'>{$l['nom']} {$l['postnom']} {$l['prenom']}</option>";
}
?>

</select>

<button type="submit">🔎 Voir</button>

</form>

</div>

<!-- ================= RAPPORT LOCATAIRE ================= -->
<?php if($rapport): ?>

<div class="locataire-section">

<button class="print-btn" onclick="printLocataire()">🖨 imprimer Locataire</button>

<button class="print-btn" onclick="closeReport()">❌ Fermer</button>

<div class="report-box">

<p><b>Nom :</b> <?= $rapport['nom'] ?> <?= $rapport['postnom'] ?> <?= $rapport['prenom'] ?></p>
<p><b>Maison :</b> <?= $rapport['adresse'] ?> - <?= $rapport['quartier'] ?></p>
<p><b>Appartement :</b> <?= $rapport['numero_appartement'] ?></p>
<p><b>Loyer :</b> <?= $rapport['loyer'] ?> $</p>
<p><b>Total payé :</b> <?= $total_paye ?> $</p>

<p><b>Statut :</b>
<span class="<?= ($statut=='OK')?'ok':'no' ?>">
<?= $statut ?>
</span>
</p>

</div>

<div class="table-box">

<h3>💰 Paiements</h3>

<table>
<tr>
<th>Mois</th>
<th>Montant</th>
<th>Date</th>
</tr>

<?php foreach($paiements as $p): ?>
<tr>
<td><?= $p['mois'] ?></td>
<td><?= $p['montant'] ?> $</td>
<td><?= $p['date_paiement'] ?></td>
</tr>
<?php endforeach; ?>

</table>

</div>

</div>

<?php endif; ?>

<script>
function printStats(){
    document.body.classList.add("print-stats");
    document.body.classList.remove("print-locataire");
    window.print();
}

function printLocataire(){
    document.body.classList.add("print-locataire");
    document.body.classList.remove("print-stats");
    window.print();
}

function closeReport(){
    document.querySelector(".locataire-section").style.display = "none";
    document.querySelector("select[name='id_locataire']").value = "";
}
</script>

</body>
</html>
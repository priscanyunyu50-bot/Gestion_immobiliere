<?php

include 'auth.php';
include 'connexion.php';

// ================= VARIABLES POUR MODIFICATION =================
$edit_mode = false;
$id_edit = 0;
$id_locataire_edit = '';
$id_appartement_edit = '';
$date_debut_edit = '';
$date_fin_edit = '';
$montant_loyer_edit = '';
$caution_edit = '';
$conditions_edit = '';

// ================= RECUPERATION POUR MODIFICATION =================
if (isset($_GET['modifier'])) {
    $id_edit = intval($_GET['modifier']);
    $stmt_edit = $conn->prepare("SELECT * FROM contrat WHERE id_contrat = ?");
    if ($stmt_edit) {
        $stmt_edit->bind_param("i", $id_edit);
        $stmt_edit->execute();
        $res_edit = $stmt_edit->get_result();
        if ($row = $res_edit->fetch_assoc()) {
            $edit_mode = true;
            $id_locataire_edit = $row['id_locataire'];
            $id_appartement_edit = $row['id_appartement'];
            $date_debut_edit = $row['date_debut'];
            $date_fin_edit = $row['date_fin'];
            $montant_loyer_edit = $row['montant'];
            $caution_edit = $row['caution'];
            $conditions_edit = $row['conditions'];
        }
    }
}

// ================= AJOUT / MODIFICATION CONTRAT =================
if (isset($_POST['ajouter']) || isset($_POST['modifier'])) {

    $id_locataire = $_POST['id_locataire'];
    $id_appartement = $_POST['id_appartement'];
    $date_debut = $_POST['date_debut'];
    $date_fin = $_POST['date_fin'];
    $montant_loyer = $_POST['montant_loyer'];
    $caution = $_POST['caution'];
    $conditions = $_POST['conditions'];

    // Calcul du Statut
    $today = date('Y-m-d');
    if ($date_debut > $today) {
        $statut = "FUTUR";
    } elseif ($date_fin < $today) {
        $statut = "TERMINE";
    } else {
        $statut = "ACTIF";
    }

    if (isset($_POST['modifier'])) {
        // UPDATE
        $id_contrat_update = intval($_POST['id_contrat']);
        $stmt = $conn->prepare("
            UPDATE contrat 
            SET id_locataire=?, id_appartement=?, date_debut=?, date_fin=?, montant=?, caution=?, statut=?, conditions=?
            WHERE id_contrat=?
        ");
        if (!$stmt) {
            die("Erreur SQL lors de la modification : " . $conn->error);
        }
        $stmt->bind_param("iissddssi", $id_locataire, $id_appartement, $date_debut, $date_fin, $montant_loyer, $caution, $statut, $conditions, $id_contrat_update);
        $stmt->execute();

    } else {
        // INSERT
        $stmt = $conn->prepare("
            INSERT INTO contrat
            (id_locataire, id_appartement, date_debut, date_fin, montant, caution, statut, conditions)
            VALUES(?,?,?,?,?,?,?,?)
        ");
        if (!$stmt) {
            die("Erreur SQL lors de l'ajout : " . $conn->error);
        }
        $stmt->bind_param("iissddss", $id_locataire, $id_appartement, $date_debut, $date_fin, $montant_loyer, $caution, $statut, $conditions);
        $stmt->execute();
    }

    header("Location: contrat.php");
    exit();
}

// ================= SUPPRESSION =================
if (isset($_GET['supprimer'])) {
    $id = intval($_GET['supprimer']);
    $stmt = $conn->prepare("DELETE FROM contrat WHERE id_contrat=?");
    if (!$stmt) {
        die("Erreur SQL lors de la suppression : " . $conn->error);
    }
    $stmt->bind_param("i", $id);
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
    font-family: Arial, sans-serif;
    padding:30px;
    color:white;
    min-height:100vh;
    background: linear-gradient(rgba(0,0,0,0.45),rgba(0,0,0,0.45)),
    url('https://images.unsplash.com/photo-1554995207-c18c203602cb?q=80&w=2070&auto=format&fit=crop')
    no-repeat center center/cover;
}

/* ================= EN-TÊTE ET BOUTON RETOUR ================= */
.top-bar{
    display:flex;
    align-items:center;
    justify-content:space-between;
    margin-bottom:20px;
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
    box-sizing: border-box;
}

textarea{ resize:none; }
select option{ color:black; }

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

button.btn-cancel{
    background: #757575;
    margin-top: 5px;
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
    z-index: 10;
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

/* ================= STATUS & ACTIONS ================= */
.badge{
    padding:6px 10px;
    border-radius:10px;
    font-weight:bold;
    color:white;
}

.actif{background:#4caf50;}
.termine{background:#f44336;}
.futur{background:#ff9800;}

.btn-action{
    padding:5px 8px;
    color:white;
    text-decoration:none;
    border-radius:6px;
    font-weight:bold;
    margin: 0 2px;
    display: inline-block;
}
.edit { background:#ff9800; }
.delete { background:#f44336; }
.print-single { background:#2196f3; }

/* ================= PRINT MODE ================= */
@media print {
    body{
        background:white !important;
        color:black !important;
    }
    .top-bar, .form-box, .print-btn-small, .btn-action{
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
    .top-bar{
        flex-direction:column;
        gap:15px;
        text-align:center;
    }
    .form-box{width:100%;}
}
</style>
</head>

<body>

<!-- ================= TOP BAR WITH BACK BUTTON ================= -->
<div class="top-bar">
    <a href="dashboard.php" class="btn-retour">
        ⬅ Retour au Dashboard
    </a>
    <h1>Gestion des Contrats</h1>
    <div style="width:180px;"></div>
</div>

<!-- ================= PRINT BUTTON ================= -->
<button class="print-btn-small" onclick="window.print()" title="Imprimer le tableau">
🖨
</button>

<!-- ================= FORM ================= -->
<div class="form-box">
<form method="POST">

<?php if($edit_mode): ?>
    <input type="hidden" name="id_contrat" value="<?= $id_edit ?>">
<?php endif; ?>

<select name="id_locataire" required>
<option value="">👤 Locataire</option>
<?php
$res = $conn->query("SELECT * FROM locataire ORDER BY nom ASC");
if ($res) {
    while($l = $res->fetch_assoc()){
        $selected = ($edit_mode && $id_locataire_edit == $l['id_locataire']) ? 'selected' : '';
        echo "<option value='{$l['id_locataire']}' {$selected}>".htmlspecialchars($l['nom'])." ".htmlspecialchars($l['postnom'])." ".htmlspecialchars($l['prenom'])."</option>";
    }
}
?>
</select>

<select name="id_appartement" required>
<option value="">🏢 Appartement</option>
<?php
$res = $conn->query("SELECT * FROM appartement ORDER BY numero_appartement ASC");
if ($res) {
    while($a = $res->fetch_assoc()){
        $selected = ($edit_mode && $id_appartement_edit == $a['id_appartement']) ? 'selected' : '';
        echo "<option value='{$a['id_appartement']}' {$selected}>Appartement ".htmlspecialchars($a['numero_appartement'])."</option>";
    }
}
?>
</select>

<input type="date" name="date_debut" value="<?= htmlspecialchars($date_debut_edit) ?>" required>
<input type="date" name="date_fin" value="<?= htmlspecialchars($date_fin_edit) ?>" required>

<input type="number" step="0.01" name="montant_loyer" placeholder="Loyer" value="<?= htmlspecialchars($montant_loyer_edit) ?>" required>
<input type="number" step="0.01" name="caution" placeholder="Caution" value="<?= htmlspecialchars($caution_edit) ?>" required>

<textarea name="conditions" rows="4" placeholder="🧾 Conditions du contrat"><?= htmlspecialchars($conditions_edit) ?></textarea>

<?php if($edit_mode): ?>
    <button type="submit" name="modifier" style="background:#ff9800;">✏️ Modifier Contrat</button>
    <a href="contrat.php"><button type="button" class="btn-cancel">Annuler</button></a>
<?php else: ?>
    <button type="submit" name="ajouter">➕ Créer Contrat</button>
<?php endif; ?>

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
<th>Actions</th>
</tr>

<?php
$res = $conn->query("
SELECT contrat.*, locataire.nom, locataire.postnom, locataire.prenom, appartement.numero_appartement
FROM contrat
INNER JOIN locataire ON contrat.id_locataire = locataire.id_locataire
INNER JOIN appartement ON contrat.id_appartement = appartement.id_appartement
ORDER BY contrat.id_contrat DESC
");

if ($res) {
    while($c = $res->fetch_assoc()){

        $statusClass = ($c['statut']=='ACTIF') ? 'actif' :
        (($c['statut']=='TERMINE') ? 'termine' : 'futur');

        $nomComplet = htmlspecialchars($c['nom'].' '.$c['postnom'].' '.$c['prenom']);

        echo '
        <tr>
        <td>'.$c['id_contrat'].'</td>
        <td>'.$nomComplet.'</td>
        <td>'.htmlspecialchars($c['numero_appartement']).'</td>
        <td>'.$c['date_debut'].'</td>
        <td>'.$c['date_fin'].'</td>
        <td>'.$c['montant'].' $</td>
        <td>'.$c['caution'].' $</td>
        <td style="max-width:180px; font-size:12px;">
        '.htmlspecialchars(substr($c['conditions'] ?? '', 0, 80)).'
        </td>
        <td>
        <span class="badge '.$statusClass.'">'.$c['statut'].'</span>
        </td>
        <td>
        <a class="btn-action edit" href="contrat.php?modifier='.$c['id_contrat'].'" title="Modifier">✏️</a>
        <a class="btn-action print-single" href="#" onclick="imprimerUnContrat(\''.$c['id_contrat'].'\', \''.$nomComplet.'\', \''.$c['numero_appartement'].'\', \''.$c['date_debut'].'\', \''.$c['date_fin'].'\', \''.$c['montant'].'\', \''.$c['caution'].'\', \''.addslashes(htmlspecialchars($c['conditions'] ?? '')).'\')" title="Imprimer le reçu">📄</a>
        <a class="btn-action delete" href="contrat.php?supprimer='.$c['id_contrat'].'" onclick="return confirm(\'Supprimer ce contrat ?\')" title="Supprimer">🗑</a>
        </td>
        </tr>
        ';
    }
}
?>
</table>
</div>

<!-- ================= JAVASCRIPT IMPRESSION INDIVIDUELLE ================= -->
<script>
function imprimerUnContrat(id, locataire, appt, debut, fin, loyer, caution, conditions) {
    var contenu = `
    <html>
    <head>
        <title>Contrat N° ${id}</title>
        <style>
            body { font-family: Arial, sans-serif; padding: 40px; color: #333; }
            .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; }
            .content { margin-top: 20px; font-size: 16px; line-height: 1.6; }
            .box { background: #f9f9f9; border: 1px solid #ddd; padding: 15px; border-radius: 8px; margin-top: 15px; }
            .footer { margin-top: 50px; display: flex; justify-content: space-between; }
        </style>
    </head>
    <body>
        <div class="header">
            <h2>CONTRAT DE BAIL / REÇU N° ${id}</h2>
        </div>
        <div class="content">
            <p><strong>Locataire :</strong> ${locataire}</p>
            <p><strong>Appartement :</strong> N° ${appt}</p>
            <p><strong>Période du contrat :</strong> du ${debut} au ${fin}</p>
            <div class="box">
                <p><strong>Montant du Loyer :</strong> ${loyer} $</p>
                <p><strong>Caution versée :</strong> ${caution} $</p>
            </div>
            <div class="box">
                <strong>Conditions et remarques :</strong><br>
                ${conditions || 'Aucune condition particulière.'}
            </div>
        </div>
        <div class="footer">
            <div>Signature du Bailleur</div>
            <div>Signature du Locataire</div>
        </div>
    </body>
    </html>
    `;
    
    var win = window.open('', '', 'height=700,width=800');
    win.document.write(contenu);
    win.document.close();
    win.focus();
    setTimeout(function() {
        win.print();
        win.close();
    }, 500);
}
</script>

</body>
</html>
<?php
include 'auth.php';
include 'connexion.php';

// Variables pour le formulaire de modification
$edit_mode = false;
$edit_id = 0;
$edit_nom = '';
$edit_postnom = '';
$edit_prenom = '';
$edit_telephone = '';
$edit_garantie = '';
$edit_id_appartement = 0;
$edit_id_maison = 0;

// ================= RECUPERATION POUR MODIFICATION =================
if (isset($_GET['modifier'])) {
    $edit_id = intval($_GET['modifier']);
    
    $stmt_edit = $conn->prepare("
        SELECT l.*, loc.id_appartement, a.id_maison
        FROM locataire l
        LEFT JOIN location loc ON l.id_locataire = loc.id_locataire
        LEFT JOIN appartement a ON loc.id_appartement = a.id_appartement
        WHERE l.id_locataire = ?
    ");
    $stmt_edit->bind_param("i", $edit_id);
    $stmt_edit->execute();
    $res_edit = $stmt_edit->get_result();

    if ($row_edit = $res_edit->fetch_assoc()) {
        $edit_mode = true;
        $edit_nom = $row_edit['nom'];
        $edit_postnom = $row_edit['postnom'];
        $edit_prenom = $row_edit['prenom'];
        $edit_telephone = $row_edit['telephone'];
        $edit_garantie = $row_edit['garantie'];
        $edit_id_appartement = $row_edit['id_appartement'];
        $edit_id_maison = $row_edit['id_maison'];
    }
}


// ================= TRAITEMENT MODIFICATION =================
if (isset($_POST['modifier_action'])) {
    $id_locataire = intval($_POST['id_locataire']);
    $nom = $_POST['nom'];
    $postnom = $_POST['postnom'];
    $prenom = $_POST['prenom'];
    $telephone = $_POST['telephone'];
    $garantie = $_POST['garantie'];
    $id_appartement_nouveau = intval($_POST['id_appartement']);

    // 1. Mise à jour des informations personnelles du locataire
    $stmt_up = $conn->prepare("
        UPDATE locataire 
        SET nom = ?, postnom = ?, prenom = ?, telephone = ?, garantie = ?
        WHERE id_locataire = ?
    ");
    $stmt_up->bind_param("ssssdi", $nom, $postnom, $prenom, $telephone, $garantie, $id_locataire);
    $stmt_up->execute();

    // 2. Vérification si l'appartement a changé
    $res_loc = $conn->query("SELECT id_appartement FROM location WHERE id_locataire = '$id_locataire'");
    $loc_actuel = $res_loc->fetch_assoc();
    $id_appartement_actuel = $loc_actuel ? $loc_actuel['id_appartement'] : 0;

    if ($id_appartement_actuel != $id_appartement_nouveau) {
        // Libérer l'ancien appartement s'il existait
        if ($id_appartement_actuel > 0) {
            $conn->query("UPDATE appartement SET statut='Libre' WHERE id_appartement='$id_appartement_actuel'");
        }

        // Mettre à jour la table location
        $conn->query("DELETE FROM location WHERE id_locataire='$id_locataire'");
        $date_debut = date('Y-m-d');
        $stmt_new_loc = $conn->prepare("INSERT INTO location (id_locataire, id_appartement, date_debut) VALUES (?, ?, ?)");
        $stmt_new_loc->bind_param("iis", $id_locataire, $id_appartement_nouveau, $date_debut);
        $stmt_new_loc->execute();

        // Occuper le nouvel appartement
        $conn->query("UPDATE appartement SET statut='Occupé' WHERE id_appartement='$id_appartement_nouveau'");
    }

    header("Location: locataire.php");
    exit();
}


// ================= AJOUT LOCATAIRE =================
if (isset($_POST['ajouter'])) {

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

    if ($check->num_rows == 0) {
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
        INSERT INTO locataire (nom, postnom, prenom, telephone, garantie)
        VALUES(?,?,?,?,?)
    ");
    $stmt->bind_param("ssssd", $nom, $postnom, $prenom, $telephone, $garantie);

    if ($stmt->execute()) {
        $id_locataire = $conn->insert_id;
        $date_debut = date('Y-m-d');

        // Ajouter location
        $stmt2 = $conn->prepare("
            INSERT INTO location (id_locataire, id_appartement, date_debut)
            VALUES(?,?,?)
        ");
        $stmt2->bind_param("iis", $id_locataire, $id_appartement, $date_debut);
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
if (isset($_GET['supprimer'])) {

    $id = intval($_GET['supprimer']);

    $res = $conn->query("
        SELECT id_appartement
        FROM location
        WHERE id_locataire='$id'
    ");

    $loc = $res->fetch_assoc();

    if ($loc) {
        $id_appartement = $loc['id_appartement'];

        $conn->query("
            UPDATE appartement
            SET statut='Libre'
            WHERE id_appartement='$id_appartement'
        ");
    }

    $conn->query("DELETE FROM location WHERE id_locataire='$id'");
    $conn->query("DELETE FROM locataire WHERE id_locataire='$id'");

    header("Location: locataire.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion Locataires</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            min-height: 100vh;
            padding: 30px;
            color: white;
            background:
                linear-gradient(rgba(0, 0, 0, 0.35), rgba(0, 0, 0, 0.35)),
                url('https://images.unsplash.com/photo-1564013799919-ab600027ffc6?q=80&w=2070&auto=format&fit=crop') no-repeat center center/cover;
        }

        body::before {
            content: "";
            position: fixed;
            inset: 0;
            backdrop-filter: blur(8px);
            z-index: -1;
        }

        /* ===== BOUTON RETOUR ===== */
        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            border-radius: 15px;
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            text-decoration: none;
            font-weight: bold;
            font-size: 15px;
            transition: 0.3s ease;
            margin-bottom: 20px;
        }

        .btn-back:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateX(-4px);
        }

        h1 {
            text-align: center;
            margin-bottom: 30px;
            font-size: 38px;
        }

        .form-box {
            width: 60%;
            margin: auto;
            padding: 30px;
            border-radius: 25px;
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.15);
        }

        input,
        select {
            width: 100%;
            padding: 15px;
            margin-top: 15px;
            border: none;
            outline: none;
            border-radius: 15px;
            background: rgba(255, 255, 255, 0.12);
            color: white;
            font-size: 15px;
        }

        input::placeholder {
            color: #ddd;
        }

        select option {
            color: black;
        }

        button, .btn-cancel {
            width: 100%;
            padding: 15px;
            margin-top: 20px;
            border: none;
            border-radius: 15px;
            background: linear-gradient(45deg, #2196f3, #42a5f5);
            color: white;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
            text-align: center;
            display: inline-block;
            text-decoration: none;
        }

        .btn-update {
            background: linear-gradient(45deg, #4caf50, #81c784);
        }

        .btn-cancel {
            background: linear-gradient(45deg, #ff9800, #ffb74d);
            margin-top: 10px;
        }

        button:hover, .btn-cancel:hover {
            transform: scale(1.02);
        }

        .table-box {
            margin-top: 40px;
            padding: 25px;
            border-radius: 25px;
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(15px);
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            padding: 15px;
            background: linear-gradient(45deg, #2196f3, #42a5f5);
        }

        td {
            padding: 15px;
            text-align: center;
            background: rgba(255, 255, 255, 0.05);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        tr:hover td {
            background: rgba(255, 255, 255, 0.1);
        }

        .delete {
            padding: 8px 12px;
            border-radius: 10px;
            background: #f44336;
            color: white;
            text-decoration: none;
            font-weight: bold;
            font-size: 13px;
            display: inline-block;
            margin: 2px;
        }

        .edit {
            padding: 8px 12px;
            border-radius: 10px;
            background: #ff9800;
            color: white;
            text-decoration: none;
            font-weight: bold;
            font-size: 13px;
            display: inline-block;
            margin: 2px;
        }

        .ok {
            color: #00ff99;
            font-weight: bold;
        }

        @media(max-width:900px) {
            .form-box {
                width: 100%;
            }
        }
    </style>
</head>

<body>

    <!-- BOUTON RETOUR DYNAMIQUE -->
    <?php if ($edit_mode): ?>
        <a href="locataire.php" class="btn-back">⬅ Annuler la modification</a>
    <?php else: ?>
        <a href="dashboard.php" class="btn-back">⬅ Retour au tableau de bord</a>
    <?php endif; ?>

    <h1>Gestion des Locataires</h1>

    <!-- ================= FORMULAIRE ================= -->
    <div class="form-box">

        <form method="POST">

            <?php if ($edit_mode): ?>
                <input type="hidden" name="id_locataire" value="<?= $edit_id ?>">
                <h3 style="text-align:center; margin-bottom:10px; color:#00ff99;">✏️ Modifier Locataire #<?= $edit_id ?></h3>
            <?php endif; ?>

            <input type="text" name="nom" placeholder="Nom" value="<?= htmlspecialchars($edit_nom) ?>" required>
            <input type="text" name="postnom" placeholder="Postnom" value="<?= htmlspecialchars($edit_postnom) ?>" required>
            <input type="text" name="prenom" placeholder="Prénom" value="<?= htmlspecialchars($edit_prenom) ?>" required>
            <input type="text" name="telephone" placeholder="Téléphone" value="<?= htmlspecialchars($edit_telephone) ?>" required>

            <!-- CHOISIR MAISON -->
            <select id="maison" required>
                <option value="">🏠 Choisir une maison</option>
                <?php
                $resMaison = $conn->query("SELECT * FROM maison ORDER BY adresse ASC");
                while ($m = $resMaison->fetch_assoc()) {
                    $selected = ($m['id_maison'] == $edit_id_maison) ? 'selected' : '';
                    echo "<option value='{$m['id_maison']}' $selected>{$m['adresse']} - {$m['quartier']}</option>";
                }
                ?>
            </select>

            <!-- CHOISIR APPARTEMENT -->
            <select name="id_appartement" id="appartement" required>
                <option value="">🏢 Choisir appartement</option>
                <?php
                // Affiche les appartements LIBRES OU l'appartement actuellement occupé par le locataire à modifier
                $sqlApp = "
                    SELECT appartement.*, maison.adresse
                    FROM appartement
                    INNER JOIN maison ON appartement.id_maison = maison.id_maison
                    WHERE appartement.statut='Libre' OR appartement.id_appartement = '$edit_id_appartement'
                ";

                $resApp = $conn->query($sqlApp);
                while ($a = $resApp->fetch_assoc()) {
                    $selected = ($a['id_appartement'] == $edit_id_appartement) ? 'selected' : '';
                    echo "
                    <option 
                        value='{$a['id_appartement']}' 
                        data-maison='{$a['id_maison']}' 
                        data-loyer='{$a['loyer']}'
                        $selected
                    >
                        Appartement {$a['numero_appartement']} " . ($a['statut'] == 'Occupé' ? '(Actuel)' : '') . "
                    </option>";
                }
                ?>
            </select>

            <!-- LOYER -->
            <input type="text" id="loyer" placeholder="💰 Loyer" readonly>

            <input type="number" step="0.01" name="garantie" placeholder="Garantie" value="<?= htmlspecialchars($edit_garantie) ?>" required>

            <?php if ($edit_mode): ?>
                <button type="submit" name="modifier_action" class="btn-update">💾 Enregistrer la modification</button>
                <a href="locataire.php" class="btn-cancel">❌ Annuler</a>
            <?php else: ?>
                <button type="submit" name="ajouter">➕ Ajouter Locataire</button>
            <?php endif; ?>

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
                LEFT JOIN location ON locataire.id_locataire = location.id_locataire
                LEFT JOIN appartement ON location.id_appartement = appartement.id_appartement
                LEFT JOIN maison ON appartement.id_maison = maison.id_maison
                ORDER BY locataire.id_locataire DESC
            ";

            $res = $conn->query($sql);

            while ($row = $res->fetch_assoc()) {
                echo "
                <tr>
                    <td>{$row['id_locataire']}</td>
                    <td>{$row['nom']} {$row['postnom']} {$row['prenom']}</td>
                    <td>{$row['telephone']}</td>
                    <td>{$row['adresse']}<br>{$row['quartier']}</td>
                    <td>Appartement {$row['numero_appartement']}</td>
                    <td>{$row['loyer']} $</td>
                    <td>{$row['garantie']} $</td>
                    <td class='ok'>{$row['statut']}</td>
                    <td>
                        <a class='edit' href='?modifier={$row['id_locataire']}'>✏️ Modifier</a>
                        <a class='delete' href='?supprimer={$row['id_locataire']}' onclick=\"return confirm('Supprimer ce locataire ?')\">🗑 Supprimer</a>
                    </td>
                </tr>
                ";
            }
            ?>
        </table>
    </div>

    <script>
        const maison = document.getElementById("maison");
        const appartement = document.getElementById("appartement");
        const loyer = document.getElementById("loyer");

        function updateAppartements() {
            let idMaison = maison.value;
            for (let option of appartement.options) {
                if (option.value == "") continue;
                if (option.dataset.maison == idMaison) {
                    option.style.display = "block";
                } else {
                    option.style.display = "none";
                }
            }
        }

        function updateLoyer() {
            let selected = appartement.options[appartement.selectedIndex];
            if (selected && selected.dataset.loyer) {
                loyer.value = selected.dataset.loyer + " $";
            } else {
                loyer.value = "";
            }
        }

        // Initialisation si en mode modification
        if (maison.value) {
            updateAppartements();
            updateLoyer();
        }

        maison.addEventListener("change", function() {
            updateAppartements();
            appartement.value = "";
            loyer.value = "";
        });

        appartement.addEventListener("change", updateLoyer);
    </script>

</body>
</html>
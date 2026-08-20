<?php
include 'auth.php';
include 'connexion.php';

// Activer le mode d'erreur strict
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$dateAujourdhui = date("Y-m-d");

// Requête pour récupérer les paiements non payés
$sql = "SELECT p.id_paiement AS id, p.date_paiement AS date_echeance, p.montant, p.dernier_rappel,
               l.nom, l.prenom, l.telephone
        FROM paiement p
        INNER JOIN locataire l ON p.id_locataire = l.id_locataire
        WHERE p.statut = 'Non payé'";

$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();

// Préparation de la mise à jour pour marquer que le rappel a été envoyé
$update = $conn->prepare("UPDATE paiement SET dernier_rappel = ? WHERE id_paiement = ?");

while ($row = $result->fetch_assoc()) {

    $nomComplet     = $row['nom'] . " " . $row['prenom'];
    $telephone      = $row['telephone'];
    $echeance       = $row['date_echeance'];
    $dernierRappel  = $row['dernier_rappel'];

    // Formatage de la date d'échéance au format FR (ex: 05/08/2026)
    $dateFormatee = date("d/m/Y", strtotime($echeance));

    // Calcul de l'écart en jours entre aujourd'hui et l'échéance
    $datetimeAujourdhui = new DateTime($dateAujourdhui);
    $datetimeEcheance   = new DateTime($echeance);
    
    // Résultat négatif si l'échéance est dans le futur
    $diffJours = (int)$datetimeEcheance->diff($datetimeAujourdhui)->format("%r%a");

    // Condition : Entre 7 jours avant et le jour même de l'échéance (-7 à 0 jours)
    if ($diffJours >= -7 && $diffJours <= 0) {
        
        // S'assurer qu'aucun rappel n'a déjà été envoyé pour cette échéance
        if (empty($dernierRappel)) {

            // VOTRE MESSAGE DE RAPPEL :
            $message = "Votre loyer arrive bientôt à échéance. Merci de prévoir le paiement avant le " . $dateFormatee . ".";

            // -------------------------------------------------------------
            // Envoi de la notification (ex: via API SMS / WhatsApp / Twilio)
            // -------------------------------------------------------------
            
            echo "<strong>Rappel envoyé à " . htmlspecialchars($nomComplet) . " (" . htmlspecialchars($telephone) . ") :</strong><br>";
            echo "<em>\"" . htmlspecialchars($message) . "\"</em><br><br>";

            // Sauvegarder la date d'envoi pour éviter de renvoyer le même message
            $maintenant = date("Y-m-d H:i:s");
            $update->bind_param("si", $maintenant, $row['id']);
            $update->execute();
        }
    }
}

$stmt->close();
$update->close();

echo "Vérification et envois terminés.";
?>
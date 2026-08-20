<?php
session_start();
include 'connexion.php';

$error = "";

if (isset($_POST['login'])) {

    $username = trim($_POST['username']);
    $motdepasse = trim($_POST['motdepasse']);

    // Correction 1 & 2 : Table 'utilisateur' et colonne 'nom_utilisateur'
    $stmt = $conn->prepare("SELECT * FROM utilisateur WHERE nom_utilisateur = ?");

    if ($stmt) {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res && $res->num_rows > 0) {

            $user = $res->fetch_assoc();

            // Récupération du mot de passe en base (nom de colonne : mot_de_passe ou motdepasse)
            $hash_base = $user['mot_de_passe'] ?? $user['motdepasse'] ?? '';

            // Correction 3 : Vérification hybride (mot de passe haché OU texte clair)
            if (password_verify($motdepasse, $hash_base) || $motdepasse === $hash_base) {

                $_SESSION['id_utilisateur'] = $user['id_utilisateur'];
                $_SESSION['username'] = $user['nom_utilisateur'] ?? $user['username'];
                $_SESSION['role'] = $user['role'];

                header("Location: dashboard.php");
                exit();

            } else {
                $error = "Mot de passe incorrect";
            }

        } else {
            $error = "Utilisateur introuvable";
        }
    } else {
        $error = "Erreur SQL : " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Connexion</title>

<style>

* {
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body {
    height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
    font-family:Arial;

    background:
    linear-gradient(rgba(0,0,0,0.35), rgba(0,0,0,0.35)),
    url('https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?q=80&w=2070&auto=format&fit=crop')
    no-repeat center center/cover;
}

body::before {
    content:"";
    position:fixed;
    inset:0;
    backdrop-filter:blur(8px);
}

.login-box {
    position:relative;
    width:380px;
    padding:35px;
    border-radius:25px;
    background:rgba(255,255,255,0.08);
    backdrop-filter:blur(18px);
    border:1px solid rgba(255,255,255,0.12);
    box-shadow:0 8px 32px rgba(0,0,0,0.25);
    color:white;
    z-index:1;
}

.login-box h1 {
    text-align:center;
    margin-bottom:25px;
    font-size:34px;
}

input {
    width:100%;
    padding:15px;
    margin-top:15px;
    border:none;
    border-radius:15px;
    outline:none;
    background:rgba(255,255,255,0.12);
    color:white;
    font-size:15px;
}

input::placeholder {
    color:#ddd;
}

button {
    width:100%;
    padding:15px;
    margin-top:20px;
    border:none;
    border-radius:15px;
    background:linear-gradient(45deg,#4facfe,#6a5af9);
    color:white;
    font-size:16px;
    font-weight:bold;
    cursor:pointer;
    transition:0.3s;
}

button:hover {
    transform:scale(1.03);
    box-shadow:0 0 20px rgba(106,90,249,0.5);
}

.error {
    margin-top:15px;
    padding:12px;
    border-radius:12px;
    background:rgba(255,0,0,0.2);
    text-align:center;
    color:#ffb3b3;
}

.show-pass {
    margin-top:10px;
    display:flex;
    align-items:center;
    gap:8px;
    font-size:14px;
}

.show-pass input {
    width:auto;
}

</style>
</head>

<body>

<div class="login-box">

<h1>🏢 jonas's apartment</h1>

<form method="POST">

<input
type="text"
name="username"
placeholder="Nom utilisateur"
required
>

<input
type="password"
name="motdepasse"
placeholder="Mot de passe"
id="password"
required
>

<div class="show-pass">

<input
type="checkbox"
onclick="togglePassword()"
>

Afficher le mot de passe

</div>

<button type="submit" name="login">

🔐 Connexion

</button>

<?php if($error != ""){ ?>

<div class="error">

<?= $error ?>

</div>

<?php } ?>

</form>

</div>

<script>

function togglePassword(){

    var x = document.getElementById("password");

    if(x.type === "password"){

        x.type = "text";

    }else{

        x.type = "password";

    }
}

</script>

</body>
</html>
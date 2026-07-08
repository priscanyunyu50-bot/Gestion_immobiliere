<!DOCTYPE html>
<html lang="fr">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Accueil - Gestion Immobilière</title>

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{

    height:100vh;

    display:flex;

    justify-content:center;

    align-items:center;

    font-family:Arial, sans-serif;

    overflow:hidden;

    background:
    linear-gradient(rgba(0,0,0,0.45), rgba(0,0,0,0.45)),
    url("https://images.unsplash.com/photo-1600585154340-be6161a56a0c") no-repeat center center/cover;
}

/* FLOU */

body::before{

    content:"";

    position:absolute;

    inset:0;

    backdrop-filter:blur(3px);

    z-index:0;
}

/* CARD */

.welcome-box{

    position:relative;

    z-index:2;

    width:420px;

    padding:40px;

    border-radius:30px;

    text-align:center;

    background:rgba(255,255,255,0.10);

    border:1px solid rgba(255,255,255,0.2);

    backdrop-filter:blur(15px);

    box-shadow:0 8px 32px rgba(0,0,0,0.35);

    color:white;

    animation:fadeIn 1s ease;
}

.welcome-box h1{

    font-size:42px;

    margin-bottom:15px;
}

.welcome-box p{

    font-size:18px;

    margin-bottom:30px;

    color:#f1f1f1;
}

/* BUTTON */

.btn{

    display:inline-block;

    padding:15px 35px;

    border-radius:15px;

    text-decoration:none;

    color:white;

    font-size:18px;

    font-weight:bold;

    background:linear-gradient(45deg,#2196f3,#42a5f5);

    transition:0.3s;
}

.btn:hover{

    transform:scale(1.05);

    box-shadow:0 0 20px rgba(33,150,243,0.6);
}

/* ANIMATION */

@keyframes fadeIn{

    from{
        opacity:0;
        transform:translateY(30px);
    }

    to{
        opacity:1;
        transform:translateY(0);
    }
}

</style>

</head>

<body>

<div class="welcome-box">

<h1>🏢 Bienvenue</h1>

<p>
Dans votre système de gestion immobilière.
</p>

<a href="login.php" class="btn">

Accéder à l’application

</a>

</div>

</body>
</html>
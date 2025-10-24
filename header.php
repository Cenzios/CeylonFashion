<?php
// header.php

if (session_id() == '' || !isset($_SESSION)) {
    session_start();
}
?>
<!DOCTYPE html>
<html class="no-js" lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Ceylon Fashion.lk</title>
  <link rel="stylesheet" href="css/foundation.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-LN+7fdVzj6u52u30Kp6M/trliBMCMKTyK833zpbD+pXdCLuTusPj697FH4R/5mcr" crossorigin="anonymous">
  <link rel="stylesheet" href="style.css">
  <script src="js/vendor/modernizr.js"></script>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-purple">
  <div class="container-fluid">
    <img src="logo.png" alt="Logo" width="30" height="24" class="d-inline-block align-text-top">
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavAltMarkup" aria-controls="navbarNavAltMarkup" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNavAltMarkup">
      <div class="navbar-nav">
        <a class="nav-link" href="#newArrivalsSection">New Arrivals</a>
        <a class="nav-link" href="#bridalAttireSection">Bridal Attire</a>
        <a class="nav-link" href="#brideMaidsSection">Bridemaid's Attire</a>
        <a class="nav-link" href="#partyWearSection">Party Wear</a>
        <a class="nav-link" href="#usedCollectionSection">Used Collection</a>
        <div class="text-center">
          <a href="/start-reselling.php" class="btn btn-primary">Start Reselling</a>
        </div>
      </div>

      <div class="icons" style="position: absolute; right: 20px; top: 50%; transform: translateY(-50%); display: flex; gap: 15px; font-size: 32px; color: white; cursor: pointer;">
        <i class="bi bi-person-circle" id="personIcon"></i>
        <i class="bi bi-heart"></i>
        <i class="bi bi-cart2"></i>
      </div>
    </div>
  </div>
</nav>

<?php
// Auto-detect base URL so assets/links work from any subfolder depth
if (!isset($baseUrl)) {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    $projectRoot = '/CeylonFashion';
    $baseUrl = $projectRoot . '/';
}
?>
<head>
  <meta charset="utf-8" />
  <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Ceylon Fashion.lk</title>
  
  <!-- CSS -->
  <link rel="stylesheet" href="<?= $baseUrl ?>assets/css/foundation.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-LN+7fdVzj6u52u30Kp6M/trliBMCMKTyK833zpbD+pXdCLuTusPj697FH4R/5mcr" crossorigin="anonymous">
  <link rel="stylesheet" href="<?= $baseUrl ?>assets/css/style.css">
  
  <script src="<?= $baseUrl ?>assets/js/vendor/modernizr.js"></script>
</head>
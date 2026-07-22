<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Eternal preview</title>
<link rel="stylesheet" href="ope.css">
<style>
  body{margin:0;padding:20px;background:#f0eee8}
  dialog.eternal-tree-modal{display:block;position:static;margin:0;border:1px solid #ccc}
  dialog.eternal-tree-modal[open]{display:block}
</style>
</head>
<body>
<?php echo file_get_contents('eternal-identidad-coloso-preview.html'); ?>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    var dlg = document.querySelector('dialog.eternal-tree-modal');
    if (dlg) { dlg.setAttribute('open', 'open'); }
  });
</script>
</body>
</html>

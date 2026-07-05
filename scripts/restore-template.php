<?php
/**
 * Restore I-Forge custom index template to MyBB database
 * Run: php scripts/restore-template.php
 */

$db = new mysqli('127.0.0.1', 'root', '', 'mybb_foro');
if ($db->connect_error) {
    die("Error de conexión: " . $db->connect_error . "\n");
}

$template = <<<'HTML'
<html>
<head>
<title>{\$mybb->settings['bbname']}</title>
{\$headerinclude}
</head>
<body>
{\$header}

<!-- I-Forge Custom Navbar -->
<nav id="iforge-navbar">
  <div class="iforge-nav">
    <a href="{\$mybb->settings['bburl']}/index.php" class="iforge-nav-logo">I-FORGE</a>
    <div class="iforge-nav-left">
      <a href="{\$mybb->settings['bburl']}/personajes.php" class="iforge-nav-link">Personaje</a>
      <a href="{\$mybb->settings['bburl']}/tramites.php" class="iforge-nav-link">Trámites</a>
      <a href="{\$mybb->settings['bburl']}/guias.php" class="iforge-nav-link">Guías</a>
      {\$iforge_zona_privada_link}
    </div>
    <div class="iforge-nav-right">
      {\$iforge_user_menu}
    </div>
  </div>
</nav>

<!-- I-Forge Banner -->
<div id="iforge-banner" style="background-image: url('{\$banner_url}')">
  <div class="iforge-banner-seal" aria-hidden="true">I-FORGE</div>
  <div class="iforge-banner-content">
    <h1 class="iforge-banner-title">I-FORGE</h1>
    <p class="iforge-banner-subtitle">Un mundo de Cazadores</p>
  </div>
</div>

<!-- I-Forge Calendar Bar -->
<div id="iforge-calendar-bar" onclick="window.location.href='{\$mybb->settings['bburl']}/calendario.php'">
  <img src="{\$mybb->settings['bburl']}/images/icons/calendar.svg" class="icon" alt="Calendario">
  <strong>{\$calendario_texto}</strong>
</div>

<!-- I-Forge Tablón -->
<div class="iforge-tablon">
  <!-- Últimos Mensajes -->
  <div class="iforge-card">
    <div class="iforge-card-header">
      <img src="{\$mybb->settings['bburl']}/images/icons/speech.svg" class="icon" alt="">
      Últimos Mensajes
    </div>
    <div class="iforge-card-body">
      {\$iforge_latest_posts}
    </div>
    <a href="{\$mybb->settings['bburl']}/search.php?action=latest" class="iforge-card-more">
      <img src="{\$mybb->settings['bburl']}/images/icons/search.svg" class="icon" alt=""> Ver más
    </a>
  </div>

  <!-- Búsquedas Activas -->
  <div class="iforge-card">
    <div class="iforge-card-header">
      <img src="{\$mybb->settings['bburl']}/images/icons/search.svg" class="icon" alt="">
      Búsquedas Activas
    </div>
    <div class="iforge-card-body">
      {\$iforge_active_searches}
    </div>
    <a href="#" class="iforge-card-more">+ Publicar búsqueda</a>
  </div>

  <!-- Noticias -->
  <div class="iforge-card">
    <div class="iforge-card-header">
      <img src="{\$mybb->settings['bburl']}/images/icons/newspaper.svg" class="icon" alt="">
      Noticias
    </div>
    <div class="iforge-card-body">
      {\$iforge_news}
    </div>
    <a href="{\$mybb->settings['bburl']}/forumdisplay.php?fid=1" class="iforge-card-more">
      <img src="{\$mybb->settings['bburl']}/images/icons/search.svg" class="icon" alt=""> Archivo
    </a>
  </div>

  <!-- Curiosidades -->
  <div class="iforge-card">
    <div class="iforge-card-header">
      <img src="{\$mybb->settings['bburl']}/images/icons/idea.svg" class="icon" alt="">
      Curiosidades
    </div>
    <div class="iforge-card-body">
      <div id="iforge-curiosidad">{\$iforge_curiosidad}</div>
      <div style="text-align:center;padding:0 var(--space-md) var(--space-sm);">
        <a id="iforge-next-curiosidad" onclick="nextCuriosidad()">&#9734; Otra curiosidad</a>
      </div>
    </div>
  </div>

  <!-- Staff -->
  <div class="iforge-staff-section">
    <div class="iforge-card-header">
      <img src="{\$mybb->settings['bburl']}/images/icons/users.svg" class="icon" alt="">
      Staff
    </div>
    <div class="iforge-staff-list">
      {\$iforge_staff_list}
    </div>
  </div>
</div>

<script>
var curiosidades = {\$curiosidades_json};
var cIdx = 0;
function nextCuriosidad() {
  cIdx = (cIdx + 1) % curiosidades.length;
  document.getElementById('iforge-curiosidad').innerHTML = curiosidades[cIdx];
}
</script>

<!-- I-Forge Categories -->
<div class="iforge-categories">
  {\$forums}
</div>

<!-- I-Forge Footer -->
<div id="iforge-footer">
  <img src="{\$mybb->settings['bburl']}/images/icons/seal.svg" class="seal" alt="">
  {\$mybb->settings['bbname']} — Powered By <a href="https://mybb.com" target="_blank">MyBB</a>
  <img src="{\$mybb->settings['bburl']}/images/icons/seal.svg" class="seal" alt="">
</div>

{\$footer}
</body>
</html>
HTML;

// Find the templateset for theme tid=8
$r = $db->query("SELECT properties FROM mybb_themes WHERE tid=8");
$theme = $r->fetch_assoc();
$props = unserialize($theme['properties']);
$templateset = (int)$props['templateset'];

echo "Theme tid=8 uses templateset=$templateset\n";

// Check if template 'index' exists in this templateset
$r2 = $db->query("SELECT tid FROM mybb_templates WHERE title='index' AND sid=$templateset");
if ($r2->num_rows > 0) {
    // Update existing template
    $stmt = $db->prepare("UPDATE mybb_templates SET template = ? WHERE title = 'index' AND sid = ?");
    $stmt->bind_param('si', $template, $templateset);
    $stmt->execute();
    echo "Template 'index' actualizado en sid=$templateset\n";
    $stmt->close();
} else {
    // Insert new template
    $stmt = $db->prepare("INSERT INTO mybb_templates (title, template, sid, version, status, dateline) VALUES ('index', ?, ?, 1839, 1, ?)");
    $now = time();
    $stmt->bind_param('sii', $template, $templateset, $now);
    $stmt->execute();
    echo "Template 'index' insertado en sid=$templateset\n";
    $stmt->close();
}

// Also update the cache
$db->query("UPDATE mybb_datacache SET cache = '' WHERE title = 'templates'");

echo "Done. Clear MyBB cache from ACP if needed.\n";
$db->close();

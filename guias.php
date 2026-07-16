<?php
/**
 * I-Forge · Guías
 * Página de front-end MyBB (dirección "Granblue Fantasy: Eternal").
 * Visor de guías para jugadores: navegación izquierda, contenido a la derecha.
 * v3.0 — Sistema completo de Granblue Fantasy: Eternal
 */

define('IN_MYBB', 1);
define('THIS_SCRIPT', 'guias.php');
require_once './global.php';

$bburl     = htmlspecialchars_uni($mybb->settings['bburl']);
$bbname    = htmlspecialchars_uni($mybb->settings['bbname']);
$loggedin  = (int)($mybb->user['uid'] ?? 0) > 0;
$uid       = (int)($mybb->user['uid'] ?? 0);
$username  = htmlspecialchars_uni($mybb->user['username'] ?? '');

$staff_level = 0;
if ($loggedin) {
    if (isset($mybb->user['gbe_staff_level'])) {
        $staff_level = (int)$mybb->user['gbe_staff_level'];
    } elseif ($db->table_exists('rol_cuentas')) {
        $cq = $db->simple_select('rol_cuentas', 'staff_level', "uid = {$uid}", array('limit' => 1));
        if ($db->num_rows($cq)) {
            $staff_level = (int)$db->fetch_field($cq, 'staff_level');
        }
    }
}

$initials = '';
if ($loggedin) {
    $parts = preg_split('/\s+/', trim((string)$mybb->user['username']));
    foreach ($parts as $p) {
        if ($p !== '') {
            $initials .= function_exists('mb_substr') ? mb_substr($p, 0, 1, 'UTF-8') : substr($p, 0, 1);
        }
    }
    $initials = function_exists('mb_substr') ? mb_substr($initials, 0, 2, 'UTF-8') : substr($initials, 0, 2);
    $initials = function_exists('mb_strtoupper') ? mb_strtoupper($initials, 'UTF-8') : strtoupper($initials);
}
$initials_e = htmlspecialchars_uni($initials);

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> · Guías</title>
<?php echo gbe_rol_head_base(); ?>
<!-- estilos en docs/themes/gbe.css (scope: gbe-pg-guias) -->
</head>
<body class="gbe-pg-guias">

<?php echo gbe_rol_navbar_html(); ?>

<div class="breadcrumb">
  <div class="breadcrumb-in">
    <a href="<?php echo $bburl; ?>/index.php">Inicio</a>
    <span class="sep">›</span>
    <b>Guías</b>
  </div>
</div>

<div class="wrap">

  <!-- INTRO -->
  <section class="reveal gbe-deco-split">
    <?php echo gbe_rol_deco_aside('ope/deco/guias', 'Manuales y guías del sistema de rol', 'Guías del sistema'); ?>
    <div class="gbe-deco-split-main">
      <div class="shead">
        <h1>Guías</h1>
        <span class="code">// reglamento &amp; sistema</span>
        <span class="rule"></span>
      </div>
      <p class="guia-intro">Aquí encontrarás <b>todas las guías oficiales</b> del sistema de juego de Granblue Fantasy: Eternal: desde cómo crear tu personaje hasta las reglas de combate, progresión y el funcionamiento del mundo. <b>Navega por las categorías</b> de abajo para encontrar lo que buscas.</p>
    </div>
  </section>

  <!-- FILTROS + VISOR -->
  <section class="reveal">
    <div class="guia-bar">
      <span class="bar-l">Categorías</span>
      <div class="filters" role="group" aria-label="Filtrar guías por categoría">
        <button class="filt" aria-pressed="true" data-cat="all">Todas</button>
        <button class="filt" aria-pressed="false" data-cat="inicial">Iniciales</button>
        <button class="filt" aria-pressed="false" data-cat="avanzada">Avanzadas</button>
        <button class="filt" aria-pressed="false" data-cat="anexa">Anexas</button>
      </div>
    </div>

    <div class="guide-shell">

      <!-- NAVEGACIÓN IZQUIERDA -->
      <nav class="guide-nav">
        <div class="guide-nav-inner" id="guideNav">
          <div class="nav-section">Iniciales</div>
          <button class="guide-nav-item active" data-guide="creacion" data-cat="inicial">
            <span class="n">01</span> Creación de personaje
          </button>
          <button class="guide-nav-item" data-guide="acciones" data-cat="inicial">
            <span class="n">02</span> Acciones y PA
          </button>
          <button class="guide-nav-item" data-guide="cartas" data-cat="inicial">
            <span class="n">03</span> Cartas de técnica
          </button>
          <button class="guide-nav-item" data-guide="progresion" data-cat="inicial">
            <span class="n">04</span> Progresión
          </button>
          <button class="guide-nav-item" data-guide="equipo" data-cat="inicial">
            <span class="n">05</span> Equipo y objetos
          </button>

          <div class="nav-section">Avanzadas</div>
          <button class="guide-nav-item" data-guide="combate" data-cat="avanzada">
            <span class="n">06</span> Combate
          </button>
          <button class="guide-nav-item" data-guide="viajes" data-cat="avanzada">
            <span class="n">07</span> Viajes
          </button>
          <button class="guide-nav-item" data-guide="naval" data-cat="avanzada">
            <span class="n">08</span> Batallas navales
          </button>
          <button class="guide-nav-item" data-guide="estilos" data-cat="avanzada">
            <span class="n">09</span> Estilos de lucha
          </button>
          <button class="guide-nav-item" data-guide="facciones" data-cat="avanzada">
            <span class="n">10</span> Facciones
          </button>
          <button class="guide-nav-item" data-guide="grupos" data-cat="avanzada">
            <span class="n">11</span> Grupos y bases
          </button>
          <button class="guide-nav-item" data-guide="virtudes" data-cat="avanzada">
            <span class="n">12</span> Virtudes y defectos
          </button>
          <button class="guide-nav-item" data-guide="bestias" data-cat="avanzada">
            <span class="n">13</span> Bestias y NPCs
          </button>
          <button class="guide-nav-item" data-guide="cyborgs" data-cat="avanzada">
            <span class="n">14</span> Cyborgs e implantes
          </button>
          <button class="guide-nav-item" data-guide="inframundo" data-cat="avanzada">
            <span class="n">15</span> Inframundo
          </button>
          <button class="guide-nav-item" data-guide="conquistas" data-cat="avanzada">
            <span class="n">16</span> Conquistas
          </button>
          <button class="guide-nav-item" data-guide="herencia" data-cat="avanzada">
            <span class="n">17</span> Herencia y legado
          </button>
          <button class="guide-nav-item" data-guide="mundo-vivo" data-cat="avanzada">
            <span class="n">18</span> Mundo Vivo
          </button>
          <button class="guide-nav-item" data-guide="frutas" data-cat="avanzada">
            <span class="n">19</span> Frutas del Diablo
          </button>
          <button class="guide-nav-item" data-guide="recompensas" data-cat="avanzada">
            <span class="n">20</span> Recompensas
          </button>
          <button class="guide-nav-item" data-guide="eventos" data-cat="avanzada">
            <span class="n">21</span> Eventos comunitarios
          </button>
          <button class="guide-nav-item" data-guide="bienvenida" data-cat="avanzada">
            <span class="n">22</span> Temas de bienvenida
          </button>

          <div class="nav-section">Anexas</div>
          <button class="guide-nav-item" data-guide="ej-combate" data-cat="anexa">
            <span class="n">23</span> Ejemplo de combate
          </button>
          <button class="guide-nav-item" data-guide="normativa" data-cat="anexa">
            <span class="n">24</span> Normativa
          </button>
          <button class="guide-nav-item" data-guide="inicio-rapido" data-cat="anexa">
            <span class="n">25</span> Inicio rápido
          </button>
        </div>
      </nav>

      <!-- CONTENIDO DERECHA -->
      <div class="guide-main">
<!-- 01 · Creación de Personaje -->
        <div class="guide-content active" id="g-creacion">
          <div class="g-title">Creación de personaje</div>
          <div class="g-sub">// paso a paso</div>
          <p>Cada usuario puede tener múltiples personajes. Cada personaje es independiente: stats, inventario, progreso, recompensa.</p>

          <h3>Modo Express: Crea tu Personaje en 5 Minutos</h3>

          <h3>Paso 1: Raza y Nombre</h3>
          <ul>
            <li><strong>Humano</strong> — Equilibrado. Versátil en cualquier situación y de gran fuerza de voluntad.</li>
            <li><strong>Erune</strong> — Orejas de animal, ágiles y con gran afinidad para la magia y las artes del éter.</li>
            <li><strong>Draph</strong> — Cuernos prominentes, fuerza física colosal y complexión robusta, ideales para el combate.</li>
            <li><strong>Harvin</strong> — Estatura diminuta, gran intelecto y agilidad, maestros de la estrategia y la tecnología.</li>
          </ul>

          <h3>Paso 2: Stats por Arquetipo</h3>
          <table class="guide-table">
            <thead><tr><th>Arquetipo</th><th>Stats destacados</th><th>Ideal para...</th></tr></thead>
            <tbody>
              <tr><td>Guerrero</td><td>FUE C, VIG D, RES D</td><td>Combate cuerpo a cuerpo.</td></tr>
              <tr><td>Tirador</td><td>DES C, PUN D, AGI D</td><td>Ataques a distancia.</td></tr>
              <tr><td>Ágil</td><td>AGI C, REF D, DES D</td><td>Esquivas, velocidad.</td></tr>
              <tr><td>Tanque</td><td>VIG C, RES D, CON D</td><td>Resistir daño, proteger.</td></tr>
              <tr><td>Estratega</td><td>INT C, ING D, CAR D</td><td>Planes, liderazgo, frutas.</td></tr>
              <tr><td>Haki</td><td>VOL C, SEN D, PER D</td><td>Usuario de Haki desde el inicio.</td></tr>
            </tbody>
          </table>

          <h3>Paso 3: Facción</h3>
          <ul>
            <li><strong>Marine</strong> — Soldado del Gobierno. Justicia y orden.</li>
            <li><strong>Pirata</strong> — Libre en el mar. Tripulación y sueños.</li>
            <li><strong>Revolucionario</strong> — Lucha en las sombras contra el Gobierno.</li>
            <li><strong>Cazarrecompensas</strong> — Independiente. Caza piratas por dinero.</li>
            <li><strong>Civil</strong> — Comerciante, artesano, médico. Vida tranquila.</li>
            <li><strong>Independiente</strong> — Sin afiliación. Tu propio camino.</li>
          </ul>

          <h3>Pasos de Creación Detallados (7 pasos)</h3>
          <ol>
            <li><strong>Raza</strong> — Elige una de las 9 razas. Define si eres puro o híbrido.</li>
            <li><strong>Nombre y concepto</strong> — Quién es, aspecto, motivación. ¿Tienes una D.?</li>
            <li><strong>Asignar rangos de stats</strong> — Aplica pasivas raciales. Sube una stat.</li>
            <li><strong>Elegir Virtudes y Defectos</strong> — 6 PC base. Consulta la guía de Virtudes y defectos.</li>
            <li><strong>Facción inicial</strong> — Marine, Pirata, Revolucionario, Cazarrecompensas, Civil o Independiente.</li>
            <li><strong>Equipo inicial</strong> — Lo que llevas al empezar.</li>
            <li><strong>Historia</strong> — Pasado, motivaciones, relaciones.</li>
          </ol>

          <h3>¿Puro o Híbrido?</h3>
          <table class="guide-table">
            <thead><tr><th>Elección</th><th>Ventaja</th><th>Desventaja</th></tr></thead>
            <tbody>
              <tr><td><strong>Puro</strong></td><td>Obtienes AMBAS pasivas (Primaria + Secundaria)</td><td>Menos flexibilidad temática</td></tr>
              <tr><td><strong>Híbrido</strong></td><td>Combinas dos Primarias (sinergias brutales)</td><td>Pierdes AMBAS Secundarias. Más difícil de rolear</td></tr>
            </tbody>
          </table>

          <h3>Stats — Tres pilares de 4 stats</h3>
          <table class="guide-table">
            <thead><tr><th>Pilar</th><th>Stat</th><th>Sigla</th><th>Mide</th></tr></thead>
            <tbody>
              <tr><td rowspan="4"><strong>Cuerpo</strong></td><td>Fuerza</td><td>FUE</td><td>Potencia física, daño cuerpo a cuerpo</td></tr>
              <tr><td>Destreza</td><td>DES</td><td>Precisión, coordinación, reflejos finos</td></tr>
              <tr><td>Vigor</td><td>VIG</td><td>Resistencia, aguante, salud, HP</td></tr>
              <tr><td>Agilidad</td><td>AGI</td><td>Velocidad, movimiento, iniciativa</td></tr>
              <tr><td rowspan="4"><strong>Mente</strong></td><td>Intelecto</td><td>INT</td><td>Conocimiento, memoria, análisis, Poneglyphs</td></tr>
              <tr><td>Ingenio</td><td>ING</td><td>Creatividad, improvisación, oficios</td></tr>
              <tr><td>Concentración</td><td>CON</td><td>Enfoque, disciplina mental</td></tr>
              <tr><td>Percepción</td><td>PER</td><td>Sentidos, intuición, detectar emboscadas</td></tr>
              <tr><td rowspan="4"><strong>Espíritu</strong></td><td>Carisma</td><td>CAR</td><td>Labia, presencia, liderazgo, intimidación</td></tr>
              <tr><td>Control</td><td>CTR</td><td>Dominio propio, temple, control de Fruta</td></tr>
              <tr><td>Voluntad</td><td>VOL</td><td>Determinación, Haki, resistencia mental</td></tr>
              <tr><td>Sensibilidad</td><td>SEN</td><td>Empatía, conexión espiritual, Voz de Todas las Cosas</td></tr>
            </tbody>
          </table>

          <h3>Escala de rangos</h3>
          <table class="guide-table">
            <thead><tr><th>Rango</th><th>Valor</th><th>Significado</th></tr></thead>
            <tbody>
              <tr><td>F</td><td>1</td><td>Por debajo de la media humana</td></tr>
              <tr><td>E</td><td>2</td><td>Apenas funcional</td></tr>
              <tr><td>D</td><td>3</td><td>Civil corriente</td></tr>
              <tr><td>C</td><td>4</td><td>Marine raso / pirata novato</td></tr>
              <tr><td>B</td><td>5</td><td>Oficial Marine / pirata competente</td></tr>
              <tr><td>A</td><td>6</td><td>Supernova / oficial de élite</td></tr>
              <tr><td>S</td><td>7</td><td>Vicealmirante / comandante Yonko</td></tr>
              <tr><td>SS</td><td>8</td><td>Almirante / Yonko</td></tr>
              <tr><td>M</td><td>9</td><td>Almirante de Flota / Rey Pirata</td></tr>
              <tr><td class="hl">M+</td><td class="hl">10</td><td class="hl">Trascendente / figuras míticas</td></tr>
            </tbody>
          </table>

          <h3>Razas y pasivas</h3>
          <p>Cada raza tiene una <strong>Pasiva Primaria</strong> (siempre activa) y una <strong>Pasiva Secundaria</strong> (condicional). Si eres <strong>puro</strong>, obtienes ambas. Si eres <strong>híbrido</strong>, obtienes solo las primarias de cada raza.</p>
          <div class="pasiva-grid">
            <div class="pasiva-card">
              <div class="pname">Humano</div>
              <div class="ptype">Primaria · Adaptabilidad</div>
              <div class="peff">Puedes subir <strong>dos stats</strong> de F a E al crear el personaje en lugar de una.</div>
              <div class="ptype mt-8">Secundaria · Mayoría Silenciosa</div>
              <div class="peff">+10% a la ganancia de Reputación con cualquier facción.</div>
            </div>
            <div class="pasiva-card">
              <div class="pname">Skypiean</div>
              <div class="ptype">Primaria · Planeo Celestial</div>
              <div class="peff">Planeas distancias cortas. Niega el daño por caída. +1 AGI para acciones de movimiento.</div>
              <div class="ptype mt-8">Secundaria · Herencia del Dial</div>
              <div class="peff">Empiezas con un Dial básico a elección. Puedes identificar cualquier Dial al verlo.</div>
            </div>
            <div class="pasiva-card">
              <div class="pname">Gyojin</div>
              <div class="ptype">Primaria · Sangre del Abismo</div>
              <div class="peff">Respiras bajo el agua. ×2 velocidad en agua. +1 VIG permanente.</div>
              <div class="ptype mt-8">Secundaria · Gyojin Karate Innato</div>
              <div class="peff">Carta Tier I "Gyojin Karate: Puño de Agua" gratis. +1 FUE en contacto con agua.</div>
            </div>
            <div class="pasiva-card">
              <div class="pname">Gigante</div>
              <div class="ptype">Primaria · Fuerza Colosal</div>
              <div class="peff">+2 FUE permanente. ×1.5 daño cuerpo a cuerpo. ×2 alcance. -1 AGI permanente.</div>
              <div class="ptype mt-8">Secundaria · Piel de Batalla</div>
              <div class="peff">+1 VIG permanente. Las heridas leves no te afectan en combate.</div>
            </div>
            <div class="pasiva-card">
              <div class="pname">Mink</div>
              <div class="ptype">Primaria · Electro</div>
              <div class="peff">Descargas eléctricas al contacto. Daño escala con AGI. +25% daño contra objetivos metálicos.</div>
              <div class="ptype mt-8">Secundaria · Instinto Salvaje</div>
              <div class="peff">+1 PER para rastrear, detectar emboscadas y percibir intenciones hostiles.</div>
            </div>
            <div class="pasiva-card">
              <div class="pname">Lunarian</div>
              <div class="ptype">Primaria · Llamarada</div>
              <div class="peff">Envuelves partes de tu cuerpo en fuego a voluntad (1 PA). Daño por contacto. +1 VIG mientras está activa.</div>
              <div class="ptype mt-8">Secundaria · Los Últimos</div>
              <div class="peff">Wanted automático de 100M. +1 VOL contra Marines. -50% REP en territorio del Gobierno.</div>
            </div>
            <div class="pasiva-card">
              <div class="pname">Sirena / Sireno</div>
              <div class="ptype">Primaria · Gracia Marina</div>
              <div class="peff">×3 velocidad en agua. Te comunicas telepáticamente con peces y criaturas marinas no hostiles. +1 SEN.</div>
              <div class="ptype mt-8">Secundaria · Canto Hipnótico</div>
              <div class="peff">+1 CAR para persuasión o distracción mediante tu voz. Calmas criaturas marinas hostiles.</div>
            </div>
            <div class="pasiva-card">
              <div class="pname">Bucaneer</div>
              <div class="ptype">Primaria · Sangre de Gigante</div>
              <div class="peff">+1 FUE y +1 VIG permanentes. Empuñas armas de categoría superior sin penalización.</div>
              <div class="ptype mt-8">Secundaria · Estirpe Marcada</div>
              <div class="peff">Wanted de 50M. +1 VOL contra opresión. Reconocido por otros Bucaneers.</div>
            </div>
            <div class="pasiva-card">
              <div class="pname">Tontatta</div>
              <div class="ptype">Primaria · Diminuto y Letal</div>
              <div class="peff">-1 FUE permanente. +2 AGI y +2 DES permanentes. Los enemigos tienen -10 a PER para detectarte.</div>
              <div class="ptype mt-8">Secundaria · Manos de Artesano</div>
              <div class="peff">Un Oficio gratis con su primera especialización. Creas objetos en la mitad de tiempo.</div>
            </div>
          </div>

          <h3>Híbridos</h3>
          <p>Puedes combinar <strong>dos razas cualesquiera</strong> (si tiene sentido biológico). Obtienes solo las <strong>pasivas primarias</strong> de ambas. Pierdes las secundarias.</p>

          <h3>Facción Inicial</h3>
          <table class="guide-table">
            <thead><tr><th>Facción</th><th>Descripción</th><th>Ventaja Inicial</th></tr></thead>
            <tbody>
              <tr><td><strong>Ejército Imperial</strong></td><td>Soldados y caballeros del Imperio de Erste.</td><td>+1 rango de facción. Acceso a recursos Imperiales.</td></tr>
              <tr><td><strong>Skyfarer</strong></td><td>Navegantes libres del cielo. Tripulación, sueños.</td><td>Aeronave pequeña inicial. +10% ganancia de Renombre.</td></tr>
              <tr><td><strong>La Sociedad / Rebeldes</strong></td><td>Luchadores encubiertos contra el Imperio.</td><td>Identidad secreta gratuita. Red de contactos.</td></tr>
              <tr><td><strong>Gremio de Cazadores</strong></td><td>Cazan bestias y forajidos por dinero.</td><td>+1 nivel en el Gremio. +10% rupias por capturas.</td></tr>
              <tr><td><strong>Civil</strong></td><td>Comerciante, médico, erudito...</td><td>+1 Oficio gratuito. Sin Renombre ni enemigos.</td></tr>
              <tr><td><strong>Independiente</strong></td><td>Sin afiliación.</td><td>Máxima libertad narrativa.</td></tr>
            </tbody>
          </table>

          <h3>Equipo Inicial</h3>
          <ul>
            <li>1 arma básica a elección. Rango F de daño.</li>
            <li>1 objeto personal definido por el jugador.</li>
            <li>Ropa y pertenencias básicas.</li>
            <li><strong>50,000 rupias</strong> iniciales.</li>
            <li>Sin Pacto Primal al inicio (debe encontrarse en juego).</li>
            <li>Sin Clase Avanzada al inicio (debe comprarse con PP a partir de rango B).</li>
          </ul>

          <div class="guide-note"><strong>¿Listo?</strong> Con esto puedes empezar a rolear. Cuando quieras optimizar tu build, consulta la guía de <strong>Virtudes y defectos</strong> y <strong>Progresión</strong>.</div>
        </div>

        <!-- 02 · Acciones y PA -->
        <div class="guide-content" id="g-acciones">
          <div class="g-title">Acciones y PA</div>
          <div class="g-sub">// cómo funciona tu turno</div>
          <p>Sistema de Puntos de Acción para el cielo de los Skydoms.</p>

          <h3>Puntos de Acción (PA)</h3>
          <p><strong>PA por post = AGI + la mejor entre INT, ING y CAR</strong></p>
          <p>Los PA se recuperan al inicio de cada post propio. No se acumulan entre posts.</p>

          <table class="guide-table">
            <thead><tr><th>Componente</th><th>Qué representa</th></tr></thead>
            <tbody>
              <tr><td><strong>AGI</strong></td><td>Tu velocidad física. +1 PA por cada rango de AGI.</td></tr>
              <tr><td><strong>mejorDe(INT, ING, CAR)</strong></td><td>Tu velocidad mental o presencia. +1 PA por cada rango.</td></tr>
            </tbody>
          </table>

          <h3>Ejemplos de PA</h3>
          <table class="guide-table">
            <thead><tr><th>Tipo de Personaje</th><th>AGI</th><th>mejorDe()</th><th>PA Totales</th></tr></thead>
            <tbody>
              <tr><td>Tanque pesado (Gigante)</td><td>2 (E)</td><td>3 (CAR D)</td><td><strong>5 PA</strong></td></tr>
              <tr><td>Velocista (Tontatta)</td><td>4 (C)</td><td>3 (ING D)</td><td><strong>7 PA</strong></td></tr>
              <tr><td>Líder pirata (Humano)</td><td>3 (D)</td><td>5 (CAR B)</td><td><strong>8 PA</strong></td></tr>
              <tr><td>Personaje inicial</td><td>2 (E)</td><td>1 (F)</td><td><strong>3 PA</strong></td></tr>
              <tr><td>Rango A equilibrado</td><td>5 (B)</td><td>5 (B)</td><td><strong>10 PA</strong></td></tr>
              <tr><td>Leyenda (todo SS)</td><td>8 (SS)</td><td>8 (SS)</td><td><strong>16 PA</strong></td></tr>
            </tbody>
          </table>

          <h3>Acciones Universales</h3>
          <table class="guide-table">
            <thead><tr><th>Acción</th><th>Coste PA</th><th>Notas</th></tr></thead>
            <tbody>
              <tr><td>Activar una carta de técnica</td><td>Según la carta (1-5 PA)</td><td>—</td></tr>
              <tr><td>Moverse (caminar/correr)</td><td>1 PA</td><td>Distancia = AGI × 2 metros</td></tr>
              <tr><td>Esprintar</td><td>2 PA</td><td>Distancia = AGI × 5 metros</td></tr>
              <tr><td>Usar un objeto</td><td>1 PA</td><td>Beber poción, activar Dial, cambiar de arma</td></tr>
              <tr><td>Esquivar (sin carta)</td><td>1 PA</td><td>Tirada de AGI</td></tr>
              <tr><td>Bloquear (sin carta)</td><td>1 PA</td><td>Tirada de FUE o DES. Reduce daño.</td></tr>
              <tr><td>Apuntar</td><td>1 PA</td><td>+2 a la precisión del siguiente ataque a distancia</td></tr>
              <tr><td>Levantarse (si estás derribado)</td><td>1 PA</td><td>—</td></tr>
              <tr><td>Agarrar/Inmovilizar</td><td>2 PA</td><td>Tirada enfrentada de FUE</td></tr>
              <tr><td>Hablar / Gritar / Rol social</td><td>0 PA</td><td>Gratis. El rol es libre.</td></tr>
            </tbody>
          </table>

          <h3>Acciones Específicas de Granblue Fantasy: Eternal</h3>
          <table class="guide-table">
            <thead><tr><th>Acción</th><th>Coste PA</th><th>Notas</th></tr></thead>
            <tbody>
              <tr><td><strong>Activar Haki (Busoshoku)</strong></td><td>1 PA</td><td>Dura todo el combate.</td></tr>
              <tr><td><strong>Transformación Zoan (completa/híbrida)</strong></td><td>2 PA</td><td>Se mantiene hasta revertir (gratis).</td></tr>
              <tr><td><strong>Convertirse en elemento (Logia)</strong></td><td>1 PA</td><td>Intangibilidad mantenida.</td></tr>
              <tr><td><strong>Disparar arma de fuego</strong></td><td>1 PA por disparo</td><td>—</td></tr>
              <tr><td><strong>Usar Den Den Mushi</strong></td><td>0-1 PA</td><td>0 fuera de combate / 1 en combate.</td></tr>
              <tr><td><strong>Sumergirse (entrar al agua)</strong></td><td>1 PA</td><td>Si tienes Fruta, aplican reglas de Inmersión.</td></tr>
              <tr><td><strong>Cargar / Recargar EN</strong></td><td>2 PA</td><td>Recuperas 10 EN o el 25% de tu EN máx.</td></tr>
            </tbody>
          </table>

          <h3>Movimiento en el Mundo (Fuera de Combate)</h3>
          <table class="guide-table">
            <thead><tr><th>Modo de Viaje</th><th>Velocidad Aproximada</th></tr></thead>
            <tbody>
              <tr><td>A pie (en una isla)</td><td>Cruza la isla en horas.</td></tr>
              <tr><td>Bote / Barco pequeño</td><td>1-2 islas por ciclo (15 días).</td></tr>
              <tr><td>Barco pirata estándar</td><td>2-4 islas por ciclo.</td></tr>
              <tr><td>Barco rápido (Marine, Yonko)</td><td>5-8 islas por ciclo.</td></tr>
            </tbody>
          </table>

          <div class="guide-note"><strong>Regla de oro para PA bajos:</strong> No intentes hacer de todo. Especialízate. <strong>Para PA altos:</strong> Guardar PA para reaccionar al turno enemigo vale más que un tercer ataque débil.</div>
        </div>

        <!-- 03 · Cartas de técnica -->
        <div class="guide-content" id="g-cartas">
          <div class="g-title">Cartas de técnica</div>
          <div class="g-sub">// tus movimientos especiales</div>
          <p>Las Cartas de Técnica representan los movimientos y habilidades especiales del personaje. Cada carta se define mediante <strong>6 categorías de tags</strong>.</p>

          <h3>Las 6 Categorías de Tags</h3>
          <table class="guide-table">
            <thead><tr><th>#</th><th>Categoría</th><th>Pregunta que responde</th><th>Ejemplos</th></tr></thead>
            <tbody>
              <tr><td>1</td><td><strong>Estilo</strong></td><td>¿De dónde viene?</td><td>Marcial, Haki, Akuma, Rokushiki, Gyojin Karate, Electro, Ittoryu...</td></tr>
              <tr><td>2</td><td><strong>Tipo</strong></td><td>¿Qué hace?</td><td>Ofensiva, Defensiva, Soporte, Control, Movilidad, Utilidad</td></tr>
              <tr><td>3</td><td><strong>Alcance</strong></td><td>¿Hasta dónde llega?</td><td>Cuerpo a cuerpo, Corto, Medio, Largo, Área, Línea, Personal</td></tr>
              <tr><td>4</td><td><strong>Elemento</strong></td><td>¿Tiene elemento?</td><td>Fuego, Hielo, Electricidad, Agua, Luz, Oscuridad, Ninguno...</td></tr>
              <tr><td>5</td><td><strong>Estado</strong></td><td>¿Qué aplica?</td><td>Quemado, Paralizado, Aturdido, Derribado, Fortalecido, Ninguno...</td></tr>
              <tr><td>6</td><td><strong>Clase</strong></td><td>¿Qué nivel?</td><td>Básica (Tier I) a Definitiva (Tier V)</td></tr>
            </tbody>
          </table>

          <h3>Tiers Detallados</h3>
          <table class="guide-table">
            <thead><tr><th>Tier</th><th>Clase</th><th>PA típico</th><th>EN típico</th><th>Coste PP</th><th>Dados típicos</th></tr></thead>
            <tbody>
              <tr><td><strong>I</strong></td><td>Básica</td><td>1-2</td><td>5-10</td><td>5 PP</td><td>1d6-1d8 + stat</td></tr>
              <tr><td><strong>II</strong></td><td>Intermedia</td><td>2</td><td>10-15</td><td>8 PP</td><td>1d10-2d6 + stat</td></tr>
              <tr><td><strong>III</strong></td><td>Avanzada</td><td>2-3</td><td>15-25</td><td>12 PP</td><td>2d8 + stat</td></tr>
              <tr><td><strong>IV</strong></td><td>Suprema</td><td>3-4</td><td>25-40</td><td>18 PP</td><td>3d8-4d6 + stat</td></tr>
              <tr><td><strong>V</strong></td><td>Definitiva</td><td>4-5</td><td>40-60</td><td>25 PP</td><td>4d8-5d6 + stat</td></tr>
            </tbody>
          </table>

          <h3>Estados Negativos (Debuffs)</h3>
          <table class="guide-table">
            <thead><tr><th>Tag</th><th>Efecto</th><th>Duración</th></tr></thead>
            <tbody>
              <tr><td>[Aturdido]</td><td>-1 PA en su próximo turno.</td><td>1 ronda</td></tr>
              <tr><td>[Quemado]</td><td>Sufre 3 de daño al inicio de cada turno.</td><td>1d3 rondas</td></tr>
              <tr><td>[Paralizado]</td><td>0 PA para movimiento.</td><td>1d2 rondas</td></tr>
              <tr><td>[Envenenado]</td><td>Sufre 5 de daño al inicio de cada turno.</td><td>Indefinido</td></tr>
              <tr><td>[Sangrado]</td><td>Sufre 2 de daño al inicio de cada turno.</td><td>1d4 rondas</td></tr>
              <tr><td>[Confuso]</td><td>Tus cartas cuestan +1 PA este turno.</td><td>1d2 rondas</td></tr>
              <tr><td>[Derribado]</td><td>Necesitas 1 PA para levantarte.</td><td>Hasta levantarse</td></tr>
              <tr><td>[Inmovilizado]</td><td>No puedes moverte del sitio.</td><td>1d3 rondas</td></tr>
            </tbody>
          </table>

          <h3>Estados Positivos (Buffs)</h3>
          <table class="guide-table">
            <thead><tr><th>Tag</th><th>Efecto</th><th>Duración</th></tr></thead>
            <tbody>
              <tr><td>[Fortalecido]</td><td>+2 al daño de tus ataques.</td><td>1d3 rondas</td></tr>
              <tr><td>[Protegido]</td><td>-5 al daño que recibes.</td><td>1d3 rondas</td></tr>
              <tr><td>[Acelerado]</td><td>+1 PA durante tu próximo turno.</td><td>1 ronda</td></tr>
              <tr><td>[Curado]</td><td>Recuperas PV o eliminas un estado alterado.</td><td>Instantáneo</td></tr>
            </tbody>
          </table>

          <h3>Construcción de un Deck</h3>
          <p>Un buen deck equilibra: <strong>ataque principal</strong> (2-3 cartas ofensivas de tu Tier máximo), <strong>defensa/supervivencia</strong> (1-2 cartas), <strong>control/utilidad</strong> (1-2 cartas) y <strong>movilidad</strong> (1 carta).</p>

          <div class="guide-note"><strong>Empiezas con 2 cartas Tier I gratuitas.</strong> El resto se compran con PP. Las cartas evolucionan con el uso, momentos épicos y desbloqueos (Haki, Awakening).</div>
        </div>

        <!-- 04 · Progresión -->
        <div class="guide-content" id="g-progresion">
          <div class="g-title">Progresión</div>
          <div class="g-sub">// cómo mejorar tu personaje</div>
          <p>Sistema de progresión: PP, rangos, Haki, Frutas del Diablo, Wanted y PL.</p>

          <h3>Puntos de Progreso (PP)</h3>
          <table class="guide-table">
            <thead><tr><th>Actividad</th><th>PP</th></tr></thead>
            <tbody>
              <tr><td>Post de 0-300 palabras</td><td>1</td></tr>
              <tr><td>Post de 300-700 palabras</td><td>2</td></tr>
              <tr><td>Post de 700-1200 palabras</td><td>3</td></tr>
              <tr><td>Post de 1200+ palabras</td><td>4</td></tr>
              <tr><td>Misión completada</td><td>+3 a +10</td></tr>
              <tr><td>Arco completado</td><td>+15 a +30</td></tr>
            </tbody>
          </table>

          <h3>Subir Stats</h3>
          <table class="guide-table">
            <thead><tr><th>Subida</th><th>Coste PP</th><th>Acumulado desde F</th></tr></thead>
            <tbody>
              <tr><td>F → E</td><td>1</td><td>1</td></tr>
              <tr><td>E → D</td><td>2</td><td>3</td></tr>
              <tr><td>D → C</td><td>4</td><td>7</td></tr>
              <tr><td>C → B</td><td>7</td><td>14</td></tr>
              <tr><td>B → A</td><td>11</td><td>25</td></tr>
              <tr><td>A → S</td><td>16</td><td>41</td></tr>
              <tr><td>S → SS</td><td>22</td><td>63</td></tr>
              <tr><td>SS → M</td><td>29</td><td>92</td></tr>
              <tr><td>M → M+</td><td>37</td><td>129</td></tr>
            </tbody>
          </table>

          <h3>Rango del Personaje</h3>
          <table class="guide-table">
            <thead><tr><th>Rango</th><th>Suma de stats</th><th>Equivalente en el mundo</th></tr></thead>
            <tbody>
              <tr><td>F</td><td>0-13</td><td>Civil, grumete, recluta</td></tr>
              <tr><td>E</td><td>14-16</td><td>Marine raso, pirata novato</td></tr>
              <tr><td>D</td><td>17-20</td><td>Pirata competente, Oficial Marine</td></tr>
              <tr><td>C</td><td>21-25</td><td>Supernova menor, Teniente</td></tr>
              <tr><td>B</td><td>26-31</td><td>Supernova, Capitán Marine</td></tr>
              <tr><td>A</td><td>32-38</td><td>Supernova mayor, Comodoro</td></tr>
              <tr><td>S</td><td>39-46</td><td>Vicealmirante, Comandante Yonko</td></tr>
              <tr><td>SS</td><td>47-55</td><td>Almirante, Yonko</td></tr>
              <tr><td>M</td><td>56-65</td><td>Almirante de Flota, Rey Pirata</td></tr>
              <tr><td class="hl">M+</td><td class="hl">66+</td><td class="hl">Leyenda viva</td></tr>
            </tbody>
          </table>

          <h3>Busoshoku Haki (Armadura) — 20 PP inicial</h3>
          <table class="guide-table">
            <thead><tr><th>Nivel</th><th>Coste PP</th><th>Efecto</th></tr></thead>
            <tbody>
              <tr><td>Básico</td><td>20 PP</td><td>Endurecer extremidades. Golpear usuarios de Logia.</td></tr>
              <tr><td>Intermedio</td><td>+30 PP</td><td>Endurecimiento total. Imbuir armas.</td></tr>
              <tr><td>Avanzado</td><td>+50 PP</td><td>Emisión a distancia.</td></tr>
              <tr><td>Supremo</td><td>+80 PP</td><td>Destrucción interna.</td></tr>
            </tbody>
          </table>

          <h3>Kenbunshoku Haki (Observación) — 20 PP inicial</h3>
          <table class="guide-table">
            <thead><tr><th>Nivel</th><th>Coste PP</th><th>Efecto</th></tr></thead>
            <tbody>
              <tr><td>Básico</td><td>20 PP</td><td>Detectar presencia de seres vivos.</td></tr>
              <tr><td>Intermedio</td><td>+30 PP</td><td>Leer intenciones, predecir movimientos.</td></tr>
              <tr><td>Avanzado</td><td>+50 PP</td><td>Detectar a kilómetros.</td></tr>
              <tr><td>Supremo</td><td>+80 PP</td><td>Vislumbrar el futuro inmediato.</td></tr>
            </tbody>
          </table>

          <h3>Haoshoku Haki (Rey)</h3>
          <p>NO se compra con PP. Al alcanzar <strong>rango B</strong>, tirada gratuita con <strong>15% de probabilidad</strong>. Tiradas adicionales cuestan 1 PL cada una. Máximo 5 tiradas totales.</p>

          <h3>PL — Puntos de Leyenda</h3>
          <p>La <strong>moneda más rara del juego</strong>. Se obtienen completando arcos narrativos, participando en Eventos del Foro o logrando momentos épicos. Se usan para Haoshoku (1 PL), Despertar Fruta (2 PL), adquirir Fruta específica (3 PL), forjar Saijo O Wazamono (3 PL).</p>

          <h3>Wanted (Recompensa)</h3>
          <table class="guide-table">
            <thead><tr><th>Nivel</th><th>Efecto en el Mundo Vivo</th></tr></thead>
            <tbody>
              <tr><td>0</td><td>Los Marines te ignoran.</td></tr>
              <tr><td>1M – 50M</td><td>Cazarrecompensas locales te buscan.</td></tr>
              <tr><td>50M – 150M</td><td>Cazadores regionales. Periódicos.</td></tr>
              <tr><td>150M – 500M</td><td>Supernova. Vicealmirantes te buscan.</td></tr>
              <tr><td>500M – 1,000M</td><td>Cazadores de élite. Prioridad.</td></tr>
              <tr><td>1,000M – 3,000M</td><td>Almirantes. Noticia global.</td></tr>
              <tr><td>3,000M+</td><td>Yonko. Prioridad mundial.</td></tr>
            </tbody>
          </table>
        </div>

        <!-- 05 · Equipo y objetos -->
        <div class="guide-content" id="g-equipo">
          <div class="g-title">Equipo y objetos</div>
          <div class="g-sub">// lo que llevas encima</div>
          <p>Equipamiento, objetos especiales y recursos del cielo de los Skydoms.</p>

          <h3>Equipo Inicial</h3>
          <p>1 arma básica (Rango F), 1 objeto personal, ropa básica y <strong>50,000 rupias</strong>. Sin Pacto Primal ni Clase Avanzada al inicio.</p>

          <h3>Armas Cuerpo a Cuerpo</h3>
          <table class="guide-table">
            <thead><tr><th>Arma</th><th>Daño Base</th><th>Stat</th><th>Coste</th></tr></thead>
            <tbody>
              <tr><td>Puños / Patadas</td><td>1d4 + FUE</td><td>FUE</td><td>0</td></tr>
              <tr><td>Palo / Bastón</td><td>1d6 + FUE</td><td>FUE</td><td>5,000</td></tr>
              <tr><td>Espada corta</td><td>1d6 + DES</td><td>DES</td><td>10,000</td></tr>
              <tr><td>Maza / Martillo</td><td>1d8 + FUE</td><td>FUE</td><td>15,000</td></tr>
              <tr><td>Tridente / Lanza</td><td>1d8 + DES o FUE</td><td>DES o FUE</td><td>25,000</td></tr>
              <tr><td>Espada larga / Katana</td><td>1d8 + DES</td><td>DES</td><td>30,000</td></tr>
              <tr><td>Mandoble (2 manos)</td><td>2d6 + DES</td><td>DES</td><td>80,000</td></tr>
            </tbody>
          </table>

          <h3>Armas a Distancia</h3>
          <table class="guide-table">
            <thead><tr><th>Arma</th><th>Daño</th><th>Alcance</th><th>Coste</th></tr></thead>
            <tbody>
              <tr><td>Honda</td><td>1d4 + DES</td><td>Corto</td><td>2,000</td></tr>
              <tr><td>Arco corto</td><td>1d6 + DES</td><td>Medio</td><td>15,000</td></tr>
              <tr><td>Pistola de chispa</td><td>1d6 + DES</td><td>Corto</td><td>20,000</td></tr>
              <tr><td>Rifle de caza</td><td>1d10 + DES</td><td>Largo</td><td>40,000</td></tr>
              <tr><td>Pistola de repetición</td><td>1d8 + DES</td><td>Corto</td><td>50,000</td></tr>
              <tr><td>Rifle de francotirador</td><td>2d6 + DES</td><td>Largo</td><td>80,000</td></tr>
            </tbody>
          </table>

          <h3>Calidad del Arma</h3>
          <table class="guide-table">
            <thead><tr><th>Calidad</th><th>Modificador</th><th>Coste</th></tr></thead>
            <tbody>
              <tr><td>Normal</td><td>+0</td><td>10K-100K</td></tr>
              <tr><td>Buena</td><td>+1 daño</td><td>100K-1M</td></tr>
              <tr><td>Excelente</td><td>+2 daño</td><td>1M-10M</td></tr>
              <tr><td>Maestra (Meito)</td><td>+3 daño, +1 DES</td><td>10M+</td></tr>
              <tr><td>Saijo O Wazamono</td><td>+4 daño, +2 DES, ignora 50% armadura</td><td>Invaluable (solo 12)</td></tr>
            </tbody>
          </table>

          <h3>Dials</h3>
          <table class="guide-table">
            <thead><tr><th>Tipo</th><th>Efecto</th><th>Coste</th></tr></thead>
            <tbody>
              <tr><td>Flame Dial</td><td>Ráfaga de fuego. 1d8.</td><td>50,000</td></tr>
              <tr><td>Impact Dial</td><td>Absorbe y devuelve impacto.</td><td>100,000</td></tr>
              <tr><td>Breath Dial</td><td>Corriente de aire. Empuja.</td><td>20,000</td></tr>
              <tr><td>Flash Dial</td><td>Destello cegador en área.</td><td>80,000</td></tr>
              <tr><td>Water Dial</td><td>Almacena 50 litros de agua.</td><td>200,000</td></tr>
              <tr><td>Thunder Dial</td><td>2d8 eléctrico. [Paralizado].</td><td>300,000</td></tr>
              <tr><td>Reject Dial</td><td>×10 potencia. 4d10.</td><td>500,000</td></tr>
            </tbody>
          </table>

          <h3>Frutas del Diablo</h3>
          <p>No empiezas con una. <strong>Se encuentran jugando.</strong> Métodos: evento aleatorio Mundo Vivo (3% por región por ciclo), mercado negro (100M-5,000M), derrotar a un usuario, misiones de exploración, o compra con PL (3 PL). Tres tipos: Paramecia, Zoan y Logia.</p>

          <h3>Den Den Mushi</h3>
          <table class="guide-table">
            <thead><tr><th>Tipo</th><th>Función</th><th>Coste</th></tr></thead>
            <tbody>
              <tr><td>Normal</td><td>Llamadas de voz. Alcance mundial.</td><td>10,000</td></tr>
              <tr><td>Negro</td><td>Comunicación encriptada.</td><td>50,000</td></tr>
              <tr><td>Vigilancia</td><td>Espionaje.</td><td>100,000</td></tr>
              <tr><td>Visual</td><td>Transmite imagen en directo.</td><td>200,000</td></tr>
            </tbody>
          </table>

          <div class="guide-note"><strong>Tener objetos raros atrae atención.</strong> Un artefacto de los Astrales, un Arma Suprema o un Pacto Primal te convierten en objetivo de facciones, cazadores y el Imperio de Erste.</div>
        </div>

        <!-- 06 · Combate -->
        <div class="guide-content" id="g-combate">
          <div class="g-title">Combate</div>
          <div class="g-sub">// cómo luchar en el rol</div>
          <p>Sistema de combate completo con todas las interacciones especiales del cielo.</p>

          <h3>Modo Combate Lite (Duelos Rápidos)</h3>
          <p>Para peleas de taberna, entrenamientos y duelos amistosos sin el sistema táctico completo. Tirada única: 1D20 + mejor stat ofensiva. 3 asaltos máximo. Sin muerte. 50% del PP.</p>

          <h3>Estructura de una Ronda</h3>
          <ol>
            <li>El DJ define el orden de posteo según la iniciativa.</li>
            <li>Cada jugador postea su turno gastando PA.</li>
            <li>Cuando todos postean, se cierra la ronda.</li>
            <li>Si no posteas en 48h, pierdes el turno.</li>
          </ol>

          <h3>Puntos de Vida (PV)</h3>
          <p><strong>PV = (FUE + VIG) × 5 + (VOL + CON) × 2</strong></p>

          <h3>Heridas</h3>
          <table class="guide-table">
            <thead><tr><th>Daño en un golpe</th><th>Tipo</th><th>Efecto</th></tr></thead>
            <tbody>
              <tr><td>Menos del 10% PV</td><td>—</td><td>Sin herida, solo daño a PV</td></tr>
              <tr><td>10% – 19% PV</td><td>Leve</td><td>-1 a acciones con esa parte</td></tr>
              <tr><td>20% – 34% PV</td><td>Grave</td><td>-2 a acciones. No puedes usar cartas con esa parte</td></tr>
              <tr><td>35% o más PV</td><td>Crítica</td><td>Esa parte no funciona. -3 a todo</td></tr>
            </tbody>
          </table>

          <h3>Usuarios de Fruta vs Agua</h3>
          <table class="guide-table">
            <thead><tr><th>Nivel</th><th>Condición</th><th>Efecto</th></tr></thead>
            <tbody>
              <tr><td>Nivel 0</td><td>Salpicadura, lluvia</td><td>Sin efecto.</td></tr>
              <tr><td>Nivel 1</td><td>Hasta rodillas</td><td>-25% stats.</td></tr>
              <tr><td>Nivel 2</td><td>Hasta cintura</td><td>-50% stats.</td></tr>
              <tr><td>Nivel 3</td><td>Hasta pecho</td><td>-75% stats.</td></tr>
              <tr><td>Nivel 4</td><td>Sumergido</td><td>Stats = 0. Ahogo en 3 turnos.</td></tr>
            </tbody>
          </table>

          <h3>Logia — Intangibilidad</h3>
          <table class="guide-table">
            <thead><tr><th>Situación</th><th>Resultado</th></tr></thead>
            <tbody>
              <tr><td>Sin Busoshoku Haki</td><td>0% de daño. El ataque atraviesa.</td></tr>
              <tr><td>Con Busoshoku Haki</td><td>100% de daño.</td></tr>
              <tr><td>Con Kairoseki</td><td>100% de daño. No puede transformarse.</td></tr>
              <tr><td>Ventaja elemental</td><td>100% incluso sin Haki.</td></tr>
            </tbody>
          </table>

          <h3>Kairoseki (Piedra Marina)</h3>
          <table class="guide-table">
            <thead><tr><th>Contacto</th><th>Efecto</th></tr></thead>
            <tbody>
              <tr><td><strong>Impacto directo</strong></td><td>100% daño. No anula poderes.</td></tr>
              <tr><td><strong>Contacto prolongado</strong></td><td>Poderes anulados. Stats al 50%.</td></tr>
              <tr><td><strong>Ambiente</strong></td><td>Drenaje: -5 EN por ronda.</td></tr>
            </tbody>
          </table>

          <h3>Haki en Combate</h3>
          <p><strong>Busoshoku:</strong> +2 daño (Básico). Permite golpear Logias. 1 PA. Dura todo el combate.</p>
          <p><strong>Kenbunshoku:</strong> +2 a +5 REF para esquivar. Pasivo. Cuesta EN.</p>
          <p><strong>Haoshoku:</strong> Derriba voluntades débiles en área. 2 PA + 15 EN.</p>

          <div class="guide-note"><strong>Cómo se gana:</strong> KO (todos a 0 PV), Rendición, Huida o cumplir el objetivo del combate. La muerte de un personaje jugador requiere consentimiento del jugador.</div>
        </div>

        <!-- 07 · Viajes -->
        <div class="guide-content" id="g-viajes">
          <div class="g-title">Viajes</div>
          <div class="g-sub">// navegación entre islas</div>
          <p>Sistema de navegación basado en <strong>oráculo automático</strong>. El DJ genera el tema de rol con clima, encuentros, hallazgos y peligros determinados por mesas D100.</p>

          <h3>El Trámite de Navegación</h3>
          <p>El Capitán rellena un formulario con: <strong>Origen, Destino, Barco, Ocupantes</strong> (con oficios), <strong>Suministros, Carga especial y Ruta.</strong></p>

          <h3>Distancia en Tramos</h3>
          <table class="guide-table">
            <thead><tr><th>Distancia</th><th>Tramos</th></tr></thead>
            <tbody>
              <tr><td>Islas adyacentes</td><td>1</td></tr>
              <tr><td>Islas cercanas (1-2 casillas)</td><td>2</td></tr>
              <tr><td>Islas medias (3-5 casillas)</td><td>3</td></tr>
              <tr><td>Islas lejanas (6-8 casillas)</td><td>4</td></tr>
              <tr><td>Islas muy lejanas (9+)</td><td>5</td></tr>
              <tr><td>Grand Line — Paraíso</td><td>+1 tramo</td></tr>
              <tr><td>Grand Line — Nuevo Mundo</td><td>+2 tramos</td></tr>
            </tbody>
          </table>

          <h3>El Oráculo de Viaje (4 mesas D100 por tramo)</h3>
          <ol>
            <li><strong>Clima:</strong> Tormenta catastrofica a viento perfecto.</li>
            <li><strong>Encuentros:</strong> Yonko, Patrulla Marine, Barco mercante, Náufrago, Criatura marina...</li>
            <li><strong>Hallazgos:</strong> Poneglyph, Cofre flotante, Mensaje en botella, Isla desconocida...</li>
            <li><strong>Peligros:</strong> Vía de agua, Timón atascado, Motín, Enfermedad, Piratas al acecho...</li>
          </ol>

          <h3>Oficios de a Bordo</h3>
          <table class="guide-table">
            <thead><tr><th>Oficio</th><th>Efecto</th></tr></thead>
            <tbody>
              <tr><td><strong>Navegante</strong></td><td>Reduce Clima Adverso.</td></tr>
              <tr><td><strong>Timonel</strong></td><td>Reduce Peligros.</td></tr>
              <tr><td><strong>Vigía</strong></td><td>Aumenta Hallazgos. Anula Emboscadas.</td></tr>
              <tr><td><strong>Carpintero</strong></td><td>Repara daños al barco.</td></tr>
              <tr><td><strong>Cocinero</strong></td><td>Mantiene moral. Evita motines.</td></tr>
              <tr><td><strong>Médico</strong></td><td>Cura enfermedades.</td></tr>
            </tbody>
          </table>

          <h3>Posts Mínimos y Plazos</h3>
          <table class="guide-table">
            <thead><tr><th>Distancia</th><th>Posts Mínimos</th><th>Plazo Máximo</th></tr></thead>
            <tbody>
              <tr><td>1 tramo</td><td>6</td><td>5 días</td></tr>
              <tr><td>2 tramos</td><td>12</td><td>10 días</td></tr>
              <tr><td>3 tramos</td><td>18</td><td>15 días</td></tr>
              <tr><td>4 tramos</td><td>24</td><td>20 días</td></tr>
              <tr><td>5 tramos</td><td>30</td><td>25 días</td></tr>
            </tbody>
          </table>

          <h3>Naufragio</h3>
          <p>Ocurre cuando: Peligro Catastrofico no superado, Casco a 0 PV, 3 Puntos de Ruptura, derrota en combate naval o Calm Belt sin Motor. El naufragio tiene 3 fases: <strong>Impacto, Evacuación y A la Deriva.</strong> El barco se pierde permanentemente.</p>

          <div class="guide-note"><strong>Sin Log Pose en Grand Line:</strong> navegar es estadísticamente un suicidio. Siempre lleva Log Pose funcional, Navegante a bordo y suministros extra.</div>
        </div>

        <!-- 08 · Batallas navales -->
        <div class="guide-content" id="g-naval">
          <div class="g-title">Batallas navales</div>
          <div class="g-sub">// combate barco contra barco</div>
          <p>Sistema completo de combate naval con zonas objetivo, puestos de tripulación y dos capas simultáneas de acción (naval + personaje).</p>

          <h3>El Tablero Naval</h3>
          <p>El DJ genera: <strong>Terreno</strong> (Mar Abierto, Islotes, Archipiélago, Hielo, Niebla, Tormenta), <strong>Corrientes, Visibilidad y Distancia Inicial.</strong></p>

          <h3>Zonas del Barco</h3>
          <table class="guide-table">
            <thead><tr><th>Zona</th><th>PV</th><th>Qué alberga</th></tr></thead>
            <tbody>
              <tr><td><strong>Casco</strong></td><td>PV totales</td><td>Integridad estructural</td></tr>
              <tr><td><strong>Mástiles</strong></td><td>20% c/u</td><td>Velas, velocidad</td></tr>
              <tr><td><strong>Timón</strong></td><td>10%</td><td>Control de dirección</td></tr>
              <tr><td><strong>Cubierta</strong></td><td>15%</td><td>Tripulantes en combate</td></tr>
              <tr><td><strong>Cañones</strong></td><td>15%</td><td>Batería de cada banda</td></tr>
              <tr><td><strong>Bodega</strong></td><td>25%</td><td>Carga, suministros</td></tr>
            </tbody>
          </table>

          <h3>Puestos de Combate</h3>
          <table class="guide-table">
            <thead><tr><th>Puesto</th><th>Oficio</th><th>Acciones Clave</th></tr></thead>
            <tbody>
              <tr><td><strong>Capitán</strong></td><td>Autoridad</td><td>Órdenes (+1 a tripulación), Inspirar (+2)</td></tr>
              <tr><td><strong>Timonel</strong></td><td>Timonel</td><td>Maniobras evasivas, posicionamiento, abordaje</td></tr>
              <tr><td><strong>Artillero</strong></td><td>Artillero</td><td>Disparar salvas, munición especial</td></tr>
              <tr><td><strong>Carpintero</strong></td><td>Carpintero</td><td>Reparar zonas, sellar brechas</td></tr>
              <tr><td><strong>Vigía</strong></td><td>Vigía</td><td>Detectar, anticipar, marcar debilidades</td></tr>
            </tbody>
          </table>

          <h3>Abordaje (4 pasos)</h3>
          <ol>
            <li><strong>Cerrar Distancia:</strong> Timonel reduce a 1 casilla.</li>
            <li><strong>Maniobra de Abordaje:</strong> Tirada del Timonel para trabar barcos.</li>
            <li><strong>Enfrentar Defensa:</strong> Tirada de FUE del líder + atacantes vs Defensa de Abordaje enemiga.</li>
            <li><strong>Combate en Cubierta:</strong> Reglas normales de combate (AV-01).</li>
          </ol>

          <h3>Hundimiento</h3>
          <p>Cuando el Casco llega a 0 PV, el barco se hunde en 4 turnos. Los tripulantes deben evacuar. El barco se pierde permanentemente.</p>

          <div class="guide-note"><strong>La Capa de Personajes</strong> ocurre simultáneamente. Un personaje divide sus PA entre acciones navales (disparar cañones) y acciones de personaje (usar cartas contra la cubierta enemiga).</div>
        </div>

        <!-- 09 · Estilos de lucha -->
        <div class="guide-content" id="g-estilos">
          <div class="g-title">Estilos de lucha</div>
          <div class="g-sub">// 25 estilos de combate</div>
          <p>Un <strong>Estilo de Lucha</strong> define la escuela marcial de tu personaje. Cada estilo ofrece: <strong>pasiva</strong> (bonificación permanente), <strong>mecánica única</strong> (habilidad especial), y <strong>cartas exclusivas</strong> que solo sus practicantes pueden aprender.</p>

          <h3>Niveles de Maestría (por uso, no por PP)</h3>
          <table class="guide-table">
            <thead><tr><th>Nivel</th><th>Puntos requeridos</th><th>Tier máximo</th></tr></thead>
            <tbody>
              <tr><td>Iniciado</td><td>0</td><td>I</td></tr>
              <tr><td>Practicante</td><td>5</td><td>II</td></tr>
              <tr><td>Experto</td><td>15</td><td>III</td></tr>
              <tr><td>Maestro</td><td>35</td><td>IV</td></tr>
              <tr><td>Gran Maestro</td><td>75</td><td>V</td></tr>
            </tbody>
          </table>
          <p>Cada vez que usas una carta del estilo en combate, ganas 1 punto. <strong>Las cartas desbloqueadas al subir de nivel NO cuestan PP.</strong></p>

          <h3>Límite de Estilos</h3>
          <p>Máximo <strong>4 estilos</strong> simultáneos (5 con la virtud correspondiente). Para aprender uno nuevo debes encontrar un maestro NPC y rolear al menos un tema de entrenamiento.</p>

          <h3>Estilos Destacados</h3>
          <table class="guide-table">
            <thead><tr><th>Estilo</th><th>Pasiva</th><th>Mecánica Única</th></tr></thead>
            <tbody>
              <tr><td><strong>Ittoryu</strong></td><td>+1 DES con una espada. Ignora 2 de armadura.</td><td>Iaido: primer ataque +5 iniciativa, no se puede esquivar.</td></tr>
              <tr><td><strong>Santoryu</strong></td><td>+1 FUE con espadas. Enemigos -2 REF.</td><td>Tercera espada en la boca. +1d4 daño.</td></tr>
              <tr><td><strong>Rokushiki</strong></td><td>+1 AGI, +1 VIG permanentes.</td><td>6 técnicas base (Soru, Geppo, Tekkai, Rankyaku, Shigan, Rokuogan).</td></tr>
              <tr><td><strong>Gyojin Karate</strong></td><td>Ignora 25% armadura. +1 FUE en agua.</td><td>Manipulación hídrica. Tag [Agua] automático.</td></tr>
              <tr><td><strong>Black Leg</strong></td><td>Patadas usan DES. +2 daño sin usar manos.</td><td>Sky Walk: permanece en el aire 2 rondas.</td></tr>
              <tr><td><strong>Electro (Mink)</strong></td><td>+1d4 daño eléctrico a todos los ataques.</td><td>Sobrecarga: daño ×2 sobre 50% PV.</td></tr>
              <tr><td><strong>Sniper Style</strong></td><td>+1 PER. Sin penalización por distancia.</td><td>Crítico ampliado (19-20). Elige localización.</td></tr>
              <tr><td><strong>Dials Combat</strong></td><td>-2 EN a cartas [Dial]. Identifica Dials.</td><td>Fusión de Dials: combina hasta 3 Dials en una carta.</td></tr>
              <tr><td><strong>Ninjutsu</strong></td><td>+1 AGI para sigilo e iniciativa.</td><td>Kawarimi: sustitución 1 vez por combate.</td></tr>
              <tr><td><strong>Hasshoken</strong></td><td>Ignora 5 de armadura/blindaje.</td><td>Onda Interna: 50% daño a VOL directamente.</td></tr>
            </tbody>
          </table>
          <p>Hay <strong>25 estilos en total</strong>: Ittoryu, Nitoryu, Santoryu, Kyotoryu, Mutoryu, Rokushiki, Gyojin Karate, Gyojin Jujutsu, Black Leg, Hasshoken, Okama Kempo, Electro, Sniper, Dials Combat, Ninjutsu, Seimei Kikan, Canto de Batalla, Estilo del Acantilado, Mil Manos, Cazador, Sombra, Explosión, Herrero, Ramen Kempo y Furia.</p>
        </div>

        <!-- 10 · Facciones -->
        <div class="guide-content" id="g-facciones">
          <div class="g-title">Facciones</div>
          <div class="g-sub">// elige tu bando</div>
          <p>Sistema de pertenencia, progresión, recursos y conflicto entre facciones.</p>

          <h3>Las Seis Facciones Jugables</h3>
          <table class="guide-table">
            <thead><tr><th>Facción</th><th>Progresión por...</th><th>Tiene rangos</th></tr></thead>
            <tbody>
              <tr><td>Marine</td><td>REP (solo misiones y eventos)</td><td>9 rangos</td></tr>
              <tr><td>Pirata</td><td>Wanted (recompensa)</td><td>9 títulos por umbral</td></tr>
              <tr><td>Revolucionario</td><td>REP (solo misiones y eventos)</td><td>7 rangos</td></tr>
              <tr><td>Gobierno Mundial</td><td>REP (solo misiones y eventos)</td><td>7 rangos</td></tr>
              <tr><td>Cazarrecompensas</td><td>Capturas Acumuladas</td><td>7 rangos (metales)</td></tr>
              <tr><td>Civil</td><td>Influencia (libre)</td><td>Sin rangos</td></tr>
            </tbody>
          </table>

          <h3>Rangos Marine</h3>
          <table class="guide-table">
            <thead><tr><th>#</th><th>Rango</th><th>REP</th><th>Beneficio Clave</th></tr></thead>
            <tbody>
              <tr><td>1</td><td>Soldado</td><td>0</td><td>Uniforme, arma reglamentaria, acceso a bases.</td></tr>
              <tr><td>3</td><td>Teniente</td><td>70</td><td>Mando de patrulla. Den Den Mushi negro.</td></tr>
              <tr><td>4</td><td>Capitán</td><td>120</td><td>Base pequeña o barco propio.</td></tr>
              <tr><td>7</td><td>Vicealmirante</td><td>330</td><td>Haki obligatorio. Puede solicitar Fruta.</td></tr>
              <tr><td>8</td><td>Almirante</td><td>450</td><td>3 puestos. Buster Call.</td></tr>
              <tr><td>9</td><td>Almirante de Flota</td><td>600</td><td>1 puesto. Comandante absoluto.</td></tr>
            </tbody>
          </table>

          <h3>Títulos Pirata (por Wanted)</h3>
          <table class="guide-table">
            <thead><tr><th>#</th><th>Título</th><th>Wanted</th></tr></thead>
            <tbody>
              <tr><td>1</td><td>Grumete</td><td>0</td></tr>
              <tr><td>5</td><td>Supernova</td><td>200M - 500M</td></tr>
              <tr><td>7</td><td>Mundial</td><td>1,000M - 3,000M</td></tr>
              <tr><td>8</td><td>Yonkou</td><td>3,000M - 5,000M</td></tr>
              <tr><td>9</td><td>Rey Pirata</td><td>5,000M+</td></tr>
            </tbody>
          </table>

          <h3>Cambio de Facción</h3>
          <p>Máximo <strong>2 cambios</strong> en toda la vida del personaje. Cada cambio requiere justificación narrativa y tiene consecuencias. Cambiar de Marine a otra facción es muy grave: pierdes 100% REP y ganas Wanted automático.</p>

          <h3>Guerras entre Facciones</h3>
          <p>Cuando la Tensión entre dos facciones alcanza 85+ en el Tablero Mundial, se desencadena una guerra. Fases: Tensión, Declaración, Resolución y Consecuencias. Las guerras generan misiones especiales con REP ×1.5 y PP +25%.</p>
        </div>

        <!-- 11 · Grupos y bases -->
        <div class="guide-content" id="g-grupos">
          <div class="g-title">Grupos y bases</div>
          <div class="g-sub">// tripulaciones, barcos y bases</div>
          <p>Sistema completo de tripulaciones, barcos, bases fijas, oficios de a bordo, acompañantes NPC y economía de grupo.</p>

          <h3>Tripulaciones</h3>
          <p>Cualquier jugador puede crear un grupo sin requisitos de rango ni coste. Estructura: <strong>Capitán/Líder, Primer Oficial (opcional), Oficiales y Tripulantes.</strong> Un personaje pertenece a un solo grupo a la vez.</p>

          <h3>Oficios de a Bordo (13 roles)</h3>
          <table class="guide-table">
            <thead><tr><th>Oficio</th><th>Función</th><th>Bonificación</th></tr></thead>
            <tbody>
              <tr><td><strong>Navegante</strong></td><td>Dirige el rumbo. Interpreta clima.</td><td>+Nivel a Velocidad del barco</td></tr>
              <tr><td><strong>Cocinero</strong></td><td>Prepara comidas. Gestiona suministros.</td><td>Comidas: +Nivel x5% recuperación EN</td></tr>
              <tr><td><strong>Médico</strong></td><td>Cura heridas y enfermedades.</td><td>+Nivel a tiradas de curación</td></tr>
              <tr><td><strong>Carpintero</strong></td><td>Repara y mantiene el barco.</td><td>Repara Nivel x10 Casco/día</td></tr>
              <tr><td><strong>Herrero/Armero</strong></td><td>Fabrica y mejora armas.</td><td>+Nivel al daño de armas forjadas</td></tr>
              <tr><td><strong>Artillero</strong></td><td>Opera los cañones.</td><td>+Nivel a tiradas de disparo</td></tr>
              <tr><td><strong>Científico</strong></td><td>Investiga y desarrolla tecnología.</td><td>Crea gadgets. +Nivel a INT</td></tr>
              <tr><td><strong>Vigía</strong></td><td>Observa el horizonte.</td><td>+Nivel a PER para detectar</td></tr>
              <tr><td><strong>Músico</strong></td><td>Mantiene la moral.</td><td>+Nivel a tiradas de Moral</td></tr>
              <tr><td><strong>Arqueólogo</strong></td><td>Lee Poneglyphs. Interpreta ruinas.</td><td>+Nivel para identificar objetos antiguos</td></tr>
              <tr><td><strong>Timonel</strong></td><td>Gobierna el barco en combate.</td><td>+Nivel a Maniobrabilidad</td></tr>
            </tbody>
          </table>

          <h3>Tipos de Barco</h3>
          <table class="guide-table">
            <thead><tr><th>Tipo</th><th>Casco</th><th>Vel</th><th>Trip</th><th>Cañones</th><th>Coste</th></tr></thead>
            <tbody>
              <tr><td>Bote / Esquife</td><td>50</td><td>1</td><td>3</td><td>0</td><td>5,000</td></tr>
              <tr><td>Balandra</td><td>150</td><td>3</td><td>10</td><td>1d6</td><td>100,000</td></tr>
              <tr><td>Goleta</td><td>300</td><td>4</td><td>25</td><td>2d6</td><td>200,000</td></tr>
              <tr><td>Bergantín</td><td>500</td><td>5</td><td>50</td><td>3d6</td><td>500,000</td></tr>
              <tr><td>Fragata</td><td>800</td><td>5</td><td>80</td><td>4d6</td><td>2,000,000</td></tr>
              <tr><td>Galeón</td><td>1500</td><td>3</td><td>200</td><td>6d6</td><td>10,000,000</td></tr>
            </tbody>
          </table>

          <h3>Mejoras de Barco</h3>
          <p>Refuerzo de Casco (+100 a +500), Velas Mejoradas (+1 Vel), Motor a Vapor (+2 Vel, navega sin viento), Timón de Precisión (+1 Man), Cañones Extra (+1d6), Cañones Pesados (+2d6), Blindaje (+5 a +10), Camuflaje, Taller de Oficio, Enfermería, Cocina Profesional. Requieren Carpintero y materiales.</p>

          <h3>Bases Fijas</h3>
          <table class="guide-table">
            <thead><tr><th>Tipo</th><th>Coste Base</th><th>Mantenimiento/ciclo</th></tr></thead>
            <tbody>
              <tr><td>Escondite</td><td>10,000</td><td>1,000</td></tr>
              <tr><td>Refugio</td><td>100,000</td><td>10,000</td></tr>
              <tr><td>Fortaleza</td><td>1,000,000</td><td>100,000</td></tr>
              <tr><td>Puerto</td><td>500,000</td><td>50,000</td></tr>
              <tr><td>Astillero</td><td>1,500,000</td><td>150,000</td></tr>
            </tbody>
          </table>
        </div>

        <!-- 12 · Virtudes y defectos -->
        <div class="guide-content" id="g-virtudes">
          <div class="g-title">Virtudes y defectos</div>
          <div class="g-sub">// personaliza tu personaje</div>
          <p>Catálogo completo de virtudes y defectos para la creación de personaje.</p>

          <h3>Sistema de Puntos</h3>
          <table class="guide-table">
            <thead><tr><th>Concepto</th><th>Valor</th></tr></thead>
            <tbody>
              <tr><td><strong>PC iniciales</strong></td><td>6 (gratis)</td></tr>
              <tr><td>Virtud Menor</td><td>1-2 PC</td></tr>
              <tr><td>Virtud Media</td><td>3 PC</td></tr>
              <tr><td>Virtud Mayor</td><td>4-5 PC</td></tr>
              <tr><td>Defecto Leve</td><td>+1 PC</td></tr>
              <tr><td>Defecto Medio</td><td>+2-3 PC</td></tr>
              <tr><td>Defecto Grave</td><td>+4-5 PC</td></tr>
            </tbody>
          </table>

          <h3>Categorías de Virtudes</h3>
          <ul>
            <li><strong>Linaje e Identidad:</strong> Voluntad de D. (3 PC), Descendiente de los Dioses (4 PC).</li>
            <li><strong>Facción y Reputación:</strong> Acto Triunfal (2 PC), Líder Nato (1 PC), Carismático (1 PC), Intimidante (1 PC), Fama (1 PC), Desapercibido (1 PC), El Más Buscado (3 PC), Doble Vida (2 PC).</li>
            <li><strong>Físicas:</strong> Grandullón (1 PC), El Más Grande (3 PC), Pequeñín (1 PC), Nadador Nato (1 PC), Sentidos Aumentados (2-5 PC).</li>
            <li><strong>Supervivencia:</strong> Sueño Ligero (2 PC), Afinidad Animal (1 PC), Orientación (1 PC), Optimista (3 PC, +5 VOL).</li>
            <li><strong>Progresión:</strong> Entrenamiento Intensivo (3 PC, +25% PP), Erudito (4 PC), Polivalente (2 PC), Iron Heart (2 PC, +3 Slots de implante).</li>
            <li><strong>Riqueza:</strong> Adinerado 1-3 (1-3 PC, hasta +10M rupias).</li>
            <li><strong>Especiales:</strong> Voz de Todas las Cosas (5 PC), Potencial de Fruta (1 PC).</li>
          </ul>

          <h3>Defectos Destacados</h3>
          <table class="guide-table">
            <thead><tr><th>Defecto</th><th>Devuelve</th><th>Efecto</th></tr></thead>
            <tbody>
              <tr><td><strong>Héroe</strong></td><td>+5 PC</td><td>No puedes evitar ayudar a quien lo necesita.</td></tr>
              <tr><td><strong>Ceguera</strong></td><td>+5 PC</td><td>-25 REF efectiva.</td></tr>
              <tr><td><strong>Nunca Rendirse</strong></td><td>+3 PC</td><td>Jamás huyes de un combate que empezaste.</td></tr>
              <tr><td><strong>Piadoso</strong></td><td>+3 PC</td><td>No puedes matar.</td></tr>
              <tr><td><strong>Enemigo Declarado</strong></td><td>+4 PC</td><td>Odias visceralmente a una facción.</td></tr>
              <tr><td><strong>Bocazas</strong></td><td>+2 PC</td><td>No dejas pasar una ofensa.</td></tr>
              <tr><td><strong>Crédulo</strong></td><td>+3 PC</td><td>Te crees casi todo.</td></tr>
            </tbody>
          </table>

          <div class="guide-note"><strong>Los defectos molan.</strong> Un personaje con defectos interesantes da mucho más juego en las historias que uno perfecto. Además, te dan PC para comprar virtudes.</div>
        </div>

        <!-- 13 · Bestias y NPCs -->
        <div class="guide-content" id="g-bestias">
          <div class="g-title">Bestias y NPCs</div>
          <div class="g-sub">// criaturas y acompañantes</div>
          <p>Bestiario completo, sistema de doma, mascotas con stats, creación de NPCs, criaturas legendarias y encuentros aleatorios por región.</p>

          <h3>Criaturas Marinas</h3>
          <table class="guide-table">
            <thead><tr><th>Criatura</th><th>Rango</th><th>PV</th><th>Acciones Naturales</th></tr></thead>
            <tbody>
              <tr><td>Sea King Pequeño</td><td>D</td><td>60</td><td>Mordisco (1d8+FUE), Coletazo, Sumergir</td></tr>
              <tr><td>Sea King Mediano</td><td>B</td><td>100</td><td>Devorar (2d8+FUE), Embestida, Rugido</td></tr>
              <tr><td>Sea King Grande</td><td>A</td><td>160</td><td>Torbellino (3d8+FUE), Coletazo Devastador</td></tr>
              <tr><td>Rey de los Sea Kings</td><td>M</td><td>250</td><td>Tsunami (4d10+FUE), Devorar Isla</td></tr>
              <tr><td>Kraken</td><td>A</td><td>180</td><td>Abrazo del Abismo (3d10+FUE)</td></tr>
              <tr><td>Leviatán</td><td>S</td><td>250</td><td>Golpe de Cola (4d10+FUE), Canto</td></tr>
            </tbody>
          </table>

          <h3>Criaturas Terrestres</h3>
          <table class="guide-table">
            <thead><tr><th>Criatura</th><th>Rango</th><th>PV</th></tr></thead>
            <tbody>
              <tr><td>Lobo Gigante</td><td>C</td><td>60</td></tr>
              <tr><td>Oso de Montaña</td><td>B</td><td>100</td></tr>
              <tr><td>Dinosaurio (Zoan Antiguo salvaje)</td><td>A</td><td>160</td></tr>
              <tr><td>Guardián de Isla</td><td>S</td><td>220</td></tr>
              <tr><td>Gorila Gigante</td><td>B</td><td>110</td></tr>
            </tbody>
          </table>

          <h3>Doma de Criaturas</h3>
          <table class="guide-table">
            <thead><tr><th>Tu Rango</th><th>Máximo Rango Domable</th></tr></thead>
            <tbody>
              <tr><td>F-E</td><td>No puedes domar</td></tr>
              <tr><td>D-C</td><td>D</td></tr>
              <tr><td>B-A</td><td>C</td></tr>
              <tr><td>S-SS</td><td>B</td></tr>
              <tr><td>M</td><td>A</td></tr>
              <tr><td>M+</td><td>S (Legendarias requieren Voz de Todas las Cosas)</td></tr>
            </tbody>
          </table>
          <p>Proceso: interactuar en rol, tirar CAR+SEN vs VOL de la criatura, varios intentos para VOL alta. Máximo <strong>2 criaturas domadas</strong> por personaje.</p>

          <h3>Mascotas (Virtud, 3 PC)</h3>
          <p>A diferencia de una criatura domada, la mascota <strong>puede subir de rango</strong> con PP y tiene vínculo profundo. La progresión va de Tier 1 (Cachorro, F) a Tier 8 (Primigenio, SS) con costes de 5 a 120 PP.</p>

          <h3>NPCs Menores (Acompañantes)</h3>
          <table class="guide-table">
            <thead><tr><th>Rango</th><th>Stats</th><th>Coste</th></tr></thead>
            <tbody>
              <tr><td>F</td><td>Todas F</td><td>Gratis (1 por grupo)</td></tr>
              <tr><td>E</td><td>Todas E, 1 oficio Aprendiz</td><td>10 PP</td></tr>
              <tr><td>D</td><td>Todas D, 1 oficio Competente</td><td>25 PP</td></tr>
              <tr><td>C</td><td>4 stats en C, resto D, cartas Tier I</td><td>50 PP</td></tr>
              <tr><td>B</td><td>6 stats en C, 3 en B, cartas Tier I</td><td>100 PP</td></tr>
            </tbody>
          </table>

          <div class="guide-note"><strong>Las Bestias Primigenias</strong> (Leviatán, Behemoth, Ziz) NO se combaten. Son eventos cataclísmicos (PE 10). Sobrevivir a una otorga 5 PE.</div>
        </div>
<!-- 14 · Cyborgs e implantes -->
        <div class="guide-content" id="g-cyborgs">
          <div class="g-title">Cyborgs e implantes</div>
          <div class="g-sub">// guerreros mejorados</div>
          <p>Sistema completo de implantes mecánicos y biológicos. Conviértete en un guerrero mejorado con límites físicos claros.</p>

          <h3>Slots de Implante</h3>
          <p><strong>Slots máximos = (VIG efectiva + CON efectiva + VOL efectiva) / 2</strong> (mínimo 3). Iron Heart da +3 Slots adicionales.</p>

          <h3>Tiers de Implante</h3>
          <table class="guide-table">
            <thead><tr><th>Tier</th><th>Slots</th><th>Coste</th><th>Durabilidad</th></tr></thead>
            <tbody>
              <tr><td>Básico (Tier I)</td><td>1</td><td>50K - 200K</td><td>10 PD</td></tr>
              <tr><td>Avanzado (Tier II)</td><td>2</td><td>200K - 1M</td><td>20 PD</td></tr>
              <tr><td>Superior (Tier III)</td><td>3-5</td><td>1M - 10M+</td><td>35 PD</td></tr>
            </tbody>
          </table>

          <h3>Implantes Mecánicos Destacados</h3>
          <table class="guide-table">
            <thead><tr><th>Implante</th><th>Tier</th><th>Efecto</th><th>Coste</th></tr></thead>
            <tbody>
              <tr><td>Brazo de Acero</td><td>I</td><td>+1 FUE efectiva. Ignora heridas Leves.</td><td>75K</td></tr>
              <tr><td>Piernas Impulsoras</td><td>I</td><td>+1 AGI movimiento. Salto ×2.</td><td>80K</td></tr>
              <tr><td>Ojo Mecánico</td><td>I</td><td>+1 PER. Visión nocturna.</td><td>60K</td></tr>
              <tr><td>Cañón de Muñeca</td><td>I</td><td>1d8+DES a distancia. Munición ilimitada.</td><td>120K</td></tr>
              <tr><td>Brazos Hidráulicos</td><td>II</td><td>+2 FUE. Empuña objetos tamaño Gigante.</td><td>350K</td></tr>
              <tr><td>Sistema de Puntería</td><td>II</td><td>+2 ataques a distancia. Zoom x4.</td><td>300K</td></tr>
              <tr><td>Propulsores de Espalda</td><td>II</td><td>Vuelo limitado. +1 AGI esquivar.</td><td>500K</td></tr>
              <tr><td>Brazos de Batalla</td><td>III</td><td>+3 FUE, +2 DES. Cuchillas retráctiles.</td><td>2.5M</td></tr>
              <tr><td>Corazón Mecánico</td><td>III</td><td>+25 PV. Regenera 5 PV/turno. Inmune a venenos.</td><td>8M</td></tr>
            </tbody>
          </table>

          <h3>Implantes Biológicos</h3>
          <table class="guide-table">
            <thead><tr><th>Implante</th><th>Tier</th><th>Efecto</th><th>Coste EN</th></tr></thead>
            <tbody>
              <tr><td>Branquias Artificiales</td><td>I</td><td>Respiras bajo el agua.</td><td>-3 EN</td></tr>
              <tr><td>Músculo Denso</td><td>I</td><td>+1 FUE efectiva.</td><td>-3 EN</td></tr>
              <tr><td>Piel Coriácea</td><td>II</td><td>-3 al daño recibido.</td><td>-5 EN</td></tr>
              <tr><td>Regeneración Acelerada</td><td>II</td><td>+3 PV al inicio de tu turno.</td><td>-5 EN</td></tr>
              <tr><td>Corazón de Bestia</td><td>III</td><td>+20 PV, +1 VIG. Frenesí 1/combate.</td><td>-8 EN</td></tr>
            </tbody>
          </table>

          <h3>Compatibilidad con Frutas del Diablo</h3>
          <ul>
            <li><strong>Paramecia + Implantes normales:</strong> Compatible sin restricciones.</li>
            <li><strong>Zoan + Implante en zona transformada:</strong> El implante NO se transforma. Pierdes su efecto mientras la zona esté transformada.</li>
            <li><strong>Logia + Implantes normales:</strong> Compatible. El implante se vuelve intangible contigo.</li>
            <li><strong>CUALQUIER Fruta + Implante con Kairoseki:</strong> INCOMPATIBLE. Anula tus poderes permanentemente.</li>
          </ul>

          <div class="guide-note"><strong>Los implantes no necesitan combustible.</strong> Una vez instalados funcionan siempre. Los fabrica un <strong>Ingeniero</strong> (mecánicos) o <strong>Biólogo</strong> (biológicos) y los instala un <strong>Médico</strong>.</div>
        </div>

        <!-- 15 · Inframundo -->
        <div class="guide-content" id="g-inframundo">
          <div class="g-title">Inframundo</div>
          <div class="g-sub">// economía criminal</div>
          <p>Sistema completo de la economía criminal y el bajo mundo. Jerarquía competitiva, mercado negro, subastas clandestinas y riesgo de exposición.</p>

          <h3>Qué es el Inframundo</h3>
          <p>No es una facción. Es una <strong>capa secreta de la sociedad</strong> que existe en paralelo a Marines, Piratas y el Gobierno. Todos los bandos lo usan. Nadie lo admite.</p>

          <h3>Jerarquía Criminal</h3>
          <table class="guide-table">
            <thead><tr><th>Rango</th><th>Cupo</th><th>Beneficio Clave</th></tr></thead>
            <tbody>
              <tr><td><strong>Lacayo</strong></td><td>Ilimitado</td><td>Acceso al Mercado Negro básico.</td></tr>
              <tr><td><strong>Matón</strong></td><td>Ilimitado</td><td>Descuento 5%. 1 secuaz NPC.</td></tr>
              <tr><td><strong>Capo</strong></td><td>24 máximos</td><td>Descuento 10%. Acceso a Subastas.</td></tr>
              <tr><td><strong>Señor</strong></td><td>12 máximos</td><td>Descuento 20%. 1 rumor verificado por ciclo.</td></tr>
              <tr><td><strong>Emperador</strong></td><td>6 máximos</td><td>Descuento 35%. Creas objetos para el Mercado Negro.</td></tr>
            </tbody>
          </table>

          <h3>Cómo Entrar</h3>
          <p>No puedes simplemente unirte. Necesitas: <strong>encontrar un contacto</strong> (rol en tabernas, puertos clandestinos), <strong>ganarte la entrada</strong> (misión de prueba), y <strong>completar tu primer encargo</strong>.</p>

          <h3>Cómo Derrocar a un Superior</h3>
          <p>El Inframundo es competitivo. Para ascender, debes <strong>desafiar al que está por encima</strong>. Tipos de desafío: duelo directo, guerra de territorios, ruina económica, exposición, golpe de estado o arbitraje de la Mesa.</p>

          <h3>El Mercado Negro</h3>
          <p>Tienda rotativa exclusiva. Inventario cambia cada ciclo (15 días). Vende objetos ilegales: armas de calidad, Dials raros, Kairōseki, Frutas del Diablo, Den Den Mushi negro, información clasificada.</p>

          <h3>Riesgo de Exposición</h3>
          <p>Cada misión criminal acumula un porcentaje de exposición. Si fallas la tirada D100, eres descubierto. Consecuencias: desde investigación Marine local hasta CP0 enviado a neutralizarte. La exposición se reduce con el tiempo sin actividad criminal.</p>

          <div class="guide-note"><strong>El anonimato es poder.</strong> Los verdaderos peces gordos no aparecen en los periódicos. Operan desde las sombras.</div>
        </div>

        <!-- 16 · Conquistas -->
        <div class="guide-content" id="g-conquistas">
          <div class="g-title">Conquistas</div>
          <div class="g-sub">// adquisición de territorios</div>
          <p>Cómo adquirir, mantener, defender y perder islas en el mundo de Granblue Fantasy: Eternal.</p>

          <h3>Métodos de Adquisición</h3>
          <table class="guide-table">
            <thead><tr><th>Método</th><th>Dificultad</th></tr></thead>
            <tbody>
              <tr><td><strong>Conquista por Fuerza</strong></td><td>Alta. Invadir y derrotar al defensor.</td></tr>
              <tr><td><strong>Compra</strong></td><td>Variable. Pagar al propietario.</td></tr>
              <tr><td><strong>Herencia</strong></td><td>Baja (narrativa). Recibir como legado.</td></tr>
              <tr><td><strong>Fundación</strong></td><td>Media. Colonizar isla deshabitada.</td></tr>
              <tr><td><strong>Matrimonio Político</strong></td><td>Baja (narrativa).</td></tr>
            </tbody>
          </table>

          <h3>Misiones de Conquista (4 fases)</h3>
          <ol>
            <li><strong>Reconocimiento:</strong> Explorar la isla, investigar al defensor.</li>
            <li><strong>Preparación:</strong> Reunir aliados, planear estrategia.</li>
            <li><strong>Ejecución:</strong> Combate principal. Enfrentar al jefe defensor.</li>
            <li><strong>Consecuencias:</strong> Decidir qué hacer con los vencidos.</li>
          </ol>

          <h3>Valor de las Islas</h3>
          <table class="guide-table">
            <thead><tr><th>Nivel</th><th>Ingresos/ciclo</th><th>Ejemplo</th></tr></thead>
            <tbody>
              <tr><td>Isla Pobre</td><td>5K-25K</td><td>Isla desierta, aldea remota</td></tr>
              <tr><td>Isla Modesta</td><td>25K-100K</td><td>Pueblo pesquero</td></tr>
              <tr><td>Isla Próspera</td><td>100K-500K</td><td>Ciudad portuaria</td></tr>
              <tr><td>Isla Rica</td><td>500K-2.5M</td><td>Capital de reino</td></tr>
              <tr><td>Isla Legendaria</td><td>2.5M+</td><td>Wano, Elbaf</td></tr>
            </tbody>
          </table>

          <h3>Beneficios del Territorio</h3>
          <ul>
            <li><strong>Ingresos pasivos</strong> por impuestos, puerto y negocios.</li>
            <li><strong>Puerto seguro</strong> para tu barco.</li>
            <li><strong>Reclutamiento de NPCs</strong> defensores.</li>
            <li><strong>Laboratorio / Taller</strong> con bonus a oficios.</li>
            <li><strong>Prestigio:</strong> tu nombre aparece en los mapas.</li>
          </ul>

          <h3>Defensa del Territorio</h3>
          <p>Cada territorio tiene defensa automática con NPCs. Si estás ausente, resisten 1-3 rondas. Tienes <strong>1 ciclo de margen</strong> para volver a defender.</p>

          <div class="guide-note"><strong>Al conquistar decides:</strong> Saqueo (recursos, -50% efectividad), Ocupación (todo sigue funcionando), Destrucción (arrasas todo) o Liberación (das la isla a la población, +15 REP).</div>
        </div>

        <!-- 17 · Herencia y legado -->
        <div class="guide-content" id="g-herencia">
          <div class="g-title">Herencia y legado</div>
          <div class="g-sub">// cuando un personaje muere</div>
          <p>La muerte de un personaje no es un game over: es una <strong>transición</strong>. Tu viaje continúa, y tu personaje fallecido pasa a formar parte de la historia del mundo.</p>

          <h3>Regla de Oro</h3>
          <p><strong>Ningún personaje puede morir sin el consentimiento explícito de su jugador.</strong> Si PV llega a 0, queda inconsciente, capturado o herido, pero no muerto.</p>

          <h3>Calidad de la Muerte</h3>
          <table class="guide-table">
            <thead><tr><th>Tier</th><th>Nombre</th><th>% Herencia</th><th>Ejemplo</th></tr></thead>
            <tbody>
              <tr><td>1</td><td>Muerte Torpe</td><td>10%</td><td>Resbalar por escaleras, suicidio.</td></tr>
              <tr><td>2</td><td>Muerte Normal</td><td>25%</td><td>Caer en combate común.</td></tr>
              <tr><td>3</td><td>Muerte Digna</td><td>40%</td><td>Morir protegiendo a un aliado.</td></tr>
              <tr><td>4</td><td>Muerte Épica</td><td>60%</td><td>Enfrentar a un Yonko sin retroceder.</td></tr>
              <tr><td>5</td><td>Muerte Legendaria</td><td>80%</td><td>Tu muerte cambia el mundo (como Roger).</td></tr>
            </tbody>
          </table>

          <h3>Qué se Hereda</h3>
          <table class="guide-table">
            <thead><tr><th>Recurso</th><th>Torpe</th><th>Normal</th><th>Digna</th><th>Épica</th><th>Legendaria</th></tr></thead>
            <tbody>
              <tr><td>PP</td><td>10%</td><td>25%</td><td>40%</td><td>60%</td><td>80%</td></tr>
              <tr><td>Rupias</td><td>10%</td><td>25%</td><td>40%</td><td>60%</td><td>80%</td></tr>
              <tr><td>Objetos</td><td>1</td><td>2</td><td>3</td><td>5</td><td>Todos</td></tr>
              <tr><td>REP (misma facción)</td><td>10%</td><td>25%</td><td>40%</td><td>60%</td><td>80%</td></tr>
              <tr><td>PL</td><td>0%</td><td>0%</td><td>0%</td><td>50%</td><td>100%</td></tr>
            </tbody>
          </table>

          <h3>Lo que NUNCA se Hereda</h3>
          <ul>
            <li>Pacto Primal (renace en el mundo)</li>
            <li>Clase Avanzada de cualquier tipo</li>
            <li>Estilos de combate aprendidos</li>
            <li>Renombre (recompensa)</li>
            <li>Rango de facción</li>
            <li>Títulos y apodos</li>
            <li>Relaciones personales y conocimiento narrativo</li>
            <li>Propiedades y territorios</li>
          </ul>

          <h3>Creación del Heredero</h3>
          <p>Debes esperar <strong>7 días</strong> tras la muerte del personaje anterior. El heredero debe tener nombre único, concepto diferente, y empieza desde Nivel 1 con los PP base + los heredados.</p>

          <div class="guide-note"><strong>Límites:</strong> Máximo 2 muertes por año, 3 meses entre muertes, y el personaje debe haber estado activo al menos 1 mes. Está prohibido matar personajes solo para transferir recursos.</div>
        </div>

        <!-- 18 · Mundo Vivo v3 (guía pública para jugadores) -->
        <div class="guide-content" id="g-mundo-vivo">
          <div class="g-title">Mundo Vivo — La Balanza v3</div>
          <div class="g-sub">// tus acciones cambian el mundo</div>
          <p>El Sistema de Mundo Vivo "La Balanza" es el corazón de Granblue Fantasy: Eternal: <strong>todo lo que haces tiene peso</strong>. Cada misión, cada combate, cada alianza o traición afecta al equilibrio del mundo. Cada mes natural se publica el periódico <strong>Eternal News</strong> contando qué ha pasado y cómo ha cambiado el mundo.</p>

          <h3>¿Cómo afecta a tu personaje?</h3>
          <ul>
            <li><strong>Tus acciones importan:</strong> cada misión que completas, cada tema en presente que abres, mueve las métricas del mundo.</li>
            <li><strong>El mundo reacciona:</strong> si los piratas causan caos en un mar, la Marina refuerza la zona. Si ayudas a una isla, su prosperidad mejora.</li>
            <li><strong>El periódico "Eternal News":</strong> cada mes se publica con las noticias del mundo. Si haces algo importante, <strong>saldrás en el periódico</strong>.</li>
            <li><strong>Misiones mensuales:</strong> el estado del mundo genera misiones. Si un mar está en crisis, aparecerán misiones de ayuda. Si hay tensiones, misiones de conflicto.</li>
            <li><strong>Navegación afectada:</strong> el clima, los peligros y los encuentros en tus viajes dependen del estado del mar por el que navegues.</li>
            <li><strong>Eventos al llegar a una isla:</strong> al abrir un tema en presente, el estado del mar determina si te encuentras algo (bueno, malo o simplemente interesante).</li>
          </ul>

          <h3>El Tablero Mundial</h3>
          <p>El mundo se divide en <strong>8 mares</strong> (zonas): East Blue, West Blue, North Blue, South Blue, Calm Belt, Red Line, Paraíso y New World. Cada mar tiene <strong>10 métricas</strong> que puedes consultar en la página <strong>Estado del Mundo</strong>:</p>
          <ul>
            <li><strong>Clima (CLI):</strong> cómo es el clima predominante (0=tormentas perpetuas, 100=calma absoluta).</li>
            <li><strong>Peligro Marítimo (PEL):</strong> qué peligroso es navegar (Sea Kings, corrientes, Calm Belt).</li>
            <li><strong>Riqueza (RIQ):</strong> recursos económicos y naturales de la zona.</li>
            <li><strong>Orden Civil (CIV):</strong> control y legalidad en las islas (0=anarquía, 100=ferreo).</li>
            <li><strong>Presión Marine (MAR):</strong> presencia e influencia de la Marina/Gobierno.</li>
            <li><strong>Actividad Pirata (PIR):</strong> cuántos piratas operan en la zona.</li>
            <li><strong>Influencia Revolucionaria (REV):</strong> presencia del Ejército Revolucionario.</li>
            <li><strong>Influencia del Inframundo (INF):</strong> poder del crimen organizado.</li>
            <li><strong>Estabilidad General (EST):</strong> salud general del mar (media ponderada).</li>
            <li><strong>Tensión General (TEN):</strong> tensión entre facciones en el mar.</li>
          </ul>
          <p>Además, hay <strong>6 facciones</strong> (Marine, Pirata, Revolucionario, Gobierno Mundial, Cazarrecompensas, Civil), cada una con su propio perfil: Reputación, Cohesión, Poder Militar, Influencia Política, Recursos, Moral y Alcance. Todo visible en la página Estado del Mundo.</p>

          <h3>¿Cómo contribuyes?</h3>
          <ol>
            <li><strong>Completa misiones</strong> — generan sucesos que la IA procesa cada mes.</li>
            <li><strong>Abre temas en presente</strong> — pueden generar eventos en la isla según el estado del mar.</li>
            <li><strong>Notifica tus temas</strong> — usa el trámite "Notificar tema" en el foro para que el staff registre tus acciones.</li>
            <li><strong>Participa en la navegación</strong> — los viajes que hagas serán registrados y afectan al mundo.</li>
          </ol>

          <h3>Continuidad entre meses</h3>
          <p>El Mundo Vivo tiene <strong>memoria</strong>. El periódico de este mes recuerda lo que pasó el mes pasado. Los hilos narrativos se extienden a lo largo de varios meses. Una noticia de hace meses puede tener consecuencias hoy. <strong>Tú puedes ser parte de esas historias.</strong> Si tu personaje aparece en un periódico, ese hecho no se olvida — puede evolucionar, generar tramas o volver a mencionarse en el futuro.</p>

          <h3>Política anti-escalada</h3>
          <p>El sistema está diseñado para que la guerra sea <strong>rara y difícil</strong>. Un combate aislado no desata una guerra. Hacen falta meses de tensión creciente y múltiples enfrentamientos para que dos facciones lleguen a un conflicto abierto. El estado normal del mundo es la <strong>paz</strong> y la <strong>aventura</strong>.</p>

          <h3>¿Dónde verlo?</h3>
          <p>Consulta el estado actual del mundo en la página <a href="estado-mundo.php"><strong>Estado del Mundo</strong></a>, donde puedes ver las métricas de cada mar, el perfil de cada facción, los arcos en marcha y los NPCs importantes con su paradero.</p>

          <div class="guide-note"><strong>Pequeñas acciones juntas mueven montañas.</strong> Varias misiones pequeñas en la misma zona durante un mes pueden desencadenar eventos mayores. La constancia y la coordinación entre jugadores tienen premio. Y recuerda: el periódico del mes que viene leerá lo que hagas este mes.</div>
        </div>

        <!-- 19 · Frutas del Diablo -->
        <div class="guide-content" id="g-frutas">
          <div class="g-title">Frutas del Diablo</div>
          <div class="g-sub">// poderes malditos del mar</div>
          <p>Sistema unificado de Frutas del Diablo: adquisición, tipos, uso en combate, despertar (Awakening) e impacto en el mundo.</p>

          <h3>Los Tres Tipos</h3>
          <table class="guide-table">
            <thead><tr><th>Tipo</th><th>Descripción</th><th>Frecuencia</th></tr></thead>
            <tbody>
              <tr><td><strong>Paramecia</strong></td><td>Poder corporal o ambiental. La más diversa.</td><td>57%</td></tr>
              <tr><td><strong>Zoan</strong></td><td>Transformación en animal. Tres formas: humana, bestia e híbrida.</td><td>28%</td></tr>
              <tr><td><strong>Logia</strong></td><td>Transformación en elemento natural. Intangibilidad.</td><td>15%</td></tr>
            </tbody>
          </table>

          <h3>Cómo se Obtiene una Fruta</h3>
          <p>Los personajes <strong>no empiezan con Pacto Primal.</strong> Cinco métodos:</p>
          <ol>
            <li><strong>Evento aleatorio Mundo Vivo (FO-01):</strong> 3% por región por ciclo.</li>
            <li><strong>Mercado negro:</strong> 100M-5,000M rupias. Requiere contactos de Inframundo.</li>
            <li><strong>Derrotar a un usuario:</strong> Debes vencer y matar cerca de una fruta normal.</li>
            <li><strong>Misiones de exploración:</strong> Ruinas, islas vírgenes.</li>
            <li><strong>Compra con PL:</strong> 3 PL garantizan la fruta en tu camino.</li>
          </ol>

          <h3>Formas Zoan</h3>
          <table class="guide-table">
            <thead><tr><th>Forma</th><th>PA</th><th>Efecto en Stats</th></tr></thead>
            <tbody>
              <tr><td>Humana</td><td>0</td><td>Stats normales.</td></tr>
              <tr><td>Híbrida</td><td>2</td><td>+2 FUE, +2 VIG, -1 AGI.</td></tr>
              <tr><td>Bestia</td><td>2</td><td>+3 FUE, +3 VIG, +1 AGI (si rápido), -2 INT, -2 CAR.</td></tr>
            </tbody>
          </table>

          <h3>Logia — Intangibilidad</h3>
          <p>Inmune a ataques físicos sin Haki. Transformación refleja (pasiva). Mantener la transformación cuesta 2 EN por ronda.</p>

          <h3>El Despertar (Awakening)</h3>
          <p><strong>Requisitos:</strong> 150 usos acumulados de cartas [Akuma], Rango S (stats 39+), CTR A (6). Coste: 2 PL (o 0 PL por crisis épica).</p>
          <table class="guide-table">
            <thead><tr><th>Tipo</th><th>Efecto del Despertar</th></tr></thead>
            <tbody>
              <tr><td><strong>Paramecia</strong></td><td>+1d6 daño en todas las cartas. Afectar el entorno.</td></tr>
              <tr><td><strong>Zoan Normal</strong></td><td>Forma híbrida mejorada. Regeneración 5 PV/turno.</td></tr>
              <tr><td><strong>Zoan Mitica</strong></td><td>Poder especial duplicado. Forma Bestia legendaria.</td></tr>
              <tr><td><strong>Logia</strong></td><td>Cambio climático permanente (10 km). Ataques [Área] sin coste.</td></tr>
            </tbody>
          </table>

          <h3>Desventajas Universales</h3>
          <ul>
            <li><strong>Agua de mar:</strong> 4 niveles de inmersión. Nivel 4 = stats 0, ahogo en 3 turnos.</li>
            <li><strong>Sello de Energía:</strong> Contacto prolongado anula poderes.</li>
            <li><strong>No puedes comer dos frutas:</strong> Muerte instantánea.</li>
            <li><strong>Nunca puedes nadar:</strong> Permanente e irreversible.</li>
          </ul>

          <h3>Fruta Custom</h3>
          <p>Puedes diseñar tu propia fruta y enviarla al staff para aprobación. Prohibidos: viajes en el tiempo, control mental total, resurrección, omnisciencia, robo de frutas, inmortalidad.</p>
        </div>

        <!-- 20 · Recompensas -->
        <div class="guide-content" id="g-recompensas">
          <div class="g-title">Recompensas</div>
          <div class="g-sub">// premios por actividad</div>
          <p>Sistema de recompensas automáticas por constancia y calidad de rol. Tres capas: diaria, semanal y de temporada.</p>

          <h3>La Racha (Diaria — cada 48h)</h3>
          <p>Cada 48 horas, si has posteado al menos 1 vez, ganas PP + rupias y tu racha sube. La racha llega hasta 30 (60 días de actividad continua) con premios crecientes.</p>
          <table class="guide-table">
            <thead><tr><th>Racha</th><th>Días</th><th>PP</th><th>Rupias</th><th>Premio Especial</th></tr></thead>
            <tbody>
              <tr><td>7</td><td>14</td><td>8</td><td>100K</td><td>Cofre Celeste</td></tr>
              <tr><td>14</td><td>28</td><td>12</td><td>200K</td><td>Cofre Primal</td></tr>
              <tr><td>21</td><td>42</td><td>18</td><td>300K</td><td>Cofre de Plata</td></tr>
              <tr><td>28</td><td>56</td><td>22</td><td>600K</td><td>Cofre Áureo</td></tr>
              <tr><td>30</td><td>60</td><td>25</td><td>1M</td><td><strong>+1 PL</strong></td></tr>
            </tbody>
          </table>

          <h3>El Jornal (Semanal — cada lunes)</h3>
          <table class="guide-table">
            <thead><tr><th>Posts esta semana</th><th>PP base</th><th>Rupias</th><th>Bonus</th></tr></thead>
            <tbody>
              <tr><td>1-2 posts</td><td>5</td><td>25K</td><td>—</td></tr>
              <tr><td>6-10 posts</td><td>15</td><td>100K</td><td>+3 REP</td></tr>
              <tr><td>16-20 posts</td><td>25</td><td>350K</td><td>+7 REP + 10% prob. PL</td></tr>
              <tr><td>21+ posts</td><td>30</td><td>500K</td><td>+10 REP + 25% prob. PL</td></tr>
            </tbody>
          </table>

          <h3>Hitos de Temporada (cada 90 días)</h3>
          <table class="guide-table">
            <thead><tr><th>Jornales completados</th><th>Recompensa</th></tr></thead>
            <tbody>
              <tr><td>8 jornales</td><td>Cofre Gigante + 20 PP + 5 REP</td></tr>
              <tr><td>12 jornales</td><td>Cofre Épico + 30 PP + 15 REP + <strong>1 PL</strong></td></tr>
              <tr><td>13 jornales (máximo)</td><td>Cofre Legendario + 50 PP + 20 REP + <strong>2 PL</strong></td></tr>
            </tbody>
          </table>

          <h3>Modo Vacaciones</h3>
          <p>Puedes congelar tu racha hasta 14 días por activación (2 usos por temporada, 28 días totales). Debes activarlo ANTES de irte.</p>

          <h3>Premios del Narrador</h3>
          <p>El staff puede otorgar bonus por posts excepcionales: "Buen rol" (+5 PP), "Excelente" (+10 PP, +3 REP) o "Épico" (+20 PP, +5 REP, +1 PL).</p>

          <div class="guide-note"><strong>La constancia tiene valor.</strong> Un personaje que rolea regularmente progresa tanto como el que derrota a un Yonko.</div>
        </div>

        <!-- 21 · Eventos comunitarios -->
        <div class="guide-content" id="g-eventos">
          <div class="g-title">Eventos comunitarios</div>
          <div class="g-sub">// torneos, cazas y guerras</div>
          <p>Los eventos comunitarios reúnen a múltiples jugadores en un mismo espacio narrativo. De 1 a 4 eventos por temporada (90 días).</p>

          <h3>Tipos de Evento</h3>
          <table class="guide-table">
            <thead><tr><th>Tipo</th><th>Descripción</th><th>Participantes</th></tr></thead>
            <tbody>
              <tr><td><strong>Torneo de Combate</strong></td><td>Eliminatorias 1v1 o 2v2.</td><td>8-32 jugadores</td></tr>
              <tr><td><strong>Caza del Tesoro</strong></td><td>Pistas escondidas en el foro.</td><td>Ilimitado</td></tr>
              <tr><td><strong>Feria / Festival</strong></td><td>Rol social. Juegos, concursos.</td><td>Ilimitado</td></tr>
              <tr><td><strong>Guerra de Facciones</strong></td><td>Evento PvP masivo por un objetivo.</td><td>Por facción</td></tr>
              <tr><td><strong>Subasta Legendaria</strong></td><td>Inframundo. Objetos únicos.</td><td>Inframundo + invitados</td></tr>
              <tr><td><strong>Asedio</strong></td><td>Una isla es atacada.</td><td>Ilimitado</td></tr>
            </tbody>
          </table>

          <h3>Premios de Evento (Estándar)</h3>
          <table class="guide-table">
            <thead><tr><th>Evento</th><th>1er Lugar</th><th>Participación</th></tr></thead>
            <tbody>
              <tr><td>Torneo de Combate</td><td>50 PP + 500K + Título "Campeón"</td><td>10 PP + 50K</td></tr>
              <tr><td>Caza del Tesoro</td><td>40 PP + Tesoro</td><td>5 PP</td></tr>
              <tr><td>Guerra de Facciones</td><td>60 PP + Territorio/Objetivo</td><td>15 PP</td></tr>
              <tr><td>Asedio</td><td>40 PP + Botín de guerra</td><td>10 PP + 50K</td></tr>
            </tbody>
          </table>

          <h3>Cómo Proponer un Evento</h3>
          <p>Los jugadores pueden proponer sus propios eventos. Envían una propuesta al staff con: tipo, fecha, duración, ubicación, premios, reglas especiales y descripción narrativa. Si se aprueba, el jugador se convierte en <strong>co-organizador</strong> junto al staff.</p>

          <h3>Impacto en Mundo Vivo</h3>
          <p>Los eventos generan PE 4-8. La Crónica del Mundo cubre los resultados. Las guerras de facciones pueden cambiar el control territorial. Los torneos dan fama al campeón.</p>
        </div>

        <!-- 22 · Temas de bienvenida -->
        <div class="guide-content" id="g-bienvenida">
          <div class="g-title">Temas de bienvenida</div>
          <div class="g-sub">// tu primera escena</div>
          <p>Cuando creas tu primer personaje, el sistema genera automáticamente un <strong>tema de bienvenida</strong> en tu isla inicial. Es tu puerta de entrada al mundo.</p>

          <h3>Qué Incluye</h3>
          <ol>
            <li><strong>Una escena inicial:</strong> Clima, ambiente, sonidos, olores y NPCs presentes en la isla.</li>
            <li><strong>Un gancho narrativo:</strong> Un conflicto menor, una petición de ayuda o un misterio local. Diseñado para resolverse en pocos posts.</li>
            <li><strong>Espacio para tu primer post:</strong> Solo tienes que responder con la reacción de tu personaje.</li>
          </ol>

          <h3>Flujo</h3>
          <ol>
            <li>Creación de personaje → Ficha enviada a revisión.</li>
            <li>Staff aprueba la ficha → SISTEMA genera tema de bienvenida (automático).</li>
            <li>El tema se crea en la zona abierta del mar correspondiente.</li>
            <li>El SISTEMA publica el primer post con la escena y el gancho.</li>
            <li>Tú posteas tu respuesta. ¡A rolear!</li>
          </ol>

          <h3>Islas Iniciales</h3>
          <table class="guide-table">
            <thead><tr><th>Isla</th><th>Mar</th><th>Ambiente</th></tr></thead>
            <tbody>
              <tr><td><strong>Toroa</strong></td><td>East Blue</td><td>Isla tranquila de pescadores. Ideal para novatos.</td></tr>
              <tr><td><strong>Portgrave</strong></td><td>West Blue</td><td>Ciudad portuaria con tradición periodística.</td></tr>
              <tr><td><strong>Loguetown</strong></td><td>East Blue</td><td>Ciudad del principio y del final. Ejecución de Roger.</td></tr>
              <tr><td><strong>Sabaody</strong></td><td>Grand Line</td><td>Archipiélago de burbujas. Aventura pura.</td></tr>
              <tr><td><strong>Water 7</strong></td><td>Grand Line</td><td>Ciudad del agua. Astilleros y carpinteros.</td></tr>
            </tbody>
          </table>

          <h3>¿Y si no respondes?</h3>
          <p>Si pasan 7 días sin respuesta, el tema se cierra automáticamente. <strong>Sin penalización.</strong> Puedes solicitar uno nuevo (máximo 3 por personaje).</p>
        </div>

        <!-- 23 · Ejemplo de combate -->
        <div class="guide-content" id="g-ej-combate">
          <div class="g-title">Ejemplo de combate</div>
          <div class="g-sub">// todas las reglas en acción</div>
          <p>Combate completo en el muelle de Loguetown que demuestra <strong>todas las reglas especiales</strong>: Logia, Haki, agua, Kairōseki, raza, cartas con tags y combate en grupo.</p>

          <h3>La Escena</h3>
          <p><strong>Bando Marine:</strong> Kaida Ishida — Almirante (S), Skypiean, Goro Goro no Mi (Logia), Busoshoku Intermedio. Capitan Renard (B), Busoshoku y Kenbunshoku Básicos. 4 Marines rasos.</p>
          <p><strong>Bando Pirata:</strong> Garran "Puño de Piedra" — Supernova (A), Busoshoku Intermedio. Taro — Pirata (C), Gyojin puro, Gyojin Karate. Nyx — Pirata (B), Kage Kage no Mi (Paramecia, NO es Logia).</p>
          <p>Los piratas intentan huir hacia su barco. Kaida y Renard les cortan el paso a metros del agua.</p>

          <h3>Ronda 1</h3>
          <p><strong>Kaida:</strong> Activa Goro Goro (Logia, 1 PA). Tormenta de Mil Rayos (Tier III, 2d8+AGI, [Área] [Paralizado]). 16 de daño en área. Los Marines rasos reciben daño completo (sin Haki). Nyx (Paramecia, NO Logia) también recibe. Garran activa Busoshoku (reduce daño a la mitad).</p>
          <p><strong>Garran:</strong> Emisión: Onda de Choque (Tier III, 2d8+VOL, [Haki]) contra Kaida. <strong>Con Haki, golpea a la Logia.</strong> 16 de daño. Kaida queda [Aturdida].</p>
          <p><strong>Renard:</strong> Corte de la Justicia (Tier II, [Haki] [Sangrado]) contra Nyx. Nyx bloquea parcialmente con sombras.</p>
          <p><strong>Taro:</strong> [Paralizado] por el ataque de Kaida. Gasta el turno en liberarse.</p>
          <p><strong>Resultado R1:</strong> Kaida 49/65 PV. Garran 63/71. Renard 48/56. Nyx 29/48. Taro 41/57.</p>

          <h3>Ronda 2 — El Agua Entra en Juego</h3>
          <p><strong>Taro:</strong> Se tira al agua (como Gyojin, sin penalización, +1 FUE). Activa Puño de Agua (Tier I, [Agua] [Empujado]) contra Kaida. Kaida esquiva pero queda al borde del muelle.</p>
          <p><strong>Nyx:</strong> Usa Látigo de Sombras de forma creativa: en lugar de dañar, <strong>empuja</strong>. Kaida cae al agua. <strong>Inmersión Nivel 4:</strong> Stats = 0, ahogo en 3 turnos.</p>
          <p><strong>Renard:</strong> Contraataca con <strong>esposas de Kairōseki</strong> contra Garran (sin Fruta que anular, pero daño contundente).</p>
          <p><strong>Marines rasos:</strong> Se tiran al agua a rescatar a Kaida.</p>

          <h3>Ronda 3 — Rescate y Retirada</h3>
          <p>Los Marines rescatan a Kaida del agua. Los piratas huyen a su barco. Renard, herido y con Kaida debilitada, no persigue. <strong>Victoria pirata parcial:</strong> escapan con vida.</p>

          <h3>Reglas Demostradas</h3>
          <table class="guide-table">
            <thead><tr><th>Regla</th><th>Dónde se vio</th></tr></thead>
            <tbody>
              <tr><td>Logia: intangible sin Haki, tocable con Haki</td><td>Garran golpea a Kaida con Busoshoku (R1).</td></tr>
              <tr><td>Paramecia NO es Logia</td><td>Nyx recibe daño normal (R1).</td></tr>
              <tr><td>Agua: 4 niveles de inmersión</td><td>Kaida Nivel 4, stats=0, ahogo en 3 turnos (R2).</td></tr>
              <tr><td>Tag [Agua] empapa pero no es inmersión</td><td>Taro usa Puño de Agua (R2).</td></tr>
              <tr><td>Kairōseki como arma</td><td>Renard golpea con esposas (R2).</td></tr>
              <tr><td>Raza Gyojin en agua (+1 FUE)</td><td>Taro entra al agua sin penalización (R2).</td></tr>
              <tr><td>Uso creativo de técnicas</td><td>Nyx usa Látigo de Sombras para empujar (R2).</td></tr>
              <tr><td>Estados alterados</td><td>[Paralizado], [Aturdido], [Derribado], [Empujado].</td></tr>
            </tbody>
          </table>
        </div>

        <!-- 24 · Normativa -->
        <div class="guide-content" id="g-normativa">
          <div class="g-title">Normativa</div>
          <div class="g-sub">// reglas del foro</div>
          <p>Reglamento completo de conducta, rol y sistema disciplinario. Todo usuario acepta implícitamente estas normas al participar.</p>

          <h3>Normas Off-Rol</h3>
          <ul>
            <li><strong>Respeto obligatorio:</strong> Trata a todos con cortesía. Sin insultos ni ataques personales.</li>
            <li><strong>Temas prohibidos:</strong> Política real, religión real, contenido NSFW inapropiado.</li>
            <li><strong>Tolerancia cero:</strong> Acoso, discriminación, amenazas o apología de la violencia = sanción inmediata.</li>
            <li><strong>Uso de IA:</strong> Prohibido generar posts de rol, diálogos o trasfondo con IA. Sí se permite corrección ortográfica.</li>
          </ul>

          <h3>Normas On-Rol</h3>
          <ul>
            <li><strong>Extensión mínima:</strong> 300 palabras por post.</li>
            <li><strong>Idioma:</strong> Español. Traducciones y frases cortas en japonés aceptadas.</li>
            <li><strong>Godmodeo:</strong> Prohibido controlar el personaje de otro sin su consentimiento.</li>
            <li><strong>Metarol:</strong> Prohibido usar información off-rol que tu personaje no conoce.</li>
            <li><strong>Cartas de técnica:</strong> Deben estar aprobadas por el staff antes de usarse en combate.</li>
            <li><strong>Multicuentas:</strong> Máximo 2 personajes por usuario. No pueden interactuar entre sí ni estar en la misma facción.</li>
          </ul>

          <h3>Invasiones (PvP)</h3>
          <p>Requieren ticket de staff, justificación narrativa y disponibilidad de DJ. <strong>Periodo de Gracia PvP:</strong> 15 días como jugador nuevo sin poder ser objetivo de invasión.</p>

          <h3>Sistema de Strikes</h3>
          <table class="guide-table">
            <thead><tr><th>Strikes</th><th>Consecuencia</th></tr></thead>
            <tbody>
              <tr><td>1 Strike</td><td>Aviso formal registrado.</td></tr>
              <tr><td>2 Strikes</td><td>Revisión del staff. Posible ban temporal.</td></tr>
              <tr><td>3 Strikes</td><td>Ban temporal automático (mínimo 7 días).</td></tr>
            </tbody>
          </table>
          <p>Los strikes caducan a los <strong>30 días</strong> sin reincidir. Existe proceso de apelación (7 días para presentarla, 7 días para resolución).</p>

          <div class="guide-note"><strong>Fair Play:</strong> Respeta los resultados de las tiradas aunque sean desfavorables. No discutas decisiones del DJ en mitad del combate. Felicita al oponente tras un buen combate.</div>
        </div>

        <!-- 25 · Inicio rápido -->
        <div class="guide-content" id="g-inicio-rapido">
          <div class="g-title">Inicio rápido</div>
          <div class="g-sub">// empieza en 2 minutos</div>

          <h3>Granblue Fantasy: Eternal en 2 Minutos</h3>
          <p>Una gran tormenta de éter agita los Skydoms y el Imperio de Erste extiende su influencia por todo el cielo. <strong>Tus acciones cambian el cielo.</strong> Cada 15 días publicamos una Crónica del Mundo. Tu personaje puede salir en ella.</p>

          <h3>Crear tu Personaje (30 segundos)</h3>
          <p><strong>Elige una raza:</strong> Humano, Erune, Draph o Harvin.</p>
          <p><strong>Elige una facción:</strong> Skyfarer, Ejército Imperial, La Sociedad, Gremio de Cazadores o Civil.</p>
          <p><strong>Asigna stats:</strong> Elige un arquetipo (Guerrero→FUE+VIG, Tirador→DES+PUN, Espadachín→FUE+DES, etc.).</p>
          <p><strong>Dale un nombre y una frase de historia.</strong> ¡Ya tienes personaje!</p>

          <h3>¿Qué Puedo Hacer desde mi Primer Post?</h3>
          <ol>
            <li><strong>Llegar a una isla y explorar</strong> — Narras tu llegada. El mundo reacciona.</li>
            <li><strong>Entrar en una taberna y hablar</strong> — El 90% de las aventuras empiezan con una jarra.</li>
            <li><strong>Buscar un tablón de misiones</strong> — Cada isla tiene misiones públicas.</li>
            <li><strong>Unirte a una tripulación</strong> — Publica en "Busco tripulación".</li>
            <li><strong>Entrenar tu primer oficio</strong> — Herrero, cocinero, carpintero, médico...</li>
            <li><strong>Empezar una pelea de taberna</strong> — Con reglas de AV-01 Combate.</li>
          </ol>

          <h3>Cómo Funciona un Post</h3>
          <p>Escribes lo que hace tu personaje. Otro jugador o un Narrador responde. Mínimo <strong>300 palabras</strong>. En español. No controles personajes ajenos. Describe: el olor del mar, cómo se mueve tu personaje, qué siente.</p>

          <h3>Tu Primer Día — 5 Pasos</h3>
          <ol>
            <li><strong>Crear tu ficha</strong> en el Panel de Usuario.</li>
            <li><strong>Presentarte</strong> en el tema de Bienvenida.</li>
            <li><strong>Elegir tu isla inicial</strong> (Toroa-East Blue, Portgrave-West Blue o Sabaody-Grand Line).</li>
            <li><strong>Escribir tu primer post</strong> en la zona abierta correspondiente.</li>
            <li><strong>Reclamar tu primera recompensa de racha</strong> tras 48 horas.</li>
          </ol>

          <h3>Si Quiero Más</h3>
          <table class="guide-table">
            <thead><tr><th>Si quieres...</th><th>Lee...</th></tr></thead>
            <tbody>
              <tr><td>Pelear con reglas completas</td><td>AV-01 — Combate</td></tr>
              <tr><td>Viajar entre islas</td><td>AV-02 — Viajes</td></tr>
              <tr><td>Unirte a una facción</td><td>AV-05 — Facciones</td></tr>
              <tr><td>Conseguir un Pacto Primal</td><td>AV-14 — Pactos Primles</td></tr>
              <tr><td>Ganar recompensas diarias</td><td>AV-16 — Recompensas</td></tr>
              <tr><td>Crear o unirte a una tripulación</td><td>AV-06 — Grupos y Bases</td></tr>
            </tbody>
          </table>

          <div class="guide-note"><strong>Lo importante ya lo has hecho si estás aquí.</strong> El resto es ir descubriendo el mundo poco a poco, a tu ritmo, sin prisa. Bienvenido a los mares, navegante.</div>
        </div>

      </div><!-- /guide-main -->

    </div><!-- /guide-shell -->

  </section>

</div><!-- /wrap -->

<?php include __DIR__ . '/inc/footer_custom.php'; ?>

<script>
(function(){
  var navItems = document.querySelectorAll('.guide-nav-item');
  var contents = document.querySelectorAll('.guide-content');
  var filts = document.querySelectorAll('.filt');
  var main = document.querySelector('.guide-main');
  var currentCat = 'all';

  function showGuide(id) {
    navItems.forEach(function(b){ b.classList.remove('active'); });
    contents.forEach(function(c){ c.classList.remove('active'); });
    var btn = document.querySelector('[data-guide="' + id + '"]');
    var panel = document.getElementById('g-' + id);
    if (btn) btn.classList.add('active');
    if (panel) panel.classList.add('active');
    if (main) main.scrollTop = 0;
    if (window.history) {
      history.replaceState(null, '', '?guia=' + id);
    }
  }

  function applyFilter(cat) {
    currentCat = cat;
    var firstVisible = null;
    navItems.forEach(function(item){
      var match = cat === 'all' || item.dataset.cat === cat;
      item.classList.toggle('hidden', !match);
      if (match && !firstVisible) firstVisible = item;
    });
    var sections = document.querySelectorAll('.guide-nav .nav-section');
    sections.forEach(function(s){
      var next = s.nextElementSibling;
      var hasVisible = false;
      while (next && !next.classList.contains('nav-section')) {
        if (!next.classList.contains('hidden')) { hasVisible = true; break; }
        next = next.nextElementSibling;
      }
      s.style.display = hasVisible ? '' : 'none';
    });
    var active = document.querySelector('.guide-nav-item.active');
    if (!active || active.classList.contains('hidden')) {
      if (firstVisible) showGuide(firstVisible.dataset.guide);
    }
  }

  navItems.forEach(function(btn){
    btn.addEventListener('click', function(){
      var id = btn.dataset.guide;
      if (id) showGuide(id);
    });
  });

  filts.forEach(function(b){
    b.addEventListener('click', function(){
      filts.forEach(function(x){ x.setAttribute('aria-pressed', 'false'); });
      b.setAttribute('aria-pressed', 'true');
      applyFilter(b.dataset.cat);
    });
  });

  var urlParams = new URLSearchParams(window.location.search);
  var guiaParam = urlParams.get('guia');
  if (guiaParam && document.getElementById('g-' + guiaParam)) {
    showGuide(guiaParam);
  }

  if ('IntersectionObserver' in window && !matchMedia('(prefers-reduced-motion:reduce)').matches) {
    var io = new IntersectionObserver(function(es){ es.forEach(function(e){
      if (e.isIntersecting) { e.target.classList.add('vis'); io.unobserve(e.target); }
    }); }, { threshold: .08 });
    document.querySelectorAll('.reveal').forEach(function(el){ io.observe(el); });
  } else {
    document.querySelectorAll('.reveal').forEach(function(el){ el.classList.add('vis'); });
  }
})();
</script>
</body>
</html>

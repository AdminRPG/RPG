<?php
/**
 * One Piece: Eternal · Zona Staff (hub)
 * Skeleton — cards se agregan una a una.
 */
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'zona-staff.php');
require_once './global.php';
require_once MYBB_ROOT . 'inc/ope_rol/core/data.php';

$bburl  = htmlspecialchars_uni($mybb->settings['bburl']);
$bbname = htmlspecialchars_uni($mybb->settings['bbname']);
$uid    = (int) ($mybb->user['uid'] ?? 0);

$staff = $uid > 0
    ? ope_rol_active_staff($uid)
    : array('pid' => 0, 'rol' => '', 'narrador' => 0, 'rank' => 0, 'is_staff' => false, 'nombre' => '');
$is_staff   = !empty($staff['is_staff']);
$staff_rank = (int) ($staff['rank'] ?? 0);
$rol_lbl    = ope_rol_staff_label($staff['rol']);
$char_name  = htmlspecialchars_uni((string) $staff['nombre']);
$mi_rango   = $rol_lbl !== '' ? $rol_lbl : 'Sin rango';

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> · Zona Staff</title>
<?php echo ope_rol_head_base(); ?>
</head>
<body class="ope-pg-zona-staff">
<?php echo ope_rol_navbar_html(); ?>
<div class="breadcrumb"><div class="breadcrumb-in">
  <a href="<?php echo $bburl; ?>/index.php">Inicio</a><span class="sep">›</span><b>Zona Staff</b>
</div></div>
<div class="wrap">
  <section class="reveal">
    <div class="shead">
      <h1>Zona Staff</h1>
      <span class="code">// paneles</span>
      <span class="rule"></span>
    </div>
  </section>
<?php if (!$is_staff): ?>
  <section class="reveal">
    <div class="plate">
      <div class="plate-h"><span class="t">Acceso restringido</span><span class="c">// solo staff</span></div>
      <div class="plate-b">
        <div class="noperm">
          <div class="lock">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
          </div>
          <div class="big">No tienes acceso a la Zona Staff</div>
          <p>Reservado al equipo del foro. La cuenta Admin MyBB tiene acceso aunque no tenga personaje activo.</p>
          <a href="<?php echo $bburl; ?>/index.php" class="btn btn-ghost">Volver al inicio</a>
        </div>
      </div>
    </div>
  </section>
<?php else: ?>
  <section class="reveal">
    <p class="zs-intro">Herramientas del equipo. Cada panel exige un <b>rank mínimo</b>; solo ves las cards a las que puedes acceder.</p>
    <div class="zs-bar">
      <span class="zs-level">Activo: <b><?php echo $char_name !== '' ? $char_name : 'Admin (sin PJ)'; ?></b> · rol: <b><?php echo htmlspecialchars_uni($mi_rango); ?></b> · rank <?php echo (int) $staff_rank; ?></span>
    </div>
  </section>
  <section class="reveal">
    <div class="zs-grid">
      <div class="zs-card">
        <div class="zs-card-h"><span class="t">Bandeja de trámites</span><span class="c">motor 5.21</span></div>
        <p class="zs-card-d">La cola del motor: 67 trámites. La IA propone, tú firmas con motivo. Todo el flujo editable y auditable.</p>
        <a href="<?php echo $bburl; ?>/bandeja.php" class="btn btn-ghost btn-sm">Abrir bandeja</a>
      </div>
      <div class="zs-card">
        <div class="zs-card-h"><span class="t">Resolución de combate</span><span class="c">F2</span></div>
        <p class="zs-card-d">Turnos de cada combate (PA vs. gastado), Tablas 1–3, matices y veredicto firmado.</p>
        <a href="<?php echo $bburl; ?>/resolucion-combate.php" class="btn btn-ghost btn-sm">Abrir panel</a>
      </div>
      <div class="zs-card">
        <div class="zs-card-h"><span class="t">Calendario del foro</span><span class="c">A.3</span></div>
        <p class="zs-card-d">Fecha on-roll, presentes con ancla, congelados y avisos de coherencia.</p>
        <a href="<?php echo $bburl; ?>/calendario-staff.php" class="btn btn-ghost btn-sm">Abrir panel</a>
      </div>
      <div class="zs-card">
        <div class="zs-card-h"><span class="t">Progresión</span><span class="c">A.3</span></div>
        <p class="zs-card-d">Cronómetros de entrenamiento, gastos de PP por concepto, saldos y reservas.</p>
        <a href="<?php echo $bburl; ?>/progresion-staff.php" class="btn btn-ghost btn-sm">Abrir panel</a>
      </div>
      <div class="zs-card">
        <div class="zs-card-h"><span class="t">Mundo Vivo</span><span class="c">A.3</span></div>
        <p class="zs-card-d">Ronda mensual, cola de análisis y la matriz de las 17 islas con su ficha viva.</p>
        <a href="<?php echo $bburl; ?>/mundo-vivo.php" class="btn btn-ghost btn-sm">Abrir panel</a>
      </div>
      <div class="zs-card">
        <div class="zs-card-h"><span class="t">Navegación</span><span class="c">A.3</span></div>
        <p class="zs-card-d">Travesías en curso con ruta, plazo, vencimientos y víveres pendientes.</p>
        <a href="<?php echo $bburl; ?>/navegacion-staff.php" class="btn btn-ghost btn-sm">Abrir panel</a>
      </div>
      <div class="zs-card">
        <div class="zs-card-h"><span class="t">Facciones</span><span class="c">A.3</span></div>
        <p class="zs-card-d">Rangos y cupos, ascensos en cola, Shichibukai e histórico de cambios.</p>
        <a href="<?php echo $bburl; ?>/facciones-staff.php" class="btn btn-ghost btn-sm">Abrir panel</a>
      </div>
      <div class="zs-card">
        <div class="zs-card-h"><span class="t">Conquista</span><span class="c">A.3</span></div>
        <p class="zs-card-d">Fases del asedio, ejércitos (unidades/hordas) y registro con motivo.</p>
        <a href="<?php echo $bburl; ?>/conquista-staff.php" class="btn btn-ghost btn-sm">Abrir panel</a>
      </div>
      <div class="zs-card">
        <div class="zs-card-h"><span class="t">Barcos</span><span class="c">A.3</span></div>
        <p class="zs-card-d">Flota por jugador, daños (18.7) y módulos del astillero (18.6).</p>
        <a href="<?php echo $bburl; ?>/barcos-staff.php" class="btn btn-ghost btn-sm">Abrir panel</a>
      </div>
      <div class="zs-card">
        <div class="zs-card-h"><span class="t">Akumas y Haki</span><span class="c">A.3</span></div>
        <p class="zs-card-d">Cupos mundiales de frutas, pool de la tirada y Conquistador (sucesos en borrador).</p>
        <a href="<?php echo $bburl; ?>/akumas-staff.php" class="btn btn-ghost btn-sm">Abrir panel</a>
      </div>
      <div class="zs-card">
        <div class="zs-card-h"><span class="t">Narradores y Misiones</span><span class="c">A.3</span></div>
        <p class="zs-card-d">Tablón de misiones (ficha de 6 bloques), auto-narradas por rondas y cupo de narradores.</p>
        <a href="<?php echo $bburl; ?>/misiones-staff.php" class="btn btn-ghost btn-sm">Abrir panel</a>
      </div>
    </div>
    <div class="zs-card reveal">
      <div class="zs-card-h"><span class="t">Tripulaciones</span><span class="c">A.3</span></div>
      <p class="zs-card-d">Fichas de banda (cofre común, plazas del barco), avisos de disolución y histórico auditable.</p>
      <a href="<?php echo $bburl; ?>/tripulaciones-staff.php" class="btn btn-ghost btn-sm">Abrir panel</a>
    </div>
    <div class="zs-card reveal">
      <div class="zs-card-h"><span class="t">Cibernética</span><span class="c">A.3</span></div>
      <p class="zs-card-d">Implantes por zona/nivel (requisitos acumulativos, balanza a 0), mantenimientos por ronda y histórico.</p>
      <a href="<?php echo $bburl; ?>/cibernetica-staff.php" class="btn btn-ghost btn-sm">Abrir panel</a>
    </div>
    <div class="zs-card reveal">
      <div class="zs-card-h"><span class="t">Familias Legendarias</span><span class="c">A.3</span></div>
      <p class="zs-card-d">Los 3 linajes con cupo mundial, portadores y bandeja de concesión/revocación (60–61).</p>
      <a href="<?php echo $bburl; ?>/familias-staff.php" class="btn btn-ghost btn-sm">Abrir panel</a>
    </div>
    <div class="zs-card reveal">
      <div class="zs-card-h"><span class="t">Bajo Mundo</span><span class="c">A.3</span></div>
      <p class="zs-card-d">Rumores por isla (veracidad solo-staff), redes y espías, carteles con caducidad de paradero y el histórico de operaciones (25–33).</p>
      <a href="<?php echo $bburl; ?>/bajomundo-staff.php" class="btn btn-ghost btn-sm">Abrir panel</a>
    </div>
    <div class="zs-card reveal">
      <div class="zs-card-h"><span class="t">Mercado / Economía</span><span class="c">A.3</span></div>
      <p class="zs-card-d">Fluctuación de precios por zona y ronda con motivo, carteras (robable/bóveda) y transacciones (10.1–10.5).</p>
      <a href="<?php echo $bburl; ?>/mercado-staff.php" class="btn btn-ghost btn-sm">Abrir panel</a>
    </div>
    <div class="zs-card reveal">
      <div class="zs-card-h"><span class="t">NPCs</span><span class="c">A.3</span></div>
      <p class="zs-card-d">Primarios con capa oculta solo-staff, bestiario y apariciones por tema (incluido «reclutado», 12.5).</p>
      <a href="<?php echo $bburl; ?>/npc-staff.php" class="btn btn-ghost btn-sm">Abrir panel</a>
    </div>
    <div class="zs-card reveal">
      <div class="zs-card-h"><span class="t">Reliquias</span><span class="c">A.3</span></div>
      <p class="zs-card-d">Fichas muertas con su leyenda e histórico de muertes con calidad y herencia (5.21-bis).</p>
      <a href="<?php echo $bburl; ?>/reliquias-staff.php" class="btn btn-ghost btn-sm">Abrir panel</a>
    </div>
    <!-- Agregar cards aquí -->
<?php endif; ?>
</div>
<?php include __DIR__ . '/inc/footer_custom.php'; ?>
<script>
if ('IntersectionObserver' in window && !matchMedia('(prefers-reduced-motion:reduce)').matches) {
  const io = new IntersectionObserver(es => es.forEach(e => {
    if (e.isIntersecting) { e.target.classList.add('vis'); io.unobserve(e.target); }
  }), { threshold: .08 });
  document.querySelectorAll('.reveal').forEach(el => io.observe(el));
} else {
  document.querySelectorAll('.reveal').forEach(el => el.classList.add('vis'));
}
</script>
</body>
</html>

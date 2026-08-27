<?php
/**
 * One Piece: Eternal · Zona Staff (hub)
 * Skeleton — cards se agregan una a una.
 */
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'zona-staff.php');
require_once './global.php';
require_once MYBB_ROOT . 'inc/ope_rol_data.php';

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
    <div class="plate">
      <div class="plate-h"><span class="t">Resolución de combate</span><span class="c">// F2 · veredictos y bandas de delta</span></div>
      <div class="plate-b">
        <p class="zs-intro">Revisa los turnos de cada combate (PA total vs. gastado), resuelve intercambio a intercambio con las Tablas 1–3, ajusta matices y firma el veredicto con histórico.</p>
        <a href="<?php echo $bburl; ?>/resolucion-combate.php" class="btn btn-ghost">Abrir panel</a>
      </div>
    </div>
  </section>
  <section class="reveal">
    <div class="plate">
      <div class="plate-h"><span class="t">Calendario del foro</span><span class="c">// A.3 · fecha on-roll, presentes y coherencia</span></div>
      <div class="plate-b">
        <p class="zs-intro">Fecha on-roll actual con su histórico de avances, presentes activos con su ancla y jugadores congelados, histórico de aperturas/cierres y avisos de pasados incoherentes.</p>
        <a href="<?php echo $bburl; ?>/calendario-staff.php" class="btn btn-ghost">Abrir panel</a>
      </div>
    </div>
    <div class="plate">
      <div class="plate-h"><span class="t">Progresión</span><span class="c">// A.3 · cronómetros, subidas, gastos de PP, saldos y reservas</span></div>
      <div class="plate-b">
        <p class="zs-intro">Cronómetros de entrenamiento por jugador, saldos y reservas con progreso de nivel, gastos de PP por concepto (atributos, dominios, técnicas — libro <code>historico_pp</code>) e histórico reciente de movimientos.</p>
        <a href="<?php echo $bburl; ?>/progresion-staff.php" class="btn btn-ghost">Abrir panel</a>
      </div>
    </div>
    <div class="plate">
      <div class="plate-h"><span class="t">Mundo Vivo</span><span class="c">// A.3 · ronda mensual, cola de análisis y matriz de islas</span></div>
      <div class="plate-b">
        <p class="zs-intro">Ronda actual con su estado (abierta/análisis/cerrada), los temas presentes en la cola de análisis, y la matriz de las 17 islas con su ficha viva (peligrosidad, control, defensa, desarrollo, clima). La skill-mundo-vivo propone; aquí firmas antes de aplicar.</p>
        <a href="<?php echo $bburl; ?>/mundo-vivo.php" class="btn btn-ghost">Abrir panel</a>
      </div>
    </div>
    <div class="plate">
      <div class="plate-h"><span class="t">Navegación</span><span class="c">// A.3 · travesías activas, oráculos, vencimientos e histórico</span></div>
      <div class="plate-b">
        <p class="zs-intro">Travesías en curso por jugador con su ruta, plazo real y vencimiento, oráculos por tema, víveres pendientes al cierre y el histórico de resueltas/vencidas. La ficha del trámite 38 se edita y firma en la bandeja (skill-navegacion).</p>
        <a href="<?php echo $bburl; ?>/navegacion-staff.php" class="btn btn-ghost">Abrir panel</a>
      </div>
    </div>
    <div class="plate">
      <div class="plate-h"><span class="t">Facciones</span><span class="c">// A.3 · rangos y cupos, ascensos, élite e histórico</span></div>
      <div class="plate-b">
        <p class="zs-intro">Tablero de rangos y cupos por facción (cúspide con cupo), ascensos en cola del trámite 20 (la skill propone el termómetro, aquí firmas), subfacciones de élite (Shichibukai 7) e histórico inmutable de cambios, deserciones e infiltraciones.</p>
        <a href="<?php echo $bburl; ?>/facciones-staff.php" class="btn btn-ghost">Abrir panel</a>
      </div>
    </div>
    <div class="plate">
      <div class="plate-h"><span class="t">Conquista</span><span class="c">// A.3 · fases, ejércitos y registro con motivo</span></div>
      <div class="plate-b">
        <p class="zs-intro">Conquistas activas por isla con sus fases (anuncio → asedio → resolución → registro → ocupación), rondas requeridas según la fuerza defensiva (16.3), ejércitos del asedio (unidades/hordas, 16.7) e histórico de resueltas con motivo. Al registrar una conquista, las tiendas del anterior dueño se suspenden (16.6).</p>
        <a href="<?php echo $bburl; ?>/conquista-staff.php" class="btn btn-ghost">Abrir panel</a>
      </div>
    </div>
    <div class="plate">
      <div class="plate-h"><span class="t">Barcos</span><span class="c">// A.3 · flota, daños y astillero</span></div>
      <div class="plate-b">
        <p class="zs-intro">Flota por jugador con su ficha (tipo, nivel, madera, casco, módulos), estados de daño (18.7) y el catálogo de módulos del astillero (18.6). El barco no tiene PA propio ni progreso: las mejoras son módulos y madera, y la madera del casco marca el límite de mar (18.5).</p>
        <a href="<?php echo $bburl; ?>/barcos-staff.php" class="btn btn-ghost">Abrir panel</a>
      </div>
    </div>
  </section>
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

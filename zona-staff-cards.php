<?php
/**
 * One Piece: Eternal · Panel Staff: Catálogo de Cards (STF-05)
 * CRUD de tarjetas reutilizables + asignación a personajes.
 */
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'zona-staff-cards.php');
require_once './global.php';
require_once MYBB_ROOT . 'inc/ope_rol/bootstrap.php';

$bburl  = htmlspecialchars_uni($mybb->settings['bburl']);
$bbname = htmlspecialchars_uni($mybb->settings['bbname']);
$uid    = (int) ($mybb->user['uid'] ?? 0);

$staff = $uid > 0
    ? ope_rol_active_staff($uid)
    : array('pid' => 0, 'rol' => '', 'narrador' => 0, 'rank' => 0, 'is_staff' => false, 'nombre' => '');
$is_staff   = !empty($staff['is_staff']);
$staff_rank = (int) ($staff['rank'] ?? 0);

if (!$is_staff && $uid !== 1) {
    header('Location: ' . $bburl . '/zona-staff.php');
    exit;
}

// Asegurar tablas
ope_rol_cat_cards_setup();

$flash = '';
$flash_ok = false;

// Definir tipos y slots fuera del POST para tenerlos disponibles
$card_tipos = ope_rol_cat_card_tipos();
$card_slots = ope_rol_cat_pj_card_slots();

// POST Handling
if ($mybb->request_method === 'post') {
    if (!verify_post_check($mybb->get_input('my_post_key'), true)) {
        $flash = 'La sesión del formulario caducó. Inténtalo de nuevo.';
    } else {
        $action = $mybb->get_input('action');

        if ($action === 'crear_card' || $action === 'editar_card') {
            $nombre = trim((string) $mybb->get_input('nombre'));
            $card_id = (int) $mybb->get_input('card_id', MyBB::INPUT_INT);

            if ($nombre === '') {
                $flash = 'El nombre es obligatorio.';
            } else {
                // Fusionar PA/EN/nivel_min con JSON estadisticas
                $estats_raw = trim((string) $mybb->get_input('estadisticas'));
                $estats = $estats_raw !== '' ? json_decode($estats_raw, true) : array();
                if (!is_array($estats)) {
                    $estats = array();
                }
                $pa = (int) $mybb->get_input('pa', MyBB::INPUT_INT);
                $en = (int) $mybb->get_input('en', MyBB::INPUT_INT);
                $nm = (int) $mybb->get_input('nivel_min', MyBB::INPUT_INT);
                if ($pa > 0) { $estats['pa'] = $pa; }
                if ($en > 0) { $estats['en'] = $en; }
                if ($nm > 0) { $estats['nivel_min'] = $nm; }

                $data = array(
                    'nombre'       => $nombre,
                    'tipo'         => $mybb->get_input('tipo'),
                    'descripcion'  => $mybb->get_input('descripcion'),
                    'contenido'    => $mybb->get_input('contenido'),
                    'icono'        => $mybb->get_input('icono'),
                    'estadisticas' => json_encode($estats, JSON_UNESCAPED_UNICODE),
                    'activo'       => (int) $mybb->get_input('activo', MyBB::INPUT_INT),
                    'orden'        => (int) $mybb->get_input('orden', MyBB::INPUT_INT),
                );

                if ($action === 'crear_card') {
                    $res = ope_rol_cat_card_crear($data);
                } else {
                    if ($card_id < 1) {
                        $flash = 'ID de card inválido.';
                    } else {
                        $res = ope_rol_cat_card_editar($card_id, $data);
                    }
                }

                if (isset($res)) {
                    $flash = $res['msg'];
                    $flash_ok = $res['ok'];
                }
            }
        } elseif ($action === 'borrar_card') {
            $card_id = (int) $mybb->get_input('card_id', MyBB::INPUT_INT);
            $force = (int) $mybb->get_input('force', MyBB::INPUT_INT) === 1;
            $res = ope_rol_cat_card_borrar($card_id, $force);
            $flash = $res['msg'];
            $flash_ok = $res['ok'];
        } elseif ($action === 'asignar_card') {
            $pid = (int) $mybb->get_input('pid', MyBB::INPUT_INT);
            $card_id = (int) $mybb->get_input('card_id', MyBB::INPUT_INT);
            $slot = $mybb->get_input('slot');
            $res = ope_rol_cat_pj_card_asignar($pid, $card_id, $slot);
            $flash = $res['msg'];
            $flash_ok = $res['ok'];
        } elseif ($action === 'desasignar_card') {
            $asig_id = (int) $mybb->get_input('asig_id', MyBB::INPUT_INT);
            $res = ope_rol_cat_pj_card_desasignar($asig_id);
            $flash = $res['msg'];
            $flash_ok = $res['ok'];
        }
    }
}

// Cargar datos
$cards = ope_rol_cat_cards(false);
$personajes = ope_rol_cat_personajes_lista();

// Agrupar cards por tipo
$cards_por_tipo = array();
foreach ($cards as $c) {
    $t = $c['tipo'] ?? 'misc';
    if (!isset($cards_por_tipo[$t])) {
        $cards_por_tipo[$t] = array();
    }
    $cards_por_tipo[$t][] = $c;
}

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> · Catálogo de Cards</title>
<?php echo ope_rol_head_base(); ?>
</head>
<body class="ope-pg-zona-staff">
<?php echo ope_rol_navbar_html(); ?>
<div class="breadcrumb"><div class="breadcrumb-in">
  <a href="<?php echo $bburl; ?>/index.php">Inicio</a><span class="sep">›</span>
  <a href="<?php echo $bburl; ?>/zona-staff.php">Zona Staff</a><span class="sep">›</span>
  <b>Catálogo de Cards</b>
</div></div>

<div class="wrap">

  <section class="reveal">
    <div class="shead">
      <h1>Catálogo de Cards</h1>
      <span class="code">// panel de contenido STF-05</span>
      <span class="rule"></span>
    </div>
  </section>

<?php if ($flash !== ''): ?>
  <div class="flash <?php echo $flash_ok ? 'ok' : 'error'; ?>">
    <?php echo htmlspecialchars_uni($flash); ?>
  </div>
<?php endif; ?>

  <section class="reveal">
    <div class="zs-mgr-toolbar">
      <div class="zs-mgr-search">
        <input type="text" id="cardSearchInput" placeholder="Buscar card por nombre o tipo..." autocomplete="off">
      </div>
      <div>
        <button type="button" class="btn btn-hot btn-sm" onclick="openModal('createCardModal')">+ Nueva Card</button>
      </div>
    </div>

<?php if (empty($cards)): ?>
    <div class="zs-empty-state">
      <div class="zs-empty-state-h">No hay cards creadas todavía</div>
      <p class="zs-empty-state-p">Crea la primera técnica, pasiva o consumible para poblar el catálogo de cards.</p>
      <button type="button" class="btn btn-hot btn-sm" onclick="openModal('createCardModal')">+ Crear primera card</button>
    </div>
<?php else: ?>
    <div class="zs-pj-grid" id="cardGrid">
<?php foreach ($cards as $c):
    $tipos = $card_tipos;
    $tipo_lbl = $tipos[$c['tipo']] ?? ucfirst($c['tipo']);
    $desc_corta = truncate_html($c['descripcion'] ?? '', 120);
    $st = (int) ($c['activo'] ?? 1) === 1 ? 'activo' : 'inactivo';
    $json = htmlspecialchars_uni(json_encode($c, JSON_UNESCAPED_UNICODE));
    $icono = trim((string) ($c['icono'] ?? ''));
    $est = json_decode($c['estadisticas'] ?? '{}', true);
?>
      <div class="zs-pj-card card-item" data-card-id="<?php echo (int) $c['id']; ?>" data-search="<?php echo htmlspecialchars_uni(mb_strtolower($c['nombre'] . ' ' . $tipo_lbl)); ?>">
        <div class="zs-pj-head">
          <div class="zs-pj-avatar zs-avatar-lg">
<?php if ($icono !== ''): ?>
            <span><?php echo htmlspecialchars_uni($icono); ?></span>
<?php else: ?>
            <span>🃏</span>
<?php endif; ?>
          </div>
          <div class="zs-pj-info">
            <div class="zs-pj-name"><?php echo htmlspecialchars_uni($c['nombre']); ?></div>
            <div class="zs-pj-owner">Tipo: <b><?php echo htmlspecialchars_uni($tipo_lbl); ?></b> &middot; ID #<?php echo (int) $c['id']; ?></div>
            <div class="zs-badge-group">
              <span class="zs-badge active">PA: <?php echo (int) ($est['pa'] ?? 0); ?> | EN: <?php echo (int) ($est['en'] ?? 0); ?></span>
<?php if (empty($c['activo'])): ?>
              <span class="zs-badge zs-badge-inactive">Inactiva</span>
<?php endif; ?>
            </div>
          </div>
        </div>

        <div class="zs-pj-meta">
          <?php echo htmlspecialchars_uni($desc_corta !== '' ? $desc_corta : 'Sin descripción'); ?>
        </div>

        <div class="zs-pj-actions">
          <button type="button" class="btn btn-hot btn-sm btn-edit-card" data-card="<?php echo $json; ?>">Editar</button>
          <button type="button" class="btn btn-ghost btn-sm btn-asign-card" data-card-id="<?php echo (int) $c['id']; ?>" data-card-nombre="<?php echo htmlspecialchars_uni($c['nombre']); ?>">Asignar</button>
          <button type="button" class="btn btn-ghost btn-sm btn-del-card zs-txt-danger" data-card-id="<?php echo (int) $c['id']; ?>" data-card-nombre="<?php echo htmlspecialchars_uni($c['nombre']); ?>">Borrar</button>
        </div>
      </div>
<?php endforeach; ?>
    </div>
<?php endif; ?>

  </section>

  <!-- Sección de asignaciones activas -->
  <section class="reveal zs-mt-32">
    <div class="plate">
      <div class="plate-h"><span class="t">Asignación de Cards a Personajes</span><span class="c">// cartas desbloqueadas</span></div>
      <div class="plate-b zs-plate-left">
<?php
$asignaciones = array();
if ($db->table_exists('rol_pj_cards') && $db->table_exists('rol_cards')) {
    $pref = TABLE_PREFIX;
    $q = $db->query("SELECT pc.*, c.nombre AS card_nombre, c.tipo AS card_tipo, c.estadisticas, c.slug AS card_slug, p.nombre AS pj_nombre
        FROM {$pref}rol_pj_cards pc
        JOIN {$pref}rol_cards c ON c.id = pc.card_id
        LEFT JOIN {$pref}rol_personajes p ON p.pid = pc.pid
        ORDER BY pc.dateline DESC");
    while ($r = $db->fetch_array($q)) {
        $asignaciones[] = $r;
    }
}
?>
<?php if (empty($asignaciones)): ?>
        <p class="empty-state zs-mt-12">No hay cards asignadas a personajes actualmente.</p>
<?php else: ?>
        <div class="zs-stafftbl zs-mt-12">
<?php foreach ($asignaciones as $as): 
    $est = json_decode($as['estadisticas'] ?? '{}', true);
?>
          <div class="zs-staffrow">
            <div class="zs-staffwho col-grow">
              <span class="zs-staffname"><?php echo htmlspecialchars_uni($as['card_nombre']); ?></span>
              <span class="zs-staffowner">&rarr; <?php echo htmlspecialchars_uni($as['pj_nombre'] ?? 'PID #' . $as['pid']); ?></span>
              <span class="col-grow zs-lbl-subtle">PA: <?php echo (int) ($est['pa'] ?? 0); ?> | EN: <?php echo (int) ($est['en'] ?? 0); ?></span>
            </div>
            <form method="post" class="zs-inline" onsubmit="return confirm('¿Desasignar esta card del personaje?');">
              <input type="hidden" name="my_post_key" value="<?php echo $mybb->post_code; ?>">
              <input type="hidden" name="action" value="desasignar_card">
              <input type="hidden" name="asig_id" value="<?php echo (int) $as['id']; ?>">
              <button type="submit" class="btn btn-ghost btn-sm zs-txt-danger">Quitar</button>
            </form>
          </div>
<?php endforeach; ?>
        </div>
<?php endif; ?>
      </div>
    </div>
  </section>
</div>

<!-- Modal: Crear Card -->
<div class="zs-modal-overlay" id="createCardModal">
  <div class="zs-modal-box zs-modal-card">
    <div class="zs-modal-h">
      <h3>Crear Nueva Card</h3>
      <button type="button" class="zs-modal-close" onclick="closeModal('createCardModal')">✕</button>
    </div>
    <form method="post">
      <input type="hidden" name="my_post_key" value="<?php echo $mybb->post_code; ?>">
      <input type="hidden" name="action" value="crear_card">

      <div class="zs-form-row">
        <div class="col-grow">
          <label>Nombre de la Card *</label>
          <input type="text" name="nombre" required placeholder="ej. Tajo del Dragón">
        </div>
        <div class="col-grow">
          <label>Tipo</label>
          <select name="tipo">
<?php foreach ($card_tipos as $tk => $tv): ?>
            <option value="<?php echo htmlspecialchars_uni($tk); ?>"><?php echo htmlspecialchars_uni($tv); ?></option>
<?php endforeach; ?>
          </select>
        </div>
        <div class="col-narrow">
          <label>Orden</label>
          <input type="number" name="orden" value="0" min="0" class="zs-control-fill">
        </div>
        <div class="col-narrow">
          <label>Activo</label>
          <select name="activo">
            <option value="1">Sí</option>
            <option value="0">No</option>
          </select>
        </div>
      </div>

      <div class="zs-form-row">
        <div class="col-narrow">
          <label>PA</label>
          <input type="number" name="pa" value="1" min="0" max="99" class="zs-control-fill">
        </div>
        <div class="col-medium">
          <label>EN</label>
          <input type="number" name="en" value="10" min="0" max="999" class="zs-control-fill">
        </div>
        <div class="col-medium">
          <label>Nivel mín.</label>
          <input type="number" name="nivel_min" value="1" min="1" max="100" class="zs-control-fill">
        </div>
        <div class="col-grow">
          <label>Icono (emoji)</label>
          <input type="text" name="icono" placeholder="⚔️ 🔥">
        </div>
      </div>

      <div class="zs-form-group">
        <label>Descripción breve</label>
        <textarea name="descripcion" rows="2" class="zs-control" placeholder="Efecto breve para vista rápida"></textarea>
      </div>

      <div class="zs-form-group">
        <label>Contenido (markup HTML o BBCode)</label>
        <textarea name="contenido" rows="4" class="zs-control" placeholder="Descripción completa, fórmula de daño, efectos secundarios…"></textarea>
      </div>

      <div class="zs-form-group">
        <label>Estadísticas / Atributos extra (JSON)</label>
        <textarea name="estadisticas" rows="2" class="zs-control" placeholder='{"fuente": "haki", "daño": "FUE x 3", "efectos": ["Quemado 1t"]}'></textarea>
      </div>

      <div class="zs-modal-actions">
        <button type="button" class="btn btn-ghost" onclick="closeModal('createCardModal')">Cancelar</button>
        <button type="submit" class="btn btn-hot">Crear Card</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Editar Card -->
<div class="zs-modal-overlay" id="editCardModal">
  <div class="zs-modal-box zs-modal-card">
    <div class="zs-modal-h">
      <h3>Editar Card</h3>
      <button type="button" class="zs-modal-close" onclick="closeModal('editCardModal')">✕</button>
    </div>
    <form method="post">
      <input type="hidden" name="my_post_key" value="<?php echo $mybb->post_code; ?>">
      <input type="hidden" name="action" value="editar_card">
      <input type="hidden" name="card_id" id="editCardId">

      <div class="zs-form-group">
        <label>Nombre *</label>
        <input type="text" name="nombre" id="editNombre" required>
      </div>

      <div class="zs-form-row">
        <div class="col-grow">
          <label>Tipo</label>
          <select name="tipo" id="editTipo">
<?php foreach ($card_tipos as $tk => $tv): ?>
            <option value="<?php echo htmlspecialchars_uni($tk); ?>"><?php echo htmlspecialchars_uni($tv); ?></option>
<?php endforeach; ?>
          </select>
        </div>
        <div class="col-narrow">
          <label>Orden</label>
          <input type="number" name="orden" id="editOrden" min="0" class="zs-control-fill">
        </div>
        <div class="col-narrow">
          <label>Activo</label>
          <select name="activo" id="editActivo">
            <option value="1">Sí</option>
            <option value="0">No</option>
          </select>
        </div>
      </div>

      <div class="zs-form-row">
        <div class="col-narrow">
          <label>PA</label>
          <input type="number" name="pa" id="editPa" min="0" max="99" class="zs-control-fill">
        </div>
        <div class="col-medium">
          <label>EN</label>
          <input type="number" name="en" id="editEn" min="0" max="999" class="zs-control-fill">
        </div>
        <div class="col-medium">
          <label>Nivel mín.</label>
          <input type="number" name="nivel_min" id="editNivelMin" min="1" max="100" class="zs-control-fill">
        </div>
        <div class="col-grow">
          <label>Icono (emoji)</label>
          <input type="text" name="icono" id="editIcono">
        </div>
      </div>

      <div class="zs-form-group">
        <label>Descripción breve</label>
        <textarea name="descripcion" id="editDescripcion" rows="2" class="zs-control"></textarea>
      </div>

      <div class="zs-form-group">
        <label>Contenido (markup HTML o BBCode)</label>
        <textarea name="contenido" id="editContenido" rows="4" class="zs-control"></textarea>
      </div>

      <div class="zs-form-group">
        <label>Estadísticas / Atributos extra (JSON)</label>
        <textarea name="estadisticas" id="editEstadisticas" rows="2" class="zs-control"></textarea>
      </div>

      <div class="zs-modal-actions">
        <button type="button" class="btn btn-ghost" onclick="closeModal('editCardModal')">Cancelar</button>
        <button type="submit" class="btn btn-hot">Guardar Cambios</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Asignar Card a Personaje -->
<div class="zs-modal-overlay" id="assignModal">
  <div class="zs-modal-box zs-modal-dialog">
    <div class="zs-modal-h">
      <h3>Asignar Card a Personaje</h3>
      <button type="button" class="zs-modal-close" onclick="closeModal('assignModal')">✕</button>
    </div>
    <form method="post">
      <input type="hidden" name="my_post_key" value="<?php echo $mybb->post_code; ?>">
      <input type="hidden" name="action" value="asignar_card">
      <input type="hidden" name="card_id" id="assignCardId">

      <p class="zs-text-lead">
        Selecciona el personaje y el slot para la card: <b id="assignCardNombre"></b>
      </p>

      <div class="zs-form-group">
        <label>Personaje *</label>
        <select name="pid" required>
          <option value="">-- Seleccionar Personaje --</option>
<?php foreach ($personajes as $pj): ?>
          <option value="<?php echo (int) $pj['pid']; ?>"><?php echo htmlspecialchars_uni($pj['nombre']); ?> (PID #<?php echo (int) $pj['pid']; ?>)</option>
<?php endforeach; ?>
        </select>
      </div>

      <div class="zs-form-group">
        <label>Slot *</label>
        <select name="slot">
<?php foreach ($card_slots as $sk => $sv): ?>
          <option value="<?php echo htmlspecialchars_uni($sk); ?>"><?php echo htmlspecialchars_uni($sv); ?></option>
<?php endforeach; ?>
        </select>
      </div>

      <div class="zs-modal-actions">
        <button type="button" class="btn btn-ghost" onclick="closeModal('assignModal')">Cancelar</button>
        <button type="submit" class="btn btn-hot">Asignar Card</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Confirmar Borrado de Card -->
<div class="zs-modal-overlay" id="delCardModal">
  <div class="zs-modal-box zs-modal-alert">
    <div class="zs-modal-h">
      <h3 class="zs-txt-danger">⚠️ Confirmar Desactivación</h3>
      <button type="button" class="zs-modal-close" onclick="closeModal('delCardModal')">✕</button>
    </div>
    <form method="post">
      <input type="hidden" name="my_post_key" value="<?php echo $mybb->post_code; ?>">
      <input type="hidden" name="action" value="borrar_card">
      <input type="hidden" name="card_id" id="delCardId">

      <p class="zs-text-sub">
        ¿Estás seguro de que deseas desactivar la card <b id="delCardNombre" class="zs-text-hi"></b>?
      </p>

      <div class="zs-modal-actions">
        <button type="button" class="btn btn-ghost" onclick="closeModal('delCardModal')">Cancelar</button>
        <button type="submit" class="btn btn-hot zs-btn-danger">Desactivar</button>
      </div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/inc/footer_custom.php'; ?>

<script>
// Modales
function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('zs-modal-overlay')) {
        e.target.classList.remove('open');
    }
});

// Editar card: llenar modal con datos
document.querySelectorAll('.btn-edit-card').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var c = JSON.parse(this.getAttribute('data-card'));
        var est = {};
        try { est = JSON.parse(c.estadisticas || '{}'); } catch(e) {}
        document.getElementById('editCardId').value = c.id;
        document.getElementById('editNombre').value = c.nombre || '';
        document.getElementById('editTipo').value = c.tipo || 'tecnica';
        document.getElementById('editOrden').value = c.orden || 0;
        document.getElementById('editActivo').value = c.activo || 1;
        document.getElementById('editPa').value = est.pa || 1;
        document.getElementById('editEn').value = est.en || 10;
        document.getElementById('editNivelMin').value = est.nivel_min || 1;
        document.getElementById('editIcono').value = c.icono || '';
        document.getElementById('editDescripcion').value = c.descripcion || '';
        document.getElementById('editContenido').value = c.contenido || '';
        document.getElementById('editEstadisticas').value = JSON.stringify(est, null, 2);
        openModal('editCardModal');
    });
});

// Asignar card
document.querySelectorAll('.btn-asign-card').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.getElementById('assignCardId').value = this.getAttribute('data-card-id');
        document.getElementById('assignCardNombre').textContent = this.getAttribute('data-card-nombre');
        openModal('assignCardModal');
    });
});

// Desactivar card
document.querySelectorAll('.btn-del-card').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.getElementById('delCardId').value = this.getAttribute('data-card-id');
        document.getElementById('delCardNombre').textContent = this.getAttribute('data-card-nombre');
        openModal('deleteCardModal');
    });
});

// Búsqueda en vivo
document.getElementById('cardSearchInput').addEventListener('input', function() {
    var q = this.value.toLowerCase().trim();
    document.querySelectorAll('.card-item').forEach(function(el) {
        var txt = el.getAttribute('data-search') || '';
        el.style.display = (q === '' || txt.indexOf(q) !== -1) ? '' : 'none';
    });
});

// Reveal on scroll
if ('IntersectionObserver' in window && !matchMedia('(prefers-reduced-motion:reduce)').matches) {
    var io = new IntersectionObserver(function(es) {
        es.forEach(function(e) { if (e.isIntersecting) { e.target.classList.add('vis'); io.unobserve(e.target); } });
    }, { threshold: .08 });
    document.querySelectorAll('.reveal').forEach(function(el) { io.observe(el); });
} else {
    document.querySelectorAll('.reveal').forEach(function(el) { el.classList.add('vis'); });
}
</script>
</body>
</html>
<?php

function truncate_html($text, $max)
{
    $clean = strip_tags((string) $text);
    $clean = preg_replace('/\s+/', ' ', $clean);
    if (function_exists('mb_strlen') && mb_strlen($clean, 'UTF-8') > $max) {
        return mb_substr($clean, 0, $max, 'UTF-8') . '…';
    }
    if (strlen($clean) > $max) {
        return substr($clean, 0, $max) . '…';
    }
    return $clean;
}

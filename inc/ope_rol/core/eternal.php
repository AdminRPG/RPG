<?php
/**
 * One Piece Eternal — carga y render SVG de árboles Identidad/Arma.
 * Datos: inc/ope_eternal/{id}.json
 *
 * Layout: filas = Tier, columnas = Foco; conectores ortogonales por prereq.
 * Modos: preview | interactivo
 */

if (!defined('IN_MYBB') && !defined('OPE_ETERNAL_STANDALONE')) {
	// Allow include from CLI/tests without full MyBB bootstrap.
}

/**
 * Lista canónica de ids de árbol Eternal.
 *
 * @return string[]
 */
function ope_eternal_tree_ids()
{
	return array(
		'identidad-coloso',
		'identidad-duelista',
		'identidad-verdugo',
		'identidad-fantasma',
		'identidad-centinela',
		'identidad-cazador',
		'identidad-detonador',
		'arma-filo',
		'arma-contundente',
		'arma-alcance',
		'arma-distancia',
		'arma-cuerpo',
	);
}

/**
 * Directorio de JSON de árboles.
 *
 * @return string
 */
function ope_eternal_data_dir()
{
	if (defined('MYBB_ROOT') && is_dir(MYBB_ROOT . 'inc/ope_eternal')) {
		return MYBB_ROOT . 'inc/ope_eternal';
	}
	return dirname(dirname(dirname(__FILE__))) . '/ope_eternal';
}

/**
 * Carga un árbol por id (cache estático).
 *
 * @param string $id
 * @return array|null
 */
function ope_eternal_load($id)
{
	static $cache = array();

	$id = (string) $id;
	if ($id === '') {
		return null;
	}
	if (array_key_exists($id, $cache)) {
		return $cache[$id];
	}

	if (!in_array($id, ope_eternal_tree_ids(), true)) {
		$cache[$id] = null;
		return null;
	}

	$path = ope_eternal_data_dir() . '/' . $id . '.json';
	if (!is_readable($path)) {
		$cache[$id] = null;
		return null;
	}

	$raw = file_get_contents($path);
	$data = json_decode($raw, true);
	if (!is_array($data) || empty($data['id'])) {
		$cache[$id] = null;
		return null;
	}

	$cache[$id] = $data;
	return $cache[$id];
}

/**
 * Todos los árboles, opcionalmente filtrados por tipo (identidad|arma).
 *
 * @param string|null $tipo
 * @return array[]
 */
function ope_eternal_all($tipo = null)
{
	$out = array();
	foreach (ope_eternal_tree_ids() as $id) {
		$tree = ope_eternal_load($id);
		if ($tree === null) {
			continue;
		}
		if ($tipo !== null && $tipo !== '' && (!isset($tree['tipo']) || $tree['tipo'] !== $tipo)) {
			continue;
		}
		$out[] = $tree;
	}
	return $out;
}

/**
 * Escape HTML seguro.
 *
 * @param mixed $s
 * @return string
 */
function ope_eternal_esc($s)
{
	return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

/**
 * Columnas de foco para layout. El Núcleo/General es el "tronco" compartido
 * por todas las ramas, así que se posiciona en la columna CENTRAL de la
 * lista de focos (no en el extremo izquierdo): para N focos, el índice de
 * inserción es floor(N/2), de forma genérica para 2, 3, 4... focos.
 *
 * @param array $tree
 * @return string[]
 */
function ope_eternal_foco_columns($tree)
{
	$used = array();
	foreach ($tree['nodos'] as $n) {
		$f = isset($n['foco']) && $n['foco'] !== '' ? (string) $n['foco'] : 'General';
		$used[$f] = true;
	}

	$has_general = !empty($used['General']);
	unset($used['General']);

	$foco_cols = array();
	if (!empty($tree['focos']) && is_array($tree['focos'])) {
		foreach ($tree['focos'] as $f) {
			$f = (string) $f;
			if (isset($used[$f])) {
				$foco_cols[] = $f;
				unset($used[$f]);
			}
		}
	}
	foreach (array_keys($used) as $f) {
		$foco_cols[] = $f;
	}

	if (!$has_general) {
		return $foco_cols;
	}

	$mid = (int) floor(count($foco_cols) / 2);
	$cols = $foco_cols;
	array_splice($cols, $mid, 0, array('General'));
	return $cols;
}

/**
 * Ancho total (px) de un grupo de nodos ya ordenado.
 *
 * @param array[] $group
 * @param int $node_w
 * @param int $gap
 * @return int
 */
function ope_eternal_group_width($group, $node_w, $gap)
{
	$n = count($group);
	if ($n < 1) {
		return $node_w;
	}
	return $n * $node_w + ($n - 1) * $gap;
}

/**
 * Estima la altura (px) que necesita un nodo para mostrar su texto de
 * efecto SIN truncar (sin line-clamp; ver `.eternal-node__efecto` en
 * docs/themes/ope.css). Heurística caracteres-por-línea a partir del
 * ancho útil del nodo y el tamaño de fuente real del efecto (.68rem).
 * El resto de bloques del nodo (nombre, meta, badge de coste, paddings)
 * se aproximan con `$chrome_h`, calibrado contra el alto histórico fijo
 * (118px) que asumía siempre 3 líneas de efecto.
 *
 * @param string $efecto
 * @param int    $node_w
 * @return int
 */
function ope_eternal_estimate_node_height($efecto, $node_w)
{
	$EFECTO_FONT_PX = 10.88; // .68rem con raíz 16px
	$EFECTO_LINE_H = $EFECTO_FONT_PX * 1.3;
	$CHAR_W = $EFECTO_FONT_PX * 0.58; // heurística conservadora (evita solapes por subestimar líneas)
	$PAD_X = 9 + 9; // padding horizontal real del nodo (8px 9px 7px)
	$chrome_h = 76; // nombre (≤2 líneas) + meta + badge de coste + paddings/gaps, sin el bloque de efecto

	$text_w = max(20, (int) $node_w - $PAD_X);
	$chars_per_line = max(8, (int) floor($text_w / $CHAR_W));
	$len = mb_strlen((string) $efecto);
	$lines = $len > 0 ? (int) ceil($len / $chars_per_line) : 1;
	$lines = max(1, $lines);

	return (int) ceil($chrome_h + $lines * $EFECTO_LINE_H);
}

/**
 * Orden topológico suave dentro de un grupo (respeta prereq internos).
 *
 * @param array[] $nodes
 * @return array[]
 */
function ope_eternal_sort_group($nodes)
{
	$by_id = array();
	foreach ($nodes as $n) {
		$by_id[(string) $n['id']] = $n;
	}
	$ids = array_keys($by_id);
	$done = array();
	$out = array();
	$guard = 0;
	while (count($out) < count($ids) && $guard < 500) {
		$guard++;
		$progress = false;
		foreach ($ids as $id) {
			if (isset($done[$id])) {
				continue;
			}
			$pr = isset($by_id[$id]['prereq']) && is_array($by_id[$id]['prereq']) ? $by_id[$id]['prereq'] : array();
			$ok = true;
			foreach ($pr as $p) {
				$p = (string) $p;
				if (isset($by_id[$p]) && !isset($done[$p])) {
					$ok = false;
					break;
				}
			}
			if ($ok) {
				$out[] = $by_id[$id];
				$done[$id] = true;
				$progress = true;
			}
		}
		if (!$progress) {
			foreach ($ids as $id) {
				if (!isset($done[$id])) {
					$out[] = $by_id[$id];
					$done[$id] = true;
				}
			}
			break;
		}
	}
	return $out;
}

/**
 * Calcula posiciones absolutas (px) para cada nodo + tamaño del lienzo.
 *
 * Cada habilidad con rangos (I, II, III…) es UN solo nodo en los datos
 * (campo `rango.max`); no hay cadenas de nodos separados que agrupar,
 * por eso el layout es una simple grilla tier × foco.
 *
 * @param array $tree
 * @return array{positions: array<string,array>, width:int, height:int, cols:string[], tiers:int[], metrics:array}
 */
function ope_eternal_compute_layout($tree)
{
	$NODE_W = 168;
	$NODE_H_MIN = 118;
	$H_GAP = 14;
	$TIER_GAP = 52;
	$PAD_X = 52;
	$PAD_Y = 36;
	$COL_GAP = 28;
	$LABEL_H = 22;

	$cols = ope_eternal_foco_columns($tree);

	// Agrupar por tier → foco
	$by = array();
	$tiers = array();
	foreach ($tree['nodos'] as $n) {
		$t = isset($n['tier']) ? (int) $n['tier'] : 1;
		$f = isset($n['foco']) && $n['foco'] !== '' ? (string) $n['foco'] : 'General';
		$tiers[$t] = true;
		if (!isset($by[$t][$f])) {
			$by[$t][$f] = array();
		}
		$by[$t][$f][] = $n;
	}
	$tier_list = array_keys($tiers);
	sort($tier_list, SORT_NUMERIC);

	// Grupos ordenados (topológico suave) + ancho real de cada grupo por celda tier×foco
	$groups = array();
	$group_widths = array();
	foreach ($cols as $c) {
		foreach ($tier_list as $t) {
			$group = isset($by[$t][$c]) ? ope_eternal_sort_group($by[$t][$c]) : array();
			$groups[$t][$c] = $group;
			$group_widths[$t][$c] = ope_eternal_group_width($group, $NODE_W, $H_GAP);
		}
	}

	// Ancho por columna = mayor ancho de grupo entre todos los tiers de esa columna
	$col_widths = array();
	foreach ($cols as $c) {
		$max_w = $NODE_W;
		foreach ($tier_list as $t) {
			$w = isset($group_widths[$t][$c]) ? $group_widths[$t][$c] : 0;
			if ($w > $max_w) {
				$max_w = $w;
			}
		}
		$col_widths[$c] = $max_w;
	}

	$col_x = array();
	$x = $PAD_X;
	foreach ($cols as $c) {
		$col_x[$c] = $x;
		$x += $col_widths[$c] + $COL_GAP;
	}
	$width = $x - $COL_GAP + $PAD_X;

	// Altura por nodo: variable según longitud real del texto de efecto
	// (sin line-clamp, ver ope_eternal_estimate_node_height). El avance
	// vertical de cada tier usa la altura MÁXIMA de sus nodos, para que
	// nunca se solape con la fila siguiente aunque cada nodo mida distinto.
	$tier_y = array();
	$tier_h = array();
	$y = $PAD_Y + $LABEL_H;
	$positions = array();
	foreach ($tier_list as $t) {
		$tier_y[$t] = $y;
		$row_h = $NODE_H_MIN;
		foreach ($cols as $c) {
			$group = $groups[$t][$c];
			$n = count($group);
			if ($n < 1) {
				continue;
			}
			// Centrar el grupo en la columna
			$group_w = $group_widths[$t][$c];
			$start_x = $col_x[$c] + max(0, (int) (($col_widths[$c] - $group_w) / 2));
			$cursor = $start_x;
			foreach ($group as $nodo) {
				$nid = (string) $nodo['id'];
				// v3: un único texto de efecto por nodo. Sumamos profundidad y
				// bonus de afinidad (pináculos) para estimar el alto real.
				$texto_h = isset($nodo['efecto']) ? (string) $nodo['efecto'] : '';
				if (!empty($nodo['profundidad'])) {
					$texto_h .= ' Profundidad: ' . (string) $nodo['profundidad'];
				}
				if (!empty($nodo['afinidad_bonus'])) {
					$texto_h .= ' Afinidad: ' . (string) $nodo['afinidad_bonus'];
				}
				$node_h = max($NODE_H_MIN, ope_eternal_estimate_node_height($texto_h, $NODE_W));
				if ($node_h > $row_h) {
					$row_h = $node_h;
				}
				$positions[$nid] = array(
					'x' => $cursor,
					'y' => $y,
					'w' => $NODE_W,
					'h' => $node_h,
					'tier' => $t,
					'foco' => $c,
					'nodo' => $nodo,
				);
				$cursor += $NODE_W + $H_GAP;
			}
		}
		$tier_h[$t] = $row_h;
		$y += $row_h + $TIER_GAP;
	}
	$height = $y - $TIER_GAP + $PAD_Y;

	return array(
		'positions' => $positions,
		'width' => (int) $width,
		'height' => (int) $height,
		'cols' => $cols,
		'col_x' => $col_x,
		'col_widths' => $col_widths,
		'tiers' => $tier_list,
		'tier_y' => $tier_y,
		'tier_h' => $tier_h,
		'metrics' => array(
			'node_w' => $NODE_W,
			'node_h' => $NODE_H_MIN,
			'pad_y' => $PAD_Y,
			'label_h' => $LABEL_H,
		),
	);
}

/**
 * Path ortogonal (codo) entre centros de dos nodos.
 *
 * @param array $from pos
 * @param array $to pos
 * @return string
 */
function ope_eternal_elbow_path($from, $to)
{
	$x1 = $from['x'] + $from['w'] / 2;
	$y1 = $from['y'] + $from['h'];
	$x2 = $to['x'] + $to['w'] / 2;
	$y2 = $to['y'];
	// Si mismo tier (cadena horizontal): conexión lateral
	if ((int) $from['tier'] === (int) $to['tier']) {
		$x1 = $from['x'] + $from['w'];
		$y1 = $from['y'] + $from['h'] / 2;
		$x2 = $to['x'];
		$y2 = $to['y'] + $to['h'] / 2;
		$mid = ($x1 + $x2) / 2;
		return sprintf('M %.1f %.1f H %.1f V %.1f H %.1f', $x1, $y1, $mid, $y2, $x2);
	}
	$mid_y = ($y1 + $y2) / 2;
	return sprintf('M %.1f %.1f V %.1f H %.1f V %.1f', $x1, $y1, $mid_y, $x2, $y2);
}

/**
 * Color de borde por tipo de nodo (Sistema Eternal v3 "Forja").
 *
 * Solo hay tres tipos de nodo (pasiva | habilitador | mini-sistema) más el
 * pináculo, que corona cada corriente.
 *
 * @param array $nodo
 * @return string
 */
function ope_eternal_node_stroke($nodo)
{
	$tipo = ope_eternal_node_tipo($nodo);
	if (!empty($nodo['pinaculo'])) {
		return '#b45309'; // ámbar — pináculo
	}
	if ($tipo === 'habilitador') {
		return '#4f46e5'; // índigo — desbloquea creación de técnicas
	}
	if ($tipo === 'mini-sistema') {
		return '#0d9488'; // teal — mini-sistema propio
	}
	return '#15803d'; // verde — pasiva mecánica
}

/**
 * Normaliza el tipo de nodo al vocabulario v3 (pasiva|habilitador|mini-sistema),
 * tolerando datos legacy (pasivo/activo/decision) por si algún árbol no se ha
 * migrado todavía.
 *
 * @param array $nodo
 * @return string
 */
function ope_eternal_node_tipo($nodo)
{
	$t = isset($nodo['tipo']) ? strtolower((string) $nodo['tipo']) : 'pasiva';
	switch ($t) {
		case 'habilitador':
			return 'habilitador';
		case 'mini-sistema':
		case 'minisistema':
		case 'mini_sistema':
			return 'mini-sistema';
		case 'pasiva':
		case 'pasivo':
		case 'activo':   // legacy: en v3 no existe "activo"; se muestra como pasiva
		case 'decision': // legacy
		default:
			return 'pasiva';
	}
}

/**
 * Etiqueta legible del tipo de nodo v3.
 *
 * @param string $tipo
 * @return string
 */
function ope_eternal_tipo_label($tipo)
{
	switch ($tipo) {
		case 'habilitador':
			return 'Habilitador';
		case 'mini-sistema':
			return 'Mini-sistema';
		default:
			return 'Pasiva';
	}
}

/**
 * Nivel de personaje en que se desbloquea cada tier (cadencia PT v3).
 *
 * @param int $tier
 * @return int
 */
function ope_eternal_tier_nivel($tier)
{
	$map = array(1 => 1, 2 => 10, 3 => 20, 4 => 30, 5 => 45);
	return isset($map[(int) $tier]) ? $map[(int) $tier] : 0;
}

/**
 * Render de un árbol Eternal v3 "Forja" como rejilla inline (5 tiers × 3
 * corrientes). Sin modal ni SVG: con solo 15 nodos la rejilla cabe en la
 * página y se lee de un vistazo. Cada nodo sigue siendo clicable para abrir
 * su ficha completa (data-ope-node) donde el JS lo soporte.
 *
 * @param array  $tree
 * @param string $mode  preview|interactivo
 * @param array  $owned
 * @return string
 */
function ope_eternal_render_tree($tree, $mode = 'preview', $owned = array())
{
	if (!is_array($tree) || empty($tree['nodos'])) {
		return '';
	}

	$mode = ($mode === 'interactivo') ? 'interactivo' : 'preview';
	$owned_map = array();
	if (is_array($owned)) {
		foreach ($owned as $oid) {
			$owned_map[(string) $oid] = true;
		}
	}

	$nombre = isset($tree['nombre']) ? $tree['nombre'] : $tree['id'];
	$resumen = isset($tree['resumen_arquetipo']) ? $tree['resumen_arquetipo'] : '';
	$rol = isset($tree['rol_mecanico']) ? $tree['rol_mecanico'] : '';
	$recurso = isset($tree['recurso_secundario']) ? $tree['recurso_secundario'] : '';
	$recurso_base = isset($tree['recurso_base']) ? (string) $tree['recurso_base'] : '';
	$focos = isset($tree['focos']) && is_array($tree['focos']) ? array_values($tree['focos']) : array();
	$tipo_arbol = isset($tree['tipo']) ? $tree['tipo'] : '';
	$version = isset($tree['version']) ? $tree['version'] : '1.0';

	// Metadatos de corriente: símbolo (α/β/γ) + filosofía por clave.
	$corr_meta = array();
	if (!empty($tree['corrientes']) && is_array($tree['corrientes'])) {
		foreach ($tree['corrientes'] as $corr) {
			if (isset($corr['clave'])) {
				$corr_meta[(string) $corr['clave']] = array(
					'simbolo'   => isset($corr['simbolo']) ? (string) $corr['simbolo'] : '',
					'filosofia' => isset($corr['filosofia']) ? (string) $corr['filosofia'] : '',
				);
			}
		}
	}

	// Agrupar nodos por tier → foco y mapa id→nombre (para el modal de nodo).
	$by = array();
	$tiers = array();
	$id_to_nombre = array();
	foreach ($tree['nodos'] as $n) {
		$t = isset($n['tier']) ? (int) $n['tier'] : 1;
		$f = isset($n['foco']) ? (string) $n['foco'] : '';
		if ($f !== '' && !in_array($f, $focos, true)) {
			$focos[] = $f;
		}
		$tiers[$t] = true;
		$by[$t][$f] = $n;
		$id_to_nombre[(string) $n['id']] = isset($n['nombre']) ? (string) $n['nombre'] : (string) $n['id'];
	}
	$tier_list = array_keys($tiers);
	sort($tier_list, SORT_NUMERIC);
	$ncols = max(1, count($focos));

	$html = '<div class="eternal-tree eternal-tree--grid"';
	$html .= ' data-tree-id="' . ope_eternal_esc($tree['id']) . '"';
	$html .= ' data-tree-tipo="' . ope_eternal_esc($tipo_arbol) . '"';
	$html .= ' data-mode="' . ope_eternal_esc($mode) . '">';

	// ── Cabecera ──────────────────────────────────────────────
	$html .= '<header class="eternal-tree__header">';
	$html .= '<div class="eternal-tree__title-row">';
	$html .= '<h2 class="eternal-tree__nombre">' . ope_eternal_esc($nombre) . '</h2>';
	$html .= '<span class="eternal-tree__ver">v' . ope_eternal_esc($version) . '</span>';
	$html .= '<button type="button" class="ope-help-btn" data-ope-help="eternal-tree" title="¿Cómo se lee este árbol?">?</button>';
	$html .= '</div>';
	if ($recurso !== '') {
		$html .= '<p class="eternal-tree__recurso"><span class="eternal-tree__label">Recurso</span> ' . ope_eternal_esc($recurso);
		if ($recurso_base !== '') {
			$html .= ' <span class="eternal-tree__recurso-base">' . ope_eternal_esc($recurso_base) . '</span>';
		}
		$html .= '</p>';
	}
	if ($resumen !== '') {
		$html .= '<p class="eternal-tree__resumen">' . ope_eternal_esc($resumen) . '</p>';
	}
	if ($rol !== '') {
		$html .= '<p class="eternal-tree__rol"><span class="eternal-tree__label">Rol</span> ' . ope_eternal_esc($rol) . '</p>';
	}
	$html .= '<ul class="eternal-tree__leyenda" aria-label="Leyenda de nodos">';
	$html .= '<li class="eternal-tree__leyenda-item eternal-tree__leyenda-item--pasiva">Pasiva</li>';
	$html .= '<li class="eternal-tree__leyenda-item eternal-tree__leyenda-item--habilitador">Habilitador</li>';
	$html .= '<li class="eternal-tree__leyenda-item eternal-tree__leyenda-item--mini">Mini-sistema</li>';
	$html .= '<li class="eternal-tree__leyenda-item eternal-tree__leyenda-item--pinaculo">Pináculo</li>';
	$html .= '</ul>';
	$html .= '<p class="eternal-tree__hint">Cada <b>fila</b> es un Tier que se abre a un nivel fijo (T1·Nv1, T2·Nv10, T3·Nv20, T4·Nv30, Pináculo·Nv45) '
		. 'y te da <b>1 PT para elegir 1 de sus 3 nodos</b> (uno por corriente). Al terminar tendrás <b>5 nodos</b> de los 15. '
		. 'No hay prerrequisitos verticales: eliges libremente en cada tier. Especializarte en una misma corriente activa bonos de '
		. '<b>Profundidad</b> y <b>Afinidad</b>. Un <b>Habilitador</b> no te da la técnica: te da el derecho a crearla con PP.</p>';
	$html .= '</header>';

	// ── Rejilla (inline) ──────────────────────────────────────
	$html .= '<div class="eternal-grid-wrap">';
	$html .= '<div class="eternal-grid" style="--et-cols:' . (int) $ncols . '" role="table" aria-label="Nodos de ' . ope_eternal_esc($nombre) . '">';

	// Cabecera de columnas: corriente + símbolo + filosofía.
	$html .= '<div class="eternal-grid__corner" aria-hidden="true"></div>';
	foreach ($focos as $f) {
		$sim = isset($corr_meta[$f]['simbolo']) ? $corr_meta[$f]['simbolo'] : '';
		$fil = isset($corr_meta[$f]['filosofia']) ? $corr_meta[$f]['filosofia'] : '';
		$html .= '<div class="eternal-grid__col" role="columnheader">';
		$html .= '<span class="eternal-grid__col-n">' . ope_eternal_esc($f);
		if ($sim !== '') {
			$html .= ' <em class="eternal-grid__col-sym">' . ope_eternal_esc($sim) . '</em>';
		}
		$html .= '</span>';
		if ($fil !== '') {
			$html .= '<span class="eternal-grid__col-f">' . ope_eternal_esc($fil) . '</span>';
		}
		$html .= '</div>';
	}

	// Filas por tier.
	foreach ($tier_list as $t) {
		$nivel = ope_eternal_tier_nivel($t);
		$is_pin_row = ((int) $t === 5);
		$html .= '<div class="eternal-grid__tier' . ($is_pin_row ? ' eternal-grid__tier--pinaculo' : '') . '" role="rowheader">';
		$html .= '<b>' . ope_eternal_esc($is_pin_row ? 'Pináculo' : ('T' . $t)) . '</b>';
		if ($nivel > 0) {
			$html .= '<span class="eternal-grid__nivel">Nv ' . (int) $nivel . '</span>';
		}
		$html .= '</div>';

		foreach ($focos as $f) {
			if (!isset($by[$t][$f])) {
				$html .= '<div class="eternal-cell eternal-cell--empty" aria-hidden="true"></div>';
				continue;
			}
			$html .= ope_eternal_render_node_cell($by[$t][$f], $mode, $owned_map, $id_to_nombre);
		}
	}

	$html .= '</div>'; // grid
	$html .= '</div>'; // grid-wrap
	$html .= '</div>'; // eternal-tree
	return $html;
}

/**
 * Lista compacta de nodos Eternal que el PJ ya posee (ficha pública / tab Talentos).
 * Crece conforme eliges nodos; no muestra el árbol completo.
 *
 * @param array $tree
 * @param array $owned ids de nodos
 * @return string
 */
function ope_eternal_render_owned($tree, $owned = array())
{
	if (!is_array($tree) || empty($tree['nodos']) || !is_array($owned) || empty($owned)) {
		return '';
	}
	$owned_map = array();
	foreach ($owned as $oid) {
		$owned_map[(string) $oid] = true;
	}
	$by_id = array();
	foreach ($tree['nodos'] as $n) {
		$by_id[(string) $n['id']] = $n;
	}

	$nombre = isset($tree['nombre']) ? $tree['nombre'] : $tree['id'];
	$html = '<div class="eternal-owned">';
	$html .= '<h3 class="eternal-owned__h">' . ope_eternal_esc($nombre) . ' <span class="c-dim">// ' . count($owned_map) . ' elegidos</span></h3>';
	$html .= '<ul class="eternal-owned__list">';
	foreach ($owned as $oid) {
		$oid = (string) $oid;
		if (!isset($by_id[$oid])) {
			continue;
		}
		$n = $by_id[$oid];
		$nnombre = isset($n['nombre']) ? (string) $n['nombre'] : $oid;
		$ntipo = ope_eternal_node_tipo($n);
		$ntipo_label = ope_eternal_tipo_label($ntipo);
		$nfoco = isset($n['foco']) ? (string) $n['foco'] : '';
		$tier = isset($n['tier']) ? (int) $n['tier'] : 1;
		$efecto = isset($n['efecto']) ? (string) $n['efecto'] : '';
		$is_pin = !empty($n['pinaculo']);
		$meta = ($is_pin ? 'Pináculo' : ('T' . $tier)) . ($nfoco !== '' ? ' · ' . $nfoco : '') . ' · ' . $ntipo_label;
		$html .= '<li class="eternal-owned__item eternal-owned__item--' . ope_eternal_esc(preg_replace('/[^a-z0-9\-]/', '', $ntipo)) . '">';
		$html .= '<div class="eternal-owned__name">' . ope_eternal_esc($nnombre) . '</div>';
		$html .= '<div class="eternal-owned__meta">' . ope_eternal_esc($meta) . '</div>';
		if ($efecto !== '') {
			$html .= '<p class="eternal-owned__efecto">' . ope_eternal_esc($efecto) . '</p>';
		}
		$html .= '</li>';
	}
	$html .= '</ul></div>';
	return $html;
}

/**
 * Renderiza un nodo como celda-botón de la rejilla Eternal.
 *
 * @param array  $nodo
 * @param string $mode
 * @param array  $owned_map
 * @param array  $id_to_nombre
 * @return string
 */
function ope_eternal_render_node_cell($nodo, $mode, $owned_map, $id_to_nombre)
{
	$nid = (string) $nodo['id'];
	$nnombre = isset($nodo['nombre']) ? (string) $nodo['nombre'] : $nid;
	$ntipo = ope_eternal_node_tipo($nodo);
	$ntipo_label = ope_eternal_tipo_label($ntipo);
	$ncodigo = isset($nodo['codigo']) ? (string) $nodo['codigo'] : '';
	$nfoco = isset($nodo['foco']) ? (string) $nodo['foco'] : '';
	$tier = isset($nodo['tier']) ? (int) $nodo['tier'] : 1;
	$is_owned = isset($owned_map[$nid]);
	$is_pin = !empty($nodo['pinaculo']);
	$coste = isset($nodo['coste_pt']) ? (int) $nodo['coste_pt'] : 1;
	$excluye = isset($nodo['excluye']) && is_array($nodo['excluye']) ? $nodo['excluye'] : array();
	$stroke = ope_eternal_node_stroke($nodo);

	$efecto_card = isset($nodo['efecto']) ? (string) $nodo['efecto'] : '';
	$profundidad = isset($nodo['profundidad']) ? (string) $nodo['profundidad'] : '';
	$afinidad = isset($nodo['afinidad']) ? (string) $nodo['afinidad'] : '';
	$afinidad_bonus = isset($nodo['afinidad_bonus']) ? (string) $nodo['afinidad_bonus'] : '';

	// Exclusión (pináculos): solo bloquea en modo interactivo con $owned real.
	$is_blocked = false;
	$blocked_by_nombre = '';
	if ($mode === 'interactivo' && !empty($excluye)) {
		foreach ($excluye as $eid) {
			$eid = (string) $eid;
			if (isset($owned_map[$eid])) {
				$is_blocked = true;
				$blocked_by_nombre = isset($id_to_nombre[$eid]) ? $id_to_nombre[$eid] : $eid;
				break;
			}
		}
	}

	$classes = array('eternal-node', 'eternal-node--cell', 'eternal-node--' . preg_replace('/[^a-z0-9\-]/', '', $ntipo));
	if ($is_pin) {
		$classes[] = 'eternal-node--pinaculo';
	}
	if ($is_owned) {
		$classes[] = 'is-owned';
	}
	if ($mode === 'interactivo') {
		$classes[] = 'eternal-node--interactivo';
	}
	if ($is_blocked) {
		$classes[] = 'eternal-node--blocked';
	}

	$payload = array(
		'id' => $nid,
		'codigo' => $ncodigo,
		'nombre' => $nnombre,
		'tipo' => $ntipo,
		'tipo_label' => $ntipo_label,
		'foco' => $nfoco,
		'tier' => $tier,
		'nivel' => ope_eternal_tier_nivel($tier),
		'efecto' => $efecto_card,
		'profundidad' => $profundidad,
		'afinidad' => $afinidad,
		'afinidad_bonus' => $afinidad_bonus,
		'coste_pt' => $coste,
		'excluye' => $excluye,
		'excluye_nombres' => array_map(function ($eid) use ($id_to_nombre) {
			$eid = (string) $eid;
			return isset($id_to_nombre[$eid]) ? $id_to_nombre[$eid] : $eid;
		}, $excluye),
		'pinaculo' => $is_pin,
		'mode' => $mode,
		'owned' => $is_owned,
		'blocked' => $is_blocked,
		'blocked_by_nombre' => $blocked_by_nombre,
	);

	$aria = $nnombre . ' (' . ($is_pin ? 'Pináculo' : $ntipo_label) . ') — ' . $efecto_card;
	if ($is_blocked) {
		$aria .= ' (bloqueado: ya elegiste ' . $blocked_by_nombre . ')';
	}

	$h = '<button type="button" class="' . ope_eternal_esc(implode(' ', $classes)) . '"';
	$h .= ' style="--et-stroke:' . ope_eternal_esc($stroke) . '"';
	$h .= ' data-node-id="' . ope_eternal_esc($nid) . '"';
	$h .= ' data-ope-node="' . ope_eternal_esc(json_encode($payload, JSON_UNESCAPED_UNICODE)) . '"';
	$h .= ' aria-label="' . ope_eternal_esc($aria) . '"';
	if ($is_blocked) {
		$h .= ' aria-disabled="true"';
	}
	$h .= '>';
	$h .= '<span class="eternal-node__head">';
	if ($ncodigo !== '') {
		$h .= '<span class="eternal-node__codigo">' . ope_eternal_esc($ncodigo) . '</span>';
	}
	$h .= '<span class="eternal-node__nombre">' . ope_eternal_esc($nnombre) . '</span>';
	$h .= '</span>';
	$h .= '<span class="eternal-node__meta">';
	$h .= '<span class="eternal-node__tipo">' . ope_eternal_esc($is_pin ? 'Pináculo' : $ntipo_label) . '</span>';
	$h .= '<span class="eternal-node__coste">' . (int) $coste . ' PT</span>';
	$h .= '</span>';
	$h .= '<span class="eternal-node__efecto">' . ope_eternal_esc($efecto_card) . '</span>';
	if ($profundidad !== '') {
		$h .= '<span class="eternal-node__profundidad"><b>Profundidad:</b> ' . ope_eternal_esc($profundidad) . '</span>';
	}
	if ($afinidad_bonus !== '') {
		$h .= '<span class="eternal-node__afinidad"><b>Afinidad:</b> ' . ope_eternal_esc($afinidad_bonus) . '</span>';
	}
	if ($is_blocked) {
		$h .= '<span class="eternal-node__bloqueo">Bloqueado: elegiste ' . ope_eternal_esc($blocked_by_nombre) . '</span>';
	}
	if ($mode === 'interactivo') {
		$btn_label = $is_blocked ? 'Bloqueado' : ($is_owned ? 'Elegido' : 'Elegir');
		$h .= '<span class="eternal-node__btn-label">' . ope_eternal_esc($btn_label) . '</span>';
	}
	$h .= '</button>';
	return $h;
}

/**
 * Textos de ayuda mecánica para modales del wizard.
 *
 * @return array<string,array{title:string,body:string}>
 */
function ope_rol_mechanics_help()
{
	return array(
		'eternal-tree' => array(
			'title' => 'Cómo se lee un árbol Eternal (v3 "Forja")',
			'body' => '<p>Cada personaje combina <b>una Identidad</b> (tu filosofía de combate y recurso propio) y <b>una Familia de Arma</b> (cómo golpea tu arma). Cada árbol tiene <b>15 nodos</b> y eliges <b>5</b>. El árbol se muestra entero como una rejilla de <b>5 tiers × 3 corrientes</b>; haz clic en cualquier nodo para ver su ficha completa.</p>'
				. '<ul>'
				. '<li><b>5 filas = 5 Tiers</b>, cada uno se abre a un nivel fijo: <b>T1·Nv1, T2·Nv10, T3·Nv20, T4·Nv30, Pináculo·Nv45</b>. Cada tier te da <b>1 PT</b> para elegir <b>1 de sus 3 nodos</b>.</li>'
				. '<li><b>3 columnas = 3 Corrientes</b> (α/β/γ): las tres filosofías temáticas del árbol. No hay columna «núcleo»: el efecto base del arma/identidad está activo desde nivel 1, fuera del árbol.</li>'
				. '<li><b>No hay prerrequisitos verticales</b>: en cada tier eliges libremente cualquiera de las 3 corrientes. Puedes especializarte o mezclar.</li>'
				. '<li><b>Profundidad</b>: si ya tienes el nodo previo de la misma corriente, el nodo gana un efecto extra. <b>Afinidad</b> (pináculos): con ≥3 nodos de esa corriente se activa un bono superior. Premian especializar sin cerrar la mezcla.</li>'
				. '<li><b>Pasiva</b> = efecto permanente · <b>Habilitador</b> = te da el <i>derecho a crear</i> técnicas de ese tipo con PP (no la técnica hecha) · <b>Mini-sistema</b> = una mecánica propia con sus reglas · <b>Pináculo</b> = coronación de la corriente (eliges 1 de 3).</li>'
				. '<li><b>1 PT por tier</b>, 5 PT por árbol. Los PT son independientes de los PP de stats.</li>'
				. '</ul>'
				. '<p>En creación solo exploras (preview). Tras la aprobación del staff, el mismo árbol pasa a modo interactivo en ficha/progresión.</p>',
		),
		'identidad' => array(
			'title' => 'Identidad Eternal',
			'body' => '<p>La Identidad es el <b>porqué</b> combates: define tu recurso secundario (Mole, Apertura, Dominio…) y el estilo de juego (burst, tank, evasión…).</p>'
				. '<p>Hay 7 Identidades. Elige una; el árbol completo aparece debajo en vista previa. Más adelante gastarás Puntos de Talento (PT) en sus nodos.</p>',
		),
		'familia-arma' => array(
			'title' => 'Familia de Arma',
			'body' => '<p>La Familia fija el <b>cómo</b> combates: Filo, Contundente, Alcance, Distancia o Cuerpo. Cada una tiene su propio árbol Eternal y un efecto inherente (sangrado, rotura, control espacial…).</p>'
				. '<p>Después eliges el arma física Tier 1 de esa familia (espada, maza, arco…).</p>',
		),
		'virtudes' => array(
			'title' => 'Virtudes y Defectos',
			'body' => '<p>Debes equilibrar la suma a <b>exactamente 0</b> (p. ej. +5 y −5). No cuestan PP ni PD al elegirlas.</p>'
				. '<p>Algunas virtudes abren <b>sub-sistemas</b> (como Cyborg). Pulsa «?» junto a ellas para leer las reglas completas.</p>',
		),
		'cyborg' => array(
			'title' => 'Mecánica Cyborg',
			'body' => '<p><b>Al marcar esta virtud, tu personaje entra en el sub-sistema Cyborg</b> de forma permanente (salvo rechazo/staff).</p>'
				. '<p>Empiezas con <b>una modificación Tier I</b> en un slot: Brazo, Pierna, Ojo o Torso. No sustituye Haki, Akuma no Mi ni nodos Eternal: es una capa física adicional.</p>'
				. '<h3>Efectos Tier I (gratis con la virtud)</h3>'
				. '<ul>'
				. '<li><b>Brazo:</b> +(2 + Potencia) daño CcC con ese brazo.</li>'
				. '<li><b>Pierna:</b> +1 m de movimiento libre por turno.</li>'
				. '<li><b>Ojo:</b> detecta mentiras evidentes 1×/escena.</li>'
				. '<li><b>Torso:</b> +5% PV máximo.</li>'
				. '</ul>'
				. '<h3>Progresión posterior (cuesta PP)</h3>'
				. '<p>Subir Tier II–IV de un slot, o abrir slots nuevos, se hace por <b>trámite + PP</b> según tramo (igual que Dotes de Poder). Tier IV exige aprobación narrativa de staff.</p>'
				. '<p>Linajes con biología especial (p. ej. Gigante, Lunarian) necesitan justificación extra. El staff valida estética y trasfondo.</p>',
		),
		'raza' => array(
			'title' => 'Linaje (Factor Linaje)',
			'body' => '<p>Cada linaje tiene un <b>perfil de stats fijo</b>, sin elegir nada: puede incluir stats positivos y también <b>penalizaciones</b> que pagan ese poder extra. No hay un neto +4 obligatorio: cada linaje se ajusta a su lugar canónico (la mayoría neta +4, <b>Gigantes</b> neta +8 por ser el más grande y duro, y <b>Humanos</b> quedan a 0 pero ganan versatilidad). Además, tu linaje da <b>acceso</b> a comprar (con Puntos de Linaje) sus <b>Rasgos Raciales</b> y su <b>dote innata</b>; nada es gratis: todo se financia con Defectos a suma cero. Detalle completo en <code>FACTOR-LINAJE.md</code>.</p>',
		),
		'stats' => array(
			'title' => 'Atributos (PS)',
			'body' => '<p>Repartes <b>20 Puntos de Stat</b> sobre 8 atributos (FUE, RES, AGI, INT, PER, TEM, VOL, CAR). Base 1; tope 5 con puntos de creación (antes del perfil de linaje fijo, que puede sumar o restar según tu linaje).</p>'
				. '<p>El nivel (1–50) sube al comprar stats con PP en juego. Los <b>Puntos de Talento (PT)</b> Eternal son aparte: recibes 1 PT de Arma y 1 PT de Identidad en los niveles <b>1, 10, 20, 30 y 45</b> (5+5 nodos en total).</p>',
		),
	);
}

// ─────────────────────────────────────────────────────────────────────────
// Persistencia de nodos elegidos por PJ (rol_pj_eternal) + presupuesto PT
// ─────────────────────────────────────────────────────────────────────────

/**
 * Umbrales de nivel de cada tier (cadencia PT v3 "Forja").
 * Tier 1..5 → Nv 1/10/20/30/45.
 *
 * @return int[]
 */
function ope_eternal_tier_umbrales()
{
	return array(1, 10, 20, 30, 45);
}

/**
 * Número de tiers desbloqueados por árbol según el nivel del personaje.
 * Cada árbol (Arma / Identidad) abre 1 nodo por tier al alcanzar el umbral.
 *
 * @param int $nivel
 * @return int  0..5
 */
function ope_eternal_tiers_desbloqueados($nivel)
{
	$n = (int) $nivel;
	$c = 0;
	foreach (ope_eternal_tier_umbrales() as $u) {
		if ($n >= $u) {
			$c++;
		}
	}
	return $c;
}

/**
 * Nodos Eternal elegidos por un personaje, agrupados por árbol.
 *
 * @param int $pid
 * @return array<string,string[]>  arbol => [nodo_id,...]
 */
function ope_eternal_picks($pid)
{
	global $db;
	$pid = (int) $pid;
	$out = array();
	if ($pid < 1 || !$db->table_exists('rol_pj_eternal')) {
		return $out;
	}
	$q = $db->simple_select('rol_pj_eternal', 'arbol, nodo_id', "pid = {$pid}");
	while ($row = $db->fetch_array($q)) {
		$out[(string) $row['arbol']][] = (string) $row['nodo_id'];
	}
	return $out;
}

/**
 * Presupuesto de PT y estado de elección para los dos árboles del personaje.
 *
 * @param int    $pid
 * @param int    $nivel
 * @param string $arbol_identidad
 * @param string $arbol_arma
 * @return array
 */
function ope_eternal_pt_budget($pid, $nivel, $arbol_identidad, $arbol_arma)
{
	$tiers = ope_eternal_tiers_desbloqueados($nivel);
	$picks = ope_eternal_picks($pid);
	$count = function ($arbol) use ($picks) {
		return ($arbol !== '' && isset($picks[$arbol])) ? count($picks[$arbol]) : 0;
	};
	$id_usados = $count($arbol_identidad);
	$ar_usados = $count($arbol_arma);
	return array(
		'nivel'               => (int) $nivel,
		'tiers_desbloqueados' => $tiers,
		'identidad' => array(
			'arbol'       => (string) $arbol_identidad,
			'disponibles' => $tiers,
			'usados'      => $id_usados,
			'restantes'   => max(0, $tiers - $id_usados),
		),
		'arma' => array(
			'arbol'       => (string) $arbol_arma,
			'disponibles' => $tiers,
			'usados'      => $ar_usados,
			'restantes'   => max(0, $tiers - $ar_usados),
		),
	);
}

/**
 * Recalcula pt_gastados = nº total de nodos Eternal del PJ.
 *
 * @param int $pid
 * @return void
 */
function ope_eternal_sync_pt_gastados($pid)
{
	global $db;
	$pid = (int) $pid;
	if ($pid < 1 || !$db->table_exists('rol_pj_eternal') || !$db->field_exists('pt_gastados', 'rol_personajes')) {
		return;
	}
	$q = $db->simple_select('rol_pj_eternal', 'COUNT(*) AS c', "pid = {$pid}");
	$c = (int) $db->fetch_field($q, 'c');
	$db->update_query('rol_personajes', array('pt_gastados' => $c), "pid = {$pid}");
}

/**
 * Elige (compra) un nodo Eternal para un personaje con validación completa:
 * el árbol pertenece al PJ, el tier está desbloqueado por nivel, 1 nodo por
 * tier y exclusión de pináculos (campo excluye).
 *
 * @param int    $pid
 * @param string $arbol
 * @param string $nodo_id
 * @return array{ok:bool,msg:string}
 */
function ope_eternal_pick($pid, $arbol, $nodo_id)
{
	global $db;
	$pid = (int) $pid;
	$arbol = trim((string) $arbol);
	$nodo_id = trim((string) $nodo_id);
	if ($pid < 1 || $arbol === '' || $nodo_id === '') {
		return array('ok' => false, 'msg' => 'Datos incompletos.');
	}
	if (!$db->table_exists('rol_pj_eternal') || !$db->table_exists('rol_personajes')) {
		return array('ok' => false, 'msg' => 'Sistema Eternal no disponible.');
	}

	$q = $db->simple_select('rol_personajes', 'pid, estado, nivel, datos', "pid = {$pid}", array('limit' => 1));
	if (!$db->num_rows($q)) {
		return array('ok' => false, 'msg' => 'Personaje no encontrado.');
	}
	$pj = $db->fetch_array($q);
	if ((string) ($pj['estado'] ?? '') !== 'aprobado') {
		return array('ok' => false, 'msg' => 'Solo personajes aprobados pueden elegir nodos.');
	}

	$datos = json_decode((string) ($pj['datos'] ?? ''), true);
	if (!is_array($datos)) {
		$datos = array();
	}
	$arboles_pj = array(
		(string) ($datos['arbol_identidad'] ?? ''),
		(string) ($datos['arbol_arma'] ?? ''),
	);
	if (!in_array($arbol, $arboles_pj, true)) {
		return array('ok' => false, 'msg' => 'Ese árbol no pertenece a tu personaje.');
	}

	$tree = ope_eternal_load($arbol);
	if (!$tree || empty($tree['nodos'])) {
		return array('ok' => false, 'msg' => 'Árbol no encontrado.');
	}

	$nodo = null;
	$by_id = array();
	foreach ($tree['nodos'] as $n) {
		$by_id[(string) $n['id']] = $n;
		if ((string) $n['id'] === $nodo_id) {
			$nodo = $n;
		}
	}
	if (!$nodo) {
		return array('ok' => false, 'msg' => 'Nodo no válido.');
	}

	$tier = (int) ($nodo['tier'] ?? 0);
	$nivel = (int) ($pj['nivel'] ?? 0);
	$tiers_ok = ope_eternal_tiers_desbloqueados($nivel);
	if ($tier < 1 || $tier > $tiers_ok) {
		$nv = ope_eternal_tier_nivel($tier);
		return array('ok' => false, 'msg' => "Ese tier se desbloquea a Nv {$nv}.");
	}

	$picks = array();
	$rq = $db->simple_select('rol_pj_eternal', 'nodo_id', "pid = {$pid} AND arbol = '" . $db->escape_string($arbol) . "'");
	while ($r = $db->fetch_array($rq)) {
		$picks[] = (string) $r['nodo_id'];
	}
	if (in_array($nodo_id, $picks, true)) {
		return array('ok' => false, 'msg' => 'Ya tienes ese nodo.');
	}
	foreach ($picks as $pk) {
		if (isset($by_id[$pk]) && (int) ($by_id[$pk]['tier'] ?? 0) === $tier) {
			return array('ok' => false, 'msg' => 'Ya elegiste un nodo en este tier. Retira el anterior primero.');
		}
	}

	$excluye = isset($nodo['excluye']) && is_array($nodo['excluye']) ? $nodo['excluye'] : array();
	foreach ($excluye as $ex) {
		if (in_array((string) $ex, $picks, true)) {
			return array('ok' => false, 'msg' => 'Ese pináculo excluye a otro que ya elegiste.');
		}
	}

	$db->insert_query('rol_pj_eternal', array(
		'pid'          => $pid,
		'arbol'        => $db->escape_string($arbol),
		'nodo_id'      => $db->escape_string($nodo_id),
		'dateline'     => TIME_NOW,
		'rango_actual' => 1,
	));
	ope_eternal_sync_pt_gastados($pid);

	return array('ok' => true, 'msg' => 'Nodo elegido: ' . (string) ($nodo['nombre'] ?? $nodo_id) . '.');
}

/**
 * Retira un nodo Eternal previamente elegido.
 *
 * @param int    $pid
 * @param string $arbol
 * @param string $nodo_id
 * @return array{ok:bool,msg:string}
 */
function ope_eternal_unpick($pid, $arbol, $nodo_id)
{
	global $db;
	$pid = (int) $pid;
	$arbol = trim((string) $arbol);
	$nodo_id = trim((string) $nodo_id);
	if ($pid < 1 || $arbol === '' || $nodo_id === '') {
		return array('ok' => false, 'msg' => 'Datos incompletos.');
	}
	if (!$db->table_exists('rol_pj_eternal')) {
		return array('ok' => false, 'msg' => 'Sistema Eternal no disponible.');
	}
	$db->delete_query(
		'rol_pj_eternal',
		"pid = {$pid} AND arbol = '" . $db->escape_string($arbol) . "' AND nodo_id = '" . $db->escape_string($nodo_id) . "'"
	);
	ope_eternal_sync_pt_gastados($pid);
	return array('ok' => true, 'msg' => 'Nodo retirado.');
}

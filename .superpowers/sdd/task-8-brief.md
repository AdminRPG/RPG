### Task 8: Botón "Editar ficha" en `personajes.php` para personajes moderados

**Files:** Modify: `personajes.php`

**Step 1: En personajes.php, después de cargar $personajes, añadir detección de moderados:**

```php
$personajes_moderados = array();
if ($loggedin && $db->table_exists('rol_mensajes')) {
    foreach ($personajes as $pj) {
        if ($pj['estado'] === 'revision') {
            $pid_i = (int)$pj['pid'];
            $mc = $db->query("
                SELECT COUNT(*) as cnt FROM " . TABLE_PREFIX . "rol_mensajes
                WHERE destino_pid = {$pid_i} AND leido = 0
                AND asunto LIKE 'Moderación:%'
            ");
            if ((int)$db->fetch_field($mc, 'cnt') > 0) {
                $personajes_moderados[$pid_i] = true;
            }
        }
    }
}
```

**Step 2: En el bucle de renderizado de cada pjcard (donde está el .pjcard-foot), añadir botón:**

```php
<?php if ($pj['estado'] === 'revision' && isset($personajes_moderados[(int)$pj['pid']])): ?>
  <a href="<?php echo $bburl; ?>/crear-personaje.php?editar=<?php echo (int)$pj['pid']; ?>" class="btn btn-hot btn-sm">Editar ficha</a>
  <span style="color:var(--h6);font-family:var(--mono);font-size:.6rem;display:block;margin-top:4px">Cambios solicitados por el staff</span>
<?php endif; ?>
```

**Step 3: En crear-personaje.php, añadir detección de ?editar=PID al inicio (después del bloque de variables):**

```php
$editando_pid = (int)($mybb->get_input('editar', MyBB::INPUT_INT));
$editando = null;
if ($editando_pid > 0 && $loggedin && $db->table_exists('rol_personajes')) {
    $eq = $db->simple_select('rol_personajes', '*', "pid = {$editando_pid} AND uid = {$uid}", array('limit' => 1));
    if ($db->num_rows($eq)) {
        $editando = $db->fetch_array($eq);
        // Marcar mensajes de moderación como leídos
        if ($db->table_exists('rol_mensajes')) {
            $db->update_query('rol_mensajes', array('leido' => 1), "destino_pid = {$editando_pid} AND asunto LIKE 'Moderación:%'");
        }
    }
}
```

Y mostrar un aviso arriba del wizard: "Estás editando la ficha de [nombre]. Los cambios se enviarán a revisión de nuevo."

Verify both pages load without errors.

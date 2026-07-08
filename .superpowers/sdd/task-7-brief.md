### Task 7: Actualizar `zona-staff.php` — enlace a revisar-personaje.php + contador

**Files:** Modify: `zona-staff.php`

**Changes:**

1. Antes de la definición del array `$zonas`, añadir contador de pendientes:
```php
$pendientes_count = 0;
if ($db->table_exists('rol_personajes')) {
    $pc = $db->simple_select('rol_personajes', 'COUNT(*) as cnt', "estado = 'revision'");
    $pendientes_count = (int)$db->fetch_field($pc, 'cnt');
}
```

2. En STF-01 (línea ~55-58), cambiar `'link' => 'personajes.php'` a `'link' => 'revisar-personaje.php'`, y cambiar el meta para que muestre el contador:
```php
'meta' => $pendientes_count . ' pendiente(s)',
```

3. Después del párrafo `.zs-intro`, añadir banner de aviso si hay pendientes:
```php
<?php if ($pendientes_count > 0): ?>
  <div style="margin-bottom:14px;padding:12px 16px;border:2px solid var(--ember);background:var(--iron-plate);display:flex;align-items:center;justify-content:space-between;gap:12px">
    <span style="font-family:var(--mono);font-size:.68rem;color:var(--ember-hi)"><b style="color:var(--ember)"><?php echo $pendientes_count; ?></b> expediente(s) pendiente(s) de revisión</span>
    <a href="<?php echo $bburl; ?>/revisar-personaje.php" class="btn btn-hot btn-sm">Revisar ahora</a>
  </div>
<?php endif; ?>
```

Verify at http://localhost/iforge/zona-staff.php

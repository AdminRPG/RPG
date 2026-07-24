---
name: mybb-plugin-dev
description: Convenciones avanzadas para crear o modificar el plugin ope_rol y módulos PHP del backend de One Piece: Eternal (capas, hooks, base de datos, consultas sin MCP mysql, graphify).
---

# Desarrollo Backend y Plugin MyBB (`ope_rol`)

Este documento define la guía técnica para desarrollar y extender el plugin de MyBB y los subsistemas PHP en `inc/ope_rol/` para **One Piece: Eternal**.

---

## 1. Arquitectura Modular por Capas (`inc/ope_rol/`)

El backend del foro no reside en un monolito inmanejable; se organiza en capas limpias dentro de `inc/ope_rol/`, inicializadas mediante el punto de entrada `inc/ope_rol/bootstrap.php`:

```
inc/ope_rol/
├── bootstrap.php            # Punto de entrada principal y carga de hooks
├── core/                    # Configuración base, constantes y cargador de datos
│   └── system.php           # Helper de bootstrap y gestión de contexto (inc/ope_rol/core/system.php)
├── catalogos/               # Tablas maestras de datos estáticos/lore
│   └── linaje.php           # Catálogo de razas y factores linaje (inc/ope_rol/catalogos/linaje.php)
├── dominio/                 # Entidades de personajes y lógica de creación
│   └── creacion.php         # Reglas de creación de ficha (inc/ope_rol/dominio/creacion.php)
├── sistemas/                # Motores de cálculo RPG (Haki, Frutas, PL, Renombre)
│   └── haki.php             # Lógica de niveles y habilidades de Haki (inc/ope_rol/sistemas/haki.php)
├── mundo/                   # Navegación, mapas, barcos y clima
│   └── islas_cat.php        # Catálogo de islas y coordenadas (inc/ope_rol/mundo/islas_cat.php)
└── tramites/                # Sistema de solicitudes y moderación Staff
    └── tramites.php         # Gestión de trámites (inc/ope_rol/tramites/tramites.php)
```

---

## 2. Convenciones de Nombres y Prefijo Obligatorio (`ope_`)

Para evitar colisiones en el namespace global de MyBB, **toda función, constante y función de dominio debe usar el prefijo `ope_` / `ope_rol_`**:

- **Funciones de renderizado/globales**: `ope_rol_head_base()`, `ope_rol_navbar_html()` (definidas en `inc/plugins/ope_rol.php` / `inc/ope_rol_data.php`).
- **Funciones de catálogos y sistemas**: `ope_rol_razas()`, `ope_rol_rasgos_generales()` (en `inc/ope_rol/catalogos/linaje.php`).
- **Funciones de dominio**: `ope_pj_hibridacion()`, `ope_pj_validar_linaje()` (en `inc/ope_rol/dominio/creacion.php`).

> ⚠️ **PROHIBIDO**: Reintroducir prefijos legacy (`gbe_`, `gbe-`, `Granblue`, `GBF`, `I-Forge` o `iforge`) en código nuevo.

---

## 3. Base de Datos sin MCP MySQL

> [!IMPORTANT]
> **NO existe un MCP de MySQL en el entorno**. Para consultar la base de datos de MyBB, verificar tablas o testear consultas, utiliza la CLI de PHP o la consola mediante `run_command`.

### 3.1. Consulta vía CLI de PHP (`run_command`)
Ejecuta fragmentos PHP de forma directa cargando el entorno de MyBB:

```bash
# Ejemplo 1: Mostrar las tablas registradas con el prefijo del proyecto
php -r "define('IN_MYBB',1); require_once './global.php'; global \$db; \$q = \$db->query('SHOW TABLES LIKE \'mybb_ope_%\''); while(\$r = \$db->fetch_array(\$q)) print_r(\$r);"

# Ejemplo 2: Inspeccionar la estructura de la tabla de linajes
php -r "define('IN_MYBB',1); require_once './global.php'; global \$db; \$q = \$db->query('DESCRIBE mybb_ope_linajes'); while(\$r = \$db->fetch_array(\$q)) print_r(\$r);"
```

### 3.2. Consulta directa vía cliente MySQL de consola
Si el ejecutable `mysql` está en el PATH del sistema:

```bash
mysql -u root -p mybb_forum -e "SELECT id, nombre FROM mybb_ope_linajes LIMIT 5;"
```

---

## 4. Errores Comunes en Backend PHP

1. **Inyección SQL por omitir `$db->escape_string()`**:
   - ❌ *Error*: `$db->query("SELECT * FROM mybb_users WHERE username = '{$username}'");`
   - ✅ *Correcto*: `$name_clean = $db->escape_string($username); $db->query("SELECT * FROM ... WHERE username = '{$name_clean}'");`

2. **Asumir el prefijo `mybb_` hardcoded**:
   - ❌ *Error*: `$db->query("SELECT * FROM mybb_ope_personajes");`
   - ✅ *Correcto*: `$db->query("SELECT * FROM " . TABLE_PREFIX . "ope_personajes");`

3. **Olvidar declarar las globales `$db` y `$mybb` en callbacks de hooks**:
   - ❌ *Error*: `function ope_mi_hook() { $q = $db->query(...); }` (causa Fatal Error `NullPointerException` en `$db`).
   - ✅ *Correcto*: `function ope_mi_hook() { global $db, $mybb; $q = $db->query(...); }`

4. **Operaciones múltiples en base de datos sin transacciones**:
   - ❌ *Error*: Ejecutar 5 `UPDATE` seguidos en un trámite sin rollback si falla el tercero.
   - ✅ *Correcto*: Usar `$db->write_query("START TRANSACTION");` y `$db->write_query("COMMIT");` (o `ROLLBACK` en `catch`).

---

## 5. Checklist de Verificación Tras Modificar Backend

Antes de dar por completada una modificación en PHP:

- [ ] Sintaxis PHP sin errores: `php -l inc/ope_rol/tu_archivo.php`
- [ ] Nombres de función y clases usan el prefijo `ope_`.
- [ ] Parámetros dinámicos escapados con `$db->escape_string()` o casteados a `(int)`.
- [ ] Actualización del grafo de conocimiento AST:
  ```bash
  py -m graphify update .
  ```
- [ ] Probar la consulta o endpoint modificado ejecutando `php` en CLI o navegando por la vista correspondiente.

# Restaurar la base de datos — One Piece: 7 Seas

Este documento explica cómo restaurar `rpg_forum` a su **estado limpio de referencia**
(sin contenido inicial: sin islas, sin NPCs, sin lore, sin misiones — con la estructura
de foros, el tema, los catálogos mecánicos y el plugin `ope_rol` activos).

## El dump

| | |
|---|---|
| **Archivo** | `backups/rpg_forum_limpio_2026-08-25.sql` |
| **Base de datos** | `rpg_forum` |
| **Tamaño** | ~1.8 MB (129 tablas) |
| **Estado** | Foro "One Piece: 7 Seas" · 21 foros (Los Mares con 8 regiones vacías + Navegación/Alta Mar + Off Topic) · tema Eternal activo · plugin `ope_rol` activo · usuarios `admin` y `OPE Eternal` |

> `backups/` está en `.gitignore`: el dump vive solo en local, no se commitea.
> El estado de referencia del **código** (plantillas, CSS, páginas) es el propio repo.

## Restaurar (Windows / Laragon)

```bash
# 1. (Opcional) recrear la base limpia desde cero
mysql -u root --host=127.0.0.1 -e "DROP DATABASE IF EXISTS rpg_forum; CREATE DATABASE rpg_forum CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 2. Importar el dump — SIEMPRE con charset utf8mb4 (si no, los acentos se corrompen)
mysql --default-character-set=utf8mb4 -u root --host=127.0.0.1 rpg_forum < backups/rpg_forum_limpio_2026-08-25.sql

# 3. Limpiar caches de MyBB para que todo se reconstruya en la siguiente petición
mysql -u root --host=127.0.0.1 rpg_forum -e "DELETE FROM mybb_datacache WHERE title IN ('templates','themes','default_theme','forums','stats','settings','plugins');"

# 4. Recargar la portada (Ctrl+Shift+R) y comprobar que responde
curl -s -o /dev/null -w "%{http_code}" http://rpg.test/
```

## Qué NO incluye el dump (archivos de entorno)

El dump solo contiene la base de datos. Estos archivos son **por entorno** y deben
existir en el workspace (no van en git):

- `inc/config.php` — conexión a MySQL (la genera el instalador de MyBB)
- `inc/settings.php` — ajustes compilados del ACP (se regenera desde `mybb_settings` al guardar en el ACP; si falta, el foro no arranca)
- `inc/ope_rol/config/viaje_ai.php` — claves locales de la IA de viajes

Si falta `inc/settings.php`, entra al ACP (`http://rpg.test/admin/`) y guarda cualquier
ajuste para regenerarlo, o repite la instalación de MyBB.

## Comprobaciones tras restaurar

- Portada con **Los Mares** (8 tarjetas-región vacías) y **Off Topic**, sin título "Bitácora del Puerto"
- `<title>` de las páginas con "One Piece: 7 Seas"
- Plugins activos: `ope_rol` (panel ACP → Configuración → Plugins)
- Acceso: `admin` / la contraseña vigente en el dump

## Volver a generar el dump

Cuando cambies el estado de referencia (estructura de foros, tema, catálogos), regenera:

```bash
mkdir -p backups
mysqldump --default-character-set=utf8mb4 --single-transaction --routines --triggers \
  --add-drop-table --set-gtid-purged=OFF -u root --host=127.0.0.1 rpg_forum \
  > backups/rpg_forum_limpio_$(date +%F).sql
```

> Importante: el CLI de MySQL usa por defecto otro charset y corrompe los acentos.
> Siempre pasa `--default-character-set=utf8mb4` tanto al dumpear como al restaurar.

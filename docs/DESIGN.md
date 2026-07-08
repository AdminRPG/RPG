---
version: 2.1
name: One Piece Eternal
palette: océano de aventura
stylesheet: docs/themes/ope.css
plugin: inc/plugins/ope_rol.php
---

# DESIGN.md — One Piece Eternal (fuente de verdad técnica)

Este documento describe **cómo se implementan** los estilos en el proyecto: los
tokens reales, las fuentes reales y —sobre todo— la **arquitectura CSS de fuente
única** que evita que cada página tenga su propia copia de estilos.

Para la visión narrativa/estética extendida (referentes de One Piece, patrones
como el bounty poster, anti-AI checklist) ver `docs/DESIGN-ONE-PIECE-ETERNAL.md`.
Este archivo manda en lo técnico: si algo contradice a los valores reales de
`docs/themes/ope.css`, gana `ope.css` y hay que actualizar este documento.

---

## 0. Regla de oro: una sola fuente de verdad, cero estilos inline

**Sí, tu superior tiene razón.** Los `<style>` inline (o repetidos página a
página) son malos porque:

1. **No hay cohesión** — cada página deriva su propia copia de la paleta, la
   navbar o los botones; con el tiempo divergen y el sitio se ve distinto en
   cada zona (es justo lo que nos pasaba con la navbar).
2. **No se cachean** — el navegador re-descarga los mismos bytes en cada página
   en vez de reutilizar un `.css` cacheado.
3. **Duplican el mantenimiento** — un cambio de color obliga a editar N archivos
   y es fácil olvidar uno.
4. **Colisionan** — dos páginas con reglas globales (`.card`, `body{…}`) se pisan.

**Cómo lo resolvemos aquí:** todo el CSS vive en **`docs/themes/ope.css`** (una
sola hoja, cacheada, sincronizada a la BD del tema MyBB). Ninguna página PHP
lleva `<style>`. La cohesión se garantiza con tres mecanismos:

| Mecanismo | Dónde | Qué garantiza |
|-----------|-------|---------------|
| Hoja única `ope.css` | `docs/themes/ope.css` | Todos los tokens y componentes en un sitio |
| `ope_rol_head_base()` | `inc/plugins/ope_rol.php:323` | Todas las páginas cargan las mismas fuentes + `ope.css` |
| `ope_rol_navbar_html()` / `ope_rol_navbar_css()` | `inc/plugins/ope_rol.php:190,277` | La navbar es idéntica en todas las zonas (HTML + CSS generados una vez) |
| Scope por página `body.ope-pg-<pagina>` | dentro de `ope.css` | Estilos específicos de una página sin colisionar con otras |

**Prohibido:** reintroducir `<style>…</style>` en cualquier `.php`. Si una página
necesita CSS propio, va en `ope.css` bajo su clase `body.ope-pg-<pagina>`.

---

## 1. Arquitectura CSS

```
docs/themes/ope.css                 ← ÚNICA hoja de estilos (tokens + componentes + páginas)
inc/plugins/ope_rol.php
  ├─ ope_rol_head_base()            ← <head>: preconnect fuentes + <link> a ope.css
  ├─ ope_rol_navbar_html()          ← HTML de la navbar (mismo markup en todas partes)
  └─ ope_rol_navbar_css()           ← CSS de la navbar, scopeado bajo #ope-navbar
```

### 1.1 Cómo se estructura `ope.css`

1. **`:root`** — todos los tokens (paleta, fuentes). Ver §2 y §3.
2. **Base** — reset, `body`, enlaces, selección, foco.
3. **Componentes globales** — breadcrumb, `.ope-wrap`, botones, tarjetas, etc.
4. **Páginas autónomas** — bloque final, cada una scopeada bajo
   `body.ope-pg-<pagina>` (personajes, ficha, tramites, guias, zona-staff,
   crear-personaje, alertas, mensajes, revisar-personaje).

### 1.2 Cómo una página PHP consume los estilos

```php
<!doctype html>
<html lang="es">
<head>
  <?php echo ope_rol_head_base(); /* fuentes + ope.css, nada de <style> */ ?>
  <title>One Piece Eternal · …</title>
</head>
<body class="ope-pg-personajes">   <!-- clase de scope obligatoria -->
  <?php echo ope_rol_navbar_html(); /* navbar única (HTML + su CSS) */ ?>
  …
</body>
</html>
```

### 1.3 Añadir estilos a una página sin romper la regla

En `ope.css`, al final, dentro del bloque de la página:

```css
/* ---- personajes.php ---- */
body.ope-pg-personajes .mi-modulo{ … }
```

Nunca escribas `.mi-modulo{…}` sin el prefijo `body.ope-pg-…`: sin scope puede
colisionar con otra página o con las plantillas de MyBB.

### 1.4 Despliegue (repo → BD/caché del tema)

`ope.css` y las plantillas viven en el repo y se sincronizan a MyBB:

```bash
php scripts/sync-theme.php import   # repo → BD + cache/themes/theme13/ope.css
php scripts/sync-theme.php verify   # comprobar que repo y BD coinciden
```

Editar en Admin CP y no sincronizar rompe la fuente única. Flujo correcto:
editar `ope.css` → `import` → `verify` → commit.

---

## 2. Tokens de color (valores reales de `ope.css`)

Paleta **océano de aventura**: base océano profundo con acentos cálidos
(melocotón / amanecer). Definida en `:root`.

### 2.1 Paleta cruda One Piece

| Token | Hex | Nombre |
|-------|-----|--------|
| `--op-sky` | `#41A4E0` | Azul Cielo |
| `--op-peach` | `#FFCB93` | Nube Melocotón |
| `--op-dawn` | `#FFE9A3` | Brillo de Amanecer |
| `--op-ocean` | `#10477B` | Azul Océano Profundo |
| `--op-tide` | `#458CC5` | Azul Marea |
| `--op-wood` | `#8C5936` | Madera de Navío |

### 2.2 Roles semánticos

| Token | Hex | Uso |
|-------|-----|-----|
| `--iron` | `#0b3157` | Fondo base / casco profundo |
| `--iron-plate` | `#10477B` | Paneles, headers de sección |
| `--iron-hi` | `#175a95` | Elevación / hover de panel |
| `--iron-edge` | `#082742` | Bordes oscuros, velo de fondo |
| `--rivet` | `#3d6f9e` | Separadores, detalles |
| `--concrete` / `--concrete-2` | `#eef6fc` / `#dbecf9` | Superficies de lectura (papel de mapa) |
| `--ink` / `--ink-2` | `#0a2f52` / `#1c5285` | Texto sobre superficies claras |
| `--paper` / `--paper-dim` | `#eef7ff` / `#c6ddf3` | Texto sobre fondos oscuros |
| `--ash` | `#8ba9c9` | Texto terciario / metadatos |
| `--ember` / `--ember-hi` | `#FFCB93` / `#FFE9A3` | **Acento primario** (acción, enlaces, hover) |
| `--patina` / `--patina-hi` | `#41A4E0` / `#63b8ea` | Acento secundario (cielo marino) |
| `--gold` / `--gold-hi` / `--gold-deep` | `#FFCB93` / `#FFE9A3` / `#e0a866` | Marca / rótulos destacados |
| `--sea` / `--sea-hi` / `--sea-deep` | `#458CC5` / `#41A4E0` / `#10477B` | Azules de estructura |
| `--red` / `--crack` | `#e63b2e` | Peligro, alertas |

### 2.3 Escala de poder (stats), océano → amanecer

`--h1 #10477B` · `--h2 #2f6ea8` · `--h3 #458CC5` · `--h4 #41A4E0` ·
`--h5 #63b8ea` · `--h6 #FFCB93` · `--h7 #ffdcae` · `--h8 #FFE9A3` · `--h9 #fff6d8`

**Regla:** usa siempre los tokens, nunca hex sueltos. Un color nuevo se añade
primero como variable en `:root` con un porqué; si no puedes justificarlo en el
mundo One Piece, no entra.

---

## 3. Tipografía (valores reales)

| Token | Familia | Uso |
|-------|---------|-----|
| `--disp` | `'Big Shoulders Display', Impact, sans-serif` | Rótulos, títulos de sección, banner |
| `--mono` | `'Space Mono', Menlo, Consolas, monospace` | Datos, stats, labels, breadcrumb |
| `--body` | `'Archivo', -apple-system, 'Segoe UI', sans-serif` | Cuerpo, descripciones, posts |

Las fuentes se cargan **solo** desde `ope_rol_head_base()` (preconnect + Google
Fonts). No añadas `<link>` de fuentes en páginas sueltas.

**Reglas:** `uppercase` solo en rótulos/labels/badges, nunca en cuerpo ni en
enlaces normales. Máximo 3 familias por pantalla (ya cubiertas por los tokens).

---

## 4. Fondo global

El `body` usa `images/ope/fondo.jpg` a pantalla completa (`fixed`, `cover`) con
un velo degradado oceánico encima para legibilidad. Se aplica con propiedades
separadas (`background-image`, `-repeat`, `-position`, `-size`, `-attachment`),
**nunca** con el atajo `background:` + `!important` (aplasta la imagen a `none`).
Este fondo es global: aplica a todas las páginas por venir del `body` de `ope.css`.

---

## 5. Navbar (fuente única)

- HTML: `ope_rol_navbar_html()` — mismo markup en toda página (logo, links,
  dropdown de usuario). **No** incluye "Mis personajes".
- CSS: `ope_rol_navbar_css()`, scopeado bajo `#ope-navbar` para alta
  especificidad y consistencia total de tamaños/orientación entre zonas.
- Nunca dupliques reglas `.ope-nav-*` en `ope.css` ni en PHP.

---

## 6. Checklist antes de commitear UI

- [ ] Ninguna página `.php` contiene `<style>`.
- [ ] La página llama a `ope_rol_head_base()` y a `ope_rol_navbar_html()`.
- [ ] El `<body>` tiene su clase `ope-pg-<pagina>` si tiene CSS propio.
- [ ] Los estilos nuevos están en `ope.css`, con tokens (no hex sueltos) y
      scopeados bajo `body.ope-pg-<pagina>` si son específicos de una página.
- [ ] Ejecutado `php scripts/sync-theme.php import` y `verify` sin diferencias.
- [ ] Commit de `docs/themes/ope.css` (+ PHP/plantillas tocadas).

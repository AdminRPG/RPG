# Protocolo de Agentes, Herramientas y Paridad Visual — One Piece: Eternal

Este documento constituye la **referencia obligatoria de herramientas y protocolo de portado visual** para todos los asistentes e IDEs de IA (Cursor, OpenCode, Antigravity/Gemini).

---

## 1. MCPs y Herramientas Disponibles

### 1.1. Servidores MCP Configurados
Actualmente, el único servidor MCP conectado y configurado es:

- **`stitch`** (`https://stitch.googleapis.com/mcp`): Servidor MCP para generación y gestión de pantallas UI y sistemas de diseño (`apply_design_system`, `create_design_system`, `create_design_system_from_design_md`, `generate_screen_from_text`, `generate_variants`, `upload_design_md`, etc.).

### 1.2. Herramientas Nativas Integradas del Agente
El agente cuenta con las siguientes herramientas nativas directamente integradas:

- **`run_command`**: Ejecución de comandos de terminal PowerShell (*Usar `;` para separar comandos, NUNCA `&&`*).
- **`view_file`**: Lectura de archivos (código, imágenes, PDF, vídeo, audio).
- **`replace_file_content`**: Edición de bloques contiguos de código.
- **`multi_replace_file_content`**: Edición atómica de múltiples bloques no contiguos en el mismo archivo.
- **`write_to_file`**: Creación de nuevos archivos en el sistema.
- **`list_dir`**: Listado de directorios.
- **`grep_search`**: Búsqueda mediante expresiones regulares con Ripgrep.
- **`browser_subagent`**: Navegante de Playwright integrado para pruebas UI end-to-end, interacción web y captura de pantalla/vídeo WebP.
- **`generate_image`**: Generador de assets visuales e ilustraciones por IA.
- **`search_web` / `read_url_content`**: Búsqueda e inspección de contenido web en Markdown.
- **`schedule` / `manage_task`**: Gestión de tareas en segundo plano y temporizadores.

> [!CAUTION]
> **MCPs INEXISTENTES — NO INTENTAR INVOCAR**:
> - ❌ **`MCP mysql`**: NO EXISTE. Para interactuar con la base de datos o validar tablas, se debe usar la CLI de PHP (`php -r "..."`) ejecutando el motor `$db` de MyBB o consultas vía `run_command`.
> - ❌ **`MCP playwright` (como servidor MCP independiente)**: NO EXISTE. Toda navegación e inspección visual en navegador debe realizarse obligatoriamente mediante la herramienta nativa `browser_subagent`.
> - ❌ **`MCP firecrawl`**: NO EXISTE. Para leer documentación web o URLs, usar `read_url_content` o `search_web`.

---

## 2. Portado visual OPE — NO portes a medias

Fuente de prototipos: `docs/Prototypes/Granblue/`.

### 2.1. Regla de oro

Un prototipo aprobado = **sistema completo**. Copiar solo el hero/carrusel **no** es portar el index.

### 2.2. Las 5 capas (todas obligatorias)

1. **Estructura** — XML/PHP: `body.ope-index`, secciones `.ope-section`, bento, wrappers.
2. **Tokens** — `:root` / `--ope-*` en `ope.css`.
3. **Overrides legacy** — bajo scope correcto, anular `.ope-panel`, `.ope-block-cat`, `#ope-navbar` OP.
4. **Head/fuentes** — `ope_rol_head_base()` + `headerinclude` (Cinzel/Cormorant/Spectral).
5. **Datos** — `index.php` lore, títulos, slugs Cielo.

### 2.3. Antes de marcar done

```bash
php scripts/sync-theme.php import
php scripts/sync-theme.php verify    # OK CSS: in sync
php scripts/check-inline-styles.php
```

- Hard refresh navegador (`cache/themes/theme13/ope.css`).
- Comparar lado a lado con `docs/Prototypes/Granblue/index.html` (scroll completo).
- Actualizar `docs/DESIGN-ONE-PIECE-ETERNAL.md` si cambia estado.

### 2.4. Anti-patrones

- Hero OPE + tablón OP brutalista (sombras `4px 4px 0 #000`).
- Botones **pill/cápsula** (`border-radius: 24px–30px`) — usar **8px** rectangular (`docs/DESIGN-ONE-PIECE-ETERNAL.md` §4.4).
- Discord como bloque azul sólido — usar tarjeta clara + borde + icono marca.
- Solo editar `ope-index.xml` sin overrides `body.ope-index` en `ope.css`.
- Solo fuentes en tema, no en `ope_rol.php`.
- Declarar “portado” sin QA visual.

### 2.5. Portada — mínimo

`body.ope-index` · hero 100vh · gaceta bento · overrides paneles · categorías `.ope-section` · harbor · navbar clara.

---

## 3. Documentación Relacionada y Referencias Cruzadas

Para mantener la coherencia en el desarrollo, consulta siempre los siguientes documentos del workspace:

| Tema | Documento Canónico |
|---|---|
| **Reglas Globales de Agente** | [`AGENTS.md`](file:///c:/Users/Fgonz/Documents/Proyectos/Op-Eternal/Eternal-RPG/AGENTS.md) |
| **Configuración Gemini IDE** | [`docs/ANTIGRAVITY.md`](file:///c:/Users/Fgonz/Documents/Proyectos/Op-Eternal/Eternal-RPG/docs/ANTIGRAVITY.md) |
| **Fuente de Verdad Visual** | [`docs/DESIGN-ONE-PIECE-ETERNAL.md`](file:///c:/Users/Fgonz/Documents/Proyectos/Op-Eternal/Eternal-RPG/docs/DESIGN-ONE-PIECE-ETERNAL.md) |
| **Scaffolding de Páginas PHP** | [`docs/GUIA-ESTILOS-PHP.md`](file:///c:/Users/Fgonz/Documents/Proyectos/Op-Eternal/Eternal-RPG/docs/GUIA-ESTILOS-PHP.md) |
| **Visión de Producto y Marca** | [`docs/PRODUCT.md`](file:///c:/Users/Fgonz/Documents/Proyectos/Op-Eternal/Eternal-RPG/docs/PRODUCT.md) |
| **Roadmap de Desarrollo** | [`docs/PLAN-MAESTRO-ONE-PIECE-ETERNAL.md`](file:///c:/Users/Fgonz/Documents/Proyectos/Op-Eternal/Eternal-RPG/docs/PLAN-MAESTRO-ONE-PIECE-ETERNAL.md) |

---
*Documento creado y sincronizado para One Piece: Eternal.*

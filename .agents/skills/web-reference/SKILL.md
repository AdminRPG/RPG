---
name: web-reference
description: Procedimiento para inspeccionar prototipos estáticos locales (docs/Prototypes/) e investigar referencias web externas sin MCP firecrawl para One Piece: Eternal.
---

# Análisis de Referencias Web y Prototipos UI

Este documento define el protocolo para analizar maquetas estáticas internas e investigar sitios web o documentación técnica externa.

---

## 1. Inspección de Prototipos Locales (`docs/Prototypes/`)

El proyecto cuenta con maquetas HTML/CSS estáticas de referencia visual aprobadas en la carpeta **`docs/Prototypes/`**:

- **Prototipo de Portada**: `docs/Prototypes/Granblue/index.html` (Referencia visual de maquetación, carrusel hero, bento de gaceta y paleta de colores).
- **Capturas Oficiales**: `docs/references/relink.granbluefantasy.jp/` (Imágenes y guía estilística acuarela/oro).

### Procedimiento de Análisis de un Prototipo Local:
1. Inspecciona la estructura HTML del prototipo con `view_file`.
2. Identifica los tokens CSS clave (`:root`), degradados HSL, tamaños de tipografía y estructuras de contenedor (`.wrap`, `.plate`, `.shead`).
3. Mapea cada componente del prototipo HTML a la plantilla XML MyBB o vista PHP correspondiente en el sistema de producción.

---

## 2. Investigación Web Externa sin MCP Firecrawl

> [!IMPORTANT]
> **NO existe un servidor MCP de Firecrawl** en el entorno. Para buscar en la web o extraer contenido de sitios de referencia (como documentación de MyBB, guías de CSS o wikis de One Piece), utiliza las herramientas nativas del agente: `search_web` y `read_url_content`.

### 2.1. Cuándo usar `search_web`
Utiliza `search_web` para encontrar documentación, resolver dudas sobre sintaxis de MyBB, patrones CSS modernos o consultar conceptos del lore de One Piece.

```json
// Ejemplo de uso de search_web:
search_web({
  "query": "MyBB $db->escape_string documentation",
  "toolAction": "Searching MyBB DB docs",
  "toolSummary": "Search MyBB docs"
})
```

### 2.2. Cuándo usar `read_url_content`
Utiliza `read_url_content` cuando dispongas de una URL específica (documentación oficial, repositorio GitHub o artículo técnico) y necesites extraer su contenido formateado limpiamente en Markdown para analizar la estructura.

```json
// Ejemplo de uso de read_url_content:
read_url_content({
  "Url": "https://docs.mybb.com/1.8/development/plugins/",
  "toolAction": "Reading MyBB plugin guide",
  "toolSummary": "Read MyBB documentation page"
})
```

---

## 3. Protocolo para Replicar una Referencia Visual en el Proyecto

Si el usuario proporciona una URL o prototipo externo como referencia para una nueva funcionalidad:

1. **Extracción**: Usa `read_url_content` para analizar el texto o, según tu agente, usa `browser_subagent` (Antigravity) o el MCP playwright (OpenCode / Cursor) para tomar capturas de la web.
2. **Análisis de Patrones**: Identifica los siguientes 4 pilares:
   - **Paleta de Color**: Extrae los colores primarios y tradúcelos a variables HSL en `ope.css`.
   - **Tipografía**: Revisa si usa tipografías Serif o Sans-serif y compáralas con las fuentes del proyecto (Cinzel / Cormorant / Spectral).
   - **Layout**: Analiza si usa grids, bentos o tarjetas.
   - **Firma Visual**: Identifica el elemento distintivo único de la pantalla.
3. **Resumen de Propuesta**: Crea o actualiza un documento en `docs/references/` resumiendo la propuesta **antes** de modificar cualquier archivo de código.
4. **Espera de Confirmación**: Muestra el análisis al usuario y solicita aprobación antes de comenzar el portado.

---

## 4. Errores Comunes al Analizar Referencias

1. **Intentar llamar al MCP `firecrawl` o `playwright`**:
   - ❌ *Error*: Tratar de invocar un MCP inexistente que devolverá fallo de sistema.
   - ✅ *Correcto*: Usar `read_url_content` / `search_web` para texto o `browser_subagent` (Antigravity) / MCP playwright (OpenCode / Cursor) para inspección gráfica.

2. **Copiar estilos CSS directamente de frameworks (Tailwind / Bootstrap) al código del foro**:
   - ❌ *Error*: Inyectar clases como `bg-blue-500 flex p-4` o utilizar neobrutalismo con sombras duras negras (`box-shadow: 4px 4px 0 #000`).
   - ✅ *Correcto*: Traducir el diseño a las clases y tokens canónicos de One Piece: Eternal (`.plate`, `.shead`, `--iron-plate`, `--gold`).

3. **Portar un prototipo de forma incompleta o aislada**:
   - ❌ *Error*: Copiar únicamente el hero o la cabecera e ignorar el resto del cuerpo de la página.
   - ✅ *Correcto*: Aplicar la regla de las 5 capas completas definida en [`docs/AGENTES-Y-HERRAMIENTAS.md`](file:///c:/Users/Fgonz/Documents/Proyectos/Op-Eternal/Eternal-RPG/docs/AGENTES-Y-HERRAMIENTAS.md).

---

## 5. Checklist de Investigación

- [ ] Referencia inspeccionada vía `read_url_content` o `view_file` (si es local).
- [ ] Mapeo de estilos y tokens acordes al sistema de diseño OPE (`docs/DESIGN-ONE-PIECE-ETERNAL.md`).
- [ ] Ausencia de dependencias de MCPs externos no configurados.
- [ ] Propuesta de arquitectura presentada al usuario antes de modificar plantillas o CSS.

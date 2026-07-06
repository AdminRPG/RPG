# Guía de Identidad Visual — I-Forge
### Documento de referencia para agentes de IA (OpenCode / Antigravity / Cursor)

> **Instrucción para agentes de IA:** este documento es la fuente de verdad del diseño visual del proyecto. Antes de generar, modificar o revisar cualquier componente de UI, lee este archivo completo. Si una decisión de diseño no está aquí especificada, elige la opción más coherente con los valores ya definidos abajo y **anótala en la sección 10 (Registro de decisiones)** en vez de inventar silenciosamente. Nunca sustituyas un valor definido aquí por un default genérico (fuente del sistema, azul #3B82F6, sombras Tailwind por defecto, etc.) sin justificarlo explícitamente.

**Cómo se usa:** rellena cada sección `[ENTRE CORCHETES]`. Lo que no rellenes, tus agentes lo interpretarán como "libertad creativa dentro del tono general" — no como "usa la opción por defecto". Cuanto más concreto seas en las secciones 1-4, más específico (y menos genérico) será el resultado.

---

## 0. Referencia cruzada

- Arquitectura de backend: `arquitectura-backend-rol-mybb.md`
- Este documento cubre solo la capa de presentación (front). Los endpoints de la API y el modelo de datos ya están definidos en el documento de backend — no los reinventes aquí, consúmelos.

---

## 1. Identidad del proyecto

| Campo | Valor |
|---|---|
| Nombre del foro | I-Forge |
| Ambientación / universo | Hunter x Hunter (mundo propio, lore original) |
| Una frase que resuma el tono | "Archivo del Cazador" |
| Público objetivo | Comunidad hispana de rol, 18+ |
| 3 adjetivos que NUNCA debe transmitir | "infantil", "corporativo", "genérico" |
| 3 adjetivos que SÍ debe transmitir | "artesanal", "brutalista", "misterioso" |
| Referencias visuales de inspiración | OnePieceGaiden (densidad de información, no estilo visual) |

**Nota para agentes:** los adjetivos de esta tabla son la brújula para cualquier decisión ambigua de color, tipografía o copy. Si dudas entre dos opciones, elige la que refuerce los adjetivos "SÍ" y evite los "NUNCA".

---

## 2. Paleta de color

Define 4-6 colores con **función**, no solo hex. Un color sin función se convertirá en decoración inconsistente.

| Rol | Hex | Uso |
|---|---|---|
| Fondo base | `#f4f0e6` | Fondo general del foro (pergamino) |
| Fondo oscuro | `#ebe6d6` | Tarjetas, paneles interiores |
| Panel | `#2d5a27` | Navbar, headers, botones primarios (verde bosque) |
| Panel hover | `#3d7a35` | Hover de paneles verdes |
| Acento primario | `#c9a84c` | Títulos, enlaces, hover, badges (tinta dorada) |
| Acento hover | `#e2c96b` | Hover del acento dorado |
| Texto principal | `#1a1a1a` | Texto body (negro tinta) |
| Texto secundario | `#5a5a4a` | Metadatos, fechas (marrón apagado) |
| Borde | `#1e3d1a` | Todos los bordes (verde casi negro) |
| Sombra | `3px 3px 0 #1e3d1a` | Sombra sólida sin blur |
| Éxito | `#3fb950` | Aprobado, positivo |
| Peligro | `#f85149` | Rechazado, alerta |
| Rango E | `#8b949e` | Badge de rango E |
| Rango D | `#6e7681` | Badge de rango D |
| Rango C | `#58a6ff` | Badge de rango C |
| Rango B | `#a371f7` | Badge de rango B |
| Rango A | `#f0883e` | Badge de rango A |
| Rango S | `#c9a84c` | Badge de rango S (dorado) |

**Reglas de contraste:** mínimo AA en texto sobre fondo. El acento dorado nunca se usa para texto body, solo para títulos, enlaces y acentos.

**Prohibido:** no usar glassmorphism, no usar azules brillantes tipo bootstrap, no usar fondos blancos, no usar sombras con blur (solo sombras sólidas `Xpx Xpx 0`).

---

## 3. Tipografía

| Rol | Familia | Peso/uso | Dónde se usa |
|---|---|---|---|
| Display / títulos | Permanent Marker, cursive | 400 | Nombre foro, títulos categoría, headers de tarjeta, tabs |
| Cuerpo | Georgia, 'Palatino Linotype', serif | 400 | Texto general, posts, descripciones |
| UI / etiquetas | -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif | 400-800 | Navegación, botones, badges, metadatos |
| Datos / stats | Menlo, Consolas, monospace | 400-700 | Estadísticas, cantidades, valores numéricos |

**Escala tipográfica:** 10/11/12/13/14/15/16/18/20/24/28/32/48px

**Regla de personalidad:** Permanent Marker simula escritura a mano de un cazador tomando notas de campo. Georgia refuerza la estética de libro/documento antiguo. Nunca usar Permanent Marker para body text — solo para títulos.

---

## 4. Layout y estructura

Describe la estructura real de las pantallas clave.

### 4.1 Índice (home)
- Navbar fija superior (56px, verde panel)
- Banner hero (300-320px, verde panel) con sello decorativo en Permanent Marker enorme al 14% opacidad + título brush dorado
- Barra de calendario (verde panel con texto blanco, indica estación/año actual)
- Tablón 3 columnas: Guías | Zonas | Gremio (tarjetas bg-dark con borde 3px + sombra sólida)
- Categorías con subforos: header panel verde + grid de tarjetas panel verde con hover dorado
- Actividad reciente: tabla con bordes dashed entre filas
- Sidebar inferior: login, online, tareas

### 4.2 Ficha de personaje
- Header con avatar, nombre en brush, badges de rango/Nen
- Tabs estilo botón (fondo verde, hover/focus dorado)
- Portada: 3 columnas de stats (Cuerpo/Mente/Espíritu) con badges de rango letra + barras
- Estadísticas derivadas en grid de tarjetas pequeñas
- Rasgos en grid con coste numérico
- Biografía con sub-tabs (Historia/Apariencia/Personalidad/Extras)
- Bélico: pilares Nen + disciplinas + stats de combate
- Técnicas Nen: filtros por tier y tipo
- Inventario: grid de items + sidebar de equipamiento

### 4.3 Otras pantallas clave
| Pantalla | Elementos imprescindibles | Elementos que sobran (evitar) |
|---|---|---|
| Inventario | Grid de items con categorías, slots de equipo | |
| Panel de economía | | |
| Panel de moderación/aprobación | | |
| Widget de postbit (dentro de MyBB) | | |

**Regla de densidad:** la ficha debe sentirse densa en información como un dashboard de simulador, no minimalista tipo landing page.

---

## 5. Elemento firma (signature element)

> El único elemento que hará que alguien reconozca tu foro sin ver el logo.

**Elemento elegido:** el sello decorativo enorme (Permanent Marker, 200px, 14% opacidad) que actúa como marca de agua en banners y categorías. Evoca un blasón de cazador grabado en el pergamino.

**Por qué encaja con la ambientación:** los cazadores firman sus archivos con un sello personal. El sello fantasmal de fondo transmite pertenencia al Gremio sin ser literal ni obvio.

**Regla para agentes:** este elemento es donde se permite complejidad/riesgo visual. El resto del diseño debe mantenerse disciplinado alrededor de él — no repartir el "protagonismo visual" en cinco sitios a la vez.

---

## 6. Iconografía y estilo de imagen generada por IA

| Campo | Valor |
|---|---|
| Estilo artístico | [POR DEFINIR] |
| Paleta de la ilustración | tonos tierra + acento dorado |
| Encuadre estándar para retratos | busto, 3/4 de perfil, fondo neutro degradado |
| Iconos de sistema (items, stats) | línea fina monocromo |

**Regla de derechos:** todo el arte se **genera**, no se extrae de webs o series con copyright.

---

## 7. Motion / animación

| Momento | Comportamiento |
|---|---|
| Hover en tarjetas | translateY(-2px) + borde cambia a dorado + sombra crece 1px |
| Hover en botones | cambio de color de fondo, 0.15s transition |
| Transición entre tabs | instantánea (show/hide) |
| Feedback de acción | [POR DEFINIR] |

**Regla:** respetar `prefers-reduced-motion` siempre. Sin animaciones decorativas sueltas.

---

## 8. Voz y tono del copy de interfaz

| Situación | Cómo debe sonar |
|---|---|
| Botones de acción | Verbos activos en español: "Ingresar", "Confirmar", "Registrar" |
| Estado vacío (sin personajes, sin items) | Tono narrativo: "Sin equipo registrado." |
| Errores | Explican qué pasó sin disculparse |
| Confirmaciones | Directas: "Ficha aprobada", "Cambios guardados" |

---

## 9. Componentes y reglas de consistencia

- [x] Botones: 3px de borde sólido, sombra 2-3px sólida, border-radius 3px, transition 0.15s
- [x] Badges de rango: píldoras con borde 2px + sombra 2px. Fondo semitransparente del color de rango
- [x] Tarjetas: borde 3px + sombra 3px sólida. Hover: translateY(-2px), borde dorado
- [x] Formularios: inputs con borde 3px + sombra 2px sólida. Focus: borde dorado
- [x] Tabs: botones verdes con borde 3px + sombra. Activo: fondo dorado + texto verde
- [x] Accesibilidad mínima: foco visible por teclado, contraste AA, `prefers-reduced-motion` respetado

---

## 10. Registro de decisiones

| Fecha | Decisión | Motivo |
|---|---|---|
| 2026-07-06 | Tema neobrutalista beige/verde/dorado adoptado como definitivo | Coincide con el CSS real en `iforge-child-theme.xml` y las preferencias del usuario |
| 2026-07-06 | Permanent Marker como display, Georgia como body, monospace para datos | Refuerza la estética de "archivo de cazador" artesanal |
| 2026-07-06 | Sombras sólidas sin blur (`3px 3px 0`) en vez de sombras difuminadas | Coherente con neobrutalismo — simula capas de papel |
| 2026-07-06 | Border-radius 3px universal | Consistencia neobrutalista |

---

## 11. Checklist antes de dar por bueno cualquier pantalla nueva

- [ ] ¿Usa los colores de la tabla de la sección 2 (no colores nuevos improvisados)?
- [ ] ¿Usa las 4 familias tipográficas definidas, no una fuente de sistema por defecto?
- [ ] ¿El elemento firma (sección 5) sigue siendo el único punto de máximo protagonismo visual?
- [ ] ¿El copy sigue el tono de la sección 8?
- [ ] ¿Se ha probado responsive (móvil) y con foco de teclado visible?
- [ ] ¿Cualquier imagen generada es arte original (no copia de IP existente)?
- [ ] ¿Los bordes son 3px + sombras sólidas (sin blur)?

---

## 12. Referencia rápida para `agent.md`

```
Antes de generar o modificar cualquier UI:
1. Lee identidad-visual-front.md completo.
2. Sigue estrictamente la paleta, tipografía y elemento firma definidos.
3. Si una decisión no está cubierta, elige la opción más coherente con los adjetivos
   "SÍ/NUNCA" de la sección 1 y regístrala en la sección 10 del documento.
4. Antes de marcar la tarea como terminada, pasa el checklist de la sección 11.
```

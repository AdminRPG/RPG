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
|---|---|---|
| Nombre del foro | I-Forge |
| Ambientación / universo | Hunter x Hunter (mundo propio, lore original) |
| Una frase que resuma el tono | "Un mundo de Cazadores" |
| Público objetivo | Comunidad hispana de rol, 18+ |
| 3 adjetivos que NUNCA debe transmitir | "infantil", "corporativo", "genérico" |
| 3 adjetivos que SÍ debe transmitir | "oscuro", "artesanal", "misterioso" |
| Referencias visuales de inspiración (no a copiar, sí a entender por qué funcionan) | [enlaces o descripciones — anota qué elemento concreto de cada referencia te gusta, no solo "que se vea así"] |

**Nota para agentes:** los adjetivos de esta tabla son la brújula para cualquier decisión ambigua de color, tipografía o copy. Si dudas entre dos opciones, elige la que refuerce los adjetivos "SÍ" y evite los "NUNCA".

---

## 2. Paleta de color

Define 4-6 colores con **función**, no solo hex. Un color sin función se convertirá en decoración inconsistente.

| Rol | Hex | Uso |
|---|---|---|
| Fondo base | `#0d1117` | Fondo general del foro |
| Fondo elevado | `#161b22` | Navbar, tarjetas, paneles |
| Borde | `#30363d` | Bordes de componentes |
| Acento primario | `#e2b714` | Enlaces, hover, elementos interactivos (oro) |
| Texto principal | `#f0f6fc` | Texto body |
| Texto muted | `#8b949e` | Metadatos, fechas |
| Éxito | `#3fb950` | Aprobado, positivo |
| Peligro | `#f85149` | Rechazado, alerta |
| Rango T1 | `#58a6ff` | Badge de rango T1 |
| Rango T2 | `#a371f7` | Badge de rango T2 |
| Rango T3 | `#f0883e` | Badge de rango T3 |

**Reglas de contraste:** [ej. "mínimo AA en texto sobre fondo", "el acento de facción nunca se usa para texto de body, solo para bordes/acentos"]

**Prohibido:** no usar glassmorphism, no usar azules brillantes tipo bootstrap, no usar fondos blancos

---

## 3. Tipografía

| Rol | Familia | Peso/uso | Dónde se usa |
|---|---|---|---|
| Display / títulos | Georgia, serif | | Nombre foro, títulos categoría |
| Cuerpo | -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif | | Texto general |
| Datos / stats | Menlo, Consolas, monospace | | Estadísticas, cantidades |
| Utilitaria / etiquetas | [opcional] | | Badges, eyebrows, metadatos |

**Escala tipográfica:** [define tamaños concretos si ya los tienes, ej. 12/14/16/20/28/40px, o deja que el agente proponga una escala modular y la documente aquí tras decidirla]

**Regla de personalidad:** la tipografía de display debe sentirse específica de esta ambientación, no una fuente que usarías igual en un SaaS de facturación. Justifica la elección en una frase.

---

## 4. Layout y estructura

Describe la estructura real de las pantallas clave (no genérico "header + content + footer").

### 4.1 Ficha de personaje
```
Pendiente de definir en fase de personajes
```

### 4.2 Otras pantallas clave
| Pantalla | Elementos imprescindibles | Elementos que sobran (evitar) |
|---|---|---|
| Inventario | [ej. grid de items con rareza por color de borde] | |
| Panel de economía | | |
| Panel de moderación/aprobación | | |
| Widget de postbit (dentro de MyBB) | | |

**Regla de densidad:** [ej. "la ficha debe sentirse densa en información como un dashboard de simulador, no minimalista tipo landing page"]

---

## 5. Elemento firma (signature element)

> El único elemento que hará que alguien reconozca tu foro sin ver el logo.

**Elemento elegido:** [ej. "la rueda circular de estadísticas con aguja tipo reloj", "el marco de cartel de búsqueda con esquinas dobladas y sello de cera"]

**Por qué encaja con la ambientación:** [1-2 frases]

**Regla para agentes:** este elemento es donde se permite complejidad/riesgo visual. El resto del diseño debe mantenerse disciplinado alrededor de él — no repartir el "protagonismo visual" en cinco sitios a la vez.

---

## 6. Iconografía y estilo de imagen generada por IA

Esta sección es el prompt-base para cualquier MCP de generación de imágenes (fal.ai, ComfyUI, etc.). Cuanto más específica, más consistencia entre todos los retratos/iconos del foro.

| Campo | Valor |
|---|---|
| Estilo artístico | [ej. "anime seinen, línea limpia, sombreado por celdas (cel-shading)", "grabado antiguo sepia", "pixel art 32-bit"] |
| Paleta de la ilustración | [puede diferir de la paleta UI — ej. "tonos tierra + un acento de color de facción"] |
| Encuadre estándar para retratos | [ej. "busto, 3/4 de perfil, fondo neutro degradado"] |
| Prompt base (a reutilizar/completar por personaje) | `[ej. "retrato estilo anime seinen, cel-shading, iluminación dramática lateral, fondo degradado oscuro, sin texto, sin marco --ar 3:4"]` |
| Negative prompt / a evitar | `[ej. "sin manos deformes, sin texto, sin marcas de agua, sin estilo 3D render"]` |
| Iconos de sistema (items, stats) | [ej. "línea fina monocromo, mismo grosor de trazo que el logo"] |

**Regla de derechos:** todo el arte se **genera**, no se extrae de webs o series con copyright (ni siquiera "en el estilo de X serie" pidiendo que se parezca a personajes concretos existentes). Los agentes deben generar arte original a partir de esta guía de estilo, nunca reproducir ilustraciones ya existentes.

---

## 7. Motion / animación

| Momento | Comportamiento |
|---|---|
| Carga de la ficha | [ej. "fade + rueda de stats se dibuja progresivamente", o "ninguna animación, carga instantánea"] |
| Hover en tarjetas/botones | |
| Transición entre tabs | |
| Feedback de acción (tirada de dados, transferencia) | [ej. "el número de la tirada aparece con un pequeño impacto/rebote"] |

**Regla:** [ej. "una sola animación orquestada por pantalla, cero animaciones decorativas sueltas", "respetar prefers-reduced-motion siempre"]

---

## 8. Voz y tono del copy de interfaz

| Situación | Cómo debe sonar |
|---|---|
| Botones de acción | [ej. verbos activos: "Enviar ficha", no "Submit"] |
| Estado vacío (sin personajes, sin items) | [ej. tono narrativo/diegético vs. tono neutro de sistema] |
| Errores | [ej. explican qué pasó y cómo arreglarlo, sin disculparse, en la voz del "narrador" del foro o en voz neutra de sistema — elige una] |
| Confirmaciones | [ej. "Ficha aprobada" no "Success"] |

---

## 9. Componentes y reglas de consistencia

- [ ] Botones: [radios de borde, estados hover/active/disabled definidos aquí o delegados a shadcn con tema propio]
- [ ] Badges de rareza/estado (ficha pendiente/aprobada/rechazada, rareza de item): [sistema de color consistente]
- [ ] Formularios: [validación inline vs. al enviar]
- [ ] Accesibilidad mínima: foco visible por teclado, contraste AA, `prefers-reduced-motion` respetado — no negociable independientemente del resto de decisiones estéticas.

---

## 10. Registro de decisiones (lo rellenan los agentes, no el usuario)

> Cada vez que un agente tome una decisión de diseño no cubierta explícitamente arriba, la documenta aquí en una línea: fecha, decisión, motivo. Esto evita que dos sesiones de agente distintas contradigan el estilo ya establecido.

| Fecha | Decisión | Motivo |
|---|---|---|
| | | |

---

## 11. Checklist antes de dar por bueno cualquier pantalla nueva

- [ ] ¿Usa los colores de la tabla de la sección 2 (no colores nuevos improvisados)?
- [ ] ¿Usa las 2-3 familias tipográficas definidas, no una fuente de sistema por defecto?
- [ ] ¿El elemento firma (sección 5) sigue siendo el único punto de máximo protagonismo visual?
- [ ] ¿El copy sigue el tono de la sección 8?
- [ ] ¿Se ha probado responsive (móvil) y con foco de teclado visible?
- [ ] ¿Cualquier imagen generada sigue el prompt-base de la sección 6, y es arte original (no copia de IP existente)?
- [ ] ¿Se ha hecho captura de pantalla (Playwright MCP u otro) y comparado contra esta guía antes de considerarlo terminado?

---

## 12. Referencia rápida para `agent.md`

Añade en tu `agent.md` (o equivalente en OpenCode/Antigravity/Cursor) algo como:

```
Antes de generar o modificar cualquier UI:
1. Lee identidad-visual-front.md completo.
2. Lee arquitectura-backend-rol-mybb.md si el componente consume datos de la API.
3. Sigue estrictamente la paleta, tipografía y elemento firma definidos.
4. Si una decisión no está cubierta, elige la opción más coherente con los adjetivos
   "SÍ/NUNCA" de la sección 1 y regístrala en la sección 10 del documento.
5. Antes de marcar la tarea como terminada, pasa el checklist de la sección 11.
```

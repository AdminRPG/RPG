# I-Forge · Estilo "PixelRPG"

Guía de estilo autocontenida para la dirección visual **PixelRPG**. Es un
estilo **completamente distinto** al "Foundry Brutalism" (carpeta
`../NeoBrutalism/`): no comparten paleta, tipografía ni layout. La única
constante es la **información** del foro (mismos datos, mismos personajes,
mismas mecánicas) y el producto: un foro de rol *play-by-post* llamado I-Forge.

> **Concepto en una frase:** *el foro ES la pantalla de un JRPG 16-bit.*
> Máximo diegético: HUD de estado, ventanas de menú tipo SNES, mapa del mundo
> por tiles, escenas de diálogo con sprites, tienda del pueblo y cursores ▶.

---

## 1. Principios

1. **Diegético, no decorativo.** No es "una web con fuente pixelada". Cada
   pantalla se reinterpreta como una vista real de videojuego:
   - `index.html` → **pantalla de título + mapa del mundo + registro de misiones** (fusiona banner y tablón).
   - `ficha.html` → **menú de estado / party** (sprite, HP/MP/XP, comandos).
   - `foro.html` → **tablón de misiones / selección de área** (temas = quests).
   - `tema.html` → **escena de diálogo / cutscene** (cada post = bocadillo con sprite).
   - `tramites.html` → **tienda / menú del pueblo** (trámites = ítems de tienda).
2. **Retro pero legible.** Tipografía **híbrida**: bitmap para chrome/HUD/títulos,
   y una fuente muy legible (`Nunito`) para prosa larga de rol.
3. **Super aesthetic.** Overlay CRT (scanlines + viñeta + flicker sutil),
   fondos con estrellas/dither, degradados de menú SNES. Nada plano.
4. **Setting-neutral.** Vocabulario propio (Flujo, temple, forjar, coladas,
   Marcos) sin depender de ninguna IP concreta.

## 2. Paleta — "crepúsculo de mazmorra"

Definida en `pixel.css` (`:root`). Usa **siempre** las variables, nunca hex sueltos.

| Rol | Token | Hex |
|---|---|---|
| Noche (fondo) | `--night` | `#13111f` |
| Ocaso / bg 2 | `--dusk` | `#1b1834` |
| Relleno ventana | `--win` / `--win2` | `#201e46` / `#2a2760` |
| Borde claro ventana | `--edge` | `#c3bdff` |
| Keyline oscuro | `--edge-d` | `#0a0913` |
| Panel plano | `--panel` | `#171532` |
| Texto | `--ink` / `--ink-dim` / `--ink-fade` | `#ece9fb` / `#9c98c9` / `#6f6b99` |
| Oro (selección/coins/XP) | `--gold` / `--gold2` | `#f4c455` / `#ffe89a` |
| Cian (links/MP) | `--cyan` | `#54dcd6` |
| Rosa (HP bajo/peligro) | `--rose` | `#f06a9c` |
| Verde (HP/éxito) | `--green` | `#84d66f` |
| Violeta (magia) | `--violet` | `#ab7bec` |

### Escala de rareza = escala de rango (E → M+)
Firma del sistema. Cada rango es un color de "rareza" JRPG:

`E` `--tE` gris · `D` `--tD` verde · `C` `--tC` cian · `B` `--tB` violeta ·
`A` `--tA` oro · `S` `--tS` rosa · `SS` `--tSS` oro claro · `M` `--tM` celeste ·
`M+` `--tMx` blanco. Úsalos en `.tier[data-tier="X"]`, badges de misión, sprites de disciplina y barras de stats.

## 3. Tipografía (híbrida)

| Fuente | Token | Uso |
|---|---|---|
| **Press Start 2P** | `--pixel` | Logo, títulos de ventana, HUD, botones cortos, badges de rango. Solo tamaños pequeños/medios. |
| **VT323** | `--vt` | Stats, menús, precios, metadatos, listas, diálogo-meta. Pixel-terminal legible en cantidad. |
| **Nunito** | `--body` | **Prosa larga**: cuerpo de posts, biografías, descripciones. Legibilidad primero. |

Regla: nunca metas párrafos largos en `--pixel`. El chrome es bitmap; el texto que se lee de verdad es `Nunito`.

## 4. Geometría y materiales

- **Sin border-radius** (excepto círculos puntuales). Todo es rejilla de píxeles.
- Bordes: `3px solid var(--edge)` + keyline `0 0 0 3px var(--edge-d)` + sombra dura `6px 6px 0 rgba(0,0,0,.5)` → sensación de "ventana de menú" apilada.
- **Ventana JRPG** = `.window` (con `.win-title` como pestaña de título encajada en el borde y marcas de esquina doradas). Variante plana: `.window.-plain`.
- `image-rendering: pixelated` en todos los `img`/sprites: un PNG de pixel-art se ve nítido.
- **CRT overlay** global (`body::after`/`::before`): scanlines + viñeta + flicker. Se desactiva con `prefers-reduced-motion`.

## 5. Movimiento

- Cursor ▶ parpadeante en `.menu-item`/`.quest` (selección de comando).
- `— PRESS START —` con parpadeo (`.press`).
- Barras HP/MP/XP con relleno animado.
- Dado con "shake"; log con reveal-on-scroll.
- `▼` de "continuar" en las cajas de diálogo.
- Todo respeta `prefers-reduced-motion: reduce` (animaciones y CRT off).

## 6. Voz y copy

Igual que el resto de I-Forge: directa, de fragua. Vocabulario: *Flujo, temple
(XP), forjar (crear), coladas (posts recientes), Marcos/Fichas/Esquirlas
(divisas), Fundidor (staff/narrador), contrato (misión)*. Se le suma la capa de
consola: *PRESS START, LV, HP/MP, party, viajar, tienda, quest*.

## 7. Sistema de juego (idéntico a NeoBrutalism)

- **12 atributos** en 3 pilares (Cuerpo/Mente/Espíritu), cada uno con rango E→M+.
- Vania Korr (ejemplo): FUE C, DES C, VIG D, AGI B, INT C, ING C, CON D, PER B, CAU C, CTR D, VOL C, SEN D.
- **Derivadas con fórmula visible**: PV=VIG×50, PA(Flujo)=CAU×30, ENE=VIG×20,
  INI=AGI×2+PER, Def. Física=VIG+AGI+Arm, etc. (ver `ficha.html`, objeto `D`).
- HUD mapea: **HP**=PV, **MP**=Flujo(PA), **XP**=Temple, **◆**=Marcos.

## 8. Catálogo de componentes (`pixel.css`)

- **Chrome:** `.hud` (barra de estado superior con sprite, LV, gauges, oro, comandos), `.crumb` (breadcrumb), `.foot`.
- **Ventanas:** `.window` / `.-plain`, `.win-title` / `.-cyan`, `.win-body` / `.-tight`.
- **Menú:** `.menu` + `.menu-item` (cursor ▶) · `.cmd > button` (tabs de comando).
- **Gauges:** `.gauge` + `.-hp/.-mp/.-xp` con `> i` (relleno). `.gauge-row` para etiqueta+valor.
- **Botones:** `.btn` + `.-gold/.-ghost/.-sm`, `.press`.
- **Etiquetas:** `.tag`, `.tier[data-tier]`, `.badge`, `.stars`.
- **Sprites:** `.sprite` (+ `img` con `onerror="this.remove()"` y `.fallback` de iniciales).
- **Misiones (foro):** `.quest` + `.quest-diff/-name/-status/-meta`.
- **Diálogo (tema):** `.scene`, `.dlg` / `.-right`, `.dlg-portrait`, `.dlg-box`, `.dlg-name`, `.dlg-text`, `.dlg-next`.
- **Tienda (trámites):** `.shop`, `.svc` + `.svc-top/-ic/-name/-code/-desc/-foot/-price`.
- **Datos:** `.params`/`.param`, `.kv`, `.filters`/`.filt`, `.pager`/`.page`, `.field`/`textarea.field`.

## 9. Integración del retrato PNG

Suelta `assets/vania.png` (y `fundidor.png`, `ren.png`). Se cargan como
sprite/retrato en `.sprite`; si el archivo no existe, aparece el `.fallback`
con iniciales. Recomendado: PNG transparente, encuadre de retrato, ~4:5 o
cuadrado. Con `image-rendering: pixelated`, el pixel-art queda nítido. Ver
`assets/README.txt`.

## 10. Accesibilidad y responsive

- Contraste AA (texto claro sobre ventanas oscuras).
- `:focus-visible` con contorno oro.
- Teclado: tabs, filtros y menús son botones/enlaces reales.
- `prefers-reduced-motion`: sin animaciones ni CRT.
- Layouts colapsan a una columna en móvil; el HUD oculta gauges/nombre en pantallas estrechas.

## 11. Checklist antes de dar por buena una pantalla

- [ ] ¿Se siente como una **pantalla de videojuego**, no una web con fuente pixel?
- [ ] ¿Usa **solo** `var(--tokens)` (sin hex ni fuentes nuevas)?
- [ ] ¿HUD + breadcrumb + footer consistentes con el resto?
- [ ] ¿Prosa larga en `Nunito` (legible), chrome en bitmap?
- [ ] ¿Rangos con color de tier correcto (E→M+)?
- [ ] ¿Funciona teclado, foco, reduced-motion y móvil?
- [ ] ¿Contiene **la misma información** que su equivalente en `NeoBrutalism`?

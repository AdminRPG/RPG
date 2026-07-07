# One Piece Eternal — Prototipos HTML Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Generar 5 prototipos HTML estáticos, autocontenidos, con CSS integrado, que materialicen el sistema de diseño "One Piece Eternal" sobre la base del DESIGN.md entregado, sin que parezcan generados por IA.

**Architecture:** Cada prototipo es un archivo HTML independiente con CSS en `<style>` y contenido representativo. Todos comparten un mismo sistema de tokens CSS (colores, tipografía, espaciado, sombras) extraído del DESIGN.md. No dependen de imágenes ni de JS. Se generan en paralelo mediante subagentes, uno por prototipo.

**Tech Stack:** HTML5, CSS3 (variables, grid, flex), Google Fonts (Pirata One, Special Elite, JetBrains Mono), SVG inline para iconos.

## Global Constraints

- CSS-first: el diseño debe funcionar sin imágenes.
- Neobrutalismo temático: bordes gruesos, sombras sólidas sin blur.
- Paleta obligatoria del DESIGN.md: mar profundo (#070d1a, #0a1628, #132238), parchment (#e8dcc8), bounty-gold (#d4a017), marine-red (#8b0000).
- Tipografías: Pirata One (display), Special Elite (body), JetBrains Mono (data), system-ui stack (UI).
- No gradientes decorativos, no grid de fondo, no animaciones breathe/pulse genéricas, no hover lift idéntico (translateY).
- Uppercase restringido: solo H1/H2 y badges de recompensa/Wanted.
- Border-radius máximo 6px excepto avatares/badges circulares.
- Máximo ancho 1200px, espaciado basado en escala 4-8-12-16-20-24-32-40-48.
- Cada archivo debe tener `<main>` + `<nav>` + `<footer>` semánticos, landmarks claros.
- Contenido de ejemplo en español, coherente con el universo One Piece.

## Estructura de archivos

- Crear: `docs/Prototypes/KIMI USA SUBAGENTES PARA IR MAS RAPIDO NO QUIERO QUE PAREZCA IA MEJORA EL PRODUCTO ACTUAL/prototype-index.html`
- Crear: `docs/Prototypes/KIMI USA SUBAGENTES PARA IR MAS RAPIDO NO QUIERO QUE PAREZCA IA MEJORA EL PRODUCTO ACTUAL/prototype-ficha.html`
- Crear: `docs/Prototypes/KIMI USA SUBAGENTES PARA IR MAS RAPIDO NO QUIERO QUE PAREZCA IA MEJORA EL PRODUCTO ACTUAL/prototype-categoria.html`
- Crear: `docs/Prototypes/KIMI USA SUBAGENTES PARA IR MAS RAPIDO NO QUIERO QUE PAREZCA IA MEJORA EL PRODUCTO ACTUAL/prototype-hilo.html`
- Crear: `docs/Prototypes/KIMI USA SUBAGENTES PARA IR MAS RAPIDO NO QUIERO QUE PAREZCA IA MEJORA EL PRODUCTO ACTUAL/prototype-tablero.html`

---

### Task 1: Prototipo Index / Página Principal

**Files:**
- Crear: `docs/Prototypes/KIMI USA SUBAGENTES PARA IR MAS RAPIDO NO QUIERO QUE PAREZCA IA MEJORA EL PRODUCTO ACTUAL/prototype-index.html`

**Interfaces:**
- Consume: tokens CSS globales del DESIGN.md.
- Produce: prototipo funcional de la home del foro.

- [ ] **Step 1: Crear estructura HTML base**
  - `<!DOCTYPE html>`, `<html lang="es">`, viewport, charset.
  - Incluir Google Fonts: Pirata One, Special Elite, JetBrains Mono.
  - `<style>` con todas las variables CSS del DESIGN.md.

- [ ] **Step 2: Implementar navbar**
  - Logo "One Piece Eternal" en Pirata One dorado.
  - Links: Inicio, Mundo, Foros, Personajes, Misión. Sin uppercase.
  - Avatar de usuario circular.

- [ ] **Step 3: Implementar hero tipo Wanted**
  - "WANTED · DEAD OR ALIVE" en rojo marina, bordes superior e inferior.
  - Título "ONE PIECE ETERNAL" en dorado, clamp(2rem, 5vw, 3.5rem), letter-spacing -0.03em.
  - Subtítulo narrativo en Special Elite.
  - CTAs "Explorar el Mundo" y "Crear Personaje".

- [ ] **Step 4: Implementar barra de estado**
  - Presente on-rol: "Verano · Año 725".
  - Usuarios online, último post.

- [ ] **Step 5: Implementar categorías del foro por regiones**
  - East Blue, Grand Line Paradise, Grand Line New World, Marines, Off-Topic.
  - Header de región en display dorado uppercase.
  - Grid de tarjetas auto-fit minmax(280px, 1fr).

- [ ] **Step 6: Implementar sidebar**
  - Últimos posts, eventos activos, tirada de dado D20, Tablón de Contratos.

- [ ] **Step 7: Implementar footer**
  - Links y créditos concisos.

- [ ] **Step 8: Verificar anti-AI checklist**
  - Sin grid de fondo, sin eyebrow genérico, sin radial gradient decorativo, sin hover lift, sin uppercase masivo.

---

### Task 2: Prototipo Ficha de Personaje (Bounty Poster)

**Files:**
- Crear: `docs/Prototypes/KIMI USA SUBAGENTES PARA IR MAS RAPIDO NO QUIERO QUE PAREZCA IA MEJORA EL PRODUCTO ACTUAL/prototype-ficha.html`

**Interfaces:**
- Consume: tokens CSS globales.
- Produce: prototipo de ficha de personaje.

- [ ] **Step 1: Crear estructura HTML base y tokens**

- [ ] **Step 2: Implementar navbar compartida**

- [ ] **Step 3: Implementar breadcrumb**
  - Inicio > East Blue > Foosha Village > Nombre del personaje.

- [ ] **Step 4: Implementar Bounty Poster**
  - Marco grueso 3px wood-dark + shadow-lg.
  - Fondo parchment.
  - Header "WANTED · DEAD OR ALIVE".
  - Retrato placeholder con borde madera.
  - Badge de recompensa grande con símbolo ฿ en font-data.
  - Datos: nombre, alias, facción, raza.

- [ ] **Step 5: Implementar stats**
  - 12 stats en 3 pilares: Cuerpo, Mente, Espíritu.
  - Barras de progreso y badges de rango (F a M+) con colores de stat.

- [ ] **Step 6: Implementar técnicas**
  - Cartas de técnica con tags: [Akuma], [Haki], [Ittoryu], etc.

- [ ] **Step 7: Implementar equipo e inventario**
  - Slots de equipo e ítems.

- [ ] **Step 8: Implementar tripulación e historia**
  - Secciones colapsables o tabuladas.

- [ ] **Step 9: Verificar anti-AI checklist**

---

### Task 3: Prototipo Categoría del Foro (Región del Mundo)

**Files:**
- Crear: `docs/Prototypes/KIMI USA SUBAGENTES PARA IR MAS RAPIDO NO QUIERO QUE PAREZCA IA MEJORA EL PRODUCTO ACTUAL/prototype-categoria.html`

**Interfaces:**
- Consume: tokens CSS globales.
- Produce: prototipo de página de categoría/región.

- [ ] **Step 1: Crear estructura HTML base y tokens**

- [ ] **Step 2: Implementar navbar compartida**

- [ ] **Step 3: Implementar breadcrumb**
  - Inicio > East Blue > Loguetown.

- [ ] **Step 4: Implementar header de región**
  - Nombre de región en display dorado uppercase.
  - Descripción narrativa.
  - Estadísticas: hilos, mensajes.

- [ ] **Step 5: Implementar subforos como tarjetas de isla**
  - Ej: "El Árbol del Golpe", "La Tienda de Armas".
  - Tier T1-T5 con color de stat.

- [ ] **Step 6: Implementar lista de hilos**
  - Avatar, título, autor, último post, respuestas.
  - Badges de tier/estado.

- [ ] **Step 7: Implementar sidebar**
  - Info de la región, NPCs importantes, misiones activas.

- [ ] **Step 8: Verificar anti-AI checklist**

---

### Task 4: Prototipo Hilo de Rol (Bitácora de Navegación)

**Files:**
- Crear: `docs/Prototypes/KIMI USA SUBAGENTES PARA IR MAS RAPIDO NO QUIERO QUE PAREZCA IA MEJORA EL PRODUCTO ACTUAL/prototype-hilo.html`

**Interfaces:**
- Consume: tokens CSS globales.
- Produce: prototipo de hilo de rol con posts.

- [ ] **Step 1: Crear estructura HTML base y tokens**

- [ ] **Step 2: Implementar navbar compartida**

- [ ] **Step 3: Implementar breadcrumb**
  - Inicio > East Blue > Loguetown > Título del hilo.

- [ ] **Step 4: Implementar header del hilo**
  - Título, tags (tier, región, estado), participantes.

- [ ] **Step 5: Implementar posts estilo bitácora**
  - Fondo parchment para contenido.
  - Header con fondo sea-deep.
  - Avatar, nombre de personaje, fecha, número de post (#001).
  - Texto narrativo en Special Elite.
  - Acciones: Responder, Citar, Reportar.

- [ ] **Step 6: Implementar formulario de respuesta**
  - textarea estilo bitácora.
  - Botón "Tirar Dados" integrado.
  - Botón "Publicar respuesta".

- [ ] **Step 7: Implementar sidebar**
  - Participantes, misiones relacionadas, objetos en escena.

- [ ] **Step 8: Verificar anti-AI checklist**

---

### Task 5: Prototipo Tablero Mundial (La Balanza)

**Files:**
- Crear: `docs/Prototypes/KIMI USA SUBAGENTES PARA IR MAS RAPIDO NO QUIERO QUE PAREZCA IA MEJORA EL PRODUCTO ACTUAL/prototype-tablero.html`

**Interfaces:**
- Consume: tokens CSS globales.
- Produce: prototipo de dashboard del estado del mundo.

- [ ] **Step 1: Crear estructura HTML base y tokens**

- [ ] **Step 2: Implementar navbar compartida**

- [ ] **Step 3: Implementar header de La Balanza**
  - "La Balanza — Estado del Mundo".
  - Ciclo actual y fecha.

- [ ] **Step 4: Implementar mapa del mundo en CSS**
  - Representación con nodos (regiones) y conexiones (líneas).
  - Indicadores de tensión por color.
  - Sin imágenes, puramente CSS.

- [ ] **Step 5: Implementar facciones**
  - Estado de 9 facciones.
  - Relación con jugador: aliado, neutral, enemigo.

- [ ] **Step 6: Implementar eventos activos**
  - Lista de eventos mundiales con nivel de impacto.

- [ ] **Step 7: Implementar última crónica**
  - Estilo periódico World Economy News.
  - Extracto de crónica.

- [ ] **Step 8: Implementar NPCs activos**
  - Lista con ubicación actual.

- [ ] **Step 9: Verificar anti-AI checklist**

---

## Verificación final

- [ ] Revisar que los 5 archivos existen en la ruta correcta.
- [ ] Abrir cada archivo en navegador y comprobar que no hay errores visuales críticos.
- [ ] Validar HTML básico (tags cerrados, atributos).
- [ ] Confirmar que la paleta y tipografía son consistentes entre prototipos.
- [ ] Aplicar la anti-AI checklist de la sección 11 del DESIGN.md.

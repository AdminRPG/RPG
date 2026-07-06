# Agent Configuration — Foro de Rol sobre MyBB

## Instrucciones generales

Este proyecto es un foro de rol basado en MyBB con una API propia para lógica de juego (fichas, inventario, economía, dados). El sistema se compone de:

1. **MyBB (PHP/MySQL)** — foro, autenticación, contenido narrativo (raíz del proyecto: `admin/`, `inc/`, `jscripts/`, etc.)
2. **API propia (`rol-backend/`)** — lógica de juego, REST endpoints (Slim 4 + JWT)
3. **Plugin puente (`mybb-plugin-rol/`)** — hooks de MyBB y widgets JS que se despliegan en `inc/plugins/` y `jscripts/`
4. **Documentación (`docs/`)** — contratos de backend y frontend

---

## Reglas para el agente

### Antes de generar o modificar cualquier componente:

1. **Lee los contratos primero:**
   - `docs/backend/arquitectura-backend-rol-mybb.md` — si el componente consume datos de la API o toca lógica de juego
   - `docs/frontend/identidad-visual-front.md` — si el componente es UI (frontend, widgets, pantallas)
   - `docs/estructura/mybb-directory-structure.md` — si trabajas con archivos de MyBB

2. **Backend:**
   - MyBB es fuente de verdad de identidad y contenido narrativo. Nunca escribir directamente sobre tablas `mybb_*`.
   - El backend propio es fuente de verdad de estado de juego (fichas, inventario, economía, dados).
   - Separación de bases de datos (esquema propio `rol_*`).
   - Autenticación puente vía JWT emitido por plugin de MyBB en login.
   - Toda operación de economía y dados es **server-side**, con transacciones SQL y auditoría.

3. **Frontend:**
   - Sigue estrictamente la paleta, tipografía y elemento firma definidos en `identidad-visual-front.md`.
   - Los widgets JS se hidratan con `fetch()` contra la API propia.
   - El fallo de la API nunca debe romper la carga del foro — siempre `try/catch` con degradación elegante.

4. **Si una decisión no está cubierta:**
   - Elige la opción más coherente con los adjetivos "SÍ/NUNCA" de la sección 1 de `identidad-visual-front.md`.
   - Documéntala en la sección 10 (Registro de decisiones) de ese mismo documento.

5. **Antes de marcar como terminado:**
   - Pasa el checklist de la sección 11 de `identidad-visual-front.md`.
   - Verifica que el contrato REST (sección 6 de `arquitectura-backend-rol-mybb.md`) se respeta.

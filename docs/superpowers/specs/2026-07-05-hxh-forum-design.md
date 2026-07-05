# Diseño del Foro — I-Forge (Hunter x Hunter)
### Documento de diseño tras lluvia de ideas — 05/07/2026

---

## 0. Principios

- **Temática:** Hunter x Hunter (mundo y terminología), pero con lore propio (cronología, personajes y sucesos inventados)
- **Sistemas propios:** atributos, sistema bélico, economía — todo original, no réplica de HxH
- **Sin MyBB visible:** MyBB solo como motor de base y lógica de foro. Todo el frontend es nuestro: templates propios, navbar propio, mensajería propia, configuración propia
- **Cohesión total:** todo debe sentirse parte del mismo diseño. Nada de estilos sueltos o elementos que parezcan de otro sitio
- **Sin glassmorphism**

---

## 1. Navegación — Navbar flotante

```
┌─────────────────────────────────────────────────────┐
│ [Personaje] [Trámites] [Guías] [Zona Privada*] [❤] │
└─────────────────────────────────────────────────────┘
```

- **Posición:** fixed, top:0, z-index alto. Siempre visible al hacer scroll.
- **Fondo:** color sólido oscuro, borde inferior sutil.
- **Items:** sin submenús por ahora. Cada uno es un enlace directo a su página.
- **Zona Privada:** solo se renderiza si el usuario tiene rol Narrador, Moderador o Administrador.
- **[❤] icono:** a la derecha del navbar. Representa al personaje activo (sin avatar, solo un icono genérico o inicial). Al hacer clic, dropdown con:
  - Mensajería
  - Configuración
  - Cerrar sesión
- **Sin loguear:** [Personaje] lleva a login. No aparece [❤].
- **Sin personaje activo:** [❤] muestra un icono gris/neutro. Mensajería y Configuración deshabilitados.

---

## 2. Banner rotatorio

Imagen grande de cabecera justo debajo del navbar (no confundir con contenido).

- **N imágenes** en carpeta `images/banners/`. PHP elige una aleatoriamente al cargar la página.
- **Overlay** oscuro semitransparente sobre la imagen.
- **Título centrado:** nombre del foro, grande, con tipografía display.
- **Subtítulo:** breve debajo del título.
- **Altura:** ~400-500px.

---

## 3. Página de índice (`/`)

```
┌─ NAVBAR ────────────────────────────────────────────┐
│ BANNER                                               │
├─ TABLÓN ────────────────────────────────────────────┤
│ 📅 DÍA 47 · VERANO · AÑO 925                [clic→] │
│ ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌─────────┐ │
│ │Últimos   │ │Búsquedas │ │Noticias  │ │Curiosid.│ │
│ │mensajes  │ │Activas   │ │          │ │         │ │
│ └──────────┘ └──────────┘ └──────────┘ └─────────┘ │
│ ┌──────────────────────────────┐                    │
│ │ Staff (contacto rápido)       │                    │
│ └──────────────────────────────┘                    │
├─ CATEGORÍAS ────────────────────────────────────────┤
│ ┌──────────────────────────────────────────────┐    │
│ │ [Imagen fondo] 🏝️ ARCHIPIÉLAGO CANDELARIA    │    │
│ │ Descripción breve                             │    │
│ └──────────────────────────────────────────────┘    │
│ ┌──────────────────────────────────────────────┐    │
│ │ [Imagen fondo] ⛰️ CONTINENTE OSCURO          │    │
│ │ Descripción breve                             │    │
│ └──────────────────────────────────────────────┘    │
│ ┌──────────────────────────────────────────────┐    │
│ │ [Imagen fondo] 📜 ZONA NARRATIVA             │    │
│ │ Descripción breve                             │    │
│ └──────────────────────────────────────────────┘    │
├─ FOOTER ────────────────────────────────────────────┤
│ Estadísticas, censo, miembros online                 │
└─────────────────────────────────────────────────────┘
```

### 3.1 Banner rotatorio
- Array de imágenes, una aleatoria cada carga
- Overlay oscuro + título centrado

### 3.2 Indicador de fecha / calendario
- Muestra: día, estación, año
- Al clic → página `/calendario`
- Sistema: 1 día real = 2 días on-rol
- 4 estaciones × 60 días = 240 días = 1 año
- Tabla `rol_calendario`: fecha_real → dia_onrol, estacion, año, eventos JSON
- Admin puede editar eventos desde el calendario o Zona Privada

### 3.3 Tablón (grid de 4 cards + staff)
- **Últimos mensajes:** últimos 4-5 posts del foro (título, personaje, tiempo relativo)
- **Búsquedas activas:** hilos marcados como "búsqueda", con botón para crear nueva
- **Noticias:** solo staff, icono + titular + breve
- **Curiosidades:** rotatorio JS, base de datos editable por staff, lore del mundo
- **Staff:** lista de miembros por rol (Admin, Narrador, Moderador) con botón MP

### 3.4 Categorías
- Cada categoría es una card visual de ancho completo con imagen de fondo
- Muestra solo: imagen, nombre de categoría, descripción breve
- Sin contadores, sin últimos posts, sin "usuarios navegando"
- Al clic → página de categoría

---

## 4. Página de categoría (`/category/{id}`)

```
┌─ NAVBAR ────────────────────────────────────────────┐
│ 🏝️ ARCHIPIÉLAGO CANDELARIA                         │
│ Donde los sueños llegan a la orilla                 │
├─ SUBFOROS ──────────────────────────────────────────┤
│ ┌──────┐ ┌──────┐ ┌──────┐ ┌──────┐               │
│ │ ⚓    │ │ 🍺   │ │ 🏛️   │ │ 🏚️   │               │
│ │Pto.  │ │Tbrna │ │Ayunt │ │Casas │               │
│ │desc. │ │desc. │ │desc. │ │desc. │               │
│ └──────┘ └──────┘ └──────┘ └──────┘               │
├─ NOTAS DEL STAFF ──────────────────────────────────┤
│ 📍 Aviso importante del staff                      │
├─ TEMAS ────────────────────────────────────────────┤
│ 📌 [T3] La sombra del kraken    🗨️ 12   🔥 3      │
│    por Aris Thorne · hace 2 días                   │
│ 📌 [T1] Rumores de taberna       🗨️ 5    🔥 0      │
│    por Lyra Vance · hace 5 horas                   │
│ 📌 [T2] El cargamento perdido   🗨️ 23   🔥 7      │
│    por Kuro · hace 1 día                           │
└─────────────────────────────────────────────────────┘
```

### 4.1 Cabecera de categoría
- Nombre + descripción
- Imagen de fondo (la misma del index o variante)

### 4.2 Subforos
- Grid de tarjetas con icono, nombre y descripción
- Sin datos de última actividad en la card

### 4.3 Notas del staff
- Bloque visible arriba de los temas
- Staff puede añadir/editar notas desde Zona Privada
- Se muestra siempre, hasta que el staff la marque como "cerrada"

### 4.4 Listado de temas
- Sin: "usuarios navegando", "última visita"
- Columnas: título (con badge de rango T1/T2/T3), autor, respuestas, vistas, última actividad
- Paginación estándar

---

## 5. Página de calendario (`/calendario`)

- Vista del año completo con estaciones marcadas
- Días con eventos destacados
- Admin puede crear/editar eventos in-page
- Eventos: título, descripción, tipo (festivo, misiones, lore, torneo)
- Integración con `rol_calendario` en BD

---

## 6. Paleta de color (pendiente de definir colores exactos)

| Rol | Uso |
|---|---|
| Fondo base | Fondo general del foro |
| Fondo navbar | Navbar flotante |
| Fondo tarjetas | Cards del tablón, subforos, temas |
| Acento primario | Botones, enlaces, hover |
| Acento secundario | Badges de rango T1/T2/T3 |
| Texto principal | Cuerpo general |
| Texto muted | Metadatos, fechas |
| Éxito | Aprobado, positivo |
| Peligro | Rechazado, alerta |

> Pendiente de definir valores hex concretos y tipografía.

---

## 7. Decisiones abiertas (a detallar después)

- [ ] Nombre definitivo del foro
- [ ] Paleta de color exacta
- [ ] Tipografía (display + cuerpo + stats)
- [ ] Iconografía (sistema de iconos para foros, categorías, rangos)
- [ ] Sistema de badges T1/T2/T3 (rangos de dificultad o nivel de hilo)
- [ ] Sistema de "Búsquedas activas" (cómo se marca un hilo como búsqueda)
- [ ] Mensajería privada entre personajes (alcance para futura fase)
- [ ] Configuración de personaje (alcance para futura fase)
- [ ] Sistema de reacciones (🔥, etc.)
- [ ] Zona Privada: contenido concreto por rol
- [ ] Trámites: qué trámites existen
- [ ] Guías: qué contenido tienen

---

## 8. Arquitectura técnica

- **MyBB:** motor de foro, auth, BD de posts/temas/usuarios
- **Templates:** MyBB templates sobreescritos completamente con HTML/CSS propio
- **API propia:** Slim 4 + Eloquent (rol-backend/)
  - Endpoints de personajes (CRUD, activo, slots)
  - Endpoints de calendario
  - Endpoints de mensajería (futuro)
  - Endpoints de trámites (futuro)
- **Widgets JS:** rol-widgets.js para hidratar datos de la API en las páginas
- **Frontend assets:** CSS, JS, imágenes en `/assets/` dentro del directorio de MyBB

---

## 9. Flujo de usuario

### Visitante no logueado
1. Llega al índice → ve banner, tablón, categorías
2. Hace clic en categoría → ve subforos, no ve temas (o ve solo públicos)
3. Hace clic en [Personaje] → login
4. Se registra en MyBB → login → se crea cuenta de rol automáticamente

### Usuario logueado sin personaje
1. Login → redirige a `/personajes` (lista vacía)
2. Crea personaje (ocupa un slot)
3. Su personaje queda en estado "borrador"
4. Lo envía a revisión → staff lo aprueba
5. Selecciona personaje como activo
6. Ya puede postear como ese personaje

### Usuario con personaje activo
1. Índice → navbar muestra icono del personaje
2. Postea en foros → el post se vincula a su personaje activo
3. Cambia de personaje activo desde `/personajes`
4. Accede a mensajería desde el dropdown del navbar

---

## 10. Notas finales

- Este documento captura la estructura acordada en la sesión de brainstorming del 05/07/2026.
- Los detalles finos (colores, fuentes, iconos) se definirán en la implementación o en una sesión posterior.
- El diseño prioriza **cohesión visual** y **personalidad** sobre cantidad de features.

# Personaje - Aranagi V. Tadisai (UID 347)

**URL:** https://onepiecegaiden.com/op/personaje.php?uid=347

## Estructura general
- Header: barra de navegación global del foro (logo, Guías, Técnicas, Información, Registros, login/register)
- Pestañas horizontales: **PORTADA** | **BIOGRAFÍA** | **BÉLICO** | **TÉCNICAS** | **INVENTARIO**
- Nombre del personaje siempre visible entre tabs y contenido
- Layout de ancho completo con sidebar contextual

---

## Contenido por pestaña

Ver archivos adjuntos:
- `personaje_tab_portada.md` — Stats principales, atributos, reputación, akuma, rasgos, historial
- `personaje_tab_biografia.md` — Historia, apariencia, personalidad, oficios, mascotas
- `personaje_tab_belico.md` — Estilos, haki, estadísticas extendidas, disciplinas
- `personaje_tab_tecnicas.md` — Técnicas (vacío para este personaje)
- `personaje_tab_inventario.md` — Equipamiento, ítems, consumibles, armas, modificación

---

## Notas de diseño
- **Cambio de tabs:** vía JavaScript (`clickPestana('portada')`). El contenido de cada tab ya está en el DOM y se muestra/oculta.
- **Secciones colapsables:** Historia, Apariencia, Personalidad, Extra se expanden/contraen con clic.
- **Modal de equipamiento:** Popup superpuesto para gestionar equipamiento.
- **Modal de cronología:** Popup para ver historial cronológico del personaje.
- **Registro de modificaciones:** Sistema interno de control de cambios con campos obligatorios y razón.
- **Uso intensivo de imágenes:** Iconos personalizados para cada estadística, disciplina, tipo de ítem.
- **Estadísticas en tiempo real:** Barras de stats, valores calculados (base + pasiva).

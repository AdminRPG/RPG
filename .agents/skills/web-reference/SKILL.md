---
name: web-reference
description: Recorre y analiza una web de ejemplo (contenido y/o diseño) cuando el usuario da una URL de referencia para replicar patrones en este proyecto
---

# Web reference

Cuando el usuario dé una URL de ejemplo:
1. Usa el MCP de firecrawl para descargar el contenido de todas las páginas del dominio en markdown, guárdalas en docs/references/<dominio>/paginas/
2. Usa el MCP de playwright para navegar a la home y 2-3 páginas clave y capturar cada una, guárdalas en docs/references/<dominio>/capturas/
3. Resume en docs/references/<dominio>/resumen.md: estructura de navegación, patrones de layout, tono de contenido, componentes recurrentes
4. No implementes nada todavía: presenta el resumen y espera mi confirmación antes de aplicar cambios

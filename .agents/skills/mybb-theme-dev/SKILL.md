---
name: mybb-theme-dev
description: Convenciones para crear o editar temas y templates de MyBB (motor de plantillas, variables, alcance global vs por foro)
---

# Desarrollo de temas MyBB

- El motor de templates usa variables tipo {$lang->cadena} y {$mybb->settings['clave']}
- Los templates viven en la tabla mybb_templates; consulta su contenido real vía el MCP de mysql antes de reescribirlos
- Distingue entre templates globales (todo el foro) y templates de un tema específico
- Los estilos van en mybb_themestylesheets o en los archivos CSS del tema, según cómo esté organizado el proyecto
- Verifica cualquier cambio visual navegando el foro en local con el MCP de playwright antes de darlo por bueno

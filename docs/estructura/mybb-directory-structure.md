# Estructura de directorios MyBB 1.8

## Raíz del proyecto (instalación de MyBB)

```
├── admin/                           # Panel de Administración (ACP)
│   ├── backups/                     # Backups de BD (internal)
│   ├── inc/                         # Código fuente del ACP
│   ├── jscripts/                    # JS del ACP
│   ├── modules/                     # Módulos de páginas del ACP
│   └── styles/                      # CSS del ACP
├── archive/                         # Modo archivo
├── cache/
│   └── themes/                      # Temas compilados
├── images/
│   ├── avatars/                     # Avatares subidos
│   ├── badges/                      # Insignias
│   ├── cp/                          # Iconos del panel de control
│   ├── css_logos/                   # Logos CSS
│   ├── group_images/                # Imágenes de grupos
│   ├── guild/                       # Imágenes de clanes/guildas
│   ├── icons/                       # Iconos del foro
│   ├── publisher/                   # Imágenes de publicador
│   ├── ranks/                       # Imágenes de rangos
│   ├── ratings/                     # Estrellas de valoración
│   ├── redirect/                    # Imagen de redirección
│   └── smilies/                     # Smilies/emoticones
├── inc/                             # Código fuente (internal)
│   ├── 3rdparty/                    # Librerías externas
│   ├── config.default.php           # Plantilla de configuración
│   ├── languages/
│   │   ├── english/                 # Pack de idioma inglés
│   │   └── espanol/                 # Pack de idioma español
│   ├── plugins/                     # Plugins instalados
│   └── settings.php                 # Configuración compilada
├── install/                         # Instalador (eliminar tras uso)
├── jscripts/                        # JavaScript del foro
├── uploads/
│   └── avatars/                     # Avatares subidos por usuarios
├── global.php                       # Carga global
├── index.php                        # Índice del foro
├── showthread.php                   # Vista de hilo
├── forumdisplay.php                 # Vista de foro
├── member.php                       # Perfil de usuario
├── modcp.php                        # Panel de moderación
├── private.php                      # Mensajería privada
├── search.php                       # Búsqueda
├── usercp.php                       # Panel de usuario
├── memberlist.php                   # Lista de miembros
├── misc.php                         # Páginas varias
├── newreply.php                     # Responder hilo
├── newthread.php                    # Nuevo hilo
├── moderation.php                   # Acciones de moderación
├── portal.php                       # Portal (opcional)
├── printthread.php                  # Vista para imprimir
├── xmlhttp.php                      # AJAX handler
├── css.php                          # CSS dinámico
├── error.php                        # Página de error
├── stats.php                        # Estadísticas
├── ratethread.php                   # Valorar hilo
├── ajax.php                         # AJAX handler
├── calendar.php                     # Calendario
└── managegroup.php                  # Gestión de grupos
```

## Extensiones del proyecto (código propio fuera del core de MyBB)

```
├── rol-backend/                     # API de lógica de juego
├── mybb-plugin-rol/                 # Plugin puente (código fuente)
│   ├── inc/plugins/rolbridge.php    # → se despliega en inc/plugins/
│   └── jscripts/rol-widgets.js      # → se despliega en jscripts/
└── docs/                            # Documentación y contratos
```

## Notas

- Los directorios marcados como **internal** no deberían ser accesibles vía web.
- `install/` debe eliminarse tras la instalación del foro.
- Los plugins se instalan en `inc/plugins/` y se activan desde el ACP.
- Los temas se importan vía ACP (archivo XML) y se compilan en `cache/themes/`.
- Las imágenes de juego (guild, ranks, badges, group_images) son específicas para foros de rol/gaming.

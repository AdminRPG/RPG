# One Piece: 7 Seas

Foro de rol play-by-post del universo **One Piece** construido sobre **MyBB 1.8**,
con motor de juego propio (codename **Eternal** / plugin `ope_rol`).

> El proyecto se llama **One Piece: 7 Seas**. "Eternal" es el nombre del motor
> (prefijos `ope_`/`ope-`, plugin `ope_rol`) — no la marca del foro.

## Estado actual

**Instalación limpia, sin contenido inicial** — punto de partida de referencia:

- **Portada**: hero + bento (Calendario on-rol, Últimas historias, El Equipo) + **Los Mares** con 8 tarjetas-región vacías (East, West, North, South Blue, Paraíso, New World, Calm Belt, Red Line) + **Off Topic**.
- Sin islas, NPCs, lore, misiones ni mundo vivo sembrado. Los catálogos mecánicos (estilos, estados, Akuma no Mi, vocaciones) quedan con su esquema.
- Usuarios: `admin` y `OPE Eternal` (bot del sistema). El plugin `ope_rol` está activo.
- La BD de referencia se respalda/restaura según `docs/RESTAURAR-BACKUP.md`.

## Estructura

```
├── admin/               ← Panel de administración MyBB
├── inc/                 ← Core MyBB + backend ope_rol (core, catalogos, mundo, sistemas)
│   ├── plugins/ope_rol.php
│   └── ope_rol/         ← Lógica del juego (entrada: bootstrap.php)
├── images/              ← Recursos gráficos del foro (incl. frutas en images/frutas/)
├── jscripts/            ← JavaScript del foro
├── cache/               ← Temas compilados (runtime)
├── uploads/             ← Archivos subidos por usuarios
├── install/             ← Instalador MyBB
├── archive/             ← Modo archivo
├── backups/             ← Dumps SQL locales (gitignored)
├── docs/                ← Documentación (diseño, estilos, producto, respaldo)
├── scripts/             ← Migraciones, seeds canónicos y utilidades
└── .github/workflows/   ← CI/CD (despliegue FTP automático)
```

## Entorno local

- **URL:** `http://rpg.test/` (vhost apunta a la raíz del repo)
- **BD:** `rpg_forum` (MySQL, charset utf8mb4)
- **PHP:** 8.3 · **MyBB:** 1.8.39
- **Acceso admin:** usuario `admin` (contraseña del ACP en `http://rpg.test/admin/`)
- Los scripts se ejecutan con `MYBB_DB_NAME=rpg_forum` (ver `scripts/`)

## Backups

El estado limpio de referencia se guarda como dump SQL en `backups/`
(gitignored). Procedimiento completo de restauración y regeneración:
**`docs/RESTAURAR-BACKUP.md`**.

## Despliegue

El workflow `.github/workflows/deploy.yml` ensambla el docroot (`back/forum`,
excluyendo docs, scripts y cache) y lo sube por **FTP a InfinityFree** en cada
push a `main`. El tema y las plantillas se sincronizan con
`php scripts/sync-theme.php import` (fuente de verdad: `docs/themes/`).

## Documentación

| Doc | Contenido |
|---|---|
| `docs/DESIGN-ONE-PIECE-ETERNAL.md` | Fuente de verdad visual + scaffolding PHP |
| `docs/GUIA-ESTILOS-PHP.md` | Guía de estilos para páginas nuevas |
| `docs/PLAN-MAESTRO-ONE-PIECE-ETERNAL.md` | Visión, fases y mecánicas del sistema |
| `docs/PRODUCT.md` | Producto y copy |
| `docs/RESTAURAR-BACKUP.md` | Restaurar/regenerar la BD limpia |

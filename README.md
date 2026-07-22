# One Piece: Eternal

Foro de rol play-by-post sobre MyBB con backend propio de mecánicas de juego.

## Estructura

```
├── admin/               ← Panel de administración MyBB
├── inc/                 ← Core de MyBB (clases, plugins, idiomas)
├── images/              ← Recursos gráficos del foro
├── jscripts/            ← JavaScript del foro
├── cache/               ← Temas compilados
├── uploads/             ← Archivos subidos por usuarios
├── install/             ← Instalador MyBB
├── archive/             ← Modo archivo
├── rol-backend/         ← API REST de lógica de juego (Slim 4 + JWT)
├── mybb-plugin-rol/     ← Plugin puente MyBB (código fuente)
├── docs/                ← Documentación, prototipos y referencias
├── scripts/             ← Scripts utilitarios
└── .github/workflows/   ← CI/CD (despliegue automático)
```

## Despliegue

El directorio `back/forum/` es el docroot que se despliega a InfinityFree vía GitHub Actions FTP.

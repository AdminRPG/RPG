# I-Forge-RPG

Foro de rol play-by-post sobre MyBB con backend propio de mecánicas de juego.

## Estructura

```
├── back/
│   ├── forum/           ← Instalación MyBB (docroot desplegable)
│   ├── plugin/          ← Plugin puente MyBB (código fuente)
│   └── sql/             ← Migraciones de base de datos
├── rol-backend/         ← API REST de lógica de juego (Slim 4 + JWT)
├── front/               ← Fuentes del tema y plantillas
├── docs/                ← Documentación y contratos
├── agent.md             ← Configuración para agentes de IA
└── .github/workflows/   ← CI/CD (despliegue automático)
```

## Despliegue

El directorio `back/forum/` es el docroot que se despliega a InfinityFree vía GitHub Actions FTP.

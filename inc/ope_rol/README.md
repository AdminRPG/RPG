# Backend `ope_rol` — One Piece: Eternal

Estructura por capas (sin framework). El plugin carga todo vía
[`bootstrap.php`](bootstrap.php). Los archivos `inc/ope_rol_*.php` son
**stubs** que redirigen aquí (compatibilidad con páginas y scripts viejos).

```
inc/ope_rol/
├── bootstrap.php          ← entrada canónica (plugin)
├── README.md
│
├── core/                  Motor compartido
│   ├── data.php           Stats, razas/linajes, bootstrap de catálogos
│   └── system.php         Combate / PP / motor
│
├── catalogos/             Datos puros (arrays, sin SQL/HTML)
│   ├── linaje.php         Factor Linaje (rasgos, defectos, dotes)
│   ├── pj.php             Armas, facciones, packs, berries
│   └── gestion.php        Tienda, tripulaciones, bibliotecas (staff)
│
├── dominio/               Reglas / use-cases (puros, testeables)
│   └── creacion.php       Validar Factor Linaje (PL suma 0, híbridos)
│
├── sistemas/              Progresión y fama
│   ├── haki.php
│   ├── frutas.php
│   ├── enlace.php
│   ├── renombre.php
│   ├── pl.php
│   └── rachas.php
│
├── mundo/                 Mundo vivo + viajes
│   ├── mundo.php
│   ├── oraculo.php
│   ├── oraculo_post.php
│   ├── oraculo_v2.php
│   ├── viajes.php
│   ├── viaje_ai.php
│   ├── viaje_cola.php
│   ├── viaje_revision.php
│   ├── viaje_revision_ai.php
│   ├── misiones.php
│   ├── mision_oraculo.php
│   ├── mision_ai.php
│   ├── mision_post.php
│   ├── islas_cat.php
│   ├── matriz_rutas.php
│   ├── barcos.php
│   └── nav_items.php
│
└── tramites/
    └── tramites.php
```

## Cómo cargar

**Preferido** (plugin y páginas nuevas):

```php
require_once MYBB_ROOT . 'inc/ope_rol/bootstrap.php';
```

**Módulo suelto** (sigue válido):

```php
require_once MYBB_ROOT . 'inc/ope_rol_data.php';      // stub → core/data.php
require_once MYBB_ROOT . 'inc/ope_rol/sistemas/haki.php'; // ruta nueva directa
```

## Capas

| Capa | Qué hace | SQL | HTML |
|---|---|---|---|
| `catalogos/*` | Datos | No | No |
| `dominio/*` | Validación / cálculo | No | No |
| `core/*` | Motor + helpers | a veces | No |
| `sistemas/*` `mundo/*` `tramites/*` | Lógica de dominio + BD | Sí | No |
| Páginas `*.php` | Controller / vista | Sí | Sí |

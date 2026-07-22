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
│   ├── system.php         Combate / PP / Eternal (motor)
│   └── eternal.php        Árboles: load, render, picks
│
├── catalogos/             Datos puros (arrays, sin SQL/HTML)
│   ├── linaje.php         Factor Linaje (rasgos, defectos, dotes)
│   ├── pj.php             Armas, facciones, packs, berries
│   ├── eternal.php        Identidades + familias de arma
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
│   └── viajes.php
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
| `core/*` | Motor + helpers | a veces | render Eternal sí |
| `sistemas/*` `mundo/*` `tramites/*` | Lógica de dominio + BD | Sí | No |
| Páginas `*.php` | Controller / vista | Sí | Sí |

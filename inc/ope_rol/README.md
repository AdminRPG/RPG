# Backend `ope_rol` — One Piece: 7 Seas

Motor canónico del foro (prefijo `ope7_*`), por capas y sin framework. El plugin
[`inc/plugins/ope_rol.php`](../plugins/ope_rol.php) lo carga todo vía
[`bootstrap.php`](bootstrap.php). La fuente de la BD es el esquema `mybb_ope_*`
(Anexo A.1) — no existe ninguna tabla ni ruta `rol_*` / `mybb_rol_*` en el
proyecto.

```
inc/ope_rol/
├── bootstrap.php          ← entrada canónica (plugin) — solo el motor 7 Seas
├── README.md
│
├── core/                  Compartido
│   ├── data.php           Catálogos + tablas + helpers base
│   ├── system.php         Combate puro (PV/EN/PA, daño, estados) y PP (saldo/spend)
│   ├── bot.php            Bot «OPE Eternal» (posts automáticos del sistema)
│   └── permisos.php       Staff/narrador (mybb_ope_cuentas), envoltorios ope7_*
│
├── catalogos/             Datos puros (arrays / lectura de BD, sin HTML)
│   ├── gestion.php        Tiendas, tripulaciones y bibliotecas públicas
│   ├── linaje.php         Factor Linaje (rasgos, defectos, dotes)
│   ├── pj.php             Facciones, packs de equipo, berries, elementos
│   └── vocaciones.php     Especializaciones y armas vocacionales
│
├── dominio/               Reglas puras (sin SQL/HTML, testeables)
│   ├── creacion.php       Validación de creación (Factor Linaje, híbridos)
│   ├── personajes.php     PJ activo (puntero), resolución de cuenta, ficha
│   └── ficha.php          Render de la ficha 7 Seas (bloques, desglose auditable)
│
├── sistemas/              Reglas de juego (F2–F6)
│   ├── combate.php        Motor de combate puro (PA, Tablas de delta, estados)
│   ├── combate_ui.php     Panel Zona B bajo el editor (persistencia de turnos)
│   ├── progresion.php     Calendario on-rol, entrenamientos, dominios, PP
│   ├── progresion_panel.php  Panel staff «Progresión» (cronómetros, libro de PP)
│   ├── mundo.php          Mundo Vivo: mares, islas, ronda, panel staff
│   ├── navegacion.php     Travesías, IRT, oráculos, víveres
│   ├── facciones.php      Trámites de facción, rangos, cupos, Shichibukai
│   ├── conquista.php      Asedios, unidades/hordas, reconquista
│   ├── barcos.php         Compra/construcción/mejora/módulos/reparación/venta
│   ├── akumas.php         Frutas del diablo y Haki (45–51)
│   ├── frutas.php         Helpers de akuma para la ficha/postbit
│   ├── haki.php           Helpers de Haki para la ficha/postbit
│   ├── misiones.php       Narradores y auto-narradas (52–55)
│   ├── tripulaciones.php  Fundación, ingreso, baja, cambios, disolución (63–67)
│   ├── cibernetica.php    Implantes y Familias Legendarias (56–61)
│   ├── bajomundo.php      Rumores, redes, carteles, cazarrecompensas (25–33)
│   ├── muertes.php        Muerte (trámite 62) y reliquias
│   ├── npc.php            NPC mayores/bestiario y reclutamiento (trámite 19)
│   ├── inventario.php     Ranuras, equipamiento y carga
│   └── tiendas.php        Tiendas por isla, mercado y panel staff
│
└── tramites/              Motor transversal de los 67 trámites (5.21)
    ├── catalogo.php       Catálogo cerrado (número, sistema, skill, quién, efecto, prompt)
    ├── motor.php          Aplicar efectos, firma, histórico, helpers
    ├── bandeja.php        Bandeja del motor (estados, lotes)
    └── paginas.php        Fuentes de datos y formularios de los trámites
```

## Cómo cargar

**Preferido** (plugin y páginas nuevas):

```php
require_once MYBB_ROOT . 'inc/ope_rol/bootstrap.php';
```

Ya no existen los stubs `inc/ope_rol_*.php` ni `mundo/*`: el motor 7 Seas se
carga entero por `bootstrap.php` y las páginas de la raíz requieren
`core/data.php` + `core/system.php` cuando necesitan helpers sueltos.

## Capas

| Capa | Qué hace | SQL | HTML |
|---|---|---|---|
| `catalogos/*` | Datos | No | No |
| `dominio/*` | Validación / cálculo | No | No |
| `core/*` | Motor + helpers | a veces | No |
| `sistemas/*` | Lógica de juego + BD | Sí | No (excepto paneles/UI propios) |
| `tramites/*` | Motor transversal de trámites | Sí | Sí (formularios) |
| Páginas `*.php` | Controller / vista | Sí | Sí |

## Convención

Toda función nueva del motor 7 Seas usa el prefijo `ope7_` (no colisiona con los
helpers `ope_rol_*` del plugin, que leen la fuente canónica `mybb_ope_*`).
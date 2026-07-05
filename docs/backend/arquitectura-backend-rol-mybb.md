# Arquitectura de Backend para Foro de Rol sobre MyBB
### Documento maestro de diseño técnico

---

## 0. Principios de diseño

1. **El foro se rige por personajes, no por cuentas.** Una cuenta (MyBB user) posee N slots de personaje. Los posts, economía, inventario y dados pertenecen a personajes, no a cuentas. Cada cuenta tiene un personaje "activo" que es el que postea.
2. **MyBB es la fuente de verdad de identidad y contenido narrativo.** Usuarios, hilos, posts, permisos y moderación viven en MyBB. No se duplican.
3. **El backend propio es la fuente de verdad de "estado de juego".** Fichas, inventario, economía, dados, mapas, relaciones entre personajes.
4. **Separación de bases de datos (o al menos de esquema).** Nunca se escribe directamente sobre tablas `mybb_*` salvo lectura puntual de usuario/sesión. Así una migración tuya nunca puede romper el foro.
5. **Todo lo dinámico se sirve por AJAX.** Las páginas de MyBB se mantienen ligeras; los widgets de rol (ficha en el postbit, inventario en el perfil, panel de dados) se hidratan vía JS después de la carga, contra tu propia API.
6. **Idempotencia y auditoría en todo lo que mueva "recursos de juego".** Economía, inventario y tiradas de dados son sensibles a duplicación/trampas: cada acción queda registrada.
7. **Autenticación puente, no duplicada.** El login sigue siendo el de MyBB. Tu API valida contra un token firmado emitido tras el login.

---

## 1. Visión general de la arquitectura

```
                         ┌────────────────────────────┐
                         │        Navegador            │
                         │  (páginas MyBB + widgets JS) │
                         └───────────┬─────────────────┘
                                     │ HTTP / AJAX (fetch)
                 ┌───────────────────┼────────────────────┐
                 │                                        │
      ┌──────────▼───────────┐                 ┌──────────▼───────────┐
      │   MyBB (PHP/MySQL)    │                 │   API de Rol (propia) │
      │   - Auth / sesión      │◄───────────────┤   - REST endpoints     │
      │   - Foros / hilos       │  valida sesión │   - Lógica de juego     │
      │   - Plugin puente        │  (token/cookie)│   - Reglas de negocio    │
      └──────────┬───────────┘                 └──────────┬───────────┘
                 │                                        │
      ┌──────────▼───────────┐                 ┌──────────▼───────────┐
      │  BD MyBB (mybb_*)      │                 │  BD Rol (rol_*)         │
      │  usuarios, posts, ...  │                 │  cuentas, personajes,   │
      └────────────────────────┘                 │  items, economia,       │
                                                 │  dados, post_personaje  │
                                                 └──────────────────────────┘
```

### Jerarquía de dominio

```
MyBB User (mybb_users.uid)
  └── Cuenta (rol_cuentas)
        ├── max_slots (ej. 3)
        ├── es_narrador (bool)
        │
        ├── Personaje 1 (slot 0)  ← activo? sí/no
        │     ├── atributos (EAV)
        │     ├── inventario
        │     ├── saldo económico
        │     └── tiradas de dados
        │
        ├── Personaje 2 (slot 1)
        └── Personaje 3 (slot 2)

Post de MyBB (mybb_posts.pid)
  └── rol_post_personaje → personaje_id
        (cada post se asocia al personaje activo al momento de publicar)
```

**Comunicación:** el navegador nunca "sabe" que hay dos sistemas. El plugin de MyBB inyecta contenedores HTML vacíos y un script hace `fetch()` contra tu API para rellenarlos.

---

## 2. Stack tecnológico recomendado

| Capa | Recomendación | Alternativa |
|---|---|---|
| Foro | MyBB (PHP 8.x) | — |
| API propia | **PHP 8.x + Slim Framework** | Node.js + Express / Python + FastAPI |
| Base de datos | MySQL/MariaDB (mismo motor que MyBB, distinto esquema) | PostgreSQL si prefieres JSONB |
| Autenticación puente | Token JWT firmado, emitido por plugin de MyBB al hacer login | Sesión de MyBB leída directamente |
| Frontend widgets | JS vanilla + `fetch()` | Alpine.js para reactividad ligera |
| Caché | Redis (cooldowns, rate limiting, sesiones) | APCu si el volumen es bajo |
| ORM | Eloquent (illuminate/database) | PDO directo |

**Por qué PHP para la API:** al compartir lenguaje con MyBB, puedes reutilizar su capa de DB sin montar un microservicio en otro runtime, y los plugins se integran de forma nativa vía hooks.

---

## 3. Estructura de carpetas del backend propio

```
rol-backend/
├── public/
│   └── index.php              # Front controller, punto de entrada único
├── src/
│   ├── Auth/
│   │   ├── JWTService.php          # encode/decode HS256
│   │   └── MyBBSessionBridge.php   # Validación contra sesión MyBB
│   ├── Controllers/
│   │   ├── CuentaController.php    # slots, personaje activo, narrator
│   │   ├── FichaController.php     # CRUD de personajes + aprobación
│   │   ├── PostController.php      # Vincular post ↔ personaje
│   │   ├── InventarioController.php
│   │   ├── EconomiaController.php
│   │   └── DadosController.php
│   ├── Models/
│   │   ├── Cuenta.php              # Account (extends Eloquent Model)
│   │   ├── Personaje.php           # Character (reemplaza Ficha.php)
│   │   ├── FichaAtributo.php       # EAV attributes for characters
│   │   ├── PostPersonaje.php       # Post↔character mapping
│   │   ├── Item.php
│   │   ├── Transaccion.php
│   │   └── Tirada.php
│   ├── Repositories/               # Acceso a BD separado de lógica (futuro)
│   ├── Services/
│   │   ├── DiceService.php
│   │   ├── EconomyService.php
│   │   └── ApprovalService.php
│   ├── Middleware/
│   │   ├── AuthMiddleware.php
│   │   ├── RateLimitMiddleware.php
│   │   └── CorsMiddleware.php
│   └── Routes/
│       └── api.php
├── database/
│   ├── migrations/
│   │   └── 001_cuentas_personajes.sql
│   └── seeds/
├── tests/
├── .env
└── composer.json
```

```
mybb-plugin-rol/                # Plugin instalado dentro de MyBB
├── inc/plugins/rolbridge.php   # Hooks: login, logout, postbit, profile, editor
└── jscripts/rol-widgets.js     # fetch() hacia la API propia
```

---

## 4. Esquema de base de datos propio (`rol_*`)

### 4.1 Cuentas de rol

```sql
CREATE TABLE rol_cuentas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    mybb_user_id INT NOT NULL UNIQUE,
    max_slots TINYINT NOT NULL DEFAULT 3,
    es_narrador BOOLEAN NOT NULL DEFAULT FALSE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_mybb_user (mybb_user_id)
);
```

### 4.2 Personajes

```sql
CREATE TABLE rol_personajes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cuenta_id INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    raza VARCHAR(100),
    clase VARCHAR(100),
    edad INT,
    historia TEXT,
    avatar_url VARCHAR(255),
    estado ENUM('borrador','pendiente','aprobado','rechazado','retirado') DEFAULT 'borrador',
    activo BOOLEAN NOT NULL DEFAULT FALSE,
    slot_index TINYINT NOT NULL DEFAULT 0,
    aprobado_por INT NULL,
    fecha_aprobacion DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cuenta_id) REFERENCES rol_cuentas(id) ON DELETE CASCADE,
    INDEX idx_cuenta (cuenta_id),
    INDEX idx_cuenta_activo (cuenta_id, activo),
    INDEX idx_estado (estado)
);
```

### 4.3 Atributos de personaje (EAV)

```sql
CREATE TABLE rol_ficha_atributos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    personaje_id INT NOT NULL,
    clave VARCHAR(50) NOT NULL,         -- ej. "fuerza", "magia"
    valor VARCHAR(255) NOT NULL,
    FOREIGN KEY (personaje_id) REFERENCES rol_personajes(id) ON DELETE CASCADE
);
```

### 4.4 Mapeo post → personaje

```sql
CREATE TABLE rol_post_personaje (
    id INT PRIMARY KEY AUTO_INCREMENT,
    post_id INT NOT NULL UNIQUE,          -- mybb_posts.pid
    personaje_id INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (personaje_id) REFERENCES rol_personajes(id) ON DELETE CASCADE,
    INDEX idx_post (post_id),
    INDEX idx_personaje (personaje_id)
);
```

### 4.5 Items, inventario, economía, dados

Estas tablas quedan sin cambios estructurales, solo referencian `personaje_id` en lugar de `mybb_user_id`:

```sql
CREATE TABLE rol_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    tipo VARCHAR(50),
    valor_economico DECIMAL(10,2) DEFAULT 0,
    metadata JSON NULL
);

CREATE TABLE rol_inventario (
    id INT PRIMARY KEY AUTO_INCREMENT,
    personaje_id INT NOT NULL,
    item_id INT NOT NULL,
    cantidad INT DEFAULT 1,
    adquirido_en DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (personaje_id) REFERENCES rol_personajes(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES rol_items(id)
);

CREATE TABLE rol_economia_saldo (
    personaje_id INT PRIMARY KEY,
    saldo DECIMAL(10,2) DEFAULT 0,
    FOREIGN KEY (personaje_id) REFERENCES rol_personajes(id) ON DELETE CASCADE
);

CREATE TABLE rol_economia_transacciones (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    origen_personaje_id INT NULL,
    destino_personaje_id INT NULL,
    monto DECIMAL(10,2) NOT NULL,
    tipo ENUM('transferencia','recompensa','compra','ajuste_admin') NOT NULL,
    referencia VARCHAR(255) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE rol_tiradas (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    personaje_id INT NOT NULL,
    formula VARCHAR(50) NOT NULL,
    resultado INT NOT NULL,
    detalle JSON NOT NULL,
    hilo_mybb_id INT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

---

## 5. Autenticación puente MyBB ↔ API propia

### 5.1 JWT emitido en login

1. El usuario inicia sesión normalmente en MyBB.
2. Hook `member_do_login_end` se dispara tras login exitoso.
3. El plugin genera un JWT firmado (HS256, secreto compartido en `.env`).
4. El JWT se guarda en cookie `rol_token`, separada de la cookie de sesión de MyBB.
5. Cada request AJAX envía `Authorization: Bearer <token>`.
6. La API valida firma + expiración sin tocar la BD de MyBB.
7. Si expiró pero la sesión MyBB sigue viva, `/auth/refresh` re-emite.

### 5.2 Auto-creación de cuenta de rol

En el mismo hook de login, el plugin hace una llamada a `GET /cuenta/mi-cuenta` con el JWT recién creado. Si la cuenta no existe, el endpoint `CuentaController::miCuenta` usa `firstOrCreate` para crearla automáticamente con 3 slots y `es_narrador = false`.

### 5.3 Flujo de personaje activo

```
Login exitoso
  → JWT emitido, cookie rol_token
  → Cuenta de rol auto-creada si no existe
  → El frontend llama a GET /cuenta/personaje-activo
      ├── Si existe: mostrar nombre en header/postbit
      └── Si 404: redirigir a selector/selección de personaje
```

### 5.4 Selección de personaje al postear

Cuando el usuario abre `newreply.php` o `newthread.php`:
1. El plugin inyecta un `<select>` con los personajes aprobados del usuario.
2. El JS (`rol-widgets.js`) rellena el select vía `GET /cuenta/mi-cuenta`.
3. Al enviar el post, se envía `rol_char_id` en el POST.
4. El plugin guarda la selección en cookie `rol_char_id` (30 días).
5. El post se vincula al personaje vía `POST /posts/vincular` (llamada desde el widget JS tras la publicación, o directamente desde el plugin PHP).

---

## 6. Diseño de la API (contrato REST)

Prefijo base: `/api/v1`

### 6.1 Auth

| Método | Endpoint | Auth | Descripción |
|---|---|---|---|
| `POST` | `/auth/refresh` | Cookie sesión MyBB | Renueva JWT si la sesión MyBB sigue activa |

### 6.2 Cuenta (account-level)

| Método | Endpoint | Auth | Descripción |
|---|---|---|---|
| `GET` | `/cuenta/mi-cuenta` | JWT | Datos de la cuenta del usuario autenticado (slots, narrador, lista de personajes) |
| `GET` | `/cuenta/personaje-activo` | JWT | Personaje activo del usuario autenticado (con atributos) |
| `POST` | `/cuenta/establecer-activo/{personajeId}` | JWT | Cambiar el personaje activo |

### 6.3 Personajes

| Método | Endpoint | Auth | Descripción |
|---|---|---|---|
| `GET` | `/personajes/mios` | JWT | Lista de personajes del usuario autenticado |
| `GET` | `/personajes/{id}` | — | Detalle de ficha (pública si aprobada) |
| `POST` | `/personajes` | JWT | Crear personaje en estado `borrador` (valida slots disponibles) |
| `PUT` | `/personajes/{id}` | JWT | Editar personaje propio (solo si está en `borrador`) |
| `POST` | `/personajes/{id}/enviar` | JWT | Pasar de `borrador` a `pendiente` |
| `POST` | `/personajes/{id}/aprobar` | JWT (staff) | Aprobar ficha |
| `POST` | `/personajes/{id}/rechazar` | JWT (staff) | Rechazar ficha con motivo |
| `GET` | `/personajes/activo/{userId}` | — | Obtener personaje activo de cualquier usuario (para widgets públicos) |

### 6.4 Posts

| Método | Endpoint | Auth | Descripción |
|---|---|---|---|
| `POST` | `/posts/vincular` | JWT | Vincular un post de MyBB al personaje activo |
| `GET` | `/posts/{postId}/personaje` | — | Obtener el personaje asociado a un post |

### 6.5 Inventario, Economía, Dados (sin cambios)

| Método | Endpoint | Auth | Descripción |
|---|---|---|---|
| `GET` | `/inventario/{personajeId}` | JWT | Listar inventario del personaje |
| `POST` | `/inventario/{personajeId}/items` | JWT (staff) | Añadir item |
| `POST` | `/inventario/transferir` | JWT | Transferir item entre personajes |
| `GET` | `/economia/{personajeId}/saldo` | JWT | Saldo actual |
| `POST` | `/economia/transferir` | JWT | Transferencia entre personajes |
| `GET` | `/economia/{personajeId}/historial` | JWT | Histórico de transacciones |
| `POST` | `/dados/tirar` | JWT | Tirada server-side |
| `GET` | `/dados/historial/{hiloId}` | — | Historial de tiradas de un hilo |

### Convenciones

- Respuestas: `{ "success": bool, "data": ..., "error": null|string }`.
- Paginación: `?page=&per_page=` en listados.
- Endpoints mutadores exigen JWT válido + verificación de pertenencia (`personaje.cuenta.mybb_user_id === token.mybb_user_id`).
- Rate limiting en endpoints sensibles.

---

## 7. Casos de uso AJAX (frontend ↔ API)

### 7.1 Widget de personaje en el postbit

```php
// inc/plugins/rolbridge.php — hook postbit
function rolbridge_postbit(&$post) {
    $post['rol_widget'] = '<div class="rol-ficha-widget" data-user="'.$post['uid'].'"></div>';
}
$plugins->add_hook('postbit', 'rolbridge_postbit');
```

```javascript
// jscripts/rol-widgets.js
document.querySelectorAll('.rol-ficha-widget').forEach(async (el) => {
  const res = await fetch(`/api/v1/personajes/activo/${el.dataset.user}`);
  if (res.ok) {
    const { data } = await res.json();
    el.innerHTML = `<strong>${data.nombre}</strong> — ${data.raza}`;
  }
});
```

### 7.2 Selector de personaje al postear

Contexto: en `newreply.php` y `newthread.php`, el usuario selecciona con qué personaje publica.

```javascript
// Rellenar <select> con los personajes del usuario
const res = await fetch('/api/v1/cuenta/mi-cuenta');
const { data } = await res.json();
const select = document.getElementById('rol-active-char');
data.personajes.filter(c => c.estado === 'aprobado').forEach(c => {
  const opt = document.createElement('option');
  opt.value = c.id; opt.textContent = c.nombre;
  if (c.activo) opt.selected = true;
  select.appendChild(opt);
});
```

### 7.3 Cambiar personaje activo desde el perfil

```javascript
async function cambiarActivo(personajeId) {
  const res = await fetch(`/api/v1/cuenta/establecer-activo/${personajeId}`, { method: 'POST' });
  if (res.ok) mostrarExito('Personaje activo cambiado');
}
```

### 7.4 Crear y enviar ficha a aprobación

```javascript
async function crearFicha(data) {
  const res = await fetch('/api/v1/personajes', {
    method: 'POST',
    headers: { 'Authorization': `Bearer ${getRolToken()}`, 'Content-Type': 'application/json' },
    body: JSON.stringify(data)
  });
  const { success, data: personaje, error } = await res.json();
  if (!success) return mostrarError(error);

  await fetch(`/api/v1/personajes/${personaje.id}/enviar`, { method: 'POST' });
  mostrarExito('Ficha enviada a revisión');
}
```

### 7.5 Tirada de dados desde un hilo

```javascript
async function tirarDados(formula, hiloId) {
  const res = await fetch('/api/v1/dados/tirar', {
    method: 'POST',
    headers: { 'Authorization': `Bearer ${getRolToken()}`, 'Content-Type': 'application/json' },
    body: JSON.stringify({ formula, hilo_mybb_id: hiloId })
  });
  const { success, data } = await res.json();
  if (success) {
    insertarEnEditor(`[dado: ${formula} = ${data.resultado}]`);
  }
}
```

---

## 8. Seguridad — checklist

- [ ] CORS restringido solo al dominio del foro (nunca `*`).
- [ ] Rate limiting en dados, economía y creación de fichas (Redis/APCu).
- [ ] Toda tirada y transacción calculada **server-side**.
- [ ] Transacciones SQL en operaciones que muevan saldo o inventario.
- [ ] Validación de pertenencia en cada endpoint mutador.
- [ ] JWT con expiración corta (1h) + refresh silencioso.
- [ ] Logs de auditoría inmutables (solo INSERT, nunca UPDATE/DELETE).
- [ ] Sanitización de HTML en campos de texto libre (XSS).

---

## 9. Roadmap de implementación sugerido

| Fase | Entregable |
|---|---|
| 0 | **Arquitectura character-driven** (SQL migrations, Models, CuentaController, post↔personaje mapping) |
| 1 | Plugin puente MyBB (JWT en login + ensureAccount + character selector en posts) |
| 2 | CRUD de personajes + flujo de aprobación + widget en postbit |
| 3 | Inventario + items maestro |
| 4 | Economía (saldo + transferencias + historial) |
| 5 | Dados server-side + integración en el editor de post |
| 6 | Panel de staff (aprobaciones, ajustes de economía, auditoría) |
| 7 | Endurecimiento: rate limiting, logs, tests automatizados |

---

## 10. Notas finales

- Empieza por el **puente de autenticación**: es la pieza que, si falla, bloquea todo lo demás.
- La tabla `rol_cuentas` se crea automáticamente con `firstOrCreate` en el primer login — no requiere migración manual.
- El mapeo `rol_post_personaje` es **inmutable**: una vez creado no se modifica, para mantener el histórico de qué personaje publicó cada post.
- Slots ampliables: cambiar `max_slots` en `rol_cuentas` permite aumentar los personajes por cuenta sin migración.

# Arquitectura de Backend para Foro de Rol sobre MyBB
### Documento maestro de diseño técnico

---

## 0. Principios de diseño

1. **MyBB es la fuente de verdad de identidad y contenido narrativo.** Usuarios, hilos, posts, permisos y moderación viven en MyBB. No se duplican.
2. **El backend propio es la fuente de verdad de "estado de juego".** Fichas, inventario, economía, dados, mapas, relaciones entre personajes.
3. **Separación de bases de datos (o al menos de esquema).** Nunca se escribe directamente sobre tablas `mybb_*` salvo lectura puntual de usuario/sesión. Así una migración tuya nunca puede romper el foro.
4. **Todo lo dinámico se sirve por AJAX.** Las páginas de MyBB se mantienen ligeras; los widgets de rol (ficha en el postbit, inventario en el perfil, panel de dados) se hidratan vía JS después de la carga, contra tu propia API.
5. **Idempotencia y auditoría en todo lo que mueva "recursos de juego".** Economía, inventario y tiradas de dados son sensibles a duplicación/trampas: cada acción queda registrada.
6. **Autenticación puente, no duplicada.** El login sigue siendo el de MyBB. Tu API valida contra la sesión de MyBB o contra un token firmado emitido tras el login.

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
     │  usuarios, posts, ...  │                 │  fichas, items, dados... │
     └────────────────────────┘                 └──────────────────────────┘
```

**Comunicación:** el navegador nunca "sabe" que hay dos sistemas. El plugin de MyBB inyecta contenedores HTML vacíos (`<div id="ficha-widget" data-user="123">`) y un script hace `fetch()` contra tu API para rellenarlos.

---

## 2. Stack tecnológico recomendado

| Capa | Recomendación | Alternativa |
|---|---|---|
| Foro | MyBB (PHP 8.x) | — |
| API propia | **PHP 8.x + Slim Framework** (o Laravel si el proyecto crecerá mucho) | Node.js + Express / Python + FastAPI |
| Base de datos | MySQL/MariaDB (mismo motor que MyBB, distinto esquema) | PostgreSQL si prefieres JSONB para fichas flexibles |
| Autenticación puente | Token JWT firmado, emitido por un plugin de MyBB al hacer login | Lectura directa de sesión de MyBB desde PHP |
| Frontend widgets | JS vanilla + `fetch()` (o Alpine.js para reactividad ligera) | React solo si el panel de rol es muy complejo (mapa interactivo, etc.) |
| Caché | Redis (cooldowns de dados, rate limiting, sesiones) | Archivos/APCu si el volumen es bajo |
| Colas (opcional) | Redis + worker simple (para narrativa con eventos: subir de nivel, loot drops) | — |

**Por qué PHP para la API:** al compartir lenguaje con MyBB, puedes reutilizar su capa de sesión/DB sin montar un microservicio en otro runtime, y los plugins de MyBB (hooks) se integran de forma nativa. Si el equipo domina más Node/Python, esa opción es igualmente válida — lo importante es el contrato de la API, no el lenguaje.

---

## 3. Estructura de carpetas del backend propio

```
rol-backend/
├── public/
│   └── index.php              # Front controller, punto de entrada único
├── src/
│   ├── Auth/
│   │   ├── MyBBSessionBridge.php   # Valida sesión/token contra MyBB
│   │   └── JWTService.php
│   ├── Controllers/
│   │   ├── FichaController.php
│   │   ├── InventarioController.php
│   │   ├── EconomiaController.php
│   │   ├── DadosController.php
│   │   └── AprobacionController.php
│   ├── Models/
│   │   ├── Ficha.php
│   │   ├── Item.php
│   │   ├── Transaccion.php
│   │   └── Tirada.php
│   ├── Repositories/           # Acceso a BD, separado de lógica de negocio
│   ├── Services/
│   │   ├── DiceService.php     # Lógica de tiradas (RNG, modificadores)
│   │   ├── EconomyService.php  # Transferencias, validación de saldo
│   │   └── ApprovalService.php # Flujo de aprobación de fichas
│   ├── Middleware/
│   │   ├── AuthMiddleware.php
│   │   ├── RateLimitMiddleware.php
│   │   └── CorsMiddleware.php
│   └── Routes/
│       └── api.php
├── database/
│   ├── migrations/
│   └── seeds/
├── tests/
├── .env
└── composer.json
```

```
mybb-plugin-rol/                # Plugin instalado dentro de MyBB
├── inc/plugins/rolbridge.php   # Hooks: login, logout, postbit, member_profile
└── jscripts/rol-widgets.js     # fetch() hacia la API propia
```

---

## 4. Esquema de base de datos propio (`rol_*`)

Todas las tablas usan `mybb_user_id` como referencia al usuario de MyBB (sin foreign key real entre bases de datos distintas, se valida a nivel de aplicación).

```sql
-- Personajes / Fichas
CREATE TABLE rol_personajes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    mybb_user_id INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    raza VARCHAR(100),
    edad INT,
    historia TEXT,
    avatar_url VARCHAR(255),
    estado ENUM('borrador','pendiente','aprobado','rechazado') DEFAULT 'borrador',
    aprobado_por INT NULL,
    fecha_aprobacion DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (mybb_user_id),
    INDEX idx_estado (estado)
);

-- Atributos de ficha (flexible, evita columnas rígidas)
CREATE TABLE rol_ficha_atributos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    personaje_id INT NOT NULL,
    clave VARCHAR(50) NOT NULL,      -- ej. "fuerza", "magia"
    valor VARCHAR(255) NOT NULL,
    FOREIGN KEY (personaje_id) REFERENCES rol_personajes(id) ON DELETE CASCADE
);

-- Items maestro
CREATE TABLE rol_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    tipo VARCHAR(50),                -- arma, consumible, cosmético...
    valor_economico DECIMAL(10,2) DEFAULT 0,
    metadata JSON NULL                -- efectos, stats, etc.
);

-- Inventario por personaje
CREATE TABLE rol_inventario (
    id INT PRIMARY KEY AUTO_INCREMENT,
    personaje_id INT NOT NULL,
    item_id INT NOT NULL,
    cantidad INT DEFAULT 1,
    adquirido_en DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (personaje_id) REFERENCES rol_personajes(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES rol_items(id)
);

-- Economía: saldo
CREATE TABLE rol_economia_saldo (
    personaje_id INT PRIMARY KEY,
    saldo DECIMAL(10,2) DEFAULT 0,
    FOREIGN KEY (personaje_id) REFERENCES rol_personajes(id) ON DELETE CASCADE
);

-- Economía: histórico de transacciones (auditoría, evita trampas)
CREATE TABLE rol_economia_transacciones (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    origen_personaje_id INT NULL,
    destino_personaje_id INT NULL,
    monto DECIMAL(10,2) NOT NULL,
    tipo ENUM('transferencia','recompensa','compra','ajuste_admin') NOT NULL,
    referencia VARCHAR(255) NULL,     -- ej. id de hilo/post relacionado
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_origen (origen_personaje_id),
    INDEX idx_destino (destino_personaje_id)
);

-- Tiradas de dados (auditoría anti-trampa: todo tirado server-side)
CREATE TABLE rol_tiradas (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    personaje_id INT NOT NULL,
    formula VARCHAR(50) NOT NULL,     -- ej. "2d6+3"
    resultado INT NOT NULL,
    detalle JSON NOT NULL,            -- cada dado individual, para transparencia
    hilo_mybb_id INT NULL,            -- referencia al thread de MyBB donde se usó
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_personaje (personaje_id),
    INDEX idx_hilo (hilo_mybb_id)
);

-- Sesiones puente (opcional, si no usas JWT stateless)
CREATE TABLE rol_sesiones_puente (
    token VARCHAR(128) PRIMARY KEY,
    mybb_user_id INT NOT NULL,
    expires_at DATETIME NOT NULL
);
```

---

## 5. Autenticación puente MyBB ↔ API propia

**Flujo recomendado (JWT emitido en login):**

1. El usuario inicia sesión normalmente en MyBB.
2. Un **hook `member_do_login_end`** (plugin propio) se dispara tras login exitoso.
3. El plugin genera un JWT firmado (HS256, secreto compartido en `.env` de ambos lados) con `{ mybb_user_id, username, usergroup, exp }`.
4. El JWT se guarda en una cookie propia (`rol_token`), separada de la cookie de sesión de MyBB.
5. Cada request AJAX del widget de rol envía ese JWT en el header `Authorization: Bearer <token>`.
6. La API propia valida firma + expiración, sin tocar la BD de MyBB en cada request (rápido, sin acoplar disponibilidad).
7. Si el JWT expiró pero la sesión de MyBB sigue viva, se re-emite silenciosamente (endpoint `/auth/refresh` que sí valida contra la sesión de MyBB).

```php
// inc/plugins/rolbridge.php (fragmento)
function rolbridge_login_end() {
    global $mybb;
    $payload = [
        'mybb_user_id' => (int) $mybb->user['uid'],
        'username'     => $mybb->user['username'],
        'usergroup'    => (int) $mybb->user['usergroup'],
        'iat'          => time(),
        'exp'          => time() + 3600,
    ];
    $jwt = JWTService::encode($payload, getenv('ROL_JWT_SECRET'));
    my_setcookie('rol_token', $jwt, time() + 3600, true);
}
$plugins->add_hook('member_do_login_end', 'rolbridge_login_end');
```

```php
// src/Middleware/AuthMiddleware.php (API propia)
public function handle(Request $request, RequestHandler $next): Response {
    $token = $this->extractBearerToken($request);
    try {
        $payload = JWTService::decode($token, getenv('ROL_JWT_SECRET'));
    } catch (ExpiredTokenException $e) {
        return $this->json(['error' => 'token_expired'], 401);
    }
    $request = $request->withAttribute('user_id', $payload['mybb_user_id']);
    return $next->handle($request);
}
```

**Por qué así y no leer la sesión de MyBB directamente en cada request:** desacopla disponibilidad (tu API no depende de tener acceso directo a la tabla `mybb_sessions` en cada llamada) y es igual de seguro si el secreto está bien protegido.

---

## 6. Diseño de la API (contrato REST)

Prefijo base: `/api/v1`

| Método | Endpoint | Descripción |
|---|---|---|
| `POST` | `/auth/refresh` | Renueva JWT si la sesión MyBB sigue activa |
| `GET` | `/personajes/mios` | Lista personajes del usuario autenticado |
| `GET` | `/personajes/{id}` | Detalle de ficha (pública si aprobada) |
| `POST` | `/personajes` | Crea ficha en estado `borrador` |
| `PUT` | `/personajes/{id}` | Edita ficha propia (solo si no está aprobada, o según reglas del foro) |
| `POST` | `/personajes/{id}/enviar` | Pasa de `borrador` a `pendiente` |
| `POST` | `/personajes/{id}/aprobar` | (staff) aprueba ficha |
| `POST` | `/personajes/{id}/rechazar` | (staff) rechaza con motivo |
| `GET` | `/inventario/{personajeId}` | Lista inventario |
| `POST` | `/inventario/{personajeId}/items` | Añade item (staff/admin o evento) |
| `POST` | `/inventario/transferir` | Transfiere item entre personajes |
| `GET` | `/economia/{personajeId}/saldo` | Saldo actual |
| `POST` | `/economia/transferir` | Transferencia entre personajes |
| `GET` | `/economia/{personajeId}/historial` | Histórico de transacciones |
| `POST` | `/dados/tirar` | Tirada de dados server-side |
| `GET` | `/dados/historial/{hiloId}` | Historial de tiradas de un hilo |

**Convenciones:**
- Respuestas siempre `{ "success": bool, "data": ..., "error": null|string }`.
- Paginación con `?page=&per_page=` en listados.
- Todo endpoint mutador (`POST`/`PUT`) exige JWT válido + verifica que el `personaje_id` pertenece al `user_id` del token (o que el usuario tiene rol de staff).
- Rate limiting en `/dados/tirar` y `/economia/transferir` (evitar espadas/spam de tiradas).

---

## 7. Casos de uso AJAX (frontend ↔ API)

### 7.1 Widget de ficha en el postbit

**Contexto:** cada post de MyBB muestra debajo del avatar la ficha resumida del personaje activo del usuario.

```php
// inc/plugins/rolbridge.php — hook postbit
function rolbridge_postbit(&$post) {
    $post['rol_widget'] = '<div class="rol-ficha-widget" data-user="'.$post['uid'].'"></div>';
}
$plugins->add_hook('postbit', 'rolbridge_postbit');
```

```javascript
// jscripts/rol-widgets.js
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.rol-ficha-widget').forEach(async (el) => {
    const userId = el.dataset.user;
    try {
      const res = await fetch(`/api/v1/personajes/activo/${userId}`, {
        headers: { 'Authorization': `Bearer ${getRolToken()}` }
      });
      const { success, data } = await res.json();
      if (success) {
        el.innerHTML = `
          <strong>${data.nombre}</strong> — ${data.raza}
          <div class="rol-mini-stats">
            ${data.atributos.map(a => `<span>${a.clave}: ${a.valor}</span>`).join('')}
          </div>`;
      }
    } catch (e) {
      el.innerHTML = ''; // fallback silencioso, no romper el postbit
    }
  });
});
```

**Detalle clave:** el fallo de la API nunca debe romper la carga del foro — siempre `try/catch` con degradación elegante.

---

### 7.2 Crear y enviar una ficha a aprobación

```javascript
// formulario de ficha
async function enviarFicha(formData) {
  const res = await fetch('/api/v1/personajes', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${getRolToken()}`
    },
    body: JSON.stringify(formData)
  });
  const { success, data, error } = await res.json();
  if (!success) return mostrarError(error);

  // segundo paso: enviar a revisión
  await fetch(`/api/v1/personajes/${data.id}/enviar`, {
    method: 'POST',
    headers: { 'Authorization': `Bearer ${getRolToken()}` }
  });
  mostrarExito('Ficha enviada a revisión');
}
```

---

### 7.3 Tirada de dados desde un hilo de rol

**Contexto:** botón "Tirar 1d20+3" dentro del editor de post de MyBB, resultado se inserta en el mensaje y queda auditado server-side (nunca se calcula en el cliente, para evitar trampas).

```javascript
async function tirarDados(formula, hiloId) {
  const res = await fetch('/api/v1/dados/tirar', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${getRolToken()}`
    },
    body: JSON.stringify({ formula, hilo_mybb_id: hiloId })
  });
  const { success, data } = await res.json();
  if (success) {
    insertarEnEditor(`[dado: ${formula} = ${data.resultado} (${data.detalle.join(', ')})]`);
  }
}
```

---

### 7.4 Transferencia de economía entre personajes

```javascript
async function transferirMonedas(destinoPersonajeId, monto) {
  const res = await fetch('/api/v1/economia/transferir', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${getRolToken()}`
    },
    body: JSON.stringify({ destino_personaje_id: destinoPersonajeId, monto })
  });
  const { success, error } = await res.json();
  success ? mostrarExito('Transferencia realizada') : mostrarError(error);
}
```

---

### 7.5 Panel de moderación: aprobar/rechazar fichas (staff)

```javascript
async function aprobarFicha(personajeId) {
  const res = await fetch(`/api/v1/personajes/${personajeId}/aprobar`, {
    method: 'POST',
    headers: { 'Authorization': `Bearer ${getRolToken()}` }
  });
  const { success } = await res.json();
  if (success) document.querySelector(`[data-ficha="${personajeId}"]`).remove();
}
```

---

## 8. Seguridad — checklist

- [ ] CORS restringido solo al dominio del foro (`Access-Control-Allow-Origin` exacto, nunca `*` si hay cookies/JWT).
- [ ] Rate limiting en dados, economía y creación de fichas (Redis, ventana deslizante).
- [ ] Toda tirada de dados y transacción económica calculada **server-side**, nunca confiar en valores enviados desde el cliente.
- [ ] Transacciones SQL (`BEGIN/COMMIT/ROLLBACK`) en cualquier operación que mueva saldo o inventario.
- [ ] Validación de pertenencia (`personaje.mybb_user_id === token.mybb_user_id`) en cada endpoint mutador, salvo bypass explícito de staff.
- [ ] JWT con expiración corta (1h) + refresh silencioso, secreto rotable.
- [ ] Logs de auditoría inmutables (tablas `_transacciones`, `_tiradas`) — nunca se actualiza ni se borra, solo se inserta.
- [ ] Sanitización de HTML en campos de texto libre (historia, descripción) antes de renderizar en el foro (XSS).

---

## 9. Roadmap de implementación sugerido

| Fase | Entregable |
|---|---|
| 1 | Plugin puente MyBB (JWT en login) + esqueleto API con auth funcionando |
| 2 | CRUD de fichas + flujo de aprobación + widget en postbit |
| 3 | Inventario + items maestro |
| 4 | Economía (saldo + transferencias + historial) |
| 5 | Dados server-side + integración en el editor de post |
| 6 | Panel de staff (aprobaciones, ajustes de economía, auditoría) |
| 7 | Endurecimiento: rate limiting, logs, tests automatizados |

---

## 10. Notas finales

- Empieza por el **puente de autenticación**: es la pieza que, si falla, bloquea todo lo demás.
- Diseña las tablas de auditoría (`_transacciones`, `_tiradas`) **desde el día uno**, aunque el foro sea pequeño — añadirlas después sobre datos ya "sucios" es mucho más caro.
- Si el foro de rol crece y necesitas mapas interactivos o eventos en tiempo real (varios usuarios viendo el mismo tablero), ahí sí conviene añadir WebSockets (ej. con un servicio ligero en Node aparte) — pero no lo metas desde el principio si no lo necesitas todavía.

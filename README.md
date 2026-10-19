# Aplicación de Gastos Personales

Aplicación web fullstack para registrar y administrar gastos personales, ingresos y tarjetas de crédito/débito.

**Grupo N° 3 — Trabajo Práctico N° 1**

Integrante    - Aportes

Sebastián Almirón: Integración con servicios externos (Brevo, API de cotización), README
Santino Fernández: Frontend (JS, formularios, lógica de cliente), README
Sebastián Infante: Backend (autenticación, tokens de sesión, seguridad), README
Emanuel Sequeira: Infraestructura (Docker, Railway, base de datos), README
Tomás Zucchi: Backend (endpoints CRUD de gastos e ingresos, validaciones), README

## URL pública

- **Frontend:** https://gastos-personales-app-production-7228.up.railway.app/frontend/index.html
- **API base:** https://gastos-personales-production-f7bc.up.railway.app/backend/api
- **Proveedor:** Railway (PaaS — Platform as a Service)
  Railway gestiona la infraestructura completa: servidores, red, TLS, DNS y el servicio MySQL, sin que el equipo administre VMs ni sistemas operativos. Proporciona HTTPS automático y base de datos en la nube.

## ¿Qué hace la app?

- Registro e inicio de sesión con contraseña protegida (`password_hash`).
- Sesión con token Bearer almacenado en localStorage.
- CRUD completo de gastos, ingresos y tarjetas.
- Asociación de gastos a tarjetas de débito o crédito; cálculo de cuotas en el backend.
- Métricas mensuales: gastos totales, ingresos totales, economía y balance por día de cierre.
- Cotización USD → ARS en tiempo real (obtenida desde `open.er-api.com`).
- Email de bienvenida al usuario registrado mediante Brevo.
- El día de cierre se configura dentro de la app (pantalla Inicio).

## Servicios externos utilizados

### 1. Brevo — Email transaccional (backend PHP)

Al registrarse un usuario nuevo, el backend realiza una llamada HTTP a la API de Brevo y envía un email de bienvenida al correo con el que se registró.

- **Quién llama:** `backend/utils/email.php` → `backend/api/register.php`
- **Endpoint usado:** `https://api.brevo.com/v3/smtp/email` (POST)
- **Datos personales enviados:** nombre y email del usuario registrado.
- **Degradación:** si `BREVO_API_KEY` está vacía o la llamada falla (timeout, error HTTP), el registro se completa igual; el email es complementario y no bloquea la función principal.

### 2. Open Exchange Rate API — Cotización USD → ARS (backend PHP)

Cada vez que se cargan las métricas, el backend consulta la cotización actualizada del dólar.

- **Quién llama:** `backend/api/metricas.php`
- **Endpoint usado:** `https://open.er-api.com/v6/latest/USD` (GET, sin clave)
- **Datos enviados al servicio:** ninguno (solo un GET público).
- **Datos recibidos:** tasa de cambio USD → ARS y fecha de actualización.
- **Degradación:** si la llamada falla o supera los 5 segundos, el campo `cotizacion_usd_ars.valor` llega como `null` y el frontend muestra "No disponible en este momento." El resto de las métricas se muestran con normalidad.

## Estructura de carpetas

```text
gastos-personales/
├── README.md
├── Dockerfile
├── railway.json
├── backend/
│   ├── .env-example
│   ├── config/
│   │   └── database.php
│   ├── utils/
│   │   ├── response.php
│   │   ├── auth.php
│   │   ├── validaciones.php
│   │   └── email.php
│   └── api/
│       ├── register.php
│       ├── login.php
│       ├── gastos.php
│       ├── ingresos.php
│       ├── tarjetas.php
│       ├── metricas.php
│       └── configuracion.php
├── docker/
│   ├── apache.conf
│   └── schema.sql
└── frontend/
    ├── index.html
    ├── dashboard.html
    ├── gastos.html
    ├── ingresos.html
    ├── tarjetas.html
    ├── css/
    │   └── style.css
    └── js/
        ├── api.js
        ├── auth.js
        ├── gastos.js
        ├── ingresos.js
        ├── tarjetas.js
        └── metricas.js
```

## Instalación local con XAMPP

1. **Requisitos:** XAMPP con Apache y MySQL activos.

2. **Copiá la carpeta del proyecto**
   Colocá la carpeta completa en `C:\xampp\htdocs\` (Windows) o `/opt/lampp/htdocs/` (Linux) con el nombre `gastos-personales`.

3. **Creá la base de datos y las tablas**
   - Abrí phpMyAdmin en `http://localhost/phpmyadmin`.
   - Creá una base de datos nueva (ej: `gastos_personales`).
   - Importá el archivo `docker/schema.sql` desde la solapa "Importar".

4. **Configurá las variables de entorno**
   - Copiá `backend/.env-example` como `backend/.env`.
   - Completá tus datos de MySQL y, si querés usar Brevo, cargá `BREVO_API_KEY`.

5. **Abrí la app**
   - Entrá a `http://localhost/gastos-personales/frontend/index.html`.

> **Nota:** el archivo de esquema está en `docker/schema.sql`, **no** en `backend/database.sql`.

## Docker y deploy en Railway

La imagen (PHP 8.3 + Apache) sirve `frontend/` y `backend/api/`. Al arrancar, `docker/init-db.php` espera a MySQL y crea las tablas de `docker/schema.sql` si no existen.

**Local con Docker:** `docker compose up --build` y abrí `http://localhost:8080`.

## Variables de entorno

Archivo de ejemplo: `backend/.env-example`

| Variable | Descripción | Requerida |
|---|---|---|
| `DB_HOST` | Host de MySQL (ej: `127.0.0.1`) | Sí |
| `DB_PORT` | Puerto de MySQL (default: `3306`) | Sí |
| `DB_NAME` | Nombre de la base de datos | Sí |
| `DB_USER` | Usuario de MySQL | Sí |
| `DB_PASS` | Contraseña de MySQL | Sí |
| `CORS_ALLOW_ORIGIN` | Origen permitido (en producción: la URL del frontend) | Recomendada |
| `TOKEN_EXPIRATION_HOURS` | Horas de validez del token (default: `24`) | No |
| `BREVO_API_KEY` | API key de Brevo. Si está vacía, no se envían emails | No |
| `BREVO_FROM_EMAIL` | Email del remitente para Brevo | No |
| `BREVO_FROM_NAME` | Nombre del remitente para Brevo | No |

> En producción se recomienda establecer `CORS_ALLOW_ORIGIN` con la URL exacta del frontend en lugar de `*`.

## Endpoints de la API

Base URL: `https://gastos-personales-production-f7bc.up.railway.app/backend/api`

| Método | Ruta | Auth | Body / Params | Respuestas |
|---|---|---|---|---|
| POST | `/register.php` | No | `nombre`, `email`, `password` | 201, 409, 422, 405, 500 |
| POST | `/login.php` | No | `email`, `password` | 200, 401, 422, 405, 500 |
| GET | `/gastos.php` | Bearer | — | 200, 401, 405, 500 |
| GET | `/gastos.php?id={id}` | Bearer | `id` (entero) | 200, 404, 401, 422, 405, 500 |
| POST | `/gastos.php` | Bearer | `descripcion`, `monto`, `fecha`, `categoria`, `metodo_pago`, `tarjeta_id`\*, `cantidad_cuotas`\* | 201, 422, 401, 405, 500 |
| PUT | `/gastos.php?id={id}` | Bearer | Igual a POST | 200, 404, 422, 401, 405, 500 |
| DELETE | `/gastos.php?id={id}` | Bearer | `id` (entero) | 200, 404, 422, 401, 405, 500 |
| GET | `/ingresos.php` | Bearer | — | 200, 401, 405, 500 |
| GET | `/ingresos.php?id={id}` | Bearer | `id` (entero) | 200, 404, 401, 422, 405, 500 |
| POST | `/ingresos.php` | Bearer | `descripcion`, `monto`, `fecha`, `es_fijo` | 201, 422, 401, 405, 500 |
| PUT | `/ingresos.php?id={id}` | Bearer | Igual a POST | 200, 404, 422, 401, 405, 500 |
| DELETE | `/ingresos.php?id={id}` | Bearer | `id` (entero) | 200, 404, 422, 401, 405, 500 |
| GET | `/tarjetas.php` | Bearer | — | 200, 401, 405, 500 |
| GET | `/tarjetas.php?id={id}` | Bearer | `id` (entero) | 200, 404, 401, 422, 405, 500 |
| GET | `/tarjetas.php?con_gastos=1` | Bearer | — | 200, 401, 405, 500 |
| POST | `/tarjetas.php` | Bearer | `nombre`, `tipo`, `banco`, `ultimos_4` | 201, 422, 401, 405, 500 |
| PUT | `/tarjetas.php?id={id}` | Bearer | Igual a POST | 200, 404, 422, 401, 405, 500 |
| DELETE | `/tarjetas.php?id={id}` | Bearer | `id` (entero) | 200, 404, 422, 401, 405, 500 |
| GET | `/metricas.php` | Bearer | `mes=YYYY-MM` (opc.), `dia_cierre=1..28` (opc.) | 200, 422, 401, 405, 500 |
| GET | `/configuracion.php` | Bearer | — | 200, 401, 405, 500 |
| PUT | `/configuracion.php` | Bearer | `dia_cierre_balance` (1–28) | 200, 422, 401, 405, 500 |

\* `tarjeta_id` es requerido si `metodo_pago` es `debito` o `credito`. `cantidad_cuotas` es requerido solo para `credito`.

### Códigos HTTP utilizados

| Código | Significado |
|---|---|
| 200 | OK |
| 201 | Creado |
| 204 | Preflight OPTIONS sin contenido |
| 400 | Solicitud inválida |
| 401 | No autorizado (token inválido o ausente) |
| 404 | Recurso no encontrado |
| 405 | Método no permitido |
| 409 | Conflicto (ej: email ya registrado) |
| 422 | Error de validación |
| 500 | Error interno del servidor |

## Seguridad

- Contraseñas con `password_hash()` / `password_verify()`.
- Consultas SQL con sentencias preparadas (PDO, sin interpolación de strings).
- Tokens de sesión aleatorios (`random_bytes`) con expiración configurable.
- `display_errors` desactivado en producción; detalles de error solo en el log del servidor.
- CORS configurable por variable de entorno (`CORS_ALLOW_ORIGIN`).
- Datos de usuario en el DOM construidos con `textContent` (sin `innerHTML` con datos externos).

## Limitaciones conocidas

- La cotización USD → ARS proviene de `open.er-api.com`; si el servicio está caído, el campo aparece como "No disponible". No refleja el dólar blue ni tipos de cambio oficiales distintos al de referencia.
- El cálculo de cuotas no incluye interés (divide el monto en partes iguales). La última cuota puede diferir en centavos por redondeo.
- No existe recuperación de contraseña; si el usuario la olvida, debe crear una cuenta nueva.
- La sesión no se invalida en el servidor al cerrar sesión: solo se borra el token del localStorage. Tokens robados antes del cierre siguen siendo válidos hasta su vencimiento.
- La app no soporta múltiples monedas: todos los montos se asumen en ARS.

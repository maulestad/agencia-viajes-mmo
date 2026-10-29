# Sistema de Gestión para Agencia de Viajes (Bolivia)

Sistema web completo para gestionar una agencia de viajes: clientes, cotizaciones, reservas, cobros en Bs y USD, proveedores, gastos, facturas, reportes y respaldos.

**Un solo código** funciona en:

| Entorno | Base de datos | Qué cambiar |
|---|---|---|
| **XAMPP** (local u hosting con cPanel) | MySQL / MariaDB | Nada |
| **Docker** (local) | MySQL 8 | Nada (`docker compose up`) |
| **Render** (nube) | PostgreSQL | Nada: lo configura `render.yaml` |

> ¿Por qué PostgreSQL en Render? Render no ofrece MySQL administrado. Su PostgreSQL sí, y el sistema se adapta solo según la variable `DATABASE_URL`.

---

## Módulos

- **Panel**: ventas, cobros, gastos y cuentas por cobrar del mes; gráfico de 12 meses; próximas salidas; tareas; pasaportes por vencer; cumpleaños de clientes.
- **Reservas y cotizaciones**: varios servicios por reserva (vuelo, hotel, tour, traslado, seguro…), con costo neto y utilidad; pasajeros; descuento; Bs o USD con tipo de cambio; estados (cotización → confirmada → pagada → completada / cancelada); duplicar cotizaciones; aviso por WhatsApp; documentos adjuntos.
- **Documentos imprimibles / PDF**: cotización o proforma, voucher de servicios, estado de cuenta y recibo de cobro, con su logo.
- **Cobros**: efectivo, QR, transferencia, depósito, tarjeta… pagos parciales, conversión de moneda, anulación con motivo y estado automático de la reserva.
- **Clientes**: CI con expedición, NIT, pasaporte con vencimiento, historial de compras y saldo, documentos (pasaporte, CI, etc.).
- **Catálogo**: destinos (12 destinos bolivianos de ejemplo), paquetes con incluye/no incluye/itinerario, y proveedores.
- **Finanzas**: gastos/egresos (incluye pagos a proveedores) y registro de facturas emitidas (NIT, razón social, CUF).
- **Reportes** (exportables a Excel/CSV): ventas, cuentas por cobrar, flujo de caja por método, rentabilidad, destinos, agentes, cuentas por pagar a proveedores, gastos y mejores clientes.
- **Seguridad**: usuarios con roles (Administrador, Agente de ventas, Contabilidad/Caja), contraseñas cifradas, protección CSRF y bitácora de auditoría.
- **Respaldos**: manual, automático diario, descarga directa y restauración. **Son portables**: un respaldo de XAMPP se restaura en Render y viceversa.

---

## 1. Instalación en XAMPP (recomendado para uso local)

1. Copie la carpeta `agencia-viajes` a `C:\xampp\htdocs\`.
2. En el panel de XAMPP inicie **Apache** y **MySQL**.
3. Abra **http://localhost/agencia-viajes/**

Listo. La base de datos `agencia_viajes` y las tablas **se crean solas** la primera vez.

- Usuario: `admin`
- Contraseña: `admin123` → **cámbiela de inmediato** (menú de usuario → Cambiar contraseña).

### Ajustes recomendados en XAMPP
Para adjuntar archivos de más de 1 MB, aumente el límite de MySQL:
`C:\xampp\mysql\bin\my.ini` → sección `[mysqld]` → `max_allowed_packet=64M` y reinicie MySQL.

Si su MySQL tiene contraseña o quiere usar otro nombre de base, copie `.env.example` como `.env` y edite `DB_USER`, `DB_PASS` y `DB_NAME`.

### Acceso desde otras PCs de la oficina
Use la IP de la PC servidor: `http://192.168.x.x/agencia-viajes/` (permita Apache en el Firewall de Windows).

---

## 2. Docker (local)

```bash
docker compose up -d --build
```
- Sistema: http://localhost:8080
- phpMyAdmin: http://localhost:8081 (usuario `agencia` / `agencia123`)

Para probarlo **igual que en Render** (PostgreSQL):
```bash
docker compose --profile pg up -d --build
```
→ http://localhost:8090

---

## 3. Despliegue en Render (manual, desde su repositorio)

El despliegue **no es automático**: `render.yaml` tiene `autoDeploy: false`.

### Pasos
1. Suba el proyecto a un repositorio de GitHub/GitLab:
   ```bash
   cd agencia-viajes
   git init
   git add .
   git commit -m "Sistema agencia de viajes"
   git branch -M main
   git remote add origin https://github.com/SU_USUARIO/agencia-viajes.git
   git push -u origin main
   ```
2. En Render: **New → Blueprint**, elija el repositorio. Render leerá `render.yaml` y creará:
   - un **Web Service** (Docker) `agencia-viajes`
   - una base **PostgreSQL** `agencia-db`, conectada sola mediante `DATABASE_URL`
3. Render le pedirá el valor de **`ADMIN_PASSWORD`**: escriba la contraseña inicial del usuario `admin`.
4. Pulse **Apply**. Cuando termine, abra la URL `https://agencia-viajes-xxxx.onrender.com`.
5. Para actualizaciones futuras: `git push` y luego en Render → **Manual Deploy → Deploy latest commit**.

### Alternativa sin Blueprint (manual)
1. **New → PostgreSQL** → cree la base y copie su *Internal Database URL*.
2. **New → Web Service** → elija el repo → Runtime **Docker**.
3. En *Environment* agregue:
   - `DATABASE_URL` = (Internal Database URL)
   - `ADMIN_PASSWORD` = su contraseña
   - `APP_ENV` = `production`
4. *Health Check Path*: `/health.php`. En *Settings* desactive **Auto-Deploy** si quiere desplegar solo a mano.

### Importante en Render
- **El disco del servidor se borra en cada despliegue.** Por eso los documentos adjuntos y el logo se guardan **dentro de la base de datos**: no se pierden. Los respaldos guardados en el servidor sí se pierden, así que use **Respaldos → “Descargar respaldo ahora”** y guarde el archivo en su PC o Drive.
- En el plan **free**, el servicio se “duerme” tras 15 minutos sin uso (la primera carga tarda unos 30-50 s) y la base PostgreSQL gratuita **vence a los 30 días**. Para trabajar en serio use al menos el plan *Starter* del web service y un plan pago de PostgreSQL (incluye respaldos automáticos de Render).
- `SEED_DEMO=1` carga destinos y paquetes de ejemplo. Póngalo en `0` antes del primer despliegue si no los quiere.

### Migrar de XAMPP a Render (o al revés)
1. En el sistema de origen: **Respaldos → Descargar respaldo ahora** (archivo `.json.gz`).
2. En el sistema de destino: **Respaldos → Restaurar desde un archivo** → escriba `RESTAURAR`.
Se migran todos los datos, incluidos usuarios y documentos adjuntos.

---

## 4. Respaldos

| Método | Cómo |
|---|---|
| Desde el sistema | *Sistema → Respaldos* → Crear / Descargar / Restaurar |
| Automático diario | Se crea al entrar el administrador (configurable: activar y cuántos conservar) |
| Consola | `php cli/backup.php` · restaurar: `php cli/restore.php archivo.json.gz --si` |
| XAMPP programado | Ejecute como administrador `scripts\programar_respaldo_diario.bat` (todos los días a las 20:00) |
| SQL nativo MySQL | `scripts\respaldo_mysqldump.bat` |
| SQL nativo Render | `pg_dump "EXTERNAL_DATABASE_URL" > respaldo.sql` |

Los respaldos quedan en `storage/backups/`. Antes de cada restauración, el sistema guarda automáticamente una copia del estado actual.

---

## 5. Configuración (variables de entorno o `.env`)

| Variable | Por defecto | Descripción |
|---|---|---|
| `DB_DRIVER` | `mysql` | `mysql` o `pgsql` |
| `DB_HOST` / `DB_PORT` / `DB_NAME` / `DB_USER` / `DB_PASS` | `127.0.0.1` / 3306 / `agencia_viajes` / `root` / *(vacío)* | Conexión |
| `DATABASE_URL` | — | Si existe, tiene prioridad (Render) |
| `DB_SSLMODE` | — | `require` si se conecta a Render desde fuera |
| `ADMIN_PASSWORD` | `admin123` | Contraseña inicial del admin (solo en la instalación) |
| `SEED_DEMO` | `1` | Datos de ejemplo en la instalación |
| `MAX_UPLOAD_MB` | `2` | Tamaño máximo de adjuntos |
| `APP_DEBUG` | `1` (Docker/Render: `0`) | Mostrar errores detallados |

Datos de la empresa, logo, tipo de cambio, condiciones de venta y respaldos automáticos se editan en **Sistema → Configuración**.

---

## 6. Roles

| Módulo | Admin | Agente | Contabilidad |
|---|:-:|:-:|:-:|
| Panel (el agente ve **solo sus propias cifras**, sin gastos) | ✔ | ✔ | ✔ |
| Reservas, clientes, cobros, tareas | ✔ | ✔ | ✔ |
| Paquetes, destinos | ✔ | ✔ | |
| Proveedores | ✔ | ✔ | ✔ |
| Gastos, facturas, reportes | ✔ | | ✔ |
| Anular cobros | ✔ | | ✔ |
| Usuarios, configuración, respaldos, bitácora | ✔ | | |

---

## Manual de usuario

`docs/Manual_Sistema_Agencia_Viajes.pdf`: recorrido por todas las pantallas con capturas, perfiles de usuario, instalación y una lista de verificación para revisar el sistema.

## 7. Estructura

```
agencia-viajes/
├── index.php              Controlador frontal (index.php?r=modulo&a=accion)
├── health.php             Health check para Render
├── app/
│   ├── config.php         Lee variables de entorno / .env
│   ├── resources.php      Definición de módulos CRUD (campos, filtros, búsqueda)
│   ├── Core/              DB (MySQL+PostgreSQL), Auth, Backup, Installer, CRUD genérico
│   ├── Controllers/       Lógica de cada módulo
│   └── Views/             Plantillas (Bootstrap 5)
├── database/              Esquemas schema_mysql.sql y schema_pgsql.sql
├── assets/                CSS, JS e imagen
├── storage/               backups/, logs/, sessions/ (no se suben al repo)
├── cli/                   backup.php, restore.php
├── scripts/               .bat para XAMPP (respaldos, tarea programada)
├── Dockerfile, docker/    Imagen PHP 8.2 + Apache (MySQL y PostgreSQL)
├── docker-compose.yml     Entorno local
└── render.yaml            Blueprint de Render
```

**Agregar campos a un módulo:** agregue la columna en ambos esquemas (`database/schema_*.sql`) y en `app/resources.php`. Para bases ya instaladas, ponga el `ALTER TABLE` en `Installer::migrate()`.

---

## Notas

- **Facturación electrónica (SIN/SIAT):** el sistema *registra* las facturas emitidas (número, NIT, CUF), pero **no se conecta** al SIAT. Para emitir facturas electrónicas válidas se necesita un sistema certificado o una integración con los servicios del SIN.
- El tipo de cambio por defecto es 6,96 Bs/USD. Actualícelo en Configuración según el tipo de cambio con el que trabaje la agencia. Cada reserva y cada cobro guardan el tipo de cambio que se usó.
- La interfaz usa Bootstrap, Chart.js y Tom Select desde CDN: se necesita internet para que se vea con estilos.

# Estado del proyecto

**Actualización documental comprobada:** 2026-10-26. La integración no implementa funcionalidades ni revalida la base de datos. Los datos históricos se distinguen a continuación.

Referencias: [AGENTS.md](../AGENTS.md), [PLANS.md](../PLANS.md), [dominio](../.ai/rules/kpgroup-domain.md) y [decisiones](decisiones-tecnicas.md).

## Entorno previamente reportado (verificar en servidor)
- Ruta: `/var/www/html/mantenimiento-vias`; BD `kp_mantenimiento_vias`.
- Laravel 13.38.0, PHP 8.3.30, Filament 5.10.3, MariaDB 10.11.14, Spatie Permission instalado; autenticación del panel `/admin` reportada.
- `RolePermissionSeeder` reportado ejecutado; roles: Administrador, Jefe de supervisores y Supervisor. No asumir que todas las policies o migraciones estén listas.

## Estado del Gantt (referencia del archivo, no validación Git)
- MV-001 y MV-002: Completada; MV-003: En proceso; MV-004 y MV-005: Completada.
- MV-006, MV-007, MV-008: Por iniciar según corte de hoja `GANTT ACTIVIDADES`.
- Contexto documental integrado con autorización. Boost se instaló posteriormente con autorización separada (ver sección final). MV-006…008 sigue sin implementarse por estas tareas; no se cambian estados funcionales.

## Comprobaciones obligatorias Codex
- `git status --short`, branch/HEAD, `composer show`, Artisan, esquema migrations y archivos existentes.
- Determinar si ya existe `AGENTS.md`, MCP, Boost, documentación o desarrollos Client/Plant. No sobrescribir ni reinstalar duplicados.
- Diferenciar `reportado`, `comprobado`, `pendiente`, `bloqueado`. No marcar terminado sin evidencia.

## Bitácora de cambios (completar al operar en repositorio)
| Fecha | Tarea | Archivos/commit | Pruebas y resultados | Estado | Riesgos/bloqueos |
|---|---|---|---|---|---|
| 2026-10-26 | Paquete de contexto generado fuera del servidor | Pendiente de integrar | Sin ejecución en repositorio | Propuesto | Codex debe auditar conflictos |

## Integración documental comprobada — 2026-10-26

- AGENTS.md sustituye el bootstrap por instrucciones unificadas; las reglas de
  dominio están en .ai/rules/kpgroup-domain.md. AGENTS_KPGroup_Fusionado.md remite
  a estos archivos para evitar instrucciones duplicadas.
- PLANS.md, los documentos de docs/ y las guías de contexto/importación enlazan
  las instrucciones vigentes; ambos short_name figuran opcionales.
- El responsable confirmó unicidad de planta por cliente, desactivación sin
  cascada y bloqueo de nuevas operaciones, conservando histórico. La clasificación
  detallada de operaciones y otras preguntas permanece en decisiones-tecnicas.md.
- Rama comprobada: main; HEAD: 1add16f. Los documentos del paquete ya aparecían
  sin seguimiento antes de esta integración; no se crea commit ni se añaden al índice.
- Boost no figuraba instalado en la revisión previa; esta integración no lo instala
  ni configura MCP. No se ejecutan migraciones ni pruebas funcionales.
- Validación documental ejecutada: 13 Markdown revisados, 75 enlaces locales
  válidos y ambos short_name nullable consistentes. Los 128 identificadores
  MV-001…MV-128 siguen consecutivos y sin cambios en el índice Gantt.
- Comparación SHA-256: 44 archivos de código, dependencias, fuentes originales
  e índice Gantt sin cambios frente al inicio. git diff --check sin errores.
- Fuentes PDF/XLSX: presencia comprobada; contenido no releído en esta integración.

| Fecha | Tarea | Archivos/commit | Validación | Estado | Pendientes |
|---|---|---|---|---|---|
| 2026-10-26 | Integración documental autorizada | AGENTS.md, .ai/rules/kpgroup-domain.md, PLANS.md, guías de raíz y seis Markdown de docs/; sin commit | Revisión documental; sin ejecución funcional | Integrado documentalmente | Boost, implementación funcional y preguntas abiertas requieren trabajo separado |

## Laravel Boost y MCP — comprobación del 2026-10-26

Instalación autorizada por el responsable en esta conversación, posterior a la
integración documental. No se ejecutaron migraciones, seeders, commits ni push.
No se implementaron MV-006…MV-008 ni se modificó lógica funcional.

### Versiones y compatibilidad verificadas

- PHP 8.3.30; Composer 2.10.3; Codex CLI 0.162.0-alpha.17.2.
- Laravel Boost v2.10.3, dependencia de desarrollo `^2.10`.
- Boost requiere PHP ^8.2 y admite Illuminate ^13.0; el entorno cumple.
  `composer check-platform-reqs` pasó después de instalar.
- Se añadieron cinco paquetes: laravel/boost v2.10.3, laravel/mcp v1.1.2,
  laravel/roster v1.0.0, composer/semver 3.5.2 y symfony/yaml v7.4.21.
- Comparación de composer.lock: cero actualizaciones y cero eliminaciones de
  paquetes existentes. Laravel 13.38.0, Filament 5.10.3 y Spatie Permission 8.3.2
  se conservaron. Composer no reportó avisos de vulnerabilidad en esta ejecución.
- Los scripts existentes de Composer ejecutaron package:discover,
  filament:upgrade y vendor:publish de laravel-assets. Se republicaron assets
  de Filament y limpiaron cachés de configuración, rutas y vistas; no se cambió
  la versión de Filament. No se ejecutó el script setup ni migraciones.

### Configuración creada y conservación de instrucciones

- [boost.json](../boost.json): Codex, directrices, MCP y seis habilidades:
  infer-conventions, laravel-best-practices, testing-best-practices,
  tailwindcss-development, filament-development y laravel-permission-development.
  Archivos en `.agents/skills/` (35 Markdown, seis SKILL.md y sus referencias).
- `.codex/config.toml`: servidor laravel-boost STDIO mediante php artisan boost:mcp,
  directorio `/var/www/html/mantenimiento-vias`, timeout de inicio 30 s y de herramientas 120 s.
  La configuración es local y ya estaba excluida por `.gitignore`; no se cambió
  esa exclusión ni se añadió un servidor global duplicado.
- CACHE_STORE=array y BOOST_PHP_EXECUTABLE_PATH="env CACHE_STORE=array php" en el
  entorno MCP mantienen caché en memoria tanto en servidor como en herramientas
  aisladas. Este ajuste es necesario porque DatabaseSchema usa Cache::remember
  y ToolExecutor limpia variables cargadas desde .env en sus subprocesos.
  No se cambió .env ni la configuración de caché de la aplicación web.
- [AGENTS.md](../AGENTS.md): un bloque gestionado Boost, texto original KP Group
  conservado íntegro como prefijo y aclaración de integración fuera del bloque.
- [.ai/rules/index.md](../.ai/rules/index.md): índice requerido por las directrices
  nuevas; enlaza kpgroup-domain.md, sin modificar ese archivo de dominio.
- Respaldo local temporal: `/tmp/kpgroup-pre-boost-nyjfth7v/` contiene AGENTS.md,
  composer.json, composer.lock, reglas de dominio y huellas previas. /tmp no es
  almacenamiento permanente. No se creó un commit de respaldo.

### Validación real

`codex mcp list --json` reconoce laravel-boost habilitado y la configuración local.
Un cliente temporal de verificación leyó esa configuración e inició el servidor
con sus argumentos y entorno: initialize, notifications/initialized y tools/list
funcionaron. Las siguientes llamadas tools/call devolvieron isError=false:

| Herramienta | Resultado comprobado |
|---|---|
| application-info | PHP, Laravel, motor MariaDB y versiones de paquetes |
| database-connections | Conexión predeterminada mariadb y nombres de conexiones; sin credenciales |
| database-schema | Esquema resumido de 14 tablas; no existen clients ni plants |
| database-query | SELECT DATABASE(), VERSION(): kp_mantenimiento_vias, MariaDB 10.11.14 |
| search-docs | Resultados reales filtrados por laravel/framework@13.x y filament/filament@5.x |

La consulta database-query se ejecuta en transacción READ ONLY con rollback según
la implementación instalada. El esquema solo consulta metadatos y usa caché en
memoria. No se invocaron herramientas de escritura.

El servidor anunció diez herramientas: las cinco anteriores y browser-logs,
get-absolute-url, last-error, read-log-entries y record-rule. Estas últimas cinco
no se ejecutaron; record-rule escribe archivos y no se usó. Tinker no está
expuesto con la configuración predeterminada de esta versión.

Modelos: `php artisan model:show 'App\Models\User' --json --no-interaction`
comprobó atributos, casts y relaciones de User mediante metadatos, sin consultar
registros personales. Esta fue una comprobación por Artisan, no por MCP:
application-info de Boost 2.10.3 no devuelve la lista de modelos Eloquent.

### Integridad, límites y próximos pasos

- El texto previo de AGENTS.md se comparó con el respaldo y permanece íntegro fuera
  del bloque gestionado. Las reglas de dominio, fuentes originales y Gantt no cambiaron.
- Comparación SHA-256 frente al inicio: sin cambios en app/, config/, database/,
  routes/, resources/ y tests/, ni en documentos previos distintos de AGENTS.md
  y este registro. composer.lock solo añade los cinco paquetes indicados.
- `git diff --check`: sin errores. No se ejecutó la suite funcional; la verificación
  fue de instalación, configuración, protocolo MCP y consultas de lectura.
- El sandbox bloqueó inicialmente las escrituras en .agents/.codex y el acceso
  de red; la instalación se completó con permisos ampliados autorizados.
- La sesión de chat activa no incorporó dinámicamente las herramientas Boost.
  La comunicación del servidor se comprobó con un cliente de protocolo independiente,
  no mediante una nueva sesión del agente. Reiniciar la extensión o abrir una sesión
  nueva y comprobar `/mcp` para confirmar su disponibilidad en la interfaz.
- Antes de MV-006…MV-008: asignar explícitamente ese bloque, leer decisiones y modelo,
  resolver colación/unicidad y clasificación de operaciones bajo cliente inactivo,
  confirmar BD de pruebas aislada y planear pruebas de ambos short_name opcionales,
  relaciones, unicidad por cliente e histórico. No incluir Resources MV-009/MV-010.

Fuentes oficiales consultadas: [Laravel Boost](https://laravel.com/framework/docs/boost)
y [MCP para Codex](https://learn.chatgpt.com/docs/extend/mcp?surface=cli).

## MV-006…MV-008 — resultados comprobados el 2026-10-26

Esta sección posterior actualiza el estado histórico anterior. Alcance autorizado
por la solicitud adjunta «KP Group — Implementación MV-006 a MV-008».
Inicio: rama main, HEAD 24d53e5 (chore: configurar Laravel Boost 2.10.3 para Codex),
árbol limpio. No se crearon commits ni se hizo push durante este bloque.

| Actividad | Estado del bloque | Evidencia y límite |
|---|---|---|
| MV-006 | Completada: diseño del alcance solicitado documentado | Sección 7 del PDF contrastada; modelo-datos.md y decisiones-tecnicas.md recogen estructura, aclaraciones y límites. Colación/concurrencia requieren validación posterior. |
| MV-007 | Parcial | Dos migraciones creadas, ejecutadas y comprobadas en SQLite :memory:, incluyendo down/up. NO ejecutadas ni validadas en MariaDB de desarrollo o testing. |
| MV-008 | Completada en el alcance Eloquent probado en SQLite | Modelos, relaciones, factories y bloqueo de altas probados. No incluye Resources, Policies, autorización HTTP ni otros módulos. |

### Archivos de implementación

- app/Models/Client.php y app/Models/Plant.php.
- database/factories/ClientFactory.php y database/factories/PlantFactory.php.
- database/migrations/2026_10_27_021628_create_clients_table.php.
- database/migrations/2026_10_27_021629_create_plants_table.php.
- tests/DatabaseTestCase.php.
- tests/Feature/Models/ClientTest.php y tests/Feature/Models/PlantTest.php.
- tests/Feature/Database/ClientPlantMigrationsTest.php.
- Documentación actualizada: modelo-datos.md, decisiones-tecnicas.md y este archivo.

Los nombres de migración fueron generados por Artisan con el reloj UTC del
servidor; no modifican fechas ni identificadores del Gantt.

### Verificaciones ejecutadas

- Versiones instaladas: Laravel 13.38.0, Filament 5.10.3, Spatie Permission 8.3.2.
- route:list --path=admin: dashboard, login y logout registrados. User sigue sin
  FilamentUser/canAccessPanel; no se tocó MV-003 ni se probó login HTTP.
- Boost no aparece como herramienta nativa en esta sesión. Se invocó su herramienta
  SearchDocs mediante boost:execute-tool: documentación Laravel 13 sobre eventos
  Eloquent y migraciones. DatabaseSchema, con CACHE_STORE=array, confirmó esquema
  previo MariaDB de 14 tablas, sin clients/plants; solo lectura de metadatos.
- Suite del bloque: **15 pruebas, 45 aserciones, todas pasaron**.
- Suite completa: **17 pruebas, 47 aserciones, todas pasaron** (incluye las dos
  pruebas de ejemplo previas). No confundirlas con pruebas de autenticación.
- Cobertura: relación 1:N y belongsTo, exclusión de plantas de otro cliente,
  ambos short_name nulos/asignables, observations nullable, defaults y casts,
  unicidad dentro del cliente, duplicidad entre clientes, FK inexistente/omitida,
  eliminación restringida, desactivación sin cascada, datos previos consultables,
  altas bloqueadas por create/save/relación y rechazo con estado del cliente
  desactualizado en memoria. Reversión y reaplicación de las nuevas migraciones.
- Base efectiva: testing / sqlite / :memory:, PDO sqlite, archivo vacío en
  PRAGMA database_list y foreign_keys=1. DatabaseTestCase valida configuración al
  arrancar y conexión efectiva antes de que LazilyRefreshDatabase migre.
- Prueba negativa deliberada: ejecutar un caso con DB_CONNECTION=mariadb y
  DB_DATABASE=kp_mantenimiento_vias produjo RuntimeException de la barrera antes
  de consultar/migrar. Salida 2 esperada, cero aserciones; no es fallo funcional.
- Pint --dirty --format agent aplicado; git diff --check sin errores.
- No se ejecutaron migraciones ni seeders en MariaDB. Sin cambios en User,
  proveedores del panel, permisos, migraciones previas, fuentes originales o Gantt.

### Comandos de validación y aplicación posterior

Pruebas aisladas (ejecutado; no utiliza MariaDB):

```bash
APP_ENV=testing APP_CONFIG_CACHE=/tmp/kpgroup-mv006-no-config-cache.php DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= DB_FOREIGN_KEYS=true CACHE_STORE=array SESSION_DRIVER=array vendor/bin/phpunit --do-not-cache-result
```

Antes de reutilizar el comando, comprobar que la ruta APP_CONFIG_CACHE no contiene
un caché de configuración. La barrera de DatabaseTestCase rechaza destinos no aislados.

**Solo tras autorización para la base de destino y comprobar su conexión efectiva**,
revisar primero el estado, después el SQL y finalmente aplicar exclusivamente
estas dos migraciones (comandos propuestos, NO ejecutados en MariaDB):

```bash
php artisan migrate:status --database=mariadb
php artisan migrate --database=mariadb --path=database/migrations/2026_10_27_021628_create_clients_table.php --path=database/migrations/2026_10_27_021629_create_plants_table.php --pretend --no-interaction
php artisan migrate --database=mariadb --path=database/migrations/2026_10_27_021628_create_clients_table.php --path=database/migrations/2026_10_27_021629_create_plants_table.php --no-interaction
php artisan migrate:status --database=mariadb
```

Validar antes en una MariaDB exclusiva de testing, con conexión separada y sin
reutilizar kp_mantenimiento_vias. La suite actual está intencionalmente limitada a
SQLite; una prueba MariaDB necesitará su configuración y protección específicas.
Validaciones pendientes allí: tipos unsigned, longitudes varchar en modo estricto,
colación/caso/acentos/espacios, FK/índice y concurrencia real. SQLite no acredita
esas propiedades de MariaDB. down() elimina las tablas y sus datos: no usarlo
como reversión inocua después de capturar información real.

### Límites y pendientes

- El callback creating valida altas Eloquent; SQL directo, insert/upsert y
  saveQuietly/withoutEvents lo omiten. No es una restricción de base de datos.
- La comprobación de activo y el INSERT no son atómicos frente a una desactivación
  concurrente. Coordinar transacciones/bloqueos en futuros escritores si se requiere
  esa garantía; no se declara probada concurrencia con SQLite.
- Histórico comprobado en este bloque: clientes, plantas, atributos y relaciones
  existentes conservados al desactivar. No existen aún históricos operativos de
  servicios/reportes ni se implementó autorización para su lectura.
- Pendientes de definición: colación y tratamiento de correcciones/reasignación,
  reactivación y otras operaciones bajo cliente inactivo. El alta de nuevas plantas
  ya está definida y cubierta; no bloquear por ello el resto del diseño aprobado.
- MV-009/MV-010, Resources, vías, cambios y tramos no se implementaron.
- Propuesta de commit (no creado): `feat: implementar clientes y plantas MV-006 a MV-008`.

Bloque detenido para revisión antes de continuar con el siguiente alcance.

## MV-003 — acceso al panel verificado el 2026-10-27

Autorización: solicitud completa «KP Group — Desarrollo MV-003, MV-009 y MV-010».
Fuentes revisadas: AGENTS.md, PLANS.md, reglas de dominio, arquitectura, modelo de
datos, reglas de negocio, decisiones y estado; documentación funcional v1.1,
especialmente secciones 2, 6, 7, 8, 9, 14, 16 y 17. Boost MCP disponible: inspección
de aplicación, consultas SELECT y search-docs para Filament 5.

### Estado real y cambios de esta etapa

- Antes de esta etapa, User tenía HasRoles, pero no FilamentUser/canAccessPanel.
  El middleware instalado permitía usuarios autenticados sin ese contrato en local
  y los rechazaba fuera de local. No había pruebas de autenticación del panel.
- app/Models/User.php ahora implementa FilamentUser y limita el panel admin a los
  tres roles aprobados, comprobando el guard del panel. Sin dependencia de APP_ENV.
- Creado tests/Feature/AdminPanelAuthenticationTest.php. AdminPanelProvider y
  RolePermissionSeeder no se modificaron; no se añadieron permisos persistidos.
- Login, dashboard y logout mantienen sus rutas Filament existentes.
- Git conserva los cambios previos de MV-006…008. No se modificaron sus migraciones,
  modelos ni factories, fuentes originales, Gantt, dependencias o .env.

Actualización de evidencia MariaDB frente al apartado histórico anterior: las
consultas de solo lectura confirman que clients y plants ya existen y que ambas
migraciones 2026_10_27_021628/021629 están registradas en el lote 3. No fueron
ejecutadas por esta etapa. Esta evidencia no equivale a una suite MariaDB aprobada;
las pruebas específicas de colación, límites y concurrencia continúan pendientes.

### Pruebas ejecutadas

- Primera ejecución de la nueva clase: 23 pruebas, 80 aserciones, todas aprobadas.
- Tras añadir el caso de guard distinto, suite completa: **41 pruebas y 128
  aserciones, todas aprobadas**. Incluye 24 pruebas nuevas de MV-003 y las 17 previas.
- Cobertura nueva: login para los tres roles; credenciales incorrectas; denegación
  de login y acceso directo sin rol o con rol externo en local/production; acceso
  permitido en ambos entornos; visitante redirigido; logout e invalidación de sesión;
  retirada de rol durante sesión; permiso core.view insuficiente por sí solo;
  rechazo de otro panel y de rol con guard diferente.
- Se ejercitaron rutas HTTP y el componente Livewire real de login. No se realizó
  validación visual en navegador ni login con usuarios reales de MariaDB.
- DatabaseTestCase comprobó testing/sqlite/:memory:, URL vacía y claves foráneas;
  antes de migrar verificó PDO sqlite, archivo vacío y PRAGMA foreign_keys=1.
  Las variantes local/production cambian config(app.env) después de preparar los
  datos aislados, sin cambiar la conexión ni ejecutar pruebas en esos entornos reales.
- Roles y permisos usados como fixtures solo se crearon en SQLite en memoria.
  No se ejecutó RolePermissionSeeder ni ninguna migración en MariaDB.
- Pint --dirty --format agent ejecutado; git diff --check sin errores.

Comando de suite completa ejecutado, tras comprobar inexistente la ruta de caché:

```bash
APP_ENV=testing APP_CONFIG_CACHE=/tmp/kpgroup-mv003-no-config-cache.php DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= DB_FOREIGN_KEYS=true CACHE_STORE=array SESSION_DRIVER=array vendor/bin/phpunit --do-not-cache-result
```

### Estado por etapa y siguiente decisión

| Actividad | Estado comprobado | Límite |
|---|---|---|
| MV-003 | Terminado verificado para autenticación y acceso al panel solicitado | No acredita autorización de operaciones de catálogos todavía inexistentes ni alcance por servicios asignados. |
| MV-009 | Implementada y verificada en SQLite aislada | ClientResource, ClientPolicy, acciones y pruebas; MariaDB pendiente. |
| MV-010 | Implementada y verificada en SQLite aislada | PlantResource, PlantPolicy, acciones y pruebas; MariaDB pendiente. |

La [propuesta mínima de cuatro permisos](decisiones-tecnicas.md#mv-003-y-propuesta-de-permisos-mv-009mv-010--2026-10-27)
está documentada y NO aplicada: view/manage por catálogo, Administrador y Jefe;
sin acceso global para Supervisor ni permisos de eliminación. La sección 6 de la
solicitud requiere autorización expresa antes de modificar seeder o permisos.

Archivos de esta etapa: app/Models/User.php, tests/Feature/AdminPanelAuthenticationTest.php,
docs/decisiones-tecnicas.md y este archivo. Sin commits ni push.
Mensaje de commit propuesto: `feat: validar acceso al panel administrativo MV-003`.

## MV-009/MV-010 — implementación verificada en SQLite el 2026-10-27

Autorización: aprobación expresa de `maintenance.client.view`,
`maintenance.client.manage`, `maintenance.plant.view` y
`maintenance.plant.manage`, exclusivamente para Administrador y Jefe de
supervisores.

- RolePermissionSeeder conserva los 25 permisos previos y agrega únicamente los
  cuatro aprobados. Administrador y Jefe reciben 29; Supervisor conserva 15 y no
  recibe permisos de clientes/plantas. `findOrCreate` y `syncPermissions` mantienen
  la ejecución idempotente. El seeder no se ejecutó en MariaDB.
- Creadas `app/Policies/ClientPolicy.php` y `app/Policies/PlantPolicy.php`.
  Consulta usa `*.view`; creación, edición y activación/desactivación usan
  `*.manage`; `delete`, `deleteAny`, `restore` y `forceDelete` están denegados.
- Creados `ClientResource` y `PlantResource` en `Configuración`, con acciones de
  creación/edición y acción explícita de activar/desactivar. No hay acciones de
  eliminación física ni bulk delete.
- ClientResource valida name requerido/150 y short_name opcional/80, busca ambos
  campos y muestra estado activo. PlantResource muestra cliente, busca por nombre,
  filtra por cliente, valida los límites y unicidad compuesta mediante una regla
  `unique` condicionada por `client_id`; el índice de base de datos sigue siendo
  la garantía final.
- Plant mantiene el bloqueo de alta para cliente inactivo y ahora rechaza también
  la reasignación de una planta hacia un cliente inactivo. No impone restricciones
  adicionales a otros cambios.
- La baja lógica conserva plantas e histórico; no se añadió cascada ni borrado.

### Pruebas de esta etapa

- Suite completa aislada: **47 pruebas y 182 aserciones, todas aprobadas**.
- Incluye la matriz de roles y permisos, idempotencia del seeder, Policies, acceso
  directo por URL para Supervisor, navegación del Resource, ausencia de borrado,
  creación y edición mediante acciones Filament de Clientes y Plantas, unicidad y
  bloqueo de clientes inactivos, reasignación rechazada y conservación histórica.
- La conexión efectiva fue SQLite `:memory:` con foreign keys activadas, validada por
  `DatabaseTestCase` antes de migrar. No se modificó ni reconstruyó
  `kp_mantenimiento_vias`; no se ejecutaron migraciones ni seeders en MariaDB.
- `vendor/bin/pint --dirty --format agent` ejecutado y `git diff --check` limpio.

Estado: MV-009 y MV-010 **implementadas y verificadas en SQLite aislada**. La
validación específica de MariaDB 10.11, incluida su colación efectiva y pruebas de
concurrencia, permanece pendiente. El acceso futuro del Supervisor por servicios
asignados no se implementa porque ese módulo aún no existe.

Archivos de esta etapa: `database/seeders/RolePermissionSeeder.php`,
`app/Policies/ClientPolicy.php`, `app/Policies/PlantPolicy.php`,
`app/Models/Plant.php`, los Resources y sus páginas bajo
`app/Filament/Resources/Clients` y `app/Filament/Resources/Plants`, y
`tests/Feature/CatalogAuthorizationAndResourcesTest.php`.

Comandos propuestos para aplicar después en MariaDB, **no ejecutados**:

```bash
php artisan migrate:status --database=mariadb
php artisan db:seed --class=Database\\Seeders\\RolePermissionSeeder --no-interaction
```

Ejecutar el seeder solo después de verificar la base y autorización de escritura;
la operación es idempotente, pero `syncPermissions` define exactamente los permisos
de los tres roles y debe revisarse frente a permisos añadidos fuera del seeder.

Mensaje de commit propuesto: `feat: implementar autorización y catálogos MV-009 MV-010`.

## Ajustes finales MV-009/MV-010 — verificados el 2026-10-27

- [PlantResource](../app/Filament/Resources/Plants/PlantResource.php) muestra solo
  clientes activos en el selector de creación. Al editar, mantiene disponible el
  cliente actual incluso si está inactivo, preservando la relación histórica.
- Al editar una planta con cliente inactivo se muestra el aviso informativo aprobado;
  es no bloqueante y no cambia las Policies ni crea un mecanismo de congelamiento.
- [config/app.php](../config/app.php) fija `es` como idioma, `es` como fallback y
  `es_MX` para Faker. Los labels de recursos son Cliente/Clientes y Planta/Plantas;
  acciones nativas como Crear, Crear y crear otro, Editar, Guardar cambios y
  Cancelar se resuelven mediante traducciones nativas de Filament 5.
- No se modificaron migraciones, permisos, roles, datos MariaDB ni archivos de
  `vendor`. No se ejecutaron migraciones ni seeders.

Pruebas después de los ajustes: **48 pruebas y 191 aserciones, todas aprobadas** en
SQLite `:memory:` con la conexión efectiva validada por `DatabaseTestCase`. Incluyen
selector de clientes inactivos, edición histórica, aviso no bloqueante, matriz de
permisos, acceso directo, unicidad, baja lógica y ausencia de eliminación física.
`vendor/bin/pint --dirty --format agent` y `git diff --check` terminaron correctamente.

Estado: MV-009 y MV-010 quedan **terminadas y verificadas en SQLite aislada**. Sigue
pendiente la comprobación visual en navegador y la validación específica de MariaDB
10.11; ninguna de las dos se ejecutó en esta etapa.

Archivos modificados en este ajuste: `app/Filament/Resources/Plants/PlantResource.php`,
`config/app.php`, `tests/Feature/CatalogAuthorizationAndResourcesTest.php`,
`docs/decisiones-tecnicas.md` y este archivo.

Mensaje de commit propuesto: `fix: ajustar idioma y selector histórico de catálogos`.

## MV-011/MV-012 — vías y cambios, verificado en SQLite el 2026-10-29

Autorización: solicitud adjunta «KP GROUP — SEMANA 02 — MV-011 y MV-012».
Fuentes contrastadas: AGENTS.md, PLANS.md, reglas de dominio, arquitectura,
modelo-datos, reglas de negocio, decisiones, estado y Documentación Funcional y
Técnica v1.1 (secciones 3, 4/RN-005 y RN-026, 6/RF-003 a RF-005, 7, 8 y 9).
Versiones comprobadas: PHP 8.3.30, Laravel 13.38.0, Filament 5.10.3, Spatie
Permission 8.3.2, Laravel Boost 2.10.3 y MariaDB 10.11.14.

### Resultado

- MV-011: diseño documentado; `Track` y `TrackSwitch` son entidades hermanas con
  pertenencia directa a Plant. Por aclaración aprobada el 2026-10-29, Vía es única
  por `(plant_id, name)` y Cambio por `(plant_id, code)`. `TrackSwitch` no es hijo
  de Track; Tramos se implementan en MV-013/MV-014.
- MV-012: dos migraciones, modelos, relaciones tipadas, factories y pruebas
  creados. Las claves foráneas a Plant restringen el borrado; `active` tiene
  default true; campos opcionales admiten NULL. Sin SoftDeletes ni croquis cargados.
- Las altas Eloquent de vías/cambios rechazan cliente o planta inactivos. Desactivar
  cliente o planta no cambia el estado de vías/cambios ni modifica códigos.
- No se modificaron permisos, Resources, migraciones de clientes/plantas, Gantt,
  fuentes, `.env` ni datos MariaDB; no se creó commit.

### Archivos de esta etapa

- Nuevos: `app/Models/Track.php`, `app/Models/TrackSwitch.php`, factories
  correspondientes, migraciones `2026_10_29_212111_create_tracks_table.php` y
  `2026_10_29_212112_create_track_switches_table.php`, pruebas de modelos y
  `tests/Feature/Database/TrackMigrationsTest.php`.
- Modificados: `app/Models/Plant.php`, `docs/modelo-datos.md`,
  `docs/decisiones-tecnicas.md` y este estado.

### Pruebas y límites

- Suite completa aislada: **62 pruebas y 228 aserciones, todas aprobadas**.
  Las tres clases nuevas aportan 14 pruebas y 37 aserciones; cubren relaciones,
  campos nulos, defaults, FK obligatoria/existente, ausencia de unicidad,
  independencia Track/TrackSwitch, conservación al desactivar, restricción de
  borrado de Plant y rollback/reaplicación de las dos migraciones.
- `DatabaseTestCase` comprobó `testing` + SQLite `:memory:`, URL vacía y
  `foreign_keys=1` sobre la conexión efectiva antes de migrar.
- `vendor/bin/pint --format agent` aplicado a archivos PHP nuevos/modificados;
  `vendor/bin/pint --dirty --format agent` pasó.
- Boost confirmó por lectura MariaDB 10.11.14 y ausencia de las tablas nuevas.
  Las migraciones **no se aplicaron ni se probaron en MariaDB**.
- Validación MariaDB pendiente: tipos unsigned, longitudes y nulabilidad
  efectivas, índices/FKs y comportamiento de colación del motor. No se afirma
  validación MariaDB a partir de SQLite.

Comando aislado de suite completa ejecutado (se comprobó que no existiera el
archivo de caché de configuración indicado):

```bash
APP_ENV=testing APP_CONFIG_CACHE=/tmp/kpgroup-mv011-no-config-cache.php DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= DB_FOREIGN_KEYS=true CACHE_STORE=array SESSION_DRIVER=array vendor/bin/phpunit --do-not-cache-result
```

### Comandos propuestos para MariaDB, requieren autorización posterior

No ejecutar hasta confirmar que `mariadb` apunta al destino autorizado y revisar
el SQL de `--pretend`. La migración añade tablas vacías; no modifica tablas
anteriores.

```bash
php artisan migrate:status --database=mariadb
php artisan migrate --database=mariadb --path=database/migrations/2026_10_29_212111_create_tracks_table.php --path=database/migrations/2026_10_29_212112_create_track_switches_table.php --pretend --no-interaction
php artisan migrate --database=mariadb --path=database/migrations/2026_10_29_212111_create_tracks_table.php --path=database/migrations/2026_10_29_212112_create_track_switches_table.php --no-interaction
php artisan migrate:status --database=mariadb
```

Estado: MV-011 y MV-012 implementadas y verificadas en SQLite aislada; MV-012
no queda validada completamente hasta revisión/aplicación en MariaDB autorizada.
Mensaje de commit propuesto: `feat: implementar vias y cambios MV-011 MV-012`.
El bloque se detiene aquí; no se inició MV-013.

## MV-011 a MV-014 — snapshot previo a corrección — 2026-10-29

Autorización: solicitud adjunta «KP GROUP — SEMANA 02 — Ajustes MV-011/MV-012 e
implementación MV-013/MV-014». Se volvieron a contrastar las reglas, arquitectura,
modelo de datos, decisiones, estado y la Documentación Funcional y Técnica v1.1,
secciones 3, 4, 6–9. Boost comprobó por lectura que las migraciones de vías/cambios
no aparecen en `migrations` y que las tablas aún no existen en MariaDB 10.11.14;
por ello sus migraciones no ejecutadas se actualizaron en sitio, según el alcance.

### Resultado

- MV-011/MV-012 actualizadas: `UNIQUE(plant_id, name)` para vías y
  `UNIQUE(plant_id, code)` para cambios. Se preservan FK restrictivas, campos y
  relaciones previas. Alta Eloquent rechaza cliente o planta inactivos.
- MV-013/MV-014 implementadas: tabla y modelo `TrackSection`, factory, relación
  vía-tramos, referencias inicial/final opcionales, unicidad `(track_id, name)` y
  FKs restrictivas a Track y TrackSwitch.
- Inicio y fin se validan por separado al crear y guardar: cada cambio debe estar
  en la planta de la vía actual del tramo, incluso al cambiar `track_id`. El
  nombre manual se conserva literalmente; no se deriva de los cambios.
- Las altas nuevas de tramo requieren cliente, planta y vía activos. Desactivar
  esos catálogos no desactiva ni elimina sus registros relacionados. No se
  bloquearon ediciones generales ni se añadieron UI, Policies, permisos o
  SoftDeletes.
- Se registraron las decisiones aprobadas y la diferencia frente al criterio
  inicial de Semana 02 (sin índices únicos) en decisiones-tecnicas.md.

### Archivos

- Nuevos: `app/Models/TrackSection.php`, `database/factories/TrackSectionFactory.php`,
  `database/migrations/2026_10_29_221616_create_track_sections_table.php` y
  `tests/Feature/Models/TrackSectionTest.php`.
- También quedan sin seguimiento, como parte del bloque previo conservado:
  modelos/factories/migraciones de Track y TrackSwitch, pruebas de ambos y
  `tests/Feature/Database/TrackMigrationsTest.php`.
- Modificados en este ajuste: modelos Track/TrackSwitch, migraciones
  `2026_10_29_212111_create_tracks_table.php` y
  `2026_10_29_212112_create_track_switches_table.php` para índices únicos, pruebas
  correspondientes, `Plant.php` (relaciones previas), y los tres documentos de
  datos, decisiones y estado.

### Verificación

- Suite completa en SQLite `:memory:`: **84 pruebas, 286 aserciones, 0 fallos**.
  Incluye regresión de Semana 01, unicidad por padre, referencias opcionales,
  pertenencia de delimitadores, cambios de delimitadores/vía durante edición,
  estados inactivos, FKs y restricción de borrado.
- `DatabaseTestCase` validó `APP_ENV=testing`, conexión efectiva SQLite
  `:memory:`, URL vacía y `foreign_keys=1` antes de migrar.
- Pint (`--format agent` sobre archivos afectados y `--dirty --format agent`) y
  `git diff --check` pasaron.
- MariaDB se consultó solo para confirmar versión, historial de estas migraciones
  y ausencia de tablas. No se aplicaron migraciones ni se modificaron datos.
- Pendiente en MariaDB: revisar SQL y comprobar índices únicos compuestos, tipos
  unsigned, longitudes, nulabilidad, FKs y colación/caso/acentos. La suite SQLite
  no acredita esos detalles del motor MariaDB.

Comando de suite ejecutado, tras comprobar que el archivo de caché de configuración
no existía:

```bash
APP_ENV=testing APP_CONFIG_CACHE=/tmp/kpgroup-mv014-no-config-cache.php DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= DB_FOREIGN_KEYS=true CACHE_STORE=array SESSION_DRIVER=array vendor/bin/phpunit --do-not-cache-result
```

### Comandos propuestos para aplicar en MariaDB, con autorización posterior

Confirmar primero que `mariadb` apunta al destino autorizado y revisar el SQL de
`--pretend`:

```bash
php artisan migrate:status --database=mariadb
php artisan migrate --database=mariadb --path=database/migrations/2026_10_29_212111_create_tracks_table.php --path=database/migrations/2026_10_29_212112_create_track_switches_table.php --path=database/migrations/2026_10_29_221616_create_track_sections_table.php --pretend --no-interaction
php artisan migrate --database=mariadb --path=database/migrations/2026_10_29_212111_create_tracks_table.php --path=database/migrations/2026_10_29_212112_create_track_switches_table.php --path=database/migrations/2026_10_29_221616_create_track_sections_table.php --no-interaction
php artisan migrate:status --database=mariadb
```

Estado en ese corte: MV-011 a MV-014 implementadas y verificadas en SQLite aislada. Las
migraciones no están verificadas en MariaDB hasta que se revisen y apliquen con
autorización. No se avanzó a MV-015; no se creó commit ni se hizo push.
Mensaje de commit propuesto: `feat: implementar tramos y restricciones de vias`.

## Corrección MV-013/MV-014 — identificador manual del tramo — 2026-11-02

Autorización: solicitud adjunta «KP GROUP — Corrección funcional MV-013 / MV-014 —
Desvincular Tramos de Cambios de vía». La aclaración reemplaza el diseño de
referencias opcionales de inicio/fin: un TrackSection pertenece solo a Track; su
nombre manual puede contener identificadores como `C1-C2`, `C1-V2` o `C4-Patio` y
no crea relaciones con TrackSwitch, vías nombradas o lugares en sus extremos.

- Se retiraron de `TrackSection` las relaciones/campos `start_switch_id` y
  `end_switch_id`, así como los atributos de factory y validaciones asociadas.
  `track_id`, `name`, `code`, estado, unicidad y reglas de altas activas se
  mantienen.
- Migración incremental añadida:
  `2026_11_02_200932_remove_switch_references_from_track_sections_table.php`.
  Antes de alterar el esquema, aborta si alguna referencia no es nula; conserva
  filas y demás atributos. Su reversión vuelve a crear columnas nullable vacías,
  por lo que no recuperaría valores eliminados.
- La consulta de solo lectura a MariaDB confirmó las tres migraciones originales
  registradas en batch 4, `total_sections=0`, `sections_with_start_switch=0` y
  `sections_with_end_switch=0`. La tabla tiene índices individuales para las dos
  FKs, que la migración elimina junto con dichas FKs/columnas.
- Suite completa aislada SQLite `:memory:`: **78 pruebas, 274 aserciones, 0 fallos**.
  Incluye eliminación/restauración de columnas y preservación de los datos de un
  tramo con referencias nulas. Pint (`--dirty --format agent`), `git diff --check`
  y `php -l` de la migración y modelo pasaron.
- Vista `--pretend` MariaDB completada. SQL emitido: comprobación de referencias
  no nulas; `ALTER TABLE track_sections DROP FOREIGN KEY` para ambas FKs; `DROP
  INDEX` para ambos índices individuales; y `DROP start_switch_id, DROP
  end_switch_id`. El modo pretend no aplicó cambios.
- Comando exacto propuesto, únicamente tras autorización para aplicarlo:
  `php artisan migrate --database=mariadb --path=database/migrations/2026_11_02_200932_remove_switch_references_from_track_sections_table.php --no-interaction`.
  No se ejecuta migración correctiva en MariaDB ni se modifican sus datos en esta
  tarea.
- Archivos de modelo, factory, migración, pruebas, y documentos de arquitectura,
  modelo de datos, decisiones y estado actualizados. No se alteran fuentes ni Gantt.

Estado: corrección implementada; actualización en MariaDB pendiente de autorización.
Mensaje de commit propuesto: `fix: desvincular tramos de cambios de via`.

## MV-015/MV-016 — mantenimiento Filament de Vías y Cambios — 2026-11-02

Autorización: solicitud adjunta «KP GROUP — SEMANA 02 — Desarrollo de interfaces
Filament — MV-015 y MV-016». Versiones comprobadas: PHP 8.3, Laravel 13.38.0,
Filament 5.10.3, Spatie Permission 8.3.2 y MariaDB 10.11.14.

### Resultado

- MV-015: Resource de Vías con alta/edición, búsqueda por nombre/código, filtros
  por cliente/planta/estado y acción lógica de activar/desactivar. El formulario
  conserva la Planta histórica actual, limita destinos a infraestructura activa y
  valida unicidad del nombre por Planta. Croquis queda fuera de la UI.
- MV-016: Resource de Cambios de vía con alta/edición, búsqueda por código/nombre,
  filtros por Planta/estado y acción lógica de activar/desactivar. El código se
  conserva como lo captura el usuario y se valida único por Planta.
- Ambos Resources están en español bajo `Configuración`. Selectores muestran
  Cliente y Planta. Avisos informan cuando la infraestructura histórica está
  inactiva; no bloquean otras correcciones del registro.
- Policies aplican view/manage y niegan eliminación física individual, masiva,
  restore y force delete. Supervisor conserva acceso al panel pero no a las rutas
  del catálogo; no se añadieron relaciones con Tramos.
- El modelo impide altas nuevas vinculadas a Planta/Cliente inactivos y también
  impide reasignar una Vía o Cambio a infraestructura inactiva. No modifica hijos
  al desactivar un registro.
- `RolePermissionSeeder` agrega exclusivamente los cuatro permisos aprobados.
  No se ejecutó en MariaDB.

### Verificación y límites

- Pruebas enfocadas: **17 pruebas, 165 aserciones, 0 fallos**. Suite completa:
  **95 pruebas, 443 aserciones, 0 fallos**. La primera corrida encontró una
  expectativa histórica de 29 permisos en la prueba de MV-009/MV-010; se actualizó
  a 33 para Administrador/Jefe, manteniendo Supervisor en 15, y la suite pasó.
- `vendor/bin/pint --dirty --format agent` y `git diff --check` pasaron.
- Las pruebas usan `DatabaseTestCase`, que rechaza cualquier configuración salvo
  `testing` + SQLite `:memory:` con claves foráneas activas.
- Boost confirmó por lectura que las tres migraciones core están registradas en
  MariaDB batch 4 y la corrección MV-013/MV-014 en batch 5; las tablas `tracks`,
  `track_switches` y `track_sections` existen. El esquema leído conserva las
  columnas `sketch_disk`/`sketch_path` y los índices únicos compuestos esperados.
- Boost también confirmó que los cuatro permisos Track aún no existen en MariaDB,
  y que los roles tienen ahora 29 permisos (Administrador/Jefe) y 15 (Supervisor).
  No se ejecutó ninguna migración ni seeder en esta etapa.
- No hubo comprobación manual en navegador. Validar al menos creación, edición,
  filtros y acciones de estado de ambos catálogos, selector histórico con Planta
  inactiva, mensajes duplicados y denegación del Supervisor.

### Archivos

- Nuevos: Resources, esquemas, tablas y páginas de Track/TrackSwitch; Policies
  correspondientes; `tests/Feature/TrackCatalogAuthorizationAndResourcesTest.php`.
- Modificados: `Track.php`, `TrackSwitch.php`, `RolePermissionSeeder.php`, y este
  archivo junto con `docs/decisiones-tecnicas.md`.

Comando recomendado para aplicar permisos, únicamente tras autorización de
escritura y revisión de los efectos de `syncPermissions`:

```bash
php artisan db:seed --class=Database\\Seeders\\RolePermissionSeeder --no-interaction
```

Estado: MV-015/MV-016 implementadas; suite completa pendiente de última corrida.
Mensaje de commit propuesto: `feat: implementar catalogos de vias MV-015 MV-016`.

## MV-017 — administración de Tramos por Vía — 2026-11-02

Autorización: solicitud adjunta «KP GROUP — SEMANA 02 — MV-017 — Implementar
administración de Tramos por Vía». Se conservaron los cambios previos de MV-015/
MV-016 y no se inició MV-018.

### Implementación

- Añadido `SectionsRelationManager` nativo de Filament 5 a la página Editar Vía.
  Permite consultar, buscar, filtrar por estado, crear y editar tramos, además de
  activar/desactivar con notificación en español. No hay acción de eliminación.
- El formulario incluye nombre manual obligatorio (120), código opcional (50) y
  estado activo por defecto. No expone `track_id` ni referencias a TrackSwitch.
  El alta se ejecuta a través de `Track::sections()`; el modelo impide cambiar la
  vía propietaria en cualquier actualización.
- La tabla está scopeada a la Vía propietaria. La Policy de TrackSection reutiliza
  los permisos de Vía existentes. `canViewForRecord()` verifica tipo, existencia,
  página propietaria y permiso del usuario; Supervisor no puede abrir el manager.
- La unicidad `UNIQUE(track_id, name)` existente se valida también en el formulario
  con mensaje en español. Altas para Cliente/Planta/Vía inactivos siguen rechazadas
  por el modelo. Aviso en la edición de Vía inactiva; se preservan y pueden editarse
  sus tramos históricos. No se añadieron permisos, Resources globales ni migraciones.

### Verificación

- Pruebas enfocadas: **10 pruebas, 95 aserciones, 0 fallos**. Suite completa:
  **105 pruebas, 538 aserciones, 0 fallos**. `vendor/bin/pint --dirty --format
  agent` y `git diff --check` pasaron.
- Las pruebas usan `DatabaseTestCase` y SQLite `:memory:` con FK activadas.
- Boost confirmó por lectura la migración de TrackSection registrada en batch 4
  y la corrección de referencias en batch 5. MariaDB contiene `track_sections` con
  columnas `track_id`, `name`, `code`, `active`, timestamps; índice único
  `(track_id, name)` y FK restrictiva a `tracks`. No se realizaron escrituras,
  migraciones ni seeders en MariaDB.
- Manual en navegador pendiente: abrir una Vía, consultar/filtrar/buscar tramos,
  crear y editar, probar duplicado, cambiar estado, verificar Vía inactiva y
  confirmar denegación de Supervisor.

Archivos nuevos: `SectionsRelationManager.php`, `TrackSectionPolicy.php` y
`tests/Feature/SectionsRelationManagerTest.php`. Modificados: `TrackResource.php`,
`TrackForm.php`, `TrackSection.php`, además de este archivo y
`docs/decisiones-tecnicas.md`.

Estado: MV-017 implementada y verificada en SQLite aislada. La validación manual
en navegador continúa pendiente.
Mensaje de commit propuesto: `feat: administrar tramos desde vias MV-017`.

## Ajustes MV-015/MV-017 — catálogo independiente y código único por Planta — 2026-11-03

Autorización: solicitud adjunta «KP GROUP — Ajustes de interfaces MV-015 y
MV-017». Se preservan las modificaciones previas sin commit y no se inicia MV-018.
Versiones confirmadas por Boost: PHP 8.3, Laravel 13.38.0, Filament 5.10.3,
Spatie Permission 8.3.2 y MariaDB 10.11.14.

### Implementación

- Se agrega `TrackSectionResource` en `Configuración > Tramos`, con páginas de
  listado, creación y edición. Convive con `SectionsRelationManager` en Editar Vía.
  Ambas interfaces conservan validación y Policy comunes, sin eliminación física.
- Selector con etiquetas Cliente — Planta — Vía; en creación limita opciones a
  infraestructura activa. En edición muestra la Vía propietaria deshabilitada;
  la regla Eloquent de MV-017 continúa bloqueando reasignación. Tabla con Cliente,
  Planta, Vía, tramo, código, estado, búsqueda por nombre/código y filtros por
  Planta/Vía/estado.
- `TrackForm` valida código nullable único dentro de la Planta y conserva el
  código actual al editar. Migración incremental
  `2026_11_03_163105_add_plant_code_unique_to_tracks_table.php` agrega
  `UNIQUE(plant_id, code)` sin cambiar la migración original ni reconstruir la
  tabla; conserva `UNIQUE(plant_id, name)`.

### Consulta MariaDB y verificación

- Consulta Boost solo lectura ejecutada: `SELECT plant_id, code, COUNT(*) AS
  occurrences FROM tracks WHERE code IS NOT NULL GROUP BY plant_id, code HAVING
  COUNT(*) > 1 ORDER BY plant_id, code`. Resultado: `[]` (ningún código no nulo
  duplicado por Planta). Se confirmó `code` nullable y el índice único existente
  `(plant_id, name)`. No se modificaron datos, no hubo seeders y la migración
  nueva no se aplicó en MariaDB.
- Pruebas enfocadas SQLite `:memory:`: **32 pruebas, 375 aserciones, 0 fallos**,
  incluyendo `SectionsRelationManager` y la migración incremental.
- Suite completa aislada: **110 pruebas, 643 aserciones, 0 fallos**. La corrida
  usó `APP_ENV=testing`, conexión efectiva SQLite `:memory:`, URL vacía y FK
  activadas mediante `DatabaseTestCase`; se comprobó que el archivo de caché
  indicado no existiera antes de arrancar.
- Pruebas enfocadas para el catálogo y regresiones relacionadas: **33 pruebas,
  377 aserciones, 0 fallos**; el catálogo por sí solo: 5 pruebas.
  Pint `--dirty --format agent`, `php -l` para PHP añadidos/modificados y
  `git diff --check` pasaron.
- Verificación visual en navegador pendiente. Revisar **Configuración > Tramos**
  para alta, tabla, búsqueda, filtros, edición y cambio de estado; después abrir
  **Configuración > Vías > Editar Vía** y confirmar el Relation Manager. Verificar
  la denegación de Supervisor y el selector/propiedad de Vía con Administrador o
  Jefe de supervisores.

Comando exacto propuesto para aplicar la migración en MariaDB solo con autorización
posterior y destino confirmado:

```bash
php artisan migrate --database=mariadb --path=database/migrations/2026_11_03_163105_add_plant_code_unique_to_tracks_table.php --no-interaction
```

Archivos de esta ampliación: Resource, formulario, tabla y páginas de Tramos;
migración compuesta; ajuste de `TrackForm`; pruebas `TrackSectionCatalogTest` y
actualización de `TrackMigrationsTest`/expectativa en prueba de catálogo de Vías;
modelo de datos, decisiones y este estado. No se cambia `track_sections`, el seeder,
Gantt, fuentes ni `.env`; no hubo commit o push.

Mensaje de commit propuesto: `feat: añadir catálogo de tramos y unicidad de código por planta`.

## Corrección MV-017 — códigos únicos por Vía en Tramos — 2026-11-03

Autorización: solicitud adjunta «KP GROUP — Corrección MV-017 — Validación de
códigos únicos por Vía en Tramos». Se preservan los cambios anteriores sin commit.

### Resultado

- El campo opcional `code` ahora valida unicidad por `track_id` al crear y editar
  desde el catálogo independiente y el Relation Manager. La edición ignora el
  propio Tramo; el Relation Manager usa el registro propietario para determinar
  la Vía, no un dato del formulario. Se mantiene la unicidad del nombre.
- Nueva migración incremental
  `2026_11_03_180740_add_track_code_unique_to_track_sections_table.php` agrega
  `UNIQUE(track_id, code)` y su `down()` elimina solo ese índice. Antes de alterar
  el esquema comprueba duplicados y, si hay, aborta indicando Vía, código, total e
  IDs. Conserva la tabla y `UNIQUE(track_id, name)`.
- La consulta MariaDB de solo lectura indicada en la solicitud halló un grupo:
  `track_id=1`, `code=t1`, tres registros (`id=1, 3, 4`). La consulta adicional
  de identificadores confirmó esos mismos IDs. No se modificaron códigos ni se
  aplicaron migraciones o seeders en MariaDB.

### Regularización manual pendiente

Revisar los Tramos `1`, `3` y `4` de la Vía `1` con el responsable y determinar
el código correcto para cada uno. Actualizar manualmente los registros aprobados,
sin borrar ni desactivar registros, y volver a ejecutar la consulta agrupada de
duplicados. La migración solo podrá aplicarse cuando la consulta devuelva cero
filas. Su guard también volverá a listar IDs si quedan duplicados.

### Verificación

- Pruebas enfocadas SQLite `:memory:`: **19 pruebas, 282 aserciones, 0 fallos**.
  Suite completa aislada: **113 pruebas, 713 aserciones, 0 fallos**. `DatabaseTestCase`
  validó `APP_ENV=testing`, SQLite `:memory:`, URL vacía y claves foráneas
  activas; se comprobó que el archivo de caché configurado no existiera.
- `vendor/bin/pint --dirty --format agent`, `php -l` de los PHP relevantes y
  `git diff --check` pasaron.
- No se hizo revisión en navegador; comprobar creación/edición desde ambos puntos
  de entrada, códigos duplicados, mismo código entre Vías y códigos NULL.

Comando para aplicar tras regularizar y volver a verificar los datos, sujeto a
autorización posterior y confirmación del destino MariaDB:

```bash
php artisan migrate --database=mariadb --path=database/migrations/2026_11_03_180740_add_track_code_unique_to_track_sections_table.php --no-interaction
```

Archivos: ambos formularios, migración nueva y prueba de su guard, pruebas del
catálogo/Relation Manager y migración, modelo de datos, decisiones y este estado.
No se modifica el modelo funcional ni se inicia MV-018.
Mensaje de commit propuesto: `fix: validar codigos de tramo unicos por via`.

## MV-018 — croquis y almacenamiento externo — pendiente

La empresa todavía no ha proporcionado ni autorizado el método de facturación requerido para Google Cloud Storage. Por ello quedan pendientes la carga, consulta y reemplazo de croquis; se retomarán cuando se defina y autorice el almacenamiento. No se añadieron credenciales, dependencias, configuración de nube ni datos de demostración. Este pendiente no impide validar los catálogos del Core ferroviario.

## MV-019 — validación de integridad y seguridad — 2026-11-04

Autorización: solicitud adjunta «KP GROUP — SEMANA 02 — MV-019 y MV-020». Se revisaron AGENTS.md, PLANS.md, dominio, documentación, Resources, Policies, modelos, migraciones, factories, seeders y pruebas. Boost confirmó PHP 8.3, Laravel 13.38.0, Filament 5.10.3, Spatie Permission 8.3.2 y MariaDB 10.11.14.

### Matriz de validación

| Área | Resultado comprobado |
|---|---|
| Relaciones | `Client 1:N Plant`, `Plant 1:N Track/TrackSwitch` y `Track 1:N TrackSection`; `TrackSwitch` no es hijo de vía ni contiene tramos. |
| Integridad | FKs restrictivas en `plants.client_id`, `tracks.plant_id`, `track_switches.plant_id` y `track_sections.track_id`; sin borrado físico en Policies ni Resources. |
| Unicidad | MariaDB tiene `UNIQUE(plant_id, name)` y `UNIQUE(plant_id, code)` para vías, `UNIQUE(plant_id, code)` para cambios y `UNIQUE(track_id, name)` para tramos. SQLite cubre además `UNIQUE(track_id, code)`. |
| Estado e histórico | Altas en infraestructura inactiva se rechazan; desactivar conserva los registros relacionados; acciones Filament activan/desactivan sin eliminar. |
| Autorización | Administrador y Jefe de supervisores tienen permisos de administración; Supervisor no tiene permisos de catálogos, ni acceso por URL directa ni por solicitudes Livewire. Policies protegen operaciones, y las acciones personalizadas verifican `update`. |

### MariaDB: resultado y pendiente

Las consultas de solo lectura confirmaron 2 clientes, 2 plantas, 2 vías, 2 cambios y 5 tramos. No hay grupos duplicados no nulos de código de tramo actualmente. No obstante, la migración `2026_11_03_180740_add_track_code_unique_to_track_sections_table.php` no figura en `migrations`; por ello MariaDB aún no tiene el índice `UNIQUE(track_id, code)`. La migración no se ejecutó: requiere autorización separada y revisión del destino. No se aplicaron migraciones, seeders, cambios de datos ni operaciones destructivas.

### Automatización ejecutada

- Suite completa con `testing` + SQLite `:memory:` y FK activadas: **113 pruebas, 713 aserciones, 0 fallos**.
- `vendor/bin/pint --dirty --format agent`: correcto.
- `php -l` sobre `app/`, `database/migrations/` y `tests/`: sin errores.
- `git diff --check`: correcto.

No se agregaron pruebas: las existentes ya cubren los casos de MV-019, incluida la salvaguarda de la migración pendiente de códigos de tramo.

## MV-020 — demostración funcional y documentación — 2026-11-04

Los módulos disponibles para Dirección son Clientes, Plantas, Vías, Cambios de vía y Tramos, tanto desde la Vía como desde el catálogo independiente. La demostración excluye expresamente croquis y almacenamiento en la nube mientras MV-018 siga pendiente. Usar datos efímeros de SQLite `:memory:` o un entorno de demostración aislado autorizado; no ejecutar seeders ni insertar datos de demostración en MariaDB de desarrollo.

### Guion propuesto para Dirección

1. Iniciar sesión como Administrador o Jefe de supervisores y abrir Configuración.
2. Registrar un Cliente activo y una Planta activa vinculada; mostrar el historial al desactivar/reactivar un catálogo, sin borrar filas.
3. En la Planta, registrar una Vía y un Cambio de vía; intentar repetir el nombre de la Vía o el código del Cambio para mostrar la validación.
4. Desde Editar Vía, crear un Tramo y probar nombre/código duplicado dentro de esa Vía; mostrar que el mismo código puede usarse en otra Vía y que el Tramo no se relaciona con Cambios.
5. Abrir Configuración > Tramos, crear y editar un segundo Tramo con el selector Cliente — Planta — Vía, filtros y búsqueda.
6. Activar/desactivar una Vía, Cambio y Tramo para mostrar conservación histórica.
7. Iniciar sesión como Supervisor e intentar abrir una URL directa de catálogo: el acceso debe ser denegado. Cerrar indicando que no se muestra ningún flujo de croquis ni integración cloud.

### Estado de cierre parcial de Etapa 1

| MV | Estado al 2026-11-04 |
|---|---|
| MV-011 | Diseño de vías: implementado y verificado en SQLite. |
| MV-012 | Modelo y migración de vías/cambios: implementado; MariaDB conserva el esquema base aplicado. |
| MV-013 | Diseño de tramos: implementado y verificado en SQLite. |
| MV-014 | Modelo y migración de tramos: implementado; MariaDB conserva el esquema base aplicado. |
| MV-015 | Administración Filament de vías: implementada y cubierta por pruebas; revisión visual manual pendiente. |
| MV-016 | Administración Filament de cambios: implementada y cubierta por pruebas; revisión visual manual pendiente. |
| MV-017 | Administración de tramos por vía y catálogo independiente: implementada y verificada en SQLite; MariaDB conserva pendiente el índice incremental de código por vía. |
| MV-018 | Pendiente por dependencia externa de Google Cloud Storage. |
| MV-019 | Terminado verificado, con la incidencia MariaDB de migración pendiente registrada. |
| MV-020 | Terminado: documentación y guion de demostración preparados. |

La Etapa 1 no se declara terminada mientras MV-018 y la aplicación autorizada de la migración pendiente de MariaDB continúen abiertas. No se creó commit ni push. Commit propuesto al cierre del bloque: `docs: registrar validacion y demostracion de etapa 1`.


## MV-021 — diseño documental de Servicio — 2026-11-04

Solicitud autorizada: «KP GROUP — SEMANA 03 — MV-021 — Modelar Servicio de
Mantenimiento». Alcance: análisis y actualización exclusiva de modelo-datos.md,
decisiones-tecnicas.md y este archivo. Inicio: rama `main`, HEAD `b5f24d9`
(`docs: registrar validacion y demostracion de etapa 1`), árbol limpio.

**Estado:** entregable de análisis terminado y contrastado documentalmente;
aprobación funcional del modelo pendiente. No se declara implementación ni
aceptación del modelo. MV-022 y siguientes no se iniciaron.

### Evidencia de inspección

- Leídos AGENTS.md, PLANS.md, índice/reglas de dominio, arquitectura, modelo,
  reglas de negocio, decisiones e historial de estado. Modelos Client, Plant y
  User inspeccionados: relaciones tipadas, Fillable, casts(), HasRoles y acceso
  al panel. Plant conserva su validación existente de Cliente inactivo; no se
  extrapola como comportamiento ya implementado para Servicios.
- Revisados inventario de archivos app/database/routes/tests, rutas web,
  migración de Plant y pruebas relacionadas de Plant. No existen archivos de
  implementación de MaintenanceService en esas rutas. No se ejecutó route:list
  ni la suite; la revisión de rutas fue estática y no valida acceso HTTP.
- PDFs originales extraídos con `pdftotext -layout` y leídos en los apartados
  pertinentes: v1.1 §7 (p. 12), relaciones/acceso y estados; RN-001…003 y
  RC-002/003/029/032…035; planificación de etapa 2 (5–18 noviembre).
- XLSX original leído mediante ZIP/XML, filas MV-021…MV-032, contrastadas con
  el índice CSV. MV-021 y MV-022 figuran el 05/11/2026; MV-023/024 el 06/11,
  MV-025/026 el 09/11, MV-027/028 el 10/11, MV-029/030 el 11/11. Son fechas
  planeadas; esta revisión del 04/11 no altera cronograma ni estados del Gantt.
- `php -v`, `composer -V`, `composer --no-plugins --no-scripts show --direct`
  y `show laravel/boost`; composer.json/lock e instalación coincidentes:
  PHP 8.3.30, Composer 2.10.3, Laravel 13.38.0, Filament 5.10.3,
  Spatie Permission 8.3.2 y Boost 2.10.3.
- Configuración MCP revisada selectivamente: caché array en servidor y subprocesos.
  Boost `application-info`, `database-schema` resumido y detalle de plants,
  `database-query` y `search-docs` disponibles y usados con éxito. Versión por
  SELECT: MariaDB 10.11.14. `plants.id` BIGINT UNSIGNED, con PK; su FK a clients
  restringe borrado/actualización. Consulta a information_schema confirmó ausencia
  de maintenance_services, maintenance_service_supervisors y maintenance_periods.
  No se consultaron datos personales ni se modificó la BD.
- Documentación Laravel 13 consultada para enums/casts, validación y migraciones;
  enlaces y propuestas en decisiones-tecnicas.md. No se instalaron paquetes.

### Resultado y validación

- Tabla completa de nueve atributos, nulabilidad, tipos propuestos, diagrama,
  reglas, índices/restricciones y evaluación de casos A–F documentados.
- Fuente confirma nombres de FK futuros `maintenance_service_id` y secuencia
  principal de estados. Se distinguen de abreviaturas anteriores y de reglas
  adicionales todavía pendientes; se conserva el historial documental.
- Pendientes explícitos: fechas, clasificación de altas con inactividad,
  correcciones históricas, duplicidad/pedido, default, transiciones, `none`,
  vigencia de asignaciones, alcance de borrado y timestamps/auditoría.
- Validación documental: `git diff --check`, revisión de diff, enlaces locales
  nuevos y alcance exclusivo a los tres documentos. Casos A–F revisados como
  diseño, no ejecutados como pruebas funcionales.
- Sin pruebas PHP, migraciones, seeders, compilación, cambios de roles/permisos,
  modelos, Resources ni configuración. No aplica Pint porque no se modificó PHP.
  FKs, enums, fechas y concurrencia deben probarse posteriormente en una BD
  aislada y autorizada; esta revisión no acredita comportamiento funcional.

MV-018 continúa **pendiente de autorización corporativa de Google Cloud Storage**
y no bloquea este análisis. Se conservan los pendientes previos de Etapa 1;
MV-021 no los revalida ni los resuelve.

Siguiente actividad: **MV-022 — Crear migración y modelo de Servicios**, después
de revisar/aprobar el modelo y resolver decisiones que afecten esquema/altas.
Recomendación: implementación mínima de Servicio y relación con Plant; dejar
Resource para MV-023, estados detallados para MV-024, asignaciones para MV-025/026
y Periodos para MV-029/030. No iniciar ninguna en este bloque.

Commit documental propuesto (no realizado):
`docs: definir modelo de servicio de mantenimiento MV-021`.
Sin commit ni push. Trabajo detenido al entregar MV-021 para revisión.


## MV-021 / MV-029 / MV-030 — actualización funcional registrada — 2026-11-04

Decisión aprobada el 4 de noviembre de 2026 por el usuario responsable del
proyecto, mediante la solicitud «Actualización de decisión funcional
MV-021 / MV-029 / MV-030». Estado: **registrada documentalmente; implementación
pendiente en MV-029/MV-030**. La aprobación se limita a la periodicidad quincenal;
no supone aceptación integral de MV-021 ni inicio de MV-022.

La periodicidad `biweekly` corresponde a **15 días naturales consecutivos**,
contados desde `maintenance_services.start_date`. El primer periodo inicia en
esa fecha y termina 14 días después, con ambos extremos incluidos. Cada periodo
posterior comienza al día siguiente del cierre del anterior. No se utilizan
quincenas de calendario (1–15 y 16–fin de mes).

Ejemplo aprobado para inicio del Servicio el 05/11/2026:

| Periodo | Inicio | Fin (incluido) |
|---|---|---|
| 1 | 05/11/2026 | 19/11/2026 |
| 2 | 20/11/2026 | 04/12/2026 |
| 3 | 05/12/2026 | 19/12/2026 |

La implementación corresponde a **MV-029/MV-030**, no a MV-021 ni MV-022.
Permanecen pendientes de definición:

- Tratamiento del último periodo parcial.
- Fechas de corte de reportes semanales y mensuales.
- Reglas para cambios posteriores de periodicidad.
- Recordatorios automáticos de captura por correo.

Se actualizaron exclusivamente `docs/modelo-datos.md`,
`docs/decisiones-tecnicas.md` y este archivo mediante secciones posteriores,
conservando el historial y los cambios de MV-021 ya presentes al inicio.
`git status --short` inicial mostró esos tres documentos modificados.
Verificación: revisión del diff y `git diff --check` sin errores.
No se ejecutaron pruebas funcionales ni se desarrollaron generación de periodos,
migraciones, modelos, formularios o notificaciones. No se alteró el Gantt ni la
base de datos. Sin commits ni push. MV-018 conserva su pendiente corporativo de
Google Cloud Storage y no bloquea esta actualización documental.

## MV-022 — modelo de Servicios verificado en SQLite — 2026-11-04

Autorización: solicitud «Semana 03 — MV-022». Rama main, HEAD b5f24d9;
los tres documentos de MV-021 ya estaban modificados y se conservaron íntegros.
Las fuentes, modelos Client/Plant/Track, factories y DatabaseTestCase se revisaron
junto con los resultados de inspección MV-021 de esta sesión. Boost search-docs
confirmó eventos Eloquent, enum casts y migraciones para Laravel 13.

MV-021 queda aprobado con planned, altas bajo Cliente/Planta activos, nombres y
pedidos repetibles, conservación sin borrado físico, fechas finales opcionales
no anteriores al inicio y timestamps Laravel. Alcance ejecutado: solo MV-022.

### Archivos

Creados:

- app/Models/MaintenanceService.php.
- app/ReportFrequency.php y app/MaintenanceServiceStatus.php.
- database/factories/MaintenanceServiceFactory.php.
- database/migrations/2026_11_04_201927_create_maintenance_services_table.php.
- tests/Feature/Models/MaintenanceServiceTest.php.

Modificados: app/Models/Plant.php (solo relación maintenanceServices),
docs/modelo-datos.md, docs/decisiones-tecnicas.md y este archivo.
Sin cambios de permisos, roles, configuración, fuentes originales ni Gantt.

### Verificación y límites

- Inspección Boost de solo lectura: no existía maintenance_services en MariaDB;
  tampoco había migración de Servicios en el repositorio. Se creó una nueva.
  El historial de migraciones consultado contiene los índices incrementales de
  Vías/Tramos en lotes 6 y 7. Esta lectura actualiza la evidencia histórica de su
  registro, pero no ejecutó esas migraciones ni revalidó su comportamiento.
- Migración aplicada exclusivamente por pruebas en SQLite :memory:.
  DatabaseTestCase verificó configuración testing/sqlite/:memory:/FK y, antes de
  migrar, PDO sqlite, archivo vacío en PRAGMA database_list y foreign_keys=1.
- Pruebas enfocadas: **29 pruebas, 72 aserciones, todas aprobadas**.
- Suite completa: **142 pruebas, 785 aserciones, todas aprobadas**. Sin errores
  ni advertencias reportados por el runner. Incluye regresión de catálogos previos.
- Cobertura: relaciones y aislamiento por Planta, FK nula/inexistente, borrado
  restringido de Planta, campos obligatorios/longitudes, límites de fechas,
  validación al editar fechas, enums/casts, defaults de BD/modelo, timestamps,
  altas rechazadas bajo inactividad aun con relaciones previamente cargadas,
  duplicados simultáneos, conservación histórica y rechazo de borrado de instancia.
- Pint --dirty --format agent ejecutado; git diff --check sin errores.
- Restricciones físicas en SQLite: PK, FK, NOT NULL y default planned; sin UNIQUE
  adicional ni CHECK. MariaDB previsto con tipos y longitudes aprobados; no
  ejecutado ni validado allí. Validaciones Laravel de nombres, pedido, fechas y
  dominios no equivalen a garantías físicas para SQL directo.
- CHECK no añadido por diferencias de DDL y falta de validación física MariaDB;
  decisión detallada en decisiones-tecnicas.md. Eventos omitidos por operaciones
  masivas/silenciosas o SQL no protegen validación/borrado. La comprobación de
  infraestructura activa no garantiza atomicidad ante desactivación concurrente.
- La factory admite overrides y campos opcionales nulos; no inventa periodos,
  asignaciones, estados automáticos ni unicidad de nombre/pedido.

Comando de suite ejecutado, tras comprobar que no existía el archivo de caché:

```bash
APP_ENV=testing APP_CONFIG_CACHE=/tmp/kpgroup-mv022-no-config-cache.php DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= DB_FOREIGN_KEYS=true CACHE_STORE=array SESSION_DRIVER=array vendor/bin/phpunit --do-not-cache-result
```

### Aplicación posterior, NO ejecutada en este bloque

Antes de aplicar: autorización de escritura, conexión efectiva de destino
confirmada sin exponer credenciales, revisión del SQL y validación en MariaDB
exclusiva de pruebas. No ejecutar migraciones pendientes ajenas al alcance.
Comandos propuestos para MariaDB de desarrollo, sujetos a esa autorización:

```bash
php artisan migrate:status --database=mariadb --no-interaction
php artisan migrate --database=mariadb --path=database/migrations/2026_11_04_201927_create_maintenance_services_table.php --pretend --no-interaction
php artisan migrate --database=mariadb --path=database/migrations/2026_11_04_201927_create_maintenance_services_table.php --no-interaction
php artisan migrate:status --database=mariadb --no-interaction
```

Verificar allí tipos unsigned, longitudes bajo modo SQL efectivo, nulabilidad,
default e integridad FK; SQLite no acredita esas propiedades MariaDB. down()
elimina tabla y datos: no usar rollback sobre información real como operación
inocua. No se ejecutaron migraciones, seeders ni escrituras sobre MariaDB.

### Dependencias futuras

planned es independiente del Programa; order_number no determina acceso.
Periodicidad por Servicio configurada por responsable autorizado, no por Supervisor
al capturar. MV-025…028 implementará asignaciones simultáneas y acceso por vigencia:
abrir directamente una asignación de Servicio única o elegir entre varias, sin
permitir acceso ajeno alterando ID/peticiones. Sin Policies operativas en MV-022.
MV-029/MV-030 implementará los 15 días naturales inclusivos desde start_date para
biweekly, no quincenas calendario; ejemplo aprobado conservado. Último periodo
parcial, cortes semanales/mensuales y cambios de frecuencia siguen pendientes.
none conserva la futura obligación de Periodo vigente. Recordatorios por correo
son requisito futuro dependiente de Periodos y Programa; no implementados.
MV-018 permanece pendiente de autorización corporativa de GCS y no bloquea MV-022.

Estado: **MV-022 implementado y verificado en SQLite aislada; aplicación y
validación MariaDB pendientes**. No se inició MV-023 ni ningún módulo posterior.
Commit propuesto: `feat: implementar servicios de mantenimiento MV-022`.
No se realizó commit ni push.

## MV-023 — módulo administrativo de Servicios — 2026-11-04

Autorización: solicitud «Semana 03 — MV-023». Inicio: rama main, HEAD c00a582
(`feat: implementar servicios de mantenimiento MV-022`), árbol limpio. Se conserva
el historial documental; no se modificaron migraciones ni modelos de MV-022.

### Inspección y alcance implementado

- Revisadas instrucciones y reglas del proyecto, documentación previa y modelos,
  enums/factory, Resources existentes, Policies y RolePermissionSeeder.
  Se consultó Boost search-docs para Filament 5: selectores dependientes, validación,
  enums/HasLabel y autorización de Resources. Versiones de esta sesión:
  Laravel 13.38.0, Filament 5.10.3, Spatie Permission 8.3.2 y PHP 8.3.30.
- Boost, únicamente por lectura, confirmó tabla maintenance_services y migración
  2026_11_04_201927_create_maintenance_services_table registrada en batch 8.
  Actualiza el estado histórico de aplicación pendiente; no se ejecutó ni se
  revalidó físicamente la migración en esta etapa.
- Los permisos existentes service.view/manage se comprobaron en MariaDB: ambos
  para Administrador/Jefe y view para Supervisor. Se reutilizan con condición
  administrativa temporal por rol en Policy. No se modificó el seeder ni los datos.
- Resource modular con tres páginas en Servicios → Servicios de mantenimiento.
  Alta con Cliente auxiliar activo y Planta dependiente; validación de pertenencia
  y activos en servidor. Campos, fechas y periodicidad según MV-022; estado
  forzado planned. Edición de los seis campos administrativos, Planta fija y
  estado informativo. Se descartan campos protegidos manipulados; histórico
  consultable/editable aunque Cliente o Planta estén inactivos.
- Tabla con las ocho columnas solicitadas, búsquedas por nombre/pedido, cuatro
  filtros y orden por fecha inicial. Carga anticipada plant.client evita N+1 en
  esas columnas. Sin acciones de borrado ni transiciones.
- Policy deniega catálogo administrativo al Supervisor incluso con service.view,
  y comprueba roles/permisos en navegación, rutas y acciones. Sin autorización
  operativa por asignaciones; integración definitiva pendiente de MV-027/MV-028.

### Archivos

Creados bajo `app/Filament/Resources/MaintenanceServices/`:

- MaintenanceServiceResource.php.
- Pages/ListMaintenanceServices.php, CreateMaintenanceService.php y EditMaintenanceService.php.
- Schemas/MaintenanceServiceForm.php.
- Tables/MaintenanceServicesTable.php.

También creados: app/Policies/MaintenanceServicePolicy.php y
tests/Feature/MaintenanceServiceResourceTest.php.
Modificados: app/ReportFrequency.php, app/MaintenanceServiceStatus.php (HasLabel),
docs/modelo-datos.md, docs/decisiones-tecnicas.md y este archivo.

### Pruebas y resultados

- Pruebas enfocadas finales: **22 pruebas, 193 aserciones, todas aprobadas**.
- Suite completa: **164 pruebas, 978 aserciones, todas aprobadas**; sin errores
  ni advertencias reportados por el runner.
- Cobertura: ambos roles administrativos, Supervisor con permiso view denegado,
  visitante redirigido, rutas directas, Livewire y revocación de rol antes de
  guardar; campos requeridos/longitudes/fechas, opciones activas y dependientes,
  pertenencia a Cliente, desactivación posterior a abrir formulario, duplicados,
  valores del enum, pedido opcional/fecha de un día, edición histórica,
  manipulación de Planta/estado, filtros/búsquedas y ausencia de borrados.
- Primera corrida enfocada: tres expectativas de prueba fallaron; se corrigieron
  para observar navegación realmente registrada (getNavigationItems construye
  elementos antes de filtrar autorización), enum devuelto por pluck Eloquent y
  comparación de fecha con whereDate en SQLite. No se relajó la autorización ni
  la validación para corregir esas expectativas.
- Pruebas ejecutadas exclusivamente mediante DatabaseTestCase en SQLite :memory:.
  La barrera comprueba configuración efectiva y PDO/PRAGMA antes de migrar.
  RolePermissionSeeder se ejecutó solo dentro de esa base aislada de pruebas.
- Pint --dirty --format agent aplicado; sintaxis `php -l` correcta en los diez
  archivos PHP afectados; git diff --check sin errores. Diff y alcance revisados.
- `php artisan route:list --path=maintenance-services --no-interaction` confirmó
  las tres rutas de listado/alta/edición.

Suite ejecutada tras comprobar ausencia del archivo de caché indicado:

```bash
APP_ENV=testing APP_CONFIG_CACHE=/tmp/kpgroup-mv023-no-config-cache.php DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= DB_FOREIGN_KEYS=true CACHE_STORE=array SESSION_DRIVER=array vendor/bin/phpunit --do-not-cache-result
```

No se ejecutó validación visual manual en navegador ni pruebas funcionales contra
MariaDB. La suite acredita renderizado HTTP/Livewire y comportamiento en SQLite,
no revisión visual responsiva ni garantías de concurrencia MariaDB. Se mantienen
los límites de eventos/SQL directo documentados en MV-022.

### Estado y pendientes

**MV-023 implementado y verificado en SQLite aislada; revisión visual manual
pendiente.** MV-024 no iniciado. Sin transiciones, asignaciones, Periodos, Programa,
recordatorios, actividades, inventario, fotos o reportes. Cambios de fechas y
frecuencia requerirán reglas adicionales cuando existan Periodos operativos.
Quincenas de 15 días naturales desde start_date permanecen para MV-029/MV-030;
none conserva la futura obligación de Periodo vigente. MV-018 sigue pendiente de
autorización corporativa para GCS.

Sin escrituras, migraciones ni seeders en MariaDB; sin cambios a credenciales,
configuración cloud, permisos, roles o cronograma. No hubo commit ni push.
Commit propuesto: `feat: administrar servicios de mantenimiento MV-023`.

## MV-024 — transiciones de Servicio verificadas — 2026-11-06

Solicitud autorizada: «KP GROUP — SEMANA 03 — MV-024 — Validar transición de
estados de Servicio». Se conservaron los cambios pendientes de MV-022/MV-023.
Boost y documentación Laravel 13/Filament 5 se consultaron por lectura; no se
ejecutaron migraciones, seeders ni escrituras en MariaDB.

### Cambios

- `MaintenanceService::canTransitionTo()` y `transitionTo()` centralizan la
  matriz. `transitionTo()` usa transacción y `lockForUpdate()`, reconsulta el
  estado actual, rechaza destinos inválidos y devuelve `false` para el mismo
  estado sin actualizar timestamps.
- `MaintenanceServiceResource::statusActions()` crea las acciones reutilizadas
  por listado y edición: Iniciar (`planned → active`), Finalizar (`active →
  completed`) y Cancelar (`active → cancelled`). Exigen confirmación, Policy
  `update`, validación de dominio y notificación en español.
- No se habilita `planned → cancelled`, ninguna reapertura, transición automática,
  modificación de fechas, generación de Periodos o coordinación con módulos aún
  inexistentes. No se añadieron columnas, estados, permisos ni cambios al seeder.

### Archivos modificados

- `app/Models/MaintenanceService.php`.
- `app/Filament/Resources/MaintenanceServices/MaintenanceServiceResource.php`.
- `app/Filament/Resources/MaintenanceServices/Tables/MaintenanceServicesTable.php`.
- `app/Filament/Resources/MaintenanceServices/Pages/EditMaintenanceService.php`.
- `tests/Feature/Models/MaintenanceServiceTest.php`.
- `tests/Feature/MaintenanceServiceResourceTest.php`.
- Los tres documentos de datos, decisiones y estado.

### Verificación

- Pruebas enfocadas MV-022/MV-023/MV-024: **67 pruebas, 306 aserciones, todas
  aprobadas**.
- Suite completa en SQLite `:memory:` mediante `DatabaseTestCase`: **180 pruebas,
  1,019 aserciones, todas aprobadas**.
- Cobertura nueva: matriz válida e inválida, cancelación desde `planned`, rechazo
  de reaperturas, mismo estado sin timestamp, estados obsoletos/repetidos,
  conservación de Planta/fechas/pedido/frecuencia, registro conservado,
  acciones visibles por estado, notificaciones, autorización Administrador/Jefe,
  denegación Supervisor y regresiones del Resource.
- `vendor/bin/pint --dirty --format agent` pasó; sintaxis PHP y
  `git diff --check` se validaron. SQLite no acredita bloqueo concurrente real de
  MariaDB ni protección de SQL directo.

Estado: **MV-024 implementado y verificado en SQLite aislada**. No se inició
MV-025. Pendientes: decisión sobre `planned → cancelled`, reaperturas, efectos
sobre asignaciones/Periodos y revisión visual manual del panel.

Commit propuesto: `feat: validar transiciones de estados de servicios MV-024`.
Sin commit ni push.

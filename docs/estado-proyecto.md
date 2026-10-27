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

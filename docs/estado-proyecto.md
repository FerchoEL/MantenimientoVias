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

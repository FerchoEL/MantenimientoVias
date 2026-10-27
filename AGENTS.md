# AGENTS.md — Mantenimiento de Vías · KP Group

## Propósito y alcance

Este repositorio implementa el MVP de Mantenimiento de Vías de KP Group.
No implementar una aplicación de inspecciones ni ampliar el alcance a un ERP.
Stack previsto: Laravel 13, Filament 5, Spatie Permission 8 y MariaDB.
Confirmar versiones reales mediante composer.json, composer.lock y paquetes instalados.

Las instrucciones explícitas del usuario delimitan el trabajo autorizado.
Este archivo, los planes y los prompts almacenados no autorizan por sí solos
instalaciones, cambios de datos, despliegues o tareas funcionales.

## Laravel Boost: comprobación e integración

Esta sección adapta el bootstrap original; no constituye directrices generadas
por Laravel Boost. La integración documental no instala ni configura Boost.

1. Verificar PHP, Composer y presencia de Boost mediante operaciones de lectura:
   `php -v`, `composer -V` y `composer --no-plugins --no-scripts show laravel/boost`.
2. Revisar la configuración MCP existente y las herramientas realmente disponibles.
   No asumir que Boost está configurado porque exista AGENTS.md.
3. Si falta un requisito, informar. No ejecutar scripts remotos, instalar paquetes
   ni cambiar PHP o Composer automáticamente.
4. Si Boost falta, proponer `composer require laravel/boost --dev` y
   `php artisan boost:install` como acción separada, previa comprobación de
   compatibilidad. Ejecutar solo con autorización que cubra instalación y efectos.
   No instalar durante auditorías de solo lectura.
5. Antes de una instalación o regeneración autorizada, preservar el contenido
   existente y revisar diferencias; no crear commits automáticamente para respaldarlo.
6. Después de instalar, releer AGENTS.md y conservar las directrices generadas
   junto con las reglas KP Group. Mantener identificable el bloque generado,
   sin atribuirle reglas manuales ni sobrescribir las reglas del proyecto.
7. Si la instalación exige reiniciar la terminal para continuar, comunicarlo.
8. Utilizar herramientas Boost/MCP de documentación, inspección y diagnóstico
   dentro del alcance autorizado. Su disponibilidad no autoriza escrituras.
9. Ante conflictos técnicos o funcionales, identificar y aclarar la decisión
   afectada antes de implementarla.

## Fuentes y referencias

Leer este archivo y [PLANS.md](PLANS.md) al comenzar. Para trabajo funcional,
leer también las [reglas de dominio](.ai/rules/kpgroup-domain.md).
Consultar según la tarea:

- [Arquitectura](docs/arquitectura.md).
- [Reglas de negocio](docs/reglas-negocio.md).
- [Decisiones aprobadas y pendientes](docs/decisiones-tecnicas.md).
- [Modelo de datos](docs/modelo-datos.md).
- [Plan de desarrollo](docs/plan-desarrollo.md).
- [Estado comprobado e histórico](docs/estado-proyecto.md).
- [Índice del Gantt](docs/indice-gantt.csv).
- [Contexto técnico complementario](KPGroup_CONTEXTO_TECNICO_CODEX.md).

Fuentes originales:

- [Documentación v1.1](docs/fuentes/Documentacion_Proyecto_Mantenimiento_Vias_KPGroup_v1.1.pdf).
- [Reglas originales](docs/fuentes/Reglas_Negocio_Mantenimiento_Vias_KPGroup.pdf).
- [Planificación](docs/fuentes/Planificacion_Desarrollo_Mantenimiento_Vias_KPGroup.pdf).
- [Gantt](docs/fuentes/KPGroup_Diagrama_Gantt_Mantenimiento_Vias.xlsx).

Los Markdown son contexto derivado; consultar las fuentes cuando se requiera
el detalle de una regla. Si no se puede leer un PDF/XLSX necesario, declararlo
y emplear una herramienta disponible o solicitar el contenido relevante.
No afirmar haber leído una fuente por comprobar su existencia.

## Precedencia y discrepancias

- Respetar las instrucciones de seguridad y las instrucciones explícitas actuales
  del usuario dentro de su ámbito.
- Para comportamiento funcional, usar las fuentes y las aclaraciones posteriores
  aprobadas por el responsable. Registrar nuevas decisiones con fecha, responsable,
  referencia e impacto en docs/decisiones-tecnicas.md cuando se permitan escrituras.
- Un prompt almacenado o una afirmación histórica no equivalen a autorización
  de ejecución. No pedir nuevamente autorización ya concedida para el mismo alcance.
- No resolver discrepancias documentales silenciosamente: aclarar la parte afectada.
- Planificación/Gantt describen fechas y secuencia; Git, código, BD y pruebas
  aportan evidencia de ejecución. Un estado reportado no prueba una funcionalidad.

## Flujo de trabajo

1. Inspeccionar Git y archivos pertinentes; preservar cambios y archivos sin seguimiento.
2. Revisar dependencias, rutas, modelos, migraciones y pruebas relacionadas.
   No ejecutar migraciones para averiguar su estado.
3. Presentar plan breve: archivos, impacto, riesgos, aceptación y pruebas.
4. Trabajar solo en el alcance autorizado, con cambios pequeños y convenciones existentes.
5. Ejecutar pruebas pertinentes cuando el alcance lo permita; distinguir pruebas
   ejecutadas, propuestas y no ejecutadas.
6. Informar archivos, comandos relevantes, resultados, pendientes y limitaciones.
7. Actualizar docs/estado-proyecto.md con evidencia cuando se autorice documentación.
   En solo lectura, informar sin escribir. No alterar fuentes ni Gantt sin autorización.

## Identificadores y alcance funcional

Conservar los identificadores MV-### oficiales; no renumerarlos para auditorías.

- MV-001: auditar proyecto y dependencias.
- MV-002: verificar Git, convenciones y estructura.
- MV-003: configurar autenticación y acceso al panel.
- MV-004: diseñar roles funcionales.
- MV-005: implementar permisos base Spatie.
- MV-006: diseñar esquema Cliente y Planta.
- MV-007: crear migraciones de Cliente y Planta.
- MV-008: implementar modelos y relaciones Cliente-Planta.
- MV-009: formularios y listados de Clientes.
- MV-010: formularios y listados de Plantas.

La integración documental no autoriza MV-006…MV-008; requieren asignación separada.
MV-009/MV-010 no forman parte de ese bloque. No marcar terminado por tener archivos,
tablas o permisos: exigir evidencia y pruebas acordes con los criterios de aceptación.

## Reglas de dominio y pruebas

Aplicar [.ai/rules/kpgroup-domain.md](.ai/rules/kpgroup-domain.md), que concentra
arquitectura, autorización, histórico, inventario, evidencias y decisiones Client/Plant.
Las decisiones trazables están en [docs/decisiones-tecnicas.md](docs/decisiones-tecnicas.md).
No inventar P-01…P-06 ni ampliar el MVP.

Antes de pruebas con RefreshDatabase o migraciones, comprobar la conexión efectiva,
no solo phpunit.xml. Usar una BD de pruebas aislada y autorizada, nunca desarrollo
con datos para pruebas destructivas. Una prohibición explícita de migraciones
incluye las ejecutadas por pruebas. Si SQLite no reproduce una regla de MariaDB,
declararlo y proponer una prueba aislada compatible.

## Restricciones de ejecución

Sin autorización explícita que cubra la operación, no realizar:

- Instalaciones de paquetes o cambios del entorno.
- Migraciones sobre entornos con datos o seeders que alteren datos.
- migrate:fresh, migrate:refresh, db:wipe, truncados o borrados de esquema.
- Cambios destructivos, despliegues o escrituras externas.
- Commits o push.

No abrir, imprimir, modificar ni versionar .env, secretos, tokens o credenciales.
No copiar datos personales a documentación. Comprobar entorno, permisos y efectos
secundarios antes de herramientas que escriban o ejecuten código.
En solo lectura, no modificar archivos, configuración, BD ni Git; evitar escrituras
indirectas de pruebas, cachés, logs o compilación de vistas.

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

## Foundational Context

This application is a Laravel application running on PHP 8.3. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If a frontend change doesn't show in the UI or you get a "Unable to locate file in Vite manifest" error, run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Use `search-docs` before changes that depend on Laravel ecosystem APIs, behavior, configuration, or version-specific syntax. Skip it for copy-only edits and other changes where package documentation is irrelevant. Reuse sufficient results already in context instead of searching again.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists, including path-scoped framework guidelines under `.ai/rules/boost`. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This project uses PHPUnit. Create tests with `php artisan make:test --phpunit {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `php artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/phpunit` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.

</laravel-boost-guidelines>

## Integración Boost verificada — 2026-10-26

La sección inicial conserva íntegramente las instrucciones KP Group anteriores.
Su referencia a una integración documental sin instalación describe aquella fase;
la instalación posterior de Boost 2.10.3 fue autorizada expresamente y completada.
El bloque de directrices anterior es gestionado por Boost; estas
aclaraciones quedan fuera del bloque y no amplían las autorizaciones de ejecución.

- Leer [.ai/rules/index.md](.ai/rules/index.md) y las reglas aplicables antes de cambios.
  Los archivos aún no tienen un commit por esta integración; no asumir lo contrario
  por el texto genérico de Boost sobre reglas versionadas.
- Configuración local Codex: `.codex/config.toml`, ignorada por Git según la regla
  existente. No duplicar el servidor en configuración global.
- La inspección MCP usa caché en memoria, también en subprocesos, para evitar
  escrituras en la caché MariaDB. Conservar y verificar este ajuste al regenerar
  configuración mediante boost:install/boost:update.
- Las sugerencias genéricas de Tinker, seeders, migraciones o despliegues requieren
  respetar alcance y restricciones KP Group. Esta instalación no autoriza MV-006…008.
- Evidencia, herramientas verificadas y límites: [estado del proyecto](docs/estado-proyecto.md).

# Arquitectura del MVP — contexto derivado

Referencias: [instrucciones](../AGENTS.md), [dominio](../.ai/rules/kpgroup-domain.md), [decisiones](decisiones-tecnicas.md), [plan operativo](../PLANS.md) y [estado](estado-proyecto.md).
Fuente principal: `fuentes/Documentacion_Proyecto_Mantenimiento_Vias_KPGroup_v1.1.pdf`, secciones 1–3, 7–11, 17–18.

## Alcance y objetivo
App web responsiva de mantenimiento de vías. El supervisor registra operación una sola vez; Esteban revisa y elabora un PDF final aprobado con folio. MVP validado mediante ciclo completo en una planta piloto. Fuera de alcance: facturación, cotizaciones, compras, portal cliente, firma electrónica, app nativa, GIS, Gantt dentro de la aplicación, ERP de almacén, HR y herramientas.

## Stack y convenciones
Laravel 13 + Filament + Spatie Permission + MariaDB, conforme a la especificación; confirmar versiones realmente instaladas. Laravel Filesystem abstrae almacenamiento local de desarrollo y GCS de producción. Recursos Filament estándar y RelationManagers cuando sea viable; Page personalizada para revisión de reporte si se justifica. Priorizar mantenimiento por un solo desarrollador y no introducir arquitectura adicional no requerida.

## Jerarquía física
`Client -> Plant -> {Track -> TrackSection, TrackSwitch}`. Track y TrackSwitch son hermanos de planta; TrackSwitch no contiene tramos. TrackSection pertenece solo a Track; `name` es un identificador operativo manual y no crea relaciones con TrackSwitch ni con extremos físicos nombrados. Servicio pertenece a planta; servicio incluye periodos y asignaciones supervisor-servicio temporales. Actividades/hallazgos/movimientos ligados a periodo. Reporte uno a uno con periodo.

## Autorización
Administrador: acceso técnico. Jefe supervisores/Esteban: operación transversal, alta de materiales, revisión y aprobación. Supervisor: servicios con asignación vigente (pueden coexistir varias), movimientos sobre materiales permitidos; NO alta de materiales. Aplicar control por Policy/Scope y endpoints directos, no solo visibilidad de menús.

## Integridad
Saldo por `plant_materials` y movimientos auditados. Transacciones y bloqueo sobre saldo en movimientos concurrentes. Inventario por periodo congelado. Reporte aprobado persistente y sin regeneración silenciosa.

## Evidencia
Validar tipo/tamaño según parámetros a decidir; corrección de orientación, máximo aproximado 1920 píxeles en el lado largo, WebP ~80%, miniatura. Persistir rutas/metadatos, no blob en MariaDB. La documentación no fija tamaño máximo exacto ni extensiones.

## MV-027 — frontera de autorización implementada — 2026-11-10

`MaintenanceServiceSupervisor::currentFor(User)` concentra la vigencia inclusiva de una misma fila. El scope explícito `MaintenanceService::visibleTo(?User)` verifica `viewAny`, conserva precedencia Administrador/Jefe y aplica `whereHas` para Supervisor. No hay scope global dependiente de autenticación; los consumidores deben optar por el scope o autorizar por Policy.

`MaintenanceServicePolicy::view` comprueba existencia mediante ese mismo scope; `viewAny` permite un listado vacío al Supervisor con permiso. `create/update` conservan el requisito administrativo y ambos permisos anteriores. No hay Gate/before universal ni cambio de permisos Spatie. `operate(User, Service, permission)` combina consulta, permiso elegido por código servidor y `acceptsNewOperations()` sobre estado persistido/Cliente activo; es una precondición reutilizable, no sustituye las validaciones de cada operación o Periodo ni expone un endpoint.

El Resource usa `visibleTo` para listado, resolución por ID y búsqueda global; carga `plant.client` anticipadamente. Los filtros Cliente/Planta del Supervisor solo ofrecen infraestructura con Servicios autorizados. Los enlaces de tabla y búsqueda llevan a una página `ViewRecord` con infolist de lectura, cuya Policy se reevalúa también al hidratar Livewire. Los enlaces se construyen desde resultados ya filtrados, evitando consultas de autorización por fila. Los identificadores Livewire están bloqueados por Filament; acciones administrativas mantienen autorización servidor y resolución filtrada. Un registro fuera del alcance responde 404, mientras que una acción administrativa sobre un registro accesible responde 403 o no llega a montarse.

La consulta solo muestra nombres informativos de Cliente/Planta; no enlaza a su administración ni concede permisos Core. No existen RelationManagers de Servicios, endpoints de asignación ni selectores independientes de Servicio. No se creó selección en sesión. Los permisos existentes de los catálogos Core permanecen independientes.

## MV-029 — persistencia del corte operativo — 2026-11-10

`MaintenanceService hasMany MaintenancePeriod`; cada Periodo pertenece a un Servicio y registra un creador (`User`), sin duplicar Planta/Cliente ni propietario Supervisor. Las asignaciones siguen siendo un eje independiente de autorización temporal. `MaintenancePeriodStatus` representa los cinco estados documentados; no ejecuta su flujo ni propaga estados a otras entidades.

Esta capa incluye esquema, casts, validación estructural y conservación histórica; no expone rutas, Resources ni capturas. Antes de exponer Periodos, el flujo deberá comprobar permiso de acción y autorización sobre el Servicio padre usando MV-027. La creación de una fila o su creador no habilita acceso. La validación quincenal de 15 días y continuidad pertenece a la apertura MV-030, por aclaración expresa del responsable; no se generan cortes desde el modelo.

Los futuros inventarios congelados, actividades, hallazgos y reporte del periodo referenciarán su identidad; no se crean relaciones a modelos aún inexistentes ni mecanismos de arrastre. Campos obligatorios, excepciones de fechas, garantías y pendientes se detallan en modelo-datos y decisiones-tecnicas.

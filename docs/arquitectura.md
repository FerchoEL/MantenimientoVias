# Arquitectura del MVP — contexto derivado

Referencias: [instrucciones](../AGENTS.md), [dominio](../.ai/rules/kpgroup-domain.md), [decisiones](decisiones-tecnicas.md), [plan operativo](../PLANS.md) y [estado](estado-proyecto.md).
Fuente principal: `fuentes/Documentacion_Proyecto_Mantenimiento_Vias_KPGroup_v1.1.pdf`, secciones 1–3, 7–11, 17–18.

## Alcance y objetivo
App web responsiva de mantenimiento de vías. El supervisor registra operación una sola vez; Esteban revisa y elabora un PDF final aprobado con folio. MVP validado mediante ciclo completo en una planta piloto. Fuera de alcance: facturación, cotizaciones, compras, portal cliente, firma electrónica, app nativa, GIS, Gantt dentro de la aplicación, ERP de almacén, HR y herramientas.

## Stack y convenciones
Laravel 13 + Filament + Spatie Permission + MariaDB, conforme a la especificación; confirmar versiones realmente instaladas. Laravel Filesystem abstrae almacenamiento local de desarrollo y GCS de producción. Recursos Filament estándar y RelationManagers cuando sea viable; Page personalizada para revisión de reporte si se justifica. Priorizar mantenimiento por un solo desarrollador y no introducir arquitectura adicional no requerida.

## Jerarquía física
`Client -> Plant -> {Track -> TrackSection, TrackSwitch}`. Track y TrackSwitch son hermanos de planta; TrackSwitch no contiene tramos. TrackSection puede referenciar `start_switch_id` y `end_switch_id` sin cambiar jerarquía. Servicio pertenece a planta; servicio incluye periodos y asignaciones supervisor-servicio temporales. Actividades/hallazgos/movimientos ligados a periodo. Reporte uno a uno con periodo.

## Autorización
Administrador: acceso técnico. Jefe supervisores/Esteban: operación transversal, alta de materiales, revisión y aprobación. Supervisor: servicios con asignación vigente (pueden coexistir varias), movimientos sobre materiales permitidos; NO alta de materiales. Aplicar control por Policy/Scope y endpoints directos, no solo visibilidad de menús.

## Integridad
Saldo por `plant_materials` y movimientos auditados. Transacciones y bloqueo sobre saldo en movimientos concurrentes. Inventario por periodo congelado. Reporte aprobado persistente y sin regeneración silenciosa.

## Evidencia
Validar tipo/tamaño según parámetros a decidir; corrección de orientación, máximo aproximado 1920 píxeles en el lado largo, WebP ~80%, miniatura. Persistir rutas/metadatos, no blob en MariaDB. La documentación no fija tamaño máximo exacto ni extensiones.

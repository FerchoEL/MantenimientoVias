# Decisiones, aclaraciones y preguntas abiertas

## Aprobadas como aclaraciones operativas posteriores (confirmadas en conversación del proyecto)
| Fecha | Tema | Decisión |
|---|---|---|
| 2026-10-26 | MV-006 Client y Plant | Ambos `short_name` opcionales (`nullable`), confirmados expresamente por el responsable |
| 2026-10-26 | MV-006 Plant | nombre único dentro del cliente, no global |
| 2026-10-26 | Desactivar cliente | conservar plantas e histórico; impedir nuevas operaciones; sin cascada |

Responsable: usuario responsable del proyecto. Referencia: autorización explícita de integración documental del 2026-10-26, puntos 4 y 5. Estatus: aprobado. Impacto futuro: ambos campos admiten NULL en migraciones, modelos, validación e interfaces; probar ambos casos. Esta integración solo registra el acuerdo, no cambia el esquema ni implementa funcionalidades.

Vínculos: [instrucciones](../AGENTS.md), [reglas de dominio](../.ai/rules/kpgroup-domain.md), [modelo](modelo-datos.md) y [plan](../PLANS.md).

## Establecidas en documentos fuente
- Core neutral y reutilizable para futura inspección, sin implementar inspección ahora.
- Track y TrackSwitch hermanos bajo Plant; TrackSection hijo de Track.
- Servicio-supervisor muchos a muchos temporal y simultáneo.
- Esteban aprueba reportes, autoriza materiales; permisos Spatie reforzados con políticas.
- Evidencias GCS en producción mediante Filesystem; DB solo metadatos.

## Pendientes fuente P-01 a P-06
| ID | Tema | Estado |
|---|---|---|
| P-01 | Subopciones de mantenimiento general | No definido |
| P-02 | Escala criticidad | No definido |
| P-03 | Límite exacto de fotos seleccionadas para PDF | No definido |
| P-04 | Formato definitivo folio | No definido; sugerencia `RMT-AAAA-######` NO aprobada |
| P-05 | Planta piloto/datos reales/croquis | No recibido |
| P-06 | Inventario inicial/materiales piloto | No recibido |

## Preguntas de implementación abiertas (NO decisiones aprobadas)
- Collation/caso/espacios y unicidad `plants.name` por cliente.
- Con `Client.active=false`, el bloqueo de nuevas operaciones y la conservación/lectura autorizada del histórico están aprobados. La creación de nuevas plantas está expresamente incluida en el bloqueo por la solicitud MV-006…MV-008. Falta clasificar por módulo las altas de servicios, periodos, actividades, hallazgos y movimientos; definir tratamiento de correcciones de registros existentes, cambios de cliente de una planta, reactivaciones y cierre/revisión/aprobación de reportes ya iniciados. No presumir excepciones ni ejecutar acciones afectadas sin resolver su clasificación. La opcionalidad de ambos `short_name` ya está resuelta.
- Límite físico de 6 evidencias por actividad mencionado en reuniones previas frente al documento que indica múltiples fotos: **verificar aprobación formal antes de imponer límite**.
- Diferencias `location_type=either/none` frente a RN-004.

Toda nueva decisión: fecha, responsable, referencia, efecto en base de datos/UI/pruebas y estatus de aprobación. No cambiar normas silenciosamente.

## MV-006…MV-008 — implementación autorizada el 2026-10-26

Responsable funcional: usuario responsable del proyecto. Referencia: solicitud
adjunta «KP Group — Implementación MV-006 a MV-008», secciones 3–6.

- Confirmado: bloquear el alta de nuevas plantas para clientes inactivos en esta
  fase. Las demás operaciones se concretarán en sus módulos.
- Campos, longitudes, nullability, active=true, timestamps, FK obligatoria con
  restricción de eliminación y UNIQUE(client_id, name) se implementan exactamente
  como solicitados. Sin columnas adicionales ni SoftDeletes.
- Decisión técnica: validación en Plant::booted(), mediante el callback creating
  de Eloquent y ValidationException en client_id. Justificación: centralizar el
  rechazo en create/save/relaciones y el futuro flujo Eloquent de Filament, sin
  crear observers, clases de eventos ni una capa de servicios. La consulta usa
  la relación como query; no confía en una instancia de Client cargada previamente.
- La FK comprueba existencia y preserva clientes con plantas; el índice comprueba
  unicidad. El estado activo del cliente se valida en aplicación, no mediante FK.
- Sin normalización adicional ni colación impuesta por estas migraciones; se
  hereda la configuración de la conexión. La equivalencia de mayúsculas, acentos
  y espacios necesita validación en MariaDB de testing antes del despliegue.
- Límites: insert/upsert/SQL directo y escrituras con eventos deshabilitados no
  ejecutan creating. Una desactivación concurrente entre comprobación e INSERT
  requiere coordinación transaccional futura; no se afirma garantía atómica.
  Los futuros escritores deben respetar este punto de entrada y revisar concurrencia.
- No se implementan reglas nuevas para edición/reasignación de plantas existentes,
  reactivación ni otras operaciones todavía pendientes. Tampoco autorización HTTP:
  conservar lectura Eloquent del histórico no equivale a una Policy implementada.

Pruebas, limitaciones del motor y comandos: [estado del proyecto](estado-proyecto.md).

## MV-003 y permisos MV-009/MV-010 — 2026-10-27

Referencia: solicitud del responsable «KP Group — Desarrollo MV-003, MV-009 y
MV-010», secciones 3 y 6; documentación v1.1, secciones 2.2 y 9.

Implementado dentro de la autorización de MV-003: User implementa FilamentUser.
canAccessPanel() permite exclusivamente el panel admin a Administrador, Jefe de
supervisores o Supervisor, con el guard del panel. La lista de roles procede de
la solicitud expresa y no depende de APP_ENV. No se inventa un permiso de panel
ni se concede administración de catálogos mediante maintenance.core.view.

### Permisos aprobados e implementados

Inspección: existen 25 permisos web, coincidentes con RolePermissionSeeder.
Administrador y Jefe tienen 25 cada uno; Supervisor tiene 15. No existen permisos
específicos de consulta/gestión de clientes o plantas. maintenance.plant-material.manage
corresponde a inventario de materiales de planta, no a administrar plantas.

Ampliación mínima aprobada, siguiendo el patrón view/manage existente:

| Permiso nuevo | Uso propuesto en Policy | Administrador | Jefe de supervisores | Supervisor |
|---|---|---|---|---|
| maintenance.client.view | ClientPolicy viewAny/view, listado e histórico | Sí | Sí | No |
| maintenance.client.manage | ClientPolicy create/update, incluido active | Sí | Sí | No |
| maintenance.plant.view | PlantPolicy viewAny/view, listado e histórico | Sí | Sí | No |
| maintenance.plant.manage | PlantPolicy create/update, incluido active | Sí | Sí | No |

- Todas las altas/ediciones requerirían acceso al Resource y su permiso de gestión;
  comprobación mediante Gate/Policies y controles de Filament en servidor, también
  para URL directa y peticiones Livewire. La navegación no será el control de acceso.
- No se proponen permisos de eliminación ni acciones de borrado; delete/deleteAny
  se denegarían. La baja se realizaría con active.
- Supervisor conserva acceso al panel, pero no recibe consulta global de estos
  catálogos. Su futura consulta por asignaciones sigue pendiente de ese módulo.
- Alternativa si se requiere separar altas de modificaciones: seis permisos,
  view/create/update por catálogo; active quedaría bajo update. No es necesaria
  para la matriz actual, que concede conjuntamente ambas operaciones.
- El seeder fue ampliado y la matriz se probó en SQLite aislada. Ejecutarlo sobre
  MariaDB de desarrollo es una escritura de datos separada y aún no realizada;
  antes de hacerlo, revisar que syncPermissions no retire permisos ajenos añadidos
  entretanto.
- Continúan pendientes las reglas para reasignar plantas y corregir/reactivar bajo
  clientes inactivos. La autorización de permisos no resuelve esas decisiones.

La autorización posterior permitió modificar el seeder y los permisos se verificaron
en aislamiento. Los Resources y Policies se implementaron según esta matriz; su
validación MariaDB y cualquier alcance por asignaciones futuras permanecen pendientes.

## Ajustes finales MV-009/MV-010 — 2026-10-27

Referencia: solicitud «KP Group — Ajustes finales MV-009 y MV-010».

- El selector de cliente de PlantResource limita las opciones activas durante la
  creación. En edición conserva como opción el cliente actual aunque esté inactivo,
  para no romper la consulta histórica.
- PlantResource muestra un aviso informativo cuando la planta pertenece a un cliente
  inactivo. El aviso no bloquea guardar ni introduce un flujo de auditoría.
- La localización de la aplicación quedó en `es`, con fallback `es` y Faker
  `es_MX`. Filament usa sus traducciones nativas; no se modificó `vendor` ni se
  instalaron paquetes.
- Se mantienen las validaciones existentes: alta y reasignación hacia clientes
  inactivos se rechazan; desactivar un cliente no altera sus plantas; no existe
  eliminación física.

## MV-011/MV-012 — vías y cambios — 2026-10-29

Referencia: solicitud adjunta «KP GROUP — SEMANA 02 — MV-011 y MV-012», secciones
2–7; documentación funcional v1.1, secciones 3, 4, 6–9. Responsable: usuario
responsable del proyecto.

- Se implementan vías y cambios como catálogos independientes, ambos vinculados
  directamente a Plant. `TrackSwitch` no pertenece a Track ni tiene tramos.
- Se aplican exactamente los campos y longitudes indicados por la solicitud. La
  aclaración aprobada el 2026-10-29 añade `UNIQUE(plant_id, name)` para Track y
  `UNIQUE(plant_id, code)` para TrackSwitch. Esta aclaración sustituye la decisión
  inicial de Semana 02 que omitía índices únicos por falta de regla definida.
- Las FKs a `plants.id` usan `RESTRICT`; así se conserva la jerarquía histórica y
  no hay borrados en cascada. Los registros se desactivan mediante `active`; no se
  agregan SoftDeletes. El futuro TrackSection deberá preservar esta integridad.
- Las altas Eloquent de Track y TrackSwitch comprueban cliente y planta activos
  con `creating`/`ValidationException`, conforme a la aclaración aprobada. Una
  desactivación no modifica el estado ni el código de los registros relacionados.
- No se editaron migraciones de clientes/plantas, roles, permisos, Gantt ni
  archivos de croquis; `sketch_disk` y `sketch_path` son metadatos únicamente.

## MV-013/MV-014 — tramos — diseño inicial sustituido 2026-11-02

Referencia: solicitud adjunta «KP GROUP — SEMANA 02», apartados 2, 4–9; secciones
3, 4, 6–9 de la documentación funcional v1.1. Responsable: usuario responsable
del proyecto. Se incorporan las decisiones expresas sobre unicidad e inactividad.

- `UNIQUE(track_id, name)` para tramos; mismo nombre en distintas vías permitido.
  El nombre es manual y se guarda exactamente como se ingresa. No se deriva ni se
  valida contra códigos de delimitadores.
- Diseño inicial: referencias opcionales de inicio/fin a TrackSwitch. Esta decisión
  queda sustituida por la aclaración funcional del responsable registrada abajo.
- Solo al crear un tramo se exige que su Cliente, Planta y Vía estén activos. Al
  desactivar cualquiera de esos catálogos no se alteran los tramos existentes ni
  se bloquean sus ediciones generales. Track y TrackSwitch también rechazan altas
  bajo cliente o planta inactivos. No se añaden Policies, permisos ni UI.
- Las migraciones de Track y TrackSwitch no aparecen en el historial MariaDB y
  sus tablas no existen allí; se actualizaron in place con los índices únicos.
  Validar colación/caso/acentos y restricciones sobre MariaDB antes de aplicar.
- Como los estados y delimitadores se verifican en eventos Eloquent, SQL directo,
  `insert`/`upsert` y escrituras silenciosas pueden omitir esas reglas. No se
  declara garantía frente a concurrencia entre desactivación y alta.

## Aclaración funcional MV-013/MV-014 — 2026-11-02

Responsable: usuario responsable del proyecto. Esta aclaración sustituye las
referencias de inicio/fin del diseño inicial. TrackSection pertenece únicamente a
Track. `name` es texto operativo manual (por ejemplo `C1-C2`, `C1-V2` o
`C4-Patio`); no se parsea ni se asocia con TrackSwitch, vías nombradas o lugares
en los extremos. Se conservan `UNIQUE(track_id, name)`, `code` nullable, `active`,
timestamps y la FK restrictiva a Track.

La migración correctiva
`2026_11_02_200932_remove_switch_references_from_track_sections_table.php`
elimina las columnas/FKs y los índices individuales de las referencias, sin
recrear la tabla. Falla antes del cambio si encuentra alguna referencia no nula.
La inspección de MariaDB por lectura confirmó cero tramos y cero referencias no
nulas; las filas y atributos restantes se preservan. Su `down()` recrea columnas
nullable vacías y FKs, pero no puede recuperar valores anteriores. No se ejecuta
rollback como parte de esta corrección.

## MV-015/MV-016 — mantenimiento de Vías y Cambios — 2026-11-02

Responsable: usuario responsable del proyecto. Se aprueban los Resources de Track
y TrackSwitch dentro de `Configuración`, con lectura/gestión exclusiva para
Administrador y Jefe de supervisores. Supervisor no recibe acceso global. Se
agregan únicamente `maintenance.track.view`, `maintenance.track.manage`,
`maintenance.track-switch.view` y `maintenance.track-switch.manage`; no se crean
permisos de borrado. Las Policies deniegan delete/deleteAny/restore/forceDelete.

Las altas requieren una Planta y Cliente activos. En edición, el selector mantiene
la Planta histórica actual; se permite guardar correcciones conservando esa
relación, pero no reasignar a otra Planta inactiva ni a una Planta cuyo Cliente
esté inactivo. Las vías se validan únicas por `(plant_id, name)` y los cambios por
`(plant_id, code)`, con mensajes locales y sus índices como garantía de base de
datos. No hay unicidad de `Track.code`. La baja es lógica y no cascada. Croquis no
se expone ni se administra todavía. TrackSwitch no se relaciona con TrackSection.

La actualización de `RolePermissionSeeder` es idempotente, pero `syncPermissions`
asigna exactamente la lista definida a Administrador y Jefe y reemplaza cualquier
permiso adicional que se les hubiera concedido fuera del seeder. La lectura de
MariaDB observó actualmente 29 permisos en cada uno y 15 en Supervisor, sin los
cuatro permisos nuevos; no ejecutar el seeder allí sin autorización de escritura.

## MV-017 — administración de Tramos desde Vía — 2026-11-02

Responsable: usuario responsable del proyecto. TrackSection se administra con
Relation Manager nativo `sections` dentro de la edición de Track. No se crea un
Resource global ni se modifica el esquema. El `track_id` lo asigna la relación
propietaria; no aparece en el formulario y el modelo rechaza cambiar el propietario
después de crear el tramo. La tabla consulta exclusivamente los tramos de esa Vía.

`TrackSectionPolicy` reutiliza `maintenance.track.view` y
`maintenance.track.manage`; no se añaden permisos. `canViewForRecord()` comprueba
de nuevo el tipo/existencia de la Vía y la autorización sobre el propietario en
la página de edición de Track. No hay acciones de borrado. Las altas siguen
bloqueadas cuando Cliente, Planta o Vía están inactivos. Registros históricos
existentes siguen visibles y editables; la edición no comprueba estados para
bloquear correcciones.

## Ajustes de interfaces MV-015/MV-017 — 2026-11-03

Responsable: usuario responsable del proyecto. Referencia: solicitud adjunta
«KP GROUP — Ajustes de interfaces MV-015 y MV-017», apartados 2–8. Se conserva
`SectionsRelationManager` y se agrega el Resource independiente `TrackSection`,
ambos con `TrackSectionPolicy` y permisos existentes `maintenance.track.view` /
`maintenance.track.manage`. Administrador y Jefe de supervisores pueden consultar
y administrar; Supervisor no accede al catálogo global. No hay eliminación física.

En el catálogo se selecciona una Vía activa bajo Planta y Cliente activos; el
selector identifica Cliente, Planta y Vía. La propietaria queda deshabilitada en
edición y el modelo continúa rechazando cualquier cambio de `track_id`. Se permite
corregir tramos históricos vinculados con infraestructura inactiva. La tabla
global ofrece búsqueda, filtros por Planta/Vía/estado y activación lógica. No se
cambia el esquema de `track_sections` ni se agrega relación con TrackSwitch.

El responsable aprobó además `UNIQUE(plant_id, code)` en `tracks`, conservando
`UNIQUE(plant_id, name)`. `code` permanece nullable: códigos no nulos pueden
repetirse entre Plantas, pero no dentro de una misma Planta; varios NULL por
Planta son válidos. El formulario valida la combinación al crear y editar, con
mensaje en español e ignorando la fila actual. No se generan ni modifican códigos
automáticamente. La migración aditiva
`2026_11_03_163105_add_plant_code_unique_to_tracks_table.php` añade o retira solo
el índice, sin recrear la tabla. La consulta MariaDB de solo lectura no encontró
grupos duplicados no nulos (`[]`); no se alteraron datos ni se aplicó la migración.

## Corrección MV-017 — códigos únicos por Vía — 2026-11-03

Responsable: usuario responsable del proyecto. Referencia: solicitud adjunta
«KP GROUP — Corrección MV-017 — Validación de códigos únicos por Vía en Tramos».
`TrackSection.code` sigue siendo opcional, pero los valores no nulos se validan y
se restringen como únicos dentro de `track_id`; otra Vía puede reutilizar el
código y una misma Vía puede tener múltiples NULL. La regla aplica en alta y
edición de `TrackSectionResource` y `SectionsRelationManager`, ignorando el
registro editado. El Relation Manager usa siempre el `track_id` del propietario.
No se cambia el modelo funcional ni se generan o modifican códigos.

Se agrega la migración incremental
`2026_11_03_180740_add_track_code_unique_to_track_sections_table.php`, con
`UNIQUE(track_id, code)`. Antes del índice, consulta códigos no nulos duplicados;
si encuentra alguno, aborta con vía, código, total e IDs para corrección manual.
`down()` elimina solo este índice. No reconstruye `track_sections` ni elimina
`UNIQUE(track_id, name)`.

Consulta de solo lectura MariaDB previa a la migración: `track_id=1`, `code=t1`,
`total=3`, IDs `1, 3, 4`. La migración no se ha aplicado y los registros no se
modificaron; aplicación pendiente de corrección manual y nueva revisión de datos.

## MV-018 — croquis y almacenamiento externo — pendiente al 2026-11-04

Responsable: empresa. Google Cloud Storage sigue sin método de facturación proporcionado o autorizado. No se elige proveedor alterno ni se implementa carga, consulta o reemplazo de croquis hasta contar con esa decisión corporativa.

## MV-019/MV-020 — validación y demostración — 2026-11-04

Responsable: usuario responsable del proyecto. La revisión integral confirmó que los catálogos de Clientes, Plantas, Vías, Cambios y Tramos se pueden demostrar sin MV-018. La demostración debe excluir croquis y nube, y usar SQLite `:memory:` o un entorno aislado autorizado, nunca seeders o datos de demostración en MariaDB de desarrollo.

La inspección MariaDB posterior halló que ya no existen grupos duplicados no nulos de `track_sections.code`. Se conserva el registro histórico de la consulta previa de tres duplicados; no se infiere cómo fueron regularizados y esta revisión no modificó datos. La migración `2026_11_03_180740_add_track_code_unique_to_track_sections_table.php` sigue sin registrarse ni aplicarse, por lo que aún falta la garantía física `UNIQUE(track_id, code)` en MariaDB. Su aplicación requiere autorización separada, confirmación del destino y revisión previa de duplicados.


## MV-021 — acuerdos y propuestas del modelo — 2026-11-04

Referencia: solicitud adjunta «KP GROUP — SEMANA 03 — MV-021», apartados 2–9;
documentación v1.1 §7 (p. 12), §8–9 y §13; reglas originales RN-001…003,
RN-026 y RC-002/003/029/032…035. Responsable funcional y de aprobación: usuario
responsable del proyecto. Autor de las propuestas técnicas: Codex. Fecha de este
registro: 2026-11-04. Autorizar el análisis no equivale a aprobar estas propuestas
ni a autorizar MV-022. Se conserva el historial anterior.

### Confirmado por las fuentes y la solicitud

- Servicio pertenece a una Planta. Nueve atributos y valores de frecuencia/estado
  contrastados en la tabla de v1.1; pedido, fin y notas opcionales.
- Asignaciones temporales múltiples y simultáneas, incluso entre distintas Plantas
  y Clientes; no exclusividad de Supervisor o Servicio por Planta (RN-001 y casos C/D).
- Periodicidad de reporte distinta de duración; `none` es sin reporte (RN-002).
  No elimina la obligación de Periodo vigente para operación (RN-003).
- RC-029 y v1.1 §13 sí describen la secuencia principal de estados. Se preserva
  esa evidencia; no se interpreta como una matriz completa ni un automatismo.
- Preservar histórico e integridad; autorización por asignación vigente también
  ante URL/ID directo. Sin cambios a roles, permisos ni módulos existentes.

Impacto futuro: relaciones 1:N, campos nullable explícitos, ausencia de
restricciones de exclusividad, conservación histórica y pruebas de acceso por
asignación. Estas reglas no están implementadas por este bloque.

### Decisiones técnicas derivadas, propuestas para revisión

- BIGINT UNSIGNED autoincremental y FK del mismo tipo que `plants.id`; FK
  `ON DELETE RESTRICT`, índice de soporte sin duplicados. Derivadas del esquema
  real y de la conservación del histórico; pendientes de implementación MV-022.
- Preferir VARCHAR(20) para ambos dominios, enums PHP respaldados por string,
  `casts()` y `Rule::enum`; fechas DATE con cast `date`. La fuente admite
  enum/string y no fija longitud: 20 es propuesta técnica, no regla de negocio.
  Evaluar CHECK de valores en MariaDB; los casts solos no protegen SQL directo.
- No agregar índices únicos sin aprobación. Evaluar `(plant_id, status)` solo
  al conocer consultas reales; no impedir servicios simultáneos.
- La fuente usa `maintenance_service_id` en tablas futuras; el `service_id` del
  resumen anterior es abreviatura conceptual. La precisión se registra en MV-021
  sin modificar ni implementar asignaciones/periodos.

Fundamento técnico consultado mediante Boost `search-docs`, Laravel 13:
[enum casts](https://github.com/laravel/docs/blob/13.x/eloquent-mutators.md#enum-casting),
[validación](https://github.com/laravel/docs/blob/13.x/validation.md) y
[migraciones](https://github.com/laravel/docs/blob/13.x/migrations.md).

### Decisiones funcionales pendientes (no son P-01…P-06)

Todas las siguientes requieren confirmación del responsable; ninguna se convierte
en restricción definitiva en MV-021. La columna de impacto indica cuándo resolver.

| Tema | Propuesta / pregunta concreta | Impacto en implementación y verificación |
|---|---|---|
| Fechas | Exigir fin >= inicio cuando exista; permitir servicio de un día y sin fin. No exigir fecha futura. | MV-022/023: validar alta y edición; probar igual, anterior, posterior y NULL; decidir CHECK de fechas. |
| Cliente/Planta inactivos | El bloqueo general de nuevas operaciones bajo Cliente inactivo ya está aprobado. Confirmar clasificación del alta de Servicio y exigir ambos activos al crear, sin cascada al desactivar. | MV-022/023: rechazo en puntos de escritura, lectura histórica y concurrencia; no asumir que FK/selector bastan. |
| Edición histórica | Definir correcciones de nombre/pedido/notas, cambios de Planta, fechas/frecuencia y reactivación del contexto. Proponer preservar propietario cuando exista operación. | MV-023/024 y módulos posteriores: no introducir bloqueo general, cascada o modificación retroactiva de cortes; concretar auditoría. |
| Duplicidad | Proponer no imponer unicidad de nombre o pedido; confirmar si se requiere distinguir duplicados accidentales dentro de una Planta. | MV-022/023: índices/validación; no confundir duplicado con simultaneidad; colación/normalización solo tras definir regla. |
| Pedido/contrato | Referencia textual opcional de 80 caracteres. Confirmar si se comparte entre Servicios, si identifica pedido o contrato indistintamente y su alcance de unicidad. | MV-022/023: sin generación, patrón, obligatoriedad por estado, vínculo ERP ni lógica comercial inferidos. |
| Estado inicial | Proponer `planned`; confirmar default y si se admite alta directamente en otro estado. | MV-022/023: default de BD y formulario coherentes; no inferirlo solo por la secuencia principal. |
| Transiciones | Conservar planned → active → completed/cancelled. Definir cancelación desde planned, reapertura, actores y efectos sobre periodos/asignaciones abiertos. | MV-024: matriz y pruebas; sin automatismos por fecha ni cierre cascada. |
| Frecuencia y `none` | Selección expresa. Confirmar anclaje semanal, quincenas calendario vs 14/15 días, meses, cortes parciales y cambios de frecuencia. Para `none`, definir periodos operativos y apertura/cierre sin reporte. | MV-029/030: RN-003 sigue vigente; no generar cortes ni un periodo perpetuo por inferencia. |
| Vigencia de asignaciones | Proponer contención dentro de fechas del Servicio; resolver extremos inclusivos, fin abierto, cambios retroactivos y finalización/cancelación. | MV-025/026: mantener solapamientos entre servicios; pruebas de bordes sin exclusividad. |
| Eliminación | Proponer denegar borrado de Servicios incluso sin dependencias; futuras FKs restrictivas protegen relaciones. | MV-022/023 y módulos posteriores: la FK a Plant por sí sola no impide borrar Servicio; no añadir SoftDeletes ni active. |
| Timestamps/auditoría | Aprobar o descartar created_at/updated_at (no enumerados en v1.1); RC-032 no define aquí campos concretos de actor. | Resolver antes de MV-022: timestamps convencionales o deshabilitarlos en Eloquent; no añadir columnas de auditoría sin definición. |

Recomendación: resolver primero fechas, inactividad, duplicidad/pedido, estado
inicial, timestamps y restricciones físicas para aprobar MV-022. Las decisiones
de transiciones, asignaciones y cortes pueden resolverse en sus actividades,
siempre antes de implementar el comportamiento afectado. No se altera el Gantt.
MV-018 continúa pendiente de autorización corporativa de Google Cloud Storage;
no bloquea el análisis de Servicios ni autoriza proveedor alterno.

## MV-024 — validación de transiciones de estado — 2026-11-06

Responsable: usuario responsable del proyecto. Referencia: solicitud adjunta
«KP GROUP — SEMANA 03 — MV-024 — Validar transición de estados de Servicio».
Estado: implementado y verificado en SQLite aislada; sin migraciones ni cambios
en MariaDB.

La matriz aprobada y ejecutada es:

- `planned → active`.
- `active → completed`.
- `active → cancelled`.
- Todo destino desde `completed` o `cancelled` queda rechazado.
- `planned → completed` y `planned → cancelled` quedan rechazados; la segunda
  permanece pendiente de decisión funcional y no se habilita.
- El mismo estado no produce escritura ni evento de cambio.

La lógica se centraliza en `MaintenanceService::canTransitionTo()` y
`MaintenanceService::transitionTo()`. Esta última utiliza una transacción y
bloqueo pesimista de la fila para reevaluar el estado persistido, evitando que
una instancia obsoleta o una solicitud repetida aplique una transición incorrecta.
No se añadió arquitectura de eventos, colas o tablas nuevas. El modelo conserva
la validación existente de atributos y fechas al guardar.

El Resource define tres acciones administrativas reutilizables en listado y
edición: iniciar, finalizar y cancelar. Sus condiciones de visibilidad no son el
control de seguridad: cada acción autoriza `update` nuevamente, y la operación de
dominio vuelve a validar la matriz. Supervisor sigue excluido por la Policy
administrativa de MV-023; no se agregan permisos ni se modifica el seeder.

No hay activación/finalización automática por fechas, modificación automática de
`end_date`, generación de Periodos, revocación de asignaciones, reapertura ni
acciones desde `planned` para cancelar. La coordinación futura con asignaciones,
acceso por vigencia, Periodos, captura, reportes y aprobaciones queda fuera de
MV-024. SQL directo, operaciones masivas o eventos deshabilitados pueden eludir
la lógica Eloquent; no se afirma protección universal.


## MV-021 / MV-029 / MV-030 — decisión aprobada de quincenas — 2026-11-04

**Estado:** aprobado. **Fecha de aprobación:** 4 de noviembre de 2026.
**Responsable:** usuario responsable del proyecto. **Referencia:** solicitud
«KP GROUP — Actualización de decisión funcional MV-021 / MV-029 / MV-030».
Esta decisión resuelve la alternativa de quincenas calendario frente a intervalos
de 14/15 días de la fila histórica «Frecuencia y `none`»; no aprueba los demás
pendientes de esa fila ni del modelo.

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

**Impacto:** MV-029/MV-030 deberán usar este anclaje y duración para delimitar los
periodos quincenales y validar continuidad, extremos inclusivos y cruces de mes
con el ejemplo aprobado. Esta decisión no exige cambios de esquema del Servicio
ni autoriza generación automática, formularios o notificaciones en este bloque.
El comportamiento de `none` sigue pendiente según el registro previo.


## MV-022 — aprobación e implementación del modelo — 2026-11-04

Responsable: usuario responsable del proyecto. Referencia: solicitud adjunta
«KP GROUP — SEMANA 03 — MV-022», apartados 2–10. Estado funcional: **MV-021
aprobado** con default planned, altas bajo Cliente/Planta activos, nombres y
pedidos repetibles, conservación sin eliminación física, end_date opcional y
mayor o igual a start_date, y timestamps convencionales. Sustituye esos pendientes
del registro inicial sin eliminar el historial. No aprueba reglas de reasignación,
reapertura ni modificación retroactiva del histórico.

Decisiones técnicas de este bloque:

- Validación en saving y control de infraestructura en creating con
  ValidationException, siguiendo las convenciones de modelos existentes.
  Las fechas se verifican también al guardar cambios, sin implementar un flujo
  de edición ni restricciones adicionales de reasignación/estado.
- Enums PHP string y almacenamiento VARCHAR(20). Rule::enum valida atributos
  persistibles en saving; los casts no son una validación completa. Asignar un
  string inválido directamente a un cast enum puede lanzar ValueError antes
  del evento; una fecha no interpretable puede fallar durante su cast. El futuro
  formulario deberá validar entradas antes de asignarlas al modelo.
- CHECK evaluado y no añadido: Blueprint instalado no ofrece un método dedicado
  de CHECK; añadirlo después de crear tabla exigiría SQL específico y SQLite no
  ofrece el mismo ALTER TABLE ADD CONSTRAINT que MariaDB. No introducir DDL no
  verificado en MariaDB ni reconstrucción de tabla para estas pruebas. Se
  mantienen validación Laravel, NOT NULL, FK restrictiva y default; no se afirma
  garantía física de fechas, dominios ni longitudes en SQLite.
- Borrado de instancias denegado en deleting. SQL directo, borrado masivo y
  operaciones sin eventos pueden eludir esta protección; no se añaden triggers,
  permisos de BD ni Policies operativas. Los futuros puntos de escritura deberán
  respetar esta frontera. FK a Planta protege al padre, no al Servicio.
- Lectura histórica sin scope global de activos; desactivar infraestructura no
  altera Servicios. Comprobación de activos e INSERT no son atómicos; concurrencia
  pendiente para escritores futuros. No se modifican otros modelos salvo relación
  inversa en Plant, ni migraciones previas.

Impacto futuro confirmado: planned es independiente del Programa; pedido/contrato
no otorga acceso. Responsable autorizado configura periodicidad al crear Servicio.
Supervisores operarán solo sobre asignaciones vigentes, aunque se superpongan entre
Plantas; acceso directo si tienen una, elección si tienen varias y control por ID
(MV-025…028). Sin implementación de esos flujos aquí.
Quincenas de 15 días naturales desde start_date se implementarán en MV-029/MV-030;
último periodo parcial y cortes semanales/mensuales/cambios siguen pendientes.
Recordatorios por correo se registran como requisito futuro dependiente de
Periodos y Programa de trabajo, no como funcionalidad aprobada para ejecutar ahora.
MV-018 conserva el pendiente corporativo de GCS y no bloquea MV-022.


## MV-023 — interfaz y autorización administrativa — 2026-11-04

Responsable: usuario responsable del proyecto. Referencia: solicitud adjunta
«KP GROUP — SEMANA 03 — MV-023», apartados 2–10. Estado: decisiones aprobadas
implementadas en el Resource, sin ampliar módulos ni modificar esquema.

- Planta fija en edición durante MV-023. Cliente/Planta son informativos; el
  servidor descarta plant_id, client_id y status del payload de edición y solo
  admite los seis campos administrativos aprobados. Un intento de reasignar
  mediante campos manipulados no cambia propietario; no se rechazan por ello
  otras correcciones válidas. La reasignación permanece pendiente de decisión
  independiente. No se añade esa restricción al modelo para otros escritores.
- Estado informativo: creación fuerza planned y edición conserva el estado
  persistido. No existe un campo editable status ni acciones de transición.
- Cliente auxiliar no se deshidrata ni persiste. Se validan existencia/actividad
  y pertenencia de Planta al Cliente en servidor. La validación Eloquent MV-022
  permanece intacta. Infraestructura inactiva impide alta, pero no lectura ni
  corrección administrativa de Servicios existentes.
- Se reutilizan enums mediante HasLabel nativo de Filament, con todas las
  etiquetas españolas aprobadas, sin nuevos valores de frecuencia/estado.

### Permisos inspeccionados y restricción provisional

Boost confirmó permisos web `maintenance.service.view` y
`maintenance.service.manage`: Administrador y Jefe de supervisores tienen ambos;
Supervisor tiene view, coincidiendo con RolePermissionSeeder. El permiso view
por sí solo no distingue consulta operativa de acceso administrativo global.

Se reutilizan los permisos existentes con una condición administrativa temporal
explícita: viewAny/view requieren rol Administrador o Jefe (guard web) y
maintenance.service.view; create/update requieren además maintenance.service.manage.
La Policy aplica a navegación, rutas directas y operaciones Livewire; delete,
deleteAny, restore/restoreAny y forceDelete/forceDeleteAny devuelven false.
No se modificaron permisos, roles ni seeder. La condición adicional por rol aplica
la alternativa expresamente autorizada por la solicitud para evitar conceder el
catálogo global al Supervisor. No se usó ocultamiento de menú como único control.

MV-027/MV-028 deberá integrar el alcance operativo definitivo por asignación
vigente; esta Policy administrativa no lo implementa. Pedido/contrato no otorga
acceso. Las pruebas cubren retirada de rol después de abrir formularios.

### Límites y dependencias

Edición de fechas/frecuencia permitida en este bloque sin alterar relacionados;
definir efectos antes de operar con Periodos existentes. No se resuelven cierres
parciales, cortes semanales/mensuales ni cambios de periodicidad sobre operación.
planned es independiente del Programa; biweekly conserva la regla de 15 días
naturales inclusivos desde start_date para MV-029/MV-030. none no elimina Periodos
operativos. Sin transiciones MV-024, asignaciones MV-025/026, recordatorios,
programas, actividades, inventario, fotografías ni reportes.
MV-018 continúa pendiente de autorización corporativa de Google Cloud Storage.
## MV-025 — diseño de asignaciones Supervisor–Servicio — 2026-11-06

Responsable del análisis: Codex. Responsable de aprobación funcional: usuario responsable del proyecto. Referencia: solicitud «KP GROUP — SEMANA 03 — MV-025». Estado: diseño documental; no se autoriza todavía MV-026 ni se crean tablas, modelos o permisos.

### Reglas confirmadas

- RN-001 permite varias asignaciones vigentes de un Supervisor a Servicios distintos, incluso en Plantas o Clientes distintos, con fechas superpuestas. No existe exclusividad global.
- RC-002 define asignaciones con inicio, fin opcional y `active`; no vincula permanentemente al Supervisor con la Planta.
- RC-003/RC-035 exige acceso por asignación vigente, autenticación, roles/permisos y protección ante URL o identificador directo. Administrador y Jefe consultan transversalmente; Supervisor opera solo sobre asignaciones vigentes.
- RC-029 conserva los estados de Servicio de MV-024. Finalizar o cancelar no implica borrar historia ni cerrar asignaciones automáticamente.
- RC-032 exige trazabilidad donde sea relevante y RC-034 protege registros históricos relacionados.

### Propuestas técnicas

- Tabla `maintenance_service_supervisors` con `id`, `maintenance_service_id`, `user_id`, `start_date`, `end_date`, `active`, timestamps; FKs obligatorias a `maintenance_services` y `users`, ambas `ON DELETE RESTRICT`. No `supervisor_id` en Servicios, FK a Plant desde la asignación ni cascada histórica.
- Modelo intermedio Eloquent completo favorecido por PK propia, `active`, fechas y auditoría; relaciones `belongsTo` a Servicio/User y `hasMany` inversas. Una navegación N:M con `using()` podrá añadirse si facilita consultas.
- Validación de `end_date >= start_date`, intervalos inclusivos y fecha de consulta explícita. Índices compuestos para usuario/estado/vigencia y servicio/estado/vigencia; no UNIQUE temporal simple.
- Para el mismo par Servicio–Supervisor, detectar solapamientos en aplicación y exigir justificación o rechazar el intervalo según regla aún no aprobada. Sí se permiten solapamientos entre Servicios distintos e intervalos históricos consecutivos.
- Validar en alta que `user_id` tenga rol Supervisor y que el Servicio exista. La continuidad del rol, desactivación de User y correcciones históricas deben definirse; retirar un rol no borra una asignación histórica.
- Acceso futuro requiere User autenticado, rol Supervisor, asignación activa y vigente, Servicio `active` y Periodo vigente cuando aplique. Es propuesta para MV-027/028, no Policy implementada ahora.

### Decisiones pendientes antes de MV-026

1. Rechazar todo solapamiento del mismo par o admitir intervalos simultáneos con motivo/auditoría.
2. Precedencia entre `active=false` y establecer `end_date`; conservación de fechas al desactivar.
3. Contener asignaciones entre `MaintenanceService.start_date/end_date` o permitir asignaciones preparatorias/posteriores.
4. Permitir altas para Servicios `planned`, `completed` o `cancelled` y límites de corrección histórica.
5. Auditoría adicional de creador, modificador, motivo y cambios de fechas.
6. Tratamiento de un User que pierde rol Supervisor, se desactiva o sale de la organización.
7. Respuesta de interfaz para Supervisor autenticado sin asignaciones.
8. Confirmar si captura requiere siempre Periodo vigente también con `report_frequency=none`; RN-003 sugiere que sí.

La propuesta para MV-026 es implementar primero modelo intermedio, FKs restrictivas, fechas inclusivas, índices y validación de rol/estado; después altas, historial, desactivación y corrección con autorización servidor. No avanzar hasta resolver estas preguntas. MV-018 sigue pendiente de GCS y no bloquea este diseño.

## MV-026 — implementación de asignaciones Supervisor–Servicio — 2026-11-06

Responsable de implementación: Codex. Aprobación funcional: usuario responsable del proyecto. Referencia: solicitud «KP GROUP — SEMANA 03 — MV-026 — Implementar asignaciones Supervisor–Servicio con vigencia».

Se implementó `maintenance_service_supervisors` con FKs obligatorias y restrictivas, fechas inclusivas, `active=true` por defecto y timestamps. El modelo intermedio tiene fillable explícito, casts, relaciones tipadas y validación de existencia, fechas y rol `Supervisor` en guard `web`. No se concede el rol, no se imponen estados del Servicio, no se contiene la asignación a las fechas del Servicio y no se añadió UNIQUE temporal.

Se permiten solapamientos entre Servicios distintos y registros repetidos o superpuestos del mismo par, conforme a la decisión pendiente de MV-025. `active=false` conserva las fechas y el registro. La eliminación Eloquent se bloquea para conservar histórico; las FKs protegen además borrados directos. No se implementaron roles, permisos, Policies, scopes, Resources, notificaciones, generación de periodos ni cambios de estado.

La validación basada en eventos Eloquent puede omitirse mediante SQL directo o actualizaciones masivas. SQLite aislada verifica relaciones y reglas de dominio, pero no sustituye una comprobación MariaDB de índices, concurrencia o diferencias del motor.

### Verificación

- Prueba focalizada: **12 pruebas, 27 aserciones, todas aprobadas** en SQLite `:memory:` con FKs activas.
- Se cubrieron relaciones, casts, factory, múltiples Servicios/Plantas, solapamientos permitidos, historial repetido, límites inclusivos, fin abierto, desactivación, FKs inexistentes, rol no Supervisor, fechas inválidas, restricciones de borrado y conservación del estado del Servicio.
- No se ejecutaron migraciones, seeders ni escrituras contra MariaDB.

Se mantienen pendientes: contención con fechas del Servicio, reglas para estados `planned/completed/cancelled`, solapamientos del mismo par con motivo o rechazo, auditoría adicional, pérdida posterior del rol y precedencia entre `active` y `end_date`. MV-027/MV-028 implementará autorización e interfaz; MV-029/MV-030 implementará Periodos.

## MV-027 — autorización implementada por asignación vigente — 2026-11-10

Responsable de aprobación: usuario responsable del proyecto. Implementación: Codex. Referencia: solicitud adjunta «KP GROUP — MV-027 — Servicios y programa — Aplicar autorización por servicio vigente», que corrige expresamente el alcance anterior de solo diseño; documentación funcional v1.1 §§2, 4/RN-001, 9.1, 13 y AC-01; decisiones MV-024…026. Impacto: modelos, Policy, Resource de Servicios, pruebas y documentación existente; sin cambio de esquema ni permisos.

Decisiones confirmadas en esta solicitud:

- Consulta del Supervisor = rol `Supervisor` web + `maintenance.service.view` + una misma fila habilitada e inclusivamente vigente para usuario/Servicio. Sin excepción histórica al vencer. Administrador/Jefe conservan consulta global y precedencia en usuarios multirrol, con permisos; no se usa `before()`.
- La fecha proviene del reloj servidor en la zona configurada (UTC); no se modifica la zona ni se admite fecha de autorización del cliente. `active=false` niega alcance aunque las fechas coincidan. Perder rol o permiso también lo niega, preservando la fila.
- Consulta permitida en los cuatro estados dentro del alcance. Nuevas operaciones requieren `active`, permiso específico y condiciones propias; Cliente inactivo las bloquea conforme a la regla ya aprobada. No se introducen límites por fechas del Servicio. Ningún estado/asignación cambia automáticamente.
- La administración de Servicios conserva MV-023/MV-024: crear/editar/transicionar solo Administrador/Jefe con view/manage; Supervisor denegado aunque tenga manage. Eliminaciones/restauraciones continúan denegadas.

Decisiones técnicas: scopes locales `currentFor` y `visibleTo`, Policy compartiendo consulta, `EXISTS` para no duplicar, carga anticipada de Planta/Cliente, vista mínima `ViewRecord`, filtros relacionados limitados y URLs resueltas por el mismo Resource. Las rehidrataciones Filament 5.10.3 vuelven a resolver el registro y comprobar acceso. No se confía en estado de sesión, atributos cargados de asignaciones ni identidad enviada por navegador. La precondición `operate` no autoriza un módulo futuro por sí sola: el código servidor fija el permiso y debe comprobar Periodo y reglas particulares.

Pendientes preservados: excepción histórica tras vencimiento, contención de asignaciones por fechas del Servicio, motivos/restricciones de solapamiento del mismo par, auditoría y corrección histórica, reglas de Periodos y condiciones particulares de capturas futuras. No bloquean esta implementación acotada. La gestión de asignaciones y MV-028 no se iniciaron.

MariaDB: lectura mediante Boost del registro de migraciones confirmó `2026_11_06_203239_create_maintenance_service_supervisors_table`, lote **9**, y Servicios en lote **8**. Esto acredita su registro como aplicada; no es una validación de datos reales ni una prueba funcional MariaDB. No se ejecutaron migraciones, seeders ni escrituras en esa conexión. MV-027 no requiere migración ni actualización de permisos.

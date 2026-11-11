# Modelo de datos — guía conceptual, verificar migraciones antes de generar

Referencias: [instrucciones](../AGENTS.md), [dominio](../.ai/rules/kpgroup-domain.md), [decisiones](decisiones-tecnicas.md), [plan operativo](../PLANS.md) y [estado](estado-proyecto.md).
Fuente: `fuentes/Documentacion_Proyecto_Mantenimiento_Vias_KPGroup_v1.1.pdf`, sección 7 (págs. 11–16) y 8 (pág. 18). Los tipos son **conceptuales**.

| Tabla | Atributos clave | Relaciones / restricciones |
|---|---|---|
| `clients` | id, name varchar(150), short_name varchar(80) nullable, active, timestamps | `hasMany(Plant)`; nombre corto opcional según aclaración aprobada posterior |
| `plants` | id, client_id, name varchar(150), short_name varchar(80) nullable, active, observations nullable | `belongsTo(Client)`; nombre único **dentro del cliente** según aclaración posterior |
| `tracks` | id, plant_id, name varchar(120), code varchar(50)?, sketch_disk varchar(40)?, sketch_path varchar(255)?, active, timestamps | `Plant hasMany Track`; `Track belongsTo Plant`; unique `(plant_id, name)` y `(plant_id, code)` (code nullable); `hasMany(TrackSection)` |
| `track_switches` | id, plant_id, code varchar(50), name varchar(120), description?, active, timestamps | `Plant hasMany TrackSwitch`; `TrackSwitch belongsTo Plant`; unique `(plant_id, code)`; no tiene hijos ni relación con Track |
| `track_sections` | id, track_id, name varchar(120), code varchar(50)?, active, timestamps | unique `(track_id, name)` y `(track_id, code)` (code nullable); solo `belongsTo(Track)`; nombre operativo manual |
| `maintenance_services` | id, plant_id, name, order_number?, start_date, end_date?, report_frequency, status, notes? | planta 1:N servicios |
| `maintenance_service_supervisors` | service_id, user_id, start_date, end_date?, active | asignaciones temporales N:M; no exclusividad |
| `maintenance_periods` | service_id, fechas, status, creador, envío, aprobación, cierre | servicio 1:N periodos |
| `maintenance_activity_categories/types` | categoría, concepto, subopción, unidad, location_type, inventory_behavior | conceptos jerarquizados |
| `maintenance_programs/items` | period_id, concepto, ubicación, fecha, asignado?, estado | programa por periodo |
| `maintenance_activities/photos` | period_id, concepto, ubicación, supervisor, fecha, estado, evidencia | fotos fuera de BD |
| `materials`, `plant_materials`, `inventory_movements` | producto; saldo por planta/material/condición; movimientos auditados | unique planta/material/condición; transacciones |
| `maintenance_period_inventories` | opening, entries, exits, adjustments, closing | resumen congelado |
| `maintenance_findings/photos` | fecha detección, criticidad, ubicación, resolución, evidencias | histórico inmutable |
| `maintenance_reports` + pivotes/revisiones | periodo único, folio único, selección, auditoría, pdf | PDF oficial después aprobación |

## MV-006 → MV-008: acuerdos posteriores confirmados
1. `clients.short_name` y `plants.short_name` son opcionales (nullable), confirmados por el responsable el 2026-10-26; ver [decisiones técnicas](decisiones-tecnicas.md).
2. Los nombres de planta son únicos dentro de un mismo cliente; distintas empresas pueden tener plantas con el mismo nombre. Definir explícitamente si comparación ignora mayúsculas/espacios de acuerdo con collation MariaDB real y documentar la decisión.
3. Desactivar un cliente conserva plantas y todos los registros históricos; bloquear nuevas operaciones bajo cliente inactivo sin desactivar plantas en cascada.
4. Mantener desactivación lógica (`active`) y relaciones históricas; no `ON DELETE CASCADE` para borrar histórico.
5. La lectura histórica autorizada se conserva. La creación de nuevas plantas para clientes inactivos queda bloqueada en esta fase por decisión expresa. Clasificar las demás operaciones por módulo y resolver correcciones, cambios de cliente, reactivaciones y cierres en [decisiones técnicas](decisiones-tecnicas.md) antes de implementar la acción afectada.

## Pruebas del bloque
- Cliente y planta con short_name nulo; planta vinculada a cliente.
- Unicidad planta dentro de cliente y duplicidad entre clientes permitida.
- Cliente inactivo no elimina ni desactiva plantas; impedir nuevas operaciones conforme regla aprobada.
- Integridad de foreign keys, manejo de cambios de cliente y autorización de recursos.

## MV-006 — correspondencia del diseño implementado

Fuente original contrastada: documentación v1.1, sección 7 (clientes/plantas,
página 11); secciones 3 y 18 para nombres neutrales, 6 para RF-001/RF-002,
8 para Client hasMany Plant / Plant belongsTo Client, 16 para AC-12 y 17 para
integridad referencial. La sección 9 define autorización para los módulos futuros;
no se implementan permisos ni interfaces en este bloque.

El original describe tipos conceptuales y no explicita nullable en ambos nombres
cortos ni unicidad de planta por cliente; esas decisiones proceden de las
aclaraciones posteriores y de la solicitud de implementación, no del PDF.

- clients: id BIGINT UNSIGNED PK; name VARCHAR(150) NOT NULL;
  short_name VARCHAR(80) NULL; active BOOLEAN NOT NULL DEFAULT true; timestamps.
- plants: id BIGINT UNSIGNED PK; client_id BIGINT UNSIGNED NOT NULL;
  name VARCHAR(150) NOT NULL; short_name VARCHAR(80) NULL;
  active BOOLEAN NOT NULL DEFAULT true; observations TEXT NULL; timestamps.
- FK plants.client_id → clients.id con ON DELETE RESTRICT;
  índice único plants(client_id, name). Sin índices redundantes ni columnas extra.
- Modelos App\Models\Client y App\Models\Plant: Fillable explícito siguiendo User,
  casts() boolean para active, HasFactory, HasMany/BelongsTo tipados.
- Defaults aplicados por la BD; recargar un modelo recién creado si se necesita
  observar un valor omitido que procede del default de la tabla.
- Validación de cliente inactivo centralizada en creating de Plant; justificación
  y límites documentados en decisiones-tecnicas.md. No se filtra globalmente el
  histórico ni se alteran plantas cuando cambia Client.active.

La estructura generada no acredita ejecución en MariaDB: ver el estado MV-007
parcial y las validaciones SQLite/MariaDB en [estado](estado-proyecto.md).

## MV-011/MV-012 — vías y cambios (29-10-2026)

Fuente contrastada: documentación funcional v1.1, secciones 3 (jerarquía), 4
(RN-005 y RN-026), 6 (RF-003 a RF-005), 7 (atributos), 8 (relaciones) y 9
(matriz de autorizaciones). Alcance implementado solo para Track y TrackSwitch;
TrackSection, interfaces y permisos específicos quedan fuera de este bloque.

- `tracks`: `id` BIGINT UNSIGNED PK; `plant_id` BIGINT UNSIGNED NOT NULL con FK
  a `plants.id` y `ON DELETE RESTRICT`; `name` VARCHAR(120) NOT NULL; `code`
  VARCHAR(50) NULL; `sketch_disk` VARCHAR(40) NULL; `sketch_path` VARCHAR(255)
  NULL; `active` BOOLEAN NOT NULL DEFAULT true; timestamps.
- `track_switches`: `id` BIGINT UNSIGNED PK; `plant_id` BIGINT UNSIGNED NOT NULL
  con FK a `plants.id` y `ON DELETE RESTRICT`; `code` VARCHAR(50) NOT NULL;
  `name` VARCHAR(120) NOT NULL; `description` TEXT NULL; `active` BOOLEAN NOT
  NULL DEFAULT true; timestamps.
- Las FKs a `plants.id` proporcionan sus índices; además, `tracks` tiene unique
  `(plant_id, name)` y `track_switches` unique `(plant_id, code)`, conforme a la
  aprobación funcional del 2026-10-29. No se modifica automáticamente ningún
  código. No existe `track_id` en `track_switches`.
- Modelos tipados `Track::plant()`, `TrackSwitch::plant()`, `Plant::tracks()` y
  `Plant::trackSwitches()`; factories asignan una planta por defecto. `active`
  se castea a boolean. No hay SoftDeletes ni carga de croquis.
- Ajuste aprobado el 2026-11-03: el código opcional de Track también es único
  por Planta cuando no es `NULL`, mediante una migración incremental que conserva
  el índice `(plant_id, name)`. Se permiten varios códigos `NULL` por Planta y
  códigos iguales en Plantas distintas; la validación de edición ignora la vía
  actual.

## MV-013/MV-014 — tramos (diseño inicial sustituido el 02-11-2026)

Fuente contrastada: documentación funcional v1.1, secciones 3, 4 (RN-005/RN-026),
6 (RF-003 a RF-005), 7 y 8; aclaraciones funcionales aprobadas en la solicitud
Semana 02.

- Diseño inicial del 29-10-2026: el esquema incluía referencias opcionales a
  cambios de vía. La aclaración funcional del responsable del 02-11-2026 sustituye
  esa parte: el tramo pertenece solo a Track; el nombre es un identificador manual
  que puede ser, por ejemplo, `C1-C2`, `C1-V2` o `C4-Patio`. No se interpreta ni
  genera relaciones con cambios, vías nombradas o lugares en sus extremos.
- Esquema vigente: `id` BIGINT UNSIGNED PK; `track_id` BIGINT UNSIGNED NOT NULL;
  `name` VARCHAR(120) NOT NULL; `code` VARCHAR(50) NULL; `active` BOOLEAN NOT NULL
  DEFAULT true; timestamps. Unique `(track_id, name)` y, mediante migración
  incremental, `(track_id, code)`; FK `track_id → tracks.id` con
  `ON DELETE RESTRICT`. Código opcional, único dentro de la Vía cuando se
  proporciona; se permiten múltiples NULL y reutilización en otras Vías.
- `Track::sections()` y `TrackSection::track()` son las relaciones Eloquent.
  `start_switch_id` y `end_switch_id` fueron retirados por una migración correctiva;
  no forman parte del esquema vigente.
- En creación se exige cliente, planta y vía activos. La baja de esos catálogos no
  desactiva ni elimina tramos ni bloquea sus ediciones generales.
- Administración disponible por dos interfaces con el mismo modelo y Policy:
  `SectionsRelationManager` dentro de Editar Vía y el catálogo independiente
  Tramos, donde se elige la Vía; en edición la propietaria permanece fija.
- La inactividad bloquea altas nuevas, pero no desactiva registros relacionados;
  las ediciones no reciben un bloqueo general. Sin SoftDeletes ni campos de
  kilometraje, longitud física, numeración o coordenadas.

## MV-021 — diseño de MaintenanceService — 2026-11-04

Diseño documental para revisión; no implementa tabla, modelo ni interfaces.
Fuente contrastada: documentación v1.1, sección 7, página impresa 12 (atributos),
secciones 8–9 (relaciones/acceso) y 13 (estados); reglas originales RN-001…003,
RN-026, RC-002/003/029/032…035. La solicitud «Semana 03 — MV-021» confirma el
alcance. Las propuestas pendientes se identifican en
[decisiones técnicas](decisiones-tecnicas.md#mv-021--acuerdos-y-propuestas-del-modelo--2026-11-04).

### Entidad y atributos

`MaintenanceService` representa un servicio de mantenimiento de una sola Planta,
con identidad propia, fechas, pedido/contrato opcional y periodicidad de reporte.
El Cliente se obtiene mediante Plant; no se duplica `client_id` ni se almacena un
supervisor único en el servicio. La frecuencia no representa su duración total.

| Atributo | Tipo físico propuesto | ¿NULL? | Significado / origen |
|---|---|---|---|
| `id` | BIGINT UNSIGNED, PK, autoincremental | No | Identidad; unsigned/autoincrement derivados de las convenciones existentes |
| `plant_id` | BIGINT UNSIGNED, FK a `plants.id` | No | Planta propietaria; tipo compatible con el esquema MariaDB inspeccionado |
| `name` | VARCHAR(150) | No | Nombre del servicio, fuente v1.1 |
| `order_number` | VARCHAR(80) | Sí | Referencia textual de pedido/contrato, no importe ni folio de reporte |
| `start_date` | DATE | No | Inicio del servicio |
| `end_date` | DATE | Sí | Fin; NULL significa sin fecha final definida |
| `report_frequency` | VARCHAR(20) | No | `weekly`, `biweekly`, `monthly`, `none`; fuente admite enum/string, longitud técnica propuesta |
| `status` | VARCHAR(20) | No | `planned`, `active`, `completed`, `cancelled`; fuente admite enum/string, longitud técnica propuesta |
| `notes` | TEXT | Sí | Notas opcionales |

No se establece default funcional: se propone `planned` para aprobación; la
frecuencia deberá seleccionarse expresamente. No se añaden `active`, `deleted_at`,
importes, adjuntos ni campos de auditoría. La tabla de la fuente no enumera
`created_at`/`updated_at`; aunque los modelos existentes los usan, su incorporación
queda pendiente. Si se aprueban, serían TIMESTAMP nullable según `timestamps()`;
si se mantiene exactamente esta tabla, el futuro modelo deberá deshabilitar los
timestamps automáticos. No confundir estos timestamps con auditoría de actores.

Propuesta técnica: enums PHP respaldados por string `ReportFrequency`
(`Weekly`, `Biweekly`, `Monthly`, `None`) y `MaintenanceServiceStatus`
(`Planned`, `Active`, `Completed`, `Cancelled`), con los valores persistidos de la
tabla y etiquetas españolas en la futura UI. Usar `casts()` para ambos enums y
`date` para las dos fechas; `Rule::enum` para validar entradas. Mantener relaciones
tipadas y asignación explícita siguiendo los modelos existentes. No crear archivos
ni nuevas carpetas en esta fase. VARCHAR evita acoplar la ampliación del catálogo
a un ENUM SQL, pero el cast no impide SQL inválido: un CHECK de pertenencia es una
opción técnica a decidir y probar en MariaDB durante MV-022.

### Relaciones lógicas y dependencias

```mermaid
erDiagram
    Client ||--o{ Plant : contiene
    Plant ||--o{ MaintenanceService : tiene
    MaintenanceService ||--o{ MaintenanceServiceSupervisor : asignaciones_futuras
    User ||--o{ MaintenanceServiceSupervisor : supervisor
    MaintenanceService ||--o{ MaintenancePeriod : periodos_futuros
```

- Propuestas para MV-022: `Plant::maintenanceServices(): HasMany` y
  `MaintenanceService::plant(): BelongsTo`. No se modifican ahora los modelos.
- Futuras: `MaintenanceService::supervisorAssignments(): HasMany` y
  `MaintenanceService::periods(): HasMany`; cada asignación pertenece al servicio
  y a User; cada periodo pertenece al servicio. Los nombres de clase/método son
  propuestas, no código existente. Asignaciones: MV-025/026; autorización: MV-027;
  simultaneidad: MV-028; periodos: MV-029/030.
- Precisión respecto al resumen conceptual anterior: la sección 7 de v1.1 llama
  a ambas FKs futuras **`maintenance_service_id`**, no `service_id`. Este último
  era abreviatura del resumen, no un nombre aprobado de columna. Se conserva el
  resumen histórico y se explicita aquí la correspondencia para MV-021.
- RN-001 permite varios supervisores por servicio y varias asignaciones de un
  supervisor entre servicios/plantas, con fechas superpuestas. No habrá unicidad
  de `plant_id`, supervisor o intervalos que imponga exclusividad. Las reglas de
  repetición de una asignación al mismo servicio se resolverán en MV-025.

### Integridad, validación e índices propuestos

- PK `id`; FK obligatoria `plant_id → plants.id`, con `ON DELETE RESTRICT` y sin
  cascadas de actualización. El índice de soporte de la FK basta inicialmente;
  no duplicarlo. La FK impide borrar una Planta referenciada, **no** borrar el
  Servicio: proponer denegación de eliminación en aplicación y FKs restrictivas
  desde asignaciones/periodos cuando existan. Conservar el histórico es regla
  aprobada; impedir también borrar servicios sin dependencias es propuesta.
- Validar nombre requerido y máximo 150; pedido nullable/string/máximo 80; notas
  nullable/string; planta existente; fecha inicial válida; fecha final nullable
  y, como propuesta a aprobar, `after_or_equal:start_date`. Permitir fechas iguales
  admitiría servicio de un día. No exigir inicio futuro ni fin por estado sin decisión.
- No proponer UNIQUE sobre `plant_id`, nombre, pedido o fechas mientras no exista
  regla aprobada. Servicios simultáneos no equivalen a duplicados accidentales.
  Un índice no único `(plant_id, status)` solo se justificaría con consultas reales
  del futuro listado; no añadir índices aislados de baja selectividad ni un índice
  para pedido sin evidencia de uso. No se decide normalización/collation implícita.
- El bloqueo general de nuevas operaciones bajo Cliente inactivo está aprobado.
  Su clasificación concreta para alta de servicios y el tratamiento de Planta
  inactiva deben confirmarse: se propone exigir ambos activos al crear, sin
  modificar servicios existentes al desactivar. Una FK no valida estados activos.
- Lectura histórica autorizada debe conservarse. No se infieren prohibiciones de
  corrección, reasignación de Planta, reapertura o cierre por inactividad. Definir
  estas operaciones antes de implementarlas; no trasladar automáticamente reglas
  específicas de Vías/Tramos al Servicio.
- RC-029 y v1.1 §13 describen `planned → active → completed / cancelled`.
  No se deducen automatismos por fecha, cancelación directa desde planned,
  reaperturas ni efectos en periodos/asignaciones. La matriz detallada se valida
  en MV-024. Finalizar o cancelar conserva relaciones e histórico.
- `none` significa sin reporte, no sin operación ni sin Periodos: RN-003 sigue
  exigiendo un periodo vigente para actividades, hallazgos y movimientos. Quedan
  pendientes su delimitación/apertura/cierre y la interacción con reportes; no
  crear un periodo ilimitado ni cortes automáticos por inferencia.
- La relación entre vigencia del Servicio y asignaciones requiere decidir límites
  inclusivos, asignación sin fin en servicio con fin, fechas fuera del servicio y
  cambios retroactivos. Se propone contener asignaciones dentro de la vigencia,
  preservando siempre la superposición entre servicios; pendiente para MV-025.
- RC-003/035 exige alcance por asignación vigente también en acceso por ID; esta
  dependencia no autoriza modificar permisos, roles ni Policies en MV-021.

### Casos revisados (evaluación de diseño, no pruebas ejecutadas)

| Caso | Evaluación y límites |
|---|---|
| A — semanal | `weekly` representa la frecuencia; el servicio puede durar múltiples semanas. Día de corte y primer/último periodo parcial pendientes de MV-029. |
| B — quincenal | `biweekly` válido; falta decidir quincenas de calendario frente a intervalos de 14/15 días. No codificar una interpretación todavía. |
| C — simultáneos | Dos servicios activos de la misma Planta con fechas superpuestas son representables; no hay UNIQUE de Planta ni restricción de solapamiento. Duplicidad de nombre/pedido pendiente. |
| D — supervisor compartido | Asignaciones independientes permiten servicios de distintas Plantas en fechas superpuestas; sin FK directa del Servicio a un supervisor único. |
| E — sin terminación | `end_date = NULL` conserva inicio obligatorio; no implica estado eterno ni genera cierre automático. |
| F — finalización/cancelación | Cambio de estado conserva identificadores, asignaciones y periodos históricos. Efectos operativos sobre registros abiertos y correcciones pendientes de definición. |

El modelo representa A–F sin exclusividad; no acredita su implementación.
MV-018 permanece pendiente de autorización corporativa de Google Cloud Storage
y no bloquea este análisis. Próxima actividad del Gantt: MV-022, únicamente tras
revisión/aprobación del modelo y de las decisiones que afecten su implementación.


## MV-021 / MV-029 / MV-030 — periodicidad quincenal aprobada — 2026-11-04

Aclaración posterior aprobada por el usuario responsable del proyecto el
4 de noviembre de 2026. Sustituye exclusivamente la incertidumbre del caso B
anterior sobre quincenas de calendario o intervalos de 14/15 días; el análisis
original se conserva como historial. Referencia: solicitud «Actualización de
decisión funcional MV-021 / MV-029 / MV-030» y registro en
[decisiones técnicas](decisiones-tecnicas.md).

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

Esta actualización solo define el criterio funcional; no cambia atributos ni
implementa generación de periodos, migraciones, modelos, formularios o
notificaciones. Los demás pendientes del diseño permanecen abiertos.

## MV-024 — transiciones de estado de MaintenanceService — 2026-11-06

La transición administrativa se centraliza en `MaintenanceService::transitionTo()`;
`canTransitionTo()` contiene la única matriz de dominio. La operación vuelve a
leer la fila con `lockForUpdate()` dentro de una transacción antes de decidir y
guardar, por lo que no depende del estado que pudiera tener una instancia o botón
renderizado previamente. El mismo estado devuelve `false`, no guarda y no genera
una actualización de timestamps.

| Estado actual | Destinos permitidos | Destinos rechazados |
|---|---|---|
| `planned` | `active` | `planned`, `completed`, `cancelled` |
| `active` | `completed`, `cancelled` | `planned`, `active` |
| `completed` | ninguno | `planned`, `active`, `completed`, `cancelled` |
| `cancelled` | ninguno | `planned`, `active`, `completed`, `cancelled` |

Las acciones Filament `Iniciar Servicio`, `Finalizar Servicio` y `Cancelar Servicio`
reutilizan esa operación. Solo aparecen para los estados aplicables, exigen
confirmación, comprueban `update` en servidor y notifican en español. No hay
selector libre de estado, cancelación desde `planned`, reapertura, automatismo por
fechas ni generación de Periodos. La finalización no completa `end_date` cuando es
NULL y no altera Planta, pedido, periodicidad, fechas, relaciones ni histórico.

El rechazo de una transición lanza `ValidationException`; la acción lo convierte
en notificación de error sin persistir cambios parciales. La fila bloqueada hace
que solicitudes repetidas u obsoletas se evalúen contra el estado persistido.
Operaciones SQL directas, actualizaciones masivas o escrituras sin eventos pueden
eludir esta lógica de Eloquent y no tienen protección universal física.

La cancelación `planned → cancelled`, las reaperturas y cualquier efecto sobre
asignaciones, Periodos, captura, reportes o aprobaciones siguen pendientes. La
coordinación futura corresponde a MV-025/026, MV-027/028, MV-029/030 y módulos
posteriores; Servicio Finalizado no equivale a Reporte Aprobado.


## MV-022 — Servicio implementado — 2026-11-04

La solicitud «Semana 03 — MV-022» aprueba MV-021 con estado inicial `planned`,
altas solo bajo Cliente y Planta activos, nombres y pedidos repetibles,
conservación sin eliminación física, fin opcional no anterior al inicio y
timestamps Laravel. Esta sección actualiza los pendientes históricos correspondientes.

Esquema implementado en la migración
`2026_11_04_201927_create_maintenance_services_table.php`: id BIGINT UNSIGNED PK
autoincremental; plant_id BIGINT UNSIGNED NOT NULL, FK a plants.id con DELETE
RESTRICT; name VARCHAR(150) NOT NULL; order_number VARCHAR(80) NULL; start_date
DATE NOT NULL; end_date DATE NULL; report_frequency VARCHAR(20) NOT NULL sin
default; status VARCHAR(20) NOT NULL DEFAULT planned; notes TEXT NULL;
created_at/updated_at TIMESTAMP NULL. Sin UNIQUE salvo PK, sin columnas adicionales.

`MaintenanceService::plant()` y `Plant::maintenanceServices()` son relaciones
tipadas. Modelo con HasFactory, Fillable explícito, casts date y enums string
`App\ReportFrequency` / `App\MaintenanceServiceStatus`. Se conservó el destino
predeterminado de `make:enum`, sin crear una carpeta base nueva. Factory con Planta
válida, inicio fijo reproducible, fin/pedido/notas nulos y frecuencia semanal;
acepta overrides mediante state/create para fechas, frecuencia, estado y duplicados.
El default planned existe en BD y modelo para observarlo antes de guardar.

Validaciones de modelo en saving: nombre requerido/máximo 150, pedido opcional/
máximo 80, inicio requerido/date, fin nullable/date/after_or_equal:start_date,
frecuencia y estado requeridos y validados contra enums, notas opcionales string.
En creating se consulta infraestructura activa sin confiar en relaciones cargadas.
En deleting se rechaza la eliminación mediante ValidationException.
La FK garantiza existencia de Planta; no comprueba estados activos.

No hay CHECK: coherencia de fechas y dominios se validan en Laravel. SQLite no
impone longitudes VARCHAR ni unsigned como MariaDB. La FK, nulabilidad y default
se probaron en SQLite; la migración no se ejecutó en MariaDB. Eventos se omiten
con operaciones masivas, SQL directo y escrituras silenciosas: no son una barrera
física universal de validación o borrado. Ningún flujo implementado debe usarlas
para eludir estas reglas. La comprobación de activos no es atómica ante una
desactivación concurrente; resolver coordinación en futuros escritores.

`planned` significa registrado y pendiente de inicio operativo, independiente del
Programa de trabajo. Etiquetas futuras: planned «Planeado», active «En ejecución»,
completed «Finalizado», cancelled «Cancelado»; frecuencias «Semanal», «Quincenal»,
«Mensual», «Sin reporte». No se implementan UI ni transiciones.
La periodicidad la configura el responsable autorizado por Servicio al crearlo;
no la elige el Supervisor durante captura. `order_number` no determina acceso.

Para MV-025…028: asignaciones temporales N:M, simultáneas y superpuestas entre
Plantas; un Servicio vigente podrá abrirse directamente, varios requerirán elegir
entre asignados; impedir acceso ajeno por ID/peticiones. No hay aún relaciones de
asignaciones ni Policies operativas. MV-029/MV-030 implementará quincenas de 15 días
naturales desde start_date, extremos incluidos, conforme al ejemplo ya registrado;
no quincenas calendario. `none` no elimina la obligación futura de Periodo vigente.
Último periodo parcial, cortes semanales/mensuales y cambios de frecuencia siguen
pendientes. Recordatorios por correo: requisito futuro dependiente de Periodos y
Programa de trabajo, sin diseño ni implementación aquí.


## MV-023 — administración Filament de Servicios — 2026-11-04

Se implementa `MaintenanceServiceResource` en Servicios → Servicios de
mantenimiento, con listado, creación y edición, reutilizando el modelo MV-022.
No se altera el esquema ni la migración. Los enums incorporan HasLabel con las
etiquetas españolas aprobadas; sus valores persistidos permanecen iguales.

Alta: Cliente activo requerido como dato auxiliar no persistido; su cambio limpia
Planta. Planta requerida, activa y perteneciente al Cliente seleccionado, validada
en servidor mediante reglas exists y opciones de relación filtradas. Se conserva
el control Eloquent de infraestructura activa. Nombre obligatorio (150), pedido
opcional (80), inicio requerido, fin opcional >= inicio, frecuencia requerida del
enum y notas opcionales. Sin unicidad de nombre/pedido. La página admite solo esos
atributos y fija status=planned, ignorando cualquier estado inyectado.

Edición: Cliente/Planta y estado se muestran con TextEntry; no son entradas
editables. La lista explícita de atributos permitidos al guardar contiene solo
nombre, pedido, fechas, periodicidad y notas. Campos plant_id/status inyectados
se descartan y no modifican el registro. Se permiten correcciones bajo Cliente o
Planta inactivos, conservando propietario e histórico. Esta protección corresponde
al Resource; no agrega una prohibición global de reasignación al modelo Eloquent.

Listado: Cliente, Planta, nombre, pedido, fechas, frecuencia y estado; búsqueda por
nombre/pedido; filtros por Cliente, Planta, frecuencia y estado; fecha inicial
ordenable, descendente por defecto. Consulta con carga anticipada plant.client.
No hay acciones de eliminación, restauración, duplicación ni cambio de estado.

Control administrativo mediante MaintenanceServicePolicy: roles Administrador o
Jefe de supervisores y permisos existentes service.view/service.manage, con nombres
completos documentados en decisiones técnicas. Supervisor sin acceso al catálogo.
No se implementa todavía consulta por asignación vigente.

Cambiar fechas/frecuencia no genera ni altera Periodos o relaciones. Cuando existan
Periodos operativos se necesitan reglas adicionales para esas modificaciones.
Quincenas de 15 días desde start_date se implementarán en MV-029/MV-030; none no
elimina la futura obligación de Periodo vigente. MV-024 y siguientes siguen fuera
de este bloque; MV-018 continúa pendiente de autorización corporativa de GCS.

## MV-026 — implementación de asignaciones Supervisor–Servicio — 2026-11-06

La decisión funcional aprobada el 2026-11-06 implementa `maintenance_service_supervisors` como entidad histórica intermedia entre `users` y `maintenance_services`. La asignación conserva `maintenance_service_id`, `user_id`, `start_date`, `end_date` nullable, `active` con valor predeterminado `true` y timestamps. Ambas claves foráneas usan `ON DELETE RESTRICT`; no existe `plant_id`, `client_id`, cascada ni restricción UNIQUE.

La vigencia usa fechas inclusivas: un intervalo de un día es válido, `end_date` puede ser NULL y solo se rechaza un fin anterior al inicio. Se permiten asignaciones simultáneas entre Servicios distintos y también registros históricos repetidos o superpuestos del mismo par, porque MV-025 no aprobó una regla de exclusión. `active=false` deshabilita la asignación sin borrar ni reescribir sus fechas.

El modelo `MaintenanceServiceSupervisor` expone `service()` y `supervisor()` como `BelongsTo`; `MaintenanceService::supervisorAssignments()` y `User::maintenanceServiceAssignments()` son `HasMany`. Al crear, y al cambiar `user_id`, se valida que el usuario tenga el rol Spatie `Supervisor` en el guard `web`; el modelo no concede roles, no cambia el estado del Servicio y no añade autorización operativa. La eliminación Eloquent de una asignación se bloquea para conservar histórico; las FKs también protegen el histórico ante borrados directos.

Los índices compuestos `(maintenance_service_id, active, start_date, end_date)` y `(user_id, active, start_date, end_date)` soportan consultas futuras por Servicio o Supervisor, estado y vigencia. No se añadieron índices simples redundantes ni se intentó expresar solapamiento temporal con UNIQUE.

La validación vive en eventos Eloquent. Actualizaciones SQL directas, `update()` masivo de query builder y otros caminos que omitan eventos pueden saltársela; las interfaces futuras deberán validar en servidor. Las pruebas usan SQLite aislada con claves foráneas activas; esto verifica relaciones y reglas de dominio, pero no sustituye una comprobación MariaDB de índices, bloqueo concurrente o diferencias del motor.

MV-027/MV-028 queda a cargo de Policy, scopes e interfaz de gestión y del acceso operativo por asignación vigente. Se mantienen pendientes de MV-025: contención con las fechas del Servicio, reglas para estados `planned/completed/cancelled`, solapamientos del mismo par con motivo o rechazo, auditoría adicional, pérdida posterior del rol y precedencia entre `active` y `end_date`. MV-029/MV-030 implementará Periodos; no se generan periodos en MV-026.
## MV-025 — diseño de asignaciones Supervisor–Servicio — 2026-11-06

Diseño documental para revisión. No crea migración, modelo, Resource, Policy ni datos. Fuentes contrastadas: documentación funcional v1.1, RN-001, RC-002/003/029/032/034/035 y decisiones MV-022…MV-024.

### Entidad propuesta

`MaintenanceServiceSupervisor` representa un intervalo de asignación de un `User` con rol Supervisor a un `MaintenanceService`. No es una relación permanente con Plant; la Planta y el Cliente se obtienen por el Servicio.

```mermaid
erDiagram
    User ||--o{ MaintenanceServiceSupervisor : recibe
    MaintenanceService ||--o{ MaintenanceServiceSupervisor : asigna
    Plant ||--o{ MaintenanceService : contiene
    Client ||--o{ Plant : contiene
```

| Atributo | Tipo propuesto | NULL/default | Restricción |
|---|---|---|---|
| `id` | BIGINT UNSIGNED, PK autoincremental | No | Identidad histórica |
| `maintenance_service_id` | BIGINT UNSIGNED, FK | No | `ON DELETE RESTRICT` |
| `user_id` | BIGINT UNSIGNED, FK | No | `ON DELETE RESTRICT`; usuario con rol Supervisor al crear |
| `start_date` | DATE | No | Inicio inclusivo |
| `end_date` | DATE | Sí | Fin inclusivo; NULL sin fin definido |
| `active` | BOOLEAN | No, `true` | Habilitación administrativa, sin borrar historia |
| `created_at` / `updated_at` | TIMESTAMP | Sí | Timestamps convencionales Laravel |

Las relaciones futuras serían `MaintenanceService::supervisorAssignments()` y `User::maintenanceServiceAssignments()`, ambas `HasMany`, y la asignación tendría `belongsTo` tipados a Servicio y User. Si se requiere navegación N:M, podrán añadirse `belongsToMany()->using(MaintenanceServiceSupervisor::class)` con campos de pivote. La elección entre modelo intermedio completo y pivote enriquecido queda para MV-026; PK propia, `active` y auditoría favorecen el modelo intermedio.

### Vigencia, estados y restricciones

- `start_date` es obligatorio; `end_date` es opcional y no puede ser anterior. Ambos extremos son inclusivos; un día es válido y no se exige fecha futura.
- Una asignación vigente en `d` cumple `active = true`, `start_date <= d` y (`end_date IS NULL OR d <= end_date`). La fecha de consulta debe ser explícita en la futura Policy/Scope.
- Se permiten asignaciones simultáneas en distintos Servicios, Plantas y Clientes. No habrá exclusividad por usuario, Planta, Cliente, día o intervalo.
- Se permiten intervalos consecutivos del mismo Supervisor y Servicio; el intervalo anterior se conserva.
- Propuesta pendiente: detectar en aplicación solapamientos del mismo par `(maintenance_service_id, user_id)` para evitar duplicidad accidental, pero permitirlos si tienen significado operativo justificado. MariaDB no expresa intervalos inclusivos mediante UNIQUE simple.
- Índices propuestos: FK individuales y compuestos `(user_id, active, start_date, end_date)` y `(maintenance_service_id, active, start_date, end_date)`. No crear UNIQUE temporal sin aprobación.
- `active = false` deshabilita administrativamente sin reescribir fechas. Finalizar con `end_date` y desactivar son operaciones distintas; su precedencia queda pendiente.

### Servicio y asignación

| Servicio | Asignación activa y vigente | Resultado operativo propuesto |
|---|---|---|
| `planned` | Sí | Preparación/consulta administrativa; no captura de ejecución |
| `active` | Sí | Consulta y captura futura, con permisos y Periodo vigente |
| `completed` o `cancelled` | Sí | Historia conservada; no nueva captura |
| cualquier estado | No vigente o `active=false` | Sin autorización operativa |

La habilitación futura requiere Servicio `active`, asignación activa y vigente, usuario autenticado con rol Supervisor y, cuando aplique, Periodo vigente. No se modifica MV-024 ni se cierran asignaciones automáticamente al completar/cancelar.

### Seguridad e interfaz futura

Administrador y Jefe podrán gestionar asignaciones e historial; solo se seleccionarán usuarios con rol Supervisor. El Supervisor no se asigna a sí mismo ni modifica sus vigencias. `user_id` y `maintenance_service_id` se validarán en servidor y con Policy; ocultar un selector no protege Livewire ni URL.

Para operación, una sola asignación vigente permite acceso directo; varias permiten elegir entre Servicios; ninguna impide captura. Toda consulta por ID debe aplicar el mismo alcance. `order_number` no participa.

### Casos A–J

| Caso | Resultado esperado | Regla o pendiente |
|---|---|---|
| A — un Supervisor, un Servicio | Acceso durante fechas inclusivas si Servicio activo y Periodo vigente | RN-001/RC-003; MV-027/028 |
| B — un Supervisor, varios Servicios | Servicios de Plantas distintas pueden coexistir | No exclusividad aprobada |
| C — varios Supervisores, un Servicio | Cada asignación se evalúa independientemente | Sin límite aprobado |
| D — cambio por lluvia | Se conserva la asignación original y se registra/use otra | No borrar ni desactivar automáticamente |
| E — consecutivas | Intervalos separados del mismo par son válidos | Solapamiento accidental pendiente |
| F — superpuestas | Servicios distintos pueden solaparse para el mismo Supervisor | RN-001 |
| G — finalización/cancelación | Se conserva asignación; no autoriza nueva captura | Sin cierre automático |
| H — sin asignaciones | Autenticado, pero sin Servicio operativo | UX pendiente MV-027 |
| I — URL directa ajena | Denegación por Policy/Scope antes de devolver registro | RC-003/035 |
| J — corrección histórica | Corregir fechas conservando identidad y trazabilidad | Auditoría y límites pendientes |

No se implementan estos escenarios en MV-025. Creación, edición, desactivación, autorización y pruebas corresponden a MV-026…MV-028.

## MV-027 — métodos de alcance, sin cambio de esquema — 2026-11-10

- `MaintenanceServiceSupervisor::currentFor(User)`: scope local con usuario, habilitación y fechas inclusivas calculadas en la zona de la aplicación (UTC). Agrupa el OR del fin nulo; usa comparaciones de fecha para que el cast `date` de Eloquent funcione también con SQLite, donde puede persistirse con hora. No acepta una fecha externa.
- `MaintenanceService::visibleTo(?User)`: scope local explícito con permiso/rol y precedencia administrativa; `whereHas` evita duplicados y exige todas las condiciones sobre la misma asignación. Invitado o usuario sin acceso obtiene consulta vacía.
- `MaintenanceService::acceptsNewOperations()`: consulta el estado persistido `active` y Cliente activo. No consulta las fechas del Servicio ni altera asignaciones, Servicio o infraestructura.
- `MaintenanceServicePolicy::operate(User, Service, string $permission)`: precondición de permiso específico, alcance y estado operativo. El permiso lo decide el código servidor; los futuros módulos deben agregar sus reglas particulares y Periodo vigente.

Se conservan columnas, FKs, índices y relaciones de MV-026. Continúan admitidos múltiples Supervisores, Servicios simultáneos y asignaciones superpuestas del mismo par. La consulta de histórico tras vencimiento no tiene excepción autorizada. Esta sección sustituye las propuestas previas de MV-025 únicamente en materia de autorización de consulta/operación; no implementa administración de asignaciones.

## MV-029 — Periodos de Servicio implementados — 2026-11-10

Fuentes contrastadas: documentación funcional v1.1 §3.1 (corte operativo), §7, página impresa 12 (`maintenance_periods`), §8.1 (relaciones), §13 (estados), RN-003/RN-014 y reglas originales RC-013/RC-022/RC-030/RC-032. Solicitud de implementación MV-029 y aclaración del responsable en esta conversación: fin ≥ inicio; un día admitido para no quincenales; duración/anclaje/continuidad quincenal se implementan al abrir en MV-030.

El Periodo es el corte operativo de **un Servicio**, referencia futura para actividades, hallazgos, inventario congelado y un reporte por periodo. No representa la duración del Servicio ni una asignación de Supervisor. Un Servicio tiene varios Periodos y asignaciones independientes; cambiar de Supervisor no divide ni redefine cortes. RN-014 requiere conservar el corte cerrado para el futuro arrastre al siguiente del mismo Servicio/Planta, pero no se implementa inventario ni transferencia.

### Estructura física

Se conserva el nombre explícito de la fuente **`maintenance_periods`**, frente al candidato `maintenance_service_periods`. El modelo convencional correspondiente es `App\Models\MaintenancePeriod`.

| Columna | Tipo y obligatoriedad | Fundamento |
|---|---|---|
| `id` | BIGINT UNSIGNED PK autoincremental | PK de fuente y convención técnica |
| `maintenance_service_id` | BIGINT UNSIGNED NOT NULL, FK restrictiva | Servicio obligatorio, sin duplicar Planta/Cliente |
| `start_date` | DATE NOT NULL | Inicio del corte |
| `end_date` | DATE NOT NULL | Fin del corte; la fuente no lo declara nullable |
| `status` | VARCHAR(20) NOT NULL, sin default | Valores documentados: open/submitted/changes_requested/approved/closed |
| `created_by` | BIGINT UNSIGNED NOT NULL, FK users restrictiva | Creador explícito en fuente; no es asignación de Supervisor |
| `submitted_at` | DATETIME NULL | Envío documentado |
| `approved_at` | DATETIME NULL | Aprobación documentada |
| `closed_at` | DATETIME NULL | Cierre documentado |
| `created_at`, `updated_at` | TIMESTAMP NULL | Convención `timestamps()` expresamente incluida en MV-029 |

Migración: `2026_11_11_002315_create_maintenance_periods_table.php`. Su timestamp corresponde a UTC del generador Artisan; la fecha de esta bitácora usa America/Monterrey. No se modifican migraciones previas. FKs nombradas `mp_service_fk` y `mp_creator_fk`, ambas `ON DELETE RESTRICT`; MariaDB proporciona índices de soporte. Sin UNIQUE temporal, número, secuencia, nombre, folio, observaciones, active, softDeletes ni columnas de actores adicionales. No se agregan índices redundantes/especulativos; SQLite no acredita el rendimiento de los índices MariaDB.

### Modelo y fronteras

- `MaintenancePeriod::service(): BelongsTo`, `MaintenanceService::periods(): HasMany` y `MaintenancePeriod::creator(): BelongsTo`. Sin relaciones a módulos inexistentes ni scopes globales.
- Fillable explícito, fechas `date`, auditoría `datetime`, estado enum string `MaintenancePeriodStatus` (Open, Submitted, ChangesRequested, Approved, Closed). Sin valor inicial automático: el escritor debe proporcionar status; la factory usa Open como fixture.
- `saving` valida Servicio/creador existentes, fechas obligatorias y válidas, `end_date >= start_date`, estado perteneciente al enum y auditoría opcional con fechas válidas. No toma automáticamente usuario/fecha de autenticación ni completa hitos según estado.
- `deleting` rechaza la eliminación Eloquent de cualquier Periodo para conservar histórico, coherente con Servicios/asignaciones. Las FKs impiden borrar Servicio/creador referenciados. Borrados masivos, SQL directo y eventos omitidos pueden eludir el callback del Periodo; no hay trigger que prohíba borrar la fila hija.
- NOT NULL y FKs son garantías físicas. Orden de fechas, existencia comprobada anticipadamente, dominio del estado y coherencia de atributos son validaciones Eloquent; no se agregaron CHECKs. Los casts de entradas inválidas pueden lanzar excepción antes del callback: los futuros formularios deben validar antes de asignar.
- `MaintenancePeriodFactory` permite `for($service, 'service')`, `for($user, 'creator')` y atributos explícitos de fechas/estado/hitos. Base determinista: 05–11/11/2026, Open e hitos nulos; no calcula calendarios ni interpreta la frecuencia del Servicio.

### Reglas de fechas y pendientes

| Tema | Situación efectiva |
|---|---|
| Fin anterior / igual / posterior | Anterior rechazado; igualdad admitida como validación estructural, probada para no quincenal; posterior admitido |
| Inclusión de extremos | Quincena: ambos incluidos por decisión del 04/11; no se calcula duración de otros cortes en MV-029 |
| Periodo abierto | Estado Open admitido; `end_date = NULL` rechazado por esquema fuente |
| Quincenas | Sigue aprobada la duración de 15 días desde inicio del Servicio; validación de duración/anclaje/continuidad expresamente diferida a apertura MV-030 por el responsable |
| Solapamientos y duplicados del mismo Servicio | Sin validación/UNIQUE nuevos; definición funcional general pendiente, ausencia de restricción no equivale a aprobación |
| Contigüidad y huecos | Quincenal aprobado: siguiente comienza al día posterior al anterior; aplicación MV-030. Reglas generales pendientes |
| Rango del Servicio | Sin contención implementada; último parcial y fechas fuera del rango pendientes |
| Numeración/unicidad | No hay número/folio de Periodo aprobado. El futuro folio pertenece al corte/reporte; no se implementa |
| Correcciones | Se revalidan campos y orden básico; límites por estado, trazabilidad y efectos retroactivos pendientes |
| Estado del Servicio | No hay bloqueo adicional en este modelo de persistencia; reglas de apertura/operación se aplicarán en su flujo autorizado. Esto no autoriza capturas en planned/completed/cancelled |

No se crean, cierran, recalculan ni eliminan Periodos automáticamente al cambiar Servicio o asignaciones. Cambios posteriores de periodicidad, cortes semanales/mensuales, `none` y último parcial siguen pendientes. El modelo no ejecuta el flujo RC-030: almacenar estados/hitos no equivale a validar transiciones o implementar apertura/cierre.

## MV-030 — comportamiento sobre el esquema de Periodos — 2026-11-11

No cambia la migración MV-029 ni agrega columnas/índices. `MaintenancePeriodLifecycle` abre con estado Open, creador autenticado y fechas validadas; cierra únicamente Approved → Closed con `closed_at` y `updated_at`, conservando la identidad y los demás hitos. Los solapamientos, vigencia, duración/anclaje/continuidad quincenales se validan en la operación bajo bloqueo del Servicio, no con una restricción SQL sobre intervalos.

Las frecuencias weekly/monthly/none admiten fechas manuales y huecos dentro de vigencia. Biweekly exige 15 días inclusivos y secuencia desde el inicio del Servicio. Último parcial y correcciones históricas siguen pendientes. `performUpdate` impide persistir cambios en filas Closed incluso desde objetos antiguos, leyendo el estado actual con lock. El borrado Eloquent y las FKs restrictivas de MV-029 se conservan. SQL directo y escrituras masivas no ejecutan estas validaciones; no deben usarse como frontera de operaciones funcionales.

## MV-031 — `maintenance_activity_categories` — 2026-11-11

| Columna | Tipo | Regla |
|---|---|---|
| `id` | BIGINT UNSIGNED | PK |
| `name` | VARCHAR(100) | Obligatorio, único global, espacios exteriores normalizados |
| `description` | TEXT NULL | Opcional |
| `active` | BOOLEAN DEFAULT TRUE | Inactividad lógica; no elimina histórico |
| `sort_order` | UNSIGNED INT DEFAULT 0 | Entero no negativo para orden estable |
| `created_at`, `updated_at` | TIMESTAMP NULL | Timestamps convencionales |

Modelo: `App\Models\MaintenanceActivityCategory`; factory solo para pruebas. La Policy usa permisos separados de catálogo. El Resource consulta activos ordenados para Supervisores, permite gestión administrativa a Administrador/Jefe y no expone eliminación. No existe FK todavía porque las actividades/tipos/programas se implementarán en módulos posteriores; no se inventa una relación global con Servicios o Periodos.

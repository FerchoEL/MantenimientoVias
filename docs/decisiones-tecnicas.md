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

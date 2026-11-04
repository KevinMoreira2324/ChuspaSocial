# Casos de prueba funcionales — Gestión de reportes de moderación

Casos manuales para verificar el registro de reportes, su aparición en la cola de moderación, los permisos de acceso y la ocultación de publicaciones en el muro de curso.

Estos casos amplían los escenarios de `moderacion.md` y verifican los requerimientos RF-029 y RF-030.

## 1. Preparación del entorno

- Moodle 4.5 LTS con el plugin `local_chuspasocial` instalado.
- Un curso con al menos dos usuarios matriculados.
- Una publicación visible en el muro del curso.
- Un usuario autenticado que pueda reportar contenido.
- Un usuario con la capacidad `local/chuspasocial:moderate` asignada en el contexto del sistema.
- Un usuario sin permisos de moderación.

La capacidad de moderación debe estar configurada en el entorno de pruebas. Su disponibilidad debe comprobarse antes de ejecutar los casos.

## 2. Casos de prueba

| ID | RF | Precondición | Pasos | Resultado esperado |
|---|---|---|---|---|
| CF-MR-01 | RF-029 | Usuario autenticado; existe una publicación visible en el muro del curso. | 1. Abrir el muro del curso. 2. Seleccionar una publicación. 3. Elegir Reportar. 4. Indicar un motivo válido. 5. Confirmar. | El reporte se registra con estado `open`, asociado a la publicación y al usuario que lo realizó. |
| CF-MR-02 | RF-029 | El usuario ya reportó una publicación. | 1. Volver a la misma publicación. 2. Intentar reportarla nuevamente. | El sistema rechaza el reporte duplicado y no crea un segundo registro para ese usuario y contenido. |
| CF-MR-03 | RF-029 | Usuario autenticado; existe una publicación reportable. | 1. Intentar enviar un reporte sin motivo. 2. Intentar enviar un identificador de contenido inválido. | El sistema rechaza los datos inválidos y no registra el reporte. |
| CF-MR-04 | RF-030 | Existe un reporte con estado `open`; usuario con permiso de moderación. | 1. Iniciar sesión como moderador. 2. Abrir `/local/chuspasocial/moderation.php`. 3. Revisar la cola. | El reporte aparece en la cola de pendientes con su tipo de contenido, identificador, motivo, usuario y fecha. |
| CF-MR-05 | RF-030 | Usuario autenticado sin capacidad de moderación. | 1. Abrir directamente `/local/chuspasocial/moderation.php`. | Moodle impide el acceso a la página de moderación. |
| CF-MR-06 | RF-030 | Existe un reporte abierto sobre una publicación de curso; usuario moderador. | 1. Abrir la cola de reportes. 2. Localizar el reporte. 3. Seleccionar Ocultar contenido. | El reporte cambia de `open` a `resolved` y la publicación queda marcada como oculta (`hidden = 1`). |
| CF-MR-07 | RF-030 | El moderador acaba de ocultar una publicación reportada. | 1. Volver al muro del curso. 2. Actualizar la página. 3. Buscar la publicación reportada. | La publicación oculta deja de mostrarse en el muro del curso sin necesidad de ejecutar procesos adicionales. |
| CF-MR-08 | RF-030 | Existe un reporte resuelto. | 1. Volver a `/local/chuspasocial/moderation.php`. 2. Revisar la cola de pendientes. | El reporte resuelto ya no aparece entre los reportes con estado `open`. |
| CF-MR-09 | RF-030 | Existe un reporte abierto; usuario moderador. | 1. Ejecutar la acción de ocultación con una sesión válida. 2. Revisar el resultado. | La operación se completa y aparece una notificación de confirmación. |
| CF-MR-10 | RF-030 | Existe un reporte abierto; usuario moderador. | 1. Intentar enviar la acción de ocultación sin una clave `sesskey` válida. | Moodle rechaza la solicitud y el reporte no se resuelve mediante esa petición. |
| CF-MR-11 | RF-030 | Existe un reporte abierto; usuario sin capacidad de moderación. | 1. Intentar acceder a la acción de ocultación mediante una solicitud directa. | El sistema rechaza la operación y no permite cambiar el estado del reporte ni ocultar la publicación. |
| CF-MR-12 | RF-030 | Un moderador ha resuelto un reporte. | 1. Consultar el registro del reporte en el entorno de pruebas. 2. Revisar `status`, `resolvedby` y `timeresolved`. | El estado es `resolved`, `resolvedby` identifica al moderador y `timeresolved` contiene la fecha de resolución. |

## 3. Transiciones de estado

Para los reportes de publicaciones, se verificará la siguiente transición:

`open` → `resolved`

Al registrar un reporte:

- `status = open`.
- Se almacena el usuario que reportó.
- Se registra el motivo y la fecha de creación.

Al ejecutar correctamente la acción de ocultación:

- `status = resolved`.
- `resolvedby` identifica al moderador.
- `timeresolved` registra el momento de resolución.
- La publicación reportada recibe `hidden = 1`.

El reporte resuelto deja de aparecer en la cola de pendientes.

## 4. Verificación de la ocultación en el muro del curso

Después de resolver un reporte sobre una publicación:

1. Confirmar que el reporte cambió a `resolved`.
2. Confirmar que la publicación tiene `hidden = 1`.
3. Acceder al muro del curso con un usuario matriculado.
4. Actualizar el muro y comprobar que la publicación no se muestra.
5. Verificar que las demás publicaciones visibles siguen apareciendo.

La comprobación del muro es necesaria para verificar el comportamiento funcional completo, además del cambio en la base de datos.

## 5. Criterios de aceptación

- [ ] Los reportes válidos se registran correctamente.
- [ ] Los reportes duplicados se rechazan.
- [ ] Los datos inválidos no generan reportes.
- [ ] Los reportes abiertos aparecen en la cola de moderación.
- [ ] Los usuarios sin permiso no pueden acceder ni moderar.
- [ ] La acción de ocultación requiere una sesión válida.
- [ ] El reporte cambia de `open` a `resolved`.
- [ ] Se registra el moderador y la fecha de resolución.
- [ ] La publicación reportada deja de mostrarse en el muro del curso.
- [ ] Los reportes resueltos desaparecen de la cola de pendientes.

## 6. Registro de resultados

Para cada caso ejecutado, registrar:

- Identificador del caso.
- Fecha de ejecución.
- Usuario y rol utilizados.
- Resultado obtenido.
- Estado: aprobado, fallido o bloqueado.
- Evidencia o captura, cuando corresponda.

Estos casos representan verificaciones pendientes de ejecución y no implican que las funcionalidades hayan sido aprobadas.

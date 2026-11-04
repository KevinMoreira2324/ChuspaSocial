# Casos de prueba funcionales — Flujo de Marketplace

## Objetivo

Documentar los casos de prueba manuales para verificar el ciclo completo de una oferta de material de estudio en ChuspaSocial: creación, visualización, filtrado por curso, contacto con el vendedor, edición y cambio de estado a vendido.

Estos casos complementan `marketplace.md` y se relacionan con los requerimientos funcionales RF-034 a RF-040.

## 1. Preparación del entorno

- Moodle con el plugin `local_chuspasocial` instalado y Marketplace habilitado.
- Un vendedor autenticado con permisos `viewmarketplace` y `createlisting`.
- Un comprador autenticado con permiso `viewmarketplace`.
- Un usuario sin permiso para acceder al Marketplace.
- Dos cursos de prueba: Curso A y Curso B.
- El comprador está matriculado en Curso A, pero no en Curso B.
- Una oferta disponible vinculada al Curso B para comprobar el filtrado.
- Una oferta disponible creada por el vendedor y vinculada al Curso A.

## 2. Casos de prueba

### CP-MKF-001 — Crear una oferta de material de estudio

- **RF relacionado:** RF-034, RF-039.
- **Precondición:** El Marketplace está habilitado y el vendedor tiene permiso para publicar.
- **Pasos:**
  1. Iniciar sesión como vendedor.
  2. Abrir `/local/chuspasocial/marketplace.php`.
  3. Seleccionar **Publicar libro**.
  4. Completar título, autor, descripción y precio.
  5. Asociar la oferta al Curso A.
  6. Guardar la oferta.
- **Resultado esperado:** La oferta se registra con el vendedor como propietario y el sistema redirige a su página de detalle.

### CP-MKF-002 — Visualizar una oferta publicada

- **RF relacionado:** RF-034.
- **Precondición:** Existe una oferta disponible vinculada al Curso A.
- **Pasos:**
  1. Iniciar sesión como comprador matriculado en Curso A.
  2. Abrir el Marketplace.
  3. Seleccionar la pestaña **Mis cursos**.
  4. Localizar la oferta.
  5. Abrir su detalle.
- **Resultado esperado:** La oferta aparece en el listado y el comprador puede consultar su título, autor, precio y descripción.

### CP-MKF-003 — Filtrar ofertas por curso

- **RF relacionado:** RF-034.
- **Precondición:** Existen ofertas disponibles vinculadas a Curso A y Curso B. El comprador solo está matriculado en Curso A.
- **Pasos:**
  1. Iniciar sesión como comprador.
  2. Abrir el Marketplace.
  3. Seleccionar **Mis cursos**.
  4. Revisar las ofertas mostradas.
- **Resultado esperado:** Se muestran las ofertas disponibles relacionadas con Curso A. Las ofertas vinculadas exclusivamente a Curso B no aparecen.

### CP-MKF-004 — Usuario sin cursos matriculados

- **RF relacionado:** RF-034.
- **Precondición:** El usuario tiene acceso al Marketplace, pero no está matriculado en ningún curso.
- **Pasos:**
  1. Iniciar sesión con el usuario.
  2. Abrir el Marketplace.
  3. Seleccionar **Mis cursos**.
- **Resultado esperado:** No se muestran ofertas y aparece el mensaje informativo correspondiente.

### CP-MKF-005 — Buscar una oferta por título o autor

- **RF relacionado:** RF-035.
- **Precondición:** Existe una oferta con título y autor conocidos.
- **Pasos:**
  1. Abrir el Marketplace.
  2. Seleccionar **Buscar**.
  3. Introducir parte del título de la oferta.
  4. Ejecutar la búsqueda.
  5. Repetir utilizando el nombre del autor.
- **Resultado esperado:** Se muestran las ofertas cuyos títulos o autores coinciden con el texto buscado, sin distinguir mayúsculas y minúsculas.

### CP-MKF-006 — Contactar al vendedor

- **RF relacionado:** RF-036.
- **Precondición:** El comprador puede acceder al detalle de una oferta publicada por otro usuario.
- **Pasos:**
  1. Iniciar sesión como comprador.
  2. Abrir el detalle de la oferta.
  3. Seleccionar **Contactar al vendedor**.
  4. Comprobar el destino del enlace.
- **Resultado esperado:** El sistema dirige al comprador a la mensajería de Moodle con el identificador del vendedor.

### CP-MKF-007 — Editar una oferta propia

- **RF relacionado:** RF-039.
- **Precondición:** El vendedor tiene una oferta registrada.
- **Pasos:**
  1. Iniciar sesión como propietario.
  2. Abrir `/local/chuspasocial/listing_edit.php?id=ID`, reemplazando `ID` por el identificador de su oferta.
  3. Modificar el título, descripción o precio.
  4. Guardar los cambios.
  5. Consultar nuevamente el detalle.
- **Resultado esperado:** Los datos se actualizan correctamente y la oferta conserva al mismo propietario.

### CP-MKF-008 — Impedir la edición de una oferta ajena

- **RF relacionado:** RF-039.
- **Precondición:** Existe una oferta perteneciente a otro usuario.
- **Pasos:**
  1. Iniciar sesión como comprador.
  2. Intentar abrir `/local/chuspasocial/listing_edit.php?id=ID` utilizando el identificador de la oferta ajena.
- **Resultado esperado:** Moodle rechaza el acceso al formulario de edición y no permite modificar la oferta.

### CP-MKF-009 — Marcar una oferta como vendida

- **RF relacionado:** RF-038.
- **Precondición:** El vendedor tiene una oferta con estado `available`.
- **Pasos:**
  1. Iniciar sesión como propietario.
  2. Abrir el detalle de la oferta.
  3. Seleccionar **Marcar como vendido**.
  4. Comprobar la confirmación.
  5. Revisar el estado de la oferta.
- **Resultado esperado:** La oferta cambia de `available` a `sold` y se muestra una notificación de confirmación.

### CP-MKF-010 — Verificar que una oferta vendida no aparece en Mis cursos

- **RF relacionado:** RF-034, RF-038.
- **Precondición:** Una oferta vinculada al Curso A fue marcada como vendida.
- **Pasos:**
  1. Iniciar sesión como comprador matriculado en Curso A.
  2. Abrir el Marketplace.
  3. Seleccionar **Mis cursos**.
  4. Buscar la oferta vendida.
- **Resultado esperado:** La oferta con estado `sold` no aparece en el listado de ofertas disponibles de Mis cursos.

### CP-MKF-011 — Impedir que otro usuario marque una oferta como vendida

- **RF relacionado:** RF-038, RF-039.
- **Precondición:** Existe una oferta disponible perteneciente a otro usuario.
- **Pasos:**
  1. Iniciar sesión como comprador.
  2. Abrir el detalle de la oferta.
  3. Verificar que no aparezca el botón **Marcar como vendido**.
  4. Intentar ejecutar la acción mediante una solicitud directa.
- **Resultado esperado:** El sistema impide el cambio de estado y la oferta permanece disponible.

### CP-MKF-012 — Validar permisos de acceso al Marketplace

- **RF relacionado:** RF-034.
- **Precondición:** Existe un usuario sin la capacidad `local/chuspasocial:viewmarketplace`.
- **Pasos:**
  1. Iniciar sesión con ese usuario.
  2. Intentar acceder directamente a `/local/chuspasocial/marketplace.php`.
  3. Intentar abrir el detalle de una oferta.
- **Resultado esperado:** Moodle rechaza el acceso a las páginas protegidas.

### CP-MKF-013 — Validar permiso para crear ofertas

- **RF relacionado:** RF-039.
- **Precondición:** El usuario puede consultar el Marketplace, pero no tiene permiso `createlisting`.
- **Pasos:**
  1. Iniciar sesión con el usuario.
  2. Abrir el Marketplace.
  3. Comprobar si aparece el botón **Publicar libro**.
  4. Intentar crear una oferta sin la capacidad requerida.
- **Resultado esperado:** No aparece el botón de publicación y el servicio rechaza la creación de una oferta sin permisos.

### CP-MKF-014 — Validar precio y fotografías de una oferta

- **RF relacionado:** RF-040.
- **Precondición:** El vendedor puede crear ofertas.
- **Pasos:**
  1. Abrir el formulario de publicación.
  2. Intentar registrar un precio negativo.
  3. Comprobar que no se acepte.
  4. Seleccionar hasta tres fotografías JPG, PNG o WEBP.
  5. Guardar la oferta.
  6. Intentar superar el límite de tres fotografías.
- **Resultado esperado:** No se aceptan precios negativos. El formulario limita las fotografías a tres archivos de los formatos permitidos.

### CP-MKF-015 — Verificar la búsqueda de una oferta vendida

- **RF relacionado:** RF-035, RF-038.
- **Precondición:** Existe una oferta marcada como `sold`.
- **Pasos:**
  1. Abrir el Marketplace.
  2. Seleccionar **Buscar**.
  3. Introducir el título de la oferta vendida.
  4. Revisar los resultados.
- **Resultado esperado:** La búsqueda puede devolver la oferta vendida porque utiliza una consulta sin filtro por estado. Su representación debe reflejar que no está disponible.

## 3. Flujo completo de una oferta

El ciclo principal que debe comprobarse es:

1. El vendedor crea una oferta.
2. La oferta queda vinculada a uno o más cursos.
3. El comprador matriculado encuentra la oferta en **Mis cursos**.
4. El comprador consulta los detalles y puede contactar al vendedor.
5. El propietario puede editar la oferta.
6. El propietario marca la oferta como vendida.
7. La oferta cambia de `available` a `sold`.
8. La oferta deja de aparecer entre las disponibles en **Mis cursos**.

## 4. Criterios de aceptación

- [ ] El vendedor puede crear una oferta válida.
- [ ] La oferta aparece en el listado correspondiente.
- [ ] El filtro por cursos respeta las matrículas del comprador.
- [ ] El comprador puede consultar el detalle de una oferta.
- [ ] El comprador puede acceder a la mensajería del vendedor.
- [ ] Solo el propietario puede editar su oferta.
- [ ] Solo el propietario puede marcarla como vendida.
- [ ] La oferta cambia correctamente de `available` a `sold`.
- [ ] Las ofertas vendidas desaparecen del listado de disponibles en Mis cursos.
- [ ] Se aplican los permisos de acceso y publicación.
- [ ] Se validan los precios y los límites de fotografías.

## 5. Registro de ejecución

| ID del caso | Resultado obtenido | Estado | Observaciones |
|---|---|---|---|
| CP-MKF-001 | Pendiente de ejecución | Pendiente | |
| CP-MKF-002 | Pendiente de ejecución | Pendiente | |
| CP-MKF-003 | Pendiente de ejecución | Pendiente | |
| CP-MKF-004 | Pendiente de ejecución | Pendiente | |
| CP-MKF-005 | Pendiente de ejecución | Pendiente | |
| CP-MKF-006 | Pendiente de ejecución | Pendiente | |
| CP-MKF-007 | Pendiente de ejecución | Pendiente | |
| CP-MKF-008 | Pendiente de ejecución | Pendiente | |
| CP-MKF-009 | Pendiente de ejecución | Pendiente | |
| CP-MKF-010 | Pendiente de ejecución | Pendiente | |
| CP-MKF-011 | Pendiente de ejecución | Pendiente | |
| CP-MKF-012 | Pendiente de ejecución | Pendiente | |
| CP-MKF-013 | Pendiente de ejecución | Pendiente | |
| CP-MKF-014 | Pendiente de ejecución | Pendiente | |
| CP-MKF-015 | Pendiente de ejecución | Pendiente | |

Los casos están documentados y pendientes de ejecución manual. Su inclusión no implica que las funcionalidades hayan sido aprobadas.

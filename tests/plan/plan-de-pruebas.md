# Plan de Pruebas

## 1. Objetivo

Verificar que el plugin `local_chuspasocial` para Moodle 4.5 LTS cumple sus requisitos funcionales: muro social general y por materia, comentarios y reacciones, etiquetas, seguimiento de usuarios, notificaciones, reporte de contenido inapropiado y marketplace de libros, respetando los permisos y la visibilidad de cada funcionalidad. También se comprueba que el plugin funcione con PHP 8.1 a 8.3 y con MariaDB, MySQL y PostgreSQL, y que su interfaz esté disponible en español e inglés.

## 2. Alcance
### En alcance
- Muro social general y por materia: publicaciones con texto e imágenes; el muro por materia solo es visible para usuarios inscritos en el curso.
- Comentarios y reacciones: creación, visualización y gestión de comentarios y reacciones asociados a publicaciones, respetando los permisos.
- Muro por materia: visibilidad restringida a los inscritos en el curso.
- Tags / hashtags: etiquetar publicaciones, seguir temas y filtrar el muro.
- Seguir a otros usuarios y ver su perfil social.
- Noticias según tags (publicaciones de usuarios, anuncios oficiales y actividad de Moodle).
- Eventos: consulta y visualización de eventos del plugin, verificando su información y visibilidad según los permisos correspondientes.
- Notificaciones (comentarios, reacciones, nuevos seguidores) y mensajes directos.
- Reporte de contenido inapropiado.
- Marketplace de libros: anuncios con precio vinculados a una o varias materias, y contacto con el vendedor.
- Validación de formularios (por ejemplo, precio mayor o igual a 0 en los anuncios).
- Permisos y capacidades de acceso a cada funcionalidad.
- Funciones externas (AJAX) del plugin `local_chuspasocial`.
- Compatibilidad con Moodle 4.5 LTS, PHP 8.1 a 8.3 y MariaDB, MySQL y PostgreSQL.
- Interfaz en español e inglés (`lang/es`, `lang/en`).

### Fuera de alcance
- Pago y entrega de los libros: se acuerdan fuera de la plataforma.
- Pasarela de pagos y gestión de transacciones económicas.
- Funcionamiento interno del núcleo de Moodle y de sus plugins de terceros.
- Versiones de Moodle distintas de 4.5 LTS.
- Aplicaciones móviles nativas (solo la interfaz web con el tema Boost).
- Pruebas de usabilidad con usuarios reales y de accesibilidad avanzada.

## 3. Tipos de prueba
- [ ] Unitarias
- [ ] Integración
- [ ] Funcionales manuales
- [ ] Rendimiento
- [ ] Seguridad
- [ ] Regresión

## 4. Entornos

| Entorno | SO | Versión |
|---|---|---|
| Local | | |
| CI | Ubuntu latest | |

### Dependencias
- Moodle 4.5 LTS con `local_chuspasocial` instalado y habilitado.
- PHP 8.1 a 8.3 y MariaDB, MySQL o PostgreSQL.
- Usuarios de prueba con roles, capacidades y matrículas diferenciadas; cursos y datos de ejemplo para publicaciones, comentarios, eventos y anuncios.
- Herramientas de pruebas automatizadas disponibles en CI: PHPUnit, Behat y `moodle-plugin-ci`.

## 5. Responsables

| Rol | Responsable |
|---|---|
| Diseño de casos | |
| Ejecución manual | |
| Automatización | |
| Reporte | |

## 6. Criterios de salida
- [ ] Cobertura mínima de ___%
- [ ] Cero bugs críticos abiertos
- [ ] Todos los casos ejecutados

## 7. Riesgos

| Riesgo | Probabilidad | Impacto | Mitigación |
|---|---|---|---|
| Incompatibilidad con Moodle 4.5 LTS o con versiones de PHP (8.1 a 8.3) | Media | Alta | Ejecutar CI con `moodle-plugin-ci` en varias versiones de PHP y probar sobre `MOODLE_405_STABLE` |
| Fallos de esquema o consultas en distintos motores de base de datos (MariaDB, MySQL, PostgreSQL) | Media | Alta | Validar el esquema XMLDB y las consultas DML en los tres motores antes de cada entrega |
| Fuga de publicaciones entre materias (un muro de curso lo deben ver solo los inscritos) | Media | Alta | Casos de prueba de permisos y capacidades por curso, con usuarios inscritos y no inscritos |
| Exposición de datos personales en perfiles, seguidores o mensajería | Media | Alta | Revisar capacidades y privacidad; documentar y verificar lo definido en `security/` |
| Vulnerabilidades web (XSS en publicaciones y comentarios, CSRF, subida de imágenes) | Media | Alta | Limpiar y validar entradas, probar con datos maliciosos y ejecutar pruebas de seguridad |
| Contenido inapropiado o abuso en publicaciones y comentarios | Alta | Media | Probar el flujo de reportes de contenido y de moderación de principio a fin |
| Datos inconsistentes en el marketplace (precio inválido, contacto externo incorrecto) | Media | Media | Pruebas de validación del formulario de anuncios (precio mayor o igual a 0, campos requeridos) |
| Bajo rendimiento del muro con muchas publicaciones, tags y notificaciones | Media | Media | Usar paginación, revisar consultas y ejecutar pruebas de carga con datos de ejemplo |
| Cobertura de pruebas insuficiente (PHPUnit y Behat) por tiempo y tamaño del equipo | Alta | Media | Priorizar los casos críticos y revisar la cobertura en CI antes de fusionar a `dev` |
| Diferencias entre entornos locales y CI (Windows, Linux, Docker) | Media | Baja | Documentar la instalación, fijar versiones de dependencias y usar `moodle-docker` como alternativa |
## 8. Estrategia de pruebas automatizadas con PHPUnit

Las pruebas automatizadas del plugin `local_chuspasocial` se desarrollan utilizando PHPUnit y el framework de pruebas de Moodle 4.5 LTS.

Su objetivo es comprobar el funcionamiento de los servicios, eventos, formularios, funciones externas y operaciones de persistencia, respetando los permisos de los usuarios y el aislamiento de los datos.

### 8.1. Estructura de carpetas

Las pruebas se organizan dentro de `src/tests/`:

| Ruta | Responsabilidad |
|---|---|
| `lib_test.php` | Pruebas de funciones generales del plugin |
| `event/` | Pruebas de eventos |
| `external/` | Pruebas de funciones externas y servicios AJAX |
| `form/` | Pruebas de formularios |
| `generator/lib.php` | Clase generadora de datos del plugin |
| `local/` | Pruebas de servicios y lógica de negocio |
| `local/persistent/` | Pruebas de persistencia y registros |
| `behat/` | Escenarios de pruebas funcionales Behat |

Los archivos de pruebas PHPUnit utilizan nombres terminados en `_test.php`, siguiendo las convenciones de Moodle.

### 8.2. Directrices para escribir pruebas

Las nuevas pruebas deben:

1. Extender `advanced_testcase` cuando necesiten utilizar la base de datos o las herramientas de Moodle.
2. Utilizar métodos de prueba con nombres descriptivos, como `test_can_post_announcement_docente()`.
3. Preparar únicamente los datos necesarios para cada escenario.
4. Verificar resultados mediante aserciones de PHPUnit, como `assertTrue()`, `assertFalse()` y `assertEquals()`.
5. Comprobar tanto los casos correctos como los errores y restricciones de permisos.
6. Evitar dependencias entre pruebas y no utilizar datos reales de producción.
7. Mantener las convenciones de codificación y documentación de Moodle.

### 8.3. Aislamiento de base de datos

Las pruebas que modifican datos deben utilizar `$this->resetAfterTest()` para que Moodle restaure el estado de prueba después de su ejecución.

Ejemplo basado en `src/tests/local/post_service_test.php`:

```php
use advanced_testcase;

class post_service_test extends advanced_testcase {
    protected function setUp(): void {
        $this->resetAfterTest();
    }
}
```

Esto permite crear usuarios, cursos, matrículas y registros durante una prueba sin dejar cambios que afecten a las siguientes.

Cada prueba debe poder ejecutarse de manera independiente.

### 8.4. Generación de datos de prueba

Moodle proporciona generadores para crear registros de prueba mediante:

```php
$generator = $this->getDataGenerator();

$course = $generator->create_course();
$user = $generator->create_user();

$generator->enrol_user($user->id, $course->id, 'student');
```

Este procedimiento permite preparar escenarios con usuarios, cursos y roles específicos.

También se pueden utilizar:

- `$this->setUser($user)` para ejecutar operaciones como un usuario determinado.
- `$this->setAdminUser()` para comprobar acciones administrativas.
- `$this->getDataGenerator()` para crear datos adicionales necesarios en cada caso.

El archivo `src/tests/generator/lib.php` define la clase `local_chuspasocial_generator`, que extiende `component_generator_base`.

Actualmente, esta clase no contiene métodos personalizados. Si se implementan generadores específicos del plugin, deberán documentarse y utilizarse en las pruebas correspondientes.

### 8.5. Ejecución de PHPUnit

Antes de ejecutar las pruebas, Moodle debe estar instalado y el entorno PHPUnit debe estar configurado con una base de datos exclusiva para pruebas.

Desde la raíz de Moodle, inicializar el entorno:

```bash
php admin/tool/phpunit/cli/init.php
```

Ejecutar las pruebas del plugin:

```bash
vendor/bin/phpunit local/chuspasocial/tests/
```

Ejecutar un archivo específico:

```bash
vendor/bin/phpunit local/chuspasocial/tests/local/post_service_test.php
```

Las rutas anteriores corresponden a la instalación del plugin dentro de Moodle. En este repositorio, el código fuente de las pruebas se encuentra en `src/tests/`.

Las pruebas deben ejecutarse únicamente en el entorno de pruebas configurado, nunca sobre una base de datos de producción.

### 8.6. Criterios de aceptación para nuevos servicios

Cada nuevo servicio del plugin debe cumplir los siguientes criterios:

- [ ] Dispone de pruebas PHPUnit para sus operaciones principales.
- [ ] Incluye escenarios correctos y casos de error.
- [ ] Verifica los permisos y capacidades correspondientes.
- [ ] Utiliza `resetAfterTest()` cuando modifica la base de datos.
- [ ] Utiliza generadores de Moodle para preparar los datos cuando corresponda.
- [ ] Comprueba los resultados mediante aserciones.
- [ ] No depende del orden de ejecución de otras pruebas.
- [ ] No modifica datos de producción.
- [ ] Cumple las convenciones de codificación de Moodle.
- [ ] Supera las pruebas PHPUnit antes de integrar el cambio en `dev`.

### 8.7. Mantenimiento y regresión

Cuando se corrija un error o se modifique un servicio existente, se deben revisar las pruebas relacionadas y agregar nuevos casos cuando sea necesario.

Las pruebas deben cubrir especialmente:

- Restricciones de acceso a publicaciones por curso.
- Creación y eliminación de comentarios y reacciones.
- Seguimiento de usuarios y etiquetas.
- Validación de formularios y anuncios.
- Operaciones de persistencia.
- Comportamiento de los servicios ante usuarios sin permisos.

Los fallos detectados deben corregirse antes de aprobar la integración del cambio, siguiendo los criterios de salida establecidos en este plan.

# 📘 Sistema de Asistencia de Cristian Soft 
### Desarrollado en **CodeIgniter 4** y **PHP 8.3**

Este proyecto es un sistema básico de **control de asistencia** hecho por Cristian Soft .  
Incluye roles de **Administrador**, **Docente** y **Estudiante**, permitiendo registrar asistencias, motivos de faltas y generar reportes en PDF.

---

# 🛠️ Tecnologías Utilizadas
- **PHP 8.3**
- **CodeIgniter 4.x**
- **MySQL / MariaDB**
- Composer

---

# 🧩 Relaciones principales

- Un **usuario** puede ser **docente** o **estudiante**.  
- Un **curso** contiene **grupos**.  
- Un **grupo** tiene **un docente responsable**.  
- Un **grupo** tiene **muchos estudiantes** (vía matrículas).  
- Una **sesión** pertenece a un solo grupo.  
- Una **asistencia** se registra por sesión y por estudiante.

---

# Datos de Prueba

| Email                   | Contraseña | Rol          | Estado     |
|-------------------------|------------|--------------|------------|
| admin@sistema.com       | admin123   | Administrador| Activo     |
| profesor@sistema.com    | prof123    | Profesor     | Activo     |
| estudiante@sistema.com  | est123     | Estudiante   | Inactivo   |
| suspendido@sistema.com  | susp123    | Estudiante   | Suspendido |

---

# 🔒 Cumplimiento y Seguridad del Sistema

Este sistema ha sido diseñado e implementado siguiendo las mejores prácticas de seguridad de la información y los requisitos de control y fiscalización del Estado Peruano, tomando como base las Normas Técnicas Peruanas (NTP).

---

## 1. Implementación de Políticas de Contraseñas Seguras

Para garantizar la **confidencialidad** e **integridad** de la información del usuario, hemos adoptado controles de seguridad basados en la **NTP ISO/IEC 27002**.

### ✅ Control Aplicado: Complejidad Mínima de Contraseñas

La política de contraseñas exige el cumplimiento estricto de los siguientes requisitos de complejidad:

* **Longitud Mínima:** más de 6 caracteres.
* **Combinación Requerida:** La contraseña debe incluir al menos un carácter de cada una de las siguientes categorías:
    * **Carácter Numérico** (dígitos: 0-9)
    * **Mayúsculas** (A-Z)
    * **Minúsculas** (a-z)
    * **Carácter Especial** (símbolos: !, @, #, $, %, etc.)

---

## 2. Módulo de Auditoría y Trazabilidad (Logging)

En cumplimiento con los requisitos de **rendición de cuentas (accountability)** de la **NTP ISO/IEC 27002** y las directrices de **Fiscalización y Control Gubernamental** de la Contraloría General de la República (CGR), este sistema incluye un módulo de auditoría.

### 📜 Fundamento Normativo

La inclusión de este módulo responde a la necesidad de permitir la fiscalización efectiva, tal como lo exigen las **Normas Generales de Control Gubernamental (NGCG)**.

### 📝 Funcionalidades del Módulo

El módulo de auditoría garantiza la **trazabilidad** y la generación de **evidencias inalterables** para fines de control.

* **Registro de Eventos:** Se registran automáticamente todas las acciones críticas, incluyendo (pero no limitado a):
    * Inicios y cierres de sesión.
    * Creación, modificación o eliminación de datos sensibles.
    * Intentos de acceso fallidos.
* **Información Registrada:** Cada registro incluye metadatos esenciales para la auditoría:
    * Identificador del Usuario (Quién).
    * Acción realizada (Qué).
    * Fecha y hora de la acción (Cuándo).
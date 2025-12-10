# 📘 Sistema de Asistencia de Cristian Soft 
### Desarrollado en **CodeIgniter 4** y **PHP 8.3**

Este proyecto es un sistema básico de **control de asistencia** hecho por Cristian Soft .  
Incluye roles de **Administrador**, **Docente** y **Estudiante**, permitiendo registrar asistencias, motivos de faltas y generar reportes en PDF.

---

# 🛠️ Tecnologías Utilizadas
- **PHP 8.3.21**
- **CodeIgniter 4**
- **MySQL / MariaDB**
- **Git**
---

# 🛠️ Entorno de desarrollo
- **Navicat Premium 16**
- **Visual Estudio Code 1.106**
- **GitHub Desktop 3.5.1**
- **Laragon 2025 v8.2.3**
- **Postman 11.75.1**
- Plantilla Premium Steex Admin - https://themeforest.net/item/steex-html-laravel-admin-dashboard-template/45530448
- Datasets - 1,040 Usuarios, 70 Cursos, 1,800+ Asistencias - https://www.kaggle.com/datasets/pertaquio/sistema-de-asistencia-acadmica/data

---

# 🧩 Relaciones principales

- Un **usuario** puede ser **docente** o **estudiante**.  
- Un **curso** contiene **grupos**.  
- Un **grupo** tiene **un docente responsable**.  
- Un **grupo** tiene **muchos estudiantes**.  
- Una **sesión** pertenece a un solo grupo.  
- Una **asistencia** se registra por sesión y por estudiante.

---

# Datos de Prueba

| Email                                | Contraseña | Rol          | Estado     |
|--------------------------------------|------------|--------------|------------|
| administrador@cristiansoft.com       | Cris*25+   | Administrador| Activo     |
| prof002@cristiansoft.com             | Cris*25+   | Profesor     | Activo     |
| est0993@cristiansoft.com             | Cris*25+   | Estudiante   | Inactivo   |
| est1000@cristiansoft.com             | Cris*25+   | Estudiante   | Suspendido |

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
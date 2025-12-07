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

# 🚀 Instalación del Proyecto

```bash
composer install
cp env .env
php spark key:generate
php spark migrate
php spark serve

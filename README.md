
<p align="center">
    <a href="https://symfony.com" target="_blank">
        <img src="https://symfony.com/logos/symfony_dynamic_01.svg" alt="Symfony Logo">
    </a>
    <br>
    <a href="https://symfony.com" target="_blank">
        <img src="https://getbootstrap.com/docs/5.3/assets/brand/bootstrap-logo-shadow.png" alt="Bootstrap Logo" width=100>
    </a>
</p>

# 🛡️ Aplicación web con autenticación completa  

Aplicación web con un **sistema de autenticación completo**, incluyendo:  
✅ **Inicio de sesión con "Recuérdame"**  
✅ **Registro con confirmación por correo (Symfony Mailer)**  
✅ **Recuperación y restablecimiento de contraseña**  

Interfaz sencilla y moderna con **Bootstrap**, completamente **responsive** 📱 y con **modo claro/oscuro automático** 🌗.

---

## 🚀 Instalación y Configuración

### 📌 1. Instalar dependencias
```bash
composer install
```

### ⚙️ 2. Configurar variables de entorno (credenciales)
Generamos un .env.local y metemos las credenciales: ``cp .env.local.example .env.local``
```bash
APP_ENV=dev

DATABASE_URL="mysql://usuario:password@127.0.0.1:3306/nombre_bd?serverVersion=8.0"

MAILER_DSN=smtp://usuario:password@smtp.servidor.com:587

APP_SECRET=tu_secreto_aqui
```

### 🔨 3. Crear la base de datos y ejecutar migraciones
```bash
php bin/console doctrine:migrations:migrate
```
<i>*Si no tienes schema, ejecuta ``php bin/console doctrine:database:create``</i>


---

## 📜 Licencia
Este proyecto está licenciado bajo la **MIT License**, lo que significa que puedes usar, modificar y distribuir el código libremente, incluso para proyectos comerciales, siempre que incluyas la atribución original.  

Puedes leer más sobre esta licencia en:  
🔗 [Licencia MIT - Open Source Initiative](https://opensource.org/licenses/MIT)
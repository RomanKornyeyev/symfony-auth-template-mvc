
<p align="center">
    <a href="https://symfony.com" target="_blank">
        <img src="https://symfony.com/logos/symfony_dynamic_01.svg" alt="Symfony Logo">
    </a>
    <br>
    <a href="https://symfony.com" target="_blank">
        <img src="https://getbootstrap.com/docs/5.3/assets/brand/bootstrap-logo-shadow.png" alt="Bootstrap Logo" width=100>
    </a>
</p>

# Template de autentificación y configuración de perfil para Symfony MVC 

> ℹ️ Versión del proyecto:
> - Symfony CLI: 5.16.1
> - Symfony: 8.0.3
> - PHP: 8.4.3
>
> ℹ️ REQUISITOS MÍNIMOS
> - Symfony CLI >= 5.10
> - Symfony >= 7.0
> - PHP >= 8.2

---

Aplicación web con un **sistema de autenticación completo**, incluyendo: 

##### AUTH
✅ **Inicio de sesión con "Recuérdame"**  
✅ **Registro con confirmación por email**  
✅ **Recuperación y restablecimiento de contraseña**  

##### GESTIÓN DE PERFIL (DASHBOARD)
✅ **edición de datos**  
✅ **edición de password con throttling y confirmación por email**  
⌛ **edición de email con confirmación por email**  

##### INTERFAZ
✅ Interfaz sencilla con **Bootstrap**, 100% **responsive** y con **modo claro/oscuro automático**.

---

## Instalación y Configuración

### OPCIÓN 1: CLONAR E INSTALAR DEPENDENCIAS (RECOMENDADO)

#### 1.1 Instalar dependencias
```bash
composer install
```

#### 1.2 Configurar variables de entorno (credenciales)
Generamos un .env.local y metemos las credenciales: ``cp .env.local.example .env.local``
```bash
APP_ENV=dev

DATABASE_URL="mysql://usuario:password@127.0.0.1:3306/nombre_bd?serverVersion=8.0"

MAILER_DSN=smtp://usuario:password@smtp.servidor.com:587

APP_SECRET=tu_secreto_aqui
```

#### 1.3 Crear la base de datos y ejecutar migraciones
```bash
php bin/console doctrine:migrations:migrate
```
<i>*Si no tienes schema, ejecuta ``php bin/console doctrine:database:create``</i>

_

### OPCIÓN 2: INICIAR UN NUEVO PROYECTO Y COPIAR ARCHIVOS NECESARIOS

#### 2.1 Crear un proyecto base de Symfony MVC
```bash
symfony new mi_proyecto --webapp
```

#### 2.2 Instalar dependencias extra necesarias
```bash
composer require babdev/pagerfanta-bundle
composer require knplabs/knp-paginator-bundle
composer require symfony/rate-limiter
```

#### 2.3 Copiar archivos de de configuración
##### 2.3.1 config
```bash
config>packages>babdev_pagerfanta.yaml
config>packages>knp_paginator.yaml
config>packages>messenger.yaml
config>packages>security.yaml # IMPORTANTE PARA AUTH PROVIDER
config>packages>twig.yaml
```

##### 2.3.2 public
```bash
public>*
```

##### 2.3.3 src
```bash
src>* # EXCEPTO Kernel.php
```

##### 2.3.4 templates
```bash
templates>*
```

#### 2.4 Credenciales y migraciones
Seguir pasos 1.2 y 1.3 para credenciales y migraciones.

_

> **ℹ️ Requisitos mínimos**
> 
> - Symfony CLI >= 5.10
> - Symfony >= 7.0
> - PHP >= 8.2
> 
> Este proyecto está construido con **Symfony CLI 5.16.1**, **Symfony 8.0.3** y **PHP 8.4.3**.
> Versiones anteriores y posteriores pueden presentar incompatibilidades.


---

## Licencia
Este proyecto está licenciado bajo la **MIT License**, lo que significa que puedes usar, modificar y distribuir el código libremente, incluso para proyectos comerciales, siempre que incluyas la atribución original.  

Puedes leer más sobre esta licencia en:  
[Licencia MIT - Open Source Initiative](https://opensource.org/licenses/MIT)
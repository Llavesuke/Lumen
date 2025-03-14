# Lumen - Plataforma de Streaming

## Descripción del Proyecto

Lumen es una plataforma de streaming moderna desarrollada con Laravel Lumen en el backend y Vue.js en el frontend. El proyecto utiliza una arquitectura de microservicios con Docker para facilitar el despliegue y la escalabilidad.

### Funcionalidades Principales

- Reproducción de contenido multimedia (videos)
- Sistema de usuarios con autenticación segura
- Servicio de renderizado con Puppeteer para scrapear enlaces m3u8 de Playdede
- Interfaz de usuario intuitiva y responsive
- Base de datos MySQL para almacenamiento persistente

## Instrucciones de Instalación

### Requisitos Previos

- Docker y Docker Compose instalados en su sistema
- Git para clonar el repositorio

### Pasos de Instalación

1. Clone el repositorio:

```sh
git clone [url-del-repositorio]
cd lumen
```

2. Configuración del entorno:

   **Backend (.env)**
   
   Copie el archivo de ejemplo y configure las variables de entorno:

   ```sh
   cd backend
   cp .env.example .env
   ```
   
   Edite el archivo `.env` con la siguiente configuración básica:

   ```
   APP_NAME=Lumen
   APP_ENV=local
   APP_KEY=base64:your-key-here
   APP_DEBUG=true
   APP_URL=http://localhost:8000
   
   DB_CONNECTION=mysql
   DB_HOST=db
   DB_PORT=3306
   DB_DATABASE=laravel
   DB_USERNAME=laravel
   DB_PASSWORD=password
   ```

   **Frontend (.env)**
   
   Asegúrese de que el archivo `.env` en la carpeta frontend contenga:

   ```
   VITE_API_URL=http://localhost:8000
   ```

3. Inicie los contenedores Docker desde la raíz del proyecto:

   ```sh
   cd ..
   docker-compose build
   docker-compose up
   ```

4. Acceda a la aplicación:
   - Frontend: http://localhost:8080
   - API Backend: http://localhost:8000

## Uso de la Aplicación

- Para acceder a la web, visite http://localhost:8080/
- Credenciales por defecto: test2@example.com / password123

## Comandos Útiles

- Detener los contenedores: `docker-compose down`
- Ver logs: `docker-compose logs -f`
- Acceder al shell del contenedor: `docker exec -it lumen_app bash`

## Enlace al Despliegue Público

[Enlace a la aplicación en producción](https://lumen-5x8j.onrender.com/landing)

## Licencia

Este proyecto está licenciado bajo la Licencia MIT - vea el archivo LICENSE para más detalles.

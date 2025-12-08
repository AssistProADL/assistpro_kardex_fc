# Configuración de Base de Datos para Producción

## 📋 Opciones de Configuración

Tu aplicación lee la configuración de BD desde `app/db.php` en este orden:
1. Variables de entorno (`DB_HOST`, `DB_NAME`, etc.)
2. Archivo `app/db.local.ini`
3. Constantes por defecto

## 🐳 Opción 1: Variables de Entorno (Recomendado)

### En el Servidor (antes de deploy)

Configura las variables de entorno en el servidor:

```bash
# SSH al servidor
ssh root@212.56.46.7

# Crear/editar archivo de variables
nano /root/.env.assistpro

# Agregar:
export DB_HOST=tu-servidor-mysql.com
export DB_PORT=3306
export DB_NAME=assistpro_etl_fc
export DB_USER=tu_usuario
export DB_PASS=tu_password_seguro
export DB_CHARSET=utf8mb4
export DB_TIMEZONE=-06:00

# Cargar variables
source /root/.env.assistpro

# Agregar al .bashrc para que persistan
echo "source /root/.env.assistpro" >> ~/.bashrc
```

### Jenkinsfile ya configurado

El `Jenkinsfile` ya está configurado para leer estas variables del servidor y pasarlas al contenedor.

## 🐳 Opción 2: Docker Secrets (Más Seguro)

Si usas Docker Swarm:

```bash
# Crear secrets
echo "tu-servidor-mysql.com" | docker secret create db_host -
echo "assistpro_etl_fc" | docker secret create db_name -
echo "tu_usuario" | docker secret create db_user -
echo "tu_password_seguro" | docker secret create db_pass -

# Modificar Jenkinsfile para usar secrets
```

## 🐳 Opción 3: Archivo .env en el Servidor

```bash
# En el servidor
ssh root@212.56.46.7

# Crear archivo de configuración
mkdir -p /opt/assistpro-config
cat > /opt/assistpro-config/db.env << EOF
DB_HOST=tu-servidor-mysql.com
DB_PORT=3306
DB_NAME=assistpro_etl_fc
DB_USER=tu_usuario
DB_PASS=tu_password_seguro
DB_CHARSET=utf8mb4
DB_TIMEZONE=-06:00
EOF

# Proteger el archivo
chmod 600 /opt/assistpro-config/db.env
```

Luego modifica el Jenkinsfile para montar este archivo:

```groovy
sh """ssh -o StrictHostKeyChecking=no -l ${user} ${serverIP} \
    'docker run -d --pull=always \
    --name ${containerName} \
    -p ${port}:7071 \
    --env-file /opt/assistpro-config/db.env \
    --restart unless-stopped \
    ${DOCKER_USER}/${PROJECT_NAME}:latest'"""
```

## 🔒 Opción 4: Archivo db.local.ini (Menos Recomendado)

Crear `app/db.local.ini` en el servidor y montarlo como volumen:

```bash
# En el servidor
mkdir -p /opt/assistpro-config
cat > /opt/assistpro-config/db.local.ini << EOF
host = tu-servidor-mysql.com
port = 3306
name = assistpro_etl_fc
user = tu_usuario
pass = tu_password_seguro
charset = utf8mb4
timezone = -06:00
EOF

chmod 600 /opt/assistpro-config/db.local.ini
```

Modificar Jenkinsfile:

```groovy
sh """ssh -o StrictHostKeyChecking=no -l ${user} ${serverIP} \
    'docker run -d --pull=always \
    --name ${containerName} \
    -p ${port}:7071 \
    -v /opt/assistpro-config/db.local.ini:/var/www/html/assistpro_kardex_fc/app/db.local.ini:ro \
    --restart unless-stopped \
    ${DOCKER_USER}/${PROJECT_NAME}:latest'"""
```

## ✅ Recomendación

**Usa Opción 1 (Variables de Entorno)** porque:
- ✅ Ya está configurado en el Jenkinsfile
- ✅ No requiere archivos adicionales
- ✅ Fácil de cambiar sin reconstruir imagen
- ✅ Compatible con CI/CD

## 🧪 Verificar Conexión

Después del deploy, verifica la conexión:

```bash
# Ver logs del contenedor
ssh root@212.56.46.7
docker logs assistpro-kardex-fc -f

# Entrar al contenedor y probar conexión
docker exec -it assistpro-kardex-fc bash
php -r "require 'app/db.php'; var_dump(db_config());"
```

## 🔐 Seguridad

- ⚠️ **NUNCA** commitees credenciales en Git
- ✅ Usa contraseñas fuertes
- ✅ Restringe acceso de red a MySQL (firewall)
- ✅ Usa usuario MySQL con permisos mínimos necesarios
- ✅ Habilita SSL/TLS para conexión MySQL si es posible

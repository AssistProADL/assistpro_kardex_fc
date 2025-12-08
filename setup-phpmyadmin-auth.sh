#!/bin/bash
# Script para configurar autenticación HTTP en PhpMyAdmin

# Crear archivo .htpasswd con usuario: admin / password: admin123
docker exec assistpro-phpmyadmin-dev sh -c "apt-get update && apt-get install -y apache2-utils"
docker exec assistpro-phpmyadmin-dev htpasswd -bc /etc/phpmyadmin/.htpasswd admin admin123

# Configurar Apache para usar autenticación
docker exec assistpro-phpmyadmin-dev sh -c 'cat > /etc/apache2/conf-available/phpmyadmin-auth.conf << EOF
<Directory /var/www/html>
    AuthType Basic
    AuthName "PhpMyAdmin - Acceso Restringido"
    AuthUserFile /etc/phpmyadmin/.htpasswd
    Require valid-user
</Directory>
EOF'

docker exec assistpro-phpmyadmin-dev a2enconf phpmyadmin-auth
docker exec assistpro-phpmyadmin-dev service apache2 reload

echo "✅ Autenticación configurada en PhpMyAdmin"
echo "   Usuario: admin"
echo "   Password: admin123"

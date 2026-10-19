
#!/bin/sh
set -eu

echo "[inicio] Preparando la base de datos..."

php /var/www/html/docker/init-db.php

echo "[inicio] Base de datos lista. Iniciando servidor PHP..."

exec php -S "0.0.0.0:${PORT:-8080}" -t /var/www/html

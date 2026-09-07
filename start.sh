#!/bin/bash
set -e

PORT="${PORT:-80}"

# Ensure only mpm_prefork is active
a2dismod mpm_event mpm_worker 2>/dev/null || true
a2enmod mpm_prefork 2>/dev/null || true

# Clean stale PID file if any
rm -f /var/run/apache2/apache2.pid

# Configure port dynamically for Railway
sed -i "s/Listen [0-9]*/Listen $PORT/g" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost *:$PORT>/g" /etc/apache2/sites-available/000-default.conf

# Configure reverse proxy friendliness
grep -q "UseCanonicalPhysicalPort Off" /etc/apache2/apache2.conf || cat << 'EOF' >> /etc/apache2/apache2.conf
ServerName localhost
UseCanonicalName Off
UseCanonicalPhysicalPort Off
EOF

exec apache2-foreground


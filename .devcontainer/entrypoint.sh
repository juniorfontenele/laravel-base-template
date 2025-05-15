#!/bin/bash
set -e

# Configurar o crontab para Laravel Scheduler (apenas para o worker)
echo "📅 Configurando Laravel Scheduler..."
    
# Criar arquivo crontab
echo "* * * * * sail cd /app && /usr/local/bin/php /app/artisan schedule:run >> /dev/null 2>&1" > /etc/cron.d/laravel-scheduler
chmod 644 /etc/cron.d/laravel-scheduler

echo "✅ Laravel Scheduler configurado com sucesso"

echo "sleep 10 && cd /app && php artisan app:started" > /tmp/startup.sh
chmod +x /tmp/startup.sh
/tmp/startup.sh &

echo "🚀 Iniciando app..."
exec /usr/sbin/cron -f

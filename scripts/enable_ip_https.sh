#!/bin/bash
set -e

IP_ADDRESS="${1:-$(curl -4fsS https://api.ipify.org)}"
EMAIL="${2:-}"
SITE_CONF="/etc/nginx/sites-available/minecraft-panel"
CERTBOT_DIR="/opt/certbot-ip"
CERTBOT="$CERTBOT_DIR/bin/certbot"

if ! [[ "$IP_ADDRESS" =~ ^[0-9]{1,3}(\.[0-9]{1,3}){3}$ ]]; then
    echo "Error: A public IPv4 address is required for IP HTTPS."
    exit 1
fi

echo "Installing current Certbot for short-lived IP certificates..."
apt install -y python3-venv
python3 -m venv "$CERTBOT_DIR"
"$CERTBOT_DIR/bin/pip" install --quiet --upgrade pip "certbot>=5.4"

mkdir -p /var/www/.well-known/acme-challenge
if ! grep -q '\.well-known/acme-challenge' "$SITE_CONF"; then
    TMP_CONF=$(mktemp)
    awk '
        index($0, "location ~ /\\.") && !added {
            print "    location ^~ /.well-known/acme-challenge/ {"
            print "        root /var/www/;"
            print "        allow all;"
            print "    }"
            print ""
            added=1
        }
        { print }
    ' "$SITE_CONF" > "$TMP_CONF"
    install -m 644 "$TMP_CONF" "$SITE_CONF"
    rm -f "$TMP_CONF"
    nginx -t
    systemctl reload nginx
fi

if [ -n "$EMAIL" ]; then
    REGISTER_ARGS=(--email "$EMAIL")
else
    REGISTER_ARGS=(--register-unsafely-without-email)
fi

"$CERTBOT" certonly --non-interactive --agree-tos "${REGISTER_ARGS[@]}" \
    --preferred-profile shortlived --webroot --webroot-path /var/www \
    --ip-address "$IP_ADDRESS"

if ! grep -q 'listen 443 ssl' "$SITE_CONF"; then
    sed -i "/listen 80;/a\\    listen 443 ssl;\\n    ssl_certificate /etc/letsencrypt/live/$IP_ADDRESS/fullchain.pem;\\n    ssl_certificate_key /etc/letsencrypt/live/$IP_ADDRESS/privkey.pem;" "$SITE_CONF"
fi

cat > /etc/systemd/system/minecraft-panel-certbot-ip.service <<EOF
[Unit]
Description=Renew Minecraft Panel IP HTTPS certificate

[Service]
Type=oneshot
ExecStart=$CERTBOT renew --quiet --no-random-sleep-on-renew --deploy-hook "systemctl reload nginx"
EOF

cat > /etc/systemd/system/minecraft-panel-certbot-ip.timer <<EOF
[Unit]
Description=Check Minecraft Panel IP HTTPS certificate renewal daily

[Timer]
OnCalendar=*-*-* 04:20:00
RandomizedDelaySec=30m
Persistent=true

[Install]
WantedBy=timers.target
EOF

systemctl daemon-reload
systemctl enable --now minecraft-panel-certbot-ip.timer
nginx -t
systemctl reload nginx
echo "HTTPS enabled at https://$IP_ADDRESS with automatic short-lived certificate renewal."

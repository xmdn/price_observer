#!/bin/sh
set -e

DOMAIN="united2.strangled.net"
CERT_PATH="/etc/letsencrypt/live/$DOMAIN/fullchain.pem"

# Check if the certificate already exists
if [ ! -f "$CERT_PATH" ]; then
  echo "No certificate found for $DOMAIN. Requesting a new one..."
  certbot certonly \
    --webroot \
    --webroot-path /var/www/html/public \
    -d "$DOMAIN" \
    --email tnebilyk@gmail.com \
    --agree-tos \
    --no-eff-email \
    --non-interactive
else
  echo "Certificate for $DOMAIN already exists. Skipping certonly..."
  # Optionally attempt a renewal if you want to keep it fresh:
  # certbot renew --webroot --webroot-path /var/www/html/public --no-eff-email --non-interactive
fi

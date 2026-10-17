#!/bin/sh
set -eu

if [ -z "${MYSQL_CA_CERT_BASE64:-}" ]; then
    echo "MYSQL_CA_CERT_BASE64 must contain the database provider's CA certificate." >&2
    exit 1
fi

printf '%s' "$MYSQL_CA_CERT_BASE64" | base64 --decode > /tmp/mysql-ca.pem
chmod 600 /tmp/mysql-ca.pem
export MYSQL_ATTR_SSL_CA=/tmp/mysql-ca.pem

exec frankenphp run --config /etc/frankenphp/Caddyfile

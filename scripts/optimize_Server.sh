#!/bin/bash
set -e

PROPERTIES="/home/minecraft/Server/server.properties"
CPU_THREADS=$(nproc)

if [ ! -f "$PROPERTIES" ]; then
    echo "server.properties not found; skipping Bedrock optimization."
    exit 0
fi

set_property() {
    local key="$1"
    local value="$2"
    if grep -q "^${key}=" "$PROPERTIES"; then
        sed -i "s/^${key}=.*/${key}=${value}/" "$PROPERTIES"
    else
        printf '%s=%s\n' "$key" "$value" >> "$PROPERTIES"
    fi
}

# Conservative defaults for smaller VPS hosts: lower chunk transmission cost while retaining playability.
set_property view-distance 16
set_property tick-distance 4
set_property max-threads "$CPU_THREADS"

echo "Applied Bedrock performance defaults: view-distance=16, tick-distance=4, max-threads=$CPU_THREADS."

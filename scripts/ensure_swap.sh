#!/bin/bash
set -e

SWAP_FILE="/swapfile"
SWAP_SIZE="2G"

if swapon --show --noheadings | grep -q .; then
    echo "Swap is already configured; no additional swap file created."
else
    echo "Creating ${SWAP_SIZE} swap file at ${SWAP_FILE}..."
    fallocate -l "$SWAP_SIZE" "$SWAP_FILE" || dd if=/dev/zero of="$SWAP_FILE" bs=1M count=2048 status=progress
    chmod 600 "$SWAP_FILE"
    mkswap "$SWAP_FILE"
    swapon "$SWAP_FILE"
    grep -qF "$SWAP_FILE none swap sw 0 0" /etc/fstab || echo "$SWAP_FILE none swap sw 0 0" >> /etc/fstab
fi

sysctl vm.swappiness=10
cat > /etc/sysctl.d/99-minecraft-panel.conf <<EOF
vm.swappiness=10
EOF
echo "Swap tuning configured successfully."

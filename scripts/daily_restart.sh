#!/bin/bash
set -e

ROOT_DIR="/home/minecraft"
MANAGE_SCRIPT="$ROOT_DIR/manage_screen.sh"
LOG_FILE="$ROOT_DIR/minecraft.log"

if [ "$(date -u '+%H%M')" != "1200" ]; then
    exit 0
fi

echo "[$(date -u '+%Y-%m-%d %H:%M:%S UTC')] Scheduled daily restart started." >> "$LOG_FILE"

if ! "$MANAGE_SCRIPT" status | grep -q "Online"; then
    echo "[$(date -u '+%Y-%m-%d %H:%M:%S UTC')] Server is offline; scheduled restart skipped." >> "$LOG_FILE"
    exit 0
fi

screen -S bedrock -p 0 -X stuff "say Server restarting in 60 seconds for daily maintenance.$(printf '\r')" || true
sleep 60
"$MANAGE_SCRIPT" restart >> "$LOG_FILE" 2>&1
echo "[$(date -u '+%Y-%m-%d %H:%M:%S UTC')] Scheduled daily restart completed." >> "$LOG_FILE"

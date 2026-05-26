#!/bin/bash
# File: /home/minecraft/bedrock_restart.sh
LOG_FILE="/home/minecraft/minecraft.log"
SCREEN_NAME="bedrock"
SERVER_DIR="/home/minecraft/Server"

echo "[$(date '+%Y-%m-%d %H:%M:%S')] Restart Script Started" >> "$LOG_FILE"

if [[ ! -x "$SERVER_DIR/bedrock_server" ]]; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Server binary not found. Install Minecraft Server before starting." >> "$LOG_FILE"
    exit 1
fi

# Kill any existing server
screen -S "$SCREEN_NAME" -X quit 2>/dev/null

# Start server in detached screen
screen -dmS "$SCREEN_NAME" bash -c "cd $SERVER_DIR && LD_LIBRARY_PATH=. ./bedrock_server >> /home/minecraft/minecraft.log 2>&1"

echo "[$(date '+%Y-%m-%d %H:%M:%S')] Server Restarted" >> "$LOG_FILE"

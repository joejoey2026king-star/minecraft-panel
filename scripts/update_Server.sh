#!/bin/bash
set -e

ROOT_DIR="/home/minecraft"
SERVER_DIR="$ROOT_DIR/Server"
LOG_FILE="$ROOT_DIR/minecraft.log"
BACKUP_DIR="$ROOT_DIR/BackupWorlds"
DOWNLOAD_API="https://net-secondary.web.minecraft-services.net/api/v1.0/download/links"
TMP_DIR=$(mktemp -d)
API_FILE="$TMP_DIR/download-links.json"
FORCE_REINSTALL=false

if [ "${1:-}" = "--force" ]; then
    FORCE_REINSTALL=true
fi

cleanup() {
    rm -rf "$TMP_DIR"
}
trap cleanup EXIT

curl --http1.1 -fsSL --retry 5 --retry-all-errors --retry-delay 3 \
    --connect-timeout 20 --max-time 120 "$DOWNLOAD_API" -o "$API_FILE"
DOWNLOAD_URL=$(php -r '
$data = json_decode(file_get_contents($argv[1]), true);
foreach (($data["result"]["links"] ?? []) as $link) {
    if (($link["downloadType"] ?? "") === "serverBedrockLinux") {
        echo $link["downloadUrl"];
        exit(0);
    }
}
exit(1);
' "$API_FILE")

if [ -z "$DOWNLOAD_URL" ]; then
    echo "Unable to resolve the current Bedrock server download."
    exit 1
fi

LATEST_VERSION=$(printf '%s' "$DOWNLOAD_URL" | sed -n 's/.*bedrock-server-\([0-9.]*\)\.zip.*/\1/p')
CURRENT_VERSION=$(cat "$SERVER_DIR/.bedrock-version" 2>/dev/null || true)
if [ -z "$CURRENT_VERSION" ]; then
    CURRENT_VERSION=$(grep 'Version:' "$LOG_FILE" 2>/dev/null | tail -1 | sed -n 's/.*Version: \([0-9.]*\).*/\1/p')
fi

if [ "$FORCE_REINSTALL" = false ] && [ -n "$CURRENT_VERSION" ] && [ "$CURRENT_VERSION" = "$LATEST_VERSION" ]; then
    echo "Minecraft Bedrock Server is already up to date ($CURRENT_VERSION)."
    exit 0
fi

if [ "$FORCE_REINSTALL" = true ]; then
    echo "Preparing protected reinstall of Minecraft Bedrock Server ${LATEST_VERSION:-latest}..."
else
    echo "Preparing update to Minecraft Bedrock Server ${LATEST_VERSION:-latest}..."
fi
# minecraft.net may leave curl requests waiting without data; wget is reliable for this archive endpoint.
wget -4 --tries=10 --timeout=60 --retry-connrefused \
    -O "$TMP_DIR/bedrock.zip" "$DOWNLOAD_URL"
unzip -tq "$TMP_DIR/bedrock.zip" >/dev/null

for file in server.properties allowlist.json permissions.json; do
    if [ -f "$SERVER_DIR/$file" ]; then
        cp -p "$SERVER_DIR/$file" "$TMP_DIR/$file"
    fi
done

if [ -x "$ROOT_DIR/manage_screen.sh" ]; then
    "$ROOT_DIR/manage_screen.sh" stop || true
fi

STAMP=$(date '+%Y%m%d-%H%M%S')
BACKUP_PATH="$BACKUP_DIR/before-server-change-$STAMP"
mkdir -p "$BACKUP_PATH"

for file in server.properties allowlist.json permissions.json; do
    if [ -f "$SERVER_DIR/$file" ]; then
        cp -p "$SERVER_DIR/$file" "$BACKUP_PATH/$file"
    fi
done

if [ -d "$SERVER_DIR/worlds" ]; then
    echo "Backing up worlds to $BACKUP_PATH/worlds.tar.gz..."
    tar -czf "$BACKUP_PATH/worlds.tar.gz" -C "$SERVER_DIR" worlds
fi

unzip -oq "$TMP_DIR/bedrock.zip" -d "$SERVER_DIR"

for file in server.properties allowlist.json permissions.json; do
    if [ -f "$TMP_DIR/$file" ]; then
        cp -p "$TMP_DIR/$file" "$SERVER_DIR/$file"
    fi
done

chmod +x "$SERVER_DIR/bedrock_server"
printf '%s\n' "$LATEST_VERSION" > "$SERVER_DIR/.bedrock-version"

if [ -x "$ROOT_DIR/manage_screen.sh" ]; then
    "$ROOT_DIR/manage_screen.sh" start
fi

if [ "$FORCE_REINSTALL" = true ]; then
    echo "Minecraft Bedrock Server reinstalled safely at ${LATEST_VERSION:-the latest release}."
else
    echo "Minecraft Bedrock Server updated to ${LATEST_VERSION:-the latest release}."
fi
echo "World backup saved to $BACKUP_PATH."

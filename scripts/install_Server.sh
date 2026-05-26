#!/bin/bash
set -e
ROOT_DIR="/home/minecraft"
SERVER_DIR="$ROOT_DIR/Server"
DOWNLOAD_API="https://net-secondary.web.minecraft-services.net/api/v1.0/download/links"
API_FILE=$(mktemp)

cleanup() {
    rm -f "$API_FILE"
}
trap cleanup EXIT

mkdir -p "$SERVER_DIR"
cd "$SERVER_DIR"

echo "Resolving current Minecraft Bedrock download..."
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

if [ -f .bedrock-download-url ] && [ "$(cat .bedrock-download-url)" != "$DOWNLOAD_URL" ]; then
    rm -f bedrock.zip
fi
printf '%s\n' "$DOWNLOAD_URL" > .bedrock-download-url

echo "Downloading Minecraft Bedrock server package (retries and resume enabled)..."
# minecraft.net may leave curl requests waiting without data; wget is reliable for this archive endpoint.
wget -4 --continue --tries=10 --timeout=60 --retry-connrefused \
    -O bedrock.zip "$DOWNLOAD_URL"
unzip -tq bedrock.zip >/dev/null
unzip -oq bedrock.zip
rm -f bedrock.zip .bedrock-download-url
chmod +x "$SERVER_DIR/bedrock_server"
LATEST_VERSION=$(printf '%s' "$DOWNLOAD_URL" | sed -n 's/.*bedrock-server-\([0-9.]*\)\.zip.*/\1/p')
printf '%s\n' "$LATEST_VERSION" > "$SERVER_DIR/.bedrock-version"
echo "[$(date)] Installation completed in $SERVER_DIR"

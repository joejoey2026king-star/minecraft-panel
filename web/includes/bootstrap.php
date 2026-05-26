<?php
session_start();

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit;
}

if (!isset($_SESSION['logged_in'])) {
    header('Location: login.php');
    exit;
}

const SERVER_DIR = '/home/minecraft/Server';
const BACKUP_DIR = '/home/minecraft/BackupWorlds';
const LOG_FILE = '/home/minecraft/minecraft.log';
const MANAGE_SCRIPT = '/home/minecraft/manage_screen.sh';

$message = '';
$messageType = 'success';
if (isset($_SESSION['panel_toast']) && is_array($_SESSION['panel_toast'])) {
    $message = (string)($_SESSION['panel_toast']['message'] ?? '');
    $messageType = (string)($_SESSION['panel_toast']['type'] ?? 'success');
    unset($_SESSION['panel_toast']);
}
$gamerules = [
    'announceAdvancements', 'commandBlockOutput', 'doDaylightCycle',
    'doEntityDrops', 'doFireTick', 'doMobLoot', 'doMobSpawning',
    'doWeatherCycle', 'fallDamage', 'fireDamage', 'keepInventory',
    'mobGriefing', 'naturalRegeneration', 'pvp', 'showCoordinates',
    'showDeathMessages', 'spawnRadius', 'tntExplodes', 'disableInsomnia'
];

function run_panel_command(string $command): string {
    exec($command . ' 2>&1', $output, $code);
    return trim(implode("\n", $output));
}

function manage_server(string $action): string {
    if (!in_array($action, ['start', 'stop', 'restart', 'status'], true)) {
        return 'Invalid server action.';
    }
    return run_panel_command(escapeshellarg(MANAGE_SCRIPT) . ' ' . escapeshellarg($action));
}

function server_online(): bool {
    return trim(manage_server('status')) === 'Online';
}

function log_action(string $message): void {
    if ($message !== '') {
        file_put_contents(LOG_FILE, '[' . date('Y-m-d H:i:s') . "] $message\n", FILE_APPEND | LOCK_EX);
    }
}

function server_port(): int {
    $properties = @file(SERVER_DIR . '/server.properties', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($properties ?: [] as $line) {
        if (preg_match('/^\s*server-port\s*=\s*(\d+)\s*$/', $line, $matches)) {
            return (int)$matches[1];
        }
    }
    return 19132;
}

function server_ipv4(): string {
    foreach (preg_split('/\s+/', trim((string)shell_exec('hostname -I'))) as $address) {
        if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return $address;
        }
    }
    return $_SERVER['SERVER_ADDR'] ?? '127.0.0.1';
}

function server_address(): string {
    return server_ipv4() . ':' . server_port();
}

function server_property(string $key, string $default = ''): string {
    $properties = @file(SERVER_DIR . '/server.properties', FILE_IGNORE_NEW_LINES);
    foreach ($properties ?: [] as $line) {
        if (preg_match('/^\s*' . preg_quote($key, '/') . '\s*=\s*(.*)\s*$/', $line, $matches)) {
            return trim($matches[1]);
        }
    }
    return $default;
}

function update_server_properties(array $values): bool {
    $path = SERVER_DIR . '/server.properties';
    $content = is_readable($path) ? (string)file_get_contents($path) : '';
    foreach ($values as $key => $value) {
        $line = $key . '=' . $value;
        $pattern = '/^\s*' . preg_quote($key, '/') . '\s*=.*$/m';
        if (preg_match($pattern, $content)) {
            $content = preg_replace($pattern, $line, $content, 1);
        } else {
            $content = rtrim($content) . ($content === '' ? '' : "\n") . $line . "\n";
        }
    }
    return file_put_contents($path, $content, LOCK_EX) !== false;
}

function installed_version(): ?string {
    $versionFile = SERVER_DIR . '/.bedrock-version';
    if (is_readable($versionFile)) {
        $version = trim((string)file_get_contents($versionFile));
        if (preg_match('/^[0-9]+(?:\.[0-9]+)+$/', $version)) {
            return $version;
        }
    }
    foreach (array_reverse(@file(LOG_FILE, FILE_IGNORE_NEW_LINES) ?: []) as $line) {
        if (preg_match('/Version:\s+([0-9.]+)/', $line, $matches)) {
            return $matches[1];
        }
    }
    return null;
}

function latest_version(): ?string {
    $api = 'https://net-secondary.web.minecraft-services.net/api/v1.0/download/links';
    $context = stream_context_create(['http' => ['timeout' => 5]]);
    $data = json_decode((string)@file_get_contents($api, false, $context), true);
    foreach (($data['result']['links'] ?? []) as $link) {
        if (($link['downloadType'] ?? '') === 'serverBedrockLinux' &&
            preg_match('/bedrock-server-([0-9.]+)\.zip/', $link['downloadUrl'] ?? '', $matches)) {
            return $matches[1];
        }
    }
    return null;
}

function format_bytes(float $bytes): string {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $unit = 0;
    while ($bytes >= 1024 && $unit < count($units) - 1) {
        $bytes /= 1024;
        $unit++;
    }
    return round($bytes, 2) . ' ' . $units[$unit];
}

function uptime_text(): string {
    if (!is_readable('/proc/uptime')) {
        return 'N/A';
    }
    $seconds = (int)explode(' ', (string)file_get_contents('/proc/uptime'))[0];
    return floor($seconds / 86400) . 'd ' . floor(($seconds % 86400) / 3600) . 'h ' .
        floor(($seconds % 3600) / 60) . 'm';
}

function memory_text(): string {
    $data = (string)shell_exec('free -m');
    if (preg_match('/^Mem:\s+(\d+)\s+(\d+)/m', $data, $match)) {
        return $match[2] . ' MB / ' . $match[1] . ' MB';
    }
    return 'N/A';
}

function send_server_input(string $command): bool {
    if (!server_online()) {
        return false;
    }
    $payload = preg_replace('/[\r\n]+/', ' ', $command) . "\r";
    exec('screen -S bedrock -p 0 -X stuff ' . escapeshellarg($payload), $output, $code);
    return $code === 0;
}

function panel_message_type(string $message): string {
    return preg_match('/\b(error|fail(?:ed|ure)?|invalid|unable|cannot|not found|not running|denied)\b/i', $message) ||
        preg_match('/^Server is offline\.$/i', trim($message))
        ? 'error'
        : 'success';
}

function process_panel_action(): string {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action'])) {
        return '';
    }
    switch ($_POST['action']) {
        case 'start':
        case 'stop':
        case 'restart':
            $result = manage_server($_POST['action']);
            log_action($result);
            return $result;
        case 'backup':
            $result = run_panel_command('/home/minecraft/autobackup.sh');
            log_action($result);
            return $result ?: 'Backup completed.';
        case 'clean':
            $result = run_panel_command('/home/minecraft/bedrock-clean.sh');
            log_action($result);
            return $result ?: 'Cleanup completed.';
        case 'clear_console':
            file_put_contents(LOG_FILE, '');
            return 'Console cleared.';
        case 'command':
            $text = trim((string)($_POST['command'] ?? ''));
            if ($text === '') {
                return 'Message cannot be empty.';
            }
            return send_server_input('say ' . $text) ? 'Message sent to players.' : 'Server is offline.';
        case 'gamerule':
            $rule = (string)($_POST['gamerule'] ?? '');
            $value = trim((string)($_POST['value'] ?? ''));
            global $gamerules;
            if (!in_array($rule, $gamerules, true) || !preg_match('/^(true|false|-?\d+)$/', $value)) {
                return 'Invalid gamerule value.';
            }
            return send_server_input("gamerule $rule $value") ? 'Gamerule updated.' : 'Server is offline.';
        case 'save_properties':
            $content = (string)($_POST['properties'] ?? '');
            return file_put_contents(SERVER_DIR . '/server.properties', $content) !== false
                ? 'Server properties saved. Restart to apply changes.'
                : 'Unable to save server properties.';
        case 'save_startup':
            $gamemode = (string)($_POST['gamemode'] ?? '');
            $difficulty = (string)($_POST['difficulty'] ?? '');
            $allowCheats = isset($_POST['allow_cheats']) ? 'true' : 'false';
            $maxPlayers = filter_input(INPUT_POST, 'max_players', FILTER_VALIDATE_INT);
            $serverPort = filter_input(INPUT_POST, 'server_port', FILTER_VALIDATE_INT);
            $viewDistance = filter_input(INPUT_POST, 'view_distance', FILTER_VALIDATE_INT);
            $tickDistance = filter_input(INPUT_POST, 'tick_distance', FILTER_VALIDATE_INT);
            if (!in_array($gamemode, ['survival', 'creative', 'adventure'], true) ||
                !in_array($difficulty, ['peaceful', 'easy', 'normal', 'hard'], true) ||
                $maxPlayers === false || $maxPlayers === null || $maxPlayers < 1 || $maxPlayers > 1000 ||
                $serverPort === false || $serverPort === null || $serverPort < 1 || $serverPort > 65535 ||
                $viewDistance === false || $viewDistance === null || $viewDistance < 5 || $viewDistance > 96 ||
                $tickDistance === false || $tickDistance === null || $tickDistance < 4 || $tickDistance > 12) {
                return 'Invalid startup settings.';
            }
            $saved = update_server_properties([
                'gamemode' => $gamemode,
                'difficulty' => $difficulty,
                'allow-cheats' => $allowCheats,
                'max-players' => (string)$maxPlayers,
                'server-port' => (string)$serverPort,
                'view-distance' => (string)$viewDistance,
                'tick-distance' => (string)$tickDistance
            ]);
            return $saved
                ? 'Startup settings saved. Restart the server to apply changes.'
                : 'Unable to save startup settings.';
        case 'update':
            return run_panel_command('/home/minecraft/update_Server.sh');
        case 'reinstall':
            return run_panel_command('/home/minecraft/update_Server.sh --force');
        case 'install':
            return run_panel_command('/home/minecraft/install_Server.sh');
        case 'uninstall':
            return run_panel_command('/home/minecraft/uninstall.sh');
        default:
            return 'Unknown action.';
    }
}

$actionMessage = process_panel_action();
if ($actionMessage !== '') {
    $message = $actionMessage;
    $messageType = panel_message_type($actionMessage);
}

<?PHP
session_start();
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

function getServerDefaults()
{
    return array(
        'server_name' => 'Globe Server',
        'server_ip' => '120.28.196.113',
        'remote_url' => 'storeupdate.rosebakeshops.com',
        'default_server_name' => 'Globe Server',
        'default_server_ip' => '120.28.196.113',
        'backup_server_name' => 'Converge Server',
        'backup_server_ip' => '161.49.101.171',
        'auto_switch' => 1
    );
}

function getServerConfigPath()
{
    return dirname(__DIR__) . DIRECTORY_SEPARATOR . '.file' . DIRECTORY_SEPARATOR . 'server.rms';
}

function readServerConfig()
{
    $defaults = getServerDefaults();
    $configPath = getServerConfigPath();

    if (file_exists($configPath)) {
        $array = @file_get_contents($configPath);
        if ($array !== false) {
            $data = json_decode($array, true);
            if (is_array($data) && isset($data[0]) && is_array($data[0])) {
                return array_merge($defaults, $data[0]);
            }
        }
    }

    return $defaults;
}

function writeServerConfig($config)
{
    $payload = json_encode(array($config));
    if ($payload !== false) {
        @file_put_contents(getServerConfigPath(), $payload, LOCK_EX);
    }
}

function canReachServer($host, $ports = array(80, 13306), $timeout = 1)
{
    $host = trim((string)$host);
    if ($host === '') {
        return false;
    }

    foreach ($ports as $port) {
        $connected = @fsockopen($host, $port, $errno, $errstr, $timeout);
        if ($connected) {
            fclose($connected);
            return true;
        }
    }

    return false;
}

function resolveServerState()
{
    $config = readServerConfig();
    $primaryName = !empty($config['default_server_name']) ? $config['default_server_name'] : 'Globe Server';
    $primaryIp = !empty($config['default_server_ip']) ? $config['default_server_ip'] : '120.28.196.113';
    $backupName = !empty($config['backup_server_name']) ? $config['backup_server_name'] : 'Converge Server';
    $backupIp = !empty($config['backup_server_ip']) ? $config['backup_server_ip'] : '161.49.101.171';
    $remoteUrl = !empty($config['remote_url']) ? $config['remote_url'] : 'storeupdate.rosebakeshops.com';

    $primaryOnline = canReachServer($primaryIp, array(80, 13306), 1);
    $backupOnline = canReachServer($backupIp, array(80, 13306), 1);

    $resolvedName = $primaryName;
    $resolvedIp = $primaryIp;
    $offlineMode = 1;
    $isOnline = 0;
    $isFailover = 0;
    $switchMode = 'offline-default';

    if ($primaryOnline) {
        $resolvedName = $primaryName;
        $resolvedIp = $primaryIp;
        $offlineMode = 0;
        $isOnline = 1;
        $isFailover = 0;
        $switchMode = 'default';
    } else if ($backupOnline) {
        $resolvedName = $backupName;
        $resolvedIp = $backupIp;
        $offlineMode = 0;
        $isOnline = 1;
        $isFailover = 1;
        $switchMode = 'failover';
    }

    $needsWrite = (
        !isset($config['default_server_ip']) ||
        !isset($config['backup_server_ip']) ||
        $config['server_name'] !== $resolvedName ||
        $config['server_ip'] !== $resolvedIp
    );

    $config['server_name'] = $resolvedName;
    $config['server_ip'] = $resolvedIp;
    $config['remote_url'] = $remoteUrl;
    $config['default_server_name'] = $primaryName;
    $config['default_server_ip'] = $primaryIp;
    $config['backup_server_name'] = $backupName;
    $config['backup_server_ip'] = $backupIp;
    $config['auto_switch'] = 1;
    $config['last_checked_at'] = date('Y-m-d H:i:s');
    $config['last_switch_mode'] = $switchMode;

    if ($needsWrite) {
        writeServerConfig($config);
    }

    $_SESSION['server'] = $resolvedName;
    $_SESSION['ACTIVE_SERVER_NAME'] = $resolvedName;
    $_SESSION['ACTIVE_SERVER_IP'] = $resolvedIp;
    $_SESSION['ACTIVE_REMOTE_URL'] = $remoteUrl;
    $_SESSION['IS_FAILOVER_ACTIVE'] = $isFailover;
    $_SESSION['OFFLINE_MODE'] = $offlineMode;
    $_SESSION['IS_ONLINE'] = $isOnline;
    $_SESSION['OFFLINE_MODE_CHECKED_AT'] = time();
    $_SESSION['AUTO_SERVER_CHECKED_AT'] = time();

    return array(
        'server_name' => $resolvedName,
        'server_ip' => $resolvedIp,
        'remote_url' => $remoteUrl,
        'offline_mode' => $offlineMode,
        'is_online' => $isOnline,
        'is_failover' => $isFailover
    );
}

$serverState = resolveServerState();
setServerStatus($serverState);
console($serverState['server_name'].' | '.$serverState['server_ip'].' | failover='.$serverState['is_failover']);

function setServerStatus($serverState)
{
    print_r('
        <script>
            sessionStorage.setItem("IS_ONLINE", '.intval($serverState['is_online']).');
            sessionStorage.setItem("OFFLINE_MODE", '.intval($serverState['offline_mode']).');
            sessionStorage.setItem("FAILOVER_ACTIVE", '.intval($serverState['is_failover']).');
            sessionStorage.setItem("ACTIVE_SERVER_NAME", '.json_encode($serverState['server_name']).');
            sessionStorage.setItem("ACTIVE_SERVER_IP", '.json_encode($serverState['server_ip']).');
            sessionStorage.setItem("ACTIVE_REMOTE_URL", '.json_encode($serverState['remote_url']).');
        </script>
    ');
}
function console($params)
{
    print_r('
        <script>
            console.log('.json_encode($params).')
        </script>
    ');
}
<?php
$config = @file_get_contents('/etc/ssh/sshd_config');
if ($config) {
    preg_match_all('/^Port\s+(\d+)/m', $config, $matches);
    echo "Config Ports: " . implode(', ', $matches[1]) . "\n";
} else {
    echo "Could not read sshd_config.\n";
}

$output = [];
@exec('ss -tlnp | grep ssh', $output);
echo "Active SSH Sockets:\n" . implode("\n", $output) . "\n";

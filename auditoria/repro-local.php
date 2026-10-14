<?php
// Testes isolados: HTTP e APIs WordPress simulados, sem rede ou credenciais reais.
define('ABSPATH', __DIR__);
define('HTGP_VERSION', '1.0.0');
class WP_Error { public function __construct(...$args) {} }
function __($s, $domain = '') { return $s; }
function is_wp_error($v) { return $v instanceof WP_Error; }
function get_option($name, $default = null) { return $GLOBALS['settings']; }
function wp_parse_url($url) { return parse_url($url); }
function wp_http_validate_url($url) { return strncmp($url, 'https://', 8) === 0 ? $url : false; }
function wp_json_encode($v) { return json_encode($v); }
function get_transient($key) { return $GLOBALS['cache'][$key] ?? false; }
function set_transient($key, $value, $ttl) { $GLOBALS['cache'][$key] = $value; }
function trailingslashit($v) { return rtrim($v, '/') . '/'; }
function add_query_arg($q, $url) { return $url . '?' . http_build_query($q); }
function wp_safe_remote_get($url, $args) {
    $GLOBALS['calls'][] = array('url' => $url, 'args' => $args);
    return array('status' => $GLOBALS['status'], 'body' => json_encode($GLOBALS['body']));
}
function wp_remote_retrieve_response_code($r) { return $r['status']; }
function wp_remote_retrieve_body($r) { return $r['body']; }
function do_action(...$args) {}
function sanitize_text_field($v) { return strip_tags($v); }
function esc_url_raw($v) { return $v; }
require __DIR__ . '/../codigo/htgithubforwp/includes/class-htgp-configuration.php';
require __DIR__ . '/../codigo/htgithubforwp/includes/class-htgp-github-client.php';
$settings = array('api_base_url' => 'https://collector.example', 'api_version' => '2022-11-28', 'user_agent' => 'synthetic-audit', 'timeout_seconds' => 5, 'response_size' => 100000, 'cache_ttl_seconds' => 300, 'api_page_size' => 100, 'max_owner_length' => 39, 'max_include_length' => 1000, 'max_repo_length' => 100);
putenv('HTGP_GITHUB_TOKEN=synthetic-not-a-real-token');
$cache = array(); $calls = array(); $status = 200;
$body = array(array('name' => 'demo', 'html_url' => 'https://github.com/demo/demo', 'private' => false));
function check($condition, $label) { if (!$condition) { throw new RuntimeException($label); } echo "PASS: $label\n"; }
$client = new HTGP_GitHub_Client(new HTGP_Configuration());
$client->repositories('demo', array());
check($calls[0]['url'] === 'https://collector.example/users/demo/repos?' && isset($calls[0]['args']['headers']['Authorization']), 'token anexado ao endpoint customizado (HTTP simulado)');
$settings['api_base_url'] = 'https://api.github.com';
$second = new HTGP_GitHub_Client(new HTGP_Configuration());
$second->repositories('demo', array());
check(count($calls) === 1, 'troca de endpoint reutiliza cache anterior');
$cache = array(); $body = array(array('name' => 'demo', 'html_url' => 'https://github.com/demo/demo'));
check(count($second->repositories('demo', array())) === 1, 'resposta sem campo private aceita como publica');
$cache = array(); $calls = array(); $status = 503;
$second->repositories('demo', array()); $second->repositories('demo', array());
check(count($calls) === 2, 'falhas consecutivas de listagem repetem HTTP');
$calls = array();
$second->latest_release('demo', 'demo'); $second->latest_release('demo', 'demo');
check(count($calls) === 2, 'falhas consecutivas de release repetem HTTP');
putenv('HTGP_GITHUB_TOKEN');

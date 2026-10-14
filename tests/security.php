<?php
// Offline regression tests; no WordPress database, real credentials or network.
define('ABSPATH', __DIR__);
define('HTGP_VERSION', '1.0.1');
class WP_Error { public $code; public function __construct($code, $message) { $this->code = $code; } }
function __($text, $domain = '') { return $text; }
function is_wp_error($value) { return $value instanceof WP_Error; }
function get_option($key, $default = false) { return $key === 'htgp_settings' ? $GLOBALS['settings'] : ($GLOBALS['options'][$key] ?? $default); }
function add_option($key, $value, $deprecated = '', $autoload = false) { if (isset($GLOBALS['options'][$key])) { return false; } $GLOBALS['options'][$key] = $value; return true; }
function maybe_serialize($v) { return serialize($v); }
function wp_cache_delete($key, $group) {}
class AuditDatabase {
    public $options = 'wp_options';
    public function prepare($sql, $key, $value, $autoload = null) { return array($key, $value, $autoload); }
    public function query($args) {
        if ($args[2] !== null) {
            if (isset($GLOBALS['options'][$args[0]])) { return 0; }
            $GLOBALS['options'][$args[0]] = unserialize($args[1]); return 1;
        }
        if (isset($GLOBALS['options'][$args[0]]) && serialize($GLOBALS['options'][$args[0]]) === $args[1]) { unset($GLOBALS['options'][$args[0]]); return 1; }
        return 0;
    }
}
$wpdb = new AuditDatabase();
function wp_generate_uuid4() { return uniqid('', true); }
function wp_hash($value) { return hash_hmac('sha256', $value, 'synthetic-test-salt'); }
function wp_parse_url($url) { return parse_url($url); }
function wp_http_validate_url($url) { return is_string($url) && strpos($url, 'https://') === 0 && !preg_match('/[\r\n]/', $url) ? $url : false; }
function wp_json_encode($v) { return json_encode($v); }
function get_transient($key) { $v = $GLOBALS['cache'][$key] ?? null; return $v && $v['expires'] > microtime(true) ? $v['value'] : false; }
function set_transient($key, $value, $ttl) { $GLOBALS['cache'][$key] = array('value' => $value, 'ttl' => $ttl, 'expires' => microtime(true) + $ttl); }
function trailingslashit($v) { return rtrim($v, '/') . '/'; }
function add_query_arg($query, $url) { return $url . '?' . http_build_query($query); }
function wp_safe_remote_get($url, $args) {
    $GLOBALS['calls'][] = array('url' => $url, 'args' => $args);
    if (isset($GLOBALS['during_http'])) { ($GLOBALS['during_http'])(); }
    if ($GLOBALS['transport_error']) { return new WP_Error('timeout', 'synthetic'); }
    return array('status' => $GLOBALS['status'], 'body' => $GLOBALS['raw'] ?? json_encode($GLOBALS['body']), 'retry' => $GLOBALS['retry']);
}
function wp_remote_retrieve_response_code($r) { return $r['status']; }
function wp_remote_retrieve_body($r) { return $r['body']; }
function wp_remote_retrieve_header($r, $key) { return $r['retry']; }
function do_action(...$args) { $GLOBALS['events'][] = $args; }
function sanitize_text_field($v) { return strip_tags($v); }
function esc_url_raw($v) { return $v; }
require __DIR__ . '/../codigo/htgithubforwp/includes/class-htgp-configuration.php';
require __DIR__ . '/../codigo/htgithubforwp/includes/class-htgp-github-client.php';
$settings = array('api_base_url' => 'https://api.example', 'api_version' => '2022-11-28', 'user_agent' => 'synthetic-audit', 'timeout_seconds' => 5, 'response_size' => 100000, 'cache_ttl_seconds' => 300, 'api_page_size' => 100, 'max_owner_length' => 39, 'max_include_length' => 1000, 'max_repo_length' => 100);
$policy = array('HTGP_PUBLIC_LINK_ORIGINS' => 'https://public.example', 'HTGP_PROFILE_ORIGINS' => 'https://public.example', 'HTGP_FAILURE_TTL_SECONDS' => '10', 'HTGP_MAX_BACKOFF_SECONDS' => '60', 'HTGP_REQUEST_BUDGET_SECONDS' => '8', 'HTGP_MAX_REQUESTS' => '2', 'HTGP_MAX_TIMEOUT_SECONDS' => '5', 'HTGP_MAX_RESPONSE_SIZE' => '100000', 'HTGP_MAX_API_PAGE_SIZE' => '100');
foreach ($policy as $key => $value) { putenv($key . '=' . $value); }
putenv('HTGP_GITHUB_TOKEN=synthetic-token'); putenv('HTGP_GITHUB_TOKEN_ORIGIN=https://api.example');
$repo = array('name' => 'demo', 'html_url' => 'https://public.example/demo/demo', 'private' => false, 'description' => '<script>synthetic</script>', 'owner' => array('login' => 'demo'));
$query = array('per_page' => 2, 'sort' => 'updated', 'direction' => 'desc', 'type' => 'owner');
function reset_state() { $GLOBALS['cache'] = $GLOBALS['options'] = $GLOBALS['calls'] = $GLOBALS['events'] = array(); $GLOBALS['status'] = 200; $GLOBALS['body'] = array($GLOBALS['repo']); $GLOBALS['retry'] = ''; $GLOBALS['transport_error'] = false; unset($GLOBALS['raw'], $GLOBALS['during_http']); }
function client() { return new HTGP_GitHub_Client(new HTGP_Configuration()); }
function check($ok, $label) { if (!$ok) { throw new RuntimeException($label); } echo "PASS: $label\n"; }
reset_state(); $c = client();
check(count($c->repositories('demo', $query)) === 1, 'legitimate public repository');
check($calls[0]['args']['headers']['Authorization'] === 'Bearer synthetic-token', 'authorized origin receives synthetic token');
$c->repositories('demo', $query); check(count($calls) === 1, 'success cache reused');
check(!$options, 'lock released after success');
$settings['api_base_url'] = 'https://other.example';
check(!((new HTGP_Configuration())->ready()), 'token origin mismatch fails closed');
check(is_wp_error(client()->repositories('demo', $query)) && count($calls) === 1, 'denied origin performs no HTTP');
putenv('HTGP_GITHUB_TOKEN'); client()->repositories('demo', $query);
check(count($calls) === 2 && !isset($calls[1]['args']['headers']['Authorization']), 'endpoint change misses cache; anonymous request has no token');
$settings['api_base_url'] = 'https://api.example'; reset_state();
putenv('HTGP_GITHUB_TOKEN=synthetic-token'); client()->repositories('demo', $query); putenv('HTGP_GITHUB_TOKEN=synthetic-other'); client()->repositories('demo', $query);
check(count($calls) === 2, 'credential rotation segregates cache');
reset_state(); $body[0]['private'] = null;
check(client()->repositories('demo', $query) === array(), 'missing/null public flag rejected');
reset_state(); $body[0]['private'] = true; check(client()->repositories('demo', $query) === array(), 'private repository rejected');
reset_state(); $body[0]['html_url'] = 'https://untrusted.example/demo'; check(client()->repositories('demo', $query) === array(), 'unapproved link rejected');
reset_state(); $body[0]['description'] = array('bad'); check(is_wp_error(client()->repositories('demo', $query)), 'malformed types rejected without fatal');
reset_state(); $raw = '{'; check(is_wp_error(client()->repositories('demo', $query)), 'malformed JSON rejected');
foreach (array(429, 503) as $failure_status) {
    reset_state(); $status = $failure_status; $retry = '30';
    client()->repositories('demo', $query); client()->repositories('demo', $query); client()->latest_release('demo', 'demo');
    check(count($calls) === 1, 'global cooldown across workers and methods for ' . $status);
    check(array_values($cache)[0]['ttl'] === 30, 'Retry-After honored');
}
reset_state(); $transport_error = true; client()->latest_release('demo', 'demo'); client()->latest_release('demo', 'demo'); check(count($calls) === 1 && !$options, 'timeout backoff and lock cleanup');
reset_state(); $status = 404; check(client()->latest_release('demo', 'demo') === null && client()->latest_release('demo', 'demo') === null && count($calls) === 1, 'release 404 negative cache');
reset_state(); $body = array('tag_name' => 'v1', 'html_url' => 'https://public.example/demo/release'); check(client()->latest_release('demo', 'demo')['tag_name'] === 'v1', 'legitimate release');
reset_state(); $nested = null; $during_http = function () use (&$nested, $query) { $nested = client()->repositories('demo', $query); }; client()->repositories('demo', $query);
check(is_wp_error($nested) && $nested->code === 'htgp_busy' && count($calls) === 1, 'interleaved worker cannot issue duplicate HTTP');
reset_state(); $c = client(); $c->repositories('demo', $query); $body = array('tag_name' => 'v1', 'html_url' => 'https://public.example/demo/release'); $c->latest_release('demo', 'one');
check(is_wp_error($c->latest_release('demo', 'two')) && count($calls) === 2, 'aggregate request count enforced');
check($calls[0]['args']['timeout'] <= 5 && $calls[0]['args']['timeout'] <= 8, 'configured time budget caps transport');
reset_state(); $conf = new HTGP_Configuration(); $options['htgp_lock_' . $conf->fingerprint()] = array('expires' => microtime(true) - 1, 'id' => 'expired');
check(count(client()->repositories('demo', $query)) === 1 && !$options, 'expired lock recovered');
reset_state(); $conf = new HTGP_Configuration(); $successor = array('expires' => microtime(true) + 30, 'id' => 'successor'); $during_http = function () use ($conf, $successor) { $GLOBALS['options']['htgp_lock_' . $conf->fingerprint()] = $successor; }; client()->repositories('demo', $query);
check($options['htgp_lock_' . $conf->fingerprint()] === $successor, 'old worker cannot delete successor lock');
putenv('HTGP_MAX_REQUESTS'); check(!(new HTGP_Configuration())->ready(), 'missing required policy fails closed'); putenv('HTGP_MAX_REQUESTS=2');
reset_state(); $raw = str_repeat('x', $settings['response_size'] + 1); check(is_wp_error(client()->repositories('demo', $query)), 'oversized response rejected');
reset_state(); $status = 429; $retry = '99999'; client()->repositories('demo', $query); check(array_values($cache)[0]['ttl'] === 60, 'Retry-After capped by configured policy');
reset_state(); $status = 429; $retry = gmdate('D, d M Y H:i:s', time() + 40) . ' GMT'; client()->repositories('demo', $query); check(array_values($cache)[0]['ttl'] >= 39 && array_values($cache)[0]['ttl'] <= 40, 'Retry-After HTTP date supported');
reset_state(); $cache['htgp_backoff_' . (new HTGP_Configuration())->fingerprint()] = array('value' => true, 'ttl' => 10, 'expires' => microtime(true) - 1); check(count(client()->repositories('demo', $query)) === 1, 'expired cooldown permits recovery');
reset_state(); $c = client(); $reflection = new ReflectionProperty(HTGP_GitHub_Client::class, 'started'); $reflection->setAccessible(true); $reflection->setValue($c, microtime(true) - 9); check(is_wp_error($c->repositories('demo', $query)) && !$calls, 'elapsed aggregate time budget denies HTTP');
reset_state(); $body = array('tag_name' => array('bad'), 'html_url' => 'https://public.example/demo'); check(is_wp_error(client()->latest_release('demo', 'demo')), 'malformed release rejected');
reset_state(); $bad_query = $query; $bad_query['per_page'] = 101; check(is_wp_error(client()->repositories('demo', $bad_query)) && !$calls, 'pagination policy enforced');
$settings['timeout_seconds'] = 6; check(!(new HTGP_Configuration())->ready(), 'unsafe administrative timeout rejected by environment cap');
$settings['timeout_seconds'] = 5;
function add_action(...$args) {}
function current_user_can($capability) { return $GLOBALS['authorized']; }
function add_settings_error(...$args) {}
function absint($v) { return abs((int) $v); }
function sanitize_key($v) { return strtolower(preg_replace('/[^a-zA-Z0-9_-]/', '', $v)); }
function wp_enqueue_style(...$args) {}
function shortcode_atts($defaults, $atts, $name) { return array_merge($defaults, $atts); }
function esc_html($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); }
function esc_attr($v) { return esc_html($v); }
function esc_url($v) { return esc_attr($v); }
function esc_html_e($v, $domain) { echo esc_html($v); }
function esc_attr_e($v, $domain) { echo esc_attr($v); }
function wp_date($format, $time) { return gmdate('Y-m-d', $time); }
function number_format_i18n($v) { return number_format($v); }
define('HTGP_URL', 'https://synthetic.example/plugin/');
require __DIR__ . '/../codigo/htgithubforwp/includes/class-htgp-plugin.php';
$settings = array_merge($settings, array('profile_url' => 'https://public.example/demo', 'limit' => 2, 'sort' => 'updated', 'direction' => 'desc', 'type' => 'owner', 'include_forks' => false, 'include_archived' => false));
$plugin = HTGP_Plugin::instance();
$authorized = false; $input = $settings; $input['api_base_url'] = 'https://other.example'; check($plugin->validate_settings($input) === $settings, 'unprivileged configuration denied');
$authorized = true; $input['profile_url'] = array('bad'); check($plugin->validate_settings($input) === $settings, 'nested administrative input rejected without fatal');
$input = $settings; $input['timeout_seconds'] = 6; check($plugin->validate_settings($input) === $settings, 'admin cannot exceed environment ceiling');
$input = $settings; $input['api_base_url'] = 'https://other.example'; check($plugin->validate_settings($input) === $settings, 'admin cannot redirect environment token');
reset_state(); $status = 404; $body = array($repo);
// Return listing success then release-not-found through the transport hook.
$during_http = function () { $GLOBALS['status'] = count($GLOBALS['calls']) === 1 ? 200 : 404; };
$html = $plugin->render_shortcode(array());
check(strpos($html, '&lt;script&gt;') !== false && strpos($html, '<script>') === false, 'hostile repository description escaped in rendered HTML');
foreach (array_merge(array_keys($policy), array('HTGP_GITHUB_TOKEN', 'HTGP_GITHUB_TOKEN_ORIGIN')) as $key) { putenv($key); }
echo "All offline security regressions passed.\n";

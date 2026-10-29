<?php
if (!defined('ABSPATH')) { exit; }
final class HTGP_GitHub_Client {
    private $configuration;
    private $started;
    private $requests = 0;
    public function __construct(HTGP_Configuration $configuration) {
        $this->configuration = $configuration;
        $this->started = microtime(true);
    }
    public function repositories($username, array $query) {
        if (!$this->valid_owner($username)) { return $this->error('htgp_owner'); }
        if (!isset($query['per_page']) || !is_int($query['per_page']) || $query['per_page'] < 1 || $query['per_page'] > $this->configuration->get('api_page_size') ||
            !in_array($query['sort'] ?? '', array('created', 'updated', 'pushed', 'full_name'), true) ||
            !in_array($query['direction'] ?? '', array('asc', 'desc'), true) ||
            !in_array($query['type'] ?? '', array('all', 'owner', 'member'), true) ||
            array_diff(array_keys($query), array('per_page', 'sort', 'direction', 'type'))) { return $this->error('htgp_query'); }
        $url = add_query_arg($query, trailingslashit($this->configuration->get('api_base_url')) . 'users/' . rawurlencode($username) . '/repos');
        return $this->fetch($url, function ($body) use ($query, $username) {
            if (!is_array($body) || ($body && array_keys($body) !== range(0, count($body) - 1)) || count($body) > $query['per_page']) { return $this->error('htgp_invalid_response'); }
            $result = array();
            foreach ($body as $repository) {
                if (!is_array($repository) || ($repository['private'] ?? null) !== false ||
                    !$this->valid_repository($repository['name'] ?? null) ||
                    !$this->configuration->allowed_url($repository['html_url'] ?? null)) {
                    do_action('htgp_security_event', 'repository_rejected');
                    continue;
                }
                foreach (array('description', 'language', 'updated_at') as $field) {
                    if (isset($repository[$field]) && !is_string($repository[$field])) { return $this->error('htgp_invalid_response'); }
                }
                foreach (array('fork', 'archived') as $field) {
                    if (isset($repository[$field]) && !is_bool($repository[$field])) { return $this->error('htgp_invalid_response'); }
                }
                foreach (array('stargazers_count', 'forks_count') as $field) {
                    if (isset($repository[$field]) && (!is_int($repository[$field]) || $repository[$field] < 0)) { return $this->error('htgp_invalid_response'); }
                }
                if (isset($repository['owner']) && (!is_array($repository['owner']) || !$this->valid_owner($repository['owner']['login'] ?? null))) { return $this->error('htgp_invalid_response'); }
                $clean = array_intersect_key($repository, array_flip(array('name', 'html_url', 'description', 'language', 'updated_at', 'fork', 'archived', 'stargazers_count', 'forks_count')));
                $topics = $repository['topics'] ?? array();
                if (!is_array($topics) || ($topics && array_keys($topics) !== range(0, count($topics) - 1)) || count($topics) > 20) { return $this->error('htgp_invalid_response'); }
                foreach ($topics as $topic) {
                    if (!is_string($topic) || !preg_match('/^[a-z0-9-]{1,50}$/D', $topic)) { return $this->error('htgp_invalid_response'); }
                }
                $clean['topics'] = array_values(array_unique($topics));
                $clean['owner'] = array('login' => $repository['owner']['login'] ?? $username);
                $clean['private'] = false;
                $result[] = $clean;
            }
            return $result;
        });
    }
    public function latest_release($owner, $repository) {
        if (!$this->valid_owner($owner) || !$this->valid_repository($repository)) { return $this->error('htgp_release'); }
        $url = trailingslashit($this->configuration->get('api_base_url')) . 'repos/' . rawurlencode($owner) . '/' . rawurlencode($repository) . '/releases/latest';
        return $this->fetch($url, function ($body) {
            if (!is_array($body) || !is_string($body['tag_name'] ?? null) || $body['tag_name'] === '' ||
                (isset($body['published_at']) && !is_string($body['published_at'])) ||
                !$this->configuration->allowed_url($body['html_url'] ?? null)) { return $this->error('htgp_invalid_response'); }
            return array('tag_name' => sanitize_text_field($body['tag_name']), 'published_at' => sanitize_text_field($body['published_at'] ?? ''), 'html_url' => esc_url_raw($body['html_url']));
        }, true);
    }
    private function fetch($url, $validate, $allow_not_found = false) {
        if (!$this->configuration->ready() || !wp_http_validate_url($url) || $this->configuration->origin($url) !== $this->configuration->origin($this->configuration->get('api_base_url'))) { return $this->error('htgp_configuration'); }
        $scope = $this->configuration->fingerprint();
        $key = 'htgp_data_' . hash('sha256', 'topics-v1|' . $scope . $url);
        $cached = get_transient($key);
        if (is_array($cached) && array_key_exists('data', $cached)) { return $cached['data']; }
        $backoff_key = 'htgp_backoff_' . $scope;
        if (get_transient($backoff_key)) { return $this->error('htgp_backoff'); }
        $remaining = $this->configuration->get('request_budget_seconds') - (microtime(true) - $this->started);
        if ($remaining <= 0 || $this->requests >= $this->configuration->get('max_requests')) { return $this->error('htgp_budget'); }
        $lock_key = 'htgp_lock_' . $scope;
        $lock = array('expires' => microtime(true) + $remaining, 'id' => wp_generate_uuid4());
        $existing = get_option($lock_key);
        if (is_array($existing) && isset($existing['expires']) && $existing['expires'] < microtime(true)) { $this->release_lock($lock_key, $existing); }
        if (!$this->acquire_lock($lock_key, $lock)) { return $this->error('htgp_busy'); }
        try {
            $cached = get_transient($key);
            if (is_array($cached) && array_key_exists('data', $cached)) { return $cached['data']; }
            if (get_transient($backoff_key)) { return $this->error('htgp_backoff'); }
            $remaining = $this->configuration->get('request_budget_seconds') - (microtime(true) - $this->started);
            if ($remaining <= 0) { return $this->error('htgp_budget'); }
            $headers = array('Accept' => 'application/vnd.github+json', 'X-GitHub-Api-Version' => $this->configuration->get('api_version'), 'User-Agent' => $this->configuration->get('user_agent'));
            $token = $this->configuration->get('github_token');
            if ($token !== '') {
                if ($this->configuration->origin($url) !== $this->configuration->get('token_origin')) { return $this->error('htgp_token_origin'); }
                $headers['Authorization'] = 'Bearer ' . $token;
            }
            ++$this->requests;
            $response = wp_safe_remote_get($url, array('headers' => $headers, 'timeout' => min($remaining, $this->configuration->get('timeout_seconds')), 'limit_response_size' => $this->configuration->get('response_size'), 'redirection' => 0, 'reject_unsafe_urls' => true));
            if (is_wp_error($response)) { return $this->fail($backoff_key, 'external_request_failed'); }
            $status = wp_remote_retrieve_response_code($response);
            if ($status === 404 && $allow_not_found) {
                set_transient($key, array('data' => null), $this->configuration->get('cache_ttl_seconds'));
                return null;
            }
            if ($status !== 200) { return $this->fail($backoff_key, 'external_response_rejected', wp_remote_retrieve_header($response, 'retry-after')); }
            $raw = wp_remote_retrieve_body($response);
            if (!is_string($raw) || strlen($raw) > $this->configuration->get('response_size')) { return $this->fail($backoff_key, 'external_response_invalid'); }
            $body = json_decode($raw, true);
            if (json_last_error() !== JSON_ERROR_NONE) { return $this->fail($backoff_key, 'external_response_invalid'); }
            $data = $validate($body);
            if (is_wp_error($data)) { return $this->fail($backoff_key, 'external_response_invalid'); }
            set_transient($key, array('data' => $data), $this->configuration->get('cache_ttl_seconds'));
            return $data;
        } finally { $this->release_lock($lock_key, $lock); }
    }
    private function acquire_lock($key, $lock) {
        global $wpdb;
        // INSERT IGNORE never overwrites another worker's option; add_option can upsert.
        $inserted = $wpdb->query($wpdb->prepare("INSERT IGNORE INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, %s, %s)", $key, maybe_serialize($lock), 'no'));
        wp_cache_delete($key, 'options');
        wp_cache_delete('notoptions', 'options');
        return $inserted === 1;
    }
    private function release_lock($key, $lock) {
        global $wpdb;
        // Compare-and-delete cannot remove a successor's lock after expiration.
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name = %s AND option_value = %s", $key, maybe_serialize($lock)));
        wp_cache_delete($key, 'options');
    }
    private function fail($key, $event, $retry_after = '') {
        $ttl = $this->configuration->get('failure_ttl_seconds');
        if (is_string($retry_after) && $retry_after !== '') {
            $retry = ctype_digit($retry_after) ? (float) $retry_after : strtotime($retry_after) - time();
            $ttl = max($ttl, min($this->configuration->get('max_backoff_seconds'), $retry));
        }
        set_transient($key, true, (int) $ttl);
        do_action('htgp_security_event', $event);
        return $this->error('htgp_unavailable');
    }
    private function valid_owner($owner) {
        return is_string($owner) && $owner !== '' && strlen($owner) <= $this->configuration->get('max_owner_length') && preg_match('/^[A-Za-z0-9](?:[A-Za-z0-9-]*[A-Za-z0-9])?$/', $owner);
    }
    private function valid_repository($name) {
        return is_string($name) && $name !== '' && strlen($name) <= $this->configuration->get('max_repo_length') && preg_match('/^[A-Za-z0-9._-]+$/', $name);
    }
    private function error($code) { return new WP_Error($code, __('Conteúdo indisponível.', 'htgithubforwp')); }
}

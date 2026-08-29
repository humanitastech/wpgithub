<?php

if (!defined('ABSPATH')) {
    exit;
}

final class HTGP_GitHub_Client {
    private $configuration;

    public function __construct(HTGP_Configuration $configuration) {
        $this->configuration = $configuration;
    }

    public function repositories($username, array $query) {
        if (!$this->configuration->ready()) {
            return new WP_Error('htgp_configuration', __('Integração não configurada.', 'htgithubforwp'));
        }
        $username = $this->validate_owner($username);
        if (is_wp_error($username)) {
            return $username;
        }
        $cache_key = 'htgp_' . hash('sha256', wp_json_encode(array($username, $query, HTGP_VERSION)));
        $cached = get_transient($cache_key);
        if (is_array($cached)) {
            return $cached;
        }
        $endpoint = trailingslashit($this->configuration->get('api_base_url')) . 'users/' . rawurlencode($username) . '/repos';
        $url = add_query_arg($query, $endpoint);
        if (!wp_http_validate_url($url)) {
            return new WP_Error('htgp_endpoint', __('Destino externo inválido.', 'htgithubforwp'));
        }
        $headers = array(
            'Accept'               => 'application/vnd.github+json',
            'X-GitHub-Api-Version' => $this->configuration->get('api_version'),
            'User-Agent'           => $this->configuration->get('user_agent'),
        );
        $token = $this->configuration->get('github_token');
        if ($token !== '') {
            $headers['Authorization'] = 'Bearer ' . $token;
        }
        $response = wp_safe_remote_get($url, array(
            'headers'             => $headers,
            'timeout'             => $this->configuration->get('timeout_seconds'),
            'limit_response_size' => $this->configuration->get('response_size'),
            'redirection'         => 0,
            'reject_unsafe_urls'  => true,
        ));
        if (is_wp_error($response)) {
            do_action('htgp_security_event', 'external_request_failed');
            return new WP_Error('htgp_unavailable', __('Serviço externo indisponível.', 'htgithubforwp'));
        }
        if (wp_remote_retrieve_response_code($response) !== 200) {
            do_action('htgp_security_event', 'external_response_rejected');
            return new WP_Error('htgp_unavailable', __('Serviço externo indisponível.', 'htgithubforwp'));
        }
        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (!is_array($body)) {
            return new WP_Error('htgp_invalid_response', __('Resposta externa inválida.', 'htgithubforwp'));
        }
        $repositories = array_values(array_filter($body, function ($repository) {
            return is_array($repository) && empty($repository['private']) && isset($repository['name'], $repository['html_url']);
        }));
        set_transient($cache_key, $repositories, $this->configuration->get('cache_ttl_seconds'));
        return $repositories;
    }

    public function latest_release($owner, $repository) {
        if (!$this->configuration->ready()) {
            return new WP_Error('htgp_configuration', __('Integração não configurada.', 'htgithubforwp'));
        }
        $owner = $this->validate_owner($owner);
        $repository = is_string($repository) ? trim($repository) : '';
        if (is_wp_error($owner) || $repository === '' || strlen($repository) > $this->configuration->get('max_repo_length') || !preg_match('/^[A-Za-z0-9._-]+$/', $repository)) {
            return new WP_Error('htgp_release', __('Repositório inválido.', 'htgithubforwp'));
        }

        $cache_key = 'htgp_release_' . hash('sha256', wp_json_encode(array($owner, $repository, HTGP_VERSION)));
        $cached = get_transient($cache_key);
        if (is_array($cached)) {
            return !empty($cached['not_found']) ? null : $cached;
        }

        $endpoint = trailingslashit($this->configuration->get('api_base_url')) . 'repos/' . rawurlencode($owner) . '/' . rawurlencode($repository) . '/releases/latest';
        if (!wp_http_validate_url($endpoint)) {
            return new WP_Error('htgp_endpoint', __('Destino externo inválido.', 'htgithubforwp'));
        }

        $headers = array(
            'Accept'               => 'application/vnd.github+json',
            'X-GitHub-Api-Version' => $this->configuration->get('api_version'),
            'User-Agent'           => $this->configuration->get('user_agent'),
        );
        $token = $this->configuration->get('github_token');
        if ($token !== '') {
            $headers['Authorization'] = 'Bearer ' . $token;
        }

        $response = wp_safe_remote_get($endpoint, array(
            'headers'             => $headers,
            'timeout'             => $this->configuration->get('timeout_seconds'),
            'limit_response_size' => $this->configuration->get('response_size'),
            'redirection'         => 0,
            'reject_unsafe_urls'  => true,
        ));
        if (is_wp_error($response)) {
            return new WP_Error('htgp_unavailable', __('Serviço externo indisponível.', 'htgithubforwp'));
        }

        $status = wp_remote_retrieve_response_code($response);
        if ($status === 404) {
            set_transient($cache_key, array('not_found' => true), $this->configuration->get('cache_ttl_seconds'));
            return null;
        }
        if ($status !== 200) {
            return new WP_Error('htgp_unavailable', __('Serviço externo indisponível.', 'htgithubforwp'));
        }

        $release = json_decode(wp_remote_retrieve_body($response), true);
        if (!is_array($release) || empty($release['tag_name'])) {
            return new WP_Error('htgp_invalid_response', __('Resposta externa inválida.', 'htgithubforwp'));
        }
        $release = array(
            'tag_name'     => sanitize_text_field($release['tag_name']),
            'published_at' => sanitize_text_field($release['published_at'] ?? ''),
            'html_url'     => esc_url_raw($release['html_url'] ?? ''),
        );
        set_transient($cache_key, $release, $this->configuration->get('cache_ttl_seconds'));
        return $release;
    }

    private function validate_owner($owner) {
        $owner = is_string($owner) ? trim($owner) : '';
        if ($owner === '' || strlen($owner) > $this->configuration->get('max_owner_length') || !preg_match('/^[A-Za-z0-9](?:[A-Za-z0-9-]*[A-Za-z0-9])?$/', $owner)) {
            return new WP_Error('htgp_owner', __('Identificador de conta inválido.', 'htgithubforwp'));
        }
        return $owner;
    }
}


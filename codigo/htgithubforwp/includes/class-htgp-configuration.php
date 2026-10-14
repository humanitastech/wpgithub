<?php

if (!defined('ABSPATH')) {
    exit;
}

final class HTGP_Configuration {
    private $values = array();
    private $errors = array();
    private $settings = array();

    public function __construct() {
        $stored = get_option('htgp_settings', array());
        $this->settings = is_array($stored) ? $stored : array();
        $this->values = array(
            'api_base_url'       => $this->environment('HTGP_GITHUB_API_BASE_URL', true, 'api_base_url'),
            'api_version'        => $this->environment('HTGP_GITHUB_API_VERSION', true, 'api_version'),
            'user_agent'         => $this->environment('HTGP_HTTP_USER_AGENT', true, 'user_agent'),
            'timeout_seconds'    => $this->positive_integer('HTGP_HTTP_TIMEOUT_SECONDS', 'timeout_seconds'),
            'response_size'      => $this->positive_integer('HTGP_RESPONSE_SIZE_BYTES', 'response_size'),
            'cache_ttl_seconds'  => $this->positive_integer('HTGP_CACHE_TTL_SECONDS', 'cache_ttl_seconds'),
            'api_page_size'      => $this->positive_integer('HTGP_API_PAGE_SIZE', 'api_page_size'),
            'max_owner_length'   => $this->positive_integer('HTGP_MAX_OWNER_LENGTH', 'max_owner_length'),
            'max_include_length' => $this->positive_integer('HTGP_MAX_INCLUDE_LENGTH', 'max_include_length'),
            'max_repo_length'    => $this->positive_integer('HTGP_MAX_REPOSITORY_NAME_LENGTH', 'max_repo_length'),
            'github_token'       => $this->environment('HTGP_GITHUB_TOKEN', false),
            'token_origin'       => $this->environment('HTGP_GITHUB_TOKEN_ORIGIN', false),
            'link_origins'       => $this->environment('HTGP_PUBLIC_LINK_ORIGINS'),
            'profile_origins'    => $this->environment('HTGP_PROFILE_ORIGINS'),
        );
        foreach (array('failure_ttl_seconds', 'max_backoff_seconds', 'request_budget_seconds', 'max_requests', 'max_timeout_seconds', 'max_response_size', 'max_api_page_size') as $key) {
            $this->values[$key] = $this->positive_integer('HTGP_' . strtoupper($key), null);
        }
        $this->validate_endpoint();
        foreach (array('timeout_seconds' => 'max_timeout_seconds', 'response_size' => 'max_response_size', 'api_page_size' => 'max_api_page_size', 'failure_ttl_seconds' => 'max_backoff_seconds') as $key => $maximum) {
            if ($this->get($key) > $this->get($maximum)) {
                $this->errors[] = __('Configuração excede o limite autorizado pelo ambiente.', 'htgithubforwp');
            }
        }
        foreach (array('link_origins', 'profile_origins') as $key) {
            $origins = array_map('trim', explode(',', $this->values[$key]));
            foreach ($origins as $origin) {
                if (!$this->valid_origin($origin)) {
                    $this->errors[] = __('Lista de origens HTTPS inválida.', 'htgithubforwp');
                }
            }
            $this->values[$key] = $origins;
        }
        if ($this->get('github_token') !== '' && (!$this->valid_origin($this->get('token_origin')) || $this->origin($this->get('api_base_url')) !== $this->get('token_origin'))) {
            $this->errors[] = __('A credencial exige uma origem HTTPS correspondente autorizada pelo ambiente.', 'htgithubforwp');
        }
        foreach (array('github_token', 'api_version', 'user_agent') as $key) {
            if (preg_match('/[\r\n]/', $this->get($key))) {
                $this->errors[] = __('Cabeçalho HTTP inválido.', 'htgithubforwp');
            }
        }
    }

    public function ready() {
        return empty($this->errors);
    }

    public function errors() {
        return $this->errors;
    }

    public function get($key) {
        return array_key_exists($key, $this->values) ? $this->values[$key] : null;
    }

    public function fingerprint() {
        $context = $this->values;
        $context['github_token'] = wp_hash($context['github_token']);
        return hash('sha256', wp_json_encode(array(HTGP_VERSION, $context)));
    }

    public function origin($url) {
        $parts = is_string($url) ? wp_parse_url($url) : false;
        if (!is_array($parts) || ($parts['scheme'] ?? '') !== 'https' || empty($parts['host']) || isset($parts['user']) || isset($parts['pass'])) {
            return null;
        }
        return 'https://' . strtolower($parts['host']) . (isset($parts['port']) ? ':' . $parts['port'] : '');
    }

    public function allowed_url($url, $list = 'link_origins') {
        return is_string($url) && in_array($this->origin($url), $this->get($list), true) && wp_http_validate_url($url);
    }

    private function valid_origin($url) {
        return $this->origin($url) === $url && wp_http_validate_url($url);
    }

    private function environment($name, $required = true, $option_key = null) {
        $value = getenv($name);
        if ($value === false && defined($name)) {
            $value = constant($name);
        }
        if (($value === false || $value === '') && $option_key !== null && isset($this->settings[$option_key])) {
            $value = $this->settings[$option_key];
        }
        $value = is_scalar($value) ? trim((string) $value) : '';
        if ($required && $value === '') {
            $this->errors[] = sprintf(__('Configuração obrigatória ausente: %s.', 'htgithubforwp'), $name);
        }
        return $value;
    }

    private function positive_integer($name, $option_key) {
        $value = $this->environment($name, true, $option_key);
        if ($value !== '' && (!ctype_digit($value) || (int) $value < 1 || (string) (int) $value !== $value)) {
            $this->errors[] = sprintf(__('Configuração numérica inválida: %s.', 'htgithubforwp'), $name);
            return null;
        }
        return $value === '' ? null : (int) $value;
    }

    private function validate_endpoint() {
        $url = $this->values['api_base_url'];
        if ($url === '') {
            return;
        }
        $parts = wp_parse_url($url);
        if (!is_array($parts) || ($parts['scheme'] ?? '') !== 'https' || empty($parts['host']) || isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment']) || !wp_http_validate_url($url)) {
            $this->errors[] = __('O endpoint externo deve ser uma URL HTTPS pública, sem credenciais incorporadas.', 'htgithubforwp');
        }
    }
}


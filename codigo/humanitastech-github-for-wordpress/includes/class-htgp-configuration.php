<?php

if (!defined('ABSPATH')) {
    exit;
}

final class HTGP_Configuration {
    private $values = array();
    private $errors = array();

    public function __construct() {
        $this->values = array(
            'api_base_url'       => $this->environment('HTGP_GITHUB_API_BASE_URL'),
            'api_version'        => $this->environment('HTGP_GITHUB_API_VERSION'),
            'timeout_seconds'    => $this->positive_integer('HTGP_HTTP_TIMEOUT_SECONDS'),
            'response_size'      => $this->positive_integer('HTGP_RESPONSE_SIZE_BYTES'),
            'cache_ttl_seconds'  => $this->positive_integer('HTGP_CACHE_TTL_SECONDS'),
            'api_page_size'      => $this->positive_integer('HTGP_API_PAGE_SIZE'),
            'max_owner_length'   => $this->positive_integer('HTGP_MAX_OWNER_LENGTH'),
            'max_include_length' => $this->positive_integer('HTGP_MAX_INCLUDE_LENGTH'),
            'max_repo_length'    => $this->positive_integer('HTGP_MAX_REPOSITORY_NAME_LENGTH'),
            'github_token'       => $this->environment('HTGP_GITHUB_TOKEN', false),
        );
        $this->validate_endpoint();
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

    private function environment($name, $required = true) {
        $value = getenv($name);
        if ($value === false && defined($name)) {
            $value = constant($name);
        }
        $value = is_scalar($value) ? trim((string) $value) : '';
        if ($required && $value === '') {
            $this->errors[] = sprintf(__('Configuração obrigatória ausente: %s.', 'humanitastech-github-for-wordpress'), $name);
        }
        return $value;
    }

    private function positive_integer($name) {
        $value = $this->environment($name);
        if ($value !== '' && (!ctype_digit($value) || (int) $value < 1)) {
            $this->errors[] = sprintf(__('Configuração numérica inválida: %s.', 'humanitastech-github-for-wordpress'), $name);
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
        if (!is_array($parts) || ($parts['scheme'] ?? '') !== 'https' || empty($parts['host']) || isset($parts['user']) || isset($parts['pass']) || !wp_http_validate_url($url)) {
            $this->errors[] = __('O endpoint externo deve ser uma URL HTTPS pública, sem credenciais incorporadas.', 'humanitastech-github-for-wordpress');
        }
    }
}

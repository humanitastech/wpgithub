<?php

if (!defined('ABSPATH')) {
    exit;
}

final class HTGP_Plugin {
    private static $instance;
    private $configuration;
    private $client;

    public static function instance() {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->configuration = new HTGP_Configuration();
        $this->client = new HTGP_GitHub_Client($this->configuration);
        add_action('init', array($this, 'register_shortcode'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    public function register_shortcode() {
        add_shortcode('ht_github_projects', array($this, 'render_shortcode'));
    }

    public function render_shortcode($attributes) {
        $settings = $this->settings();
        if (is_wp_error($settings)) {
            return $this->notice($settings);
        }
        $owner = $this->owner_from_profile_url($settings['profile_url']);
        if (is_wp_error($owner)) {
            return $this->notice($owner);
        }
        $attributes = shortcode_atts(array('include' => ''), $attributes, 'ht_github_projects');
        $include = $this->validate_repository_list($attributes['include']);
        if (is_wp_error($include)) {
            return $this->notice($include);
        }
        $query = array(
            'per_page' => $settings['limit'],
            'sort'      => $settings['sort'],
            'direction' => $settings['direction'],
            'type'      => $settings['type'],
        );
        $repositories = $this->client->repositories($owner, $query);
        if (is_wp_error($repositories)) {
            return $this->notice($repositories);
        }
        $repositories = array_values(array_filter($repositories, function ($repository) use ($settings, $include) {
            if (!$settings['include_forks'] && !empty($repository['fork'])) {
                return false;
            }
            if (!$settings['include_archived'] && !empty($repository['archived'])) {
                return false;
            }
            return !$include || in_array($repository['name'], $include, true);
        }));
        ob_start();
        ?>
        <section class="htgp-projects" aria-label="<?php esc_attr_e('Projetos públicos', 'humanitastech-github-for-wordpress'); ?>">
            <?php foreach ($repositories as $repository) : ?>
                <article class="htgp-project">
                    <h3 class="htgp-project__title"><a href="<?php echo esc_url($repository['html_url']); ?>" target="_blank" rel="noopener noreferrer external"><?php echo esc_html($repository['name']); ?></a></h3>
                    <?php if (!empty($repository['description'])) : ?><p class="htgp-project__description"><?php echo esc_html($repository['description']); ?></p><?php endif; ?>
                    <dl class="htgp-project__metadata">
                        <?php if (!empty($repository['language'])) : ?><div><dt><?php esc_html_e('Linguagem', 'humanitastech-github-for-wordpress'); ?></dt><dd><?php echo esc_html($repository['language']); ?></dd></div><?php endif; ?>
                        <div><dt><?php esc_html_e('Estrelas', 'humanitastech-github-for-wordpress'); ?></dt><dd><?php echo esc_html(number_format_i18n((int) ($repository['stargazers_count'] ?? 0))); ?></dd></div>
                        <div><dt><?php esc_html_e('Forks', 'humanitastech-github-for-wordpress'); ?></dt><dd><?php echo esc_html(number_format_i18n((int) ($repository['forks_count'] ?? 0))); ?></dd></div>
                    </dl>
                </article>
            <?php endforeach; ?>
        </section>
        <?php
        return ob_get_clean();
    }

    public function admin_menu() {
        add_options_page(__('Integração GitHub', 'humanitastech-github-for-wordpress'), __('Integração GitHub', 'humanitastech-github-for-wordpress'), 'manage_options', 'htgp-settings', array($this, 'settings_page'));
    }

    public function register_settings() {
        register_setting('htgp_settings', 'htgp_settings', array('sanitize_callback' => array($this, 'validate_settings')));
    }

    public function validate_settings($input) {
        if (!current_user_can('manage_options') || !is_array($input)) {
            add_settings_error('htgp_settings', 'forbidden', __('Configuração rejeitada.', 'humanitastech-github-for-wordpress'));
            return get_option('htgp_settings', array());
        }
        $validated = array(
            'api_base_url'       => esc_url_raw($input['api_base_url'] ?? ''),
            'api_version'        => sanitize_text_field($input['api_version'] ?? ''),
            'user_agent'         => sanitize_text_field($input['user_agent'] ?? ''),
            'timeout_seconds'    => absint($input['timeout_seconds'] ?? 0),
            'response_size'      => absint($input['response_size'] ?? 0),
            'cache_ttl_seconds'  => absint($input['cache_ttl_seconds'] ?? 0),
            'api_page_size'      => absint($input['api_page_size'] ?? 0),
            'max_owner_length'   => absint($input['max_owner_length'] ?? 0),
            'max_include_length' => absint($input['max_include_length'] ?? 0),
            'max_repo_length'    => absint($input['max_repo_length'] ?? 0),
            'profile_url'      => esc_url_raw($input['profile_url'] ?? ''),
            'limit'            => absint($input['limit'] ?? 0),
            'sort'             => sanitize_key($input['sort'] ?? ''),
            'direction'        => sanitize_key($input['direction'] ?? ''),
            'type'             => sanitize_key($input['type'] ?? ''),
            'include_forks'    => !empty($input['include_forks']),
            'include_archived' => !empty($input['include_archived']),
        );
        $endpoint = wp_parse_url($validated['api_base_url']);
        $integer_keys = array('timeout_seconds', 'response_size', 'cache_ttl_seconds', 'api_page_size', 'max_owner_length', 'max_include_length', 'max_repo_length');
        $invalid_integer = false;
        foreach ($integer_keys as $integer_key) {
            if ($validated[$integer_key] < 1) {
                $invalid_integer = true;
            }
        }
        $profile_owner = $this->owner_from_profile_url($validated['profile_url'], $validated['max_owner_length']);
        if (!is_array($endpoint) || ($endpoint['scheme'] ?? '') !== 'https' || empty($endpoint['host']) || isset($endpoint['user']) || isset($endpoint['pass']) || !wp_http_validate_url($validated['api_base_url']) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $validated['api_version']) || $validated['user_agent'] === '' || $invalid_integer || is_wp_error($profile_owner) || $validated['limit'] < 1 || $validated['limit'] > $validated['api_page_size'] || !in_array($validated['sort'], array('created', 'updated', 'pushed', 'full_name'), true) || !in_array($validated['direction'], array('asc', 'desc'), true) || !in_array($validated['type'], array('all', 'owner', 'member'), true)) {
            add_settings_error('htgp_settings', 'invalid', __('Configuração rejeitada por validação.', 'humanitastech-github-for-wordpress'));
            return get_option('htgp_settings', array());
        }
        return $validated;
    }

    public function settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Acesso negado.', 'humanitastech-github-for-wordpress'));
        }
        $settings = get_option('htgp_settings', array());
        ?>
        <div class="wrap"><h1><?php esc_html_e('Integração GitHub', 'humanitastech-github-for-wordpress'); ?></h1>
        <?php if (!$this->configuration->ready()) : ?><div class="notice notice-error"><p><?php echo esc_html(implode(' ', $this->configuration->errors())); ?></p></div><?php endif; ?>
        <form action="options.php" method="post"><?php settings_fields('htgp_settings'); ?>
        <table class="form-table" role="presentation">
        <tr><th><label for="htgp_api_base_url"><?php esc_html_e('Endpoint HTTPS da API', 'humanitastech-github-for-wordpress'); ?></label></th><td><input type="url" class="regular-text" id="htgp_api_base_url" name="htgp_settings[api_base_url]" value="<?php echo esc_attr($settings['api_base_url'] ?? ''); ?>" required></td></tr>
        <tr><th><label for="htgp_api_version"><?php esc_html_e('Versão da API', 'humanitastech-github-for-wordpress'); ?></label></th><td><input class="regular-text" id="htgp_api_version" name="htgp_settings[api_version]" value="<?php echo esc_attr($settings['api_version'] ?? ''); ?>" pattern="\d{4}-\d{2}-\d{2}" required></td></tr>
        <tr><th><label for="htgp_user_agent"><?php esc_html_e('Identificação HTTP', 'humanitastech-github-for-wordpress'); ?></label></th><td><input class="regular-text" id="htgp_user_agent" name="htgp_settings[user_agent]" value="<?php echo esc_attr($settings['user_agent'] ?? ''); ?>" required></td></tr>
        <tr><th><label for="htgp_timeout_seconds"><?php esc_html_e('Timeout em segundos', 'humanitastech-github-for-wordpress'); ?></label></th><td><input type="number" id="htgp_timeout_seconds" name="htgp_settings[timeout_seconds]" value="<?php echo esc_attr($settings['timeout_seconds'] ?? ''); ?>" min="1" required></td></tr>
        <tr><th><label for="htgp_response_size"><?php esc_html_e('Limite da resposta em bytes', 'humanitastech-github-for-wordpress'); ?></label></th><td><input type="number" id="htgp_response_size" name="htgp_settings[response_size]" value="<?php echo esc_attr($settings['response_size'] ?? ''); ?>" min="1" required></td></tr>
        <tr><th><label for="htgp_cache_ttl_seconds"><?php esc_html_e('Cache em segundos', 'humanitastech-github-for-wordpress'); ?></label></th><td><input type="number" id="htgp_cache_ttl_seconds" name="htgp_settings[cache_ttl_seconds]" value="<?php echo esc_attr($settings['cache_ttl_seconds'] ?? ''); ?>" min="1" required></td></tr>
        <tr><th><label for="htgp_api_page_size"><?php esc_html_e('Máximo de projetos por consulta', 'humanitastech-github-for-wordpress'); ?></label></th><td><input type="number" id="htgp_api_page_size" name="htgp_settings[api_page_size]" value="<?php echo esc_attr($settings['api_page_size'] ?? ''); ?>" min="1" required></td></tr>
        <tr><th><label for="htgp_max_owner_length"><?php esc_html_e('Limite do identificador da conta', 'humanitastech-github-for-wordpress'); ?></label></th><td><input type="number" id="htgp_max_owner_length" name="htgp_settings[max_owner_length]" value="<?php echo esc_attr($settings['max_owner_length'] ?? ''); ?>" min="1" required></td></tr>
        <tr><th><label for="htgp_max_include_length"><?php esc_html_e('Limite do filtro de inclusão', 'humanitastech-github-for-wordpress'); ?></label></th><td><input type="number" id="htgp_max_include_length" name="htgp_settings[max_include_length]" value="<?php echo esc_attr($settings['max_include_length'] ?? ''); ?>" min="1" required></td></tr>
        <tr><th><label for="htgp_max_repo_length"><?php esc_html_e('Limite do nome de repositório', 'humanitastech-github-for-wordpress'); ?></label></th><td><input type="number" id="htgp_max_repo_length" name="htgp_settings[max_repo_length]" value="<?php echo esc_attr($settings['max_repo_length'] ?? ''); ?>" min="1" required></td></tr>
        <tr><th><label for="htgp_profile_url"><?php esc_html_e('URL do perfil no GitHub', 'humanitastech-github-for-wordpress'); ?></label></th><td><input type="url" class="regular-text" id="htgp_profile_url" name="htgp_settings[profile_url]" value="<?php echo esc_attr($settings['profile_url'] ?? ''); ?>" aria-describedby="htgp_profile_url_description" required><p class="description" id="htgp_profile_url_description"><?php esc_html_e('Informe o perfil de qualquer usuário ou organização, por exemplo: https://github.com/nome-da-conta', 'humanitastech-github-for-wordpress'); ?></p></td></tr>
        <tr><th><label for="htgp_limit"><?php esc_html_e('Quantidade', 'humanitastech-github-for-wordpress'); ?></label></th><td><input type="number" id="htgp_limit" name="htgp_settings[limit]" value="<?php echo esc_attr($settings['limit'] ?? ''); ?>" min="1" max="<?php echo esc_attr($this->configuration->get('api_page_size')); ?>" required></td></tr>
        <tr><th><label for="htgp_sort"><?php esc_html_e('Ordenação', 'humanitastech-github-for-wordpress'); ?></label></th><td><select id="htgp_sort" name="htgp_settings[sort]" required><option value=""></option><?php foreach (array('created','updated','pushed','full_name') as $value) : ?><option value="<?php echo esc_attr($value); ?>" <?php selected($settings['sort'] ?? '', $value); ?>><?php echo esc_html($value); ?></option><?php endforeach; ?></select></td></tr>
        <tr><th><label for="htgp_direction"><?php esc_html_e('Direção', 'humanitastech-github-for-wordpress'); ?></label></th><td><select id="htgp_direction" name="htgp_settings[direction]" required><option value=""></option><?php foreach (array('asc','desc') as $value) : ?><option value="<?php echo esc_attr($value); ?>" <?php selected($settings['direction'] ?? '', $value); ?>><?php echo esc_html($value); ?></option><?php endforeach; ?></select></td></tr>
        <tr><th><label for="htgp_type"><?php esc_html_e('Vínculo', 'humanitastech-github-for-wordpress'); ?></label></th><td><select id="htgp_type" name="htgp_settings[type]" required><option value=""></option><?php foreach (array('all','owner','member') as $value) : ?><option value="<?php echo esc_attr($value); ?>" <?php selected($settings['type'] ?? '', $value); ?>><?php echo esc_html($value); ?></option><?php endforeach; ?></select></td></tr>
        <tr><th><?php esc_html_e('Inclusões', 'humanitastech-github-for-wordpress'); ?></th><td><label><input type="checkbox" name="htgp_settings[include_forks]" value="1" <?php checked(!empty($settings['include_forks'])); ?>> <?php esc_html_e('Forks', 'humanitastech-github-for-wordpress'); ?></label><br><label><input type="checkbox" name="htgp_settings[include_archived]" value="1" <?php checked(!empty($settings['include_archived'])); ?>> <?php esc_html_e('Arquivados', 'humanitastech-github-for-wordpress'); ?></label></td></tr>
        </table><?php submit_button(); ?></form></div>
        <?php
    }

    private function settings() {
        $settings = get_option('htgp_settings', array());
        $required = array('profile_url', 'limit', 'sort', 'direction', 'type');
        foreach ($required as $key) {
            if (!isset($settings[$key]) || $settings[$key] === '') {
                return new WP_Error('htgp_settings', __('Integração não configurada.', 'humanitastech-github-for-wordpress'));
            }
        }
        return $settings;
    }

    private function owner_from_profile_url($url, $maximum_length = null) {
        $parts = wp_parse_url(is_string($url) ? trim($url) : '');
        if (!is_array($parts)) {
            return new WP_Error('htgp_profile_url', __('URL de perfil inválida.', 'humanitastech-github-for-wordpress'));
        }
        $host = strtolower(rtrim($parts['host'] ?? '', '.'));
        if (($parts['scheme'] ?? '') !== 'https' || !in_array($host, array('github.com', 'www.github.com'), true) || isset($parts['port']) || isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment']) || !wp_http_validate_url($url)) {
            return new WP_Error('htgp_profile_url', __('URL de perfil inválida.', 'humanitastech-github-for-wordpress'));
        }
        $segments = array_values(array_filter(explode('/', trim($parts['path'] ?? '', '/')), 'strlen'));
        $owner = count($segments) === 1 ? rawurldecode($segments[0]) : '';
        $limit = $maximum_length === null ? $this->configuration->get('max_owner_length') : (int) $maximum_length;
        if ($owner === '' || $limit < 1 || strlen($owner) > $limit || !preg_match('/^[A-Za-z0-9](?:[A-Za-z0-9-]*[A-Za-z0-9])?$/', $owner)) {
            return new WP_Error('htgp_profile_url', __('A URL deve apontar diretamente para um perfil.', 'humanitastech-github-for-wordpress'));
        }
        return $owner;
    }

    private function validate_repository_list($value) {
        if (!is_string($value) || strlen($value) > $this->configuration->get('max_include_length')) {
            return new WP_Error('htgp_include', __('Filtro inválido.', 'humanitastech-github-for-wordpress'));
        }
        if ($value === '') {
            return array();
        }
        $items = array_filter(array_map('trim', explode(',', $value)));
        foreach ($items as $item) {
            if (strlen($item) > $this->configuration->get('max_repo_length') || !preg_match('/^[A-Za-z0-9._-]+$/', $item)) {
                return new WP_Error('htgp_include', __('Filtro inválido.', 'humanitastech-github-for-wordpress'));
            }
        }
        return array_values(array_unique($items));
    }

    private function notice(WP_Error $error) {
        $message = current_user_can('manage_options') ? $error->get_error_message() : __('Conteúdo indisponível.', 'humanitastech-github-for-wordpress');
        return '<p class="htgp-notice" role="status">' . esc_html($message) . '</p>';
    }
}

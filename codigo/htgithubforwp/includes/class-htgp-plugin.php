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
        wp_enqueue_style('htgp-project-cards', HTGP_URL . 'assets/css/project-cards.css', array(), HTGP_VERSION);
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
        <section class="htgp-projects" aria-label="<?php esc_attr_e('Projetos públicos', 'htgithubforwp'); ?>">
            <?php foreach ($repositories as $repository) : ?>
                <?php
                $repository_owner = sanitize_text_field($repository['owner']['login'] ?? $owner);
                $latest_release = $this->client->latest_release($repository_owner, $repository['name']);
                $latest_release = is_array($latest_release) ? $latest_release : null;
                $release_date = '';
                if ($latest_release && !empty($latest_release['published_at'])) {
                    $release_timestamp = strtotime($latest_release['published_at']);
                    $release_date = $release_timestamp ? wp_date(get_option('date_format'), $release_timestamp) : '';
                }
                $updated_timestamp = !empty($repository['updated_at']) ? strtotime($repository['updated_at']) : false;
                $updated_date = $updated_timestamp ? wp_date(get_option('date_format'), $updated_timestamp) : '';
                ?>
                <article class="htgp-project">
                    <div class="htgp-project__header">
                        <span class="htgp-project__eyebrow"><?php esc_html_e('Repositório público', 'htgithubforwp'); ?></span>
                        <span class="htgp-project__mark" aria-hidden="true">↗</span>
                    </div>
                    <h3 class="htgp-project__title"><a href="<?php echo esc_url($repository['html_url']); ?>" target="_blank" rel="noopener noreferrer external"><?php echo esc_html($repository['name']); ?></a></h3>
                    <p class="htgp-project__description"><?php echo esc_html(!empty($repository['description']) ? $repository['description'] : __('Sem descrição no GitHub.', 'htgithubforwp')); ?></p>
                    <dl class="htgp-project__metadata">
                        <div><dt><?php esc_html_e('Autor', 'htgithubforwp'); ?></dt><dd><?php echo esc_html($repository_owner); ?></dd></div>
                        <div><dt><?php esc_html_e('Versão', 'htgithubforwp'); ?></dt><dd><?php echo esc_html($latest_release ? $latest_release['tag_name'] : __('Sem release', 'htgithubforwp')); ?></dd></div>
                        <?php if ($release_date !== '') : ?><div><dt><?php esc_html_e('Última versão', 'htgithubforwp'); ?></dt><dd><?php echo esc_html($release_date); ?></dd></div><?php endif; ?>
                        <?php if ($updated_date !== '') : ?><div><dt><?php esc_html_e('Atualizado', 'htgithubforwp'); ?></dt><dd><?php echo esc_html($updated_date); ?></dd></div><?php endif; ?>
                        <?php if (!empty($repository['language'])) : ?><div><dt><?php esc_html_e('Linguagem', 'htgithubforwp'); ?></dt><dd><?php echo esc_html($repository['language']); ?></dd></div><?php endif; ?>
                        <div><dt><?php esc_html_e('Estrelas', 'htgithubforwp'); ?></dt><dd><?php echo esc_html(number_format_i18n((int) ($repository['stargazers_count'] ?? 0))); ?></dd></div>
                        <div><dt><?php esc_html_e('Forks', 'htgithubforwp'); ?></dt><dd><?php echo esc_html(number_format_i18n((int) ($repository['forks_count'] ?? 0))); ?></dd></div>
                    </dl>
                </article>
            <?php endforeach; ?>
        </section>
        <?php
        return ob_get_clean();
    }

    public function admin_menu() {
        add_options_page(__('Integração GitHub', 'htgithubforwp'), __('Integração GitHub', 'htgithubforwp'), 'manage_options', 'htgp-settings', array($this, 'settings_page'));
    }

    public function register_settings() {
        register_setting('htgp_settings', 'htgp_settings', array('sanitize_callback' => array($this, 'validate_settings')));
    }

    public function validate_settings($input) {
        if (!current_user_can('manage_options') || !is_array($input)) {
            add_settings_error('htgp_settings', 'forbidden', __('Configuração rejeitada.', 'htgithubforwp'));
            return get_option('htgp_settings', array());
        }
        foreach ($input as $value) {
            if (!is_scalar($value)) {
                add_settings_error('htgp_settings', 'invalid_type', __('Configuração rejeitada.', 'htgithubforwp'));
                return get_option('htgp_settings', array());
            }
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
        foreach (array('timeout_seconds' => 'max_timeout_seconds', 'response_size' => 'max_response_size', 'api_page_size' => 'max_api_page_size') as $key => $maximum) {
            if ($validated[$key] > $this->configuration->get($maximum)) {
                $invalid_integer = true;
            }
        }
        if ($this->configuration->get('github_token') !== '' && $this->configuration->origin($validated['api_base_url']) !== $this->configuration->get('token_origin')) {
            $invalid_integer = true;
        }
        $profile_owner = $this->owner_from_profile_url($validated['profile_url'], $validated['max_owner_length']);
        if (!is_array($endpoint) || ($endpoint['scheme'] ?? '') !== 'https' || empty($endpoint['host']) || isset($endpoint['user']) || isset($endpoint['pass']) || !wp_http_validate_url($validated['api_base_url']) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $validated['api_version']) || $validated['user_agent'] === '' || $invalid_integer || is_wp_error($profile_owner) || $validated['limit'] < 1 || $validated['limit'] > $validated['api_page_size'] || !in_array($validated['sort'], array('created', 'updated', 'pushed', 'full_name'), true) || !in_array($validated['direction'], array('asc', 'desc'), true) || !in_array($validated['type'], array('all', 'owner', 'member'), true)) {
            add_settings_error('htgp_settings', 'invalid', __('Configuração rejeitada por validação.', 'htgithubforwp'));
            return get_option('htgp_settings', array());
        }
        do_action('htgp_security_event', 'configuration_validated');
        return $validated;
    }

    public function settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Acesso negado.', 'htgithubforwp'));
        }
        $settings = get_option('htgp_settings', array());
        ?>
        <div class="wrap"><h1><?php esc_html_e('Integração GitHub', 'htgithubforwp'); ?></h1>
        <?php if (!$this->configuration->ready()) : ?><div class="notice notice-error"><p><?php echo esc_html(implode(' ', $this->configuration->errors())); ?></p></div><?php endif; ?>
        <form action="options.php" method="post"><?php settings_fields('htgp_settings'); ?>
        <table class="form-table" role="presentation">
        <tr><th><label for="htgp_api_base_url"><?php esc_html_e('Endpoint HTTPS da API', 'htgithubforwp'); ?></label></th><td><input type="url" class="regular-text" id="htgp_api_base_url" name="htgp_settings[api_base_url]" value="<?php echo esc_attr($settings['api_base_url'] ?? ''); ?>" required></td></tr>
        <tr><th><label for="htgp_api_version"><?php esc_html_e('Versão da API', 'htgithubforwp'); ?></label></th><td><input class="regular-text" id="htgp_api_version" name="htgp_settings[api_version]" value="<?php echo esc_attr($settings['api_version'] ?? ''); ?>" pattern="\d{4}-\d{2}-\d{2}" required></td></tr>
        <tr><th><label for="htgp_user_agent"><?php esc_html_e('Identificação HTTP', 'htgithubforwp'); ?></label></th><td><input class="regular-text" id="htgp_user_agent" name="htgp_settings[user_agent]" value="<?php echo esc_attr($settings['user_agent'] ?? ''); ?>" required></td></tr>
        <tr><th><label for="htgp_timeout_seconds"><?php esc_html_e('Timeout em segundos', 'htgithubforwp'); ?></label></th><td><input type="number" id="htgp_timeout_seconds" name="htgp_settings[timeout_seconds]" value="<?php echo esc_attr($settings['timeout_seconds'] ?? ''); ?>" min="1" required></td></tr>
        <tr><th><label for="htgp_response_size"><?php esc_html_e('Limite da resposta em bytes', 'htgithubforwp'); ?></label></th><td><input type="number" id="htgp_response_size" name="htgp_settings[response_size]" value="<?php echo esc_attr($settings['response_size'] ?? ''); ?>" min="1" required></td></tr>
        <tr><th><label for="htgp_cache_ttl_seconds"><?php esc_html_e('Cache em segundos', 'htgithubforwp'); ?></label></th><td><input type="number" id="htgp_cache_ttl_seconds" name="htgp_settings[cache_ttl_seconds]" value="<?php echo esc_attr($settings['cache_ttl_seconds'] ?? ''); ?>" min="1" required></td></tr>
        <tr><th><label for="htgp_api_page_size"><?php esc_html_e('Máximo de projetos por consulta', 'htgithubforwp'); ?></label></th><td><input type="number" id="htgp_api_page_size" name="htgp_settings[api_page_size]" value="<?php echo esc_attr($settings['api_page_size'] ?? ''); ?>" min="1" required></td></tr>
        <tr><th><label for="htgp_max_owner_length"><?php esc_html_e('Limite do identificador da conta', 'htgithubforwp'); ?></label></th><td><input type="number" id="htgp_max_owner_length" name="htgp_settings[max_owner_length]" value="<?php echo esc_attr($settings['max_owner_length'] ?? ''); ?>" min="1" required></td></tr>
        <tr><th><label for="htgp_max_include_length"><?php esc_html_e('Limite do filtro de inclusão', 'htgithubforwp'); ?></label></th><td><input type="number" id="htgp_max_include_length" name="htgp_settings[max_include_length]" value="<?php echo esc_attr($settings['max_include_length'] ?? ''); ?>" min="1" required></td></tr>
        <tr><th><label for="htgp_max_repo_length"><?php esc_html_e('Limite do nome de repositório', 'htgithubforwp'); ?></label></th><td><input type="number" id="htgp_max_repo_length" name="htgp_settings[max_repo_length]" value="<?php echo esc_attr($settings['max_repo_length'] ?? ''); ?>" min="1" required></td></tr>
        <tr><th><label for="htgp_profile_url"><?php esc_html_e('URL do perfil no GitHub', 'htgithubforwp'); ?></label></th><td><input type="url" class="regular-text" id="htgp_profile_url" name="htgp_settings[profile_url]" value="<?php echo esc_attr($settings['profile_url'] ?? ''); ?>" aria-describedby="htgp_profile_url_description" required><p class="description" id="htgp_profile_url_description"><?php esc_html_e('Informe a URL do perfil em uma origem aprovada pelo ambiente.', 'htgithubforwp'); ?></p></td></tr>
        <tr><th><label for="htgp_limit"><?php esc_html_e('Quantidade', 'htgithubforwp'); ?></label></th><td><input type="number" id="htgp_limit" name="htgp_settings[limit]" value="<?php echo esc_attr($settings['limit'] ?? ''); ?>" min="1" max="<?php echo esc_attr($this->configuration->get('api_page_size')); ?>" required></td></tr>
        <tr><th><label for="htgp_sort"><?php esc_html_e('Ordenação', 'htgithubforwp'); ?></label></th><td><select id="htgp_sort" name="htgp_settings[sort]" required><option value=""></option><?php foreach (array('created','updated','pushed','full_name') as $value) : ?><option value="<?php echo esc_attr($value); ?>" <?php selected($settings['sort'] ?? '', $value); ?>><?php echo esc_html($value); ?></option><?php endforeach; ?></select></td></tr>
        <tr><th><label for="htgp_direction"><?php esc_html_e('Direção', 'htgithubforwp'); ?></label></th><td><select id="htgp_direction" name="htgp_settings[direction]" required><option value=""></option><?php foreach (array('asc','desc') as $value) : ?><option value="<?php echo esc_attr($value); ?>" <?php selected($settings['direction'] ?? '', $value); ?>><?php echo esc_html($value); ?></option><?php endforeach; ?></select></td></tr>
        <tr><th><label for="htgp_type"><?php esc_html_e('Vínculo', 'htgithubforwp'); ?></label></th><td><select id="htgp_type" name="htgp_settings[type]" required><option value=""></option><?php foreach (array('all','owner','member') as $value) : ?><option value="<?php echo esc_attr($value); ?>" <?php selected($settings['type'] ?? '', $value); ?>><?php echo esc_html($value); ?></option><?php endforeach; ?></select></td></tr>
        <tr><th><?php esc_html_e('Inclusões', 'htgithubforwp'); ?></th><td><label><input type="checkbox" name="htgp_settings[include_forks]" value="1" <?php checked(!empty($settings['include_forks'])); ?>> <?php esc_html_e('Forks', 'htgithubforwp'); ?></label><br><label><input type="checkbox" name="htgp_settings[include_archived]" value="1" <?php checked(!empty($settings['include_archived'])); ?>> <?php esc_html_e('Arquivados', 'htgithubforwp'); ?></label></td></tr>
        </table><?php submit_button(); ?></form></div>
        <?php
    }

    private function settings() {
        $settings = get_option('htgp_settings', array());
        $required = array('profile_url', 'limit', 'sort', 'direction', 'type');
        foreach ($required as $key) {
            if (!isset($settings[$key]) || $settings[$key] === '') {
                return new WP_Error('htgp_settings', __('Integração não configurada.', 'htgithubforwp'));
            }
        }
        return $settings;
    }

    private function owner_from_profile_url($url, $maximum_length = null) {
        $parts = wp_parse_url(is_string($url) ? trim($url) : '');
        if (!is_array($parts)) {
            return new WP_Error('htgp_profile_url', __('URL de perfil inválida.', 'htgithubforwp'));
        }
        if (($parts['scheme'] ?? '') !== 'https' || !$this->configuration->allowed_url($url, 'profile_origins') || isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])) {
            return new WP_Error('htgp_profile_url', __('URL de perfil inválida.', 'htgithubforwp'));
        }
        $segments = array_values(array_filter(explode('/', trim($parts['path'] ?? '', '/')), 'strlen'));
        $owner = count($segments) === 1 ? rawurldecode($segments[0]) : '';
        $limit = $maximum_length === null ? $this->configuration->get('max_owner_length') : (int) $maximum_length;
        if ($owner === '' || $limit < 1 || strlen($owner) > $limit || !preg_match('/^[A-Za-z0-9](?:[A-Za-z0-9-]*[A-Za-z0-9])?$/', $owner)) {
            return new WP_Error('htgp_profile_url', __('A URL deve apontar diretamente para um perfil.', 'htgithubforwp'));
        }
        return $owner;
    }

    private function validate_repository_list($value) {
        if (!is_string($value) || strlen($value) > $this->configuration->get('max_include_length')) {
            return new WP_Error('htgp_include', __('Filtro inválido.', 'htgithubforwp'));
        }
        if ($value === '') {
            return array();
        }
        $items = array_filter(array_map('trim', explode(',', $value)));
        foreach ($items as $item) {
            if (strlen($item) > $this->configuration->get('max_repo_length') || !preg_match('/^[A-Za-z0-9._-]+$/', $item)) {
                return new WP_Error('htgp_include', __('Filtro inválido.', 'htgithubforwp'));
            }
        }
        return array_values(array_unique($items));
    }

    private function notice(WP_Error $error) {
        $message = current_user_can('manage_options') ? $error->get_error_message() : __('Conteúdo indisponível.', 'htgithubforwp');
        return '<p class="htgp-notice" role="status">' . esc_html($message) . '</p>';
    }
}


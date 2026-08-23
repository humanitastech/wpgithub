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
        $repositories = $this->client->repositories($settings['owner'], $query);
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
        <section class="htgp-projects" aria-label="<?php esc_attr_e('Projetos públicos', 'humanitastech-github-projects'); ?>">
            <?php foreach ($repositories as $repository) : ?>
                <article class="htgp-project">
                    <h3 class="htgp-project__title"><a href="<?php echo esc_url($repository['html_url']); ?>" target="_blank" rel="noopener noreferrer external"><?php echo esc_html($repository['name']); ?></a></h3>
                    <?php if (!empty($repository['description'])) : ?><p class="htgp-project__description"><?php echo esc_html($repository['description']); ?></p><?php endif; ?>
                    <dl class="htgp-project__metadata">
                        <?php if (!empty($repository['language'])) : ?><div><dt><?php esc_html_e('Linguagem', 'humanitastech-github-projects'); ?></dt><dd><?php echo esc_html($repository['language']); ?></dd></div><?php endif; ?>
                        <div><dt><?php esc_html_e('Estrelas', 'humanitastech-github-projects'); ?></dt><dd><?php echo esc_html(number_format_i18n((int) ($repository['stargazers_count'] ?? 0))); ?></dd></div>
                        <div><dt><?php esc_html_e('Forks', 'humanitastech-github-projects'); ?></dt><dd><?php echo esc_html(number_format_i18n((int) ($repository['forks_count'] ?? 0))); ?></dd></div>
                    </dl>
                </article>
            <?php endforeach; ?>
        </section>
        <?php
        return ob_get_clean();
    }

    public function admin_menu() {
        add_options_page(__('Integração GitHub', 'humanitastech-github-projects'), __('Integração GitHub', 'humanitastech-github-projects'), 'manage_options', 'htgp-settings', array($this, 'settings_page'));
    }

    public function register_settings() {
        register_setting('htgp_settings', 'htgp_settings', array('sanitize_callback' => array($this, 'validate_settings')));
    }

    public function validate_settings($input) {
        if (!current_user_can('manage_options') || !is_array($input)) {
            add_settings_error('htgp_settings', 'forbidden', __('Configuração rejeitada.', 'humanitastech-github-projects'));
            return get_option('htgp_settings', array());
        }
        $validated = array(
            'owner'            => sanitize_text_field($input['owner'] ?? ''),
            'limit'            => absint($input['limit'] ?? 0),
            'sort'             => sanitize_key($input['sort'] ?? ''),
            'direction'        => sanitize_key($input['direction'] ?? ''),
            'type'             => sanitize_key($input['type'] ?? ''),
            'include_forks'    => !empty($input['include_forks']),
            'include_archived' => !empty($input['include_archived']),
        );
        if ($validated['limit'] < 1 || $validated['limit'] > $this->configuration->get('api_page_size') || !in_array($validated['sort'], array('created', 'updated', 'pushed', 'full_name'), true) || !in_array($validated['direction'], array('asc', 'desc'), true) || !in_array($validated['type'], array('all', 'owner', 'member'), true)) {
            add_settings_error('htgp_settings', 'invalid', __('Configuração rejeitada por validação.', 'humanitastech-github-projects'));
            return get_option('htgp_settings', array());
        }
        return $validated;
    }

    public function settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Acesso negado.', 'humanitastech-github-projects'));
        }
        $settings = get_option('htgp_settings', array());
        ?>
        <div class="wrap"><h1><?php esc_html_e('Integração GitHub', 'humanitastech-github-projects'); ?></h1>
        <?php if (!$this->configuration->ready()) : ?><div class="notice notice-error"><p><?php echo esc_html(implode(' ', $this->configuration->errors())); ?></p></div><?php endif; ?>
        <form action="options.php" method="post"><?php settings_fields('htgp_settings'); ?>
        <table class="form-table" role="presentation">
        <tr><th><label for="htgp_owner"><?php esc_html_e('Conta', 'humanitastech-github-projects'); ?></label></th><td><input id="htgp_owner" name="htgp_settings[owner]" value="<?php echo esc_attr($settings['owner'] ?? ''); ?>" required></td></tr>
        <tr><th><label for="htgp_limit"><?php esc_html_e('Quantidade', 'humanitastech-github-projects'); ?></label></th><td><input type="number" id="htgp_limit" name="htgp_settings[limit]" value="<?php echo esc_attr($settings['limit'] ?? ''); ?>" min="1" max="<?php echo esc_attr($this->configuration->get('api_page_size')); ?>" required></td></tr>
        <tr><th><label for="htgp_sort"><?php esc_html_e('Ordenação', 'humanitastech-github-projects'); ?></label></th><td><select id="htgp_sort" name="htgp_settings[sort]" required><option value=""></option><?php foreach (array('created','updated','pushed','full_name') as $value) : ?><option value="<?php echo esc_attr($value); ?>" <?php selected($settings['sort'] ?? '', $value); ?>><?php echo esc_html($value); ?></option><?php endforeach; ?></select></td></tr>
        <tr><th><label for="htgp_direction"><?php esc_html_e('Direção', 'humanitastech-github-projects'); ?></label></th><td><select id="htgp_direction" name="htgp_settings[direction]" required><option value=""></option><?php foreach (array('asc','desc') as $value) : ?><option value="<?php echo esc_attr($value); ?>" <?php selected($settings['direction'] ?? '', $value); ?>><?php echo esc_html($value); ?></option><?php endforeach; ?></select></td></tr>
        <tr><th><label for="htgp_type"><?php esc_html_e('Vínculo', 'humanitastech-github-projects'); ?></label></th><td><select id="htgp_type" name="htgp_settings[type]" required><option value=""></option><?php foreach (array('all','owner','member') as $value) : ?><option value="<?php echo esc_attr($value); ?>" <?php selected($settings['type'] ?? '', $value); ?>><?php echo esc_html($value); ?></option><?php endforeach; ?></select></td></tr>
        <tr><th><?php esc_html_e('Inclusões', 'humanitastech-github-projects'); ?></th><td><label><input type="checkbox" name="htgp_settings[include_forks]" value="1" <?php checked(!empty($settings['include_forks'])); ?>> <?php esc_html_e('Forks', 'humanitastech-github-projects'); ?></label><br><label><input type="checkbox" name="htgp_settings[include_archived]" value="1" <?php checked(!empty($settings['include_archived'])); ?>> <?php esc_html_e('Arquivados', 'humanitastech-github-projects'); ?></label></td></tr>
        </table><?php submit_button(); ?></form></div>
        <?php
    }

    private function settings() {
        $settings = get_option('htgp_settings', array());
        $required = array('owner', 'limit', 'sort', 'direction', 'type');
        foreach ($required as $key) {
            if (!isset($settings[$key]) || $settings[$key] === '') {
                return new WP_Error('htgp_settings', __('Integração não configurada.', 'humanitastech-github-projects'));
            }
        }
        return $settings;
    }

    private function validate_repository_list($value) {
        if (!is_string($value) || strlen($value) > $this->configuration->get('max_include_length')) {
            return new WP_Error('htgp_include', __('Filtro inválido.', 'humanitastech-github-projects'));
        }
        if ($value === '') {
            return array();
        }
        $items = array_filter(array_map('trim', explode(',', $value)));
        foreach ($items as $item) {
            if (strlen($item) > $this->configuration->get('max_repo_length') || !preg_match('/^[A-Za-z0-9._-]+$/', $item)) {
                return new WP_Error('htgp_include', __('Filtro inválido.', 'humanitastech-github-projects'));
            }
        }
        return array_values(array_unique($items));
    }

    private function notice(WP_Error $error) {
        $message = current_user_can('manage_options') ? $error->get_error_message() : __('Conteúdo indisponível.', 'humanitastech-github-projects');
        return '<p class="htgp-notice" role="status">' . esc_html($message) . '</p>';
    }
}

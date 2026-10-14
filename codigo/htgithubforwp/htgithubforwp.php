<?php
/**
 * Plugin Name: HumanitasTech GitHub for WordPress
 * Plugin URI:  https://humanitastech.com/wpgithub/
 * Description: Exibe repositórios públicos do GitHub em uma grade curável, responsiva e armazenada em cache.
 * Version:     1.0.1
 * Author:      HumanitasTech
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: htgithubforwp
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('HTGP_VERSION')) {
    define('HTGP_VERSION', '1.0.1');
}
if (!defined('HTGP_FILE')) {
    define('HTGP_FILE', __FILE__);
}
if (!defined('HTGP_DIR')) {
    define('HTGP_DIR', plugin_dir_path(__FILE__));
}
if (!defined('HTGP_URL')) {
    define('HTGP_URL', plugin_dir_url(__FILE__));
}

require_once HTGP_DIR . 'includes/class-htgp-configuration.php';
require_once HTGP_DIR . 'includes/class-htgp-github-client.php';
require_once HTGP_DIR . 'includes/class-htgp-plugin.php';

HTGP_Plugin::instance();


<?php
/**
 * Plugin Name: HumanitasTech GitHub Projects
 * Plugin URI:  https://humanitastech.com/
 * Description: Exibe repositórios públicos do GitHub em uma grade curável, responsiva e armazenada em cache.
 * Version:     1.0.0
 * Author:      HumanitasTech
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: humanitastech-github-projects
 */

if (!defined('ABSPATH')) {
    exit;
}

define('HTGP_VERSION', '1.0.0');
define('HTGP_FILE', __FILE__);
define('HTGP_DIR', plugin_dir_path(__FILE__));
define('HTGP_URL', plugin_dir_url(__FILE__));

require_once HTGP_DIR . 'includes/class-htgp-configuration.php';
require_once HTGP_DIR . 'includes/class-htgp-github-client.php';
require_once HTGP_DIR . 'includes/class-htgp-plugin.php';

HTGP_Plugin::instance();

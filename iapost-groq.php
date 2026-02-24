<?php
/**
 * Plugin Name:  IAPOST Groq
 * Plugin URI:   https://github.com/sergioparedes/iapost-groq
 * Description:  Genera artículos SEO completos con la API de Groq (LLaMA). Integra con Yoast SEO, crea bloques Gutenberg y gestiona meta social (OG + Twitter). Incluye promptbooks por tipo de contenido y rol periodístico.
 * Version:      1.1.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author:       Sergio Paredes & Claude Code
 * Author URI:   https://github.com/sergioparedes
 * License:      GPL-2.0+
 * License URI:  https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:  iapost-groq
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'IAPOSTGROQ_VERSION', '1.1.0' );
define( 'IAPOSTGROQ_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'IAPOSTGROQ_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'IAPOSTGROQ_PLUGIN_FILE', __FILE__ );

/**
 * Main plugin class (singleton).
 */
final class IAPOST_Groq {

    /** @var IAPOST_Groq|null */
    private static $instance = null;

    public static function instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }

    private function load_dependencies(): void {
        require_once IAPOSTGROQ_PLUGIN_DIR . 'includes/class-promptbooks.php';
        require_once IAPOSTGROQ_PLUGIN_DIR . 'includes/class-groq-api.php';
        require_once IAPOSTGROQ_PLUGIN_DIR . 'includes/class-content-generator.php';
        require_once IAPOSTGROQ_PLUGIN_DIR . 'includes/class-post-creator.php';
        require_once IAPOSTGROQ_PLUGIN_DIR . 'includes/class-admin.php';
    }

    private function init_hooks(): void {
        new IAPOSTGROQ_Admin();
    }

    /** Activation hook. */
    public static function activate(): void {
        add_option( 'iapostgroq_provider', 'groq' );
        add_option( 'iapostgroq_api_key', '' );
        add_option( 'iapostgroq_openai_api_key', '' );
        add_option( 'iapostgroq_model', 'llama-3.3-70b-versatile' );
        add_option( 'iapostgroq_content_type', 'post_blog' );
        add_option( 'iapostgroq_journalist_role', 'generalista' );
    }
}

register_activation_hook( IAPOSTGROQ_PLUGIN_FILE, array( 'IAPOST_Groq', 'activate' ) );

/**
 * Global accessor.
 */
function iapostgroq(): IAPOST_Groq {
    return IAPOST_Groq::instance();
}

iapostgroq();

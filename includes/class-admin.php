<?php
/**
 * Admin Class
 *
 * @package IAPOST_Groq
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IAPOSTGROQ_Admin {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

        // AJAX — generator
        add_action( 'wp_ajax_iapostgroq_generate',      array( $this, 'ajax_generate' ) );
        add_action( 'wp_ajax_iapostgroq_rewrite',       array( $this, 'ajax_rewrite' ) );
        add_action( 'wp_ajax_iapostgroq_publish',       array( $this, 'ajax_publish' ) );

        // AJAX — tags search
        add_action( 'wp_ajax_iapostgroq_search_tags', array( $this, 'ajax_search_tags' ) );

        // AJAX — settings
        add_action( 'wp_ajax_iapostgroq_test_api',               array( $this, 'ajax_test_api' ) );
        add_action( 'wp_ajax_iapostgroq_save_settings',          array( $this, 'ajax_save_settings' ) );
        add_action( 'wp_ajax_iapostgroq_save_custom_promptbook', array( $this, 'ajax_save_custom_promptbook' ) );
        add_action( 'wp_ajax_iapostgroq_delete_custom_promptbook', array( $this, 'ajax_delete_custom_promptbook' ) );
    }

    // ─── Menu ────────────────────────────────────────────────────────────────

    public function add_admin_menu(): void {
        add_menu_page(
            'IAPOST Groq',
            'IAPOST Groq',
            'edit_posts',
            'iapostgroq',
            array( $this, 'render_generator_page' ),
            'dashicons-edit-large',
            25
        );

        add_submenu_page( 'iapostgroq', 'Generador — IAPOST Groq',     'Generador',     'edit_posts',     'iapostgroq',          array( $this, 'render_generator_page' ) );
        add_submenu_page( 'iapostgroq', 'Configuración — IAPOST Groq', 'Configuración', 'manage_options', 'iapostgroq-settings', array( $this, 'render_settings_page' ) );
    }

    // ─── Enqueue ─────────────────────────────────────────────────────────────

    public function enqueue_scripts( string $hook ): void {
        $allowed = array( 'toplevel_page_iapostgroq', 'iapost-groq_page_iapostgroq-settings' );
        if ( ! in_array( $hook, $allowed, true ) ) {
            return;
        }

        wp_enqueue_style( 'iapostgroq_admin', IAPOSTGROQ_PLUGIN_URL . 'assets/admin.css', array(), IAPOSTGROQ_VERSION );
        wp_enqueue_script( 'jquery-ui-autocomplete' );
        wp_enqueue_script( 'iapostgroq_ajax',  IAPOSTGROQ_PLUGIN_URL . 'assets/admin.js',  array( 'jquery', 'jquery-ui-autocomplete' ), IAPOSTGROQ_VERSION, true );

        wp_localize_script( 'iapostgroq_ajax', 'IAPOSTGROQ', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'iapostgroq_nonce' ),
            'models'   => IAPOSTGROQ_Groq_API::available_models(),
            'provider' => get_option( 'iapostgroq_provider', 'groq' ),
            'strings'  => array(
                'generating'    => 'Generando contenido…',
                'rewriting'     => 'Re-redactando…',
                'publishing'    => 'Creando borrador…',
                'testing'       => 'Verificando conexión…',
                'saving'        => 'Guardando…',
                'deleting'      => 'Eliminando…',
                'error'         => 'Error inesperado. Inténtalo de nuevo.',
                'edit_post'     => 'Editar entrada',
                'confirm_delete'=> '¿Eliminar este elemento? Esta acción no se puede deshacer.',
            ),
        ) );
    }

    // ─── Nonce check ─────────────────────────────────────────────────────────

    private function verify_nonce(): void {
        if ( ! check_ajax_referer( 'iapostgroq_nonce', 'nonce', false ) ) {
            wp_send_json_error( array( 'message' => 'Nonce inválido.' ), 403 );
        }
    }

    // ─── AJAX: Generator ─────────────────────────────────────────────────────

    public function ajax_generate(): void {
        $this->verify_nonce();
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( array( 'message' => 'Permisos insuficientes.' ), 403 );
        }

        $noticia         = sanitize_textarea_field( wp_unslash( $_POST['noticia'] ?? '' ) );
        $enfoque         = sanitize_textarea_field( wp_unslash( $_POST['enfoque'] ?? '' ) );
        $content_type    = sanitize_key( $_POST['content_type'] ?? '' );
        $journalist_role = sanitize_key( $_POST['journalist_role'] ?? '' );

        if ( empty( $noticia ) || empty( $enfoque ) ) {
            wp_send_json_error( array( 'message' => 'La noticia y el enfoque son obligatorios.' ) );
        }

        try {
            $generator = new IAPOSTGROQ_Content_Generator();
            $data      = $generator->generate( $noticia, $enfoque, '', $content_type, $journalist_role );
            wp_send_json_success( $data );
        } catch ( RuntimeException $e ) {
            wp_send_json_error( array( 'message' => $e->getMessage() ) );
        }
    }

    public function ajax_rewrite(): void {
        $this->verify_nonce();
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( array( 'message' => 'Permisos insuficientes.' ), 403 );
        }

        $noticia         = sanitize_textarea_field( wp_unslash( $_POST['noticia'] ?? '' ) );
        $enfoque         = sanitize_textarea_field( wp_unslash( $_POST['enfoque'] ?? '' ) );
        $extra           = sanitize_textarea_field( wp_unslash( $_POST['extra_instructions'] ?? '' ) );
        $content_type    = sanitize_key( $_POST['content_type'] ?? '' );
        $journalist_role = sanitize_key( $_POST['journalist_role'] ?? '' );

        if ( empty( $noticia ) || empty( $enfoque ) ) {
            wp_send_json_error( array( 'message' => 'La noticia y el enfoque son obligatorios.' ) );
        }

        try {
            $generator = new IAPOSTGROQ_Content_Generator();
            $data      = $generator->generate( $noticia, $enfoque, $extra, $content_type, $journalist_role );
            wp_send_json_success( $data );
        } catch ( RuntimeException $e ) {
            wp_send_json_error( array( 'message' => $e->getMessage() ) );
        }
    }

    public function ajax_publish(): void {
        $this->verify_nonce();
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( array( 'message' => 'Permisos insuficientes.' ), 403 );
        }

        // Parse categories (sent as array)
        $raw_cats   = $_POST['categories'] ?? array();
        $categories = is_array( $raw_cats ) ? array_map( 'intval', $raw_cats ) : array();

        $data = array(
            'title'               => sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) ),
            'content'             => wp_kses_post( wp_unslash( $_POST['content'] ?? '' ) ),
            'slug'                => sanitize_title( wp_unslash( $_POST['slug'] ?? '' ) ),
            'keyword'             => sanitize_text_field( wp_unslash( $_POST['keyword'] ?? '' ) ),
            'seo_title'           => sanitize_text_field( wp_unslash( $_POST['seo_title'] ?? '' ) ),
            'meta_description'    => sanitize_textarea_field( wp_unslash( $_POST['meta_description'] ?? '' ) ),
            'og_title'            => sanitize_text_field( wp_unslash( $_POST['og_title'] ?? '' ) ),
            'og_description'      => sanitize_textarea_field( wp_unslash( $_POST['og_description'] ?? '' ) ),
            'twitter_title'       => sanitize_text_field( wp_unslash( $_POST['twitter_title'] ?? '' ) ),
            'twitter_description' => sanitize_textarea_field( wp_unslash( $_POST['twitter_description'] ?? '' ) ),
            'categories'          => $categories,
            'tags'                => sanitize_text_field( wp_unslash( $_POST['tags'] ?? '' ) ),
        );

        try {
            $creator  = new IAPOSTGROQ_Post_Creator();
            $post_id  = $creator->create_draft( $data );
            $edit_url = get_edit_post_link( $post_id, 'raw' );
            wp_send_json_success( array( 'post_id' => $post_id, 'edit_url' => $edit_url ) );
        } catch ( RuntimeException $e ) {
            wp_send_json_error( array( 'message' => $e->getMessage() ) );
        }
    }

    // ─── AJAX: Tags search ───────────────────────────────────────────────────

    public function ajax_search_tags(): void {
        check_ajax_referer( 'iapostgroq_nonce', 'nonce' );

        $term = sanitize_text_field( wp_unslash( $_GET['term'] ?? '' ) );
        if ( strlen( $term ) < 1 ) {
            wp_send_json( array() );
        }

        $tags = get_tags( array(
            'search'     => $term,
            'number'     => 10,
            'hide_empty' => false,
            'orderby'    => 'count',
            'order'      => 'DESC',
        ) );

        $suggestions = is_array( $tags )
            ? array_map( fn( $tag ) => $tag->name, $tags )
            : array();

        wp_send_json( $suggestions );
    }

    // ─── AJAX: Settings ──────────────────────────────────────────────────────

    public function ajax_test_api(): void {
        $this->verify_nonce();
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Permisos insuficientes.' ), 403 );
        }

        $api    = new IAPOSTGROQ_Groq_API();
        $result = $api->test_connection();

        if ( $result['success'] ) {
            wp_send_json_success( array( 'message' => $result['message'] ) );
        } else {
            wp_send_json_error( array( 'message' => $result['message'] ) );
        }
    }

    public function ajax_save_settings(): void {
        $this->verify_nonce();
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Permisos insuficientes.' ), 403 );
        }

        $provider        = sanitize_key( $_POST['provider'] ?? '' );
        $api_key         = sanitize_text_field( wp_unslash( $_POST['api_key'] ?? '' ) );
        $openai_api_key  = sanitize_text_field( wp_unslash( $_POST['openai_api_key'] ?? '' ) );
        $model           = sanitize_text_field( wp_unslash( $_POST['model'] ?? '' ) );
        $content_type    = sanitize_key( $_POST['content_type'] ?? '' );
        $journalist_role = sanitize_key( $_POST['journalist_role'] ?? '' );

        if ( in_array( $provider, array( 'groq', 'openai' ), true ) ) {
            update_option( 'iapostgroq_provider', $provider );
        }
        update_option( 'iapostgroq_api_key', $api_key );
        update_option( 'iapostgroq_openai_api_key', $openai_api_key );
        if ( ! empty( $model ) ) {
            update_option( 'iapostgroq_model', $model );
        }
        if ( ! empty( $content_type ) && array_key_exists( $content_type, IAPOSTGROQ_Promptbooks::get_all_content_types() ) ) {
            update_option( 'iapostgroq_content_type', $content_type );
        }
        if ( ! empty( $journalist_role ) && array_key_exists( $journalist_role, IAPOSTGROQ_Promptbooks::get_all_journalist_roles() ) ) {
            update_option( 'iapostgroq_journalist_role', $journalist_role );
        }

        wp_send_json_success( array( 'message' => 'Configuración guardada correctamente.' ) );
    }

    // ─── AJAX: Custom promptbooks ─────────────────────────────────────────────

    public function ajax_save_custom_promptbook(): void {
        $this->verify_nonce();
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Permisos insuficientes.' ), 403 );
        }

        $pb_type = sanitize_key( $_POST['promptbook_type'] ?? '' ); // 'content_type' | 'journalist_role'
        $key     = sanitize_key( $_POST['key'] ?? '' );
        $label   = sanitize_text_field( wp_unslash( $_POST['label'] ?? '' ) );
        $prompt  = sanitize_textarea_field( wp_unslash( $_POST['prompt'] ?? '' ) );

        if ( ! in_array( $pb_type, array( 'content_type', 'journalist_role' ), true ) ) {
            wp_send_json_error( array( 'message' => 'Tipo de promptbook inválido.' ) );
        }
        if ( empty( $key ) || empty( $label ) || empty( $prompt ) ) {
            wp_send_json_error( array( 'message' => 'Nombre, clave e instrucciones son obligatorios.' ) );
        }

        // Prevent overwriting built-in keys
        $built_in_keys = $pb_type === 'content_type'
            ? array_keys( IAPOSTGROQ_Promptbooks::content_types() )
            : array_keys( IAPOSTGROQ_Promptbooks::journalist_roles() );

        if ( in_array( $key, $built_in_keys, true ) ) {
            wp_send_json_error( array( 'message' => 'Esa clave ya existe como elemento integrado. Usa un nombre diferente.' ) );
        }

        $option_name = $pb_type === 'content_type'
            ? 'iapostgroq_custom_content_types'
            : 'iapostgroq_custom_journalist_roles';

        $items          = (array) get_option( $option_name, array() );
        $items[ $key ]  = array( 'label' => $label, 'prompt' => $prompt );
        update_option( $option_name, $items );

        wp_send_json_success( array( 'message' => 'Guardado correctamente.', 'key' => $key ) );
    }

    public function ajax_delete_custom_promptbook(): void {
        $this->verify_nonce();
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Permisos insuficientes.' ), 403 );
        }

        $pb_type = sanitize_key( $_POST['promptbook_type'] ?? '' );
        $key     = sanitize_key( $_POST['key'] ?? '' );

        if ( ! in_array( $pb_type, array( 'content_type', 'journalist_role' ), true ) || empty( $key ) ) {
            wp_send_json_error( array( 'message' => 'Parámetros inválidos.' ) );
        }

        $option_name = $pb_type === 'content_type'
            ? 'iapostgroq_custom_content_types'
            : 'iapostgroq_custom_journalist_roles';

        $items = (array) get_option( $option_name, array() );
        unset( $items[ $key ] );
        update_option( $option_name, $items );

        wp_send_json_success( array( 'message' => 'Eliminado correctamente.' ) );
    }

    // ─── View: Generator ─────────────────────────────────────────────────────

    public function render_generator_page(): void {
        $content_types    = IAPOSTGROQ_Promptbooks::get_all_content_types();
        $journalist_roles = IAPOSTGROQ_Promptbooks::get_all_journalist_roles();
        $saved_type       = get_option( 'iapostgroq_content_type', 'post_blog' );
        $saved_role       = get_option( 'iapostgroq_journalist_role', 'generalista' );

        // WP categories for the publish section
        $wp_categories = get_categories( array( 'hide_empty' => false, 'orderby' => 'name', 'order' => 'ASC' ) );
        ?>
        <div class="wrap">
            <h1>IAPOST Groq — Generador de Contenido</h1>

            <div id="iapostgroq-error"   class="notice notice-error"   style="display:none;"></div>
            <div id="iapostgroq-success" class="notice notice-success" style="display:none;"></div>

            <!-- Section 1: Input -->
            <div class="iapostgroq-card">
                <h2>1. Datos de la noticia</h2>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="iapostgroq-noticia">Noticia base</label></th>
                        <td>
                            <textarea id="iapostgroq-noticia" rows="6" class="large-text"
                                placeholder="Pega aquí el texto de la noticia o sus puntos clave…"></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="iapostgroq-enfoque">Enfoque del artículo</label></th>
                        <td>
                            <textarea id="iapostgroq-enfoque" rows="3" class="large-text"
                                placeholder="¿Desde qué ángulo quieres abordar la noticia? Ej: impacto económico, consejos prácticos…"></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="iapostgroq-content-type">Tipo de contenido</label></th>
                        <td>
                            <select id="iapostgroq-content-type">
                                <?php foreach ( $content_types as $value => $label ) : ?>
                                    <option value="<?php echo esc_attr( $value ); ?>"
                                        data-desc="<?php echo esc_attr( IAPOSTGROQ_Promptbooks::get_content_type_prompt( $value ) ); ?>"
                                        <?php selected( $saved_type, $value ); ?>>
                                        <?php echo esc_html( $label ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p id="iapostgroq-content-type-desc" class="description iapostgroq-promptbook-desc"></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="iapostgroq-journalist-role">Rol del periodista</label></th>
                        <td>
                            <select id="iapostgroq-journalist-role">
                                <?php foreach ( $journalist_roles as $value => $label ) : ?>
                                    <option value="<?php echo esc_attr( $value ); ?>"
                                        data-desc="<?php echo esc_attr( IAPOSTGROQ_Promptbooks::get_journalist_role_prompt( $value ) ); ?>"
                                        <?php selected( $saved_role, $value ); ?>>
                                        <?php echo esc_html( $label ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p id="iapostgroq-journalist-role-desc" class="description iapostgroq-promptbook-desc"></p>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <button id="iapostgroq-btn-generate" class="button button-primary button-large">Generar Contenido</button>
                    <span id="iapostgroq-spinner" class="spinner" style="float:none;margin-top:0;visibility:hidden;"></span>
                </p>
            </div>

            <!-- Section 2: Results -->
            <div id="iapostgroq-results" class="iapostgroq-card" style="display:none;margin-top:20px;">
                <h2>2. Contenido generado</h2>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="iapostgroq-keyword">Keyword SEO</label></th>
                        <td>
                            <input type="text" id="iapostgroq-keyword" class="regular-text" />
                            <p class="description">Ancla SEO — debe aparecer en título, slug, meta descripción, redes y artículo (4-6 veces).</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="iapostgroq-title">Título del post</label></th>
                        <td><input type="text" id="iapostgroq-title" class="large-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="iapostgroq-slug">Slug</label></th>
                        <td><input type="text" id="iapostgroq-slug" class="large-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="iapostgroq-seo-title">
                                SEO Title <span id="iapostgroq-seo-title-count" class="iapostgroq-char-count">(0/60)</span>
                            </label>
                        </th>
                        <td><input type="text" id="iapostgroq-seo-title" class="large-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="iapostgroq-metadesc">
                                Meta Descripción <span id="iapostgroq-metadesc-count" class="iapostgroq-char-count">(0/140)</span>
                            </label>
                        </th>
                        <td>
                            <textarea id="iapostgroq-metadesc" rows="3" class="large-text"></textarea>
                            <div class="iapostgroq-bar-wrap">
                                <div id="iapostgroq-metadesc-bar" class="iapostgroq-bar"></div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="iapostgroq-social">Post para redes sociales</label></th>
                        <td><textarea id="iapostgroq-social" rows="3" class="large-text"></textarea></td>
                    </tr>

                    <!-- Yoast Social Meta -->
                    <tr><td colspan="2"><hr style="margin:8px 0 4px;"><strong style="font-size:13px;color:#1d2327;">Yoast SEO — Social</strong> <span style="color:#50575e;font-size:12px;">(se guarda automáticamente en Yoast al publicar)</span></td></tr>
                    <tr>
                        <th scope="row">
                            <label for="iapostgroq-og-title">
                                Facebook Title <span id="iapostgroq-og-title-count" class="iapostgroq-char-count">(0/60)</span>
                            </label>
                        </th>
                        <td><input type="text" id="iapostgroq-og-title" class="large-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="iapostgroq-og-description">
                                Facebook Description <span id="iapostgroq-og-desc-count" class="iapostgroq-char-count">(0/160)</span>
                            </label>
                        </th>
                        <td><textarea id="iapostgroq-og-description" rows="2" class="large-text"></textarea></td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="iapostgroq-twitter-title">
                                Twitter Title <span id="iapostgroq-twitter-title-count" class="iapostgroq-char-count">(0/55)</span>
                            </label>
                        </th>
                        <td><input type="text" id="iapostgroq-twitter-title" class="large-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="iapostgroq-twitter-description">
                                Twitter Description <span id="iapostgroq-twitter-desc-count" class="iapostgroq-char-count">(0/140)</span>
                            </label>
                        </th>
                        <td><textarea id="iapostgroq-twitter-description" rows="2" class="large-text"></textarea></td>
                    </tr>

                    <tr><td colspan="2"><hr style="margin:8px 0 4px;"></td></tr>
                    <tr>
                        <th scope="row"><label>Contenido del artículo</label></th>
                        <td>
                            <div class="iapostgroq-editor-wrap">
                                <div class="iapostgroq-editor-tabs">
                                    <button type="button" class="iapostgroq-tab-btn active" data-panel="code">
                                        Código Gutenberg
                                    </button>
                                    <button type="button" class="iapostgroq-tab-btn" data-panel="preview">
                                        Vista previa
                                    </button>
                                </div>
                                <div class="iapostgroq-panel-code">
                                    <textarea id="iapostgroq-content" rows="22" class="large-text" style="font-family:monospace;font-size:12px;"></textarea>
                                </div>
                                <div class="iapostgroq-panel-preview" style="display:none;">
                                    <div id="iapostgroq-content-preview" class="iapostgroq-preview-render"></div>
                                </div>
                            </div>
                        </td>
                    </tr>

                    <!-- Categories & Tags -->
                    <tr>
                        <th scope="row"><label for="iapostgroq-categories">Categorías</label></th>
                        <td>
                            <select id="iapostgroq-categories" multiple size="6" style="min-width:260px;">
                                <?php foreach ( $wp_categories as $cat ) : ?>
                                    <option value="<?php echo esc_attr( $cat->term_id ); ?>">
                                        <?php echo esc_html( $cat->name ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">Ctrl/Cmd + clic para seleccionar varias.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="iapostgroq-tags">Etiquetas</label></th>
                        <td>
                            <input type="text" id="iapostgroq-tags" class="large-text"
                                placeholder="tecnología, inteligencia artificial, seguridad (separadas por comas)" />
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <button id="iapostgroq-btn-rewrite" class="button button-secondary button-large">Re-redactar</button>
                    &nbsp;
                    <button id="iapostgroq-btn-publish" class="button button-primary button-large">Enviar como Entrada</button>
                    <span id="iapostgroq-spinner-publish" class="spinner" style="float:none;margin-top:0;visibility:hidden;"></span>
                </p>
            </div>
        </div>

        <!-- Success popup -->
        <div id="iapostgroq-success-overlay" class="iapostgroq-success-overlay" style="display:none;">
            <div class="iapostgroq-success-box">
                <div class="iapostgroq-success-icon">&#10003;</div>
                <h2>¡Artículo creado exitosamente!</h2>
                <p id="iapostgroq-redirect-msg">Redirigiendo al editor en <strong><span id="iapostgroq-redirect-count">3</span>s</strong>…</p>
                <a id="iapostgroq-edit-now-link" href="#" class="button button-primary button-hero">
                    Editar entrada ahora
                </a>
                <br>
                <button id="iapostgroq-stay-here" class="button button-secondary" style="margin-top:12px;">
                    Quedarse aquí
                </button>
            </div>
        </div>

        <!-- Rewrite Modal -->
        <div id="iapostgroq-modal-overlay" class="iapostgroq-modal-overlay" style="display:none;">
            <div class="iapostgroq-modal-content">
                <h2>Re-redactar con instrucciones adicionales</h2>
                <p>Indica qué quieres cambiar en el contenido generado:</p>
                <textarea id="iapostgroq-modal-instructions" rows="5" style="width:100%;"
                    placeholder="Ej: Añade más datos estadísticos, usa un tono más informal, amplía la conclusión…"></textarea>
                <p style="text-align:right;margin-top:12px;">
                    <button id="iapostgroq-modal-cancel" class="button button-secondary">Cancelar</button>
                    &nbsp;
                    <button id="iapostgroq-modal-apply" class="button button-primary">Aplicar</button>
                    <span id="iapostgroq-spinner-rewrite" class="spinner" style="float:none;margin-top:0;visibility:hidden;"></span>
                </p>
            </div>
        </div>
        <?php
    }

    // ─── View: Settings ───────────────────────────────────────────────────────

    public function render_settings_page(): void {
        $current_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'api';
        $tabs        = array(
            'api'   => 'API y Modelo',
            'tipos' => 'Tipos de Contenido',
            'roles' => 'Roles de Periodista',
        );
        ?>
        <div class="wrap">
            <h1>IAPOST Groq — Configuración</h1>

            <nav class="nav-tab-wrapper" style="margin-bottom:0;">
                <?php foreach ( $tabs as $tab => $label ) : ?>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=iapostgroq-settings&tab=' . $tab ) ); ?>"
                       class="nav-tab <?php echo $current_tab === $tab ? 'nav-tab-active' : ''; ?>">
                        <?php echo esc_html( $label ); ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div id="iapostgroq-settings-msg" class="notice" style="display:none;margin-top:16px;"></div>

            <?php
            if ( $current_tab === 'api' ) {
                $this->render_tab_api();
            } elseif ( $current_tab === 'tipos' ) {
                $this->render_tab_promptbooks( 'content_type' );
            } elseif ( $current_tab === 'roles' ) {
                $this->render_tab_promptbooks( 'journalist_role' );
            }
            ?>
        </div>
        <?php
    }

    // ─── View: Tab API ────────────────────────────────────────────────────────

    private function render_tab_api(): void {
        $provider         = get_option( 'iapostgroq_provider', 'groq' );
        $api_key          = get_option( 'iapostgroq_api_key', '' );
        $openai_api_key   = get_option( 'iapostgroq_openai_api_key', '' );
        $model            = get_option( 'iapostgroq_model', 'llama-3.3-70b-versatile' );
        $saved_type       = get_option( 'iapostgroq_content_type', 'post_blog' );
        $saved_role       = get_option( 'iapostgroq_journalist_role', 'generalista' );
        $all_models       = IAPOSTGROQ_Groq_API::available_models();
        $active_models    = $all_models[ $provider ] ?? $all_models['groq'];
        $content_types    = IAPOSTGROQ_Promptbooks::get_all_content_types();
        $journalist_roles = IAPOSTGROQ_Promptbooks::get_all_journalist_roles();
        ?>
        <div class="iapostgroq-card">
            <h2>Proveedor de IA</h2>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">Proveedor</th>
                    <td>
                        <label style="margin-right:24px;">
                            <input type="radio" name="iapostgroq-provider" value="groq"
                                <?php checked( $provider, 'groq' ); ?> />
                            <strong>Groq</strong>
                            <span class="description" style="margin-left:4px;">— LLaMA, Mixtral, Gemma (gratuito)</span>
                        </label>
                        <label>
                            <input type="radio" name="iapostgroq-provider" value="openai"
                                <?php checked( $provider, 'openai' ); ?> />
                            <strong>OpenAI</strong>
                            <span class="description" style="margin-left:4px;">— GPT-4o, GPT-4 (requiere cuenta de pago)</span>
                        </label>
                    </td>
                </tr>
                <tr id="iapostgroq-groq-key-row">
                    <th scope="row"><label for="iapostgroq-api-key">Groq API Key</label></th>
                    <td>
                        <input type="password" id="iapostgroq-api-key" class="regular-text"
                            value="<?php echo esc_attr( $api_key ); ?>" autocomplete="off" />
                        <p class="description">
                            Obtén tu clave gratis en <a href="https://console.groq.com/keys" target="_blank" rel="noopener">console.groq.com/keys</a>
                        </p>
                    </td>
                </tr>
                <tr id="iapostgroq-openai-key-row">
                    <th scope="row"><label for="iapostgroq-openai-api-key">OpenAI API Key</label></th>
                    <td>
                        <input type="password" id="iapostgroq-openai-api-key" class="regular-text"
                            value="<?php echo esc_attr( $openai_api_key ); ?>" autocomplete="off" />
                        <p class="description">
                            Obtén tu clave en <a href="https://platform.openai.com/api-keys" target="_blank" rel="noopener">platform.openai.com/api-keys</a>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="iapostgroq-model-select">Modelo</label></th>
                    <td>
                        <select id="iapostgroq-model-select">
                            <?php foreach ( $active_models as $value => $label ) : ?>
                                <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $model, $value ); ?>>
                                    <?php echo esc_html( $label ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            </table>
            <p class="submit">
                <button id="iapostgroq-btn-save-settings" class="button button-primary button-large">Guardar configuración</button>
                &nbsp;
                <button id="iapostgroq-btn-test-api" class="button button-secondary button-large">Verificar conexión</button>
                <span id="iapostgroq-spinner-settings" class="spinner" style="float:none;margin-top:0;visibility:hidden;"></span>
            </p>
        </div>

        <div class="iapostgroq-card" style="margin-top:20px;">
            <h2>Valores por defecto del Generador</h2>
            <p class="description" style="margin-bottom:16px;">
                Se preseleccionan al abrir el Generador. Puedes cambiarlos allí por generación sin afectar estos defaults.
            </p>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="iapostgroq-default-content-type">Tipo de contenido</label></th>
                    <td>
                        <select id="iapostgroq-default-content-type">
                            <?php foreach ( $content_types as $value => $label ) : ?>
                                <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $saved_type, $value ); ?>>
                                    <?php echo esc_html( $label ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="iapostgroq-default-journalist-role">Rol del periodista</label></th>
                    <td>
                        <select id="iapostgroq-default-journalist-role">
                            <?php foreach ( $journalist_roles as $value => $label ) : ?>
                                <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $saved_role, $value ); ?>>
                                    <?php echo esc_html( $label ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            </table>
            <p class="submit">
                <button id="iapostgroq-btn-save-defaults" class="button button-primary">Guardar defaults</button>
                <span id="iapostgroq-spinner-defaults" class="spinner" style="float:none;margin-top:0;visibility:hidden;"></span>
            </p>
        </div>
        <?php
    }

    // ─── View: Tab custom promptbooks ─────────────────────────────────────────

    private function render_tab_promptbooks( string $pb_type ): void {
        $is_type     = ( $pb_type === 'content_type' );
        $built_in    = $is_type ? IAPOSTGROQ_Promptbooks::content_types() : IAPOSTGROQ_Promptbooks::journalist_roles();
        $option_name = $is_type ? 'iapostgroq_custom_content_types' : 'iapostgroq_custom_journalist_roles';
        $custom      = (array) get_option( $option_name, array() );
        $singular    = $is_type ? 'tipo de contenido' : 'rol de periodista';
        $title_built = $is_type ? 'Tipos integrados' : 'Roles integrados';
        $title_cust  = $is_type ? 'Tipos personalizados' : 'Roles personalizados';
        $title_add   = $is_type ? 'Añadir tipo de contenido' : 'Añadir rol de periodista';
        ?>
        <!-- Built-in (read-only) -->
        <div class="iapostgroq-card">
            <h2><?php echo esc_html( $title_built ); ?></h2>
            <table class="wp-list-table widefat fixed striped iapostgroq-pb-table">
                <thead>
                    <tr>
                        <th style="width:220px;">Nombre</th>
                        <th>Vista previa de instrucciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $built_in as $key => $label ) :
                        $preview = $is_type
                            ? IAPOSTGROQ_Promptbooks::get_content_type_prompt( $key )
                            : IAPOSTGROQ_Promptbooks::get_journalist_role_prompt( $key );
                        // Strip the first line header (TIPO DE CONTENIDO:... / ROL:...)
                        $lines   = explode( "\n", $preview, 2 );
                        $preview_clean = isset( $lines[1] ) ? trim( $lines[1] ) : $preview;
                    ?>
                        <tr>
                            <td><strong><?php echo esc_html( $label ); ?></strong><br><code><?php echo esc_html( $key ); ?></code></td>
                            <td class="iapostgroq-pb-preview"><?php echo esc_html( substr( $preview_clean, 0, 160 ) ); ?>…</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Custom -->
        <div class="iapostgroq-card" style="margin-top:20px;">
            <h2><?php echo esc_html( $title_cust ); ?></h2>

            <?php if ( empty( $custom ) ) : ?>
                <p>No hay <?php echo esc_html( $singular ); ?>s personalizados todavía.</p>
            <?php else : ?>
                <table class="wp-list-table widefat fixed striped iapostgroq-pb-table" id="iapostgroq-custom-table-<?php echo esc_attr( $pb_type ); ?>">
                    <thead>
                        <tr>
                            <th style="width:220px;">Nombre</th>
                            <th>Vista previa de instrucciones</th>
                            <th style="width:160px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $custom as $key => $data ) : ?>
                            <tr id="iapostgroq-row-<?php echo esc_attr( $pb_type . '-' . $key ); ?>">
                                <td><strong><?php echo esc_html( $data['label'] ?? '' ); ?></strong><br><code><?php echo esc_html( $key ); ?></code></td>
                                <td class="iapostgroq-pb-preview"><?php echo esc_html( substr( $data['prompt'] ?? '', 0, 160 ) ); ?>…</td>
                                <td>
                                    <button class="button iapostgroq-edit-custom"
                                        data-pb-type="<?php echo esc_attr( $pb_type ); ?>"
                                        data-key="<?php echo esc_attr( $key ); ?>"
                                        data-label="<?php echo esc_attr( $data['label'] ?? '' ); ?>"
                                        data-prompt="<?php echo esc_attr( $data['prompt'] ?? '' ); ?>">
                                        Editar
                                    </button>
                                    <button class="button iapostgroq-delete-custom" style="margin-top:4px;"
                                        data-pb-type="<?php echo esc_attr( $pb_type ); ?>"
                                        data-key="<?php echo esc_attr( $key ); ?>">
                                        Eliminar
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <!-- Add / Edit form -->
            <div class="iapostgroq-pb-form" style="margin-top:28px;padding-top:20px;border-top:1px solid #f0f0f1;">
                <h3 id="iapostgroq-form-title-<?php echo esc_attr( $pb_type ); ?>">
                    <?php echo esc_html( $title_add ); ?>
                </h3>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label>Nombre</label></th>
                        <td>
                            <input type="text" id="iapostgroq-new-label-<?php echo esc_attr( $pb_type ); ?>"
                                class="regular-text iapostgroq-new-label"
                                data-pb-type="<?php echo esc_attr( $pb_type ); ?>"
                                placeholder="Ej: Newsletter, Community Manager…" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label>Clave (slug)</label></th>
                        <td>
                            <input type="text" id="iapostgroq-new-key-<?php echo esc_attr( $pb_type ); ?>"
                                class="regular-text" readonly
                                style="background:#f6f7f7;color:#666;" />
                            <p class="description">Se genera automáticamente. Solo letras, números y guiones bajos.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label>Instrucciones (prompt)</label></th>
                        <td>
                            <textarea id="iapostgroq-new-prompt-<?php echo esc_attr( $pb_type ); ?>"
                                rows="7" class="large-text"
                                placeholder="Describe el estilo, tono, estructura y reglas para este <?php echo esc_attr( $singular ); ?>. La IA aplicará estas instrucciones en cada generación."></textarea>
                        </td>
                    </tr>
                </table>
                <p>
                    <button class="button button-primary iapostgroq-save-custom"
                        data-pb-type="<?php echo esc_attr( $pb_type ); ?>">
                        Guardar
                    </button>
                    <button class="button iapostgroq-cancel-edit" style="margin-left:8px;display:none;"
                        data-pb-type="<?php echo esc_attr( $pb_type ); ?>">
                        Cancelar edición
                    </button>
                    <span class="spinner iapostgroq-spinner-pb-<?php echo esc_attr( $pb_type ); ?>"
                        style="float:none;margin-top:0;visibility:hidden;"></span>
                </p>
            </div>
        </div>
        <?php
    }
}

<?php
/**
 * Post Creator
 *
 * Creates WordPress draft posts with Yoast SEO metadata.
 *
 * @package IAPOST_Groq
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IAPOSTGROQ_Post_Creator {

    /**
     * Create a draft post from generated content.
     *
     * @param array $data Keys: title, content, slug, keyword, seo_title, meta_description
     * @return int Post ID
     * @throws RuntimeException
     */
    public function create_draft( array $data ): int {
        $slug = sanitize_title( $data['slug'] ?? '' );

        $post_id = wp_insert_post(
            array(
                'post_title'   => wp_strip_all_tags( $data['title'] ?? '' ),
                'post_content' => wp_kses_post( $data['content'] ?? '' ),
                'post_status'  => 'draft',
                'post_type'    => 'post',
                'post_name'    => $slug,
            ),
            true
        );

        if ( is_wp_error( $post_id ) ) {
            throw new RuntimeException( 'Error al crear la entrada: ' . $post_id->get_error_message() );
        }

        // Categories
        if ( ! empty( $data['categories'] ) && is_array( $data['categories'] ) ) {
            wp_set_post_categories( $post_id, array_map( 'intval', $data['categories'] ) );
        }

        // Tags
        if ( ! empty( $data['tags'] ) ) {
            wp_set_post_tags( $post_id, sanitize_text_field( $data['tags'] ) );
        }

        // Yoast SEO — basic
        update_post_meta( $post_id, '_yoast_wpseo_focuskw',  sanitize_text_field( $data['keyword'] ?? '' ) );
        update_post_meta( $post_id, '_yoast_wpseo_title',    sanitize_text_field( $data['seo_title'] ?? '' ) );
        update_post_meta( $post_id, '_yoast_wpseo_metadesc', sanitize_textarea_field( $data['meta_description'] ?? '' ) );

        // Yoast SEO — Open Graph (Facebook)
        update_post_meta( $post_id, '_yoast_wpseo_opengraph-title',       sanitize_text_field( $data['og_title'] ?? '' ) );
        update_post_meta( $post_id, '_yoast_wpseo_opengraph-description', sanitize_textarea_field( $data['og_description'] ?? '' ) );

        // Yoast SEO — Twitter
        update_post_meta( $post_id, '_yoast_wpseo_twitter-title',       sanitize_text_field( $data['twitter_title'] ?? '' ) );
        update_post_meta( $post_id, '_yoast_wpseo_twitter-description', sanitize_textarea_field( $data['twitter_description'] ?? '' ) );

        return $post_id;
    }
}

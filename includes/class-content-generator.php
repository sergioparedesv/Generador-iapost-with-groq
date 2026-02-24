<?php
/**
 * Content Generator
 *
 * Orchestrates the two Groq API calls to produce SEO metadata + Gutenberg article.
 *
 * @package IAPOST_Groq
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IAPOSTGROQ_Content_Generator {

    private IAPOSTGROQ_Groq_API $api;

    public function __construct() {
        $this->api = new IAPOSTGROQ_Groq_API();
    }

    /**
     * Generate SEO metadata and article content.
     *
     * @param string $noticia
     * @param string $enfoque
     * @param string $extra_instructions
     * @param string $content_type   Key from IAPOSTGROQ_Promptbooks::content_types()
     * @param string $journalist_role Key from IAPOSTGROQ_Promptbooks::journalist_roles()
     * @return array{keyword, title, seo_title, meta_description, slug, social_post, content}
     * @throws RuntimeException
     */
    public function generate( string $noticia, string $enfoque, string $extra_instructions = '', string $content_type = '', string $journalist_role = '' ): array {

        // Resolve defaults
        if ( empty( $content_type ) ) {
            $content_type = (string) get_option( 'iapostgroq_content_type', 'post_blog' );
        }
        if ( empty( $journalist_role ) ) {
            $journalist_role = (string) get_option( 'iapostgroq_journalist_role', 'generalista' );
        }

        // ── Call 1: SEO JSON ──────────────────────────────────────────────────
        $seo_user_prompt = sprintf(
            "Noticia base:\n%s\n\nEnfoque del artículo:\n%s\n\n" .
            "PROCESO OBLIGATORIO — sigue este orden:\n" .
            "1. Determina la KEYWORD principal (1-3 palabras). Llámala KW a partir de ahora.\n" .
            "2. TÍTULO: incluye KW de forma natural (50-65 chars).\n" .
            "3. SEO TITLE: incluye KW (máx 60 chars).\n" .
            "4. SLUG desde KW (minúsculas, guiones, sin stopwords).\n" .
            "5. META DESCRIPCIÓN (110-138 chars, NUNCA superar 140):\n" .
            "   — Escribe la KW TEXTUALMENTE dentro del texto (no al final, no como hashtag, no como sinónimo).\n" .
            "   — Plantilla de referencia: \"[KW] [beneficio o contexto breve]. [CTA activo].\"\n" .
            "   — Ejemplo: si KW = 'energía solar': 'La energía solar reduce tu factura hasta un 70%%. Descubre cómo instalarlo en tu hogar.'\n" .
            "   — Verifica: copia la KW del paso 1 y busca esa cadena exacta en lo que acabas de escribir. ¿Está? Si NO → reescribe.\n" .
            "6. SOCIAL POST con KW + emojis + hashtags (150-200 chars).\n" .
            "7. OG TITLE para Facebook: atractivo, con KW (55-60 chars).\n" .
            "8. OG DESCRIPTION para Facebook: conversacional, con KW + CTA (110-140 chars).\n" .
            "9. TWITTER TITLE: directo, con KW (50-55 chars).\n" .
            "10. TWITTER DESCRIPTION: dinámico, con KW (100-140 chars).\n\n" .
            "ANTES DE EMITIR EL JSON — revisa campo a campo:\n" .
            "  · meta_description: ¿contiene la KW exacta del paso 1? Si NO → reescribe con la KW dentro.\n" .
            "  · social_post, og_description, twitter_description: ¿contienen la KW? Si NO → reescribe.\n\n" .
            "Responde ÚNICAMENTE con JSON válido (sin markdown, sin texto adicional):\n" .
            '{"keyword":"...","title":"...","seo_title":"...","meta_description":"...","slug":"...","social_post":"...","og_title":"...","og_description":"...","twitter_title":"...","twitter_description":"..."}',
            $noticia,
            $enfoque
        );

        if ( ! empty( $extra_instructions ) ) {
            $seo_user_prompt .= "\n\nInstrucciones adicionales: " . $extra_instructions;
        }

        $seo_result = $this->api->chat(
            array(
                array(
                    'role'    => 'system',
                    'content' => IAPOSTGROQ_Promptbooks::build_seo_system_prompt( $content_type, $journalist_role ),
                ),
                array(
                    'role'    => 'user',
                    'content' => $seo_user_prompt,
                ),
            ),
            900,
            0.3
        );

        $seo_data = $this->parse_seo_json( $seo_result['text'] );

        // ── Call 2: Gutenberg Article ─────────────────────────────────────────
        $keyword = $seo_data['keyword'];
        $article_user_prompt = sprintf(
            "Escribe un artículo SEO de 1200-1500 palabras sobre:\n%s\n\n" .
            "Enfoque: %s\n" .
            "Keyword: \"%s\"\n" .
            "Título: \"%s\"\n\n" .

            "═══ REGLA 1 — DENSIDAD DE KEYWORD (CUENTA OBLIGATORIA) ═══\n" .
            "La keyword \"%s\" debe aparecer entre 2 y 4 veces en TODO el texto — incluyendo headings. MÁXIMO ABSOLUTO: 4.\n" .
            "Para el resto de referencias usa sinónimos, pronombres o variantes ('el tema', 'este avance', 'la tecnología', etc.).\n" .
            "ANTES de terminar cuenta cuántas veces aparece la keyword:\n" .
            "  · Si hay 5 o más → sustituye los excesos por sinónimos AHORA mismo, antes de responder.\n" .
            "  · Si hay 1 o menos → añade 1 uso natural en un párrafo relevante.\n\n" .

            "═══ REGLA 2 — FRASES CORTAS (OBLIGATORIO) ═══\n" .
            "Cada frase DEBE tener menos de 20 palabras. Si una frase supera 20 palabras, divídela con un punto.\n" .
            "INCORRECTO: \"La inteligencia artificial está transformando la industria tecnológica de manera significativa, afectando a empresas grandes y pequeñas en todos los sectores.\"\n" .
            "CORRECTO: \"La inteligencia artificial transforma la industria tecnológica. Afecta tanto a grandes empresas como a pymes.\"\n\n" .

            "═══ REGLA 3 — PALABRAS DE TRANSICIÓN (OBLIGATORIO) ═══\n" .
            "Al menos el 30%% de los párrafos deben COMENZAR con un conector de esta lista:\n" .
            "Además | Sin embargo | Por otro lado | En consecuencia | No obstante | De hecho |\n" .
            "Por ejemplo | En primer lugar | A continuación | Por último | Asimismo |\n" .
            "De esta manera | Por tanto | En cambio | Es decir | En definitiva | Gracias a esto | Como resultado\n\n" .

            "═══ REGLA 4 — VARIEDAD DE INICIO ═══\n" .
            "NUNCA tres frases consecutivas empiezan con la misma palabra. Varía el sujeto o el conector.\n\n" .

            "═══ REGLA 5 — ENLACES ═══\n" .
            "Incluye OBLIGATORIAMENTE estos 2 elementos en el artículo:\n\n" .
            "A) ENLACE EXTERNO (en un párrafo relevante, fuente autorizada del tema):\n" .
            "<a href=\"https://es.wikipedia.org/wiki/[tema-relacionado]\" target=\"_blank\" rel=\"noopener noreferrer\">texto descriptivo del destino</a>\n\n" .
            "B) ENLACE INTERNO (en un párrafo, relacionado con otro artículo del sitio):\n" .
            "<a href=\"/[slug-articulo-relacionado]\">texto descriptivo relacionado</a>\n\n" .
            "CRÍTICO — ANCHOR TEXT: El texto del enlace (lo que va entre <a> y </a>) NO puede ser la keyword exacta ni contenerla.\n" .
            "Usa textos como: 'más información', 'ver fuente oficial', 'según los expertos', 'guía completa', 'datos del estudio', etc.\n\n" .

            "═══ ESTRUCTURA DEL ARTÍCULO ═══\n" .
            "- Primer H2: DEBE contener la keyword pero ser DISTINTO al título \"%s\". Redáctalo como una pregunta, afirmación o enfoque diferente al título. El párrafo de introducción que le sigue debe incluir la keyword en las primeras 100 palabras.\n" .
            "- 3 o más H2 adicionales\n" .
            "- H3 para subtemas donde sea apropiado\n" .
            "- Párrafos cortos (2-3 líneas)\n" .
            "- Conclusión con CTA\n\n" .

            "FORMATO GUTENBERG ESTRICTO — sin texto fuera de bloques:\n" .
            "<!-- wp:paragraph --><p>...</p><!-- /wp:paragraph -->\n" .
            "<!-- wp:heading {\"level\":2} --><h2>...</h2><!-- /wp:heading -->\n" .
            "<!-- wp:heading {\"level\":3} --><h3>...</h3><!-- /wp:heading -->",
            $noticia,
            $enfoque,
            $keyword,
            $seo_data['title'],
            $keyword,            // for density rule
            $seo_data['title']   // for H2 ≠ title rule
        );

        if ( ! empty( $extra_instructions ) ) {
            $article_user_prompt .= "\n\nInstrucciones adicionales: " . $extra_instructions;
        }

        $article_result = $this->api->chat(
            array(
                array(
                    'role'    => 'system',
                    'content' => IAPOSTGROQ_Promptbooks::build_article_system_prompt( $content_type, $journalist_role, $seo_data['keyword'] ),
                ),
                array(
                    'role'    => 'user',
                    'content' => $article_user_prompt,
                ),
            ),
            4000,
            0.8
        );

        return array_merge( $seo_data, array( 'content' => $article_result['text'] ) );
    }

    /**
     * Parse and validate the SEO JSON string returned by the API.
     *
     * @param string $raw
     * @return array
     */
    public function parse_seo_json( string $raw ): array {
        // Strip markdown code fences (```json ... ```)
        $cleaned = preg_replace( '/^```(?:json)?\s*/i', '', trim( $raw ) );
        $cleaned = preg_replace( '/\s*```$/', '', $cleaned );
        $cleaned = trim( $cleaned );

        $data = json_decode( $cleaned, true );

        // Fallback: extract first {...} block if direct decode fails
        if ( ! is_array( $data ) ) {
            if ( preg_match( '/\{[^{}]*(?:\{[^{}]*\}[^{}]*)?\}/s', $cleaned, $match ) ) {
                $data = json_decode( $match[0], true );
            }
        }

        if ( ! is_array( $data ) ) {
            $data = array();
        }

        $required_keys = array( 'keyword', 'title', 'seo_title', 'meta_description', 'slug', 'social_post', 'og_title', 'og_description', 'twitter_title', 'twitter_description' );
        foreach ( $required_keys as $key ) {
            if ( ! isset( $data[ $key ] ) ) {
                $data[ $key ] = '';
            }
        }

        // Ensure slug is always valid
        if ( empty( $data['slug'] ) && ! empty( $data['title'] ) ) {
            $data['slug'] = sanitize_title( $data['title'] );
        }

        return $data;
    }
}

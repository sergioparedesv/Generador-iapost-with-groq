<?php
/**
 * Promptbooks
 *
 * Biblioteca de instrucciones de escritura por tipo de contenido y rol periodístico.
 *
 * @package IAPOST_Groq
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IAPOSTGROQ_Promptbooks {

    // ─── Built-in lists ──────────────────────────────────────────────────────

    public static function content_types(): array {
        return array(
            'noticia'          => 'Noticia periodística',
            'articulo_opinion' => 'Artículo de opinión',
            'reportaje'        => 'Reportaje en profundidad',
            'post_blog'        => 'Post de blog',
            'tutorial'         => 'Tutorial / Guía paso a paso',
            'entrevista'       => 'Entrevista / Q&A',
            'resena'           => 'Reseña / Review',
            'cuento'           => 'Cuento / Historia',
            'receta'           => 'Receta',
        );
    }

    public static function journalist_roles(): array {
        return array(
            'generalista'    => 'Periodista generalista',
            'tecnologia'     => 'Experto en Tecnología e IA',
            'politica'       => 'Experto en Política',
            'economia'       => 'Experto en Economía y Finanzas',
            'ciencia'        => 'Experto en Ciencia y Salud',
            'cultura'        => 'Experto en Cultura y Entretenimiento',
            'deportes'       => 'Experto en Deportes',
            'medio_ambiente' => 'Experto en Medio Ambiente',
            'internacional'  => 'Corresponsal Internacional',
        );
    }

    // ─── Merged lists (built-in + custom) ────────────────────────────────────

    public static function get_all_content_types(): array {
        $all    = self::content_types();
        $custom = get_option( 'iapostgroq_custom_content_types', array() );
        foreach ( (array) $custom as $key => $data ) {
            $all[ $key ] = $data['label'] ?? $key;
        }
        return $all;
    }

    public static function get_all_journalist_roles(): array {
        $all    = self::journalist_roles();
        $custom = get_option( 'iapostgroq_custom_journalist_roles', array() );
        foreach ( (array) $custom as $key => $data ) {
            $all[ $key ] = $data['label'] ?? $key;
        }
        return $all;
    }

    // ─── Content type prompt ─────────────────────────────────────────────────

    public static function get_content_type_prompt( string $type ): string {
        $prompts = array(

            'noticia' =>
                "TIPO DE CONTENIDO: Noticia periodística.\n" .
                "Sigue la pirámide invertida: el primer párrafo responde quién, qué, cuándo, dónde y por qué. " .
                "Desarrolla los detalles en los siguientes párrafos y deja el contexto general al final. " .
                "Usa verbos en tiempo pasado para hechos ocurridos y presente para situaciones vigentes. " .
                "Tono objetivo, factual y conciso. Evita adjetivos valorativos.",

            'articulo_opinion' =>
                "TIPO DE CONTENIDO: Artículo de opinión.\n" .
                "Comienza con una tesis clara y provocadora. Desarrolla argumentos apoyados en hechos. " .
                "Anticipa el contraargumento principal y rebátelo con solidez. " .
                "Concluye con una postura definida y una llamada a la reflexión. " .
                "Usa la primera persona con moderación. Tono analítico, crítico y persuasivo.",

            'reportaje' =>
                "TIPO DE CONTENIDO: Reportaje en profundidad.\n" .
                "Desarrolla el tema con contexto histórico, múltiples perspectivas y datos verificables. " .
                "Abre con un anclaje narrativo potente (escena, anécdota o dato sorprendente). " .
                "Estructura el cuerpo de forma cronológica o temática según convenga. " .
                "Cierra con proyección futura o reflexión de fondo. Tono riguroso y narrativo.",

            'post_blog' =>
                "TIPO DE CONTENIDO: Post de blog.\n" .
                "Tono conversacional, cercano y dinámico. Usa párrafos cortos (2-3 líneas). " .
                "Abre con un hook que genere curiosidad o identifique un problema del lector. " .
                "Incluye listas, preguntas retóricas y ejemplos cotidianos para mantener el ritmo. " .
                "Finaliza con un CTA claro y directo. Estilo amigable, práctico y fácil de escanear.",

            'tutorial' =>
                "TIPO DE CONTENIDO: Tutorial / Guía paso a paso.\n" .
                "Comienza con los requisitos previos y el objetivo final que logrará el lector. " .
                "Numera cada paso con una acción específica y el resultado esperado. " .
                "Incluye consejos de eficiencia, advertencias de errores comunes y resolución de problemas. " .
                "Tono instructivo, preciso y alentador. Usa imperativos en los pasos.",

            'entrevista' =>
                "TIPO DE CONTENIDO: Entrevista / Q&A.\n" .
                "Introduce al entrevistado con contexto relevante en 2-3 frases. " .
                "Alterna entre preguntas incisivas (en negrita) y respuestas naturales y fluidas. " .
                "Las preguntas deben revelar perspectivas únicas, no solo confirmar lo conocido. " .
                "Cierra con una pregunta memorable. Si no es una entrevista real, adapta el Q&A al tema.",

            'resena' =>
                "TIPO DE CONTENIDO: Reseña / Review.\n" .
                "Evalúa con criterios claros: contexto y descripción, puntos fuertes, puntos débiles, " .
                "comparativa con similares y veredicto final con calificación (ej. 8/10). " .
                "Tono crítico pero justo y fundamentado. El lector debe poder decidir tras leer la reseña.",

            'cuento' =>
                "TIPO DE CONTENIDO: Cuento / Historia narrativa.\n" .
                "Usa estructura narrativa: presentación de personajes y escenario, " .
                "nudo con un conflicto central relacionado con el tema, y desenlace significativo. " .
                "Emplea lenguaje descriptivo, sensorial y evocador. Incluye al menos un diálogo breve. " .
                "El tema de la noticia inspira la historia pero se transforma narrativamente.",

            'receta' =>
                "TIPO DE CONTENIDO: Receta.\n" .
                "Estructura en secciones: introducción con historia o contexto del plato, " .
                "lista de ingredientes con cantidades, pasos numerados de preparación, variaciones y consejos. " .
                "Tono instructivo pero cálido y apetecible. Usa imperativos en los pasos.",
        );

        if ( isset( $prompts[ $type ] ) ) {
            return $prompts[ $type ];
        }

        // Check custom
        $custom = get_option( 'iapostgroq_custom_content_types', array() );
        if ( isset( $custom[ $type ]['prompt'] ) ) {
            return "TIPO DE CONTENIDO: " . ( $custom[ $type ]['label'] ?? $type ) . ".\n" . $custom[ $type ]['prompt'];
        }

        return $prompts['post_blog'];
    }

    // ─── Journalist role prompt ───────────────────────────────────────────────

    public static function get_journalist_role_prompt( string $role ): string {
        $prompts = array(

            'generalista' =>
                "ROL: Periodista generalista.\n" .
                "Escribe para una audiencia amplia y diversa. Explica los términos técnicos cuando los uses. " .
                "Mantén el interés con información relevante para la vida cotidiana del lector promedio. " .
                "Equilibra rigor informativo con accesibilidad.",

            'tecnologia' =>
                "ROL: Experto en Tecnología e Inteligencia Artificial.\n" .
                "Usa vocabulario técnico preciso (algoritmos, modelos, APIs, infraestructura) pero hazlo accesible. " .
                "Contextualiza en tendencias del sector: IA generativa, cloud, ciberseguridad, startups. " .
                "Menciona impacto en la industria, adopción empresarial y casos de uso reales. " .
                "Audiencia: profesionales tech, emprendedores y entusiastas de la tecnología.",

            'politica' =>
                "ROL: Analista político.\n" .
                "Contextualiza en el marco institucional, geopolítico y los actores involucrados. " .
                "Analiza consecuencias para la gobernanza, la ciudadanía y las relaciones de poder. " .
                "Usa lenguaje neutral y riguroso; presenta perspectivas diversas sin tomar partido. " .
                "Referencia marcos legales, precedentes históricos y declaraciones oficiales cuando sea pertinente.",

            'economia' =>
                "ROL: Economista y periodista financiero.\n" .
                "Analiza el impacto económico con indicadores concretos: PIB, inflación, empleo, bolsa, inversión. " .
                "Contextualiza en tendencias macroeconómicas globales y locales. " .
                "Explica mecanismos económicos de forma clara. " .
                "Audiencia: inversores, directivos, emprendedores y ciudadanos con interés financiero.",

            'ciencia' =>
                "ROL: Periodista científico y divulgador.\n" .
                "Traduce conceptos complejos en lenguaje comprensible sin perder rigor. " .
                "Menciona metodología, evidencia disponible y limitaciones del conocimiento actual. " .
                "Evita el sensacionalismo; prioriza la precisión y el matiz. " .
                "Cita organismos de referencia (OMS, NASA, universidades) cuando sea relevante.",

            'cultura' =>
                "ROL: Crítico cultural y periodista de entretenimiento.\n" .
                "Conecta el tema con tendencias culturales, referencias artísticas y el zeitgeist actual. " .
                "Usa un tono accesible, vibrante y con personalidad propia. " .
                "Incluye referencias a cine, música, literatura o arte cuando enriquezca el texto. " .
                "Audiencia: amantes de la cultura, creadores y consumidores de entretenimiento.",

            'deportes' =>
                "ROL: Periodista deportivo.\n" .
                "Combina análisis táctico o estadístico con narrativa emocionante. " .
                "Usa terminología deportiva precisa y apropiada al deporte en cuestión. " .
                "Conecta con el impacto en aficionados, clubes y la industria del deporte. " .
                "Aporta contexto histórico (récords, temporadas previas, rivalidades).",

            'medio_ambiente' =>
                "ROL: Periodista ambiental y de sostenibilidad.\n" .
                "Contextualiza en la crisis climática, pérdida de biodiversidad y transición energética. " .
                "Usa datos científicos de fuentes como el IPCC, WWF o agencias ambientales. " .
                "Tono urgente pero constructivo: señala el problema y visibiliza soluciones. " .
                "Conecta lo global con el impacto local y cotidiano del lector.",

            'internacional' =>
                "ROL: Corresponsal internacional.\n" .
                "Aporta contexto geopolítico, histórico y cultural indispensable para entender el hecho. " .
                "Conecta eventos locales con dinámicas globales y regionales. " .
                "Menciona actores internacionales relevantes: ONU, OTAN, UE, líderes y tratados. " .
                "Tono cosmopolita, riguroso y con perspectiva comparada.",
        );

        if ( isset( $prompts[ $role ] ) ) {
            return $prompts[ $role ];
        }

        // Check custom
        $custom = get_option( 'iapostgroq_custom_journalist_roles', array() );
        if ( isset( $custom[ $role ]['prompt'] ) ) {
            return "ROL: " . ( $custom[ $role ]['label'] ?? $role ) . ".\n" . $custom[ $role ]['prompt'];
        }

        return $prompts['generalista'];
    }

    // ─── Combined system prompt builders ─────────────────────────────────────

    /** System prompt for the article (Call 2). */
    public static function build_article_system_prompt( string $content_type, string $journalist_role, string $keyword ): string {
        $type_prompt = self::get_content_type_prompt( $content_type );
        $role_prompt = self::get_journalist_role_prompt( $journalist_role );

        return "Eres un redactor SEO experto. Genera contenido HTML con bloques Gutenberg:\n" .
               "<!-- wp:paragraph --><p>...</p><!-- /wp:paragraph -->\n" .
               "<!-- wp:heading {\"level\":2} --><h2>...</h2><!-- /wp:heading -->\n\n" .

               $type_prompt . "\n\n" .
               $role_prompt . "\n\n" .

               "REGLAS DE LEGIBILIDAD YOAST — VERIFICACIÓN OBLIGATORIA FRASE A FRASE:\n\n" .
               "REGLA A — FRASES CORTAS (CRÍTICA):\n" .
               "Objetivo: MENOS del 25% de las frases pueden superar 20 palabras.\n" .
               "Método: Escribe frases de 8-15 palabras. Si una frase llega a 18+ palabras, ponle punto y empieza otra.\n" .
               "INCORRECTO: \"La inteligencia artificial está transformando la industria de manera significativa y afecta a todo el sector.\"\n" .
               "CORRECTO: \"La inteligencia artificial transforma la industria. Su impacto alcanza a todo el sector.\"\n\n" .
               "REGLA B — TRANSICIONES (CRÍTICA):\n" .
               "Al menos el 30% de los párrafos DEBEN empezar con un conector de esta lista — ÚSALOS:\n" .
               "Además, | Sin embargo, | Por otro lado, | En consecuencia, | No obstante, | De hecho, |\n" .
               "Por ejemplo, | En primer lugar, | A continuación, | Por último, | Asimismo, |\n" .
               "De esta manera, | Por tanto, | En cambio, | Es decir, | En definitiva, | Como resultado,\n\n" .
               "REGLA C — VARIEDAD (CRÍTICA):\n" .
               "Después de escribir cada párrafo, verifica: ¿alguna palabra aparece al inicio de 3 frases seguidas?\n" .
               "Si sí → reescribe esas frases variando el sujeto o añadiendo un conector.\n\n" .
               "REGLA D — VOCABULARIO:\n" .
               "Usa palabras del habla cotidiana. Si usas un tecnicismo, explícalo en la misma frase.\n\n" .

               "ANCLA SEO INVARIABLE: La keyword \"" . $keyword . "\" es el eje del contenido. " .
               "Debe aparecer: en el primer H2, en las primeras 100 palabras y MÁXIMO 4 veces en todo el texto (headings incluidos). " .
               "Superar 4 apariciones es keyword stuffing y penaliza el SEO. " .
               "Usa sinónimos y pronombres para el resto de referencias. Presencia siempre natural, nunca forzada.";
    }

    /** System prompt for SEO JSON (Call 1). */
    public static function build_seo_system_prompt( string $content_type, string $journalist_role ): string {
        $all_types = self::get_all_content_types();
        $all_roles = self::get_all_journalist_roles();
        $type_label = $all_types[ $content_type ] ?? 'Post de blog';
        $role_label = $all_roles[ $journalist_role ] ?? 'Periodista generalista';

        return "Eres un experto SEO. Responde SIEMPRE en JSON válido sin markdown.\n" .
               "Contexto: el contenido es de tipo \"" . $type_label . "\" y lo redacta un \"" . $role_label . "\".\n" .
               "El título, slug, meta descripción y social post DEBEN reflejar ese tipo de contenido y ese tono.\n\n" .
               "REGLA ABSOLUTA — META DESCRIPTION:\n" .
               "El campo meta_description DEBE contener la keyword EXACTA (las mismas palabras, en el mismo orden, sin variaciones ni sinónimos).\n" .
               "La keyword debe aparecer en los primeros 2/3 del texto de la meta descripción, no al final.\n" .
               "Longitud: entre 110 y 138 caracteres (NUNCA superar 140).\n\n" .
               "COMPROBACIÓN MENTAL OBLIGATORIA antes de emitir el JSON:\n" .
               "Toma la cadena exacta del campo keyword. Localiza esa cadena dentro de meta_description. Si no la encuentras → reescribe meta_description hasta que esté.";
    }
}

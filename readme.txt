=== IAPOST Groq ===
Contributors:       sergioparedes
Tags:               ai, groq, llama, seo, content generation, yoast, gutenberg, openai
Requires at least:  6.0
Tested up to:       6.7
Requires PHP:       7.4
Stable tag:         1.1.0
License:            GPL-2.0+
License URI:        https://www.gnu.org/licenses/gpl-2.0.html

Genera artículos SEO completos con la API de Groq (LLaMA). Integra con Yoast SEO, crea bloques Gutenberg y publica borradores listos para revisar.

== Description ==

**IAPOST Groq** es un plugin de generación de contenido periodístico y SEO impulsado por inteligencia artificial. Utiliza la API de Groq (compatible con OpenAI) y modelos LLaMA de alto rendimiento para producir artículos completos a partir de una noticia base y un enfoque editorial.

=== ¿Qué hace el plugin? ===

A partir de una **noticia base** y un **enfoque editorial**, el plugin realiza dos llamadas a la API de Groq:

1. **Llamada SEO** — genera en JSON: keyword, título, SEO title, meta descripción, slug, post para redes sociales, OG title/description (Facebook) y Twitter title/description.
2. **Llamada artículo** — genera el artículo completo en bloques Gutenberg HTML (1200-1500 palabras) con estructura SEO optimizada.

Todo el contenido se inserta como **borrador** en WordPress con los metadatos de Yoast SEO completos, listo para revisar y publicar.

=== Características principales ===

* **Generador en dos pasos**: noticia + enfoque → 10 campos editables → borrador Gutenberg
* **Integración completa con Yoast SEO**: keyword, SEO title, meta description, OG y Twitter (title + description)
* **Promptbooks**: 9 tipos de contenido (noticia, post blog, tutorial, reportaje, receta, cuento…) × 9 roles de periodista (tecnología, política, economía, ciencia, cultura…)
* **Promptbooks personalizados**: crea y gestiona tus propios tipos de contenido y roles desde la configuración
* **Editor con vista previa Gutenberg**: panel con pestaña Código y Vista previa en tiempo real
* **Etiquetas con autocompletado**: busca entre las etiquetas existentes al escribir
* **Categorías y etiquetas**: asigna en el momento de publicar
* **Contadores de caracteres**: SEO title (60), meta description (140 con barra visual), OG, Twitter
* **Reglas de legibilidad Yoast**: frases cortas (<20 palabras), palabras de transición, variedad de inicio de frase
* **Densidad de keyword controlada**: entre 4 y 6 apariciones por artículo
* **Enlace externo e interno**: incluidos automáticamente para cumplir los requisitos de Yoast
* **Re-redactar con instrucciones**: modal para refinar el contenido con instrucciones adicionales
* **Popup de éxito con redirección**: cuenta atrás de 3 segundos hacia el editor de la entrada creada

=== Modelos soportados ===

* `llama-3.3-70b-versatile` (recomendado)
* `llama-3.1-8b-instant` (rápido)
* `mixtral-8x7b-32768`
* `gemma2-9b-it`

=== Requisitos ===

* WordPress 6.0 o superior
* PHP 7.4 o superior
* Plugin Yoast SEO instalado y activo (recomendado)
* Clave de API de Groq (gratuita en [console.groq.com](https://console.groq.com/keys))

== Installation ==

1. Sube la carpeta `iapost-groq` al directorio `/wp-content/plugins/`
2. Activa el plugin desde el menú **Plugins > Plugins instalados**
3. Ve a **IAPOST Groq > Configuración**
4. Introduce tu clave de API de Groq y haz clic en **Guardar configuración**
5. Haz clic en **Verificar conexión** para confirmar que la API responde correctamente
6. (Opcional) Configura el tipo de contenido y rol de periodista por defecto
7. Ve a **IAPOST Groq > Generador** y comienza a crear contenido

== Frequently Asked Questions ==

= ¿Necesito una cuenta de pago en Groq? =

No. Groq ofrece un plan gratuito con límites generosos. Puedes obtener tu clave en [console.groq.com/keys](https://console.groq.com/keys).

= ¿El plugin publica automáticamente las entradas? =

No. El plugin crea siempre **borradores**. El usuario revisa el contenido antes de publicar.

= ¿Funciona sin Yoast SEO? =

Sí, el generador funciona sin Yoast. Sin embargo, los campos de SEO title, meta description, focus keyword y meta social no se poblarán en ningún panel porque no habrá dónde mostrarlos. Se recomienda tener Yoast instalado.

= ¿Puedo usar otros modelos de Groq? =

Puedes cambiar el modelo desde **Configuración > API y Modelo**. El plugin incluye cuatro opciones preconfiguradas. Si Groq lanza nuevos modelos compatibles, puedes añadirlos directamente desde la base de código.

= ¿Qué son los promptbooks? =

Son conjuntos de instrucciones de escritura que guían a la IA según el tipo de contenido (noticia, receta, tutorial…) y el perfil del redactor (experto en tecnología, política, economía…). Puedes crear los tuyos desde **Configuración > Tipos de Contenido** y **Configuración > Roles de Periodista**.

= ¿Por qué el artículo no supera la revisión de legibilidad de Yoast? =

El plugin incluye reglas de legibilidad en el prompt: frases de menos de 20 palabras, mínimo 30% de párrafos con palabras de transición, variedad en el inicio de frases. Si el artículo generado no supera la revisión, usa el botón **Re-redactar** con instrucciones específicas, por ejemplo: "Acorta todas las frases, usa más palabras de transición".

= ¿El contenido generado es 100% original? =

El contenido es generado por IA a partir de la noticia y el enfoque que proporcionas. Se recomienda revisar siempre el artículo antes de publicarlo para verificar datos, añadir fuentes reales y personalizar el estilo editorial.

= ¿Cómo añado imágenes al artículo? =

El plugin no genera imágenes. Después de crear el borrador, ábrelo en el editor de Gutenberg y añade imágenes manualmente. Se recomienda que el alt text de cada imagen incluya la keyword principal.

== Screenshots ==

1. **Generador** — formulario de entrada con selector de tipo de contenido y rol de periodista
2. **Resultados** — los 10 campos SEO y social editables tras la generación
3. **Editor de contenido** — panel con pestañas Código Gutenberg y Vista previa
4. **Configuración** — pestañas de API, tipos de contenido y roles personalizados
5. **Popup de éxito** — confirmación con cuenta atrás y redirección al editor Gutenberg
6. **Yoast SEO** — panel con keyword, SEO title, meta description y social meta poblados

== Changelog ==

= 1.1.0 =
* Multi-proveedor: soporte para OpenAI (GPT-4o, GPT-4 Turbo, GPT-3.5) además de Groq
* Ampliación de modelos Groq: DeepSeek R1, Qwen QwQ 32B, LLaMA 3.1 70B, Gemma 7B y más (10 modelos)
* Switch de proveedor en configuración con selector de API key y modelos dinámico por proveedor
* Corrección: primer H2 del artículo siempre diferente al título del post
* Mejora SEO: densidad de keyword reducida a máximo 4 apariciones (antes 6)
* Mejora SEO: anchor text de enlaces internos/externos nunca usa la keyword exacta
* Mejora SEO: meta description genera la keyword exacta con plantilla y verificación interna
* Prompt de meta description reforzado con comprobación obligatoria campo a campo

= 1.0.0 =
* Lanzamiento inicial
* Generador de artículos SEO con API Groq (LLaMA 3.3 70B)
* Integración con Yoast SEO: keyword, SEO title, meta description
* Integración con Yoast SEO Social: OG title/description + Twitter title/description
* Promptbooks: 9 tipos de contenido × 9 roles de periodista
* Promptbooks personalizados con CRUD completo en ajustes
* Editor de contenido con vista previa Gutenberg en tiempo real
* Autocompletado de etiquetas con búsqueda en etiquetas existentes
* Selección de categorías y etiquetas al publicar
* Contadores de caracteres y barra de progreso en meta description
* Modal de re-redacción con instrucciones adicionales
* Popup de éxito con cuenta atrás y redirección al editor
* Reglas de legibilidad Yoast integradas en los prompts
* Control de densidad de keyword (4-6 apariciones)
* Generación automática de enlace externo e interno en el artículo

== Upgrade Notice ==

= 1.1.0 =
Añade soporte para OpenAI (GPT-4o y más), amplía los modelos de Groq a 10, y mejora la calidad SEO del contenido generado.

= 1.0.0 =
Primera versión estable. No hay actualizaciones previas.

== Credits ==

Desarrollado por **Sergio Paredes** con la asistencia de **Claude Code** (Anthropic).

* API de IA: [Groq](https://groq.com) — inferencia ultrarrápida con modelos LLaMA
* SEO: [Yoast SEO](https://yoast.com) — integración de metadatos SEO y social
* Editor: [Gutenberg](https://wordpress.org/gutenberg) — bloques HTML nativos de WordPress

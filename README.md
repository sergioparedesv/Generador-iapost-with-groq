# IAPOST Groq — Generador de Contenido SEO para WordPress

> Plugin de WordPress que genera artículos SEO completos usando la API de **Groq** (LLaMA 3.3 70B), integra con **Yoast SEO** y publica borradores en **Gutenberg** listos para revisar.

**Desarrollado por Sergio Paredes & Claude Code**

---

## ¿Qué hace?

A partir de una **noticia base** y un **enfoque editorial**, el plugin realiza dos llamadas a la API de Groq y produce:

| Campo | Descripción |
|---|---|
| Keyword SEO | 1-3 palabras clave óptimas |
| Título del post | 50-65 caracteres con keyword |
| SEO Title (Yoast) | Máx. 60 caracteres |
| Meta Description | 110-138 caracteres con CTA |
| Slug | Generado desde la keyword |
| Social Post | Para copiar a redes, con emojis y hashtags |
| OG Title/Description | Para Facebook (Yoast) |
| Twitter Title/Description | Para Twitter/X (Yoast) |
| Artículo Gutenberg | 1200-1500 palabras en bloques `<!-- wp:* -->` |

Todo se inserta como **borrador** en WordPress con los metadatos de Yoast SEO completos.

---

## Características

- **Promptbooks** — 9 tipos de contenido × 9 roles periodísticos
  - Tipos: Noticia, Artículo de opinión, Reportaje, Post de blog, Tutorial, Entrevista, Reseña, Cuento, Receta
  - Roles: Tecnología, Política, Economía, Ciencia, Cultura, Deportes, Medio Ambiente, Internacional, Generalista
- **Promptbooks personalizados** — crea los tuyos desde la UI de configuración (CRUD completo)
- **Editor con Vista previa Gutenberg** — pestaña Código y Vista previa en tiempo real
- **Reglas de legibilidad Yoast** integradas en el prompt (frases cortas, transiciones, variedad)
- **Densidad de keyword controlada** — entre 4 y 6 apariciones, sin keyword stuffing
- **Enlace externo e interno** generados automáticamente en el artículo
- **Yoast Social Meta** — OG y Twitter title/description poblados automáticamente
- **Autocompletado de etiquetas** — busca entre etiquetas existentes de WordPress
- **Categorías y etiquetas** — asignación en el momento de publicar
- **Contadores de caracteres** con barra de progreso visual en meta description
- **Modal Re-redactar** — refina el artículo con instrucciones adicionales
- **Popup de éxito** con cuenta atrás de 3s que redirige al editor Gutenberg

---

## Requisitos

| Requisito | Versión mínima |
|---|---|
| WordPress | 6.0 |
| PHP | 7.4 |
| Extensión PHP cURL | Cualquier versión |
| Yoast SEO | Recomendado (el plugin funciona sin él) |
| Groq API Key | Gratuita |

> **Sin dependencias externas.** No requiere Composer, Node.js, npm ni ningún paso de compilación. PHP puro + jQuery (incluido en WordPress).

---

## Instalación

### Opción A — Subir ZIP

1. Descarga el ZIP desde [Releases](https://github.com/sergioparedesv/Generador-iapost-with-groq/releases)
2. En WordPress: **Plugins → Añadir nuevo → Subir plugin**
3. Sube el ZIP y activa el plugin

### Opción B — Manual (cPanel / FTP)

1. Descarga o clona este repositorio
2. Sube la carpeta `iapost-groq/` a `/wp-content/plugins/`
3. Activa el plugin desde **Plugins → Plugins instalados**

### Opción C — WP-CLI

```bash
wp plugin install https://github.com/sergioparedesv/Generador-iapost-with-groq/archive/refs/heads/main.zip --activate
```

---

## Configuración

1. Ve a **IAPOST Groq → Configuración → API y Modelo**
2. Introduce tu clave de API de Groq ([obtenerla gratis aquí](https://console.groq.com/keys))
3. Haz clic en **Guardar** y luego en **Verificar conexión**
4. (Opcional) Configura el tipo de contenido y rol de periodista por defecto

---

## Uso

### Generar un artículo

1. Ve a **IAPOST Groq → Generador**
2. Pega la **noticia base** y define el **enfoque editorial**
3. Selecciona el **tipo de contenido** y el **rol del periodista**
4. Haz clic en **Generar Contenido**
5. Revisa y edita los 10 campos generados
6. Asigna categorías y etiquetas
7. Haz clic en **Enviar como Entrada** → se crea el borrador y redirige al editor Gutenberg

### Re-redactar

Si el artículo no cumple tus expectativas, haz clic en **Re-redactar**, escribe instrucciones específicas (ej: *"Usa un tono más informal, acorta las frases"*) y aplica.

### Gestionar Promptbooks

En **Configuración → Tipos de Contenido** y **Configuración → Roles de Periodista** puedes:
- Ver los 9 tipos/roles integrados (solo lectura)
- Crear, editar y eliminar tipos y roles personalizados
- Definir las instrucciones exactas que la IA aplicará para ese perfil

---

## Estructura del plugin

```
iapost-groq/
├── iapost-groq.php                  ← Entrada del plugin (singleton + hooks)
├── readme.txt                       ← Documentación formato WordPress.org
├── README.md                        ← Este archivo
├── assets/
│   ├── admin.css                    ← Estilos del panel de administración
│   └── admin.js                     ← jQuery AJAX, preview Gutenberg, autocomplete
└── includes/
    ├── class-groq-api.php           ← Wrapper cURL para la API de Groq
    ├── class-content-generator.php  ← Orquesta las 2 llamadas a la API
    ├── class-post-creator.php       ← wp_insert_post + metadatos Yoast
    ├── class-admin.php              ← Menú, vistas, handlers AJAX
    └── class-promptbooks.php        ← Biblioteca de promptbooks (tipos × roles)
```

---

## Modelos disponibles

| Modelo | Descripción |
|---|---|
| `llama-3.3-70b-versatile` | **Recomendado** — mejor calidad |
| `llama-3.1-8b-instant` | Más rápido, menor calidad |
| `mixtral-8x7b-32768` | Ventana de contexto grande |
| `gemma2-9b-it` | Alternativa ligera |

---

## Despliegue en producción (cPanel / Hosting compartido)

El plugin **no tiene dependencias externas** ni pasos de compilación. Para desplegarlo:

1. Sube la carpeta `iapost-groq/` a `public_html/wp-content/plugins/`
2. Activa desde el panel de WordPress
3. Asegúrate de que PHP tenga activada la extensión `cURL` (habitual en todos los hostings)
4. El servidor debe poder hacer peticiones HTTPS salientes a `api.groq.com` (puerto 443)

> La mayoría de hostings compartidos en cPanel permiten esto por defecto. Si el hosting tiene restricciones de `allow_url_fopen` o firewall saliente, consulta con tu proveedor.

---

## Metadatos Yoast SEO que se rellenan

| Meta key | Campo |
|---|---|
| `_yoast_wpseo_focuskw` | Keyword principal |
| `_yoast_wpseo_title` | SEO Title |
| `_yoast_wpseo_metadesc` | Meta Description |
| `_yoast_wpseo_opengraph-title` | Facebook Title |
| `_yoast_wpseo_opengraph-description` | Facebook Description |
| `_yoast_wpseo_twitter-title` | Twitter Title |
| `_yoast_wpseo_twitter-description` | Twitter Description |

---

## Licencia

GPL-2.0+ — ver [LICENSE](https://www.gnu.org/licenses/gpl-2.0.html)

---

## Créditos

Desarrollado por **[Sergio Paredes](https://github.com/sergioparedesv)** con la asistencia de **[Claude Code](https://claude.ai/claude-code)** (Anthropic).

- API de IA: [Groq](https://groq.com) — inferencia ultrarrápida con modelos LLaMA
- SEO: [Yoast SEO](https://yoast.com)
- Editor: [Gutenberg / WordPress](https://wordpress.org)

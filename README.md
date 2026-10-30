# viki-aiproductoptimizer

Módulo `aiproductoptimizer`: mejora títulos, descripciones y meta tags de los productos con ChatGPT o Gemini.

## Qué hace

Añade una pestaña en la ficha de producto y un panel de back office para generar los textos, con tono, idioma y palabras clave configurables. Registra lo optimizado en `aiopt_optimized`.

## Arquitectura

- `aiproductoptimizer.php`: instalación y hooks `displayAdminProductsExtra`, `actionAdminControllerSetMedia`.
- `classes/AIProvider.php`: base común. `OpenAIProvider.php` y `GeminiProvider.php`: proveedores.
- `controllers/admin/AdminAiProductOptimizerController.php`: panel y lotes.

## Instalación en otro PrestaShop

Sube `aiproductoptimizer/` a `modules/` e instálalo. PS 1.7.0 a 8.x. Elige proveedor y pega su clave en la configuración.

## Configuración

Ajustes `AIOPT_*`: `PROVIDER`, `OPENAI_KEY`, `OPENAI_MODEL`, `GEMINI_KEY`, `GEMINI_MODEL`, `LANGUAGE`, `TONE`, `INCLUDE_KEYWORDS`, `AUTO_SAVE`.

## Dependencias

API de OpenAI o de Gemini. `gkdailypanel` reutiliza sus claves y `scripts/aiopt_nightly_seo.php` de goalkers-web lanza lotes nocturnos.

## Núcleo y específico

Módulo genérico, sin datos de Goalkers en el código.

## Pendientes

- Sin pendientes abiertos.

---

© 8Pecados, todos los derechos reservados. Repositorio privado.

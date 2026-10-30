<?php
/**
 * Interfaz base para proveedores de IA
 * 
 * @author    OKVIKI <https://okviki.com>
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

abstract class AIProvider
{
    protected $apiKey;
    protected $model;

    public function __construct($apiKey, $model)
    {
        $this->apiKey = $apiKey;
        $this->model = $model;
    }

    abstract public function optimizeProduct($title, $currentDescription, $category, $options = []);
    abstract public function generateTitle($originalTitle, $category, $options = []);
    abstract public function generateShortDescription($title, $currentDescription, $options = []);
    abstract public function generateLongDescription($title, $currentDescription, $category, $options = []);
    abstract public function generateMetaTitle($title, $category, $options = []);
    abstract public function generateMetaDescription($title, $description, $options = []);

    protected function buildSystemPrompt($language, $tone)
    {
        $toneDescriptions = [
            'profesional' => 'profesional, formal y confiable',
            'casual' => 'cercano, amigable y conversacional',
            'persuasivo' => 'persuasivo, orientado a ventas y con llamadas a la acción',
            'tecnico' => 'técnico, detallado y preciso con especificaciones',
            'lujoso' => 'elegante, sofisticado y premium',
        ];

        $languages = [
            'es' => 'español',
            'en' => 'inglés',
            'fr' => 'francés',
            'de' => 'alemán',
            'it' => 'italiano',
            'pt' => 'portugués',
        ];

        $toneDesc = $toneDescriptions[$tone] ?? 'profesional';
        $langName = $languages[$language] ?? 'español';

        return "Eres un experto en SEO y copywriting para ecommerce. Tu trabajo es optimizar contenido de productos para mejorar su posicionamiento en buscadores y aumentar las conversiones.

REGLAS IMPORTANTES:
- Escribe SIEMPRE en {$langName}
- Usa un tono {$toneDesc}
- Incluye palabras clave relevantes de forma natural
- No uses caracteres especiales innecesarios
- Evita el keyword stuffing
- Los meta títulos deben tener máximo 60 caracteres
- Las meta descripciones deben tener entre 150-160 caracteres
- Las descripciones deben ser únicas y atractivas
- Usa HTML válido para las descripciones largas (párrafos, listas)

Responde SIEMPRE en formato JSON válido.";
    }

    protected function buildOptimizationPrompt($title, $currentDescription, $category, $options)
    {
        $keywords = isset($options['keywords']) ? implode(', ', $options['keywords']) : '';
        $brand = $options['brand'] ?? '';
        $features = $options['features'] ?? '';

        $prompt = "Optimiza el siguiente producto para SEO:

TÍTULO ORIGINAL: {$title}
CATEGORÍA: {$category}";

        if ($brand) {
            $prompt .= "\nMARCA: {$brand}";
        }

        if ($currentDescription) {
            $prompt .= "\nDESCRIPCIÓN ACTUAL: " . mb_substr(strip_tags($currentDescription), 0, 500);
        }

        if ($features) {
            $prompt .= "\nCARACTERÍSTICAS: {$features}";
        }

        if ($keywords) {
            $prompt .= "\nPALABRAS CLAVE A INCLUIR: {$keywords}";
        }

        $prompt .= "

Genera un JSON con la siguiente estructura:
{
    \"title\": \"Título optimizado SEO (máx 70 caracteres)\",
    \"short_description\": \"Resumen atractivo del producto (máx 300 caracteres)\",
    \"description\": \"Descripción completa en HTML con párrafos y listas si es necesario (400-800 palabras)\",
    \"meta_title\": \"Meta título SEO (máx 60 caracteres)\",
    \"meta_description\": \"Meta descripción SEO (150-160 caracteres)\",
    \"suggested_keywords\": [\"keyword1\", \"keyword2\", \"keyword3\"]
}

IMPORTANTE: Devuelve SOLO el JSON, sin markdown ni texto adicional.";

        return $prompt;
    }

    protected function parseJsonResponse($response)
    {
        $response = preg_replace('/```json\s*/', '', $response);
        $response = preg_replace('/```\s*/', '', $response);
        $response = trim($response);

        $data = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Error al parsear respuesta JSON: ' . json_last_error_msg());
        }

        return $data;
    }

    protected function validateApiKey()
    {
        if (empty($this->apiKey)) {
            throw new Exception('API Key no configurada. Ve a la configuración del módulo.');
        }
    }
}

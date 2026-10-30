<?php
/**
 * Proveedor de Google Gemini
 * 
 * @author    OKVIKI <https://okviki.com>
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class GeminiProvider extends AIProvider
{
    private $apiBaseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/';

    private function makeRequest($prompt, $systemPrompt = '', $maxTokens = 2000)
    {
        $this->validateApiKey();

        $url = $this->apiBaseUrl . $this->model . ':generateContent?key=' . $this->apiKey;

        $contents = [];
        
        if ($systemPrompt) {
            $contents[] = [
                'role' => 'user',
                'parts' => [['text' => $systemPrompt . "\n\nAhora responde a la siguiente petición:"]],
            ];
            $contents[] = [
                'role' => 'model',
                'parts' => [['text' => 'Entendido. Estoy listo para optimizar contenido de productos siguiendo las reglas indicadas. Responderé siempre en formato JSON válido.']],
            ];
        }

        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => $prompt]],
        ];

        $data = [
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => 0.7,
                'maxOutputTokens' => $maxTokens,
                'responseMimeType' => 'application/json',
            ],
            'safetySettings' => [
                ['category' => 'HARM_CATEGORY_HARASSMENT', 'threshold' => 'BLOCK_NONE'],
                ['category' => 'HARM_CATEGORY_HATE_SPEECH', 'threshold' => 'BLOCK_NONE'],
                ['category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => 'BLOCK_NONE'],
                ['category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_NONE'],
            ],
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_TIMEOUT => 120,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception('Error de conexión: ' . $error);
        }

        $result = json_decode($response, true);

        if ($httpCode !== 200) {
            $errorMsg = isset($result['error']['message']) ? $result['error']['message'] : 'Error desconocido';
            throw new Exception('Error de Gemini (' . $httpCode . '): ' . $errorMsg);
        }

        if (!isset($result['candidates'][0]['content']['parts'][0]['text'])) {
            if (isset($result['candidates'][0]['finishReason']) && $result['candidates'][0]['finishReason'] === 'SAFETY') {
                throw new Exception('La respuesta fue bloqueada por filtros de seguridad de Gemini');
            }
            throw new Exception('Respuesta inválida de Gemini');
        }

        return $result['candidates'][0]['content']['parts'][0]['text'];
    }

    public function optimizeProduct($title, $currentDescription, $category, $options = [])
    {
        $language = $options['language'] ?? 'es';
        $tone = $options['tone'] ?? 'profesional';

        $systemPrompt = $this->buildSystemPrompt($language, $tone);
        $prompt = $this->buildOptimizationPrompt($title, $currentDescription, $category, $options);

        $response = $this->makeRequest($prompt, $systemPrompt, 3000);
        return $this->parseJsonResponse($response);
    }

    public function generateTitle($originalTitle, $category, $options = [])
    {
        $language = $options['language'] ?? 'es';
        $tone = $options['tone'] ?? 'profesional';

        $systemPrompt = $this->buildSystemPrompt($language, $tone);
        $prompt = "Genera un título optimizado para SEO basado en:
TÍTULO ORIGINAL: {$originalTitle}
CATEGORÍA: {$category}

Responde con un JSON: {\"title\": \"título optimizado (máx 70 caracteres)\"}";

        $response = $this->makeRequest($prompt, $systemPrompt, 200);
        $data = $this->parseJsonResponse($response);
        return $data['title'] ?? $originalTitle;
    }

    public function generateShortDescription($title, $currentDescription, $options = [])
    {
        $language = $options['language'] ?? 'es';
        $tone = $options['tone'] ?? 'profesional';

        $systemPrompt = $this->buildSystemPrompt($language, $tone);
        $prompt = "Genera una descripción corta/resumen para este producto:
TÍTULO: {$title}
DESCRIPCIÓN ACTUAL: " . mb_substr(strip_tags($currentDescription), 0, 500) . "

Responde con un JSON: {\"short_description\": \"resumen atractivo (máx 300 caracteres)\"}";

        $response = $this->makeRequest($prompt, $systemPrompt, 500);
        $data = $this->parseJsonResponse($response);
        return $data['short_description'] ?? '';
    }

    public function generateLongDescription($title, $currentDescription, $category, $options = [])
    {
        $language = $options['language'] ?? 'es';
        $tone = $options['tone'] ?? 'profesional';

        $systemPrompt = $this->buildSystemPrompt($language, $tone);
        $prompt = "Genera una descripción completa optimizada para SEO:
TÍTULO: {$title}
CATEGORÍA: {$category}
DESCRIPCIÓN ACTUAL: " . mb_substr(strip_tags($currentDescription), 0, 500) . "

Responde con un JSON: {\"description\": \"descripción HTML completa (400-800 palabras)\"}";

        $response = $this->makeRequest($prompt, $systemPrompt, 2000);
        $data = $this->parseJsonResponse($response);
        return $data['description'] ?? '';
    }

    public function generateMetaTitle($title, $category, $options = [])
    {
        $language = $options['language'] ?? 'es';
        $tone = $options['tone'] ?? 'profesional';

        $systemPrompt = $this->buildSystemPrompt($language, $tone);
        $prompt = "Genera un meta título SEO para:
TÍTULO PRODUCTO: {$title}
CATEGORÍA: {$category}

Responde con un JSON: {\"meta_title\": \"meta título SEO (máx 60 caracteres)\"}";

        $response = $this->makeRequest($prompt, $systemPrompt, 200);
        $data = $this->parseJsonResponse($response);
        return $data['meta_title'] ?? '';
    }

    public function generateMetaDescription($title, $description, $options = [])
    {
        $language = $options['language'] ?? 'es';
        $tone = $options['tone'] ?? 'profesional';

        $systemPrompt = $this->buildSystemPrompt($language, $tone);
        $prompt = "Genera una meta descripción SEO para:
TÍTULO: {$title}
DESCRIPCIÓN: " . mb_substr(strip_tags($description), 0, 300) . "

Responde con un JSON: {\"meta_description\": \"meta descripción SEO (150-160 caracteres)\"}";

        $response = $this->makeRequest($prompt, $systemPrompt, 300);
        $data = $this->parseJsonResponse($response);
        return $data['meta_description'] ?? '';
    }
}

<?php
/**
 * Proveedor de OpenAI (ChatGPT)
 * 
 * @author    OKVIKI <https://okviki.com>
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class OpenAIProvider extends AIProvider
{
    private $apiUrl = 'https://api.openai.com/v1/chat/completions';

    private function makeRequest($messages, $maxTokens = 2000)
    {
        $this->validateApiKey();

        $data = [
            'model' => $this->model,
            'messages' => $messages,
            'max_tokens' => $maxTokens,
            'temperature' => 0.7,
            'response_format' => ['type' => 'json_object'],
        ];

        $ch = curl_init($this->apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
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
            throw new Exception('Error de OpenAI (' . $httpCode . '): ' . $errorMsg);
        }

        if (!isset($result['choices'][0]['message']['content'])) {
            throw new Exception('Respuesta inválida de OpenAI');
        }

        return $result['choices'][0]['message']['content'];
    }

    public function optimizeProduct($title, $currentDescription, $category, $options = [])
    {
        $language = $options['language'] ?? 'es';
        $tone = $options['tone'] ?? 'profesional';

        $messages = [
            ['role' => 'system', 'content' => $this->buildSystemPrompt($language, $tone)],
            ['role' => 'user', 'content' => $this->buildOptimizationPrompt($title, $currentDescription, $category, $options)],
        ];

        $response = $this->makeRequest($messages, 3000);
        return $this->parseJsonResponse($response);
    }

    public function generateTitle($originalTitle, $category, $options = [])
    {
        $language = $options['language'] ?? 'es';
        $tone = $options['tone'] ?? 'profesional';

        $prompt = "Genera un título optimizado para SEO basado en:
TÍTULO ORIGINAL: {$originalTitle}
CATEGORÍA: {$category}

Responde con un JSON: {\"title\": \"título optimizado (máx 70 caracteres)\"}";

        $messages = [
            ['role' => 'system', 'content' => $this->buildSystemPrompt($language, $tone)],
            ['role' => 'user', 'content' => $prompt],
        ];

        $response = $this->makeRequest($messages, 200);
        $data = $this->parseJsonResponse($response);
        return $data['title'] ?? $originalTitle;
    }

    public function generateShortDescription($title, $currentDescription, $options = [])
    {
        $language = $options['language'] ?? 'es';
        $tone = $options['tone'] ?? 'profesional';

        $prompt = "Genera una descripción corta/resumen para este producto:
TÍTULO: {$title}
DESCRIPCIÓN ACTUAL: " . mb_substr(strip_tags($currentDescription), 0, 500) . "

Responde con un JSON: {\"short_description\": \"resumen atractivo (máx 300 caracteres)\"}";

        $messages = [
            ['role' => 'system', 'content' => $this->buildSystemPrompt($language, $tone)],
            ['role' => 'user', 'content' => $prompt],
        ];

        $response = $this->makeRequest($messages, 500);
        $data = $this->parseJsonResponse($response);
        return $data['short_description'] ?? '';
    }

    public function generateLongDescription($title, $currentDescription, $category, $options = [])
    {
        $language = $options['language'] ?? 'es';
        $tone = $options['tone'] ?? 'profesional';

        $prompt = "Genera una descripción completa optimizada para SEO:
TÍTULO: {$title}
CATEGORÍA: {$category}
DESCRIPCIÓN ACTUAL: " . mb_substr(strip_tags($currentDescription), 0, 500) . "

Responde con un JSON: {\"description\": \"descripción HTML completa (400-800 palabras)\"}";

        $messages = [
            ['role' => 'system', 'content' => $this->buildSystemPrompt($language, $tone)],
            ['role' => 'user', 'content' => $prompt],
        ];

        $response = $this->makeRequest($messages, 2000);
        $data = $this->parseJsonResponse($response);
        return $data['description'] ?? '';
    }

    public function generateMetaTitle($title, $category, $options = [])
    {
        $language = $options['language'] ?? 'es';
        $tone = $options['tone'] ?? 'profesional';

        $prompt = "Genera un meta título SEO para:
TÍTULO PRODUCTO: {$title}
CATEGORÍA: {$category}

Responde con un JSON: {\"meta_title\": \"meta título SEO (máx 60 caracteres)\"}";

        $messages = [
            ['role' => 'system', 'content' => $this->buildSystemPrompt($language, $tone)],
            ['role' => 'user', 'content' => $prompt],
        ];

        $response = $this->makeRequest($messages, 200);
        $data = $this->parseJsonResponse($response);
        return $data['meta_title'] ?? '';
    }

    public function generateMetaDescription($title, $description, $options = [])
    {
        $language = $options['language'] ?? 'es';
        $tone = $options['tone'] ?? 'profesional';

        $prompt = "Genera una meta descripción SEO para:
TÍTULO: {$title}
DESCRIPCIÓN: " . mb_substr(strip_tags($description), 0, 300) . "

Responde con un JSON: {\"meta_description\": \"meta descripción SEO (150-160 caracteres)\"}";

        $messages = [
            ['role' => 'system', 'content' => $this->buildSystemPrompt($language, $tone)],
            ['role' => 'user', 'content' => $prompt],
        ];

        $response = $this->makeRequest($messages, 300);
        $data = $this->parseJsonResponse($response);
        return $data['meta_description'] ?? '';
    }
}

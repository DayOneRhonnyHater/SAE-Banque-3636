<?php
// FinBot/classes/Chatbot.php
class Chatbot {
    private $apiKey;
    private $model;
    
    public function __construct($apiKey = null, $model = null) {
        $this->apiKey = $apiKey ?? GEMINI_API_KEY;
        $this->model = $model ?? GEMINI_MODEL;
    }
    
    public function sendMessage($message, $context = []) {
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . $this->model . ':generateContent';
        
        // Préparer les données utilisateur avec contexte bancaire
        $data = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $message]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.4,
                'topK' => 32,
                'topP' => 0.95,
                'maxOutputTokens' => 1024,
            ]
        ];
        
        // Ajouter le contexte spécifique à l'utilisateur si disponible
        if (!empty($context)) {
            $data['contents'][0]['parts'][0]['text'] = "Contexte utilisateur: " . json_encode($context) . "\n\nQuestion: " . $message;
        }
        
        $options = [
            'http' => [
                'method' => 'POST',
                'header' => [
                    'Content-Type: application/json',
                    'x-goog-api-key: ' . $this->apiKey
                ],
                'content' => json_encode($data)
            ]
        ];
        
        $context = stream_context_create($options);
        $response = file_get_contents($url, false, $context);
        
        return $this->parseResponse(json_decode($response, true));
    }
    
    private function parseResponse($response) {
        // Extraire le texte de la réponse
        if (isset($response['candidates'][0]['content']['parts'][0]['text'])) {
            return $response['candidates'][0]['content']['parts'][0]['text'];
        }
        
        return "Je suis désolé, je n'ai pas pu traiter votre demande.";
    }

    public function sendMessagePython($message, $context = []) {
        $data = json_encode([
            'message' => $message,
            'context' => $context
        ]);
        
        // Chemin relatif corrigé selon votre structure
        $scriptPath = __DIR__ . '/../../python/chatbot.py';
        
        $command = escapeshellcmd("python3 $scriptPath " . escapeshellarg($data));
        $output = shell_exec($command);
        
        return json_decode($output, true);
    }
}

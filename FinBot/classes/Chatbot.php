<?php
class Chatbot {
    const MAX_RETRIES = 2;
    const TIMEOUT = 30; // secondes

    public function sendMessage($message, $context = []) {
        $apiKey = getenv('GEMINI_API_KEY');
        $scriptPath = realpath(__DIR__ . '/../../python/chatbot.py');
        
        $data = [
            'message' => $message,
            'context' => array_merge($context, [
                'user_ip' => $_SERVER['REMOTE_ADDR'],
                'timestamp' => time()
            ])
        ];

        $command = sprintf(
            'export GEMINI_API_KEY=%s && python %s %s 2>&1',
            escapeshellarg($apiKey),
            escapeshellarg($scriptPath),
            escapeshellarg(json_encode($data))
        );

        $output = shell_exec($command);
        $response = json_decode($output, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("Erreur JSON: $output");
            return ['error' => 'Réponse invalide du serveur'];
        }

        return $response;
    }
}
?>
 

<?php
class Chatbot {
    /**
     * Envoie un message au script Python du chatbot et retourne la réponse
     * 
     * @param string $message Message de l'utilisateur
     * @param array $context Contexte utilisateur (optionnel)
     * @return array Réponse décodée du chatbot
     */
    public function sendMessagePython($message, $context = []) {
        // Préparation des données au format JSON
        $data = json_encode([
            'message' => $message,
            'context' => $context
        ]);
        
        // Chemin absolu vers le script Python (adapté à votre structure)
        $scriptPath = realpath(__DIR__ . '/../../python/chatbot.py');
        
        // Construction de la commande shell sécurisée
        $command = escapeshellcmd("python3 $scriptPath " . escapeshellarg($data));
        
        // Exécution et récupération de la sortie
        $output = shell_exec($command);
        
        // Décodage de la réponse JSON
        $response = json_decode($output, true);
        
        // Gestion des erreurs de décodage
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['error' => 'Erreur de format de réponse du chatbot'];
        }
        
        
        // Gestion des erreurs d'exécution du script Python
        if ($output === null) {
            return ['error' => 'Erreur d\'exécution du script Python'];
}
        return $response;
    }
}
C:/Users/Moi wsh/Documents/SAE-Banque-3636/FinBot
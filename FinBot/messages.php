<?php
// filepath: c:\wamp\www\projetBUT\SAE.04\SAE-Banque\FinBot\messages.php
session_start();
require_once __DIR__ . '/config/app.php';

// Vérifier si l'utilisateur est connecté
require_once __DIR__ . '/includes/auth_functions.php';
try {
    checkAuth();
} catch (Exception $e) {
    header('Location: index.php');
    exit;
}

// Récupérer l'ID de l'utilisateur connecté
$userId = $_SESSION['user']['id'];
$role = $_SESSION['user']['role'];

// Configuration de l'API Gemini
define('GEMINI_API_KEY', 'YOUR_GEMINI_API_KEY'); // À remplacer par votre clé API
define('GEMINI_API_URL', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent');

// Initialiser l'historique de chat si nécessaire
if (!isset($_SESSION['chat_history'])) {
    $_SESSION['chat_history'] = [
        [
            'role' => 'ai',
            'content' => "Bonjour, je suis FinBot, votre conseiller financier virtuel. Comment puis-je vous aider aujourd'hui?",
            'time' => date('H:i')
        ]
    ];
}

// Traitement des demandes AJAX
$success = '';
$error = '';

// Vérifier si c'est une requête AJAX
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    // Action: envoi de message au chatbot
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'chat') {
        // Récupérer les données JSON
        $requestData = json_decode(file_get_contents('php://input'), true);
        $userMessage = isset($requestData['message']) ? trim($requestData['message']) : '';
        
        if (empty($userMessage)) {
            echo json_encode(['success' => false, 'error' => 'Message vide']);
            exit;
        }
        
        // Ajouter le message de l'utilisateur à l'historique
        $_SESSION['chat_history'][] = [
            'role' => 'user',
            'content' => $userMessage,
            'time' => date('H:i')
        ];
        
        try {
            // Préparer l'historique pour l'API Gemini
            $geminiMessages = [];
            foreach ($_SESSION['chat_history'] as $message) {
                $geminiMessages[] = [
                    'role' => $message['role'] === 'ai' ? 'model' : 'user',
                    'parts' => [
                        ['text' => $message['content']]
                    ]
                ];
            }
            
            // Ajouter des instructions spécifiques pour le contexte bancaire
            $systemPrompt = [
                'role' => 'system',
                'parts' => [
                    ['text' => "Tu es FinBot, un assistant financier bancaire intelligent. Tu dois aider les clients avec leurs questions financières, leur donner des conseils sur les produits bancaires, les aider à comprendre leurs options d'épargne et d'investissement, et leur fournir des informations générales sur la finance personnelle. Réponds de manière professionnelle, précise et amicale. N'invente pas de produits spécifiques qui n'existent pas. Précise quand une consultation avec un conseiller humain est nécessaire pour des besoins complexes. Utilise le tutoiement et exprime-toi en français. N'oublie pas d'inclure des avertissements appropriés lorsque tu donnes des conseils financiers."]
                ]
            ];
            
            // Ajouter le message système au début
            array_unshift($geminiMessages, $systemPrompt);
            
            // Préparer la requête pour l'API Gemini
            $requestBody = [
                'contents' => $geminiMessages,
                'generationConfig' => [
                    'temperature' => 0.7,
                    'topK' => 40,
                    'topP' => 0.95,
                    'maxOutputTokens' => 1024
                ]
            ];
            
            // Envoyer la requête à l'API Gemini
            $ch = curl_init(GEMINI_API_URL . '?key=' . GEMINI_API_KEY);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestBody));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json'
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode !== 200) {
                throw new Exception('Erreur de l\'API Gemini: ' . $response);
            }
            
            $responseData = json_decode($response, true);
            
            // Traiter la réponse de Gemini
            if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
                $aiResponse = $responseData['candidates'][0]['content']['parts'][0]['text'];
                
                // Ajouter la réponse à l'historique
                $_SESSION['chat_history'][] = [
                    'role' => 'ai',
                    'content' => $aiResponse,
                    'time' => date('H:i')
                ];
                
                echo json_encode(['success' => true, 'response' => $aiResponse]);
            } else {
                throw new Exception('Format de réponse inattendu');
            }
        } catch (Exception $e) {
            // En cas d'erreur, ajouter un message d'erreur à l'historique
            $_SESSION['chat_history'][] = [
                'role' => 'ai',
                'content' => "Désolé, j'ai rencontré un problème technique. Veuillez réessayer ou contacter un conseiller.",
                'time' => date('H:i')
            ];
            
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        
        exit;
    }
    
    // Action: réinitialiser la conversation
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'reset_chat') {
        // Réinitialiser l'historique de chat
        $_SESSION['chat_history'] = [
            [
                'role' => 'ai',
                'content' => "Bonjour, je suis FinBot, votre conseiller financier virtuel. Comment puis-je vous aider aujourd'hui?",
                'time' => date('H:i')
            ]
        ];
        
        echo json_encode(['success' => true]);
        exit;
    }
}

// Configurer les variables pour le layout
$pageTitle = 'FinBot - Conseiller Virtuel';
$pageCss = 'messages';
$viewFile = '/views/messages.php';

// Scripts spécifiques au chatbot
$footerScripts = <<<HTML
<!-- Scripts supplémentaires pour le chatbot peuvent être ajoutés ici -->
HTML;

// Inclure le layout principal
include __DIR__ . '/templates/layout.php';
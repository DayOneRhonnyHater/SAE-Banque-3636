<?php
// filepath: c:\wamp\www\projetBUT\SAE.04\SAE-Banque\FinBot\includes\validations.php

/**
 * Fonctions de validation pour l'application FinBot
 */

// Pas besoin d'inclusions externes pour les fonctions de validation de base

/**
 * Valide une adresse email
 * 
 * @param string $email Adresse email à valider
 * @return bool|string True si valide, message d'erreur sinon
 */
function validateEmail($email) {
    $email = trim($email);
    
    if (empty($email)) {
        return "L'adresse email est requise";
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return "L'adresse email n'est pas valide";
    }
    
    return true;
}

/**
 * Valide un mot de passe selon les critères de sécurité
 * 
 * @param string $password Mot de passe à valider
 * @param bool $isStrict Mode strict (plus exigeant)
 * @return bool|string True si valide, message d'erreur sinon
 */
function validatePassword($password, $isStrict = false) {
    if (empty($password)) {
        return "Le mot de passe est requis";
    }
    
    if (strlen($password) < 8) {
        return "Le mot de passe doit contenir au moins 8 caractères";
    }
    
    // Validation de base
    $hasUppercase = preg_match('/[A-Z]/', $password);
    $hasLowercase = preg_match('/[a-z]/', $password);
    $hasNumber = preg_match('/[0-9]/', $password);
    
    if (!$hasUppercase || !$hasLowercase || !$hasNumber) {
        return "Le mot de passe doit contenir au moins une majuscule, une minuscule et un chiffre";
    }
    
    // Validation stricte (optionnelle)
    if ($isStrict) {
        $hasSpecialChar = preg_match('/[^A-Za-z0-9]/', $password);
        
        if (!$hasSpecialChar) {
            return "Le mot de passe doit contenir au moins un caractère spécial";
        }
        
        if (strlen($password) < 12) {
            return "Le mot de passe doit contenir au moins 12 caractères";
        }
    }
    
    return true;
}

/**
 * Valide un numéro de téléphone
 * 
 * @param string $phone Numéro de téléphone à valider
 * @return bool|string True si valide, message d'erreur sinon
 */
function validatePhone($phone) {
    $phone = trim($phone);
    
    if (empty($phone)) {
        return "Le numéro de téléphone est requis";
    }
    
    // Nettoyage du numéro (retirer espaces, tirets, etc.)
    $cleanPhone = preg_replace('/[^0-9+]/', '', $phone);
    
    // Pour un numéro français commençant par 0
    if (preg_match('/^0[1-9][0-9]{8}$/', $cleanPhone)) {
        return true;
    }
    
    // Pour un numéro international commençant par +
    if (preg_match('/^\+[1-9][0-9]{6,14}$/', $cleanPhone)) {
        return true;
    }
    
    return "Le numéro de téléphone n'est pas valide";
}

/**
 * Valide un montant financier
 * 
 * @param mixed $amount Montant à valider
 * @param float $min Montant minimum (optionnel)
 * @param float $max Montant maximum (optionnel)
 * @return bool|string True si valide, message d'erreur sinon
 */
function validateAmount($amount, $min = null, $max = null) {
    // Convertir la valeur en nombre
    $amount = str_replace(',', '.', trim($amount));
    
    if (!is_numeric($amount)) {
        return "Le montant doit être un nombre";
    }
    
    $amount = floatval($amount);
    
    if ($amount <= 0) {
        return "Le montant doit être supérieur à zéro";
    }
    
    if ($min !== null && $amount < $min) {
        return "Le montant minimum est de " . number_format($min, 2, ',', ' ') . " €";
    }
    
    if ($max !== null && $amount > $max) {
        return "Le montant maximum est de " . number_format($max, 2, ',', ' ') . " €";
    }
    
    return true;
}

/**
 * Valide une date
 * 
 * @param string $date Date à valider (format YYYY-MM-DD)
 * @param string $min Date minimum (optionnel)
 * @param string $max Date maximum (optionnel)
 * @return bool|string True si valide, message d'erreur sinon
 */
function validateDate($date, $min = null, $max = null) {
    $date = trim($date);
    
    if (empty($date)) {
        return "La date est requise";
    }
    
    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return "Le format de date n'est pas valide";
    }
    
    // Reconstruire la date pour vérifier si le format est correct
    $checkDate = date('Y-m-d', $timestamp);
    if ($checkDate !== $date) {
        return "La date n'est pas valide";
    }
    
    if ($min !== null && strtotime($date) < strtotime($min)) {
        return "La date doit être postérieure au " . date('d/m/Y', strtotime($min));
    }
    
    if ($max !== null && strtotime($date) > strtotime($max)) {
        return "La date doit être antérieure au " . date('d/m/Y', strtotime($max));
    }
    
    return true;
}

/**
 * Valide un code postal français
 * 
 * @param string $postalCode Code postal à valider
 * @return bool|string True si valide, message d'erreur sinon
 */
function validatePostalCode($postalCode) {
    $postalCode = trim($postalCode);
    
    if (empty($postalCode)) {
        return "Le code postal est requis";
    }
    
    if (!preg_match('/^[0-9]{5}$/', $postalCode)) {
        return "Le code postal doit contenir 5 chiffres";
    }
    
    return true;
}

/**
 * Valide un texte (longueur min/max)
 * 
 * @param string $text Texte à valider
 * @param int $minLength Longueur minimum
 * @param int $maxLength Longueur maximum
 * @return bool|string True si valide, message d'erreur sinon
 */
function validateText($text, $minLength = 0, $maxLength = null) {
    if ($minLength > 0 && strlen(trim($text)) < $minLength) {
        return "Ce champ doit contenir au moins " . $minLength . " caractères";
    }
    
    if ($maxLength !== null && strlen(trim($text)) > $maxLength) {
        return "Ce champ ne peut pas dépasser " . $maxLength . " caractères";
    }
    
    return true;
}

/**
 * Valide le format d'un IBAN
 * 
 * @param string $iban IBAN à valider
 * @return bool|string True si valide, message d'erreur sinon
 */
function validateIBAN($iban) {
    // Supprimer les espaces
    $iban = str_replace(' ', '', trim($iban));
    
    if (empty($iban)) {
        return "L'IBAN est requis";
    }
    
    // Vérification de base (longueur et format)
    if (!preg_match('/^[A-Z]{2}[0-9]{2}[A-Z0-9]{4,}$/', $iban)) {
        return "Le format de l'IBAN n'est pas valide";
    }
    
    // Pour une validation plus précise, on pourrait implémenter l'algorithme complet
    // de vérification des IBAN (MOD 97-10)
    
    return true;
}

/**
 * Valide le format d'un BIC
 * 
 * @param string $bic BIC à valider
 * @return bool|string True si valide, message d'erreur sinon
 */
function validateBIC($bic) {
    $bic = str_replace(' ', '', trim($bic));
    
    if (empty($bic)) {
        return "Le BIC est requis";
    }
    
    // Format BIC: 8 ou 11 caractères (lettres et chiffres)
    if (!preg_match('/^[A-Z]{6}[A-Z0-9]{2}([A-Z0-9]{3})?$/', $bic)) {
        return "Le format du BIC n'est pas valide";
    }
    
    return true;
}

/**
 * Valide un formulaire complet
 * 
 * @param array $data Données du formulaire
 * @param array $rules Règles de validation
 * @return array Tableau d'erreurs (vide si tout est valide)
 */
function validateForm($data, $rules) {
    $errors = [];
    
    foreach ($rules as $field => $validations) {
        // Si le champ n'existe pas et qu'il est requis
        if (!isset($data[$field]) && in_array('required', $validations)) {
            $errors[$field] = "Ce champ est requis";
            continue;
        }
        
        // Si le champ existe, on applique les validations
        if (isset($data[$field])) {
            $value = $data[$field];
            
            foreach ($validations as $validation) {
                // Si c'est une chaîne simple (nom de fonction)
                if (is_string($validation) && $validation !== 'required') {
                    $validationFunction = 'validate' . ucfirst($validation);
                    if (function_exists($validationFunction)) {
                        $result = $validationFunction($value);
                        if ($result !== true) {
                            $errors[$field] = $result;
                            break;
                        }
                    }
                }
                // Si c'est un tableau (fonction avec paramètres)
                else if (is_array($validation)) {
                    $validationFunction = 'validate' . ucfirst($validation[0]);
                    if (function_exists($validationFunction)) {
                        $params = array_merge([$value], array_slice($validation, 1));
                        $result = call_user_func_array($validationFunction, $params);
                        if ($result !== true) {
                            $errors[$field] = $result;
                            break;
                        }
                    }
                }
            }
        }
    }
    
    return $errors;
}

/**
 * Exemple d'utilisation de validateForm:
 * 
 * $rules = [
 *     'email' => ['required', 'email'],
 *     'password' => ['required', ['password', true]], // password avec validation stricte
 *     'montant' => ['required', ['amount', 10, 1000]], // montant entre 10 et 1000
 *     'date_naissance' => ['required', ['date', '1900-01-01', date('Y-m-d')]]
 * ];
 * 
 * $errors = validateForm($_POST, $rules);
 * if (empty($errors)) {
 *     // Formulaire valide
 * } else {
 *     // Afficher les erreurs
 * }
 */
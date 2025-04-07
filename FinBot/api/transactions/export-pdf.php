<?php
session_start();
require_once __DIR__ . '/../../classes/Database.php';
require_once __DIR__ . '/../../includes/auth_functions.php';
require_once __DIR__ . '/../../vendor/autoload.php'; // Nécessite TCPDF

if (!isset($_SESSION['user'])) {
    header('HTTP/1.1 401 Unauthorized');
    exit('Non autorisé');
}

try {
    // Récupération des transactions directement avec la classe Database
    $db = Database::getInstance();
    $transactions = $db->select(
        "SELECT t.date_transaction, t.type_transaction as type, t.description, t.montant, c.numero_compte 
         FROM transactions t 
         JOIN comptes c ON t.compte_id = c.id 
         WHERE c.utilisateur_id = ? 
         ORDER BY t.date_transaction DESC",
        [$_SESSION['user']['id']]
    );
    
    // Création du PDF
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Métadonnées
    $pdf->SetCreator('FinBot+');
    $pdf->SetAuthor('FinBot+');
    $pdf->SetTitle('Relevé de transactions');
    
    // En-tête et pied de page
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(true);
    
    // Police par défaut
    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
    $pdf->SetMargins(15, 15, 15);
    
    // Nouvelle page
    $pdf->AddPage();
    
    // Titre
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'Relevé de transactions', 0, 1, 'C');
    $pdf->Ln(5);
    
    // Informations client
    $pdf->SetFont('helvetica', '', 11);
    $pdf->Cell(0, 6, 'Client: ' . $_SESSION['user']['prenom'] . ' ' . $_SESSION['user']['nom'], 0, 1);
    $pdf->Cell(0, 6, 'Date d\'émission: ' . date('d/m/Y'), 0, 1);
    $pdf->Ln(5);
    
    // En-têtes du tableau
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->SetFillColor(240, 240, 240);
    $headers = ['Date', 'Compte', 'Type', 'Description', 'Montant'];
    $widths = [30, 35, 25, 60, 30];
    
    foreach ($headers as $i => $header) {
        $pdf->Cell($widths[$i], 7, $header, 1, 0, 'C', true);
    }
    $pdf->Ln();
    
    // Données
    $pdf->SetFont('helvetica', '', 10);
    foreach ($transactions as $transaction) {
        $pdf->Cell($widths[0], 6, date('d/m/Y H:i', strtotime($transaction['date_transaction'])), 1);
        $pdf->Cell($widths[1], 6, $transaction['numero_compte'], 1);
        $pdf->Cell($widths[2], 6, $transaction['type'], 1);
        $pdf->Cell($widths[3], 6, $transaction['description'], 1);
        
        // Colorisation des montants (positif en vert, négatif en rouge)
        $montant = number_format($transaction['montant'], 2, ',', ' ') . ' €';
        $pdf->SetTextColor($transaction['montant'] < 0 ? 255 : 0, $transaction['montant'] < 0 ? 0 : 128, 0);
        $pdf->Cell($widths[4], 6, $montant, 1, 0, 'R');
        $pdf->SetTextColor(0, 0, 0); // Reset couleur
        
        $pdf->Ln();
    }
    
    // Nom du fichier
    $filename = "transactions_" . date('Y-m-d_His') . ".pdf";
    
    // Envoi du PDF
    $pdf->Output($filename, 'D');

} catch (Exception $e) {
    error_log("Erreur export PDF: " . $e->getMessage());
    header('HTTP/1.1 500 Internal Server Error');
    exit('Erreur lors de l\'export: ' . $e->getMessage());
}
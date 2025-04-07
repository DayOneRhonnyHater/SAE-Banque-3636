// Scripts pour la page des journaux

// Fonction pour exporter les journaux
function exportLogs() {
    // Récupérer les filtres actuels
    const userId = document.getElementById('user_id').value;
    const actionType = document.getElementById('action').value;
    const dateFrom = document.getElementById('date_from').value;
    const dateTo = document.getElementById('date_to').value;
    
    // Construire l'URL d'exportation
    let exportUrl = '../api/admin/export-logs.php?format=csv';
    
    if (userId) exportUrl += `&user_id=${encodeURIComponent(userId)}`;
    if (actionType) exportUrl += `&action=${encodeURIComponent(actionType)}`;
    if (dateFrom) exportUrl += `&date_from=${encodeURIComponent(dateFrom)}`;
    if (dateTo) exportUrl += `&date_to=${encodeURIComponent(dateTo)}`;
    
    // Rediriger vers l'URL d'exportation
    window.location.href = exportUrl;
}
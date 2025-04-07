// Scripts pour les graphiques du tableau de bord

// Charger les graphiques lorsque la page est prête
document.addEventListener('DOMContentLoaded', function() {
    // Graphique des utilisateurs
    const usersCtx = document.getElementById('usersChart').getContext('2d');
    
    // Requête AJAX pour récupérer les données
    fetch('../api/admin/chart-data.php?type=users')
        .then(response => response.json())
        .then(data => {
            const usersChart = new Chart(usersCtx, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: 'Nouveaux utilisateurs',
                        data: data.values,
                        borderColor: '#4e73df',
                        backgroundColor: 'rgba(78, 115, 223, 0.05)',
                        pointBackgroundColor: '#4e73df',
                        pointBorderColor: '#4e73df',
                        pointHoverRadius: 3,
                        pointHoverBackgroundColor: '#2e59d9',
                        pointHoverBorderColor: '#2e59d9',
                        pointHitRadius: 10,
                        pointBorderWidth: 2,
                        fill: true
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    responsive: true,
                    scales: {
                        x: {
                            grid: {
                                display: false,
                                drawBorder: false
                            },
                            ticks: {
                                maxTicksLimit: 7
                            }
                        },
                        y: {
                            ticks: {
                                maxTicksLimit: 5,
                                padding: 10
                            },
                            grid: {
                                color: "rgb(234, 236, 244)",
                                drawBorder: false,
                                borderDash: [2],
                                zeroLineBorderDash: [2]
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        });
    
    // Graphique des transactions
    const transactionsCtx = document.getElementById('transactionsChart').getContext('2d');
    
    // Requête AJAX pour récupérer les données
    fetch('../api/admin/chart-data.php?type=transactions')
        .then(response => response.json())
        .then(data => {
            const transactionsChart = new Chart(transactionsCtx, {
                type: 'bar',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: 'Transactions',
                        data: data.values,
                        backgroundColor: '#36b9cc',
                        hoverBackgroundColor: '#2c9faf',
                        borderColor: '#36b9cc',
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    responsive: true,
                    scales: {
                        x: {
                            grid: {
                                display: false,
                                drawBorder: false
                            }
                        },
                        y: {
                            ticks: {
                                maxTicksLimit: 5,
                                padding: 10
                            },
                            grid: {
                                color: "rgb(234, 236, 244)",
                                drawBorder: false,
                                borderDash: [2],
                                zeroLineBorderDash: [2]
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        });
});
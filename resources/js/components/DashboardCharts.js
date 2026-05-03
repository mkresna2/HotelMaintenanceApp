import Chart from 'chart.js/auto';

/**
 * Dashboard Charts Component using Chart.js
 * Renders KPI widgets: MTTR, Compliance Rate, Complaint Trends
 * Supports responsive resizing and dynamic data updates
 */
export function initDashboardCharts() {
    const charts = {};

    // MTTR Trend Chart
    const mttrCtx = document.getElementById('mttr-chart');
    if (mttrCtx) {
        charts.mttr = new Chart(mttrCtx, {
            type: 'line',
            data: {
                labels: [], // Will be populated from API
                datasets: [{
                    label: 'Mean Time to Repair (Hours)',
                    data: [],
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `MTTR: ${context.parsed.y.toFixed(1)} hours`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Hours'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Date'
                        }
                    }
                }
            }
        });
    }

    // Compliance Rate Chart
    const complianceCtx = document.getElementById('compliance-chart');
    if (complianceCtx) {
        charts.compliance = new Chart(complianceCtx, {
            type: 'doughnut',
            data: {
                labels: ['Completed', 'Missed'],
                datasets: [{
                    data: [0, 0], // Will be populated from API
                    backgroundColor: [
                        'rgb(34, 197, 94)', // Green for completed
                        'rgb(239, 68, 68)'  // Red for missed
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.parsed / total) * 100).toFixed(1);
                                return `${context.label}: ${context.parsed} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }

    // Complaint Trends Chart
    const complaintsCtx = document.getElementById('complaints-chart');
    if (complaintsCtx) {
        charts.complaints = new Chart(complaintsCtx, {
            type: 'bar',
            data: {
                labels: [], // Will be populated from API
                datasets: [
                    {
                        label: 'Plumbing',
                        data: [],
                        backgroundColor: 'rgb(59, 130, 246)'
                    },
                    {
                        label: 'Electrical',
                        data: [],
                        backgroundColor: 'rgb(234, 179, 8)'
                    },
                    {
                        label: 'HVAC',
                        data: [],
                        backgroundColor: 'rgb(239, 68, 68)'
                    },
                    {
                        label: 'Other',
                        data: [],
                        backgroundColor: 'rgb(156, 163, 175)'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        stacked: true,
                        title: {
                            display: true,
                            text: 'Number of Complaints'
                        }
                    },
                    x: {
                        stacked: true,
                        title: {
                            display: true,
                            text: 'Date'
                        }
                    }
                }
            }
        });
    }

    // Asset Health Distribution Chart
    const assetHealthCtx = document.getElementById('asset-health-chart');
    if (assetHealthCtx) {
        charts.assetHealth = new Chart(assetHealthCtx, {
            type: 'pie',
            data: {
                labels: ['Excellent', 'Good', 'Fair', 'Poor', 'Critical'],
                datasets: [{
                    data: [0, 0, 0, 0, 0], // Will be populated from API
                    backgroundColor: [
                        'rgb(34, 197, 94)',  // Excellent - Green
                        'rgb(139, 195, 74)', // Good - Light Green
                        'rgb(234, 179, 8)',  // Fair - Yellow
                        'rgb(249, 115, 22)', // Poor - Orange
                        'rgb(239, 68, 68)'   // Critical - Red
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'right'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.parsed / total) * 100).toFixed(1);
                                return `${context.label}: ${context.parsed} assets (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }

    // Load data from API
    async function loadChartData() {
        try {
            const response = await fetch('/api/reports/dashboard-data', {
                headers: {
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) throw new Error('Failed to load chart data');

            const data = await response.json();

            // Update MTTR chart
            if (charts.mttr && data.mttr) {
                charts.mttr.data.labels = data.mttr.labels;
                charts.mttr.data.datasets[0].data = data.mttr.values;
                charts.mttr.update();
            }

            // Update Compliance chart
            if (charts.compliance && data.compliance) {
                charts.compliance.data.datasets[0].data = [
                    data.compliance.completed,
                    data.compliance.missed
                ];
                charts.compliance.update();
            }

            // Update Complaints chart
            if (charts.complaints && data.complaints) {
                charts.complaints.data.labels = data.complaints.labels;
                charts.complaints.datasets = data.complaints.categories;
                charts.complaints.update();
            }

            // Update Asset Health chart
            if (charts.assetHealth && data.assetHealth) {
                charts.assetHealth.data.datasets[0].data = [
                    data.assetHealth.excellent,
                    data.assetHealth.good,
                    data.assetHealth.fair,
                    data.assetHealth.poor,
                    data.assetHealth.critical
                ];
                charts.assetHealth.update();
            }

        } catch (error) {
            console.error('Error loading chart data:', error);
        }
    }

    // Handle window resize for responsive charts
    let resizeTimeout;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(() => {
            Object.values(charts).forEach(chart => {
                chart.resize();
            });
        }, 250);
    });

    // Initialize
    loadChartData();

    // Auto-refresh every 5 minutes
    setInterval(loadChartData, 5 * 60 * 1000);

    return charts;
}

// Auto-initialize if DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initDashboardCharts);
} else {
    initDashboardCharts();
}

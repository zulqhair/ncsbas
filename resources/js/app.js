import 'bootstrap';
import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

const dashboardColors = {
    primary: '#2457c5',
    secondary: '#6b7894',
    success: '#169b62',
    warning: '#e3a008',
    danger: '#d14343',
    purple: '#7656d6',
    softBlue: '#8fb2ff',
};

function renderDashboardCharts() {
    const data = window.ncsbasDashboardData;

    if (! data) {
        return;
    }

    const createChart = (id, config) => {
        const canvas = document.getElementById(id);

        if (canvas) {
            new Chart(canvas, config);
        }
    };

    createChart('assessmentStatusChart', {
        type: 'doughnut',
        data: {
            labels: data.statuses.labels,
            datasets: [{
                data: data.statuses.values,
                backgroundColor: [
                    dashboardColors.secondary,
                    dashboardColors.warning,
                    dashboardColors.primary,
                    dashboardColors.success,
                ],
                borderWidth: 0,
                hoverOffset: 8,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '68%',
            plugins: {
                legend: { position: 'bottom' },
            },
        },
    });

    createChart('maturityChart', {
        type: 'bar',
        data: {
            labels: data.maturity.labels,
            datasets: [{
                label: 'Assessments',
                data: data.maturity.values,
                backgroundColor: [
                    dashboardColors.secondary,
                    dashboardColors.warning,
                    dashboardColors.primary,
                    dashboardColors.success,
                ],
                borderRadius: 8,
                borderSkipped: false,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0 } },
                x: { grid: { display: false } },
            },
            plugins: { legend: { display: false } },
        },
    });

    createChart('assessmentScoresChart', {
        type: 'line',
        data: {
            labels: data.assessments.map((assessment) => assessment.label),
            datasets: [{
                label: 'Overall score (%)',
                data: data.assessments.map((assessment) => assessment.score),
                borderColor: dashboardColors.primary,
                backgroundColor: 'rgba(36, 87, 197, 0.14)',
                pointBackgroundColor: dashboardColors.primary,
                pointRadius: 5,
                pointHoverRadius: 7,
                fill: true,
                tension: 0.35,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true, max: 100, ticks: { callback: (value) => `${value}%` } },
                x: { grid: { display: false } },
            },
            plugins: {
                legend: { position: 'bottom' },
                tooltip: { callbacks: { label: (context) => `${context.parsed.y}%` } },
            },
        },
    });

    createChart('elementPerformanceChart', {
        type: 'bar',
        data: {
            labels: data.elements.labels,
            datasets: [{
                label: 'Average maturity score (%)',
                data: data.elements.values,
                backgroundColor: dashboardColors.softBlue,
                hoverBackgroundColor: dashboardColors.primary,
                borderRadius: 6,
                borderSkipped: false,
            }],
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    beginAtZero: true,
                    max: 100,
                    ticks: { callback: (value) => `${value}%` },
                },
                y: { grid: { display: false } },
            },
            plugins: {
                legend: { position: 'bottom' },
                tooltip: { callbacks: { label: (context) => `${context.parsed.x}%` } },
            },
        },
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', renderDashboardCharts);
} else {
    renderDashboardCharts();
}

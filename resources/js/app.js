import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);
Chart.defaults.font.family = "'IBM Plex Sans', Arial, sans-serif";
Chart.defaults.font.size = 14;
Chart.defaults.color = '#526071';
Chart.defaults.borderColor = '#e7ecf3';
Chart.defaults.animation = window.matchMedia('(prefers-reduced-motion: reduce)').matches ? false : { duration: 250 };

const dashboardColors = {
    primary: '#0b2e66',
    secondary: '#526071',
    success: '#16794b',
    warning: '#b86300',
    danger: '#b42318',

    softBlue: '#315d91',
};

function renderDashboardCharts() {
    const data = window.ncsbasDashboardData;

    if (! data) {
        return;
    }

    const createChart = (id, config) => {
        const canvas = document.getElementById(id);

        if (canvas) {
            const container = canvas.closest('[data-chart-container]');
            container.hidden = false;
            try {
                new Chart(canvas, config);
                container.parentElement.querySelector('.chart-data').open = false;
            } catch {
                container.hidden = true;
            }
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
                borderRadius: 3,
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
                backgroundColor: 'rgba(11, 46, 102, 0.06)',
                pointBackgroundColor: dashboardColors.primary,
                pointRadius: 5,
                pointHoverRadius: 7,
                fill: false,
                tension: 0,
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
            labels: data.elements.labels.map((label, index) => 'E' + (index + 1)),
            datasets: [{
                label: 'Average maturity score (%)',
                data: data.elements.values,
                backgroundColor: dashboardColors.softBlue,
                hoverBackgroundColor: dashboardColors.primary,
                borderRadius: 3,
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
                tooltip: { callbacks: { title: (items) => data.elements.labels[items[0].dataIndex], label: (context) => `${context.parsed.x}%` } },
            },
        },
    });
}

function setupNavigation() {
    const toggle = document.querySelector('[data-menu-toggle]');
    const nav = document.getElementById('primary-navigation');
    const desktop = window.matchMedia('(min-width: 1024px)');
    if (! toggle || ! nav) {
        return;
    }
    toggle.hidden = false;
    toggle.parentElement.classList.add('menu-enhanced');
    const setOpen = (open) => {
        nav.classList.toggle('is-open', open);
        toggle.setAttribute('aria-expanded', String(open || desktop.matches));
    };
    setOpen(false);
    toggle.addEventListener('click', () => setOpen(! nav.classList.contains('is-open')));
    nav.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && ! desktop.matches) {
            setOpen(false);
            toggle.focus();
        }
    });
    desktop.addEventListener('change', () => {
        if (! desktop.matches && nav.contains(document.activeElement)) {
            toggle.focus();
        }
        setOpen(false);
    });
}

function setupSequentialQuestionnaire() {
    const form = document.querySelector('[data-sequential-questionnaire]');
    const submitButton = document.querySelector('[data-submit-assessment]');
    const submissionForm = document.querySelector('[data-review-submission]');
    if (! form) {
        return;
    }
    const progress = document.querySelector('[data-submission-progress]');
    const saveState = document.querySelector('[data-save-state]');
    const guidance = document.querySelector('[data-submit-guidance]');
    const groups = Object.values([...form.querySelectorAll('[data-questionnaire-row]')].reduce((result, row) => {
        (result[row.dataset.element] ??= []).push(row);
        return result;
    }, {}));
    const serialize = () => JSON.stringify([...form.querySelectorAll('.questionnaire-input:checked')].map((input) => [input.name, input.value]));
    const original = serialize();
    let dirty = form.dataset.restoredInput === 'true';
    let complete = false;
    let saving = false;
    const refresh = () => {
        let completed = 0;
        groups.forEach((rows) => {
            let blockedReason = null;
            let elementComplete = true;
            rows.forEach((row) => {
                const inputs = [...row.querySelectorAll('.questionnaire-input')];
                const note = row.querySelector('[data-questionnaire-note]');
                const blocked = blockedReason !== null;
                inputs.forEach((input) => {
                    input.disabled = blocked;
                    if (blocked) {
                        input.checked = false;
                    }
                });
                row.classList.toggle('is-blocked', blocked);
                note.hidden = ! blocked;
                note.textContent = blocked ? (blockedReason === 'no' ? 'Skipped because an earlier question in this element was answered No.' : 'Answer the previous question Yes to continue.') : '';
                if (! blocked) {
                    const selected = row.querySelector('.questionnaire-input:checked')?.value;
                    if (selected === 'No') {
                        blockedReason = 'no';
                    } else if (! selected) {
                        blockedReason = 'incomplete';
                        elementComplete = false;
                    }
                }
            });
            if (elementComplete) {
                completed++;
            }
        });
        dirty = form.dataset.restoredInput === 'true' || serialize() !== original;
        complete = groups.length > 0 && completed === groups.length;
        saveState.textContent = dirty ? 'Unsaved changes' : 'No unsaved changes';
        progress.textContent = `${completed}/${groups.length} elements complete`;
        if (submitButton) {
            submitButton.disabled = ! complete || dirty;
        }
        if (guidance) {
            guidance.textContent = dirty ? 'Save your draft to include these changes in the review.' : (complete ? 'All elements are complete and saved. You can submit for review.' : 'Finish and save every element to enable submission.');
        }
    };
    window.addEventListener('pageshow', () => {
        saving = false;
        refresh();
    });
    form.addEventListener('change', refresh);
    form.addEventListener('submit', () => { saving = true; });
    submissionForm?.addEventListener('submit', (event) => {
        if (dirty || ! complete) {
            event.preventDefault();
            form.querySelector('button[type="submit"]').focus();
        }
    });
    window.addEventListener('beforeunload', (event) => {
        if (dirty && ! saving) {
            event.preventDefault();
            event.returnValue = '';
        }
    });
    refresh();
}

function setupForms() {
    document.querySelectorAll('[data-loading-form]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (event.defaultPrevented) {
                return;
            }
            if (form.getAttribute('aria-busy') === 'true') {
                event.preventDefault();
                return;
            }
            form.setAttribute('aria-busy', 'true');
            const button = event.submitter;
            if (button) {
                button.dataset.originalHtml = button.innerHTML;
                button.setAttribute('aria-disabled', 'true');
                button.textContent = button.dataset.loadingLabel || 'Please wait…';
            }
        });
    });
    window.addEventListener('pageshow', () => {
        document.querySelectorAll('form[aria-busy]').forEach((form) => form.removeAttribute('aria-busy'));
        document.querySelectorAll('[data-original-html]').forEach((button) => {
            button.innerHTML = button.dataset.originalHtml;
            button.removeAttribute('aria-disabled');
            delete button.dataset.originalHtml;
        });
    });
}

function initialize() {
    setupNavigation();
    setupSequentialQuestionnaire();
    setupForms();
    document.querySelectorAll('[data-element-link]').forEach((link) => {
        link.addEventListener('click', () => {
            const element = document.getElementById(link.hash.slice(1));
            if (element) {
                element.open = true;
                element.querySelector('summary').focus({ preventScroll: true });
            }
        });
    });
    document.querySelector('[data-error-summary]')?.focus();
    document.fonts.ready.then(renderDashboardCharts);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initialize);
} else {
    initialize();
}

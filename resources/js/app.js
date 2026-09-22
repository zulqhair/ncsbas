import * as echarts from 'echarts/core';
import { BarChart, LineChart, PieChart, RadarChart } from 'echarts/charts';
import { AriaComponent, GridComponent, LegendComponent, RadarComponent, TooltipComponent } from 'echarts/components';
import { CanvasRenderer } from 'echarts/renderers';

echarts.use([
    AriaComponent,
    BarChart,
    CanvasRenderer,
    GridComponent,
    LegendComponent,
    LineChart,
    PieChart,
    RadarChart,
    RadarComponent,
    TooltipComponent,
]);

const dashboardColors = {
    primary: '#0b2e66',
    secondary: '#526071',
    success: '#16794b',
    warning: '#b86300',
    danger: '#b42318',

    softBlue: '#315d91',
};

const chartFontFamily = "'IBM Plex Sans', Arial, sans-serif";
const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const chartInstances = new Map();

function escapeHtml(value) {
    return String(value).replace(/[&<>"']/g, (character) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;',
    }[character]));
}

function wrapChartLabel(label) {
    const maximumLineLength = 14;
    const lines = [];
    let line = '';

    String(label).split(' ').forEach((word) => {
        const nextLine = line === '' ? word : line+' '+word;

        if (nextLine.length > maximumLineLength && line !== '') {
            lines.push(line);
            line = word;

            return;
        }

        line = nextLine;
    });

    if (line !== '') {
        lines.push(line);
    }

    return lines.join('\n');
}

function createChart(id, option, containerSelector) {
    const element = document.getElementById(id);

    if (! element) {
        return;
    }

    const container = element.closest(containerSelector);

    if (! container) {
        return;
    }

    container.hidden = false;
    chartInstances.get(id)?.dispose();

    try {
        const chart = echarts.init(element, null, { renderer: 'canvas' });

        chart.setOption({
            animation: ! reducedMotion,
            aria: { enabled: true },
            textStyle: {
                color: '#526071',
                fontFamily: chartFontFamily,
            },
            ...option,
        });

        chartInstances.set(id, chart);
        container.parentElement.querySelector('.chart-data')?.removeAttribute('open');
    } catch {
        container.hidden = true;
    }
}

window.addEventListener('resize', () => {
    chartInstances.forEach((chart) => chart.resize());
});

function renderDashboardCharts() {
    const data = window.ncsbasDashboardData;

    if (! data) {
        return;
    }

    createChart('assessmentStatusChart', {
        color: [
            dashboardColors.secondary,
            dashboardColors.warning,
            dashboardColors.primary,
            dashboardColors.success,
        ],
        tooltip: {
            trigger: 'item',
            valueFormatter: (value) => String(value),
        },
        legend: {
            bottom: 0,
            type: 'scroll',
        },
        series: [{
            type: 'pie',
            radius: ['50%', '68%'],
            avoidLabelOverlap: true,
            label: { show: false },
            labelLine: { show: false },
            data: data.statuses.labels.map((label, index) => ({
                name: label,
                value: data.statuses.values[index],
            })),
        }],
    }, '[data-chart-container]');

    createChart('maturityChart', {
        color: [
            dashboardColors.secondary,
            dashboardColors.warning,
            dashboardColors.primary,
            dashboardColors.success,
        ],
        grid: {
            top: 16,
            right: 16,
            bottom: 48,
            left: 44,
        },
        tooltip: {
            trigger: 'axis',
            axisPointer: { type: 'shadow' },
        },
        xAxis: {
            type: 'category',
            data: data.maturity.labels,
            axisTick: { show: false },
            axisLine: { lineStyle: { color: '#e7ecf3' } },
        },
        yAxis: {
            type: 'value',
            minInterval: 1,
            splitLine: { lineStyle: { color: '#e7ecf3' } },
        },
        series: [{
            name: 'Assessments',
            type: 'bar',
            data: data.maturity.values,
            barMaxWidth: 44,
            itemStyle: {
                borderRadius: [3, 3, 0, 0],
            },
        }],
    }, '[data-chart-container]');

    createChart('assessmentScoresChart', {
        color: [dashboardColors.primary],
        grid: {
            top: 24,
            right: 20,
            bottom: 48,
            left: 52,
        },
        tooltip: {
            trigger: 'axis',
            valueFormatter: (value) => value+'%',
        },
        xAxis: {
            type: 'category',
            boundaryGap: false,
            data: data.assessments.map((assessment) => assessment.label),
            axisTick: { show: false },
            axisLine: { lineStyle: { color: '#e7ecf3' } },
        },
        yAxis: {
            type: 'value',
            min: 0,
            max: 100,
            axisLabel: { formatter: '{value}%' },
            splitLine: { lineStyle: { color: '#e7ecf3' } },
        },
        series: [{
            name: 'Overall score',
            type: 'line',
            data: data.assessments.map((assessment) => assessment.score),
            symbolSize: 9,
            lineStyle: { width: 2 },
            itemStyle: { color: dashboardColors.primary },
        }],
    }, '[data-chart-container]');

    createChart('elementPerformanceChart', {
        color: [dashboardColors.softBlue],
        grid: {
            top: 20,
            right: 48,
            bottom: 24,
            left: 300,
        },
        tooltip: {
            trigger: 'axis',
            axisPointer: { type: 'shadow' },
            valueFormatter: (value) => value+'%',
        },
        xAxis: {
            type: 'value',
            min: 0,
            max: 100,
            axisLabel: { formatter: '{value}%' },
            splitLine: { lineStyle: { color: '#e7ecf3' } },
        },
        yAxis: {
            type: 'category',
            inverse: true,
            data: data.elements.labels,
            axisTick: { show: false },
            axisLine: { show: false },
            axisLabel: {
                width: 280,
                overflow: 'truncate',
            },
        },
        series: [{
            name: 'Average maturity score',
            type: 'bar',
            data: data.elements.values,
            barMaxWidth: 20,
            itemStyle: { borderRadius: [0, 3, 3, 0] },
        }],
    }, '[data-chart-container]');
}

function renderReviewChart() {
    const data = window.ncsbasReviewData;

    if (! data) {
        return;
    }

    const domainPointSeries = data.domains.map((domain, index) => ({
        name: domain.name,
        type: 'radar',
        symbol: 'circle',
        symbolSize: 10,
        lineStyle: { opacity: 0 },
        areaStyle: { opacity: 0 },
        data: [{
            value: data.domains.map((item, pointIndex) => pointIndex === index ? item.score : '-'),
            domainIndex: index,
        }],
    }));

    createChart('reviewDomainRadarChart', {
        color: [dashboardColors.primary],
        tooltip: {
            trigger: 'item',
            formatter: (params) => {
                const index = params.data.domainIndex;

                if (index === undefined) {
                    return 'Move over a point to see its domain details.';
                }

                const domain = data.domains[index];

                return '<strong>'+escapeHtml(domain.name)+'</strong>'
                    +'<br>Average maturity: '+domain.score+'/3'
                    +'<br>Elements: '+domain.elementCount;
            },
        },
        radar: {
            center: ['50%', '51%'],
            radius: '62%',
            splitNumber: 3,
            axisName: {
                color: '#526071',
                fontFamily: chartFontFamily,
                fontSize: 12,
            },
            splitLine: { lineStyle: { color: '#e7ecf3' } },
            splitArea: { areaStyle: { color: ['#ffffff', '#fafbfd'] } },
            axisLine: { lineStyle: { color: '#e7ecf3' } },
            indicator: data.domains.map((domain) => ({
                name: domain.name,
                max: 3,
            })),
        },
        series: [{
            name: 'Maturity score',
            type: 'radar',
            symbol: 'none',
            lineStyle: {
                color: dashboardColors.primary,
                width: 2,
            },
            areaStyle: {
                color: 'rgba(11, 46, 102, 0.14)',
            },
            data: [{ value: data.domains.map((domain) => domain.score) }],
        }, ...domainPointSeries],
    }, '[data-review-domain-chart-container]');

    createChart('reviewMaturityDistributionChart', {
        color: [
            dashboardColors.secondary,
            dashboardColors.warning,
            dashboardColors.softBlue,
            dashboardColors.success,
        ],
        tooltip: {
            trigger: 'item',
            formatter: (params) => '<strong>'+escapeHtml(params.name)+'</strong><br>'+params.value+' elements ('+params.percent+'%)',
        },
        legend: {
            bottom: 0,
            type: 'scroll',
        },
        series: [{
            type: 'pie',
            radius: ['50%', '68%'],
            avoidLabelOverlap: true,
            label: { show: false },
            labelLine: { show: false },
            data: data.maturityDistribution.map((level) => ({
                name: level.name,
                value: level.count,
            })),
        }],
    }, '[data-review-distribution-chart-container]');

    createChart('reviewLowestElementsChart', {
        color: [dashboardColors.warning],
        grid: {
            top: 16,
            right: 16,
            bottom: 82,
            left: 44,
        },
        tooltip: {
            trigger: 'item',
            formatter: (params) => {
                const element = data.lowestElements[params.dataIndex];

                return '<strong>Element '+element.number+': '+escapeHtml(element.name)+'</strong>'
                    +'<br>Maturity: '+escapeHtml(element.maturityLevel)+' ('+element.score+'/3)'
                    +'<br>Yes responses: '+element.yesCount;
            },
        },
        xAxis: {
            type: 'category',
            data: data.lowestElements.map((element) => element.name),
            axisTick: { show: false },
            axisLine: { lineStyle: { color: '#e7ecf3' } },
            axisLabel: {
                formatter: wrapChartLabel,
                fontSize: 10,
                lineHeight: 12,
            },
        },
        yAxis: {
            type: 'value',
            min: 0,
            max: 3,
            interval: 1,
            axisLabel: { formatter: '{value}' },
            splitLine: { lineStyle: { color: '#e7ecf3' } },
        },
        series: [{
            name: 'Maturity score',
            type: 'bar',
            data: data.lowestElements.map((element) => element.score),
            barMaxWidth: 42,
            itemStyle: { borderRadius: [3, 3, 0, 0] },
        }],
    }, '[data-review-lowest-chart-container]');

    const elements = [...data.elements].sort((first, second) => first.number - second.number);

    createChart('reviewElementMaturityChart', {
        color: [dashboardColors.softBlue],
        grid: {
            top: 20,
            right: 48,
            bottom: 24,
            left: 320,
        },
        tooltip: {
            trigger: 'item',
            formatter: (params) => {
                const element = elements[params.dataIndex];

                return '<strong>Element '+element.number+': '+escapeHtml(element.name)+'</strong>'
                    +'<br>Maturity: '+escapeHtml(element.maturityLevel)+' ('+element.score+'/3)'
                    +'<br>Yes responses: '+element.yesCount;
            },
        },
        xAxis: {
            type: 'value',
            min: 0,
            max: 3,
            interval: 1,
            axisLabel: { formatter: '{value}' },
            splitLine: { lineStyle: { color: '#e7ecf3' } },
        },
        yAxis: {
            type: 'category',
            inverse: true,
            data: elements.map((element) => element.name),
            axisTick: { show: false },
            axisLine: { show: false },
            axisLabel: {
                width: 300,
                overflow: 'truncate',
            },
        },
        series: [{
            name: 'Maturity score',
            type: 'bar',
            data: elements.map((element) => element.score),
            barMaxWidth: 20,
            itemStyle: { borderRadius: [0, 3, 3, 0] },
        }],
    }, '[data-review-element-chart-container]');
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
    document.fonts.ready.then(() => {
        renderDashboardCharts();
        renderReviewChart();
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initialize);
} else {
    initialize();
}

function initLaporanKesehatanBankPage() {
    const dateFilter = document.getElementById('filter-date');
    const filterBtn = document.getElementById('btn-filter-health');
    const container = document.getElementById('rasio-panel');
    const compareSelect = document.getElementById('filter-compare');
    const template = document.getElementById('ratio-card-template');

    window.healthCharts = window.healthCharts || {}; // To store chart instances

    if (!container) return;

    // Set default date to today
    if (typeof flatpickr !== 'undefined') {
        flatpickr(dateFilter, { dateFormat: "Y-m-d", defaultDate: "today", allowInput: true });
    } else {
        dateFilter.valueAsDate = new Date();
    }

    // Tab switching logic
    const tabs = document.querySelectorAll('.health-tab-btn');
    const tabPanes = document.querySelectorAll('.health-tab-pane');
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            tabs.forEach(t => t.classList.remove('border-primary', 'text-primary', 'dark:text-primary', 'dark:border-primary'));
            tabPanes.forEach(p => p.classList.add('hidden'));

            tab.classList.add('border-primary', 'text-primary', 'dark:text-primary', 'dark:border-primary');
            const targetPane = document.querySelector(tab.dataset.tabsTarget);
            if (targetPane) targetPane.classList.remove('hidden');
        });
    });
    // Set default active tab
    if (tabs.length > 0) {
        tabs[0].classList.add('border-primary', 'text-primary', 'dark:text-primary', 'dark:border-primary');
        tabPanes[0].classList.remove('hidden');
    }

    const ratioDefinitions = {
        roa: { name: 'Return on Assets (ROA)', formula: '(Laba Bersih / Total Aset) x 100%', icon: 'bi-graph-up-arrow', color: 'blue', desc: 'Efisiensi aset menghasilkan laba.', format: (v) => `${v.toFixed(2)}%`, interpret: (v) => v > 1.5 ? 'Sangat Baik' : (v > 0.5 ? 'Baik' : 'Perlu Perhatian') },
        roe: { name: 'Return on Equity (ROE)', formula: '(Laba Bersih / Total Ekuitas) x 100%', icon: 'bi-reception-4', color: 'blue', desc: 'Imbal hasil atas modal sendiri.', format: (v) => `${v.toFixed(2)}%`, interpret: (v) => v > 10 ? 'Sangat Baik' : (v > 5 ? 'Baik' : 'Perlu Perhatian') },
        nim: { name: 'Net Interest Margin (NIM)', formula: '(Pendapatan Bunga Net / Aset Produktif) x 100%', icon: 'bi-percent', color: 'blue', desc: 'Selisih bunga pinjaman & simpanan.', format: (v) => `${v.toFixed(2)}%`, interpret: (v) => v > 5 ? 'Sangat Baik' : (v > 3 ? 'Baik' : 'Tipis') },
        bopo: { name: 'BOPO', formula: '(Total Beban / Total Pendapatan) x 100%', icon: 'bi-receipt-cutoff', color: 'blue', desc: 'Efisiensi biaya operasional.', format: (v) => `${v.toFixed(2)}%`, interpret: (v) => v < 94 ? 'Efisien' : 'Kurang Efisien', inverse: true, threshold: 94 },
        ldr: { name: 'Loan to Deposit Ratio (LDR)', formula: '(Total Pinjaman / Total Simpanan) x 100%', icon: 'bi-bank', color: 'green', desc: 'Rasio pinjaman terhadap simpanan.', format: (v) => `${v.toFixed(2)}%`, interpret: (v) => v > 78 && v < 92 ? 'Ideal' : 'Kurang Ideal', threshold: 92 },
        car: { name: 'Capital Adequacy Ratio (CAR)', formula: '(Total Ekuitas / Total Aset) x 100%', icon: 'bi-shield-check', color: 'purple', desc: 'Kecukupan modal terhadap risiko.', format: (v) => `${v.toFixed(2)}%`, interpret: (v) => v > 12 ? 'Kuat' : 'Perlu Diperkuat', threshold: 12 },
        npl: { name: 'Non-Performing Loan (NPL)', formula: '(Total Kredit Macet / Total Pinjaman) x 100%', icon: 'bi-exclamation-triangle', color: 'red', desc: 'Rasio kredit macet.', format: (v) => `${v.toFixed(2)}%`, interpret: (v) => v < 5 ? 'Sehat' : 'Berisiko', inverse: true, threshold: 5 },
    };

    async function loadReport() {
        const date = dateFilter.value;
        const compareType = compareSelect.value;
        const dupontPanel = document.getElementById('dupont-panel');
        
        const loadingHtml = `
            <div class="text-center py-16">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary mx-auto"></div>
                <p class="mt-4 text-gray-500">Memuat data analisis...</p>
            </div>
        `;
        container.innerHTML = loadingHtml;
        dupontPanel.innerHTML = loadingHtml;

        try {
            const params = new URLSearchParams({
                date: date,
                compare_type: compareType
            });
            const response = await fetch(`${basePath}/api/laporan_kesehatan_bank_handler.php?${params.toString()}`);
            const result = await response.json();

            if (!result.success) throw new Error(result.message);

            renderReport(result.data);
            renderDuPontAnalysis(result.data);

        } catch (error) {
            container.innerHTML = `<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative text-center">${error.message}</div>`;
        }
    }

    function renderReport(data) {
        container.innerHTML = ''; // Clear loading

        const { current, previous, historical_trends } = data;
        const sections = {
            'Rentabilitas': { data: current.rentabilitas, prevData: previous.rentabilitas, icon: 'bi-cash-coin', color: 'blue' },
            'Likuiditas': { data: current.likuiditas, prevData: previous.likuiditas, icon: 'bi-droplet-half', color: 'green' },
            'Permodalan': { data: current.permodalan, prevData: previous.permodalan, icon: 'bi-bank2', color: 'purple' },
            'Kualitas Aset': { data: current.kualitas_aset, prevData: previous.kualitas_aset, icon: 'bi-gem', color: 'red' },
        };

        for (const [title, section] of Object.entries(sections)) {
            const sectionDiv = document.createElement('div');
            sectionDiv.innerHTML = `
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-lg bg-${section.color}-50 dark:bg-${section.color}-900/30 text-${section.color}-600 dark:text-${section.color}-400 flex items-center justify-center">
                        <i class="bi ${section.icon} text-xl"></i>
                    </div>
                    <h2 class="text-xl font-bold text-gray-800 dark:text-white">${title}</h2>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6"></div>
            `;
            
            const grid = sectionDiv.querySelector('.grid');
            for (const [key, value] of Object.entries(section.data)) {
                if (ratioDefinitions[key]) {
                    const prevValue = section.prevData ? section.prevData[key] : null;
                    grid.appendChild(createRatioCard(key, value, prevValue, historical_trends));
                }
            }
            container.appendChild(sectionDiv);
        }
    }

    function createRatioCard(key, value, previousValue, historicalData) {
        const def = ratioDefinitions[key];
        const card = template.content.cloneNode(true).firstElementChild;

        card.querySelector('.ratio-name').textContent = def.name;
        card.querySelector('.ratio-description').textContent = def.desc;
        card.querySelector('.ratio-value').textContent = def.format(value);

        const iconContainer = card.querySelector('.ratio-icon-container');
        const icon = card.querySelector('.ratio-icon');
        iconContainer.classList.add(`bg-${def.color}-50`, `dark:bg-${def.color}-900/30`);
        iconContainer.setAttribute('title', `Rumus: ${def.formula}`); // Add formula to tooltip
        icon.classList.add(`bi-${def.icon}`, `text-${def.color}-600`, `dark:text-${def.color}-400`);

        const interpretation = def.interpret(value);
        const interpretationEl = card.querySelector('.ratio-interpretation');
        interpretationEl.textContent = interpretation;

        // Handle comparison badge
        if (previousValue !== null && previousValue !== undefined) {
            const comparisonEl = card.querySelector('.ratio-comparison');
            updateComparisonBadge(comparisonEl, value, previousValue, def.inverse);
        }

        let colorClass = '';
        if (def.inverse) { // Lower is better
            if (interpretation === 'Sehat' || interpretation === 'Efisien') {
                colorClass = 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300';
            } else {
                colorClass = 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300';
            }
        } else { // Higher is better
            if (interpretation.includes('Baik') || interpretation.includes('Sehat') || interpretation.includes('Ideal') || interpretation.includes('Kuat') || interpretation.includes('Efisien')) {
                colorClass = 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300';
            } else if (interpretation.includes('Waspada') || interpretation.includes('Tipis')) {
                colorClass = 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300';
            } else {
                colorClass = 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300';
            }
        }
        interpretationEl.classList.add(...colorClass.split(' '));

        // New part for sparkline
        const canvas = card.querySelector('.ratio-sparkline');
        if (canvas && historicalData && historicalData[key]) {
            renderSparkline(canvas, historicalData.labels, historicalData[key], def);
        }

        return card;
    }

    function renderDuPontAnalysis(data) {
        const dupontPanel = document.getElementById('dupont-panel');
        const { current, previous } = data;

        const createComponentCard = (title, value, prevValue, formatFn, isInverse = false) => {
            const change = value - prevValue;
            const isPositive = change > 0;
            const isGood = isInverse ? !isPositive : isPositive;
            const colorClass = isGood ? 'text-green-600' : 'text-red-600';
            const icon = isPositive ? 'bi-arrow-up' : 'bi-arrow-down';
            
            let comparisonHtml = '';
            if (prevValue !== null && Math.abs(change) > 0.001) {
                comparisonHtml = `<div class="text-xs mt-1 flex items-center justify-center ${colorClass}"><i class="bi ${icon} mr-1"></i> ${Math.abs(change).toFixed(4)}</div>`;
            }

            return `
                <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 text-center">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">${title}</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">${formatFn(value)}</p>
                    ${comparisonHtml}
                </div>
            `;
        };

        const html = `
            <div class="p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl mb-6">
                <h3 class="text-lg font-semibold text-blue-800 dark:text-blue-200">Apa itu Analisis DuPont?</h3>
                <p class="text-sm text-blue-700 dark:text-blue-300 mt-1">
                    Analisis DuPont adalah kerangka kerja yang memecah Return on Equity (ROE) menjadi tiga komponen: Profitabilitas, Efisiensi Aset, dan Leverage Keuangan. Ini membantu mengidentifikasi kekuatan dan kelemahan utama dalam kinerja keuangan.
                </p>
            </div>
            <div class="flex flex-col lg:flex-row items-center justify-center gap-4 text-2xl font-bold text-gray-500 dark:text-gray-400">
                ${createComponentCard('Profit Margin', current.dupont.profit_margin, previous.dupont.profit_margin, (v) => `${(v * 100).toFixed(2)}%`)}
                <span>×</span>
                ${createComponentCard('Asset Turnover', current.dupont.asset_turnover, previous.dupont.asset_turnover, (v) => `${v.toFixed(2)}x`)}
                <span>×</span>
                ${createComponentCard('Financial Leverage', current.dupont.financial_leverage, previous.dupont.financial_leverage, (v) => `${v.toFixed(2)}x`, true)}
                <span>=</span>
                ${createComponentCard('Return on Equity (ROE)', current.rentabilitas.roe / 100, previous.rentabilitas.roe / 100, (v) => `${(v * 100).toFixed(2)}%`)}
            </div>
        `;

        dupontPanel.innerHTML = html;
    }

    function updateComparisonBadge(element, currentValue, previousValue, isInverse) {
        if (element === null) return;

        const change = currentValue - previousValue;

        if (Math.abs(change) < 0.01) {
            element.classList.add('hidden');
            return;
        }

        const isPositive = change > 0;
        // Logic: Usually positive growth is good (green), negative is bad (red).
        // isInverse: Positive growth (e.g. NPL) is bad (red), negative is good (green).
        const isGood = isInverse ? !isPositive : isPositive;

        const colorClass = isGood ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300';
        const icon = isPositive ? 'bi-arrow-up' : 'bi-arrow-down';
        const displayValue = Math.abs(change).toFixed(2);

        element.className = `inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-xs font-bold ${colorClass}`;
        element.innerHTML = `<i class="bi ${icon}"></i> ${displayValue}%`;
        element.classList.remove('hidden');
    }

    function renderSparkline(canvas, labels, data, definition) {
        const key = `sparkline-${Math.random()}`; // Give it a unique key
        if (window.healthCharts[key]) {
            window.healthCharts[key].destroy();
        }

        const chartColor = {
            blue: '#3b82f6',
            green: '#10b981',
            purple: '#8b5cf6',
            red: '#ef4444',
        }[definition.color] || '#6b7280';

        const hexToRgb = (hex) => {
            let result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
            return result ? `${parseInt(result[1], 16)}, ${parseInt(result[2], 16)}, ${parseInt(result[3], 16)}` : '0,0,0';
        };

        // Defer chart initialization to the next event loop tick
        setTimeout(() => {
            if (!canvas.isConnected) return; // Safeguard if element is removed before timeout

            // Annotation configuration
            const annotationOptions = {};
            if (definition.threshold !== undefined) {
                annotationOptions.annotations = {
                    thresholdLine: {
                        type: 'line',
                        yMin: definition.threshold,
                        yMax: definition.threshold,
                        borderColor: 'rgba(239, 68, 68, 0.6)', // Red-500
                        borderWidth: 1,
                        borderDash: [4, 4],
                        label: {
                            content: `Batas: ${definition.threshold}%`,
                            enabled: true,
                            position: 'end',
                            font: { size: 9 },
                            backgroundColor: 'rgba(239, 68, 68, 0.6)',
                            padding: 2,
                            yAdjust: -10
                        }
                    }
                };
            }

            window.healthCharts[key] = new Chart(canvas, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        borderColor: chartColor,
                        borderWidth: 2,
                        pointRadius: 0,
                        tension: 0.4,
                        fill: {
                            target: 'origin',
                            above: `rgba(${hexToRgb(chartColor)}, 0.1)`,
                            below: `rgba(${hexToRgb(chartColor)}, 0.1)`
                        }
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: { enabled: false },
                        annotation: annotationOptions
                    },
                    scales: {
                        x: { display: false },
                        y: { display: false }
                    }
                }
            });
        }, 0);
    }

    filterBtn.addEventListener('click', loadReport);
    loadReport(); // Initial load
}
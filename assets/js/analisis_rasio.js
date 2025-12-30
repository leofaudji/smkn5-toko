function initAnalisisRasioPage() {
    const dateInput = document.getElementById('ra-tanggal-akhir');
    const compareDateInput = document.getElementById('ra-tanggal-pembanding');
    const analyzeBtn = document.getElementById('ra-tampilkan-btn');
    const contentContainer = document.getElementById('ratio-analysis-content');
    const cardTemplate = document.getElementById('ratio-card-template');
    const exportPdfBtn = document.getElementById('export-ra-pdf');

    if (!analyzeBtn) return;

    const commonOptions = { dateFormat: "d-m-Y", allowInput: true };
    const datePicker = flatpickr(dateInput, commonOptions);
    const compareDatePicker = flatpickr(compareDateInput, commonOptions);

    // Set default dates
    const today = new Date();
    datePicker.setDate(today, true);
    const lastMonth = new Date(new Date().setMonth(today.getMonth() - 1));
    compareDatePicker.setDate(lastMonth, true);

    const ratioDefinitions = {
        profit_margin: {
            name: 'Profit Margin',
            formula: '(Laba Bersih / Total Pendapatan) * 100%',
            description: 'Mengukur seberapa besar laba bersih yang dihasilkan dari setiap rupiah pendapatan. Semakin tinggi, semakin baik.',
            format: (val) => val.toFixed(2),
        interpret: (val) => val > 0.1 ? 'Sehat' : (val > 0.05 ? 'Waspada' : 'Berisiko'),
        color: (val) => val > 0.1 ? 'text-green-600 dark:text-green-400' : (val > 0.05 ? 'text-yellow-500 dark:text-yellow-400' : 'text-red-600 dark:text-red-400'),
        },
        debt_to_equity: {
            name: 'Debt to Equity Ratio',
            formula: 'Total Liabilitas / Total Ekuitas',
            description: 'Mengukur proporsi pembiayaan perusahaan antara utang dan modal sendiri. Semakin rendah, semakin aman posisi keuangan perusahaan.',
            format: (val) => val.toFixed(2),
            interpret: (val) => val < 1 ? 'Sehat' : (val < 2 ? 'Waspada' : 'Berisiko Tinggi'),
        color: (val) => val < 1 ? 'text-green-600 dark:text-green-400' : (val < 2 ? 'text-yellow-500 dark:text-yellow-400' : 'text-red-600 dark:text-red-400'),
        },
        debt_to_asset: {
            name: 'Debt to Asset Ratio',
            formula: 'Total Liabilitas / Total Aset',
            description: 'Mengukur seberapa besar aset perusahaan yang dibiayai oleh utang. Semakin rendah, semakin baik.',
            format: (val) => val.toFixed(2),
        interpret: (val) => val < 0.4 ? 'Sangat Sehat' : (val < 0.6 ? 'Waspada' : 'Berisiko'),
        color: (val) => val < 0.4 ? 'text-green-600 dark:text-green-400' : (val < 0.6 ? 'text-yellow-500 dark:text-yellow-400' : 'text-red-600 dark:text-red-400'),
        },
        return_on_equity: {
            name: 'Return on Equity (ROE)',
            formula: '(Laba Bersih / Total Ekuitas) * 100%',
            description: 'Mengukur kemampuan perusahaan menghasilkan laba dari modal yang diinvestasikan oleh pemilik/anggota. Semakin tinggi, semakin efisien penggunaan modal.',
            format: (val) => `${(val * 100).toFixed(2)}%`,
            interpret: (val) => val > 0.15 ? 'Sangat Baik' : (val > 0.05 ? 'Baik' : 'Kurang Efisien'),
        color: (val) => val > 0.15 ? 'text-green-600 dark:text-green-400' : (val > 0.05 ? 'text-yellow-500 dark:text-yellow-400' : 'text-red-600 dark:text-red-400'),
        },
        return_on_assets: {
            name: 'Return on Assets (ROA)',
            formula: '(Laba Bersih / Total Aset) * 100%',
            description: 'Mengukur efisiensi perusahaan dalam menggunakan asetnya untuk menghasilkan laba. Semakin tinggi, semakin baik.',
            format: (val) => `${(val * 100).toFixed(2)}%`,
            interpret: (val) => val > 0.1 ? 'Sangat Efisien' : (val > 0.05 ? 'Efisien' : 'Kurang Efisien'),
        color: (val) => val > 0.1 ? 'text-green-600 dark:text-green-400' : (val > 0.05 ? 'text-yellow-500 dark:text-yellow-400' : 'text-red-600 dark:text-red-400'),
        },
        asset_turnover: {
            name: 'Asset Turnover Ratio',
            formula: 'Total Pendapatan / Total Aset',
            description: 'Mengukur efisiensi penggunaan aset untuk menghasilkan pendapatan. Semakin tinggi, semakin efisien.',
            format: (val) => val.toFixed(2) + 'x',
            interpret: (val) => val > 1.5 ? 'Sangat Efisien' : (val > 1 ? 'Efisien' : 'Kurang Efisien'),
        color: (val) => val > 1.5 ? 'text-green-600 dark:text-green-400' : (val > 1 ? 'text-yellow-500 dark:text-yellow-400' : 'text-red-600 dark:text-red-400'),
        }
    };

    async function runAnalysis() {
        const date = dateInput.value.split('-').reverse().join('-');
        const compareDate = compareDateInput.value ? compareDateInput.value.split('-').reverse().join('-') : '';

        if (!date) {
            showToast('Tanggal analisis wajib diisi.', 'error');
            return;
        }

        const originalBtnHtml = analyzeBtn.innerHTML;
        analyzeBtn.disabled = true;
        analyzeBtn.innerHTML = `<svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Menganalisis...`;
        contentContainer.innerHTML = '<div class="text-center p-10"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto"></div></div>';

        try {
            const params = new URLSearchParams({ date });
            if (compareDate) {
                params.append('compare_date', compareDate);
            }
            const response = await fetch(`${basePath}/api/analisis-rasio?${params.toString()}`);
            const result = await response.json();
            if (result.status !== 'success') throw new Error(result.message);

            const { current, previous } = result.data;
            contentContainer.innerHTML = '<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"></div>';
            const gridContainer = contentContainer.querySelector('.grid');

            for (const key in current) {
                if (ratioDefinitions[key]) {
                    const def = ratioDefinitions[key];
                    const cardContent = cardTemplate.content.cloneNode(true);
                    const card = cardContent.firstElementChild;
                    
                    card.querySelector('.ratio-name').textContent = def.name;
                    card.querySelector('[title]').setAttribute('title', def.description);
                    card.querySelector('.ratio-value').textContent = def.format(current[key] || 0);
                    card.querySelector('.ratio-formula').textContent = `Rumus: ${def.formula}`;
                    
                    const interpretationEl = card.querySelector('.ratio-interpretation');
                    interpretationEl.innerHTML = `<span class="font-semibold">Interpretasi:</span> <span class="${def.color(current[key] || 0)} ml-2">${def.interpret(current[key] || 0)}</span>`;

                    if (previous && previous[key] !== null && previous[key] !== undefined) {
                        const change = current[key] - previous[key];
                        const changeIcon = change >= 0 ? '<i class="bi bi-arrow-up"></i>' : '<i class="bi bi-arrow-down"></i>';
                        const changeColor = change >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400';
                        const changeValue = Math.abs(change * (def.format(1).includes('%') ? 100 : 1)).toFixed(2);
                        card.querySelector('.ratio-comparison').innerHTML = `${changeIcon} <span class="${changeColor} ml-1">${changeValue}${def.format(1).includes('%') ? '%' : ''}</span> vs periode sebelumnya`;
                    } else {
                        card.querySelector('.ratio-comparison').textContent = 'Tidak ada data pembanding.';
                    }

                    gridContainer.appendChild(card);
                }
            }
        } catch (error) {
            contentContainer.innerHTML = `<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">${error.message}</div>`;
        } finally {
            analyzeBtn.disabled = false;
            analyzeBtn.innerHTML = originalBtnHtml;
        }
    }

    analyzeBtn.addEventListener('click', runAnalysis);

    exportPdfBtn.addEventListener('click', (e) => {
        e.preventDefault();
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `${basePath}/api/pdf`;
        form.target = '_blank';
        const params = {
            report: 'analisis-rasio',
            date: dateInput.value.split('-').reverse().join('-'),
            compare_date: compareDateInput.value.split('-').reverse().join('-')
        };
        for (const key in params) {
            const hiddenField = document.createElement('input');
            hiddenField.type = 'hidden';
            hiddenField.name = key;
            hiddenField.value = params[key];
            form.appendChild(hiddenField);
        }
        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    });

    runAnalysis(); // Initial load
}

function initAnalisisRasioPage() {
    const dateInput = document.getElementById('ra-tanggal-akhir');
    const compareDateInput = document.getElementById('ra-tanggal-pembanding');
    const analyzeBtn = document.getElementById('ra-tampilkan-btn');
    const contentContainer = document.getElementById('ratio-analysis-content');
    const cardTemplate = document.getElementById('ratio-card-template');
    const exportPdfBtn = document.getElementById('export-ra-pdf');

    if (!analyzeBtn) return;

    // Set default dates
    const today = new Date();
    dateInput.value = today.toISOString().split('T')[0];
    const lastMonth = new Date(today.setMonth(today.getMonth() - 1));
    compareDateInput.value = lastMonth.toISOString().split('T')[0];

    const ratioDefinitions = {
        profit_margin: {
            name: 'Profit Margin',
            formula: '(Laba Bersih / Total Pendapatan) * 100%',
            description: 'Mengukur seberapa besar laba bersih yang dihasilkan dari setiap rupiah pendapatan. Semakin tinggi, semakin baik.',
            format: (val) => val.toFixed(2),
            interpret: (val) => val < 0.5 ? 'Sehat' : (val < 0.8 ? 'Waspada' : 'Berisiko Tinggi'),
            color: (val) => val < 0.5 ? 'text-success' : (val < 0.8 ? 'text-warning' : 'text-danger'),
        },
        debt_to_equity: {
            name: 'Debt to Equity Ratio',
            formula: 'Total Liabilitas / Total Ekuitas',
            description: 'Mengukur proporsi pembiayaan perusahaan antara utang dan modal sendiri. Semakin rendah, semakin aman posisi keuangan perusahaan.',
            format: (val) => val.toFixed(2),
            interpret: (val) => val < 1 ? 'Sehat' : (val < 2 ? 'Waspada' : 'Berisiko Tinggi'),
            color: (val) => val < 1 ? 'text-success' : (val < 2 ? 'text-warning' : 'text-danger'),
        },
        debt_to_asset: {
            name: 'Debt to Asset Ratio',
            formula: 'Total Liabilitas / Total Aset',
            description: 'Mengukur seberapa besar aset perusahaan yang dibiayai oleh utang. Semakin rendah, semakin baik.',
            format: (val) => val.toFixed(2),
            interpret: (val) => val < 0.4 ? 'Sangat Sehat' : (val < 0.6 ? 'Sehat' : 'Berisiko'),
            color: (val) => val < 0.4 ? 'text-success' : (val < 0.6 ? 'text-primary' : 'text-danger'),
        },
        return_on_equity: {
            name: 'Return on Equity (ROE)',
            formula: '(Laba Bersih / Total Ekuitas) * 100%',
            description: 'Mengukur kemampuan perusahaan menghasilkan laba dari modal yang diinvestasikan oleh pemilik/anggota. Semakin tinggi, semakin efisien penggunaan modal.',
            format: (val) => `${(val * 100).toFixed(2)}%`,
            interpret: (val) => val > 0.15 ? 'Sangat Baik' : (val > 0.05 ? 'Baik' : 'Kurang Efisien'),
            color: (val) => val > 0.15 ? 'text-success' : (val > 0.05 ? 'text-warning' : 'text-danger'),
        },
        return_on_assets: {
            name: 'Return on Assets (ROA)',
            formula: '(Laba Bersih / Total Aset) * 100%',
            description: 'Mengukur efisiensi perusahaan dalam menggunakan asetnya untuk menghasilkan laba. Semakin tinggi, semakin baik.',
            format: (val) => `${(val * 100).toFixed(2)}%`,
            interpret: (val) => val > 0.1 ? 'Sangat Efisien' : (val > 0.05 ? 'Efisien' : 'Kurang Efisien'),
            color: (val) => val > 0.1 ? 'text-success' : (val > 0.05 ? 'text-primary' : 'text-warning'),
        },
        asset_turnover: {
            name: 'Asset Turnover Ratio',
            formula: 'Total Pendapatan / Total Aset',
            description: 'Mengukur efisiensi penggunaan aset untuk menghasilkan pendapatan. Semakin tinggi, semakin efisien.',
            format: (val) => val.toFixed(2) + 'x',
            interpret: (val) => val > 1.5 ? 'Sangat Efisien' : (val > 1 ? 'Efisien' : 'Kurang Efisien'),
            color: (val) => val > 1.5 ? 'text-success' : (val > 1 ? 'text-primary' : 'text-warning'),
        }
    };

    async function runAnalysis() {
        const date = dateInput.value;
        const compareDate = compareDateInput.value;

        if (!date) {
            showToast('Tanggal analisis wajib diisi.', 'error');
            return;
        }

        contentContainer.innerHTML = '<div class="text-center p-5"><div class="spinner-border"></div></div>';

        try {
            const params = new URLSearchParams({ date });
            if (compareDate) {
                params.append('compare_date', compareDate);
            }
            const response = await fetch(`${basePath}/api/analisis-rasio?${params.toString()}`);
            const result = await response.json();
            if (result.status !== 'success') throw new Error(result.message);

            const { current, previous } = result.data;
            contentContainer.innerHTML = '<div class="row"></div>';
            const rowContainer = contentContainer.querySelector('.row');

            for (const key in current) {
                if (ratioDefinitions[key]) {
                    const def = ratioDefinitions[key];
                    const card = cardTemplate.content.cloneNode(true);
                    
                    card.querySelector('.ratio-name').textContent = def.name;
                    card.querySelector('[data-bs-toggle="tooltip"]').setAttribute('title', def.description);
                    card.querySelector('.ratio-value').textContent = def.format(current[key]);
                    card.querySelector('.ratio-formula').textContent = `Rumus: ${def.formula}`;
                    
                    const interpretationEl = card.querySelector('.ratio-interpretation');
                    interpretationEl.textContent = `Interpretasi: ${def.interpret(current[key])}`;
                    interpretationEl.classList.add(def.color(current[key]));

                    if (previous && previous[key] !== null) {
                        const change = current[key] - previous[key];
                        const changeIcon = change >= 0 ? '<i class="bi bi-arrow-up"></i>' : '<i class="bi bi-arrow-down"></i>';
                        const changeColor = change >= 0 ? 'text-success' : 'text-danger';
                        card.querySelector('.ratio-comparison').innerHTML = `${changeIcon} <span class="${changeColor}">${Math.abs(change * (def.name.includes('%') ? 100 : 1)).toFixed(2)}</span> vs periode sebelumnya`;
                    } else {
                        card.querySelector('.ratio-comparison').textContent = 'Tidak ada data pembanding.';
                    }

                    rowContainer.appendChild(card);
                }
            }

            // Re-initialize tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

        } catch (error) {
            contentContainer.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
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
            date: dateInput.value,
            compare_date: compareDateInput.value
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

// File: assets/js/ksp/member/home.js

let healthScoreChartInstance = null;
let paymentScanner = null;

document.addEventListener('DOMContentLoaded', () => {
    const paymentForm = document.getElementById('form-confirm-payment');
    if (paymentForm) {
        paymentForm.addEventListener('submit', handlePaymentConfirmation);
    }
});

/**
 * Animates a number from a start value to an end value.
 * @param {HTMLElement} obj The element to update.
 * @param {number} start The starting number.
 * @param {number} end The ending number.
 * @param {number} duration The animation duration in milliseconds.
 */
function animateValue(obj, start, end, duration) {
    let startTimestamp = null;
    const step = (timestamp) => {
        if (!startTimestamp) startTimestamp = timestamp;
        const progress = Math.min((timestamp - startTimestamp) / duration, 1);
        obj.innerHTML = Math.floor(progress * (end - start) + start);
        if (progress < 1) {
            window.requestAnimationFrame(step);
        } else {
            obj.innerHTML = end; // Ensure it ends on the exact value
        }
    };
    window.requestAnimationFrame(step);
}

async function loadFinancialHealth() {
    const scoreEl = document.getElementById('health-score-text');
    const ratingEl = document.getElementById('health-score-rating');
    const insightsEl = document.getElementById('smart-insights-list');

    if (!scoreEl || !ratingEl || !insightsEl) return;

    try {
        // Menggunakan data yang sudah dimuat oleh loadSummary()
        const data = window.memberDashboardData;
        if (!data || !data.simpanan_per_jenis) {
            // Jika data belum siap, coba lagi setelah 1 detik
            setTimeout(loadFinancialHealth, 1000);
            return;
        }

        // --- Calculation Logic ---
        let score = 0;
        const insights = [];

        const totalSimpanan = data.simpanan_per_jenis.reduce((sum, s) => sum + s.saldo, 0);
        const simpananSukarela = data.simpanan_per_jenis.find(s => s.tipe === 'sukarela')?.saldo || 0;
        const sisaPinjaman = data.pinjaman || 0;

        // 1. Savings to Loan Ratio (Max 40 points)
        let ratioScore = 0;
        if (sisaPinjaman > 0) {
            const ratio = totalSimpanan / sisaPinjaman;
            ratioScore = Math.min(40, ratio * 20);
        } else {
            ratioScore = 40; // Skor penuh jika tidak ada pinjaman
        }
        score += ratioScore;
        if (ratioScore < 20) insights.push({ text: "Tingkatkan simpanan untuk menyeimbangkan pinjaman.", icon: "bi-arrow-up-circle", color: "text-green-500" });

        // 2. Voluntary Savings Contribution (Max 30 points)
        let sukarelaScore = 0;
        if (totalSimpanan > 0) {
            const sukarelaRatio = simpananSukarela / totalSimpanan;
            sukarelaScore = Math.min(30, sukarelaRatio * 60);
        }
        score += sukarelaScore;
        if (sukarelaScore < 15) insights.push({ text: "Mulai menabung di Simpanan Sukarela untuk dana darurat.", icon: "bi-piggy-bank", color: "text-blue-500" });

        // 3. On-time Payments (Max 20 points) - Dummy logic
        // Di implementasi nyata, ini akan dihitung dari riwayat angsuran
        const hasOverdue = data.upcoming_payments.some(p => new Date(p.tanggal_jatuh_tempo) < new Date());
        let paymentScore = hasOverdue ? 0 : 20;
        score += paymentScore;
        if (hasOverdue) insights.push({ text: "Ada angsuran yang terlambat. Segera selesaikan.", icon: "bi-exclamation-triangle", color: "text-red-500" });

        // 4. Account Age (Max 10 points)
        const joinDate = new Date(data.tanggal_daftar);
        const years = (new Date() - joinDate) / (1000 * 60 * 60 * 24 * 365);
        let ageScore = Math.min(10, years * 5);
        score += ageScore;

        score = Math.round(score);

        // --- Update UI ---
        animateValue(scoreEl, 0, score, 1500); // Animate the score

        let ratingText = "Perlu Ditingkatkan";
        let ratingColor = "text-red-500";
        if (score >= 80) { ratingText = "Sangat Sehat"; ratingColor = "text-green-600"; }
        else if (score >= 60) { ratingText = "Sehat"; ratingColor = "text-blue-600"; }
        else if (score >= 40) { ratingText = "Cukup Sehat"; ratingColor = "text-yellow-600"; }
        ratingEl.textContent = ratingText;
        ratingEl.className = `text-xs font-bold mb-1 ${ratingColor}`;

        // Render Insights
        if (insights.length > 0) {
            insightsEl.innerHTML = insights.slice(0, 2).map(i => `
                <div class="flex items-start gap-2 text-xs">
                    <i class="bi ${i.icon} ${i.color} mt-0.5"></i>
                    <p class="text-gray-600 font-medium">${i.text}</p>
                </div>
            `).join('');
        } else {
            insightsEl.innerHTML = `<div class="flex items-start gap-2 text-xs"><i class="bi bi-check-circle-fill text-green-500 mt-0.5"></i><p class="text-gray-600 font-medium">Kondisi keuangan Anda sangat prima. Pertahankan!</p></div>`;
        }

        renderHealthScoreChart(score);

    } catch (e) {
        console.error("Failed to load financial health:", e);
    }
}

function renderHealthScoreChart(score) {
    const ctx = document.getElementById('healthScoreChart').getContext('2d');
    // Use colors for white background
    const progressColor = score >= 80 ? '#16a34a' : (score >= 60 ? '#2563eb' : '#ca8a04'); 
    const trackColor = '#f3f4f6';

    if (healthScoreChartInstance) {
        healthScoreChartInstance.destroy();
    }
    healthScoreChartInstance = new Chart(ctx, {
        type: 'doughnut',
        data: {
            datasets: [{
                data: [score, 100 - score],
                backgroundColor: [progressColor, trackColor],
                borderWidth: 0,
                borderRadius: 20,
            }]
        },
        options: {
            responsive: true,
            cutout: '80%',
            plugins: { legend: { display: false }, tooltip: { enabled: false } },
            animation: { 
                duration: 1500,
                easing: 'easeOutQuart',
                animateRotate: true, 
                animateScale: true }
        }
    });
}

window.openPaymentScanner = function() {
    const modal = document.getElementById('modal-payment-scanner');
    if (!modal) return;

    // Reset views
    document.getElementById('payment-scanner-view').classList.remove('hidden');
    document.getElementById('payment-confirmation-view').classList.add('hidden');
    document.getElementById('form-confirm-payment').reset();

    modal.classList.remove('hidden');

    if (paymentScanner) {
        return; // Already running
    }

    setTimeout(() => {
        paymentScanner = new Html5Qrcode("payment-reader");
        const config = { fps: 10, qrbox: { width: 250, height: 250 } };
        
        paymentScanner.start({ facingMode: "environment" }, config, onPaymentScanSuccess)
        .catch(err => {
            console.error("Error starting payment scanner", err);
            Swal.fire('Error', 'Gagal mengakses kamera. Pastikan izin kamera diberikan.', 'error');
            stopPaymentScanner();
        });
    }, 200);
}

async function onPaymentScanSuccess(decodedText, decodedResult) {
    // Stop scanning
    stopPaymentScanner(false); // Don't hide modal yet

    try {
        const qrData = JSON.parse(decodedText);
        if (!qrData.merchant || !qrData.amount) {
            throw new Error("Format QR Code tidak valid.");
        }

        // Cek saldo sukarela sebelum menampilkan konfirmasi
        const data = window.memberDashboardData;
        let saldoTersedia = 0;
        let namaSimpanan = 'Simpanan Sukarela';

        if (data && data.simpanan_per_jenis) {
            const defaultId = data.default_payment_savings_id;
            const source = data.simpanan_per_jenis.find(s => s.id == defaultId) || data.simpanan_per_jenis.find(s => s.tipe === 'sukarela');
            if (source) { saldoTersedia = source.saldo; namaSimpanan = source.nama; }
        }

        if (qrData.amount > saldoTersedia) {
            stopPaymentScanner(); // Tutup scanner
            Swal.fire('Saldo Tidak Cukup', `Saldo ${namaSimpanan} Anda (<b>${formatRupiah(saldoTersedia)}</b>) tidak mencukupi untuk pembayaran sebesar <b>${formatRupiah(qrData.amount)}</b>.`, 'warning');
            return;
        }

        // Switch to confirmation view
        document.getElementById('payment-scanner-view').classList.add('hidden');
        document.getElementById('payment-confirmation-view').classList.remove('hidden');

        // Populate data
        document.getElementById('payment-merchant-name').textContent = qrData.merchant;
        document.getElementById('payment-amount').textContent = formatRupiah(qrData.amount);
        document.getElementById('payment-data-input').value = decodedText;
        
        // Focus password input
        document.getElementById('payment-password').focus();

    } catch (e) {
        Swal.fire('Error', `QR Code tidak valid: ${e.message}`, 'error');
        // Close modal completely on error
        stopPaymentScanner();
    }
}

window.stopPaymentScanner = function(hideModal = true) {
    const modal = document.getElementById('modal-payment-scanner');
    if (hideModal && modal) {
        modal.classList.add('hidden');
    }
    
    if (paymentScanner) {
        paymentScanner.stop().then(() => {
            paymentScanner.clear();
            paymentScanner = null;
        }).catch(err => console.error("Failed to stop payment scanner", err));
    }
}

async function handlePaymentConfirmation(e) {
    e.preventDefault();
    const form = e.target;
    const btn = form.querySelector('button[type="submit"]');
    const originalText = btn.textContent;
    btn.disabled = true;
    btn.textContent = 'Memproses...';

    const formData = new FormData(form);

    try {
        const response = await fetch(`${basePath}/api/member/dashboard?action=process_qr_payment`, {
            method: 'POST',
            body: formData
        });
        const result = await response.json();

        if (result.success) {
            stopPaymentScanner();
            Swal.fire('Pembayaran Berhasil', result.message, 'success');
            loadSummary(); // Refresh dashboard data
        } else {
            throw new Error(result.message);
        }
    } catch (error) {
        Swal.fire('Pembayaran Gagal', error.message, 'error');
    } finally {
        btn.disabled = false;
        btn.textContent = originalText;
    }
}

async function loadSavingsChart() {
    if (typeof Chart === 'undefined') {
        console.warn('Chart.js library is not loaded.');
        return;
    }
    const chartCanvas = document.getElementById('savingsChart');
    if (!chartCanvas) return;

    try {
        const res = await fetch(`${basePath}/api/member/dashboard?action=savings_growth`);
        const json = await res.json();
        
        if(json.success) {
            const ctx = chartCanvas.getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: json.labels,
                    datasets: [{
                        label: 'Total Simpanan',
                        data: json.data,
                        borderColor: '#3b82f6', // blue-500
                        backgroundColor: (context) => {
                            const ctx = context.chart.ctx;
                            const gradient = ctx.createLinearGradient(0, 0, 0, 200);
                            gradient.addColorStop(0, 'rgba(59, 130, 246, 0.2)');
                            gradient.addColorStop(1, 'rgba(59, 130, 246, 0)');
                            return gradient;
                        },
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true,
                        pointRadius: 3,
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: '#3b82f6',
                        pointBorderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return formatRupiah(context.parsed.y);
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: false,
                            ticks: {
                                callback: function(value) {
                                    if (value >= 1000000) return (value / 1000000).toFixed(1) + 'jt';
                                    if (value >= 1000) return (value / 1000).toFixed(0) + 'rb';
                                    return value;
                                },
                                font: { size: 10 },
                                color: '#9ca3af'
                            },
                            grid: { borderDash: [2, 4], color: '#f3f4f6' },
                            border: { display: false }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { font: { size: 10 }, color: '#9ca3af' },
                            border: { display: false }
                        }
                    }
                }
            });
        }
    } catch(e) { console.error(e); }
}

window.openLevelInfoModal = function() {
    document.getElementById('modal-level-info').classList.remove('hidden');
}

// Expose to global scope
window.loadSavingsChart = loadSavingsChart;
function initGenerateQrPage() {
    // Load QRCode library dynamically if not present
    if (typeof QRCode === 'undefined') {
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js';
        document.head.appendChild(script);
    }

    const form = document.getElementById('qr-generator-form');
    const qrcodeContainer = document.getElementById('qrcode');
    const qrCard = document.getElementById('qr-card');
    const emptyState = document.getElementById('empty-state');
    const qrActions = document.getElementById('qr-actions');
    
    const displayMerchant = document.getElementById('display-merchant');
    const displayAmount = document.getElementById('display-amount');
    const displayRef = document.getElementById('display-ref');

    const printBtn = document.getElementById('print-qr-btn');
    const downloadBtn = document.getElementById('download-qr-btn'); // New button
    let qrcodeInstance = null;

    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            const merchant = document.getElementById('qr-merchant').value;
            const amount = parseFloat(document.getElementById('qr-amount').value);
            const ref = document.getElementById('qr-ref').value;

            if (!merchant || isNaN(amount) || amount <= 0) {
                // Asumsi fungsi showNotification tersedia secara global atau gunakan alert standar
                if (typeof showNotification === 'function') {
                    showNotification('Nama Merchant dan Jumlah harus diisi dengan benar.', 'error');
                } else {
                    alert('Nama Merchant dan Jumlah harus diisi dengan benar.');
                }
                return;
            }

            const qrData = { merchant, amount, ref: ref || null };
            const jsonString = JSON.stringify(qrData);

            // Update UI
            qrcodeContainer.innerHTML = '';
            
            // Hide empty state, show card
            emptyState.classList.add('hidden');
            qrCard.classList.remove('scale-95', 'opacity-50', 'blur-sm');
            qrCard.classList.add('scale-100', 'opacity-100', 'blur-0');
            qrActions.classList.remove('hidden');

            // Update Card Content
            displayMerchant.textContent = merchant;
            displayAmount.textContent = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(amount);
            displayRef.textContent = ref ? `REF: ${ref}` : '';

            // Pastikan library QRCode sudah dimuat
            if (typeof QRCode !== 'undefined') {
                qrcodeInstance = new QRCode(qrcodeContainer, { 
                    text: jsonString, 
                    width: 200, 
                    height: 200, 
                    colorDark : "#000000", 
                    colorLight : "#ffffff", 
                    correctLevel : QRCode.CorrectLevel.H 
                });
            } else {
                console.error('Library QRCode tidak ditemukan.');
                alert('Gagal memuat library QR Code.');
            }
        });
    }

    if (printBtn) {
        printBtn.addEventListener('click', function() {
            const merchantName = document.getElementById('qr-merchant').value;
            const amount = document.getElementById('qr-amount').value;
            const formattedAmount = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(amount);

            const printWindow = window.open('', 'PRINT', 'height=600,width=800');

            printWindow.document.write('<html><head><title>Cetak QR Code</title>');
            printWindow.document.write('<style>body { font-family: sans-serif; text-align: center; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; } .qr-wrapper { display: inline-block; border: 2px solid #333; padding: 40px; border-radius: 20px; } h3 { margin: 10px 0 5px; font-size: 24px; } h4 { margin: 5px 0 20px; font-size: 32px; color: #2563eb; } img { margin: 0 auto; }</style>');
            printWindow.document.write('</head><body>');
            printWindow.document.write('<div class="qr-wrapper">');
            printWindow.document.write(`<h3>${merchantName}</h3>`);
            printWindow.document.write(`<h4>${formattedAmount}</h4>`);
            printWindow.document.write(qrcodeContainer.innerHTML);
            printWindow.document.write('</div>');
            printWindow.document.write('</body></html>');

            printWindow.document.close();
            printWindow.focus();
            
            // Beri waktu sedikit agar gambar QR ter-render di window baru sebelum print
            setTimeout(() => {
                printWindow.print();
                printWindow.close();
            }, 500);
        });
    }

    if (downloadBtn) {
        downloadBtn.addEventListener('click', function() {
            const img = qrcodeContainer.querySelector('img');
            if (img) {
                const link = document.createElement('a');
                link.download = `QR-${document.getElementById('qr-ref').value || 'Payment'}.png`;
                link.href = img.src;
                link.click();
            }
        });
    }
}

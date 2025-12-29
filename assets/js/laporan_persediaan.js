function initLaporanPersediaanPage() {
    const tableBody = document.getElementById('reportTableBody');
    const searchInput = document.getElementById('searchInput');
    const totalValueCell = document.getElementById('totalInventoryValue');
    const totalInventoryValueHeader = document.getElementById('totalInventoryValueHeader');
    const loadingIndicator = document.getElementById('loadingIndicator');
    const printButton = document.getElementById('printButton');
    const exportButton = document.getElementById('exportButton');

    let searchDebounceTimer;
    let allItems = []; // Untuk menyimpan semua data asli dari API

    // Fungsi untuk memformat angka menjadi format Rupiah
    function formatRupiah(number) {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0
        }).format(number);
    }

    // Fungsi untuk merender data ke tabel
    function renderTable(items) {
        if (items.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">Tidak ada data untuk ditampilkan.</td></tr>';
            totalValueCell.textContent = formatRupiah(0);
            totalInventoryValueHeader.textContent = formatRupiah(0);
            return;
        }

        let totalValue = 0;
        const rowsHtml = items.map((item, index) => {
            const inventoryValue = (item.stok || 0) * (item.harga_beli || 0);
            totalValue += inventoryValue;
            return `
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="px-6 py-4 text-center text-sm text-gray-900 dark:text-white">${index + 1}</td>
                    <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">${item.nama_barang}</td>
                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">${item.sku || '-'}</td>
                    <td class="px-6 py-4 text-right text-sm text-gray-900 dark:text-white">${item.stok || 0}</td>
                    <td class="px-6 py-4 text-right text-sm text-gray-900 dark:text-white">${formatRupiah(item.harga_beli || 0)}</td>
                    <td class="px-6 py-4 text-right text-sm font-bold text-gray-900 dark:text-white">${formatRupiah(inventoryValue)}</td>
                </tr>
            `;
        }).join('');

        tableBody.innerHTML = rowsHtml;
        totalValueCell.textContent = formatRupiah(totalValue);
        totalInventoryValueHeader.textContent = formatRupiah(totalValue);
    }

    // Fungsi untuk memuat data dari API
    async function loadInventoryData() {
        loadingIndicator.style.display = 'block';
        tableBody.innerHTML = '';

        try {
            // Menggunakan endpoint yang sama dengan stok opname, pastikan API mengembalikan harga_beli
            const response = await fetch(`${basePath}/api/stok?action=list&limit=9999`);
            const data = await response.json();

            if (data.status === 'success') {
                allItems = data.data;
                renderTable(allItems);
            } else {
                throw new Error(data.message || 'Gagal memuat data dari server.');
            }
        } catch (error) {
            tableBody.innerHTML = `<tr><td colspan="6" class="px-6 py-4 text-center text-red-500 text-sm">Gagal memuat data: ${error.message}</td></tr>`;
        } finally {
            loadingIndicator.style.display = 'none';
        }
    }

    // Fungsi filter/pencarian
    function filterData() {
        const searchTerm = searchInput.value.toLowerCase();
        if (!searchTerm) {
            renderTable(allItems);
            return;
        }

        const filteredItems = allItems.filter(item =>
            item.nama_barang.toLowerCase().includes(searchTerm) ||
            (item.sku && item.sku.toLowerCase().includes(searchTerm))
        );
        renderTable(filteredItems);
    }

    // Event listener untuk pencarian dengan debounce
    searchInput.addEventListener('input', () => {
        clearTimeout(searchDebounceTimer);
        searchDebounceTimer = setTimeout(filterData, 300);
    });

    // Event listener untuk tombol cetak
    printButton.addEventListener('click', () => {
        // Implementasi sederhana, bisa dikembangkan dengan library seperti Print.js
        window.print();
    });

    // Event listener untuk tombol export (implementasi sederhana ke CSV)
    exportButton.addEventListener('click', () => {
        const headers = ['Nama Barang', 'SKU', 'Stok', 'Harga Beli', 'Nilai Persediaan'];
        const rows = allItems.map(item => [
            item.nama_barang,
            item.sku || '',
            item.stok || 0,
            item.harga_beli || 0,
            (item.stok || 0) * (item.harga_beli || 0)
        ]);

        let csvContent = "data:text/csv;charset=utf-8," + headers.join(",") + "\n" + rows.map(e => e.join(",")).join("\n");
        
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", "laporan_persediaan.csv");

        // Beri tahu router SPA untuk mengabaikan klik ini, mencegah error navigasi.
        link.setAttribute('data-spa-ignore', 'true');

        document.body.appendChild(link);
        link.click();
        // Hapus link setelah jeda singkat untuk memastikan download dimulai.
        setTimeout(() => document.body.removeChild(link), 150);
    });

    // Muat data saat halaman pertama kali dibuka
    loadInventoryData();
}
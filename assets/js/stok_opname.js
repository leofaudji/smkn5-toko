function initStokOpnamePage() {
    const form = document.getElementById('stockOpnameForm');
    const tableBody = document.getElementById('itemsTableBody');
    const adjAccountSelect = document.getElementById('adj_account_id');
    const saveButton = document.getElementById('saveButton');
    const searchInput = document.getElementById('searchInput');
    const stockFilter = document.getElementById('stockFilter');

    if (!form) return; // Hentikan jika elemen utama tidak ditemukan

    const tanggalPicker = flatpickr("#tanggal", { dateFormat: "d-m-Y", allowInput: true });
    let physicalStockValues = {}; // Untuk menyimpan input stok fisik sementara
    let searchDebounceTimer;

    // Set tanggal hari ini
    tanggalPicker.setDate(new Date(), true);

    // 1. Muat Akun Penyesuaian
    async function loadAdjustmentAccounts() {
        try {
            const response = await fetch(`${basePath}/api/stok?action=get_adjustment_accounts`);
            const data = await response.json();
            if (data.status === 'success') {
                adjAccountSelect.innerHTML = '<option value="">-- Pilih Akun --</option>';
                data.data.forEach(acc => {
                    adjAccountSelect.innerHTML += `<option value="${acc.id}">${acc.kode_akun} - ${acc.nama_akun}</option>`;
                });
            } else {
                throw new Error(data.message);
            }
        } catch (error) {
            adjAccountSelect.innerHTML = '<option value="">Gagal memuat akun</option>';
            showToast(`Gagal memuat akun penyeimbang: ${error.message}`, 'error');
        }
    }

    // 2. Fungsi untuk memuat barang dengan filter dan pencarian
    async function loadItems() {
        // Simpan nilai input yang ada sebelum memuat ulang
        tableBody.querySelectorAll('.physical-stock-input').forEach(input => {
            physicalStockValues[input.dataset.itemId] = {
                physical: input.value,
                system: input.dataset.stokSistem
            };
        });

        const searchTerm = searchInput.value;
        const filterValue = stockFilter.value;
        const query = new URLSearchParams({
            action: 'list', 
            limit: 9999, // Ambil semua barang untuk stok opname
            search: searchTerm,
            stok_filter: filterValue
        });

        tableBody.innerHTML = `<tr><td colspan="6" class="text-center p-5"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto"></div> Memuat data...</td></tr>`;

        try {
            const response = await fetch(`${basePath}/api/stok?${query.toString()}`);
            const data = await response.json();

            if (data.status === 'success') {
                if (data.data.length > 0) {
                    let rowsHtml = ''; // Siapkan string untuk menampung semua baris
                    data.data.forEach((item, index) => {
                        // Cek apakah ada nilai yang tersimpan, jika tidak, gunakan stok sistem
                        const physicalStock = physicalStockValues[item.id] !== undefined ? physicalStockValues[item.id].physical : item.stok;
                        const selisih = parseInt(physicalStock, 10) - parseInt(item.stok, 10);

                        // Gabungkan string HTML ke variabel, jangan langsung ke DOM
                        rowsHtml += `
                            <tr data-item-id="${item.id}" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">${index + 1}</td>
                                <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">${item.nama_barang}</td>
                                <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">${item.sku || '-'}</td>
                                <td class="px-4 py-2 text-sm text-right text-gray-900 dark:text-white">${item.stok}</td>
                                <td class="px-4 py-2">
                                    <input type="number" class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 text-sm text-center physical-stock-input"
                                           value="${physicalStock}"
                                           data-item-id="${item.id}"
                                           data-stok-sistem="${item.stok}">
                                </td>
                                <td class="px-4 py-2 text-sm text-right font-bold difference-cell ${selisih < 0 ? 'text-red-600 dark:text-red-400' : (selisih > 0 ? 'text-green-600 dark:text-green-400' : 'text-gray-900 dark:text-white')}">${selisih}</td>
                            </tr>
                        `;
                    });
                    tableBody.innerHTML = rowsHtml; // Masukkan semua baris ke tabel sekaligus
                } else {
                    tableBody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-gray-500 dark:text-gray-400">Tidak ada data barang yang cocok dengan kriteria.</td></tr>';
                }
            } else {
                // Jika status dari API adalah 'error' atau lainnya, tampilkan pesan dari server
                throw new Error(data.message || 'Gagal memuat data dari server.');
            }
        } catch (error) {
            tableBody.innerHTML = `<tr><td colspan="6" class="text-center text-red-500 py-4">Gagal memuat data: ${error.message}</td></tr>`;
        }
    }

    // 3. Hitung Selisih Secara Real-time
    tableBody.addEventListener('input', function(e) {
        if (e.target.classList.contains('physical-stock-input')) {
            const input = e.target;
            const itemId = input.dataset.itemId;
            const stokSistem = parseInt(input.dataset.stokSistem, 10);
            const stokFisik = parseInt(input.value, 10) || 0;
            const selisih = stokFisik - stokSistem;

            // Simpan nilai terbaru ke object
            physicalStockValues[itemId] = {
                physical: input.value,
                system: stokSistem
            };

            const differenceCell = input.closest('tr').querySelector('.difference-cell');
            differenceCell.textContent = selisih;
            
            // Beri warna untuk mempermudah
            differenceCell.classList.remove('text-red-600', 'dark:text-red-400', 'text-green-600', 'dark:text-green-400', 'text-gray-900', 'dark:text-white');
            if (selisih < 0) {
                differenceCell.classList.add('text-red-600', 'dark:text-red-400');
            } else if (selisih > 0) {
                differenceCell.classList.add('text-green-600', 'dark:text-green-400');
            } else {
                differenceCell.classList.add('text-gray-900', 'dark:text-white');
            }
        }
    });

    // 4. Simpan Data
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        saveButton.disabled = true;
        saveButton.innerHTML = `<svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Menyimpan...`;

        const itemsToAdjust = [];
        // Ambil semua input yang ada di tabel saat ini
        const currentInputs = tableBody.querySelectorAll('.physical-stock-input');
        currentInputs.forEach(input => {
            physicalStockValues[input.dataset.itemId] = {
                physical: input.value,
                system: input.dataset.stokSistem
            };
        });

        // Iterasi melalui semua nilai yang telah diubah, bukan hanya yang terlihat
        for (const itemId in physicalStockValues) {
            const stokFisik = parseInt(physicalStockValues[itemId].physical, 10);
            const stokSistem = parseInt(physicalStockValues[itemId].system, 10);
            
            if (stokSistem !== null && stokSistem !== stokFisik) {
                itemsToAdjust.push({
                    item_id: parseInt(itemId, 10),
                    stok_fisik: stokFisik
                });
            }
        }

        if (itemsToAdjust.length === 0) {
            showToast('Tidak ada perubahan stok yang perlu disimpan.', 'info');
            saveButton.disabled = false;
            saveButton.innerHTML = `<i class="bi bi-save me-2"></i>Simpan Hasil Stok Opname`;
            return;
        }

        const payload = {
            action: 'batch_adjust_stock',
            tanggal: document.getElementById('tanggal').value.split('-').reverse().join('-'),
            adj_account_id: document.getElementById('adj_account_id').value,
            keterangan: document.getElementById('keterangan').value,
            items: itemsToAdjust
        };

        fetch(`${basePath}/api/stok`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showToast('Stok opname berhasil disimpan!', 'success');
                // Beri jeda agar toast terlihat sebelum navigasi
                setTimeout(() => {
                    navigate(`${basePath}/stok`); // Gunakan fungsi navigate dari SPA
                }, 1000);
            } else {
                throw new Error(data.message || 'Terjadi kesalahan.');
            }
        })
        .catch(error => {
            showToast('Error: ' + error.message, 'error');
        })
        .finally(() => {
            saveButton.disabled = false;
            saveButton.innerHTML = `<i class="bi bi-save me-2"></i>Simpan Hasil Stok Opname`;
        });
    });

    // --- Initial Load ---
    loadAdjustmentAccounts();
    loadItems();

    // Event listener untuk filter
    stockFilter.addEventListener('change', loadItems);

    // Event listener untuk pencarian dengan debounce
    searchInput.addEventListener('input', () => {
        clearTimeout(searchDebounceTimer);
        searchDebounceTimer = setTimeout(loadItems, 300); // Jeda 300ms sebelum melakukan pencarian
    });
}
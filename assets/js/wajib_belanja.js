function initWajibBelanjaPage() {
    const form = document.getElementById('wb-form');
    const modal = document.getElementById('wb-form-modal');
    const tableBody = document.getElementById('wb-table-body');
    const loadingEl = document.getElementById('wb-loading');
    const paginationInfo = document.getElementById('wb-pagination-info');
    const paginationContainer = document.getElementById('wb-pagination');
    const tambahBtn = document.getElementById('wb-tambah-btn');
    const itemsBody = document.getElementById('wb-items-body');
    const addRowBtn = document.getElementById('wb-add-row-btn');
    const loadAllBtn = document.getElementById('wb-load-all-btn');
    const totalDisplay = document.getElementById('wb-total-display');

    let currentPage = 1;
    let nominalDefault = 50000;
    let anggotaList = []; // Simpan daftar anggota untuk dropdown

    async function fetchWajibBelanja(page = 1) {
        currentPage = page;
        loadingEl.style.display = 'block';
        tableBody.innerHTML = '';

        try {
            const response = await fetch(`${basePath}/api/wajib-belanja?action=list&page=${page}`);
            const result = await response.json();

            if (result.success) {
                renderTable(result.data);
                renderPagination(paginationContainer, result.pagination, fetchWajibBelanja);
                paginationInfo.textContent = `Menampilkan ${result.data.length} dari ${result.pagination.total_records} data.`;
            } else {
                showToast(result.message, 'error');
            }
        } catch (error) {
            showToast('Gagal memuat data.', 'error');
            console.error(error);
        } finally {
            loadingEl.style.display = 'none';
        }
    }

    function renderTable(data) {
        if (data.length === 0) {
            tableBody.innerHTML = `<tr><td colspan="6" class="text-center py-4">Tidak ada data.</td></tr>`;
            return;
        }

        tableBody.innerHTML = data.map(item => `
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">${formatDate(item.tanggal)}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">${item.nama_anggota}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-right">${formatRupiah(item.jumlah)}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">${item.metode_pembayaran}</td>
                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">${item.keterangan || '-'}</td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <button class="text-red-600 hover:text-red-900" title="Hapus" disabled>
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
        `).join('');
    }

    async function initForm() {
        try {
            const response = await fetch(`${basePath}/api/wajib-belanja?action=init_data`);
            const result = await response.json();

            if (result.success) {
                anggotaList = result.anggota; // Simpan ke variabel global
                const kasSelect = document.getElementById('wb-akun-kas-id');
                kasSelect.innerHTML = '<option value="">Pilih Akun Kas/Bank</option>' + result.kas_accounts.map(k => `<option value="${k.id}">${k.kode_akun} - ${k.nama_akun}</option>`).join('');
                
                nominalDefault = result.nominal_default;
            } else {
                showToast('Gagal memuat data form.', 'error');
            }
        } catch (error) {
            showToast('Gagal memuat data form.', 'error');
        }
    }

    function addRow(anggotaId = '', jumlah = nominalDefault) {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td class="px-4 py-2">
                <select name="anggota_id[]" class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-primary focus:ring-primary sm:text-sm wb-row-anggota">
                    <option value="">Pilih Anggota</option>
                    ${anggotaList.map(a => `<option value="${a.id}" ${a.id == anggotaId ? 'selected' : ''}>${a.nomor_anggota} - ${a.nama_lengkap}</option>`).join('')}
                </select>
            </td>
            <td class="px-4 py-2">
                <input type="number" name="jumlah[]" value="${jumlah}" class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-primary focus:ring-primary sm:text-sm text-right wb-row-jumlah" required>
            </td>
            <td class="px-4 py-2">
                <input type="text" name="keterangan_row[]" class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" placeholder="Opsional">
            </td>
            <td class="px-4 py-2 text-center">
                <button type="button" class="text-red-600 hover:text-red-900 wb-remove-row"><i class="bi bi-trash"></i></button>
            </td>
        `;
        itemsBody.appendChild(row);
        calculateTotal();
    }

    function calculateTotal() {
        let total = 0;
        document.querySelectorAll('.wb-row-jumlah').forEach(input => {
            total += parseFloat(input.value) || 0;
        });
        totalDisplay.textContent = formatRupiah(total);
    }

    // Event Listeners untuk Tabel
    addRowBtn.addEventListener('click', () => addRow());

    loadAllBtn.addEventListener('click', () => {
        if (confirm('Apakah Anda yakin ingin memuat semua anggota aktif? Ini akan menghapus baris yang sudah ada.')) {
            itemsBody.innerHTML = '';
            anggotaList.forEach(anggota => {
                addRow(anggota.id);
            });
        }
    });

    itemsBody.addEventListener('click', (e) => {
        if (e.target.closest('.wb-remove-row')) {
            e.target.closest('tr').remove();
            calculateTotal();
        }
    });

    itemsBody.addEventListener('input', (e) => {
        if (e.target.classList.contains('wb-row-jumlah')) {
            calculateTotal();
        }
    });

    tambahBtn.addEventListener('click', () => {
        form.reset();
        itemsBody.innerHTML = ''; // Reset tabel
        document.getElementById('wb-tanggal').valueAsDate = new Date();
        
        // Tambah satu baris kosong default
        addRow();
        
        openModal('wb-form-modal');
    });

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        // Validasi minimal satu baris
        const rows = itemsBody.querySelectorAll('tr');
        if (rows.length === 0) {
            showToast('Harap tambahkan minimal satu anggota.', 'error');
            return;
        }

        // Validasi anggota duplikat
        const selectedMembers = new Set();
        let hasDuplicate = false;
        document.querySelectorAll('.wb-row-anggota').forEach(select => {
            if (select.value) {
                if (selectedMembers.has(select.value)) hasDuplicate = true;
                selectedMembers.add(select.value);
            }
        });

        if (hasDuplicate) {
            showToast('Terdapat anggota yang dipilih lebih dari sekali.', 'error');
            return;
        }

        const submitBtn = document.getElementById('wb-form-submit-btn');
        const originalBtnText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = 'Menyimpan...';

        try {
            // Kumpulkan data manual karena struktur array
            const formData = {
                tanggal: document.getElementById('wb-tanggal').value,
                metode_pembayaran: document.getElementById('wb-metode-pembayaran').value,
                akun_kas_id: document.getElementById('wb-akun-kas-id').value,
                items: []
            };

            rows.forEach(row => {
                const anggotaId = row.querySelector('.wb-row-anggota').value;
                const jumlah = row.querySelector('.wb-row-jumlah').value;
                const ket = row.querySelector('input[name="keterangan_row[]"]').value;
                
                if (anggotaId && jumlah > 0) {
                    formData.items.push({
                        anggota_id: anggotaId,
                        jumlah: jumlah,
                        keterangan: ket
                    });
                }
            });

            const response = await fetch(`${basePath}/api/wajib-belanja`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            });
            const result = await response.json();

            if (result.success) {
                showToast(result.message, 'success');
                closeModal('wb-form-modal');
                fetchWajibBelanja(currentPage);
            } else {
                showToast(result.message, 'error');
            }
        } catch (error) {
            showToast('Terjadi kesalahan saat menyimpan.', 'error');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        }
    });

    // Initial load
    fetchWajibBelanja();
    initForm();
}
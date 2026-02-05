// File: assets/js/penarikan.js

/**
 * Inisialisasi fungsionalitas untuk halaman Persetujuan Penarikan.
 * Fungsi ini harus dipanggil dari main.js ketika halaman penarikan dimuat.
 * 
 * @depends {Function} openModal - Diharapkan tersedia dari main.js
 * @depends {Function} closeModal - Diharapkan tersedia dari main.js
 * @depends {Function} formatDate - Diharapkan tersedia dari main.js
 * @depends {Function} formatRupiah - Diharapkan tersedia dari main.js
 * @depends {Function} showToast - Diharapkan tersedia dari main.js
 */
function initPenarikanPage() {

    async function loadKasAccounts() {
        try {
            const response = await fetch(`${basePath}/api/ksp/simpanan?action=get_kas_accounts`);
            const result = await response.json();
            if (result.success) {
                const select = document.getElementById('approve-akun-kas');
                if (select) {
                    select.innerHTML = result.data.map(acc => `<option value="${acc.id}">${acc.kode_akun} - ${acc.nama_akun}</option>`).join('');
                }
            }
        } catch (error) {
            console.error('Error loading cash accounts:', error);
        }
    }

    async function loadPenarikanData() {
        const tbody = document.getElementById('penarikan-table-body');
        if (!tbody) return;
        const search = document.getElementById('search-penarikan').value;
        const status = document.getElementById('filter-status').value;
        tbody.innerHTML = '<tr><td colspan="6" class="text-center p-4">Memuat data...</td></tr>';

        try {
            const response = await fetch(`${basePath}/api/ksp/penarikan?action=list&search=${search}&status=${status}`);
            const result = await response.json();

            if (result.success && result.data.length > 0) {
                tbody.innerHTML = result.data.map(item => {
                    let statusBadge = '';
                    switch(item.status) {
                        case 'approved': statusBadge = '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Approved</span>'; break;
                        case 'rejected': statusBadge = `<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800" title="${item.catatan_admin || ''}">Rejected</span>`; break;
                        default: statusBadge = '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Pending</span>';
                    }

                    let actions = '';
                    if (item.status === 'pending') {
                        actions = `
                            <button onclick='openApproveModal(${JSON.stringify(item)})' class="text-green-600 hover:text-green-900 mr-2" title="Setujui"><i class="bi bi-check-circle-fill"></i></button>
                            <button onclick='openRejectModal(${JSON.stringify(item)})' class="text-red-600 hover:text-red-900" title="Tolak"><i class="bi bi-x-circle-fill"></i></button>
                        `;
                    }

                    return `
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${formatDate(item.tanggal_pengajuan)}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">${item.nama_lengkap}<br><span class="text-xs text-gray-400">${item.nomor_anggota}</span></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${item.jenis_simpanan}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white text-right">${formatRupiah(item.jumlah)}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-center">${statusBadge}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-center">${actions}</td>
                        </tr>
                    `;
                }).join('');
            } else {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center p-4">Tidak ada data pengajuan.</td></tr>';
            }
        } catch (error) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center p-4 text-red-500">Gagal memuat data.</td></tr>';
            console.error(error);
        }
    }

    async function handleFormSubmit(e, action) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        const btn = form.querySelector('button[type="submit"]');
        const originalText = btn.textContent;
        btn.disabled = true;
        btn.textContent = 'Memproses...';

        try {
            const response = await fetch(`${basePath}/api/ksp/penarikan?action=${action}`, {
                method: 'POST',
                body: new URLSearchParams(formData)
            });
            const result = await response.json();
            if (result.success) {
                showToast(result.message, 'success');
                closeModal(action === 'approve' ? 'modal-approve' : 'modal-reject');
                loadPenarikanData();
            } else {
                showToast(result.message, 'error');
            }
        } catch (error) {
            showToast('Terjadi kesalahan jaringan.', 'error');
        } finally {
            btn.disabled = false;
            btn.textContent = originalText;
        }
    }

    // --- Global Functions for onclick ---
    window.openApproveModal = (item) => {
        document.getElementById('approve-id').value = item.id;
        document.getElementById('approve-amount').textContent = formatRupiah(item.jumlah);
        document.getElementById('approve-member').textContent = item.nama_lengkap;
        openModal('modal-approve');
    };

    window.openRejectModal = (item) => {
        document.getElementById('reject-id').value = item.id;
        document.getElementById('reject-reason').value = '';
        openModal('modal-reject');
    };

    // --- Event Listeners ---
    document.getElementById('btn-filter').addEventListener('click', loadPenarikanData);
    document.getElementById('form-approve').addEventListener('submit', (e) => handleFormSubmit(e, 'approve'));
    document.getElementById('form-reject').addEventListener('submit', (e) => handleFormSubmit(e, 'reject'));

    // --- Initial Load ---
    loadPenarikanData();
    loadKasAccounts();
}
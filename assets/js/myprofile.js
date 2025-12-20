function initMyProfilePage() {
    const form = document.getElementById('change-password-form');
    const saveBtn = document.getElementById('save-password-btn');

    if (!form || !saveBtn) return;

    saveBtn.addEventListener('click', async () => {
        const formData = new FormData(form);
        const originalBtnHtml = saveBtn.innerHTML;

        // Client-side validation
        if (formData.get('new_password') !== formData.get('confirm_password')) {
            showToast('Password baru dan konfirmasi tidak cocok.', 'error');
            return;
        }
        if (formData.get('new_password').length < 6) {
            showToast('Password baru minimal harus 6 karakter.', 'error');
            return;
        }

        saveBtn.disabled = true;
        saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Menyimpan...';

        try {
            const minDelay = new Promise(resolve => setTimeout(resolve, 500));
            const fetchPromise = fetch(`${basePath}/api/my-profile/change-password`, {
                method: 'POST',
                body: formData
            });

            const [response] = await Promise.all([fetchPromise, minDelay]);

            const result = await response.json();
            showToast(result.message, result.status === 'success' ? 'success' : 'error');
            if (result.status === 'success') {
                form.reset();
            }
        } catch (error) {
            showToast('Terjadi kesalahan jaringan.', 'error');
        } finally {
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalBtnHtml;
        }
    });
}
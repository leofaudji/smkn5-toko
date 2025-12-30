<?php
$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}
?>

<div class="flex justify-between flex-wrap items-center pt-3 pb-2 mb-3 border-b border-gray-200 dark:border-gray-700">
    <h1 class="text-2xl font-semibold text-gray-800 dark:text-white flex items-center gap-2"><i class="bi bi-shield-lock-fill"></i> Ganti Password</h1>
</div>

<div class="max-w-2xl mx-auto">
    <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg">
        <div class="p-6">
            <form id="change-password-form">
                <div class="space-y-6">
                    <div>
                        <label for="current_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Password Saat Ini</label>
                        <input type="password" name="current_password" id="current_password" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-primary focus:ring-primary sm:text-sm">
                    </div>
                    <div>
                        <label for="new_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Password Baru</label>
                        <input type="password" name="new_password" id="new_password" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" minlength="6">
                    </div>
                    <div>
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Konfirmasi Password Baru</label>
                        <input type="password" name="confirm_password" id="confirm_password" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-primary focus:ring-primary sm:text-sm">
                    </div>
                </div>
                <div class="mt-8 pt-5 border-t border-gray-200 dark:border-gray-700 flex justify-end">
                    <button type="submit" id="save-password-btn" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary hover:bg-primary-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                        <i class="bi bi-save-fill mr-2"></i> Simpan Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('change-password-form');
        if (form) {
            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                const saveBtn = document.getElementById('save-password-btn');
                const originalBtnHtml = saveBtn.innerHTML;
                saveBtn.disabled = true;
                saveBtn.innerHTML = `<svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Menyimpan...`;

                const formData = new FormData(this);
                let isSuccess = false;

                try {
                    const response = await fetch(`${basePath}/api/my-profile/change-password`, { method: 'POST', body: formData });
                    const result = await response.json();
                    showToast(result.message, result.status === 'success' ? 'success' : 'error');
                    if (result.status === 'success') {
                        isSuccess = true;
                        // Redirect to login page after a short delay
                        setTimeout(() => {
                            // Programmatically click the logout link which performs a full page reload and redirect.
                            document.getElementById('logout-link')?.click();
                        }, 2000);
                    }
                } catch (error) {
                    showToast('Terjadi kesalahan jaringan.', 'error');
                } finally {
                    if (!isSuccess) {
                        saveBtn.disabled = false;
                        saveBtn.innerHTML = originalBtnHtml;
                    }
                }
            });
        }
    });
</script>

<?php
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>
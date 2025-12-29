<?php
$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}

// Security check
check_permission('users', 'menu');

$conn = Database::getInstance()->getConnection();
$roles_res = $conn->query("SELECT id, name FROM roles ORDER BY name ASC");
$roles_options = [];
while ($role = $roles_res->fetch_assoc()) {
    $roles_options[] = $role;
}

$role_id_filter = isset($_GET['role_id']) ? (int)$_GET['role_id'] : null;
$role_name_filter = '';
if ($role_id_filter) {
    $stmt = $conn->prepare("SELECT name FROM roles WHERE id = ?");
    $stmt->bind_param('i', $role_id_filter);
    $stmt->execute();
    if ($res = $stmt->get_result()->fetch_assoc()) {
        $role_name_filter = htmlspecialchars($res['name']);
    }
}
?>

<div class="flex justify-between flex-wrap items-center pt-3 pb-2 mb-3 border-b border-gray-200 dark:border-gray-700">
    <h1 class="text-2xl font-semibold text-gray-800 dark:text-white flex items-center gap-2"><i class="bi bi-people-fill"></i> Manajemen Pengguna <?= $role_name_filter ? '<span class="text-base font-normal text-gray-500 dark:text-gray-400">- Filter: ' . $role_name_filter . '</span>' : '' ?></h1>
    <div class="flex mb-2 md:mb-0">
        <?php if ($role_id_filter): ?>
        <a href="<?= base_url('/users') ?>" class="inline-flex items-center px-4 py-2 bg-yellow-500 border border-transparent rounded-md font-semibold text-sm text-white shadow-sm hover:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 mr-2">
            <i class="bi bi-x-circle-fill mr-2"></i> Hapus Filter
        </a>
        <?php endif; ?>
        <button type="button" class="inline-flex items-center px-4 py-2 bg-primary border border-transparent rounded-md font-semibold text-sm text-white shadow-sm hover:bg-primary-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary" id="add-user-btn">
            <i class="bi bi-plus-circle-fill mr-2"></i> Tambah Pengguna
        </button>
    </div>
</div>

<!-- Tabel Data Pengguna -->
<div class="bg-white dark:bg-gray-800 shadow rounded-lg">
    <div class="p-6">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Username</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nama Lengkap</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Role</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Dibuat pada</th>
                        <th class="relative px-6 py-3"><span class="sr-only">Aksi</span></th>
                    </tr>
                </thead>
                <tbody id="users-table-body" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <!-- Data akan dimuat di sini oleh JavaScript -->
                    <tr>
                        <td colspan="5" class="text-center p-5"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto"></div></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal untuk Tambah/Edit Pengguna -->
<div id="userModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="userModalLabel" role="dialog" aria-modal="true">
  <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeModal('userModal')"></div>
    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
    <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
      <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
        <h5 class="text-lg font-medium text-gray-900 dark:text-white" id="userModalLabel"></h5>
        <button type="button" class="text-gray-400 hover:text-gray-500 focus:outline-none" onclick="closeModal('userModal')"><i class="bi bi-x-lg"></i></button>
      </div>
      <div class="p-6">
        <form id="user-form" class="space-y-4">
            <input type="hidden" name="id" id="user-id">
            <input type="hidden" name="action" id="user-action">
            <div>
                <label for="username" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Username</label>
                <input type="text" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" id="username" name="username" required>
            </div>
            <div>
                <label for="nama_lengkap" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nama Lengkap</label>
                <input type="text" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" id="nama_lengkap" name="nama_lengkap">
            </div>
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Password</label>
                <input type="password" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" id="password" name="password">
                <p id="password-help" class="mt-2 text-sm text-gray-500 dark:text-gray-400">Kosongkan jika tidak ingin mengubah password.</p>
            </div>
            <div>
                <label for="role" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Role</label>
                <select class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" id="role_id" name="role_id" required>
                    <?php foreach ($roles_options as $role): ?>
                        <option value="<?= $role['id'] ?>"><?= htmlspecialchars($role['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
      </div>
      <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
        <button type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary text-base font-medium text-white hover:bg-primary-600 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm" id="save-user-btn">Simpan</button>
        <button type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm" onclick="closeModal('userModal')">Batal</button>
      </div>
    </div>
  </div>
</div>

<?php if (!$is_spa_request) { require_once PROJECT_ROOT . '/views/footer.php'; } ?>
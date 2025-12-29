<?php
// Handle AJAX Requests (Save/Delete/Get)
if (isset($_POST['action'])) {
    // Pastikan hanya admin yang bisa akses action ini
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    header('Content-Type: application/json');
    $conn = Database::getInstance()->getConnection();
    
    try {
        if ($_POST['action'] === 'get_role') {
            $id = (int)$_POST['id'];
            $stmt = $conn->prepare("SELECT * FROM roles WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $role = $stmt->get_result()->fetch_assoc();
            
            if ($role) {
                // Ambil permissions
                $stmt_perm = $conn->prepare("SELECT permission_id FROM role_permissions WHERE role_id = ?");
                $stmt_perm->bind_param("i", $id);
                $stmt_perm->execute();
                $res_perm = $stmt_perm->get_result();
                $perms = [];
                while($row = $res_perm->fetch_assoc()) {
                    $perms[] = (int)$row['permission_id'];
                }
                $role['permissions'] = $perms;

                // Ambil menus
                $stmt_menu = $conn->prepare("SELECT menu_key FROM role_menus WHERE role_id = ?");
                if ($stmt_menu) {
                    $stmt_menu->bind_param("i", $id);
                    $stmt_menu->execute();
                    $res_menu = $stmt_menu->get_result();
                    $menus = [];
                    while($row = $res_menu->fetch_assoc()) {
                        $menus[] = $row['menu_key'];
                    }
                    
                    // FIX: Jika Role Admin (ID 1) dan belum ada data di role_menus, anggap semua menu aktif
                    if ($id == 1 && empty($menus)) {
                        $all_menus_config = require PROJECT_ROOT . '/config/menus.php';
                        foreach ($all_menus_config as $m) {
                            if (isset($m['key'])) $menus[] = $m['key'];
                            if (isset($m['children'])) {
                                foreach ($m['children'] as $child) {
                                    if (isset($child['key'])) $menus[] = $child['key'];
                                }
                            }
                        }
                    }
                    $role['menus'] = $menus;
                }
            }
            
            echo json_encode(['success' => true, 'data' => $role]);
        }
        elseif ($_POST['action'] === 'save_role') {
            $id = $_POST['id'] ?? '';
            $name = $_POST['name'];
            $description = $_POST['description'];
            $permissions = $_POST['permissions'] ?? []; // Array ID permission
            $menus = $_POST['menus'] ?? []; // Array ID menu

            $conn->begin_transaction();

            if (!empty($id)) {
                // Update
                $stmt = $conn->prepare("UPDATE roles SET name = ?, description = ? WHERE id = ?");
                $stmt->bind_param("ssi", $name, $description, $id);
                if (!$stmt->execute()) {
                    throw new Exception("Gagal mengupdate role: " . $stmt->error);
                }
                $role_id = $id;
            } else {
                // Insert
                $stmt = $conn->prepare("INSERT INTO roles (name, description) VALUES (?, ?)");
                $stmt->bind_param("ss", $name, $description);
                if (!$stmt->execute()) {
                    throw new Exception("Gagal membuat role baru: " . $stmt->error);
                }
                $role_id = $conn->insert_id;
            }

            $role_id = (int)$role_id;
            // Update Permissions (Hapus semua lalu insert baru)
            $conn->query("DELETE FROM role_permissions WHERE role_id = $role_id");
            
            if (!empty($permissions)) {
                $stmt_p = $conn->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
                foreach ($permissions as $perm_id) {
                    $stmt_p->bind_param("ii", $role_id, $perm_id);
                    $stmt_p->execute();
                }
            }

            // Update Menus (Hapus semua lalu insert baru)
            $conn->query("DELETE FROM role_menus WHERE role_id = $role_id");
            
            if (!empty($menus)) {
                $stmt_m = $conn->prepare("INSERT INTO role_menus (role_id, menu_key) VALUES (?, ?)");
                if ($stmt_m) {
                    foreach ($menus as $menu_key) {
                        $stmt_m->bind_param("is", $role_id, $menu_key);
                        $stmt_m->execute();
                    }
                }
            }

            $conn->commit();
            echo json_encode(['success' => true]);
        }
        elseif ($_POST['action'] === 'delete_role') {
            $id = (int)$_POST['id'];
            // Cegah hapus role Admin (ID 1)
            if ($id == 1) {
                throw new Exception("Role Admin tidak dapat dihapus.");
            }
            
            $stmt = $conn->prepare("DELETE FROM roles WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            
            echo json_encode(['success' => true]);
        }
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// --- Bagian View ---

$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}

// Security check
check_permission('roles', 'menu');

// Fetch Data untuk View
$conn = Database::getInstance()->getConnection();
$roles_res = $conn->query("
    SELECT r.*, COUNT(u.id) as user_count 
    FROM roles r 
    LEFT JOIN users u ON r.id = u.role_id 
    GROUP BY r.id 
    ORDER BY r.id ASC
");
$perms_res = $conn->query("SELECT * FROM permissions ORDER BY slug ASC");
$menu_items = require PROJECT_ROOT . '/config/menus.php';
?>

<div id="roles-page-container">
    <div class="flex justify-between flex-wrap items-center pt-3 pb-2 mb-3 border-b border-gray-200 dark:border-gray-700">
        <h1 class="text-2xl font-semibold text-gray-800 dark:text-white flex items-center gap-2">
            <i class="bi bi-shield-lock"></i> Manajemen Role & Permission
        </h1>
        <button type="button" id="add-role-btn" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary hover:bg-primary-600 focus:outline-none">
            <i class="bi bi-plus-lg mr-2"></i> Tambah Role
        </button>
    </div>

    <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Role</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Deskripsi</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Jumlah User</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                <?php while($role = $roles_res->fetch_assoc()): ?>
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($role['name']) ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400"><?= htmlspecialchars($role['description']) ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-500 dark:text-gray-400">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                            <i class="bi bi-people-fill mr-1"></i> <?= $role['user_count'] ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <button data-action="edit-role" data-id="<?= $role['id'] ?>" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 mr-3">Edit</button>
                        <?php if($role['id'] != 1): ?>
                        <button data-action="delete-role" data-id="<?= $role['id'] ?>" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">Hapus</button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Role -->
<div id="roleModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeModal('roleModal')"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
            <form id="role-form">
                <input type="hidden" name="action" value="save_role">
                <input type="hidden" name="id" id="role-id">
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4" id="modal-title">Form Role</h3>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nama Role</label>
                        <input type="text" name="name" id="role-name" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Deskripsi</label>
                        <input type="text" name="description" id="role-desc" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm">
                    </div>

                    <div class="mb-4">
                        <div class="flex justify-between items-center mb-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Akses Menu (Sidebar)</label>
                            <label class="inline-flex items-center text-xs text-gray-500 dark:text-gray-400 cursor-pointer">
                                <input type="checkbox" id="select-all-menus" class="rounded border-gray-300 text-primary focus:ring-primary h-3 w-3 mr-1"> Pilih Semua
                            </label>
                        </div>
                        <div class="grid grid-cols-1 gap-2 max-h-60 overflow-y-auto border border-gray-200 dark:border-gray-700 rounded p-2">
                            <?php foreach ($menu_items as $item): ?>
                                <?php if ($item['type'] === 'header'): ?>
                                    <div class="mt-2 mb-1 text-xs font-bold text-gray-500 uppercase tracking-wider border-b border-gray-100 pb-1"><?= $item['label'] ?></div>
                                <?php elseif ($item['type'] === 'item' || $item['type'] === 'collapse'): ?>
                                    <div class="flex items-start ml-2">
                                        <div class="flex items-center h-5">
                                            <input id="menu_<?= $item['key'] ?>" name="menus[]" value="<?= $item['key'] ?>" type="checkbox" class="focus:ring-primary h-4 w-4 text-primary border-gray-300 rounded menu-parent-checkbox" data-key="<?= $item['key'] ?>">
                                        </div>
                                        <div class="ml-3 text-sm">
                                            <label for="menu_<?= $item['key'] ?>" class="font-medium text-gray-700 dark:text-gray-300"><i class="<?= $item['icon'] ?> mr-1"></i> <?= $item['label'] ?></label>
                                        </div>
                                    </div>
                                    <?php if ($item['type'] === 'collapse' && !empty($item['children'])): ?>
                                        <div class="ml-8 mt-1 space-y-1 border-l-2 border-gray-100 pl-2 menu-children-container">
                                            <?php foreach ($item['children'] as $child): ?>
                                                <div class="flex items-center">
                                                    <input id="menu_<?= $child['key'] ?>" name="menus[]" value="<?= $child['key'] ?>" type="checkbox" class="focus:ring-primary h-4 w-4 text-primary border-gray-300 rounded menu-child-checkbox" data-parent="<?= $item['key'] ?>">
                                                    <label for="menu_<?= $child['key'] ?>" class="ml-2 text-sm text-gray-600 dark:text-gray-400"><?= $child['label'] ?></label>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="mb-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Hak Akses (Permissions)</label>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2 max-h-60 overflow-y-auto border border-gray-200 dark:border-gray-700 rounded p-2">
                            <?php while($perm = $perms_res->fetch_assoc()): ?>
                            <div class="flex items-start">
                                <div class="flex items-center h-5">
                                    <input id="perm_<?= $perm['id'] ?>" name="permissions[]" value="<?= $perm['id'] ?>" type="checkbox" class="focus:ring-primary h-4 w-4 text-primary border-gray-300 rounded">
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="perm_<?= $perm['id'] ?>" class="font-medium text-gray-700 dark:text-gray-300"><?= htmlspecialchars($perm['name']) ?></label>
                                    <p class="text-xs text-gray-500 dark:text-gray-400"><?= htmlspecialchars($perm['slug']) ?></p>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" id="save-role-btn" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary text-base font-medium text-white hover:bg-primary-600 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">Simpan</button>
                    <button type="button" onclick="closeModal('roleModal')" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">Batal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>
<?php
// This file is included by header.php and contains the sidebar menu structure.
// Load menu configuration
$menu_items = require PROJECT_ROOT . '/config/menus.php';

// Fetch allowed menus for current user
$allowed_menus = [];
$is_admin = false;

// Cek apakah user adalah admin (berdasarkan role session atau role_id 1)
if ((isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'admin') || (isset($_SESSION['role_id']) && $_SESSION['role_id'] == 1)) {
    $is_admin = true;
}

// Jika bukan admin dan memiliki role_id, ambil menu yang diizinkan dari database
if (!$is_admin) {
    $role_id = $_SESSION['role_id'] ?? null;

    // Fallback: Jika role_id tidak ada di session, coba ambil dari DB berdasarkan username
    if (!$role_id && isset($_SESSION['username'])) {
        $conn = Database::getInstance()->getConnection();
        $stmt_u = $conn->prepare("SELECT role_id FROM users WHERE username = ?");
        $stmt_u->bind_param("s", $_SESSION['username']);
        $stmt_u->execute();
        $res_u = $stmt_u->get_result();
        if ($row_u = $res_u->fetch_assoc()) {
            $role_id = $row_u['role_id'];
            $_SESSION['role_id'] = $role_id; // Simpan ke session untuk request berikutnya
        }
    }

    if ($role_id) {
    $conn = Database::getInstance()->getConnection();
    $stmt = $conn->prepare("SELECT menu_key FROM role_menus WHERE role_id = ?");
        $stmt->bind_param("i", $role_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $allowed_menus[] = $row['menu_key'];
    }
}

function render_menu_item($url, $icon, $text) {
    echo '<a href="' . base_url($url) . '" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 group">
            <i class="' . $icon . ' mr-4 text-lg text-gray-500 dark:text-gray-400 group-hover:text-primary transition-colors"></i>
            <span>' . $text . '</span>
          </a>';
}

function render_collapsible_menu($id, $icon, $text, $items) {
    $items_html = '';
    $total = count($items);
    foreach ($items as $index => $item) {
        $is_last = ($index === $total - 1);
        // Garis vertikal: jika item terakhir, tingginya setengah (h-1/2) untuk membentuk sudut L
        $vertical_line_height = $is_last ? 'h-1/2' : 'h-full';
        
        $items_html .= '
        <div class="relative">
            <!-- Garis Vertikal -->
            <div class="absolute left-6 top-0 ' . $vertical_line_height . ' w-px bg-gray-300 dark:bg-gray-600"></div>
            <!-- Garis Horizontal -->
            <div class="absolute left-6 top-1/2 w-5 h-px bg-gray-300 dark:bg-gray-600"></div>
            
            <a href="' . base_url($item['url']) . '" class="flex items-center ml-11 px-3 py-2 text-sm font-normal rounded-lg text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700/50 hover:text-primary dark:hover:text-primary-400 transition-colors">
                ' . $item['label'] . '
            </a>
        </div>';
    }

    echo '<div data-controller="collapse">
            <button onclick="toggleCollapse(this)" class="w-full flex items-center justify-between px-4 py-3 text-sm font-medium rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 group">
                <span class="flex items-center">
                    <i class="' . $icon . ' mr-4 text-lg text-gray-500 dark:text-gray-400 group-hover:text-primary transition-colors"></i>
                    <span>' . $text . '</span>
                </span>
                <i class="bi bi-chevron-down transform transition-transform duration-200"></i>
            </button>
            <div class="collapse-content hidden">
                ' . $items_html . '
            </div>
          </div>';
}

function is_menu_allowed($key, $allowed_menus, $is_admin) {
    if ($is_admin) return true;
    return in_array($key, $allowed_menus);
}
?>

<!-- Menu Items -->
<?php foreach ($menu_items as $item): ?>
    <?php
    // Skip jika header
    if ($item['type'] === 'header') {
        echo '<div class="pt-4 pb-2 px-4"><p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">' . $item['label'] . '</p></div>';
        continue;
    }

    // Cek Permission
    if (!is_menu_allowed($item['key'], $allowed_menus, $is_admin ?? false)) {
        continue;
    }

    if ($item['type'] === 'item') {
        render_menu_item($item['url'], $item['icon'], $item['label']);
    } elseif ($item['type'] === 'collapse') {
        // Filter children based on permissions
        $visible_children = [];
        foreach ($item['children'] as $child) {
            if (is_menu_allowed($child['key'], $allowed_menus, $is_admin ?? false)) {
                $child['text'] = $child['label']; // Helper expects 'text'
                $visible_children[] = $child;
            }
        }
        
        // Only render parent if it has visible children
        if (!empty($visible_children)) {
            render_collapsible_menu($item['key'] . '-menu', $item['icon'], $item['label'], $visible_children);
        }
    }
    ?>
<?php endforeach; ?>
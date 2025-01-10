<?php
if (!defined('FUNCTIONS_INCLUDED')) {
    define('FUNCTIONS_INCLUDED', true);

function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function format_date($date) {
    return date('d/m/Y', strtotime($date));
}

function format_datetime($datetime) {
    return date('d/m/Y H:i', strtotime($datetime));
}

function format_money($amount) {
    return number_format($amount, 0, ',', '.') . ' đ';
}

function generate_pagination($current_page, $total_pages, $url) {
    $pagination = '';
    
    if ($total_pages > 1) {
        $pagination .= '<nav aria-label="Page navigation" class="mt-4">';
        $pagination .= '<ul class="inline-flex -space-x-px text-sm">';
        
        // Previous page
        if ($current_page > 1) {
            $pagination .= sprintf(
                '<li><a href="%s?page=%d" class="flex items-center justify-center px-3 h-8 ml-0 leading-tight text-gray-500 bg-white border border-gray-300 rounded-l-lg hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">Previous</a></li>',
                $url,
                $current_page - 1
            );
        }
        
        // Page numbers
        for ($i = 1; $i <= $total_pages; $i++) {
            if ($i == $current_page) {
                $pagination .= sprintf(
                    '<li><a aria-current="page" class="flex items-center justify-center px-3 h-8 text-blue-600 border border-gray-300 bg-blue-50 hover:bg-blue-100 hover:text-blue-700 dark:border-gray-700 dark:bg-gray-700 dark:text-white">%d</a></li>',
                    $i
                );
            } else {
                $pagination .= sprintf(
                    '<li><a href="%s?page=%d" class="flex items-center justify-center px-3 h-8 leading-tight text-gray-500 bg-white border border-gray-300 hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">%d</a></li>',
                    $url,
                    $i,
                    $i
                );
            }
        }
        
        // Next page
        if ($current_page < $total_pages) {
            $pagination .= sprintf(
                '<li><a href="%s?page=%d" class="flex items-center justify-center px-3 h-8 leading-tight text-gray-500 bg-white border border-gray-300 rounded-r-lg hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">Next</a></li>',
                $url,
                $current_page + 1
            );
        }
        
        $pagination .= '</ul></nav>';
    }
    
    return $pagination;
}

function upload_file($file, $target_dir = 'uploads/') {
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $target_file = $target_dir . basename($file["name"]);
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
    
    // Check if file already exists
    if (file_exists($target_file)) {
        $filename = pathinfo($file["name"], PATHINFO_FILENAME);
        $i = 1;
        while(file_exists($target_dir . $filename . '_' . $i . '.' . $imageFileType)) {
            $i++;
        }
        $target_file = $target_dir . $filename . '_' . $i . '.' . $imageFileType;
    }
    
    // Check file size
    if ($file["size"] > 5000000) {
        throw new Exception("File quá lớn. Kích thước tối đa là 5MB.");
    }
    
    // Allow certain file formats
    $allowed_types = array('jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx');
    if (!in_array($imageFileType, $allowed_types)) {
        throw new Exception("Chỉ cho phép tải lên các file: " . implode(', ', $allowed_types));
    }
    
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return $target_file;
    } else {
        throw new Exception("Có lỗi xảy ra khi tải file lên.");
    }
}

function delete_file($file_path) {
    if (file_exists($file_path)) {
        unlink($file_path);
        return true;
    }
    return false;
}

function export_excel($data, $filename) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="' . $filename . '.xls"');
    header('Cache-Control: max-age=0');
    
    echo '<table border="1">';
    
    // Headers
    if (!empty($data)) {
        echo '<tr>';
        foreach (array_keys($data[0]) as $key) {
            echo '<th>' . htmlspecialchars($key) . '</th>';
        }
        echo '</tr>';
    }
    
    // Data
    foreach ($data as $row) {
        echo '<tr>';
        foreach ($row as $cell) {
            echo '<td>' . htmlspecialchars($cell) . '</td>';
        }
        echo '</tr>';
    }
    
    echo '</table>';
    exit;
}

function log_activity($ip, $user, $action, $result, $details = '') {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("INSERT INTO log (IP, NguoiDung, HanhDong, KetQua, ChiTiet, NgayTao) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("sssss", $ip, $user, $action, $result, $details);
    $stmt->execute();
}

function send_notification($user_id, $title, $message) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("INSERT INTO thongbao (NguoiDungId, TieuDe, NoiDung) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user_id, $title, $message);
    return $stmt->execute();
}

function get_user_notifications($user_id, $limit = 5) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT * FROM thongbao WHERE NguoiDungId = ? ORDER BY NgayTao DESC LIMIT ?");
    $stmt->bind_param("ii", $user_id, $limit);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function mark_notification_as_read($notification_id) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("UPDATE thongbao SET DaDoc = 1 WHERE Id = ?");
    $stmt->bind_param("i", $notification_id);
    return $stmt->execute();
}

} // End of guard

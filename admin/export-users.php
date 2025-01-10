<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Redirect if not logged in or not an admin
if (!is_user_logged_in() || !is_admin()) {
    header('HTTP/1.1 403 Forbidden');
    exit('Unauthorized');
}

$export = export_users_csv();
if ($export) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $export['filename'] . '"');
    echo $export['content'];
} else {
    header('HTTP/1.1 500 Internal Server Error');
    echo 'Error generating export';
}
?>

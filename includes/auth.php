<?php
// includes/auth.php — Cek sesi admin
session_start();

function requireAdmin(): void {
    if (empty($_SESSION['admin_logged_in'])) {
        header('Location: ' . getAdminBase() . 'login.php');
        exit;
    }
}

function isAdminLoggedIn(): bool {
    return !empty($_SESSION['admin_logged_in']);
}

function getAdminBase(): string {
    // Deteksi path admin secara dinamis
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    if (str_contains($script, '/admin/')) {
        return '/ARIWEB/admin/';
    }
    return '/ARIWEB/admin/';
}

<?php
// includes/auth.php — Cek sesi admin
session_start();

function requireAdmin(): void {
    if (empty($_SESSION['admin_logged_in'])) {
        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        if (str_contains($script, '/admin/')) {
            header('Location: login.php');
        } else {
            header('Location: admin/login.php');
        }
        exit;
    }
}

function isAdminLoggedIn(): bool {
    return !empty($_SESSION['admin_logged_in']);
}

function getAdminBase(): string {
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    if (str_contains($script, '/ARIWEB/')) {
        return '/ARIWEB/admin/';
    }
    return '/admin/';
}


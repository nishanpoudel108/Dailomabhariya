<?php
require_once __DIR__ . '/includes/session.php';
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>दैलोमा भरिया | System Access Portal</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .login-wrap { display: flex; align-items: center; justify-content: center; min-height: 100vh; background: #f3f4f6; }
        .login-box { width: 100%; max-width: 400px; background: white; padding: 2.5rem; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 1.2rem; }
        .form-group label { display: block; margin-bottom: 0.4rem; font-weight: 500; }
        .form-control { width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; outline: none; }
        .form-control:focus { border-color: var(--primary-color); }
        .btn-block { width: 100%; padding: 0.75rem; background: var(--primary-color); border: none; color: white; border-radius: 6px; font-weight: bold; cursor: pointer; }
    </style>
</head>
<body>

<div class="login-wrap">
    <div class="login-box">
        <h2 style="text-align: center; margin-bottom: 0.5rem; color: var(--primary-dark);">Sign In</h2>
        <p style="text-align: center; color: #6b7280; font-size: 0.9rem; margin-bottom: 2rem;">Access your tracking and groceries dashboard</p>
        
        <div id="err-banner" style="display:none; background:#fee2e2; color:#b91c1c; padding:0.75rem; border-radius:6px; margin-bottom:1rem; font-size:0.9rem;"></div>

        <form id="loginForm">
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" id="email" class="form-control" required autocomplete="username">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" id="password" class="form-control" required autocomplete="current-password">
            </div>
            <button type="submit" class="btn-block">Authenticate System Access</button>
        </form>
        <p style="text-align: center; margin-top: 1.5rem; font-size: 0.9rem;">New account? <a href="register.php" style="color: var(--primary-color);">Create Profile</a></p>
    </div>
</div>

<script src="assets/js/app.js"></script>
<script>
document.getElementById('loginForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const errBanner = document.getElementById('err-banner');
    errBanner.style.display = 'none';

    try {
        const response = await fetch('api/auth.php?action=login', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                email: document.getElementById('email').value,
                password: document.getElementById('password').value
            })
        });
        const res = await response.json();
        
        if (res.success) {
            window.location.href = res.role === 'Super Admin' ? 'admin/index.php' : 'customer/index.php';
        } else {
            errBanner.innerText = res.message;
            errBanner.style.display = 'block';
        }
    } catch (err) {
        errBanner.innerText = "Processing communication pipeline failure.";
        errBanner.style.display = 'block';
    }
});
</script>
</body>
</html>
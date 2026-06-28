<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/config/environment.php';

if (!mynak_allow_dev_debug_tools()) {
    http_response_code(404);
    exit('Not found.');
}

echo "<h2>Process Form Test</h2>";
?>

<form method="POST" action="ajax/simple_process_form.php">
    <input type="hidden" name="form_id" value="1">
    <input type="hidden" name="popup_form" value="1">
    
    <label>Ad: <input type="text" name="name" value="Test User" required></label><br><br>
    <label>Email: <input type="email" name="email" value="test@example.com" required></label><br><br>
    <label>Telefon: <input type="tel" name="phone" value="0555 123 45 67"></label><br><br>
    <label>Mesaj: <textarea name="message">Test mesajı</textarea></label><br><br>
    
    <button type="submit">Form Gönder</button>
</form>

<p><a href="view_logs.php">Logları Görüntüle</a></p>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
form { background: #f9f9f9; padding: 20px; border-radius: 5px; }
label { display: block; margin: 10px 0; }
input, textarea { width: 300px; padding: 8px; }
button { padding: 10px 20px; background: #007cba; color: white; border: none; border-radius: 5px; }
</style>

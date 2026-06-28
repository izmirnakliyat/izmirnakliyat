<?php
/**
 * AI Makale Cron Çalıştırıcı
 * cPanel Cron: 0 9,18 * * * php /home/kullanici/public_html/cron/run-ai-tasks.php
 */
@set_time_limit(600);
define('CRON_RUN', true);

// CLI veya web üzerinden çalışabilir
$base = dirname(__DIR__);
require_once $base . '/config/config.php';
require_once $base . '/config/db.php';
require_once $base . '/admin/includes/auto_blog_functions.php';

$log  = [];
$now  = date('Y-m-d H:i:s');
$log[] = "[{$now}] Cron başladı.";

ensure_auto_blog_tasks_table();

// Zamanı gelmiş aktif görevleri al
$result = $conn->query("SELECT * FROM auto_blog_tasks WHERE status = 1 AND (next_run IS NULL OR next_run <= NOW())");
if (!$result || $result->num_rows === 0) {
    $log[] = "Çalıştırılacak görev yok.";
    output($log); exit;
}

$tasks = $result->fetch_all(MYSQLI_ASSOC);
$log[] = count($tasks) . " görev bulundu.";

foreach ($tasks as $task) {
    $log[] = "▶ Görev: {$task['name']} (ID:{$task['id']})";

    $all_topics = array_values(array_filter(array_map('trim', explode("\n", $task['topics']))));
    if (empty($all_topics)) {
        $log[] = "  ⚠ Konu yok, atlandı.";
        continue;
    }
    shuffle($all_topics);
    $count = min((int)$task['articles_per_run'], count($all_topics));

    for ($i = 0; $i < $count; $i++) {
        $topic  = $all_topics[$i];
        $result = generate_blog_post_from_task($task, $topic);
        if ($result['success']) {
            $log[] = "  ✅ \"{$result['title']}\" (ID:{$result['post_id']})";
        } else {
            $log[] = "  ❌ Hata [{$topic}]: {$result['message']}";
        }
        sleep(1); // API rate limit için
    }

    // Güncelle
    $next_run = calculate_next_run($task['schedule']);
    $stmt = $conn->prepare("UPDATE auto_blog_tasks SET last_run=NOW(), next_run=? WHERE id=?");
    $stmt->bind_param("si", $next_run, $task['id']);
    $stmt->execute();
    $log[] = "  ⏭ Sonraki çalışma: {$next_run}";
}

$log[] = "[" . date('Y-m-d H:i:s') . "] Cron tamamlandı.";
output($log);

function output($lines) {
    $text = implode(PHP_EOL, $lines) . PHP_EOL;
    // Cron log dosyasına yaz
    $log_file = dirname(__DIR__) . '/logs/cron_auto_blog.log';
    if (!is_dir(dirname($log_file))) @mkdir(dirname($log_file), 0755, true);
    file_put_contents($log_file, $text, FILE_APPEND);
    // CLI'da ekrana da bas
    if (php_sapi_name() === 'cli') echo $text;
    // Web üzerinden JSON döndür
    if (!defined('CRON_RUN') || php_sapi_name() !== 'cli') {
        header('Content-Type: application/json');
        echo json_encode(['log' => $lines]);
    }
}

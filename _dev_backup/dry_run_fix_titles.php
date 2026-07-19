<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

// UTF-8 fix
header('Content-Type: text/plain; charset=utf-8');

echo "Analyzing duplicate titles for potential fixes...\n\n";

// Helper for title case
function slugToTitle($slug)
{
    $title = str_replace('-', ' ', $slug);
    $title = mb_convert_case($title, MB_CASE_TITLE, "UTF-8");
    // Manual fixes for Turkish chars if mb_convert_case misses (it usually works for UTF-8)
    return $title;
}

// 1. Find duplicates
$sql = "SELECT baslik, COUNT(*) as count, GROUP_CONCAT(id) as ids 
        FROM blog_posts 
        WHERE durum = 1 
        GROUP BY baslik 
        HAVING count > 1";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "Duplicate Title: [" . $row['baslik'] . "] (Count: " . $row['count'] . ")\n";
        $ids = explode(',', $row['ids']);

        foreach ($ids as $id) {
            $post_res = $conn->query("SELECT id, baslik, slug, icerik FROM blog_posts WHERE id = $id");
            $post = $post_res->fetch_assoc();

            echo "  - ID: " . $post['id'] . "\n";
            echo "    Slug: " . $post['slug'] . "\n";

            // Try to suggest a new title
            $suggestion = "";
            $clean_content = strip_tags($post['icerik']);
            $lines = explode("\n", trim($clean_content));
            $first_line = trim($lines[0]);

            // 1. Check for "Başlık:" prefix
            if (preg_match('/^Başlık:\s*(.+)$/i', $first_line, $match)) {
                $suggestion = $match[1];
            }
            // 2. Check if first line resembles a Title (Length 10-100, no http)
            elseif (strlen($first_line) > 10 && strlen($first_line) < 100 && $first_line !== $post['baslik'] && strpos($first_line, 'http') === false) {
                $suggestion = $first_line;
            }
            // 3. Generate from Slug
            else {
                $slug_title = slugToTitle($post['slug']);

                // If slug title is significantly different from current title
                if (levenshtein($slug_title, $post['baslik']) > 3) {
                    $suggestion = $slug_title;
                }
                // 4. Append ID as last resort
                else {
                    $suggestion = $post['baslik'] . " (" . $post['id'] . ")";
                }
            }

            // Final check
            $suggestion = trim($suggestion);
            if (empty($suggestion)) {
                $suggestion = $post['baslik'] . " " . $post['id'];
            }

            echo "    Current: " . $post['baslik'] . "\n";
            echo "    Suggest: " . $suggestion . "\n";
        }
        echo "\n";
    }
} else {
    echo "No duplicate titles found.\n";
}
?>
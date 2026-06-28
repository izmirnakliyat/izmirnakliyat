<?php
// Set the content type to CSS
header('Content-Type: text/css');

// Database connection
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';

// Cache for 1 hour (reduce server load)
header('Cache-Control: max-age=3600');

// Get color settings from database
$primary_color = '#0046AD';   // Default value
$secondary_color = '#79B32A'; // Default value
$text_color = '#333333';      // Default value

$colors_result = $conn->query("SELECT name, value FROM settings WHERE name IN ('primary_color', 'secondary_color', 'text_color')");
if ($colors_result && $colors_result->num_rows > 0) {
    while ($row = $colors_result->fetch_assoc()) {
        switch ($row['name']) {
            case 'primary_color':
                if (!empty($row['value'])) {
                    $primary_color = $row['value'];
                }
                break;
            case 'secondary_color':
                if (!empty($row['value'])) {
                    $secondary_color = $row['value'];
                }
                break;
            case 'text_color':
                if (!empty($row['value'])) {
                    $text_color = $row['value'];
                }
                break;
        }
    }
}
?>

/* Dynamic CSS Variables */
:root {
    --primary-color: <?php echo $primary_color; ?>;
    --secondary-color: <?php echo $secondary_color; ?>;
    --text-color: <?php echo $text_color; ?>;
}

/* You can add additional styles that use these variables here if needed */ 
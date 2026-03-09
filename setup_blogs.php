<?php
require 'includes/db.php';

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS blogs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        category TEXT NOT NULL,
        image_path TEXT DEFAULT 'https://via.placeholder.com/600x400',
        content TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    echo "<h1>Blogs Table Created</h1>";
    
    // Insert dummy data if empty
    $count = $pdo->query("SELECT COUNT(*) FROM blogs")->fetchColumn();
    if ($count == 0) {
        $stmt = $pdo->prepare("INSERT INTO blogs (title, category, content) VALUES (?, ?, ?)");
        $stmt->execute([
            'Why Pre-Owned Luxury is the Future', 
            'Sustainability', 
            'Exploring the environmental impact of fashion and how recommerce is changing the game.'
        ]);
        $stmt->execute([
            'How to Authenticate Designer Bags', 
            'Guides', 
            'Tips and tricks from our expert authenticators on what to look for when buying vintage.'
        ]);
        $stmt->execute([
            'Styling Your Home with Vintage Furniture', 
            'Lifestyle', 
            'Create a unique space that tells a story with our curated furniture collection.'
        ]);
        echo "<p>Initialized with sample data.</p>";
    } else {
        echo "<p>Table already exists and has data.</p>";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

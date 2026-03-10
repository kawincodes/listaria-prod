<?php
require 'includes/db.php';
require_once __DIR__ . '/includes/session.php';
include 'includes/header.php';
?>

<div class="container" style="margin-top: 3rem; margin-bottom: 5rem;">
    <div style="text-align: center; max-width: 800px; margin: 0 auto;">
        <h1 style="font-size: 2.5rem; margin-bottom: 1rem;">Our Blog</h1>
        <p style="color: #666; font-size: 1.1rem; line-height: 1.6;">
            Discover the latest trends in luxury recommerce, sustainability, and style guides from the Listaria team.
        </p>
    </div>

    <style>
        .blog-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2rem;
            margin-top: 4rem;
        }
        @media (max-width: 900px) {
            .blog-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 600px) {
            .blog-grid { 
                grid-template-columns: repeat(2, 1fr); 
                gap: 1rem;
            }
            /* Adjust card content for small 2-column layout */
            .blog-card img {
                height: 140px !important;
            }
            .blog-card > div {
                padding: 1rem !important;
            }
            .blog-card h3 {
                font-size: 1rem !important;
                margin-bottom: 0.5rem !important;
            }
            .blog-card p {
                font-size: 0.8rem !important;
                line-height: 1.4 !important;
                display: -webkit-box;
                -webkit-line-clamp: 3;
                -webkit-box-orient: vertical;
                overflow: hidden;
            }
        }
    </style>

    <div class="blog-grid">
        
        <?php
        $stmt = $pdo->query("SELECT * FROM blogs ORDER BY created_at DESC");
        $blogs = $stmt->fetchAll();

        foreach ($blogs as $blog):
        ?>
            <!-- Blog Post -->
            <a href="blog_details.php?id=<?php echo $blog['id']; ?>" class="blog-card" style="border: 1px solid #eee; border-radius: 12px; overflow: hidden; transition: transform 0.2s; display: block; text-decoration: none; color: inherit;">
                <img src="<?php echo htmlspecialchars($blog['image_path']); ?>" alt="<?php echo htmlspecialchars($blog['title']); ?>" style="width: 100%; height: 200px; object-fit: cover;">
                <div style="padding: 1.5rem;">
                    <div style="font-size: 0.8rem; color: var(--brand-color); font-weight: 600; margin-bottom: 0.5rem; text-transform:uppercase;"><?php echo htmlspecialchars($blog['category']); ?></div>
                    <h3 style="margin: 0 0 1rem; color: #1a1a1a;"><?php echo htmlspecialchars($blog['title']); ?></h3>
                    <p style="color: #777; font-size: 0.9rem; line-height: 1.5;">
                        <?php echo substr(htmlspecialchars($blog['content']), 0, 100) . '...'; ?>
                    </p>
                    <span style="display: inline-block; margin-top: 1rem; color: #333; font-weight: 600;">Read More &rarr;</span>
                </div>
            </a>
        <?php endforeach; ?>
        
        <?php if(count($blogs) == 0): ?>
            <p style="text-align:center; grid-column:1/-1;">No blogs found.</p>
        <?php endif; ?>

    </div>
</div>

<?php include 'includes/footer.php'; ?>

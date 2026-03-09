<?php
require 'includes/db.php';
session_start();

$id = $_GET['id'] ?? 0;

if(!$id || !is_numeric($id)) {
    header("Location: blogs.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM blogs WHERE id = ?");
$stmt->execute([$id]);
$blog = $stmt->fetch();

if (!$blog) {
    header("Location: blogs.php");
    exit;
}

// Fetch Random Related Blogs
$relatedStmt = $pdo->prepare("SELECT * FROM blogs WHERE id != ? ORDER BY RANDOM() LIMIT 2");
$relatedStmt->execute([$id]);
$relatedBlogs = $relatedStmt->fetchAll();

include 'includes/header.php';
?>

<style>
    /* Professional Blog Theme */
    :root {
        --blog-accent: #6B21A8;
        --blog-bg: #ffffff;
        --blog-text: #334155;
        --blog-heading: #1e293b;
    }

    body {
        background-color: #f8fafc; /* Light aesthetic background */
    }

    .blog-details-container {
        max-width: 900px;
        margin: 0 auto;
        padding-bottom: 5rem;
    }

    .blog-hero {
        position: relative;
        height: 450px;
        width: 100%;
        margin-bottom: -100px; /* Overlap effect */
    }

    .blog-hero-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        /* mask-image: linear-gradient(to bottom, black 50%, transparent 100%); */
    }
    
    .blog-hero-overlay {
        position: absolute;
        top: 0; left: 0; width: 100%; height: 100%;
        background: linear-gradient(to bottom, rgba(0,0,0,0.2) 0%, rgba(0,0,0,0.6) 100%);
    }

    .article-card {
        background: white;
        border-radius: 24px;
        padding: 3rem;
        box-shadow: 0 20px 40px rgba(0,0,0,0.06);
        position: relative;
        z-index: 10;
        margin: 0 1.5rem;
    }

    .category-pill {
        background: #f3e8ff;
        color: #7e22ce;
        padding: 6px 14px;
        border-radius: 50px;
        font-weight: 700;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: inline-block;
        margin-bottom: 1rem;
    }

    .article-title {
        font-size: 2.8rem;
        line-height: 1.1;
        color: var(--blog-heading);
        margin: 0 0 1.5rem 0;
        font-weight: 800;
        letter-spacing: -0.02em;
    }

    .author-meta {
        display: flex;
        align-items: center;
        gap: 12px;
        border-bottom: 1px solid #e2e8f0;
        padding-bottom: 2rem;
        margin-bottom: 2rem;
    }

    .author-avatar {
        width: 48px;
        height: 48px;
        background: #e2e8f0;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        color: #64748b;
        font-size: 1.2rem;
    }

    .author-info h4 {
        margin: 0;
        font-size: 1rem;
        color: #0f172a;
        font-weight: 700;
    }

    .author-info span {
        font-size: 0.85rem;
        color: #64748b;
    }

    .article-content {
        font-size: 1.15rem;
        line-height: 1.8;
        color: #334155;
        font-family: 'Georgia', serif; /* Editorial feel */
        text-align: justify;
        word-wrap: break-word;
        overflow-wrap: break-word;
        hyphens: auto;
    }
    
    .article-content p {
        margin-bottom: 1.5rem;
    }

    .share-section {
        margin-top: 3rem;
        padding-top: 2rem;
        border-top: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .share-btn {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 10px 20px;
        border-radius: 50px;
        border: 1px solid #e2e8f0;
        background: white;
        color: #475569;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        text-decoration: none;
        font-size: 0.9rem;
    }

    .share-btn:hover {
        background: #f1f5f9;
        color: #0f172a;
    }
    
    /* Toast Notification */
    #toast {
        visibility: hidden;
        min-width: 250px;
        background-color: #333;
        color: #fff;
        text-align: center;
        border-radius: 8px;
        padding: 12px;
        position: fixed;
        z-index: 1000;
        left: 50%;
        bottom: 30px;
        transform: translateX(-50%);
        font-size: 0.9rem;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    }
    
    #toast.show {
        visibility: visible;
        animation: fadein 0.5s, fadeout 0.5s 2.5s;
    }

    @keyframes fadein {
        from {bottom: 0; opacity: 0;}
        to {bottom: 30px; opacity: 1;}
    }

    @keyframes fadeout {
        from {bottom: 30px; opacity: 1;}
        to {bottom: 0; opacity: 0;}
    }

    /* Related Section */
    .related-section {
        margin-top: 4rem;
        padding: 0 1.5rem;
    }
    
    .section-heading {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .related-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.5rem;
    }

    .related-card {
        background: white;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        text-decoration: none;
        color: inherit;
        transition: transform 0.2s;
        display: block;
    }

    .related-card:hover {
        transform: translateY(-4px);
    }

    .related-img {
        height: 160px;
        width: 100%;
        object-fit: cover;
    }
    
    .related-content {
        padding: 1.25rem;
    }

    .back-nav {
        position: absolute;
        top: 20px;
        left: 20px;
        z-index: 20;
    }
    
    .btn-back-circle {
        width: 44px; height: 44px;
        background: rgba(255,255,255,0.9);
        backdrop-filter: blur(5px);
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        color: #1e293b;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        text-decoration: none;
        font-size: 1.25rem;
        transition: transform 0.2s;
    }
    
    .btn-back-circle:hover {
        transform: scale(1.05);
    }

    /* Responsive */
    @media (max-width: 768px) {
        .blog-hero { height: 350px; }
        .article-card {
            padding: 1.5rem;
            margin: 0 1rem;
            margin-top: -60px; /* Pull up more on mobile */
        }
        .article-title { font-size: 1.8rem; }
        .blog-details-container { padding-bottom: 2rem; }
        .related-grid { grid-template-columns: 1fr; }
    }
</style>

<div class="blog-details-container">
    
    <!-- Hero Image Background -->
    <div class="blog-hero">
        <div class="back-nav">
            <a href="blogs.php" class="btn-back-circle"><ion-icon name="arrow-back"></ion-icon></a>
        </div>
        <img src="<?php echo htmlspecialchars($blog['image_path']); ?>" class="blog-hero-img" alt="Cover Image">
        <div class="blog-hero-overlay"></div>
    </div>

    <!-- Main Content Card -->
    <div class="article-card">
        <div class="category-pill"><?php echo htmlspecialchars($blog['category']); ?></div>
        
        <h1 class="article-title"><?php echo htmlspecialchars($blog['title']); ?></h1>
        
        <!-- Author / Meta -->
        <div class="author-meta">
            <div class="author-avatar">L</div>
            <div class="author-info">
                <h4>Listaria Team</h4>
                <span><?php echo date('F j, Y', strtotime($blog['created_at'])); ?> &bull; 5 min read</span>
            </div>
        </div>

        <!-- Professional Content Typography -->
        <div class="article-content">
            <?php 
                // Basic formatting: ensure paragraphs are respected
                echo nl2br(htmlspecialchars($blog['content'])); 
            ?>
        </div>

        <!-- Share Section -->
        <div class="share-section">
            <span style="font-weight: 600; color: #64748b;">Share this article</span>
            <div style="display:flex; gap:10px;">
                <button class="share-btn" onclick="copyLink()">
                    <ion-icon name="link-outline"></ion-icon> Copy Link
                </button>
            </div>
        </div>
    </div>
    
    <!-- Toast -->
    <div id="toast">Link copied to clipboard!</div>

    <!-- Related Articles -->
    <?php if(count($relatedBlogs) > 0): ?>
    <div class="related-section">
        <div class="section-heading">
            <ion-icon name="albums-outline" style="color:var(--blog-accent);"></ion-icon>
            You might also like
        </div>
        <div class="related-grid">
            <?php foreach($relatedBlogs as $rb): ?>
            <a href="blog_details.php?id=<?php echo $rb['id']; ?>" class="related-card">
                <img src="<?php echo htmlspecialchars($rb['image_path']); ?>" class="related-img" alt="Related">
                <div class="related-content">
                    <div style="font-size:0.75rem; color:#6B21A8; font-weight:700; text-transform:uppercase; margin-bottom:5px;">
                        <?php echo htmlspecialchars($rb['category']); ?>
                    </div>
                    <h3 style="margin:0; font-size:1.1rem; color:#1e293b; line-height:1.3;">
                        <?php echo htmlspecialchars($rb['title']); ?>
                    </h3>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

</div>

<script>
function copyLink() {
    const link = window.location.href;
    const title = document.title;
    
    // 1. Try Native Share API (Mobile)
    if (navigator.share) {
        navigator.share({
            title: title,
            text: 'Check out this article on Listaria:',
            url: link
        })
        .then(() => console.log('Successful share'))
        .catch((error) => console.log('Error sharing', error));
        return; // Exit if share API works (or is opened)
    }

    // 2. Fallback to Clipboard (Desktop / HTTP)
    if (navigator.clipboard && window.isSecureContext) {
        // Secure context (HTTPS)
        navigator.clipboard.writeText(link).then(() => {
            showToast();
        });
    } else {
        // Fallback for HTTP
        const textArea = document.createElement("textarea");
        textArea.value = link;
        textArea.style.position = "fixed";
        textArea.style.left = "-9999px";
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        
        try {
            document.execCommand('copy');
            showToast();
        } catch (err) {
            console.error('Fallback: Oops, unable to copy', err);
            alert('Unable to copy link manually. Please copy the URL from the address bar.');
        }
        
        document.body.removeChild(textArea);
    }
}

function showToast() {
    var x = document.getElementById("toast");
    x.className = "show";
    setTimeout(function(){ x.className = x.className.replace("show", ""); }, 3000);
}
</script>

<?php include 'includes/footer.php'; ?>

<?php
require 'includes/db.php';
session_start();

// Fetch Content
$stmt = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = 'refund_policy'");
$stmt->execute();
$text = $stmt->fetchColumn();

if (!$text) {
    $text = "Refund Policy content not available.";
}

// Reuse policy parser logic
function parseLegalDoc($text) {
    $lines = explode("\n", $text);
    $sections = [];
    $contentHtml = '';
    $inList = false;

    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;

        // Section Headers (e.g., "1. Eligibility")
        if (preg_match('/^(\d+)\.\s+(.*)/', $line, $matches)) {
            if ($inList) { $contentHtml .= '</ul>'; $inList = false; }
            $id = 'section-' . $matches[1];
            $title = $matches[2];
            $sections[] = ['id' => $id, 'title' => $matches[1] . '. ' . $title];
            $contentHtml .= '<h2 id="' . $id . '" class="legal-section-title">' . htmlspecialchars($line) . '</h2>';
        }
        // Emoji Headers (e.g., "✅ Eligible Returns")
        elseif (preg_match('/^[^\x00-\x7F].*/u', $line)) {
            if ($inList) { $contentHtml .= '</ul>'; $inList = false; }
            $contentHtml .= '<h2 class="legal-section-title-emoji">' . htmlspecialchars($line) . '</h2>';
        }
        // Bullets
        elseif (strpos($line, '•') === 0 || strpos($line, '-') === 0 || strpos($line, '*') === 0) {
            if (!$inList) { $contentHtml .= '<ul class="legal-list">'; $inList = true; }
            $cleanLine = ltrim($line, '•-* ');
            $contentHtml .= '<li>' . htmlspecialchars($cleanLine) . '</li>';
        }
        // Regular Text
        else {
            if ($inList) { $contentHtml .= '</ul>'; $inList = false; }
            
            if (stripos($line, 'Effective Date:') === 0 || stripos($line, 'Last Updated:') === 0) {
                $contentHtml .= '<div class="legal-meta-badge">' . htmlspecialchars($line) . '</div>';
            }
            elseif (stripos($line, 'Refund') !== false && strlen($line) < 50) {
                 $contentHtml .= '<h1 class="legal-main-title">' . htmlspecialchars($line) . '</h1>';
            }
            else {
                $contentHtml .= '<p class="legal-paragraph">' . htmlspecialchars($line) . '</p>';
            }
        }
    }
    if ($inList) { $contentHtml .= '</ul>'; }
    return ['sections' => $sections, 'html' => $contentHtml];
}

$parsed = parseLegalDoc($text);

include 'includes/header.php';
?>

<div class="legal-page-container">
    <div class="legal-header-banner">
        <div class="container-inner">
            <nav class="breadcrumb">
                <a href="index.php">Home</a>
                <ion-icon name="chevron-forward-outline"></ion-icon>
                <span>Legal</span>
                <ion-icon name="chevron-forward-outline"></ion-icon>
                <span class="active">Refund & Return Policy</span>
            </nav>
        </div>
    </div>

    <div class="legal-content-grid container-inner">
        <!-- Sidebar Navigation -->
        <aside class="legal-sidebar">
            <div class="sticky-sidebar">
                <h4 class="sidebar-title">Contents</h4>
                <nav class="sidebar-nav">
                    <?php foreach ($parsed['sections'] as $sec): ?>
                        <a href="#<?php echo $sec['id']; ?>" class="nav-item"><?php echo htmlspecialchars($sec['title']); ?></a>
                    <?php endforeach; ?>
                </nav>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="legal-main-content">
            <div class="legal-paper">
                <?php echo $parsed['html']; ?>
            </div>
        </main>
    </div>
</div>

<style>
    :root {
        --legal-bg: #f8fafc;
        --paper-bg: #ffffff;
        --brand-purple: #6B21A8;
        --text-main: #1e293b;
        --text-muted: #64748b;
        --border-color: #e2e8f0;
    }

    .legal-page-container {
        background-color: var(--legal-bg);
        min-height: 100vh;
        padding-top: 80px;
        font-family: 'Inter', sans-serif;
    }

    .container-inner {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    }

    .legal-header-banner {
        padding: 2rem 0;
        background: white;
        border-bottom: 1px solid var(--border-color);
        margin-bottom: 3rem;
    }

    .breadcrumb {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.9rem;
        color: var(--text-muted);
    }

    .breadcrumb a {
        text-decoration: none;
        color: var(--brand-purple);
        font-weight: 500;
    }

    .breadcrumb .active {
        color: var(--text-main);
        font-weight: 600;
    }

    .legal-content-grid {
        display: grid;
        grid-template-columns: 280px 1fr;
        gap: 3rem;
        align-items: start;
        padding-bottom: 6rem;
    }

    /* Sidebar Styles */
    .sticky-sidebar {
        position: sticky;
        top: 100px;
        background: white;
        padding: 1.5rem;
        border-radius: 16px;
        border: 1px solid var(--border-color);
        box-shadow: 0 4px 12px rgba(0,0,0,0.03);
    }

    .sidebar-title {
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: var(--text-muted);
        margin-top: 0;
        margin-bottom: 1.5rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid var(--border-color);
    }

    .sidebar-nav {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .nav-item {
        color: var(--text-muted);
        text-decoration: none;
        font-size: 0.95rem;
        padding: 8px 12px;
        border-radius: 8px;
        transition: all 0.2s;
        font-weight: 500;
    }

    .nav-item:hover {
        background: #f3f4f6;
        color: var(--brand-purple);
    }

    /* Main Content Styles */
    .legal-paper {
        background: white;
        padding: 4rem;
        border-radius: 20px;
        border: 1px solid var(--border-color);
        box-shadow: 0 10px 40px rgba(0,0,0,0.03);
    }

    .legal-main-title {
        font-size: 2.5rem;
        font-weight: 800;
        color: #0f172a;
        margin-bottom: 1rem;
        letter-spacing: -1px;
    }

    .legal-meta-badge {
        display: inline-block;
        background: #f1f5f9;
        color: var(--text-muted);
        padding: 6px 16px;
        border-radius: 100px;
        font-size: 0.9rem;
        font-weight: 600;
        margin-bottom: 3rem;
    }

    .legal-section-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: #0f172a;
        margin-top: 3rem;
        margin-bottom: 1.25rem;
        scroll-margin-top: 120px;
    }

    .legal-section-title-emoji {
        font-size: 1.8rem;
        font-weight: 700;
        margin-top: 4rem;
        margin-bottom: 1.5rem;
        color: #0f172a;
        border-bottom: 2px solid #f1f5f9;
        padding-bottom: 0.5rem;
    }

    .legal-paragraph {
        margin-bottom: 1.5rem;
        color: #334155;
        line-height: 1.8;
        font-size: 1.05rem;
    }

    .legal-list {
        margin: 1.5rem 0;
        padding-left: 0;
        list-style: none;
    }

    .legal-list li {
        margin-bottom: 1rem;
        padding-left: 1.8rem;
        position: relative;
        color: #334155;
        line-height: 1.7;
    }

    .legal-list li::before {
        content: "";
        position: absolute;
        left: 0;
        top: 10px;
        width: 8px;
        height: 8px;
        background-color: #f87171; /* Accent for returns */
        border-radius: 50%;
    }

    @media (max-width: 1024px) {
        .legal-content-grid {
            grid-template-columns: 1fr;
        }
        .legal-sidebar {
            display: none;
        }
        .legal-paper {
            padding: 2.5rem;
        }
    }

    @media (max-width: 640px) {
        .legal-main-title { font-size: 2rem; }
        .legal-paper { padding: 1.5rem; border-radius: 0; border: none; }
        .legal-page-container { padding-top: 60px; }
    }
</style>

<?php include 'includes/footer.php'; ?>

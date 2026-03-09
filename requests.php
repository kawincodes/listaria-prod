<?php
session_start();
require 'includes/db.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $budget = $_POST['budget'] ?? null;
    $user_id = $_SESSION['user_id'];

    $stmt = $pdo->prepare("INSERT INTO product_requests (user_id, title, description, budget) VALUES (?, ?, ?, ?)");
    if ($stmt->execute([$user_id, $title, $description, $budget])) {
        header("Location: requests.php?msg=success");
        exit;
    } else {
        $message = '<div class="alert error">Failed to post request.</div>';
    }
}

if (isset($_GET['msg']) && $_GET['msg'] === 'success') {
    $message = '<div class="alert success">Request posted! Vendors will contact you if they have it.</div>';
}

// Fetch all open requests
$stmt = $pdo->query("SELECT r.*, u.full_name FROM product_requests r JOIN users u ON r.user_id = u.id WHERE r.status = 'open' ORDER BY r.created_at DESC");
$requests = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="requests-container" style="max-width: 800px; margin: 0 auto; padding: 20px; padding-top: 100px;">
    <div class="requests-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <div>
            <h1 style="margin: 0; font-size: 1.8rem;">Product Requests</h1>
            <p style="color: #666; margin: 5px 0 0;">Can't find what you're looking for? Ask the community.</p>
        </div>
        <button onclick="openRequestModal()" class="btn-primary" style="padding: 10px 20px; border-radius: 30px;">Post Request</button>
    </div>

    <?php echo $message; ?>

    <div class="requests-feed">
        <?php if (count($requests) > 0): ?>
            <?php foreach ($requests as $req): ?>
            <div class="request-card" style="background: white; padding: 20px; border-radius: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 20px; border-left: 4px solid var(--brand-color);">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px;">
                    <h3 style="margin: 0; font-size: 1.1rem;"><?php echo htmlspecialchars($req['title']); ?></h3>
                    <span style="font-weight: 700; color: #166534; background: #dcfce7; padding: 4px 10px; border-radius: 20px; font-size: 0.85rem;">
                        Budget: ₹<?php echo number_format($req['budget']); ?>
                    </span>
                </div>
                <p style="color: #444; font-size: 0.95rem; line-height: 1.5; margin-bottom: 15px;"><?php echo htmlspecialchars($req['description']); ?></p>
                <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #f0f0f0; padding-top: 12px; font-size: 0.85rem; color: #888;">
                    <span>Requested by <strong><?php echo htmlspecialchars($req['full_name']); ?></strong></span>
                    <span><?php echo date('M j, Y', strtotime($req['created_at'])); ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="text-align: center; padding: 50px; color: #888;">
                <ion-icon name="search-outline" style="font-size: 3rem; margin-bottom: 10px;"></ion-icon>
                <p>No active requests found. Be the first to post!</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Post Request Modal -->
<div id="requestModal" class="modal" style="display:none; align-items:center; justify-content:center;">
    <div class="modal-content" style="border-radius:16px; max-width:500px; width:90%; padding:20px;">
        <div class="summary-header">
            <h3>Post a Requirement</h3>
            <span class="close-summary" onclick="closeRequestModal()">&times;</span>
        </div>
        <form method="POST" action="requests.php">
            <div class="form-group" style="margin-bottom:15px;">
                <label>What are you looking for?</label>
                <input type="text" name="title" required class="form-input" placeholder="e.g. Vintage Rolex Oyster">
            </div>
            <div class="form-group" style="margin-bottom:15px;">
                <label>Tell us more</label>
                <textarea name="description" required class="form-input" rows="4" placeholder="Mention size, color, condition..."></textarea>
            </div>
            <div class="form-group" style="margin-bottom:20px;">
                <label>Estimated Budget (₹)</label>
                <input type="number" name="budget" required class="form-input" placeholder="e.g. 50000">
            </div>
            <button type="submit" class="btn-primary" style="width:100%; padding:15px; border-radius:12px;">Post Requirement</button>
        </form>
    </div>
</div>

<script>
function openRequestModal() {
    <?php if (!isset($_SESSION['user_id'])): ?>
    window.location.href = 'login.php?redirect=requests.php';
    <?php else: ?>
    document.getElementById('requestModal').style.display = 'flex';
    <?php endif; ?>
}
function closeRequestModal() {
    document.getElementById('requestModal').style.display = 'none';
}
</script>

<?php include 'includes/footer.php'; ?>

<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div style="max-width:900px; margin:0 auto; padding:2rem;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:2rem;">
        <h1 style="font-size:1.8rem; font-weight:700;">Product Requests</h1>
        <?php if (session()->get('user_id')): ?>
            <button onclick="document.getElementById('requestForm').style.display='block'" style="background:#6B21A8; color:white; padding:10px 20px; border:none; border-radius:8px; cursor:pointer; font-weight:600;">New Request</button>
        <?php endif; ?>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div style="background:#e8f5e9; color:#2e7d32; padding:12px; border-radius:8px; margin-bottom:1rem;"><?= session()->getFlashdata('success') ?></div>
    <?php endif; ?>

    <div id="requestForm" style="display:none; background:white; padding:1.5rem; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.06); margin-bottom:2rem;">
        <form method="POST" action="/requests" style="display:flex; flex-direction:column; gap:1rem;"><?= csrf_field() ?>
            <input type="text" name="title" required placeholder="What are you looking for?" style="padding:12px; border:1px solid #ddd; border-radius:8px;">
            <textarea name="description" required placeholder="Describe what you need..." rows="3" style="padding:12px; border:1px solid #ddd; border-radius:8px; resize:vertical;"></textarea>
            <input type="number" name="budget" placeholder="Budget (₹)" style="padding:12px; border:1px solid #ddd; border-radius:8px;">
            <button type="submit" class="btn-primary" style="padding:12px; border-radius:8px;">Post Request</button>
        </form>
    </div>

    <div style="display:flex; flex-direction:column; gap:1rem;">
        <?php foreach ($requests as $req): ?>
            <div style="background:white; padding:1.5rem; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
                <h3 style="font-weight:600; margin-bottom:6px;"><?= esc($req['title']) ?></h3>
                <p style="color:#666; font-size:0.9rem; margin-bottom:8px;"><?= esc($req['description']) ?></p>
                <div style="display:flex; gap:1rem; font-size:0.8rem; color:#999;">
                    <?php if ($req['budget']): ?>
                        <span>Budget: ₹<?= number_format($req['budget']) ?></span>
                    <?php endif; ?>
                    <span><?= date('M d, Y', strtotime($req['created_at'])) ?></span>
                    <span style="color:<?= $req['status'] === 'open' ? '#2e7d32' : '#999' ?>;"><?= ucfirst($req['status']) ?></span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?= $this->endSection() ?>

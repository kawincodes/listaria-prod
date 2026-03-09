<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="admin-header"><h1>Chats</h1></div>

<?php if (empty($negotiations)): ?>
    <div class="card"><p style="color:var(--text-muted);">No chats yet.</p></div>
<?php else: ?>
    <?php foreach ($negotiations as $neg): ?>
        <div class="card">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
                <div>
                    <strong><?= esc($neg['product']['title'] ?? 'N/A') ?></strong>
                    <span style="color:var(--text-muted); font-size:0.85rem;"> | <?= esc($neg['buyer']['full_name'] ?? 'Buyer') ?> ↔ <?= esc($neg['seller']['full_name'] ?? 'Seller') ?></span>
                </div>
                <span class="badge <?= $neg['status'] === 'active' ? 'badge-success' : 'badge-info' ?>"><?= ucfirst($neg['status']) ?></span>
            </div>
            <div style="max-height:200px; overflow-y:auto; background:#f9f9f9; padding:1rem; border-radius:8px;">
                <?php foreach ($neg['messages'] as $msg): ?>
                    <div style="margin-bottom:8px;">
                        <strong style="font-size:0.8rem; color:<?= $msg['sender_id'] == $neg['buyer_id'] ? 'var(--primary)' : 'var(--success)' ?>;">
                            <?= $msg['sender_id'] == $neg['buyer_id'] ? 'Buyer' : 'Seller' ?>
                        </strong>
                        <span style="font-size:0.75rem; color:var(--text-muted);"> <?= date('M d H:i', strtotime($msg['created_at'])) ?></span>
                        <p style="margin:2px 0 0; font-size:0.9rem;"><?= esc($msg['message']) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
<?= $this->endSection() ?>

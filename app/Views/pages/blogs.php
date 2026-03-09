<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div style="max-width:1200px; margin:0 auto; padding:2rem;">
    <h1 style="font-size:1.8rem; font-weight:700; margin-bottom:2rem;">Blogs</h1>
    <?php if (empty($blogs)): ?>
        <p style="color:#999; text-align:center; padding:3rem;">No blog posts yet.</p>
    <?php else: ?>
        <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(300px, 1fr)); gap:1.5rem;">
            <?php foreach ($blogs as $blog): ?>
                <a href="/blog/<?= $blog['id'] ?>" style="text-decoration:none; color:inherit; border-radius:12px; overflow:hidden; background:white; box-shadow:0 2px 8px rgba(0,0,0,0.06); transition:transform 0.2s;">
                    <img src="/<?= esc($blog['image_path']) ?>" alt="" style="width:100%; height:200px; object-fit:cover;">
                    <div style="padding:1rem;">
                        <span style="font-size:0.75rem; color:#6B21A8; text-transform:uppercase; font-weight:600;"><?= esc($blog['category']) ?></span>
                        <h3 style="font-size:1.1rem; margin:6px 0; font-weight:600;"><?= esc($blog['title']) ?></h3>
                        <span style="font-size:0.8rem; color:#999;"><?= date('M d, Y', strtotime($blog['created_at'])) ?></span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>

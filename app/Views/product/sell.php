<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div style="max-width:700px; margin:2rem auto; padding:0 1.5rem;">
    <h1 style="font-size:1.8rem; font-weight:700; margin-bottom:0.5rem;">Sell Your Item</h1>
    <p style="color:#666; margin-bottom:2rem;">List your luxury item on Listaria marketplace.</p>

    <form method="POST" action="/sell" enctype="multipart/form-data" style="display:flex; flex-direction:column; gap:1.5rem;"><?= csrf_field() ?>
        <div class="form-group">
            <label>Title *</label>
            <input type="text" name="title" required placeholder="e.g. Gucci Marmont Bag">
        </div>
        <div class="form-group">
            <label>Brand</label>
            <input type="text" name="brand" placeholder="e.g. Gucci, Louis Vuitton">
        </div>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
            <div class="form-group">
                <label>Min Price (₹) *</label>
                <input type="number" name="price_min" required placeholder="5000">
            </div>
            <div class="form-group">
                <label>Max Price (₹) *</label>
                <input type="number" name="price_max" required placeholder="10000">
            </div>
        </div>
        <div class="form-group">
            <label>Category</label>
            <select name="category" style="width:100%; padding:12px; border:1px solid #ddd; border-radius:8px; font-size:16px;">
                <option value="All">All</option>
                <option value="Fashion">Fashion</option>
                <option value="Electronics">Electronics</option>
                <option value="Home">Home</option>
                <option value="Accessories">Accessories</option>
                <option value="Shoes">Shoes</option>
                <option value="Watches">Watches</option>
                <option value="Bags">Bags</option>
            </select>
        </div>
        <div class="form-group">
            <label>Condition *</label>
            <select name="condition_tag" required style="width:100%; padding:12px; border:1px solid #ddd; border-radius:8px; font-size:16px;">
                <option value="Brand New">Brand New</option>
                <option value="Lightly Used">Lightly Used</option>
                <option value="Regularly Used">Regularly Used</option>
            </select>
        </div>
        <div class="form-group">
            <label>Description</label>
            <textarea name="description" rows="4" placeholder="Describe your item..." style="width:100%; padding:12px; border:1px solid #ddd; border-radius:8px; font-size:16px; resize:vertical; box-sizing:border-box;"></textarea>
        </div>
        <div class="form-group">
            <label>Location</label>
            <input type="text" name="location" placeholder="Bangalore, India" value="Bangalore, India">
        </div>
        <div class="form-group">
            <label>Images (up to 5)</label>
            <input type="file" name="images[]" multiple accept="image/*" style="padding:10px;">
        </div>
        <div class="form-group">
            <label>Video (optional)</label>
            <input type="file" name="video" accept="video/*" style="padding:10px;">
        </div>
        <button type="submit" class="btn-primary" style="padding:14px; font-size:1rem; border-radius:12px; width:100%;">List Product</button>
    </form>
</div>

<style>
    .form-group { display:flex; flex-direction:column; }
    .form-group label { font-weight:600; margin-bottom:6px; color:#333; font-size:0.9rem; }
    .form-group input, .form-group select { padding:12px; border:1px solid #ddd; border-radius:8px; font-size:16px; box-sizing:border-box; }
    .form-group input:focus, .form-group select:focus { border-color:#6B21A8; outline:none; }
</style>
<?= $this->endSection() ?>

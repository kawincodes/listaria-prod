
<!-- Search Overlay -->
<div id="mobileSearchOverlay" class="mobile-search-overlay">
    <div class="search-header">
        <form action="index.php" method="GET" style="width: 100%; display: flex; align-items: center; gap: 10px;" onsubmit="return false;">
            <ion-icon name="search-outline" style="color: #666; font-size: 1.2rem;"></ion-icon>
            <input type="text" name="search" id="liveSearchInput" class="mobile-search-input" placeholder="Search for luxury items..." autocomplete="off">
            <button type="button" class="close-search-btn" onclick="toggleMobileSearch()">
                <ion-icon name="close-circle-outline"></ion-icon>
            </button>
        </form>
    </div>
    <div id="searchResults" class="search-results-area">
        <!-- Live results will appear here -->
    </div>
</div>

<div class="mobile-bottom-nav">
    <a href="index.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' && !isset($_GET['search']) ? 'active' : ''; ?>">
        <ion-icon name="home-outline"></ion-icon>
        <span>Home</span>
    </a>
    
    <!-- Search Trigger -->
    <a href="javascript:void(0)" onclick="toggleMobileSearch()" class="nav-item <?php echo isset($_GET['search']) ? 'active' : ''; ?>">
        <ion-icon name="search-outline"></ion-icon>
        <span>Search</span>
    </a>
    
    <div class="nav-center-wrapper">
        <a href="sell.php" class="nav-fab" style="background: var(--brand-light) !important;">
            <ion-icon name="add-outline"></ion-icon>
        </a>
        <span class="nav-fab-label">Sell</span>
    </div>

    <a href="wishlist.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'wishlist.php' ? 'active' : ''; ?>">
        <ion-icon name="heart-outline"></ion-icon>
        <span>Saved</span>
    </a>
    <a href="profile.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
        <ion-icon name="person-outline"></ion-icon>
        <span>Profile</span>
    </a>
</div>

<style>
    .mobile-search-overlay {
        position: fixed;
        top: -100%; /* Hidden initially at top */
        left: 0;
        width: 100%;
        background: #ffffff;
        padding: 60px 20px 20px; /* Added top padding to clear status bars/safe areas */
        border-bottom-left-radius: 24px;
        border-bottom-right-radius: 24px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.15);
        z-index: 10000;
        transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        transform: translateY(-20px);
        max-height: 85vh;
        display: flex;
        flex-direction: column;
    }

    .mobile-search-overlay.active {
        top: 0;
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
        transform: translateY(0);
    }

    [data-theme="dark"] .mobile-search-overlay {
        background: #1a1a1a;
        box-shadow: 0 -5px 20px rgba(0,0,0,0.3);
    }

    .search-header {
        display: flex;
        align-items: center;
        width: 100%;
        background: var(--input-bg);
        padding: 10px 15px;
        border-radius: 12px;
        border: 1px solid var(--border-color);
    }

    .mobile-search-input {
        border: none;
        background: transparent;
        width: 100%;
        outline: none;
        font-size: 1rem;
        color: var(--primary-text);
        font-family: var(--font-family);
    }

    .close-search-btn {
        background: none;
        border: none;
        padding: 0;
        cursor: pointer;
        display: flex;
        align-items: center;
        color: var(--secondary-text);
    }

    .close-search-btn ion-icon {
        font-size: 1.5rem;
    }

    .search-results-area {
        margin-top: 20px;
        overflow-y: auto;
        flex: 1;
        padding-bottom: 20px;
    }

    .search-result-item {
        display: flex;
        align-items: center;
        padding: 12px;
        gap: 15px;
        text-decoration: none;
        border-bottom: 1px solid var(--border-light);
        transition: background 0.2s;
        border-radius: 12px;
    }

    .search-result-item:hover {
        background: var(--hover-bg);
    }

    .search-result-image {
        width: 60px;
        height: 60px;
        object-fit: cover;
        border-radius: 8px;
        background: #f8f9fa;
    }

    .search-result-info {
        flex: 1;
    }

    .search-result-title {
        font-weight: 600;
        font-size: 0.95rem;
        color: var(--primary-text);
        margin-bottom: 2px;
    }

    .search-result-price {
        font-weight: 700;
        font-size: 0.9rem;
        color: var(--brand-color);
    }

    .no-results {
        text-align: center;
        padding: 40px 20px;
        color: var(--secondary-text);
        font-size: 0.9rem;
    }
</style>

<script>
function toggleMobileSearch() {
    const overlay = document.getElementById('mobileSearchOverlay');
    const input = document.getElementById('liveSearchInput');
    
    if (overlay.classList.contains('active')) {
        overlay.classList.remove('active');
        input.blur();
    } else {
        overlay.classList.add('active');
        setTimeout(() => input.focus(), 300); // Focus after transition finishes
    }
}

// Live Search Logic
let searchTimeout = null;
const searchInput = document.getElementById('liveSearchInput');
const resultsArea = document.getElementById('searchResults');

if (searchInput) {
    searchInput.addEventListener('input', (e) => {
        clearTimeout(searchTimeout);
        const query = e.target.value.trim();
        
        if (query.length < 2) {
            resultsArea.innerHTML = '';
            return;
        }

        searchTimeout = setTimeout(async () => {
            try {
                const res = await fetch(`api/search.php?q=${encodeURIComponent(query)}`);
                const data = await res.json();
                
                if (data.success) {
                    displayResults(data.results);
                }
            } catch (err) {
                console.error("Search error:", err);
            }
        }, 300); // Debounce
    });
}

function displayResults(results) {
    if (results.length === 0) {
        resultsArea.innerHTML = '<div class="no-results">No items found matching your search.</div>';
        return;
    }

    resultsArea.innerHTML = results.map(item => `
        <a href="product_details.php?id=${item.id}" class="search-result-item">
            <img src="${item.image}" class="search-result-image" alt="${item.title}">
            <div class="search-result-info">
                <div class="search-result-title">${item.title}</div>
                <div class="search-result-price">₹${item.price}</div>
            </div>
            <ion-icon name="chevron-forward-outline" style="color: #ccc;"></ion-icon>
        </a>
    `).join('');
}
</script>

document.addEventListener('DOMContentLoaded', () => {

    // --- 3D Tilt Effect REMOVED for cleaner professional look ---
    // Hover effects are now handled purely via CSS in style.css

    // --- Credit Card Flip on CVV Focus ---
    const cvvInput = document.getElementById('cvv');
    const creditCard = document.querySelector('.credit-card');

    if (cvvInput && creditCard) {
        cvvInput.addEventListener('focus', () => {
            creditCard.classList.add('flipped');
        });

        cvvInput.addEventListener('blur', () => {
            creditCard.classList.remove('flipped');
        });

        // Live update for card number, holder, etc can be added here
        const numInput = document.getElementById('card_number');
        const numDisplay = document.querySelector('.card-number');
        if (numInput && numDisplay) {
            numInput.addEventListener('input', (e) => {
                numDisplay.textContent = e.target.value || '#### #### #### ####';
            });
        }
    }

    // --- Sell Page Image Upload Logic ---
    const dropZone = document.getElementById('drop-zone');
    const fileInput = document.getElementById('image-upload');

    if (dropZone && fileInput) {
        dropZone.addEventListener('click', () => fileInput.click());

        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.classList.add('dragover');
        });

        dropZone.addEventListener('dragleave', () => {
            dropZone.classList.remove('dragover');
        });

        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('dragover');
            fileInput.files = e.dataTransfer.files;
            handleFiles(fileInput.files);
        });

        fileInput.addEventListener('change', () => {
            handleFiles(fileInput.files);
        });
    }

    function handleFiles(files) {
        const previewArea = document.getElementById('preview-area');
        if (!previewArea) return;

        previewArea.innerHTML = ''; // Clear previous
        if (files.length < 3) {
            previewArea.innerHTML = '<p style="color:red;">Please select at least 3 images.</p>';
        } else {
            previewArea.innerHTML = `<p style="color:green;">${files.length} images selected.</p>`;
        }
    }

    // Form Validation for Sell Page
    const sellForm = document.getElementById('sell-form');
    if (sellForm) {
        sellForm.addEventListener('submit', (e) => {
            if (fileInput.files.length < 3) {
                e.preventDefault();
                alert('You must upload at least 3 images!');
            }
        });
    }
    // --- Theme Toggle Logic ---
    const themeToggleBtn = document.getElementById('theme-toggle');
    const themeIcon = themeToggleBtn ? themeToggleBtn.querySelector('ion-icon') : null;

    function setTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('theme', theme);

        if (themeIcon) {
            themeIcon.setAttribute('name', theme === 'dark' ? 'sunny-outline' : 'moon-outline');
        }
    }

    // Check for saved theme
    const savedTheme = localStorage.getItem('theme');

    // Default to white (light) for every user
    if (savedTheme) {
        setTheme(savedTheme);
    } else {
        setTheme('light');
    }

    if (themeToggleBtn) {
        themeToggleBtn.addEventListener('click', () => {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            setTheme(newTheme);
        });
    }
});

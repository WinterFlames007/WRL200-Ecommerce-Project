document.addEventListener('DOMContentLoaded', function () {
    const confirmLinks = document.querySelectorAll('[data-confirm]');

    confirmLinks.forEach(function (link) {
        link.addEventListener('click', function (event) {
            const message = link.getAttribute('data-confirm') || 'Are you sure?';

            if (!window.confirm(message)) {
                event.preventDefault();
            }
        });
    });

    const qtyButtons = document.querySelectorAll('[data-qty-action]');

    qtyButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            const action = button.getAttribute('data-qty-action');
            const wrapper = button.closest('.quantity-box');

            if (!wrapper) return;

            const input = wrapper.querySelector('.qty-input');

            if (!input) return;

            let currentValue = parseInt(input.value || '1', 10);
            const min = parseInt(input.getAttribute('min') || '1', 10);
            const max = parseInt(input.getAttribute('max') || '999', 10);

            if (action === 'decrease' && currentValue > min) {
                input.value = currentValue - 1;
            }

            if (action === 'increase' && currentValue < max) {
                input.value = currentValue + 1;
            }
        });
    });

    const cartLink = document.querySelector('.cart-link');

    if (cartLink) {
        document.addEventListener('itemAddedToCart', () => {
            cartLink.classList.add('cart-bounce');

            setTimeout(() => {
                cartLink.classList.remove('cart-bounce');
            }, 300);
        });
    }

    const thumbButtons = document.querySelectorAll('[data-product-thumb]');
    const mainImage = document.getElementById('product-main-image');

    thumbButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            const image = button.getAttribute('data-image');

            if (!mainImage || !image) return;

            mainImage.style.backgroundImage = `url('${image}')`;
            mainImage.classList.add('real-image');

            thumbButtons.forEach(function (thumb) {
                thumb.classList.remove('active');
            });

            button.classList.add('active');
        });
    });

    const tabButtons = document.querySelectorAll('[data-tab-target]');
    const tabPanels = document.querySelectorAll('[data-tab-panel]');

    tabButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            const target = button.getAttribute('data-tab-target');

            tabButtons.forEach(function (btn) {
                btn.classList.remove('active');
            });

            tabPanels.forEach(function (panel) {
                panel.classList.remove('active');
            });

            button.classList.add('active');

            const panel = document.querySelector(`[data-tab-panel="${target}"]`);
            if (panel) {
                panel.classList.add('active');
            }
        });
    });

    const variantBox = document.getElementById('product-variant-box');

    if (variantBox) {
        const variants = JSON.parse(variantBox.getAttribute('data-variants') || '[]');
        let selectedSize = variantBox.getAttribute('data-default-size') || '';
        let selectedColour = variantBox.getAttribute('data-default-colour') || '';

        const sizeButtons = document.querySelectorAll('[data-variant-size]');
        const colourButtons = document.querySelectorAll('[data-variant-colour]');
        const variantIdInput = document.getElementById('selected-variant-id');
        const quantityInput = document.getElementById('selected-variant-quantity');
        const priceBox = document.getElementById('product-detail-price');
        const stockBadge = document.getElementById('product-stock-badge');
        const buyButton = document.getElementById('product-buy-button');
        const mainImageElement = document.getElementById('product-main-image');
        const thumbsContainer = document.getElementById('product-thumbs');

        
        
        function findMatchingVariant() {
            let exactMatch = variants.find(function (variant) {
                return variant.size === selectedSize && variant.colour === selectedColour;
            });

            if (exactMatch) {
                return exactMatch;
            }

            let sizeOnlyMatch = variants.find(function (variant) {
                return variant.size === selectedSize && variant.stock_qty > 0;
            });

            if (sizeOnlyMatch) {
                selectedColour = sizeOnlyMatch.colour;
                return sizeOnlyMatch;
            }

            let colourOnlyMatch = variants.find(function (variant) {
                return variant.colour === selectedColour && variant.stock_qty > 0;
            });

            if (colourOnlyMatch) {
                selectedSize = colourOnlyMatch.size;
                return colourOnlyMatch;
            }

            return variants[0] || null;
        }




        function bindThumbEvents() {
            const currentThumbButtons = document.querySelectorAll('[data-product-thumb]');

            currentThumbButtons.forEach(function (button) {
                button.addEventListener('click', function () {
                    const image = button.getAttribute('data-image');

                    if (!mainImageElement || !image) return;

                    mainImageElement.style.backgroundImage = `url('${image}')`;
                    mainImageElement.classList.add('real-image');

                    currentThumbButtons.forEach(function (thumb) {
                        thumb.classList.remove('active');
                    });

                    button.classList.add('active');
                });
            });
        }

        function renderThumbs(images) {
            if (!thumbsContainer) return;

            thumbsContainer.innerHTML = '';

            images.forEach(function (image, index) {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'product-thumb' + (index === 0 ? ' active' : '');
                button.setAttribute('data-product-thumb', '');
                button.setAttribute('data-image', image);
                button.setAttribute('aria-label', `Product image ${index + 1}`);
                button.style.backgroundImage = `url('${image}')`;

                thumbsContainer.appendChild(button);
            });

            bindThumbEvents();
        }





        function updateUiFromVariant() {
            const variant = findMatchingVariant();
            if (!variant) return;

            variantIdInput.value = variant.id;
            priceBox.textContent = `£${Number(variant.price).toFixed(2)}`;

            const maxQty = variant.stock_qty > 0 ? variant.stock_qty : 1;
            quantityInput.max = maxQty;

            if (parseInt(quantityInput.value || '1', 10) > maxQty) {
                quantityInput.value = maxQty;
            }

            if (variant.stock_qty > 0 && variant.status === 'active') {
                stockBadge.textContent = 'In stock';
                stockBadge.classList.remove('badge-danger');
                stockBadge.classList.add('badge-success');

                buyButton.textContent = 'Add to Cart';
                buyButton.disabled = false;
                buyButton.classList.remove('button-danger');
            } else {
                stockBadge.textContent = 'Out of stock';
                stockBadge.classList.remove('badge-success');
                stockBadge.classList.add('badge-danger');

                buyButton.textContent = 'Out of Stock';
                buyButton.disabled = true;
                buyButton.classList.add('button-danger');
            }




            const variantImages = Array.isArray(variant.images) ? variant.images : [];
            let galleryImages = [];

            if (variantImages.length) {
                galleryImages = variantImages;
            } else if (variant.image_path) {
                galleryImages = [variant.image_path];
            } else {
                const defaultImage = mainImageElement ? (mainImageElement.getAttribute('data-default-image') || '') : '';
                galleryImages = defaultImage ? [defaultImage] : [];
            }

            if (mainImageElement && galleryImages.length) {
                mainImageElement.style.backgroundImage = `url('${galleryImages[0]}')`;
                mainImageElement.classList.add('real-image');
                renderThumbs(galleryImages);
            }



            sizeButtons.forEach(function (button) {
                button.classList.toggle('active', button.getAttribute('data-variant-size') === selectedSize);
            });

            colourButtons.forEach(function (button) {
                button.classList.toggle('active', button.getAttribute('data-variant-colour') === selectedColour);
            });
        }

        sizeButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                selectedSize = button.getAttribute('data-variant-size') || '';
                updateUiFromVariant();
            });
        });

        colourButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                selectedColour = button.getAttribute('data-variant-colour') || '';
                updateUiFromVariant();
            });
        });
 
        bindThumbEvents();
        updateUiFromVariant();

    }

    const autoHideAlert = document.getElementById('account-success-alert');

    if (autoHideAlert) {
        setTimeout(function () {
            autoHideAlert.classList.add('fade-out');

            setTimeout(function () {
                autoHideAlert.remove();
            }, 400);
        }, 4000);
    }
});
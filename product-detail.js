

document.addEventListener('DOMContentLoaded', function() {

    const tabs = document.querySelectorAll('.tab-btn');
    const tabPanes = document.querySelectorAll('.tab-pane');

    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const targetTab = this.dataset.tab;

            tabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');

            tabPanes.forEach(pane => {
                if (pane.id === targetTab) {
                    pane.classList.add('active');
                } else {
                    pane.classList.remove('active');
                }
            });
        });
    });

    
    const mainProductImage = document.getElementById('main-product-image');
    const thumbnailsDiv = document.querySelector('.thumbnails');
    const thumbnailImages = thumbnailsDiv ? thumbnailsDiv.querySelectorAll('img') : [];
    const prevThumbBtn = document.querySelector('.thumb-nav.prev'); 
    const nextThumbBtn = document.querySelector('.thumb-nav.next'); 


    let currentThumbnailIndex = 0;
    const thumbnailsPerPage = 4;

    function updateThumbnailsVisibility() {
        if (!thumbnailsDiv) return;

      
        thumbnailImages.forEach(img => img.style.display = 'none');


        for (let i = 0; i < thumbnailsPerPage; i++) {
            const indexToShow = currentThumbnailIndex + i;
            if (thumbnailImages[indexToShow]) {
                thumbnailImages[indexToShow].style.display = 'block';
            }
        }
    }

 
    let currentMainImageIndex = 0; 

    if (mainProductImage && typeof allProductImages !== 'undefined' && allProductImages && allProductImages.length > 0) {
        const initialMainSrc = mainProductImage.src;
   
        const foundIndex = allProductImages.findIndex(url => initialMainSrc.includes(url));
        if (foundIndex !== -1) {
            currentMainImageIndex = foundIndex;
        }
    }

    function updateMainImage(index) {
        if (typeof allProductImages === 'undefined' || !allProductImages || allProductImages.length === 0) {
            console.warn("No product images available in 'allProductImages' array.");
            return;
        }

        if (index >= 0 && index < allProductImages.length) {
            mainProductImage.src = allProductImages[index];
            mainProductImage.setAttribute('data-zoom-image', allProductImages[index]);

         
            if (typeof $.fn.elevateZoom !== 'undefined' && $(mainProductImage).data('elevateZoom')) {
                $(mainProductImage).data('elevateZoom').destroy();
            }
            if (typeof $.fn.elevateZoom !== 'undefined') {
                $(mainProductImage).elevateZoom({
                    zoomWindowWidth: 400,
                    zoomWindowHeight: 400,
                    borderSize: 1,
                    borderColor: 'var(--primary-color)',
                    lensSize: 100,
                    easing: true,
                    scrollZoom: true
                });
            }

        
            thumbnailImages.forEach((thumb, i) => {
                if (i === index) {
                    thumb.classList.add('active');
                } else {
                    thumb.classList.remove('active');
                }
            });

         
            if (index < currentThumbnailIndex || index >= currentThumbnailIndex + thumbnailsPerPage) {
                currentThumbnailIndex = index;
                updateThumbnailsVisibility();
            }
        }
    }

 
    thumbnailImages.forEach((thumb, index) => {
        thumb.addEventListener('click', function() {
        
            currentMainImageIndex = index;
            updateMainImage(currentMainImageIndex);

            
        });
    });

    if (prevThumbBtn && nextThumbBtn) {
    
        prevThumbBtn.addEventListener('click', () => {
         
            if (currentThumbnailIndex > 0) {
                currentThumbnailIndex--;
                updateThumbnailsVisibility();
            }

        
            if (typeof allProductImages !== 'undefined' && allProductImages.length > 0) {
                currentMainImageIndex--;
                if (currentMainImageIndex < 0) {
                    currentMainImageIndex = allProductImages.length - 1; 
                }
                updateMainImage(currentMainImageIndex);
            }
        });

      
        nextThumbBtn.addEventListener('click', () => {
      
            if (currentThumbnailIndex + thumbnailsPerPage < thumbnailImages.length) {
                currentThumbnailIndex++;
                updateThumbnailsVisibility();
            }

       
            if (typeof allProductImages !== 'undefined' && allProductImages.length > 0) {
                currentMainImageIndex++;
                if (currentMainImageIndex >= allProductImages.length) {
                    currentMainImageIndex = 0;  
                }
                updateMainImage(currentMainImageIndex);
            }
        });
    }

   
    updateThumbnailsVisibility();

    if (thumbnailImages.length > 0) {
        thumbnailImages[0].classList.add('active');
    }
  
    if (typeof $.fn.elevateZoom !== 'undefined' && mainProductImage) {
        $(mainProductImage).elevateZoom({
            zoomWindowWidth: 400,
            zoomWindowHeight: 400,
            borderSize: 1,
            borderColor: 'var(--primary-color)',
            lensSize: 100,
            easing: true,
            scrollZoom: true
        });
    }

 

    const quantityInput = document.getElementById('quantity');
    const decreaseBtn = document.getElementById('decrease-quantity');
    const increaseBtn = document.getElementById('increase-quantity');

    if (quantityInput && decreaseBtn && increaseBtn) {
        decreaseBtn.addEventListener('click', () => {
            let currentValue = parseInt(quantityInput.value);
            if (currentValue > parseInt(quantityInput.min)) {
                quantityInput.value = currentValue - 1;
            }
        });

        increaseBtn.addEventListener('click', () => {
            let currentValue = parseInt(quantityInput.value);
            if (currentValue < parseInt(quantityInput.max)) {
                quantityInput.value = currentValue + 1;
            }
        });
    }



    const addToCartBtn = document.getElementById('add-to-cart');
    if (addToCartBtn) {
        addToCartBtn.addEventListener('click', function() {
            const productId = this.dataset.productId;
            const quantity = 1; 

            console.log(`Adding product ID ${productId} with quantity ${quantity} to cart.`);

            fetch('add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `product_id=${productId}&quantity=${quantity}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert(data.message || 'Product added to cart!');
                    window.location.href = 'cart.php';
                } else {
                    alert('Failed to add product to cart: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error adding to cart:', error);
                alert('An error occurred while adding to cart.');
            });
        });
    }


   
    const addToWishlistBtn = document.getElementById('add-to-wishlist');
    if (addToWishlistBtn) {
        addToWishlistBtn.addEventListener('click', function() {
            const listingId = this.dataset.productId;

            if (!listingId) {
                console.error("Error: Listing ID not found for 'Save to Favorites' button.");
                alert("Could not save to favorites. Listing ID missing.");
                return;
            }

            console.log(`Saving listing ID ${listingId} to favorites.`);

            fetch('add_to_wishlist.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `product_id=${listingId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message || 'Listing added to your favorites!');
                } else {
                    alert('Failed to save to favorites: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error saving to favorites:', error);
                alert('An error occurred while saving to favorites.');
            });
        });
    }

    const shareProductBtn = document.getElementById('share-product');
    if (shareProductBtn) {
        shareProductBtn.addEventListener('click', function() {
            const productTitle = document.querySelector('.product-info h1').innerText;
            const productUrl = window.location.href;

            if (navigator.share) {
                navigator.share({
                    title: productTitle,
                    url: productUrl,
                }).then(() => {
                 
                }).catch(console.error);
            } else {
           
                navigator.clipboard.writeText(productUrl).then(() => {
                    alert('Link copied to clipboard!');
                }).catch(err => {
                    console.error('Could not copy text: ', err);
                });
            }
        });
    }


    const reportListingBtn = document.getElementById('report-listing');
    if (reportListingBtn) {
        reportListingBtn.addEventListener('click', function() {
            const listingId = this.dataset.productId || 'unknown';

            if (confirm('Are you sure you want to report this listing?')) {
                fetch('report_listing.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `listing_id=${listingId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message || 'Listing reported successfully!');
                    } else {
                        alert('Failed to report listing: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error reporting listing:', error);
                    alert('An error occurred while reporting this listing.');
                });
            }
        });
    }
});
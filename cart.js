document.addEventListener('DOMContentLoaded', function() {

  window.addEventListener('scroll', function() {
    const header = document.getElementById('header');
    if (header) { 
      if (window.scrollY > 100) {
        header.classList.add('scrolled');
      } else {
        header.classList.remove('scrolled');
      }
    }
  });


  const bar = document.getElementById('bar');
  const nav = document.getElementById('navbar');

  if (bar && nav) {
    bar.addEventListener('click', () => {
      nav.classList.add('active');
    });

 
    let closeBtn = document.getElementById('close');
    if (!closeBtn) {
      closeBtn = document.createElement('i');
      closeBtn.id = 'close';
      closeBtn.className = 'fas fa-times';
      closeBtn.style.cssText = 'position: absolute; top: 30px; left: 30px; font-size: 24px; cursor: pointer;';
      nav.prepend(closeBtn);
    }
  
    closeBtn.addEventListener('click', () => {
      nav.classList.remove('active');
    });
  }


  const decreaseBtns = document.querySelectorAll('.quantity-btn.decrease');
  const increaseBtns = document.querySelectorAll('.quantity-btn.increase');

  decreaseBtns.forEach(btn => {
    btn.addEventListener('click', function() {
      const input = this.closest('.quantity-controls').querySelector('.quantity-input');
      let currentValue = parseInt(input.value);
      if (currentValue > parseInt(input.min)) {
        input.value = currentValue - 1;
      }
    });
  });

  increaseBtns.forEach(btn => {
    btn.addEventListener('click', function() {
      const input = this.closest('.quantity-controls').querySelector('.quantity-input');
      let currentValue = parseInt(input.value);
      const maxValue = parseInt(input.getAttribute('max') || 10); 
      if (currentValue < maxValue) {
        input.value = currentValue + 1;
      }
    });
  });


  const shippingOptions = document.querySelectorAll('.shipping-option input[type="radio"]');

  shippingOptions.forEach(option => {
    option.addEventListener('change', function() {
      const shippingMethod = this.value; 

      console.log(`Selected ${shippingMethod} shipping.`);
      showToast(`Updating shipping method to ${shippingMethod}...`);

    
      fetch('update_cart_shipping.php', { 
          method: 'POST',
          headers: {
              'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: `shipping_method=${shippingMethod}`
      })
      .then(response => {
          if (!response.ok) {
              throw new Error('Network response was not ok ' + response.statusText);
          }
          return response.json();
      })
      .then(data => {
          if (data.status === 'success') {
              showToast(data.message || 'Shipping method updated!', 'success');

            
              const shippingSpan = document.getElementById('summary-shipping-price'); 
              const taxSpan = document.getElementById('summary-tax-price');        
              const totalSpan = document.getElementById('summary-total-price');     

              if (shippingSpan) {
                  shippingSpan.textContent = 'R' + parseFloat(data.shipping_cost).toFixed(2);
              }
              if (taxSpan) {
                  taxSpan.textContent = 'R' + parseFloat(data.tax_amount).toFixed(2);
              }
              if (totalSpan) {
                  totalSpan.textContent = 'R' + parseFloat(data.total).toFixed(2);
              }

          } else {
              showToast(data.message || 'Failed to update shipping.', 'error');
              console.error('Server error:', data.message);
          }
      })
      .catch(error => {
          console.error('Error updating shipping:', error);
          showToast('An error occurred while updating shipping. Please try again.', 'error');
      });
    });
  });


  const saveForLaterBtns = document.querySelectorAll('.save-for-later-btn');

  saveForLaterBtns.forEach(btn => {
    btn.addEventListener('click', function() {
      const productId = this.closest('.cart-item').dataset.productId;

      const icon = this.querySelector('i');
      const isSaved = icon.classList.contains('fas'); 

   
      if (isSaved) {
        icon.classList.remove('fas');
        icon.classList.add('far');
        showToast('Item removed from wishlist');
      } else {
        icon.classList.remove('far');
        icon.classList.add('fas');
        showToast('Item saved to wishlist');
      }

      
      /*
      fetch('save_to_favorites.php', {
          method: 'POST',
          headers: {
              'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: `product_id=${productId}&action=${isSaved ? 'remove' : 'add'}`
      })
      .then(response => response.json())
      .then(data => {
          if (data.success) {
              // UI already updated, maybe update a badge if needed
              console.log('Favorites status updated on backend.');
          } else {
              // Revert UI if backend failed
              icon.classList.toggle('fas');
              icon.classList.toggle('far');
              showToast('Failed to update wishlist: ' + (data.message || 'Unknown error'), 'error');
          }
      })
      .catch(error => {
          console.error('Error saving to favorites:', error);
          // Revert UI on network error
          icon.classList.toggle('fas');
          icon.classList.toggle('far');
          showToast('An error occurred while saving to favorites.', 'error');
      });
      */
      console.log(`Toggled wishlist for product ${productId}. Backend call commented out.`);
    });
  });

  const couponForm = document.querySelector('.coupon-form');

  if (couponForm) {
    couponForm.addEventListener('submit', function(e) {
      e.preventDefault();

      const couponInput = this.querySelector('.coupon-input');
      const couponCode = couponInput.value.trim();

      if (couponCode === '') {
        showToast('Please enter a coupon code', 'error');
        return;
      }

    
      /*
      fetch('apply_coupon.php', {
          method: 'POST',
          headers: {
              'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: `coupon_code=${couponCode}`
      })
      .then(response => response.json())
      .then(data => {
          if (data.success) {
              showToast(data.message || 'Coupon applied successfully!', 'success');
              // You would typically reload the cart summary section or the entire cart here
              // window.location.reload();
          } else {
              showToast(data.message || 'Invalid coupon code. Please try again.', 'error');
          }
      })
      .catch(error => {
          console.error('Error applying coupon:', error);
          showToast('An error occurred while applying the coupon.', 'error');
      });
      */
    
      setTimeout(() => {
        if (couponCode.toLowerCase() === 'discount10') {
          showToast('Coupon applied successfully! 10% discount added.', 'success');
        
        } else {
          showToast('Invalid coupon code. Please try again.', 'error');
        }
      }, 800);
    });
  }

  const addToCartBtns = document.querySelectorAll('.recommended-products .add-to-cart-btn');

  addToCartBtns.forEach(btn => {
    btn.addEventListener('click', function() {
      const productId = this.dataset.productId;
      const button = this; 

      button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
      button.disabled = true;

      fetch('add_to_cart.php', {
          method: 'POST',
          headers: {
              'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: `product_id=${productId}&quantity=1` 
      })
      .then(response => response.json())
      .then(data => {
          if (data.status === 'success') {
              showToast(data.message || 'Product added to cart successfully!', 'success');

           
              const badge = document.querySelector('.cart-link .badge'); 
              if (badge) {
                  let currentCount = parseInt(badge.textContent || '0');
                  badge.textContent = currentCount + 1;
              }
             
          } else {
              showToast('Failed to add product: ' + (data.message || 'Unknown error'), 'error');
          }
      })
      .catch(error => {
          console.error('Error adding recommended product to cart:', error);
          showToast('An error occurred while adding to cart.', 'error');
      })
      .finally(() => {
       
          setTimeout(() => {
              button.innerHTML = '<i class="fas fa-shopping-cart"></i> Add to Cart';
              button.disabled = false;
          }, 1000); 
      });
    });
  });



  const animateOnScroll = function() {
    const elements = document.querySelectorAll('.seller-section, .cart-summary, .recommended-products, .trust-item');

    elements.forEach(element => {
      const elementPosition = element.getBoundingClientRect().top;
      const windowHeight = window.innerHeight;

      if (elementPosition < windowHeight - 100) {
        element.classList.add('animate-fadeIn');
      }
    });
  };


  animateOnScroll();


  window.addEventListener('scroll', animateOnScroll);
});


function showToast(message, type = 'info') {

  let toast = document.getElementById('toast-notification');

  if (!toast) {
    toast = document.createElement('div');
    toast.id = 'toast-notification';
    toast.style.cssText = `
      position: fixed;
      bottom: 20px;
      right: 20px;
      padding: 12px 20px;
      border-radius: 4px;
      font-size: 14px;
      font-weight: 500;
      z-index: 1000;
      transform: translateY(100px);
      transition: transform 0.3s ease;
      max-width: 300px;
      cursor: pointer; /* Make it clickable to close */
    `;
    document.body.appendChild(toast);


    toast.addEventListener('click', () => {
        toast.style.transform = 'translateY(100px)';
    });
  }

  if (type === 'success') {
    toast.style.backgroundColor = '#10b981';
    toast.style.color = 'white';
    toast.style.boxShadow = '0 4px 15px rgba(16, 185, 129, 0.3)';
  } else if (type === 'error') {
    toast.style.backgroundColor = '#ef4444';
    toast.style.color = 'white';
    toast.style.boxShadow = '0 4px 15px rgba(239, 68, 68, 0.3)';
  } else { 
    toast.style.backgroundColor = '#3a86ff';
    toast.style.color = 'white';
    toast.style.boxShadow = '0 4px 15px rgba(59, 130, 246, 0.3)';
  }


  toast.textContent = message;

  clearTimeout(toast.dataset.timeoutId);
  toast.style.transform = 'translateY(0)';


  const timeoutId = setTimeout(() => {
    toast.style.transform = 'translateY(100px)';
  }, 3000);
  toast.dataset.timeoutId = timeoutId; 
}
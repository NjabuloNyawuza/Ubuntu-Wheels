document.addEventListener('DOMContentLoaded', function() {

    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    const mobileFilterBtn = document.querySelector('.mobile-filter-btn');
    const filtersSection = document.getElementById('filters-section');
    
    if (mobileMenuBtn) {
      mobileMenuBtn.addEventListener('click', function() {
     
        if (!document.querySelector('.mobile-menu')) {
          const mobileMenu = document.createElement('div');
          mobileMenu.className = 'mobile-menu';
          
          const closeBtn = document.createElement('button');
          closeBtn.className = 'mobile-menu-close';
          closeBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"></path><path d="m6 6 12 12"></path></svg>';
          
          const navClone = document.querySelector('.nav ul').cloneNode(true);
          
          mobileMenu.appendChild(closeBtn);
          mobileMenu.appendChild(navClone);
          
          const overlay = document.createElement('div');
          overlay.className = 'overlay';
          
          document.body.appendChild(mobileMenu);
          document.body.appendChild(overlay);
          
          closeBtn.addEventListener('click', function() {
            mobileMenu.classList.remove('active');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
          });
          
          overlay.addEventListener('click', function() {
            mobileMenu.classList.remove('active');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
          });
        }
        
        const mobileMenu = document.querySelector('.mobile-menu');
        const overlay = document.querySelector('.overlay');
        
        mobileMenu.classList.add('active');
        overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
      });
    }
    

    if (mobileFilterBtn) {
      mobileFilterBtn.addEventListener('click', function() {
        filtersSection.classList.add('active');
        document.body.style.overflow = 'hidden';
        
      
        if (!document.querySelector('.filter-close-btn')) {
          const closeBtn = document.createElement('button');
          closeBtn.className = 'filter-close-btn';
          closeBtn.style.cssText = 'position: absolute; top: 1rem; right: 1rem; background: none; border: none; font-size: 1.5rem; color: var(--text-color);';
          closeBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"></path><path d="m6 6 12 12"></path></svg>';
          
          filtersSection.appendChild(closeBtn);
          
          closeBtn.addEventListener('click', function() {
            filtersSection.classList.remove('active');
            document.body.style.overflow = '';
          });
        }
        
  
        if (!document.querySelector('.filter-overlay')) {
          const overlay = document.createElement('div');
          overlay.className = 'filter-overlay';
          overlay.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5); z-index: 199;';
          
          document.body.appendChild(overlay);
          
          overlay.addEventListener('click', function() {
            filtersSection.classList.remove('active');
            overlay.style.display = 'none';
            document.body.style.overflow = '';
          });
        } else {
          document.querySelector('.filter-overlay').style.display = 'block';
        }
      });
    }
    

    const quickViewBtns = document.querySelectorAll('.quick-view-btn');
    const modal = document.getElementById('product-modal');
    const closeModal = document.querySelector('.close-modal');
    
    quickViewBtns.forEach(btn => {
      btn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
   
        const card = this.closest('.product-card');
        const title = card.querySelector('.product-title').textContent;
        const price = card.querySelector('.current-price').textContent;
        const originalPrice = card.querySelector('.original-price')?.textContent || '';
        const discountBadge = card.querySelector('.discount-badge')?.textContent || '';
        const rating = card.querySelector('.product-rating span').textContent;
        const image = card.querySelector('.product-image img').src;
        const category = card.querySelector('.product-category').textContent;
        const location = card.querySelector('.product-location span').textContent;
        
    
        document.getElementById('modal-product-title').textContent = title;
        document.getElementById('modal-main-image').src = image;
        document.getElementById('modal-main-image').alt = title;
        
        const modalPrice = document.getElementById('modal-product-price');
        modalPrice.innerHTML = `<span class="current-price">${price}</span>`;
        if (originalPrice) {
          modalPrice.innerHTML += `<span class="original-price">${originalPrice}</span>`;
        }
        if (discountBadge) {
          modalPrice.innerHTML += `<span class="discount-badge">${discountBadge}</span>`;
        }
        
        document.getElementById('modal-product-location').querySelector('span').textContent = location;
        
   
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
      });
    });
    
    if (closeModal) {
      closeModal.addEventListener('click', function() {
        modal.classList.remove('active');
        document.body.style.overflow = '';
      });
    }
    
  
    window.addEventListener('click', function(e) {
      if (e.target === modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
      }
    });
    
    
    const thumbnails = document.querySelectorAll('.thumbnail');
    thumbnails.forEach(thumb => {
      thumb.addEventListener('click', function() {
     
        thumbnails.forEach(t => t.classList.remove('active'));
        
      
        this.classList.add('active');
        
   
        const mainImage = document.getElementById('modal-main-image');
        mainImage.src = this.querySelector('img').src;
      });
    });
    
  
    const minusBtn = document.querySelector('.quantity-btn.minus');
    const plusBtn = document.querySelector('.quantity-btn.plus');
    const quantityInput = document.getElementById('quantity');
    
    if (minusBtn && plusBtn && quantityInput) {
      minusBtn.addEventListener('click', function() {
        let value = parseInt(quantityInput.value);
        if (value > 1) {
          quantityInput.value = value - 1;
        }
      });
      
      plusBtn.addEventListener('click', function() {
        let value = parseInt(quantityInput.value);
        let max = parseInt(quantityInput.getAttribute('max'));
        if (value < max) {
          quantityInput.value = value + 1;
        }
      });
    }
    

    const favoriteButtons = document.querySelectorAll('.product-favorite');
    favoriteButtons.forEach(btn => {
      btn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        this.classList.toggle('active');
        
    
        const svg = this.querySelector('svg');
        if (this.classList.contains('active')) {
          svg.setAttribute('fill', '#ef4444');
        } else {
          svg.setAttribute('fill', 'none');
        }
      });
    });
    
 
    const addToCartBtns = document.querySelectorAll('.add-to-cart-btn');
    addToCartBtns.forEach(btn => {
      btn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
    
        const card = this.closest('.product-card');
        const title = card.querySelector('.product-title').textContent;
        
      
        showNotification(`Added "${title.trim()}" to cart`, 'success');
        
      
        const cartBadge = document.querySelector('.header-action-badge');
        if (cartBadge) {
          let count = parseInt(cartBadge.textContent);
          cartBadge.textContent = count + 1;
        }
      });
    });
    
 
    const addToCartModalBtn = document.querySelector('.add-to-cart-modal-btn');
    if (addToCartModalBtn) {
      addToCartModalBtn.addEventListener('click', function() {
        const title = document.getElementById('modal-product-title').textContent;
        const quantity = document.getElementById('quantity').value;
        
       
        showNotification(`Added ${quantity} "${title.trim()}" to cart`, 'success');
        
     
        const cartBadge = document.querySelector('.header-action-badge');
        if (cartBadge) {
          let count = parseInt(cartBadge.textContent);
          cartBadge.textContent = parseInt(count) + parseInt(quantity);
        }
        

        modal.classList.remove('active');
        document.body.style.overflow = '';
      });
    }
    

    const minHandle = document.getElementById('min-handle');
    const maxHandle = document.getElementById('max-handle');
    const sliderFill = document.querySelector('.price-slider-fill');
    const minPriceInput = document.getElementById('min-price');
    const maxPriceInput = document.getElementById('max-price');
    
    if (minHandle && maxHandle && sliderFill && minPriceInput && maxPriceInput) {
      let isDragging = false;
      let currentHandle = null;
      
 
      updateSliderFromInputs();
      
   
      minPriceInput.addEventListener('input', updateSliderFromInputs);
      maxPriceInput.addEventListener('input', updateSliderFromInputs);
      
      function updateSliderFromInputs() {
        const min = parseInt(minPriceInput.value) || 0;
        const max = parseInt(maxPriceInput.value) || 1000;
        const minPercent = (min / 1000) * 100;
        const maxPercent = (max / 1000) * 100;
        
        minHandle.style.left = `${minPercent}%`;
        maxHandle.style.left = `${maxPercent}%`;
        sliderFill.style.left = `${minPercent}%`;
        sliderFill.style.right = `${100 - maxPercent}%`;
      }
      
   
      minHandle.addEventListener('mousedown', startDrag);
      maxHandle.addEventListener('mousedown', startDrag);
      
      function startDrag(e) {
        isDragging = true;
        currentHandle = e.target;
        document.addEventListener('mousemove', drag);
        document.addEventListener('mouseup', stopDrag);
        e.preventDefault();
      }
      
      function drag(e) {
        if (!isDragging) return;
        
        const sliderTrack = document.querySelector('.price-slider-track');
        const rect = sliderTrack.getBoundingClientRect();
        const trackWidth = rect.width;
        const offsetX = e.clientX - rect.left;
        
        let percent = Math.min(Math.max(0, offsetX / trackWidth * 100), 100);
        
        if (currentHandle === minHandle) {
          const maxPercent = parseFloat(maxHandle.style.left);
          percent = Math.min(percent, maxPercent - 5);
          minHandle.style.left = `${percent}%`;
          sliderFill.style.left = `${percent}%`;
          minPriceInput.value = Math.round(percent / 100 * 1000);
        } else if (currentHandle === maxHandle) {
          const minPercent = parseFloat(minHandle.style.left);
          percent = Math.max(percent, minPercent + 5);
          maxHandle.style.left = `${percent}%`;
          sliderFill.style.right = `${100 - percent}%`;
          maxPriceInput.value = Math.round(percent / 100 * 1000);
        }
      }
      
      function stopDrag() {
        isDragging = false;
        document.removeEventListener('mousemove', drag);
        document.removeEventListener('mouseup', stopDrag);
      }
      
    
      const applyPriceBtn = document.getElementById('apply-price');
      if (applyPriceBtn) {
        applyPriceBtn.addEventListener('click', function() {
          const min = parseInt(minPriceInput.value) || 0;
          const max = parseInt(maxPriceInput.value) || 1000;
          
          showNotification(`Price filter applied: $${min} - $${max}`, 'info');
        });
      }
    }
    
 
    const clearFiltersBtn = document.getElementById('clear-filters');
    if (clearFiltersBtn) {
      clearFiltersBtn.addEventListener('click', function() {
    
        document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
          checkbox.checked = false;
        });
        
 
        if (minPriceInput && maxPriceInput) {
          minPriceInput.value = 10;
          maxPriceInput.value = 1000;
          updateSliderFromInputs();
        }
        
     
        document.querySelectorAll('input[name="rating"]')[0].checked = true;
        

        document.querySelector('.active-filters').innerHTML = '';
        
        showNotification('All filters cleared', 'info');
      });
    }
    
   
    const removeFilterBtns = document.querySelectorAll('.remove-filter');
    removeFilterBtns.forEach(btn => {
      btn.addEventListener('click', function() {
        const filter = this.parentElement;
        const filterText = filter.textContent.trim().replace('×', '').trim();
        
     
        filter.remove();
        
        showNotification(`Filter "${filterText}" removed`, 'info');
      });
    });
    
 
    const pageNumbers = document.querySelectorAll('.page-number');
    const prevPageBtn = document.getElementById('prev-page');
    const nextPageBtn = document.getElementById('next-page');
    
    pageNumbers.forEach(btn => {
      btn.addEventListener('click', function() {
    
        pageNumbers.forEach(b => b.classList.remove('active'));
        
  
        this.classList.add('active');
        
   
        const currentPage = parseInt(this.textContent);
        prevPageBtn.disabled = currentPage === 1;
        nextPageBtn.disabled = currentPage === 10;
        
     
        showNotification(`Navigated to page ${currentPage}`, 'info');
      });
    });
    
    if (prevPageBtn && nextPageBtn) {
      prevPageBtn.addEventListener('click', function() {
 
        const activePage = document.querySelector('.page-number.active');
        const currentPage = parseInt(activePage.textContent);
        
        if (currentPage > 1) {
      
          document.querySelector(`.page-number:nth-child(${currentPage - 1})`).click();
        }
      });
      
      nextPageBtn.addEventListener('click', function() {

        const activePage = document.querySelector('.page-number.active');
        const currentPage = parseInt(activePage.textContent);
        
        if (currentPage < 10) {
     
          const nextPage = document.querySelector(`.page-number:nth-child(${currentPage + 1})`);
          if (nextPage) {
            nextPage.click();
          }
        }
      });
    }
    

    const loadMoreBtn = document.querySelector('.load-more-btn');
    if (loadMoreBtn) {
      loadMoreBtn.addEventListener('click', function() {
   
        this.textContent = 'Loading...';
        this.disabled = true;
        
        setTimeout(() => {
    
          this.textContent = 'Load More Products';
          this.disabled = false;
          
          showNotification('More products loaded', 'success');
        }, 1500);
      });
    }
    

    const newsletterForm = document.querySelector('.newsletter-form');
    if (newsletterForm) {
      newsletterForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const emailInput = this.querySelector('input[type="email"]');
        const email = emailInput.value.trim();
        
        if (email === '') {
          showNotification('Please enter your email address', 'error');
          return;
        }
        
        if (!isValidEmail(email)) {
          showNotification('Please enter a valid email address', 'error');
          return;
        }
        
     
        emailInput.value = '';
        showNotification('Thank you for subscribing to our newsletter!', 'success');
      });
    }
    
 
    function isValidEmail(email) {
      const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
      return re.test(email);
    }
    

    function showNotification(message, type = 'info') {
  
      let notification = document.querySelector('.notification');
      
      if (!notification) {
        notification = document.createElement('div');
        notification.className = 'notification';
        notification.style.cssText = `
          position: fixed;
          bottom: 20px;
          right: 20px;
          padding: 12px 20px;
          border-radius: var(--radius);
          font-size: 14px;
          font-weight: 500;
          z-index: 1000;
          transform: translateY(100px);
          transition: transform 0.3s ease;
          max-width: 300px;
          box-shadow: var(--shadow-md);
        `;
        document.body.appendChild(notification);
      }
      
      if (type === 'success') {
        notification.style.backgroundColor = '#10b981';
        notification.style.color = 'white';
      } else if (type === 'error') {
        notification.style.backgroundColor = '#ef4444';
        notification.style.color = 'white';
      } else {
        notification.style.backgroundColor = '#4f46e5';
        notification.style.color = 'white';
      }
      
    
      notification.textContent = message;
      setTimeout(() => {
        notification.style.transform = 'translateY(0)';
      }, 10);
      
     
      setTimeout(() => {
        notification.style.transform = 'translateY(100px)';
      }, 3000);
    }
  
    const productCards = document.querySelectorAll('.product-card');
    productCards.forEach(card => {
      card.addEventListener('click', function() {
        const title = this.querySelector('.product-title').textContent;
        
    
        const quickViewBtn = this.querySelector('.quick-view-btn');
        if (quickViewBtn) {
          quickViewBtn.click();
        }
      });
    });
    
    
    window.addEventListener('scroll', function() {
      const header = document.querySelector('.header');
      if (window.scrollY > 50) {
        header.style.boxShadow = 'var(--shadow-md)';
      } else {
        header.style.boxShadow = 'var(--shadow)';
      }
    });
  });
  
  window.addEventListener('load', function() {
    const products = document.querySelectorAll('.product-card');
    
    products.forEach((product, index) => {
      setTimeout(() => {
        product.style.opacity = '0';
        product.style.transform = 'translateY(20px)';
        product.style.animation = 'fadeIn 0.5s ease forwards';
        product.style.animationDelay = `${index * 0.1}s`;
      }, 100);
    });
  });
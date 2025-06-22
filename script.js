
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

const close = document.getElementById('close'); 

if (bar) {
  bar.addEventListener('click', () => {
      if (nav) { 
          nav.classList.add('active');
      }
  });
}

if (close) { 
  close.addEventListener('click', () => {
      if (nav) { 
          nav.classList.remove('active');
      }
  });
}



document.addEventListener('DOMContentLoaded', function() {

  const heroBrowseBtn = document.querySelector('#hero .hero-buttons .primary');
  const heroSellBtn = document.querySelector('#hero .hero-buttons .secondary');

  if (heroBrowseBtn) {
      heroBrowseBtn.addEventListener('click', () => {
          window.location.href = 'browse.php';
      });
  }

  if (heroSellBtn) {
      heroSellBtn.addEventListener('click', () => {
          window.location.href = 'sell.php'; 
      });
  }


  const smBannerSellBtn = document.querySelector('#sm-banner .banner-box .white');
  if (smBannerSellBtn) {
      smBannerSellBtn.addEventListener('click', () => {
          window.location.href = 'sell.php'; 
      });
  }


  const smBannerLearnMoreBtn = document.querySelector('#sm-banner .banner-box2 .white');
  if (smBannerLearnMoreBtn) {
      smBannerLearnMoreBtn.addEventListener('click', () => {
          window.location.href = 'how-it-works.php'; 
      });
  }

 
  const howItWorksCtaBtn = document.querySelector('.how-it-works .cta-button .primary');
  if (howItWorksCtaBtn) {
      howItWorksCtaBtn.addEventListener('click', () => {
          window.location.href = 'sell.php'; 
      });
  }

  
  const bannerListCarBtn = document.querySelector('#banner button.normal');
  if (bannerListCarBtn) {
      bannerListCarBtn.addEventListener('click', () => {
          window.location.href = 'sell.php'; 
      });
  }


  const changeLocationBtn = document.querySelector('.location-selector .change-location');
  if (changeLocationBtn) {
      changeLocationBtn.addEventListener('click', () => {
        
          alert('Changing location functionality not yet implemented. Redirecting to a placeholder.');
    
      });
  }

 
  const mobileSearchIcon = document.querySelector('#mobile .search-icon');
  const mobileSearchContainer = document.querySelector('.mobile-search');

  if (mobileSearchIcon && mobileSearchContainer) {
      mobileSearchIcon.addEventListener('click', function() {
          mobileSearchContainer.classList.toggle('active');
      });
  }

 
  const tabButtons = document.querySelectorAll('.tabs .tab-btn');
  const tabContents = document.querySelectorAll('.tab-content');

  tabButtons.forEach(button => {
      button.addEventListener('click', () => {
          const targetTab = button.dataset.tab;

          tabButtons.forEach(btn => btn.classList.remove('active'));
          tabContents.forEach(content => content.classList.remove('active'));

          button.classList.add('active');
          const contentToShow = document.getElementById(targetTab + '-tab');
          if (contentToShow) {
              contentToShow.classList.add('active');
          }
      });
  });

  // Wishlist Button 
  /*
  document.querySelectorAll('.wishlist-btn').forEach(button => {
      button.addEventListener('click', function(e) {
          e.preventDefault(); // Prevent default link behavior
          const productId = this.href.split('add=')[1]; // Extract product ID
          const icon = this.querySelector('i');

          // Example AJAX call (you'd need to create wishlist_action.php)
          fetch(`wishlist_action.php?product_id=${productId}`, {
              method: 'POST', // Or GET, depending on your endpoint
              headers: {
                  'Content-Type': 'application/json'
              },
              // body: JSON.stringify({ productId: productId }) // If sending JSON
          })
          .then(response => response.json())
          .then(data => {
              if (data.status === 'success') {
                  // Toggle heart icon (e.g., fas fa-heart for filled, far fa-heart for outline)
                  icon.classList.toggle('far');
                  icon.classList.toggle('fas');
                  // Optionally update cart item count if you use a badge for wishlist
                  const cartBadge = document.querySelector('.cart-link .badge');
                  if (cartBadge) {
                      cartBadge.textContent = data.new_count; // Assuming new_count is returned by PHP
                  }
                  console.log('Wishlist updated:', data.message);
              } else {
                  console.error('Error updating wishlist:', data.message);
              }
          })
          .catch(error => {
              console.error('Fetch error:', error);
          });
      });
  });
  */

}); 
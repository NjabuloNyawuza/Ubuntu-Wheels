document.addEventListener('DOMContentLoaded', function() {
 
 
  window.addEventListener('scroll', function() {
    const header = document.getElementById('header');
    if (window.scrollY > 100) {
      header.classList.add('scrolled');
    } else {
      header.classList.remove('scrolled');
    }
  });
  

  const bar = document.getElementById('bar');
  const nav = document.getElementById('navbar');
  
  if (bar && nav) {
    bar.addEventListener('click', () => {
      nav.classList.add('active');
    });
    
  
    if (!document.getElementById('close')) {
      const closeBtn = document.createElement('i');
      closeBtn.id = 'close';
      closeBtn.className = 'fas fa-times';
      closeBtn.style.cssText = 'position: absolute; top: 30px; left: 30px; font-size: 24px; cursor: pointer;';
      nav.prepend(closeBtn);
      
      closeBtn.addEventListener('click', () => {
        nav.classList.remove('active');
      });
    } else {
 
      document.getElementById('close').addEventListener('click', () => {
        nav.classList.remove('active');
      });
    }
  }

  const paymentMethods = document.querySelectorAll('input[name="payment_method"]');
  const cardDetailsForm = document.getElementById('card-details-form');
  

  if (cardDetailsForm) { 
      const checkedPaymentMethod = document.querySelector('input[name="payment_method"]:checked');
      if (checkedPaymentMethod && checkedPaymentMethod.value !== 'card') {
          cardDetailsForm.style.display = 'none';
      }
  }

  paymentMethods.forEach(method => {
    method.addEventListener('change', function() {
      if (this.value === 'card') {
        cardDetailsForm.style.display = 'block';
      } else {
        cardDetailsForm.style.display = 'none';
      }
    });
  });
  
 
  const cardNumberInput = document.getElementById('card_number');
  
  if (cardNumberInput) {
    cardNumberInput.addEventListener('input', function(e) {
      let value = e.target.value.replace(/\D/g, '');
     
      if (value.length > 0) {
        value = value.match(new RegExp('.{1,4}', 'g')).join(' ');
      }
      
      e.target.value = value;
      
    
      if (e.target.value.length > 19) {
        e.target.value = e.target.value.slice(0, 19);
      }
    });
  }
  

  const expiryDateInput = document.getElementById('expiry_date');
  
  if (expiryDateInput) {
    expiryDateInput.addEventListener('input', function(e) {
      let value = e.target.value.replace(/\D/g, '');
      
      if (value.length > 2) {
        value = value.slice(0, 2) + '/' + value.slice(2);
      }
      
      e.target.value = value;
      
    
      if (e.target.value.length > 5) {
        e.target.value = e.target.value.slice(0, 5);
      }
    });
  }
  
 
  const cvvInput = document.getElementById('cvv');
  
  if (cvvInput) {
    cvvInput.addEventListener('input', function(e) {
      let value = e.target.value.replace(/\D/g, '');
      e.target.value = value;
      
     
      if (e.target.value.length > 4) {
        e.target.value = e.target.value.slice(0, 4);
      }
    });
  }
  
 
  const checkoutForm = document.getElementById('checkout-form');
  
  if (checkoutForm) {
    checkoutForm.addEventListener('submit', function(e) {
      const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
      const termsAgree = document.getElementById('terms_agree');
      
      let clientValidationPassed = true; 

      
      if (!termsAgree.checked) {
        showToast('You must agree to the Terms and Conditions, Privacy Policy, and Refund Policy.', 'error');
        termsAgree.focus();
        clientValidationPassed = false;
      }
      
   
      if (paymentMethod === 'card') {
        const cardNumber = document.getElementById('card_number').value.replace(/\s/g, '');
        const cardName = document.getElementById('card_name').value;
        const expiryDate = document.getElementById('expiry_date').value; // MM/YY
        const cvv = document.getElementById('cvv').value;
        
      
        if (cardNumber.length !== 16 || !/^\d{16}$/.test(cardNumber)) {
          showToast('Please enter a valid 16-digit card number.', 'error');
       
          if (clientValidationPassed) document.getElementById('card_number').focus();
          clientValidationPassed = false;
        }
        
     
        if (cardName.trim() === '') {
          showToast('Please enter the name on your card.', 'error');
          if (clientValidationPassed) document.getElementById('card_name').focus();
          clientValidationPassed = false;
        }
        
      
        const expiryRegex = /^(0[1-9]|1[0-2])\/\d{2}$/;
        if (!expiryRegex.test(expiryDate)) {
            showToast('Please enter a valid expiry date (MM/YY).', 'error');
            if (clientValidationPassed) document.getElementById('expiry_date').focus();
            clientValidationPassed = false;
        } else {
           
            const [month, year] = expiryDate.split('/').map(Number);
            const currentYear = new Date().getFullYear() % 100; 
            const currentMonth = new Date().getMonth() + 1; 
            
            if (year < currentYear || (year === currentYear && month < currentMonth)) {
                showToast('Card expiry date cannot be in the past.', 'error');
                if (clientValidationPassed) document.getElementById('expiry_date').focus();
                clientValidationPassed = false;
            }
        }
        
     
        if (cvv.length < 3 || cvv.length > 4 || !/^\d{3,4}$/.test(cvv)) {
          showToast('Please enter a valid CVV (3 or 4 digits).', 'error');
          if (clientValidationPassed) document.getElementById('cvv').focus();
          clientValidationPassed = false;
        }
      }
      
     
      if (!clientValidationPassed) {
        e.preventDefault(); 
      } else {
      
        const submitBtn = document.querySelector('.place-order-btn');
        if (submitBtn) { 
          submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
          submitBtn.disabled = true;
        }
  
      }
    });
  }

  

  const shippingMethods = document.querySelectorAll('input[name="shipping_method"]');
  
  shippingMethods.forEach(method => {
    method.addEventListener('change', function() {
  
      const shippingMethod = this.value;
      
      console.log(`Selected ${shippingMethod} shipping`);
      
  
      showToast(`Shipping method updated to ${shippingMethod}`);
    });
  });
  

  const animateOnScroll = function() {
    const elements = document.querySelectorAll('.form-section, .order-summary, .need-help, .protection-item');
    
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
    `;
    document.body.appendChild(toast);
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
  setTimeout(() => {
    toast.style.transform = 'translateY(0)';
  }, 10);
  

  setTimeout(() => {
    toast.style.transform = 'translateY(100px)';
  }, 3000);
}

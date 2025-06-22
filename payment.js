document.addEventListener('DOMContentLoaded', function() {
    const paymentMethods = document.querySelectorAll('.payment-method input[type="radio"]');
    const creditCardForm = document.getElementById('credit-card-form');
    const paymentMethodLabels = document.querySelectorAll('.payment-method');
    const sameAddressCheckbox = document.getElementById('same-address');
    const billingAddressForm = document.getElementById('billing-address-form');
    const paymentForm = document.getElementById('payment-form');
    const placeOrderBtn = document.getElementById('place-order');
    const cardNumberInput = document.getElementById('card-number');
    const expiryDateInput = document.getElementById('expiry-date');
    const cvvInput = document.getElementById('cvv');

  
    function handlePaymentMethodChange() {
        const creditCardForm = document.getElementById('credit-card-form'); 
        console.log('handlePaymentMethodChange triggered. Selected value:', this.value);
        console.log('creditCardForm element:', creditCardForm);
        if (this.value === 'credit-card') {
            if (creditCardForm) {
                creditCardForm.style.display = 'block';
            } else {
                console.log('creditCardForm not found in handlePaymentMethodChange');
            }
        } else {
            if (creditCardForm) {
                creditCardForm.style.display = 'none';
            } else {
                console.log('creditCardForm not found in handlePaymentMethodChange (hiding)');
            }
        }
    }

    paymentMethods.forEach(radio => {
        radio.addEventListener('change', handlePaymentMethodChange);
        console.log('Added change listener to radio:', radio);
    });

    paymentMethodLabels.forEach(method => {
        const radio = method.querySelector('input[type="radio"]');
        method.addEventListener('click', function() {
            paymentMethodLabels.forEach(m => m.classList.remove('selected'));
            this.classList.add('selected');
            radio.checked = true;
            console.log('Payment method label clicked. Selected value:', radio.value);
            handlePaymentMethodChange.call(radio); 
        });
    });


    const selectedPaymentMethod = document.querySelector('.payment-method input[type="radio"]:checked');
    const initialCreditCardForm = document.getElementById('credit-card-form'); 
    console.log('Initial selected payment method:', selectedPaymentMethod ? selectedPaymentMethod.value : 'none');
    console.log('initialCreditCardForm element:', initialCreditCardForm);
    if (selectedPaymentMethod && selectedPaymentMethod.value !== 'credit-card') {
        if (initialCreditCardForm) {
            initialCreditCardForm.style.display = 'none';
        } else {
            console.log('initialCreditCardForm not found during initial check');
        }
    } else if (selectedPaymentMethod && selectedPaymentMethod.value === 'credit-card') {
        if (initialCreditCardForm) {
            initialCreditCardForm.style.display = 'block';
        } else {
            console.log('initialCreditCardForm not found during initial check (credit-card selected)');
        }
    } else if (initialCreditCardForm) {
        initialCreditCardForm.style.display = 'none'; 
    }

   
    if (sameAddressCheckbox && billingAddressForm) {
        sameAddressCheckbox.addEventListener('change', function() {
            console.log('sameAddressCheckbox changed. Checked:', this.checked);
            billingAddressForm.style.display = this.checked ? 'none' : 'block';
        });
    }


    if (placeOrderBtn && paymentForm) {
        placeOrderBtn.addEventListener('click', function(e) {
            const selectedMethod = document.querySelector('input[name="payment-method"]:checked')?.value;
            console.log('Place Order button clicked. Selected payment method:', selectedMethod);

            if (selectedMethod === 'credit-card') {
                const requiredFields = paymentForm.querySelectorAll('#credit-card-form [required]');
                let isValid = true;
                console.log('Validating credit card form.');

                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        isValid = false;
                        field.classList.add('error');
                        field.style.borderColor = '#e53935';
                        field.addEventListener('input', () => {
                            field.style.borderColor = '';
                            field.classList.remove('error');
                        }, { once: true });
                        console.log('Required field empty:', field.id);
                    }
                });

                if (cardNumberInput?.value.trim() && !/^\d{13,19}$/.test(cardNumberInput.value.replace(/\s/g, ''))) {
                    isValid = false;
                    cardNumberInput.classList.add('error');
                    cardNumberInput.style.borderColor = '#e53935';
                    console.log('Invalid card number format:', cardNumberInput.value);
                }

                if (expiryDateInput?.value.trim() && !/^\d{2}\/\d{2}$/.test(expiryDateInput.value)) {
                    isValid = false;
                    expiryDateInput.classList.add('error');
                    expiryDateInput.style.borderColor = '#e53935';
                    console.log('Invalid expiry date format:', expiryDateInput.value);
                }

                if (cvvInput?.value.trim() && !/^\d{3,4}$/.test(cvvInput.value)) {
                    isValid = false;
                    cvvInput.classList.add('error');
                    cvvInput.style.borderColor = '#e53935';
                    console.log('Invalid CVV format:', cvvInput.value);
                }

                if (!isValid) {
                    e.preventDefault();
                    const firstError = paymentForm.querySelector('.error');
                    if (firstError) {
                        firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        firstError.focus();
                        alert('Please check your payment information and try again.');
                    } else {
                        alert('Please fill in all required fields.');
                    }
                    console.log('Validation failed.');
                } else {
                    console.log('Credit card validation passed.');
                }
            } else {
                console.log('Payment method is not credit-card. Skipping validation.');
           
            }
        });
    }


    cardNumberInput?.addEventListener('input', function() {
        const value = this.value.replace(/\D/g, '').slice(0, 16);
        let formattedValue = '';
        for (let i = 0; i < value.length; i++) {
            formattedValue += value[i];
            if ((i + 1) % 4 === 0 && i < value.length - 1) {
                formattedValue += ' ';
            }
        }
        this.value = formattedValue;
        console.log('Card number input:', this.value);
    });

    expiryDateInput?.addEventListener('input', function() {
        const value = this.value.replace(/\D/g, '').slice(0, 4);
        if (value.length > 2) {
            this.value = value.slice(0, 2) + '/' + value.slice(2);
        } else {
            this.value = value;
        }
        console.log('Expiry date input:', this.value);
    });

    cvvInput?.addEventListener('input', function() {
        this.value = this.value.replace(/\D/g, '').slice(0, 4);
        console.log('CVV input:', this.value);
    });
});
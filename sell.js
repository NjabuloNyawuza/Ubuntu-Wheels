document.addEventListener('DOMContentLoaded', function() {
  
    const formSections = document.querySelectorAll('.form-section');
    const progressSteps = document.querySelectorAll('.progress-step');
    const progressLines = document.querySelectorAll('.progress-line');
    const nextButtons = document.querySelectorAll('.btn-next');
    const backButtons = document.querySelectorAll('.btn-back');
    const form = document.querySelector('#sell-form form'); 

   
    function updateProgress() {
        const activeSection = document.querySelector('.form-section.active');
        if (!activeSection) return;

        const activeSectionId = activeSection.id;
        let activeIndex = 0;

     
        if (activeSectionId === 'section-details') {
            activeIndex = 0;
        } else if (activeSectionId === 'section-photos') {
            activeIndex = 1;
        } else if (activeSectionId === 'section-price') {
            activeIndex = 2;
        } else if (activeSectionId === 'section-review') {
            activeIndex = 3;
        }

      
        progressSteps.forEach((step, index) => {
            if (index < activeIndex) {
                step.classList.add('completed');
                step.classList.remove('active');
            } else if (index === activeIndex) {
                step.classList.add('active');
                step.classList.remove('completed');
            } else {
                step.classList.remove('active', 'completed');
            }
        });

    
        progressLines.forEach((line, index) => {
            if (index < activeIndex) {
                line.classList.add('completed');
            } else {
                line.classList.remove('completed');
            }
        });
    }


    nextButtons.forEach(button => {
        button.addEventListener('click', function(event) {
            event.preventDefault(); 

            const nextSectionId = this.getAttribute('data-next');
            const currentSection = this.closest('.form-section');
            const nextSection = document.getElementById(nextSectionId);

        
            if (validateSection(currentSection)) {
                currentSection.classList.remove('active');
                nextSection.classList.add('active');

                updateProgress(); 

            
                document.getElementById('sell-form').scrollIntoView({ behavior: 'smooth' });

             
                if (nextSectionId === 'section-review') {
                    updatePreview();
                }
            }
        });
    });


    backButtons.forEach(button => {
        button.addEventListener('click', function(event) {
            event.preventDefault(); 

            const prevSectionId = this.getAttribute('data-back');
            const currentSection = this.closest('.form-section');
            const prevSection = document.getElementById(prevSectionId);

            currentSection.classList.remove('active');
            prevSection.classList.add('active');

            updateProgress(); 

   
            document.getElementById('sell-form').scrollIntoView({ behavior: 'smooth' });
        });
    });

  
    updateProgress();

    // --- Form Validation Logic ---
    /**
     * Displays an error message below an input field.
     * @param {HTMLElement} input - The input element to show the error for.
     * @param {string} message - The error message to display.
     */
    function showError(input, message) {
        clearError(input); 

        const errorDiv = document.createElement('div');
        errorDiv.className = 'error';
        errorDiv.textContent = message;

     
        if (input.type === 'radio' || input.type === 'checkbox') {
            const parentGroup = input.closest('.form-group');
            if (parentGroup) {
              
                parentGroup.appendChild(errorDiv);
            }
        } else {
        
            input.parentNode.insertBefore(errorDiv, input.nextSibling);
        }
        input.classList.add('input-error'); 
    }

    /**
     * Clears any error messages associated with an input field.
     * @param {HTMLElement} input - The input element to clear errors for.
     */
    function clearError(input) {
    
        const parent = input.closest('.form-group') || input.parentNode;
        const existingError = parent.querySelector('.error');
        if (existingError) {
            existingError.remove();
        }
        input.classList.remove('input-error'); 
    }

    /**
     * Validates all required fields within a given form section.
     * @param {HTMLElement} section - The form section element to validate.
     * @returns {boolean} - True if the section is valid, false otherwise.
     */
    function validateSection(section) {
        // Select all required inputs, textareas, and the condition radio group
        const inputs = section.querySelectorAll('input[required], select[required], textarea[required], input[name="condition"]');
        let sectionIsValid = true;

        inputs.forEach(input => {
            if (input.type === 'radio' || input.type === 'checkbox') {
                const groupName = input.name;
             
                const checkedInput = section.querySelector(`input[name="${groupName}"]:checked`);
                if (!checkedInput) {
                    showError(input, `Please select a ${groupName}.`);
                    sectionIsValid = false;
                } else {
                    clearError(input);
                }
            } else if (!input.value.trim()) {
               
                const fieldName = input.previousElementSibling ? input.previousElementSibling.textContent.replace('*', '').trim() : 'This field';
                showError(input, `${fieldName} is required.`);
                sectionIsValid = false;
            } else {
                clearError(input);
            }
        });

 
        const descriptionInput = section.querySelector('#description');
        if (descriptionInput && descriptionInput.value.trim().length < 30) {
            showError(descriptionInput, 'Description must be at least 30 characters long.');
            sectionIsValid = false;
        }

     
        const priceInput = section.querySelector('#price');
        if (priceInput && (isNaN(priceInput.value) || parseFloat(priceInput.value) <= 0)) {
            showError(priceInput, 'Price must be a positive number.');
            sectionIsValid = false;
        }

   
        if (section.id === 'section-review') {
            const termsCheckbox = document.getElementById('terms');
            if (termsCheckbox && !termsCheckbox.checked) {
                showError(termsCheckbox, 'You must agree to the Terms and Conditions.');
                sectionIsValid = false;
            } else {
                clearError(termsCheckbox);
            }
        }

        return sectionIsValid;
    }

   
    const inputsToMonitor = [
        '#title', '#description', '#price', '#location', '#area', '#category',
        '#pickup', '#delivery', '#shipping', '#negotiable'
    ];


    inputsToMonitor.forEach(selector => {
        const input = document.querySelector(selector);
        if (input) {
      
            const eventType = (input.tagName === 'INPUT' && (input.type === 'text' || input.type === 'number')) || input.tagName === 'TEXTAREA' ? 'input' : 'change';
            input.addEventListener(eventType, updatePreview);
        }
    });

 
    document.querySelectorAll('input[name="condition"]').forEach(radio => {
        radio.addEventListener('change', updatePreview);
    });

    /**
     * Updates the content of the listing preview section with current form data.
     */
    function updatePreview() {
        // Update Title
        const title = document.getElementById('title').value;
        document.getElementById('preview-title').textContent = title || 'Item Title';

        // Update Price
        const priceInput = document.getElementById('price');
        let priceValue = priceInput ? parseFloat(priceInput.value) : 0;
        document.getElementById('preview-price').textContent = !isNaN(priceValue) && priceValue > 0 ? priceValue.toFixed(2) : '0.00';

        // Update Location and Area
        const locationSelect = document.getElementById('location');
        const areaInput = document.getElementById('area');
        const selectedLocationText = locationSelect.options[locationSelect.selectedIndex]?.text || 'Location';
        const areaValue = areaInput.value.trim();
        document.getElementById('preview-location').textContent = areaValue ? `${areaValue}, ${selectedLocationText}` : selectedLocationText;

        // Update Condition
        const conditionChecked = document.querySelector('input[name="condition"]:checked');
        document.getElementById('preview-condition').textContent = conditionChecked ? conditionChecked.value : 'Not specified';

        // Update Category
        const categorySelect = document.getElementById('category');
        document.getElementById('preview-category').textContent = categorySelect.options[categorySelect.selectedIndex]?.text || 'Not specified';

        // Update Description
        const descriptionInput = document.getElementById('description');
        document.getElementById('preview-description').textContent = descriptionInput.value.trim() || 'No description provided.';

        // Update Delivery Options
        const deliveryOptionsList = document.getElementById('preview-delivery-options');
        if (deliveryOptionsList) {
            deliveryOptionsList.innerHTML = ''; // Clear existing options

            if (document.getElementById('pickup').checked) {
                const li = document.createElement('li');
                li.textContent = 'In-person pickup';
                deliveryOptionsList.appendChild(li);
            }
            if (document.getElementById('delivery').checked) {
                const li = document.createElement('li');
                li.textContent = 'Seller can deliver';
                deliveryOptionsList.appendChild(li);
            }
            if (document.getElementById('shipping').checked) {
                const li = document.createElement('li');
                li.textContent = 'Nationwide shipping available';
                deliveryOptionsList.appendChild(li);
            }

            // If no delivery options are selected, display a message
            if (deliveryOptionsList.children.length === 0) {
                const li = document.createElement('li');
                li.textContent = 'No delivery options selected.';
                deliveryOptionsList.appendChild(li);
            }
        }

        // Update Main Preview Image in the Review Section
        const mainPhotoInput = document.getElementById('main-photo');
        const previewMainImage = document.getElementById('preview-main-image');
        if (mainPhotoInput && mainPhotoInput.files && mainPhotoInput.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                if (previewMainImage) {
                    previewMainImage.src = e.target.result;
                }
            };
            reader.readAsDataURL(mainPhotoInput.files[0]);
        } else if (previewMainImage) {
            // Revert to a placeholder image if no file is selected for the main photo
            previewMainImage.src = 'images/placeholder-image.jpg';
        }
    }



    const descriptionTextarea = document.getElementById('description');
    const charCountSpan = document.querySelector('.char-count span');

    if (descriptionTextarea && charCountSpan) {
        descriptionTextarea.addEventListener('input', function() {
            const count = this.value.length;
            charCountSpan.textContent = count; // Update the displayed character count

            // Provide visual feedback if the character limit is exceeded
            if (count > 2000) {
                charCountSpan.style.color = 'red';
               
            } else {
                charCountSpan.style.color = ''; // Reset color
            }
        });


        descriptionTextarea.dispatchEvent(new Event('input'));
    }

 

    const photoInputs = document.querySelectorAll('.photo-input');

    photoInputs.forEach(input => {
        input.addEventListener('change', function(e) {
            const file = this.files[0];
            const uploadBox = this.closest('.photo-upload-box');
       
            const previewImage = uploadBox.querySelector('.photo-preview');
            const uploadIcon = uploadBox.querySelector('.upload-icon');
            const uploadText = uploadBox.querySelector('.upload-text');
            let removeButton = uploadBox.querySelector('.remove-photo');

            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    // Hide original upload elements when a photo is selected
                    if (uploadIcon) uploadIcon.style.display = 'none';
                    if (uploadText) uploadText.style.display = 'none';

                    // Display the selected image
                    previewImage.src = e.target.result;
                    previewImage.style.display = 'block';

                    if (!removeButton) {
                        removeButton = document.createElement('div');
                        removeButton.className = 'remove-photo';
                        removeButton.innerHTML = '<i class="fas fa-times"></i>';
                        uploadBox.appendChild(removeButton);

                        // Add event listener to the remove button
                        removeButton.addEventListener('click', function(event) {
                            event.stopPropagation(); // Prevent the click from bubbling up to the input
                            // Clear the file input, hide the preview, and show original upload elements
                            input.value = ''; // This clears the selected file
                            previewImage.src = '';
                            previewImage.style.display = 'none';
                            if (uploadIcon) uploadIcon.style.display = '';
                            if (uploadText) uploadText.style.display = '';
                            this.remove(); // Remove the remove button itself

                            // If this was the main photo, update the review section's main image to placeholder
                            if (uploadBox.classList.contains('main-upload')) {
                                updatePreview(); // Re-evaluate main preview image in review section
                            }
                        });
                    }

                    // If this is the main photo input, update the preview image in the review section as well
                    if (uploadBox.classList.contains('main-upload')) {
                        updatePreview();
                    }
                };
                reader.readAsDataURL(file);
            } else {
       
                previewImage.src = '';
                previewImage.style.display = 'none';
                if (uploadIcon) uploadIcon.style.display = '';
                if (uploadText) uploadText.style.display = '';
                if (removeButton) removeButton.remove();

                // If it was the main photo, revert the preview image in the review section
                if (uploadBox.classList.contains('main-upload')) {
                    updatePreview();
                }
            }
        });
    });




    const categorySelect = document.getElementById('category');
    const subcategorySelect = document.getElementById('subcategory');

    if (categorySelect && subcategorySelect) {
        /**
         * Fetches and populates subcategories based on the selected category ID.
         * @param {string} categoryId - The ID of the selected main category.
         * @param {string|null} selectedSubcategoryId - Optional: The ID of a subcategory to pre-select.
         */
        function populateSubcategories(categoryId, selectedSubcategoryId = null) {
            // Clear current options and disable the subcategory select
            subcategorySelect.innerHTML = '<option value="">Select a subcategory</option>';
            subcategorySelect.disabled = true;

            // Only proceed if a valid categoryId is provided
            if (categoryId) {
                // Make an AJAX request to fetch subcategories from your server
                fetch(`/iteca/fetch_subcategories.php?category_id=${categoryId}`)
                    .then(response => {
                        // Check if the network response was successful
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json(); // Parse the JSON response
                    })
                    .then(data => {
                        // Clear options again before populating (in case of re-fetch)
                        subcategorySelect.innerHTML = '<option value="">Select a subcategory</option>';

                        if (data.length > 0) {
                            // Populate the subcategory dropdown with fetched data
                            data.forEach(sub => {
                                const option = document.createElement('option');
                                option.value = sub.id;
                                option.textContent = sub.name;
                                // If a pre-selected subcategory ID is provided, mark it as selected
                                if (selectedSubcategoryId && selectedSubcategoryId == sub.id) {
                                    option.selected = true;
                                }
                                subcategorySelect.appendChild(option);
                            });
                            subcategorySelect.disabled = false; // Enable the dropdown
                        } else {
                            // If no subcategories are found for the selected category
                            subcategorySelect.disabled = true;
                            subcategorySelect.innerHTML = '<option value="">No subcategories available</option>';
                        }
                    })
                    .catch(error => {
                        // Handle any errors during the fetch operation
                        console.error('Error fetching subcategories:', error);
                        subcategorySelect.disabled = true; // Keep disabled on error
                        subcategorySelect.innerHTML = '<option value="">Error loading subcategories</option>';
                    });
            }
            // If no categoryId, the subcategory select remains disabled with default text
        }

        // Event listener for when the main category selection changes
        categorySelect.addEventListener('change', function() {
            const categoryId = this.value;
            // Call populateSubcategories without a pre-selected ID (since it's a new selection)
            populateSubcategories(categoryId);
        });

        // On page load, if a category was previously selected (e.g., due to PHP pre-filling after validation error),
        // populate the subcategories for that initial category.
        const initialCategoryId = categorySelect.value;
        // Get the initial subcategory value from a data attribute in the HTML
        const initialSubcategoryId = subcategorySelect.dataset.initialValue;

        if (initialCategoryId) {
            populateSubcategories(initialCategoryId, initialSubcategoryId);
        }
    }

    // --- "Save as Draft" Button Placeholder ---
    const saveAsDraftBtn = document.querySelector('.btn-save-draft');
    if (saveAsDraftBtn) {
        saveAsDraftBtn.addEventListener('click', function(event) {
            event.preventDefault(); // Prevent full form submission for a draft save
            alert('This feature is under construction. In a live application, your listing data would typically be saved to the database with a "draft" status via an AJAX request.');
            // TODO: Implement actual AJAX call to save draft data without publishing
        });
    }

   
});
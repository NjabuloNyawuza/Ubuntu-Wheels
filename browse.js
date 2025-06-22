document.addEventListener('DOMContentLoaded', function() {
    const productListingSection = document.getElementById('product-listing-section');
    const filterForm = document.getElementById('filter-form');
    const sortSelect = document.getElementById('sort-select');
    const applyPriceBtn = document.querySelector('.btn-apply-price');
    const filterCheckboxes = document.querySelectorAll('.filter-options input[type="checkbox"]');
    const filterSelects = document.querySelectorAll('.filter-options select');
    const viewOptions = document.querySelectorAll('.view-option');
    const clearFiltersBtn = document.querySelector('.clear-filters');

  
    const makeSelect = document.getElementById('make-select');
    const modelSelect = document.getElementById('model-select');
    const minYearInput = document.querySelector('input[name="min_year"]');
    const maxYearInput = document.querySelector('input[name="max_year"]');
    const applyYearBtn = document.querySelector('.btn-apply-year');
    const minMileageInput = document.querySelector('input[name="min_mileage"]');
    const maxMileageInput = document.querySelector('input[name="max_mileage"]');
    const applyMileageBtn = document.querySelector('.btn-apply-mileage');
    const fuelTypeSelect = document.getElementById('fuel-type-select');
    const transmissionSelect = document.getElementById('transmission-select');


    function getFilterParams() {
        const params = new URLSearchParams(window.location.search);

    
        const mainSearchInput = document.querySelector('section#header .search-container input[name="search"]') ||
                                document.querySelector('.mobile-search .search-container input[name="search"]');
        if (mainSearchInput && mainSearchInput.value) {
            params.set('search', mainSearchInput.value);
        } else {
            params.delete('search');
        }

        const formElements = filterForm.elements;
        for (let i = 0; i < formElements.length; i++) {
            const element = formElements[i];
            
            if (element.name && !element.disabled) {
                if (element.type === 'checkbox') {
                 
                    const checkedValues = Array.from(document.querySelectorAll(`input[name="${element.name}"]:checked`)).map(cb => cb.value);
                    if (checkedValues.length > 0) {
                        params.delete(element.name); 
                        checkedValues.forEach(val => params.append(element.name, val));
                    } else {
                        params.delete(element.name);
                    }
                } else if (element.tagName === 'SELECT') {
                    if (element.value) {
                        params.set(element.name, element.value);
                    } else {
                        params.delete(element.name);
                    }
                } else if (element.type === 'number' || element.type === 'text') {
                    
                    if (element.value && !['min_price', 'max_price', 'min_year', 'max_year', 'min_mileage', 'max_mileage'].includes(element.name)) {
                         params.set(element.name, element.value);
                    }
                }
            }
        }

      
        if (document.querySelector('input[name="min_price"]').value) {
            params.set('min_price', document.querySelector('input[name="min_price"]').value);
        } else { params.delete('min_price'); }
        if (document.querySelector('input[name="max_price"]').value) {
            params.set('max_price', document.querySelector('input[name="max_price"]').value);
        } else { params.delete('max_price'); }

        if (minYearInput && minYearInput.value) {
            params.set('min_year', minYearInput.value);
        } else { params.delete('min_year'); }
        if (maxYearInput && maxYearInput.value) {
            params.set('max_year', maxYearInput.value);
        } else { params.delete('max_year'); }

        if (minMileageInput && minMileageInput.value) {
            params.set('min_mileage', minMileageInput.value);
        } else { params.delete('min_mileage'); }
        if (maxMileageInput && maxMileageInput.value) {
            params.set('max_mileage', maxMileageInput.value);
        } else { params.delete('max_mileage'); }



        const currentView = document.querySelector('.view-option.active')?.dataset.view || 'grid';
        params.set('view', currentView);

        const currentPage = parseInt(document.querySelector('.pagination-link.active')?.dataset.page) || 1;
        params.set('page', currentPage);

        
        if (sortSelect) {
            params.set('sort', sortSelect.value);
        } else {
            params.delete('sort'); 
        }

        return params;
    }

  
    function updateUrl(params) {
        const newUrl = `${window.location.pathname}?${params.toString()}`;
        history.pushState(params.toString(), '', newUrl);
    }

  
    async function updateProductDisplay(params, pushState = true) {
        productListingSection.classList.add('loading'); 

        const url = `${window.location.pathname}?${params.toString()}`;

        try {
            const response = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest' 
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

           
            productListingSection.innerHTML = data.productsHtml;

         
            attachEventListenersToDynamicContent();

         
            if (data.models && makeSelect && modelSelect) {
                populateModelSelect(data.models, params.get('model') || '');
            }

            if (pushState) {
                updateUrl(params);
            }

        } catch (error) {
            console.error('Error fetching products:', error);

            productListingSection.innerHTML = '<div class="no-results"><h2>Error Loading Products</h2><p>Something went wrong. Please try again.</p></div>';
        } finally {
            productListingSection.classList.remove('loading'); 
        }
    }

    function populateModelSelect(models, selectedModel = '') {
        if (!modelSelect) return;

  
        modelSelect.innerHTML = '<option value="">All Models</option>';
        if (models && models.length > 0) {
            models.forEach(model => {
                const option = document.createElement('option');
                option.value = model;
                option.textContent = model;
                if (model === selectedModel) {
                    option.selected = true;
                }
                modelSelect.appendChild(option);
            });
            modelSelect.disabled = false; 
        } else {
            modelSelect.disabled = true; 
        }
    }

    
    function attachEventListenersToDynamicContent() {
   
        const newSortSelect = productListingSection.querySelector('#sort-select');
        if (newSortSelect) {
            newSortSelect.addEventListener('change', function() {
                const params = getFilterParams();
                params.set('sort', this.value);
                params.set('page', 1); 
                updateProductDisplay(params);
            });
         
            const mainSortSelect = document.getElementById('sort-select');
            if(mainSortSelect && mainSortSelect !== newSortSelect) {
                mainSortSelect.value = newSortSelect.value;
            }
        }

        const paginationLinks = productListingSection.querySelectorAll('.pagination-link, .pagination-arrow');
        paginationLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const pageNum = this.dataset.page;
                const params = getFilterParams();
                params.set('page', pageNum);
                updateProductDisplay(params);
            });
        });

  
        const newViewOptions = productListingSection.querySelectorAll('.view-option');
        newViewOptions.forEach(option => {
            option.addEventListener('click', function(e) {
                e.preventDefault();
                const newView = this.dataset.view;
                const params = getFilterParams();
                params.set('view', newView);
              
                const filterFormViewInput = document.querySelector('#filter-form input[name="view"]');
                if (filterFormViewInput) filterFormViewInput.value = newView;

                // Update the active class on both sets of view options
                document.querySelectorAll('.view-option').forEach(opt => {
                    opt.classList.remove('active');
                    if (opt.dataset.view === newView) {
                        opt.classList.add('active');
                    }
                });

                updateProductDisplay(params);
            });
        });

     
        attachProductCardActionListeners();
    }

    function attachInitialListeners() {
     
        if (sortSelect) {
            sortSelect.addEventListener('change', function() {
                const params = getFilterParams();
                params.set('sort', this.value);
                params.set('page', 1); 
                updateProductDisplay(params);
            });
        }

    
        if (applyPriceBtn) {
            applyPriceBtn.addEventListener('click', function() {
                const params = getFilterParams();
                params.set('page', 1); 
                updateProductDisplay(params);
            });
        }

    
        if (applyYearBtn) {
            applyYearBtn.addEventListener('click', function() {
                const params = getFilterParams();
                params.set('page', 1); 
                updateProductDisplay(params);
            });
        }

   
        if (applyMileageBtn) {
            applyMileageBtn.addEventListener('click', function() {
                const params = getFilterParams();
                params.set('page', 1);
                updateProductDisplay(params);
            });
        }

      
        filterCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const params = getFilterParams();
                params.set('page', 1);
                updateProductDisplay(params);
            });
        });

     
        filterSelects.forEach(select => {
            select.addEventListener('change', function() {
                const params = getFilterParams();
                params.set('page', 1); 

                if (this.id === 'make-select') {
                
                    params.delete('model');
                    modelSelect.value = ''; 
                    modelSelect.disabled = true; 

                   
                }

                updateProductDisplay(params);
            });
        });


 
        viewOptions.forEach(option => {
            option.addEventListener('click', function(e) {
                e.preventDefault();
                const newView = this.dataset.view;
                const params = getFilterParams();
                params.set('view', newView);
 
                const filterFormViewInput = document.querySelector('#filter-form input[name="view"]');
                if (filterFormViewInput) filterFormViewInput.value = newView;

        
                document.querySelectorAll('.view-option').forEach(opt => {
                    opt.classList.remove('active');
                    if (opt.dataset.view === newView) {
                        opt.classList.add('active');
                    }
                });
                updateProductDisplay(params);
            });
        });

  
        if (clearFiltersBtn) {
            clearFiltersBtn.addEventListener('click', function(e) {
                e.preventDefault();
      
                window.location.href = window.location.pathname;
            });
        }

  
        const mainSearchForm = document.querySelector('section#header .search-container form');
        if (mainSearchForm) {
            mainSearchForm.addEventListener('submit', function(e) {

            });
        }
        const mobileSearchForm = document.querySelector('.mobile-search .search-container form');
        if (mobileSearchForm) {
            mobileSearchForm.addEventListener('submit', function(e) {
  
            });
        }

        attachProductCardActionListeners();
    }

    function attachProductCardActionListeners() {
        const wishlistButtons = document.querySelectorAll('.wishlist-btn');
        wishlistButtons.forEach(button => {
            button.removeEventListener('click', handleWishlistClick); 
            button.addEventListener('click', handleWishlistClick);
        });

        const messageButtons = document.querySelectorAll('.message-btn');
        messageButtons.forEach(button => {
            button.removeEventListener('click', handleMessageClick); 
            button.addEventListener('click', handleMessageClick);
        });
    }

    function handleWishlistClick(e) {
        e.preventDefault();
        const productId = this.getAttribute('href').split('=')[1];
        console.log('Adding product to wishlist:', productId);
        const icon = this.querySelector('i');
        icon.classList.remove('far');
        icon.classList.add('fas');
        icon.style.color = 'var(--primary-color)';
        alert('Product added to wishlist!');

    }

    function handleMessageClick(e) {
        e.preventDefault();
        const href = this.getAttribute('href');
        const sellerId = new URLSearchParams(href.split('?')[1]).get('seller');
        const productId = new URLSearchParams(href.split('?')[1]).get('product');
        console.log('Messaging seller:', sellerId, 'about product:', productId);
     
        window.location.href = `messages.php?seller=${sellerId}&product=${productId}`;
    }


    const showFiltersBtn = document.getElementById('show-filters');
    if (showFiltersBtn) {
        showFiltersBtn.addEventListener('click', function() {
            let overlay = document.querySelector('.mobile-filters-overlay');
            let filtersContainer = document.querySelector('.mobile-filters-container');

            if (!overlay) {
                overlay = document.createElement('div');
                overlay.className = 'mobile-filters-overlay';
                document.body.appendChild(overlay);
            }

            if (!filtersContainer) {
                filtersContainer = document.createElement('div');
                filtersContainer.className = 'mobile-filters-container';
                document.body.appendChild(filtersContainer);
            }

  
            filtersContainer.innerHTML = '';
            const sidebarContent = document.querySelector('.filters-sidebar').cloneNode(true);

         
            const mobileFilterForm = sidebarContent.querySelector('#filter-form');
            if(mobileFilterForm) {
                mobileFilterForm.id = 'mobile-filter-form';
                mobileFilterForm.action = '#'; 
                mobileFilterForm.method = 'GET';
             
                const applyFiltersButton = mobileFilterForm.querySelector('.btn-apply-filters');
                if (applyFiltersButton) {
                    applyFiltersButton.type = 'button';
                }
            }

            const header = `
                <div class="mobile-filters-header">
                    <h3>Filters</h3>
                    <div class="close-filters">
                        <i class="fas fa-times"></i>
                    </div>
                </div>
            `;

            filtersContainer.insertAdjacentHTML('afterbegin', header); 
            filtersContainer.appendChild(sidebarContent); 

            const closeBtn = filtersContainer.querySelector('.close-filters');
            closeBtn.addEventListener('click', function() {
                overlay.style.display = 'none';
                filtersContainer.classList.remove('active');
                document.body.style.overflow = '';
            });

            overlay.addEventListener('click', function() {
                overlay.style.display = 'none';
                filtersContainer.classList.remove('active');
                document.body.style.overflow = '';
            });

            const mobileSortSelect = filtersContainer.querySelector('#sort-select');
            if (mobileSortSelect) {
                mobileSortSelect.addEventListener('change', function() {
                  
                    if(sortSelect) sortSelect.value = this.value;
                    const params = getFilterParams();
                    params.set('sort', this.value);
                    params.set('page', 1);
                    updateProductDisplay(params);
             
                    overlay.style.display = 'none';
                    filtersContainer.classList.remove('active');
                    document.body.style.overflow = '';
                });
            }

     
            function handleMobileApplyButton(buttonSelector, inputSelectors) {
                const mobileButton = filtersContainer.querySelector(buttonSelector);
                if (mobileButton) {
                    mobileButton.addEventListener('click', function() {
                        inputSelectors.forEach(selector => {
                            const mobileInput = filtersContainer.querySelector(selector);
                            const mainInput = document.querySelector(selector);
                            if (mobileInput && mainInput) {
                                mainInput.value = mobileInput.value;
                            }
                        });
                        const params = getFilterParams();
                        params.set('page', 1);
                        updateProductDisplay(params);
                        overlay.style.display = 'none';
                        filtersContainer.classList.remove('active');
                        document.body.style.overflow = '';
                    });
                }
            }


            handleMobileApplyButton('.btn-apply-price', ['input[name="min_price"]', 'input[name="max_price"]']);
   
            handleMobileApplyButton('.btn-apply-year', ['input[name="min_year"]', 'input[name="max_year"]']);

            handleMobileApplyButton('.btn-apply-mileage', ['input[name="min_mileage"]', 'input[name="max_mileage"]']);


            const mobileFilterCheckboxes = filtersContainer.querySelectorAll('.filter-options input[type="checkbox"]');
            mobileFilterCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
      
                    const mainCheckbox = document.getElementById(this.id);
                    if(mainCheckbox) mainCheckbox.checked = this.checked;

                  
                });
            });

            const mobileFilterSelects = filtersContainer.querySelectorAll('.filter-options select');
            mobileFilterSelects.forEach(select => {
                select.addEventListener('change', function() {
              
                    const mainSelect = document.getElementById(this.id);
                    if(mainSelect) mainSelect.value = this.value;

                   
                    if (this.id === 'make-select') {
                    
                        const mainModelSelect = document.getElementById('model-select');
                        const mobileModelSelect = filtersContainer.querySelector('#model-select');

                        if (mainModelSelect) mainModelSelect.value = '';
                        if (mobileModelSelect) mobileModelSelect.value = '';

                        if (mainModelSelect) mainModelSelect.disabled = true;
                        if (mobileModelSelect) mobileModelSelect.disabled = true;

                  
                        const params = getFilterParams();
                        params.set('page', 1);
                        updateProductDisplay(params); 
                    } else {

                        const params = getFilterParams();
                        params.set('page', 1);
                        updateProductDisplay(params);
                    }

                    overlay.style.display = 'none';
                    filtersContainer.classList.remove('active');
                    document.body.style.overflow = '';
                });
            });

            const mobileApplyFiltersBtn = filtersContainer.querySelector('#mobile-filter-form .btn-apply-filters');
            if(mobileApplyFiltersBtn){
                mobileApplyFiltersBtn.addEventListener('click', function() {

                    const params = getFilterParams();
                    params.set('page', 1);
                    updateProductDisplay(params);
                    overlay.style.display = 'none';
                    filtersContainer.classList.remove('active');
                    document.body.style.overflow = '';
                });
            }

    
            overlay.style.display = 'block';
            filtersContainer.classList.add('active');
            document.body.style.overflow = 'hidden'; 
        });
    }


    window.onpopstate = function(event) {
        const currentParams = new URLSearchParams(window.location.search);
        updateProductDisplay(currentParams, false); 


        const formElements = filterForm.elements;
        currentParams.forEach((value, key) => {
            if (formElements[key]) {
                if (formElements[key].type === 'checkbox') {

                    const checkboxes = document.querySelectorAll(`input[name="${key}"]`);
                    checkboxes.forEach(chk => {
                        chk.checked = false; 
                    });
                    currentParams.getAll(key).forEach(val => {
                        const specificCheckbox = document.querySelector(`input[name="${key}"][value="${val}"]`);
                        if (specificCheckbox) {
                            specificCheckbox.checked = true;
                        }
                    });
                } else {
                    formElements[key].value = value;
                }
            }
        });

        if (currentParams.has('min_price')) document.querySelector('input[name="min_price"]').value = currentParams.get('min_price');
        else document.querySelector('input[name="min_price"]').value = '';
        if (currentParams.has('max_price')) document.querySelector('input[name="max_price"]').value = currentParams.get('max_price');
        else document.querySelector('input[name="max_price"]').value = '';

        if (currentParams.has('min_year')) minYearInput.value = currentParams.get('min_year');
        else minYearInput.value = '';
        if (currentParams.has('max_year')) maxYearInput.value = currentParams.get('max_year');
        else maxYearInput.value = '';

        if (currentParams.has('min_mileage')) minMileageInput.value = currentParams.get('min_mileage');
        else minMileageInput.value = '';
        if (currentParams.has('max_mileage')) maxMileageInput.value = currentParams.get('max_mileage');
        else maxMileageInput.value = '';

     
        const currentView = currentParams.get('view') || 'grid';
        document.querySelectorAll('.view-option').forEach(opt => {
            opt.classList.remove('active');
            if (opt.dataset.view === currentView) {
                opt.classList.add('active');
            }
        });

        if (currentParams.has('make')) {
            const currentMake = currentParams.get('make');

            if (makeSelect) makeSelect.value = currentMake;


            if (currentParams.has('model') && modelSelect) {

            } else if (modelSelect) {
                modelSelect.value = '';
                modelSelect.disabled = true;
            }
        } else {
            if (makeSelect) makeSelect.value = '';
            if (modelSelect) {
                modelSelect.value = '';
                modelSelect.disabled = true;
            }
        }


        if (sortSelect && currentParams.has('sort')) {
            sortSelect.value = currentParams.get('sort');
        }
    };

    attachInitialListeners();

    attachEventListenersToDynamicContent();
});
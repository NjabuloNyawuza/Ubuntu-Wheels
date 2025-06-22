document.addEventListener('DOMContentLoaded', function() {

    const sidebarToggle = document.getElementById('sidebar-toggle');
    const adminContainer = document.querySelector('.admin-container');
    const adminMain = document.querySelector('.admin-main');

    if (sidebarToggle && adminContainer && adminMain) {
        sidebarToggle.addEventListener('click', function() {
            adminContainer.classList.toggle('sidebar-collapsed');
            adminMain.classList.toggle('sidebar-collapsed');
        });
    }

  
    const themeToggleBtn = document.getElementById('theme-toggle');
    const body = document.body;

    if (themeToggleBtn && body) {
        const currentTheme = localStorage.getItem('theme') || 'light';
        body.classList.add(currentTheme + '-theme');
        themeToggleBtn.innerHTML = currentTheme === 'dark' ? '<i class="fas fa-sun"></i>' : '<i class="fas fa-moon"></i>';

        themeToggleBtn.addEventListener('click', function() {
            let theme = body.classList.contains('dark-theme') ? 'light' : 'dark';
            body.classList.remove('light-theme', 'dark-theme');
            body.classList.add(theme + '-theme');
            localStorage.setItem('theme', theme);

            themeToggleBtn.innerHTML = theme === 'dark' ? '<i class="fas fa-sun"></i>' : '<i class="fas fa-moon"></i>';
        });
    }

 
    function setupDropdown(buttonSelector, dropdownSelector) {
        const button = document.querySelector(buttonSelector);
        const dropdown = document.querySelector(dropdownSelector);

        if (button && dropdown) {
            button.addEventListener('click', function(event) {
                event.stopPropagation();
                dropdown.classList.toggle('show');
            });

            document.addEventListener('click', function(event) {
                if (!button.contains(event.target) && !dropdown.contains(event.target)) {
                    dropdown.classList.remove('show');
                }
            });
        }
    }

 
    setupDropdown('.header-profile .profile-btn', '.header-profile .profile-dropdown');


    setupDropdown('.header-notifications .notification-btn', '.header-notifications .notification-dropdown');

    setupDropdown('.header-messages .message-btn', '.header-messages .message-dropdown');

    const tabButtons = document.querySelectorAll('.tab-btn');

    if (tabButtons.length > 0) {
        tabButtons.forEach(button => {
            button.addEventListener('click', function() {
                const tabId = this.getAttribute('data-tab');

                document.querySelectorAll('.tab-btn').forEach(btn => {
                    btn.classList.remove('active');
                });

                document.querySelectorAll('.tab-content').forEach(content => {
                    content.classList.remove('active');
                });

                this.classList.add('active');
                document.getElementById(`${tabId}-tab`).classList.add('active');
            });
        });
    }


    const deleteButtons = document.querySelectorAll('.btn-action.delete');
    const deleteModal = document.getElementById('deleteModal');
   
    const modalClose = deleteModal ? deleteModal.querySelector('.modal-close') : null;
    const cancelButton = deleteModal ? deleteModal.querySelector('.btn-cancel') : null;
  
    const deleteConfirmButton = deleteModal ? deleteModal.querySelector('.btn-delete-deleteConfirmButton') : null;

    let itemToDelete = null;

    if (deleteButtons.length > 0 && deleteModal) {
        deleteButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                itemToDelete = this.getAttribute('data-id');
                const itemName = this.getAttribute('data-name') || 'this item';

                const modalItemNameSpan = deleteModal.querySelector('#deleteItemNameSpan');
                if (modalItemNameSpan) {
                    modalItemNameSpan.textContent = itemName;
                }
                deleteModal.classList.add('active');
            });
        });

        if (modalClose) {
            modalClose.addEventListener('click', function() {
                deleteModal.classList.remove('active');
            });
        }

        if (cancelButton) {
            cancelButton.addEventListener('click', function() {
                deleteModal.classList.remove('active');
            });
        }

        

        window.addEventListener('click', function(e) {
            if (e.target === deleteModal) {
                deleteModal.classList.remove('active');
            }
        });
    }

 
    const closeAlertButtons = document.querySelectorAll('.alert .close-alert');
    closeAlertButtons.forEach(button => {
        button.addEventListener('click', function() {
            this.closest('.alert').style.display = 'none';
        });
    });



    const selectAll = document.getElementById('select-all');
    const selectItems = document.querySelectorAll('.select-item');

    if (selectAll && selectItems.length > 0) {
        selectAll.addEventListener('change', function() {
            selectItems.forEach(item => {
                item.checked = this.checked;
            });
        });

        selectItems.forEach(item => {
            item.addEventListener('change', function() {
                const allChecked = Array.from(selectItems).every(item => item.checked);
                const someChecked = Array.from(selectItems).some(item => item.checked);

                selectAll.checked = allChecked;
                selectAll.indeterminate = someChecked && !allChecked;
            });
        });
    }

  
    const globalSearchInput = document.querySelector('.table-search input[type="text"]'); 
    const globalSearchButton = document.querySelector('.table-search button'); 
    const searchResultsOverlay = document.getElementById('searchResultsOverlay');
    const closeSearchResultsButton = document.querySelector('.close-search-results');
    const searchQueryDisplay = document.getElementById('searchQueryDisplay');
    const noSearchResultsMessage = document.getElementById('noSearchResults');

    const searchResultsUsers = document.getElementById('searchResultsUsers');
    const usersResultCount = document.getElementById('usersResultCount');
    const searchUsersCategory = document.getElementById('searchUsersCategory');

    const searchResultsListings = document.getElementById('searchResultsListings');
    const listingsResultCount = document.getElementById('listingsResultCount');
    const searchListingsCategory = document.getElementById('searchListingsCategory');

    const searchResultsTransactions = document.getElementById('searchResultsTransactions');
    const transactionsResultCount = document.getElementById('transactionsResultCount');
    const searchTransactionsCategory = document.getElementById('searchTransactionsCategory');

    const searchResultsReports = document.getElementById('searchResultsReports');
    const reportsResultCount = document.getElementById('reportsResultCount');
    const searchReportsCategory = document.getElementById('searchReportsCategory');

    function renderGlobalSearchResults(results, query) {
        searchQueryDisplay.textContent = query;
        let totalResults = 0;

      
        searchResultsUsers.innerHTML = '';
        searchResultsListings.innerHTML = '';
        searchResultsTransactions.innerHTML = '';
        searchResultsReports.innerHTML = '';

        if (results.users && results.users.length > 0) {
            results.users.forEach(user => {
                const li = document.createElement('li');
                li.innerHTML = `
                    <strong>${user.name}</strong>
                    <span>Email: ${user.email} | Status: ${user.status} | Joined: ${new Date(user.created_at).toLocaleDateString()}</span>
                    <a href="users.php?view=${user.id}" style="font-size:0.8em; color: var(--primary-color);">View User</a>
                `;
                searchResultsUsers.appendChild(li);
            });
            usersResultCount.textContent = results.users.length;
            searchUsersCategory.style.display = 'block';
            totalResults += results.users.length;
        } else {
            searchUsersCategory.style.display = 'none';
        }

        
        if (results.listings && results.listings.length > 0) {
            results.listings.forEach(listing => {
                const li = document.createElement('li');
                li.innerHTML = `
                    <strong>${listing.title}</strong>
                    <span>Price: R${parseFloat(listing.Price).toFixed(2)} | Status: ${listing.status} | Listed: ${new Date(listing.date).toLocaleDateString()}</span>
                    <a href="listings.php?view=${listing.id}" style="font-size:0.8em; color: var(--primary-color);">View Listing</a>
                `;
                searchResultsListings.appendChild(li);
            });
            listingsResultCount.textContent = results.listings.length;
            searchListingsCategory.style.display = 'block';
            totalResults += results.listings.length;
        } else {
            searchListingsCategory.style.display = 'none';
        }

        
        if (results.transactions && results.transactions.length > 0) {
            results.transactions.forEach(transaction => {
                const li = document.createElement('li');
                li.innerHTML = `
                    <strong>Transaction ID: ${transaction.id}</strong>
                    <span>Item: ${transaction.item_name || 'N/A'} | Amount: R${parseFloat(transaction.amount).toFixed(2)} | Status: ${transaction.status}</span>
                    <span>Buyer: ${transaction.buyer_name || 'N/A'} | Seller: ${transaction.seller_name || 'N/A'} | Date: ${new Date(transaction.date).toLocaleDateString()}</span>
                    <a href="transactions.php?view=${transaction.id}" style="font-size:0.8em; color: var(--primary-color);">View Transaction</a>
                `;
                searchResultsTransactions.appendChild(li);
            });
            transactionsResultCount.textContent = results.transactions.length;
            searchTransactionsCategory.style.display = 'block';
            totalResults += results.transactions.length;
        } else {
            searchTransactionsCategory.style.display = 'none';
        }

    
        if (results.reports && results.reports.length > 0) {
            results.reports.forEach(report => {
                const li = document.createElement('li');
                li.innerHTML = `
                    <strong>Report ID: ${report.id}</strong>
                    <span>Reason: ${report.reason} | Status: ${report.status} | Reported Item: ${report.reported_item_name || 'N/A'}</span>
                    <span>Reporter: ${report.reporter_name || 'N/A'} | Date: ${new Date(report.date).toLocaleDateString()}</span>
                    <a href="reports.php?view=${report.id}" style="font-size:0.8em; color: var(--primary-color);">View Report</a>
                `;
                searchResultsReports.appendChild(li);
            });
            reportsResultCount.textContent = results.reports.length;
            searchReportsCategory.style.display = 'block';
            totalResults += results.reports.length;
        } else {
            searchReportsCategory.style.display = 'none';
        }

        if (totalResults === 0) {
            noSearchResultsMessage.style.display = 'block';
        } else {
            noSearchResultsMessage.style.display = 'none';
        }

        searchResultsOverlay.classList.add('active'); 
    }

 
    async function performGlobalSearch() {
        const query = globalSearchInput.value.trim();
        if (query.length < 2) { 
            searchResultsOverlay.classList.remove('active'); 
            return;
        }

        try {
            const response = await fetch(`../api/general_search.php?q=${encodeURIComponent(query)}`);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const data = await response.json();

            if (data.error) {
                console.error('Search API Error:', data.error, data.details);
                alert('Error performing search. See console for details.');
                return;
            }

            renderGlobalSearchResults(data, query);

        } catch (error) {
            console.error('Failed to perform global search:', error);
            alert('Could not perform global search.');
        }
    }

    if (globalSearchInput) {
        
        let searchTimeout;
        globalSearchInput.addEventListener('keyup', function(event) {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                performGlobalSearch();
            }, 500); 
        });

        globalSearchButton.addEventListener('click', performGlobalSearch);
    }

 
    if (closeSearchResultsButton) {
        closeSearchResultsButton.addEventListener('click', () => {
            searchResultsOverlay.classList.remove('active');
            globalSearchInput.value = ''; 
        });
    }

    if (searchResultsOverlay) {
        searchResultsOverlay.addEventListener('click', (event) => {
            if (event.target === searchResultsOverlay) {
                searchResultsOverlay.classList.remove('active');
                globalSearchInput.value = ''; 
            }
        });
    }

    if (typeof Chart !== 'undefined') {

        async function fetchChartData(chartInstance, endpoint, period, unit = '') {
            try {
                
                const response = await fetch(`${endpoint}?period=${period}`);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                const data = await response.json();

                chartInstance.data.labels = data.labels;
                chartInstance.data.datasets[0].data = data.values;
                chartInstance.options.plugins.tooltip.callbacks.label = function(context) {
                    return `${context.dataset.label}: ${unit}${context.raw.toLocaleString()}`;
                };
                chartInstance.options.scales.y.ticks.callback = function(value) {
                    return unit + value.toLocaleString();
                };
                chartInstance.update();
            } catch (error) {
                console.error(`Error fetching chart data from ${endpoint} for period ${period}:`, error);
                
                chartInstance.data.labels = [];
                chartInstance.data.datasets[0].data = [];
                chartInstance.update();
            }
        }


      
        const revenueChartCanvas = document.getElementById('revenueChart');
        let revenueChart; 

        if (revenueChartCanvas) {
            revenueChart = new Chart(revenueChartCanvas, {
                type: 'line',
                data: {
                    labels: [], 
                    datasets: [{
                        label: 'Revenue',
                        data: [], 
                        borderColor: '#ff385c',
                        backgroundColor: 'rgba(255, 56, 92, 0.1)',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            
                        }
                    }
                }
            });

            
            fetchChartData(revenueChart, '../api/get_revenue_data.php', 'month', 'R');

            const revenueChartPeriodButtons = document.querySelectorAll('.chart-card:first-child .btn-chart-option');
            if (revenueChartPeriodButtons.length > 0) {
                revenueChartPeriodButtons.forEach(button => {
                    button.addEventListener('click', function() {
                        revenueChartPeriodButtons.forEach(btn => btn.classList.remove('active'));
                        this.classList.add('active');
                        const period = this.getAttribute('data-period');
                        fetchChartData(revenueChart, '../api/get_revenue_data.php', period, 'R');
                    });
                });
            }
        }

        
        const userGrowthChartCanvas = document.getElementById('userGrowthChart');
        let userGrowthChart; 

        if (userGrowthChartCanvas) {
            userGrowthChart = new Chart(userGrowthChartCanvas, {
                type: 'bar',
                data: {
                    labels: [], 
                    datasets: [{
                        label: 'New Users',
                        data: [], 
                        backgroundColor: '#10b981',
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            
                        }
                    }
                }
            });

           
            fetchChartData(userGrowthChart, '../api/get_user_growth_data.php', 'month', ''); 

            
            const userGrowthChartPeriodButtons = document.querySelectorAll('.chart-card:nth-child(2) .btn-chart-option');
            if (userGrowthChartPeriodButtons.length > 0) {
                userGrowthChartPeriodButtons.forEach(button => {
                    button.addEventListener('click', function() {
                        userGrowthChartPeriodButtons.forEach(btn => btn.classList.remove('active'));
                        this.classList.add('active');
                        const period = this.getAttribute('data-period');
                        fetchChartData(userGrowthChart, '../api/get_user_growth_data.php', period, ''); 
                    });
                });
            }
        }
    }

    
    const tableSearch = document.querySelector('.table-search input');
    if (tableSearch) {
        tableSearch.addEventListener('keyup', function() {
        
            if (searchResultsOverlay && searchResultsOverlay.classList.contains('active')) {
                return;
            }

            const searchTerm = this.value.toLowerCase();
           
            const activeTabContent = document.querySelector('.tab-content.active');
            const tableRows = activeTabContent ? activeTabContent.querySelectorAll('.data-table tbody tr') : [];

            tableRows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }


    const bulkActionSelect = document.querySelector('.bulk-actions select');
    const bulkActionApply = document.querySelector('.bulk-actions .btn-apply');

    if (bulkActionSelect && bulkActionApply) {
        bulkActionApply.addEventListener('click', function() {
            const action = bulkActionSelect.value;
            if (!action) return;

            const selectedItems = Array.from(document.querySelectorAll('.select-item:checked')).map(item => item.value);

            if (selectedItems.length === 0) {
                alert('Please select at least one item.');
                return;
            }

            console.log(`Applying action: ${action} to items:`, selectedItems);
           
        });
    }

    
    const filterApply = document.querySelector('.filter-actions .btn-apply');
    const filterReset = document.querySelector('.filter-actions .btn-reset');

    if (filterApply) {
        filterApply.addEventListener('click', function() {
            const statusFilters = Array.from(document.querySelectorAll('input[name="status[]"]:checked')).map(item => item.value);
            const verifiedFilters = Array.from(document.querySelectorAll('input[name="verified[]"]:checked')).map(item => item.value);

            console.log('Status Filters:', statusFilters);
            console.log('Verified Filters:', verifiedFilters);

            
        });
    }

    if (filterReset) {
        filterReset.addEventListener('click', function() {
            document.querySelectorAll('input[name="status[]"], input[name="verified[]"]').forEach(checkbox => {
                checkbox.checked = true;
            });
        });
    }

    
    const paginationButtons = document.querySelectorAll('.pagination .page-btn:not(.prev):not(.next)');
    const prevButton = document.querySelector('.pagination .page-btn.prev');
    const nextButton = document.querySelector('.pagination .page-btn.next');

    if (paginationButtons.length > 0) {
        paginationButtons.forEach(button => {
            button.addEventListener('click', function() {
                paginationButtons.forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');

                prevButton.disabled = this.textContent === '1';
                nextButton.disabled = this.textContent === paginationButtons[paginationButtons.length - 1].textContent;

                console.log(`Loading page ${this.textContent}`);
            
            });
        });

        if (prevButton) {
            prevButton.addEventListener('click', function() {
                if (this.disabled) return;
                const activePage = document.querySelector('.pagination .page-btn.active');
                const prevPage = activePage ? activePage.previousElementSibling : null;

                if (prevPage && !prevPage.classList.contains('prev') && !prevPage.classList.contains('page-ellipsis')) {
                    activePage.classList.remove('active');
                    prevPage.classList.add('active');

                    this.disabled = prevPage.textContent === '1';
                    if (nextButton) nextButton.disabled = false; 
                    console.log(`Loading page ${prevPage.textContent}`);
                }
            });
        }

        if (nextButton) {
            nextButton.addEventListener('click', function() {
                if (this.disabled) return;
                const activePage = document.querySelector('.pagination .page-btn.active');
                const nextPage = activePage ? activePage.nextElementSibling : null;

                if (nextPage && !nextPage.classList.contains('next') && !nextPage.classList.contains('page-ellipsis')) {
                    activePage.classList.remove('active');
                    nextPage.classList.add('active');

                    if (prevButton) prevButton.disabled = false; 
                    this.disabled = nextPage.textContent === paginationButtons[paginationButtons.length - 1].textContent;
                    console.log(`Loading page ${nextPage.textContent}`);
                }
            });
        }
    }

    
    const refreshButton = document.querySelector('.btn-refresh');
    if (refreshButton) {
        refreshButton.addEventListener('click', function() {
            const icon = this.querySelector('i');
            icon.classList.add('fa-spin');
            console.log('Refreshing data...');
            
            location.reload(); 

            
        });
    }

  
    const dateFilter = document.getElementById('dateFilter');
    if (dateFilter) {
        
        async function fetchDashboardStats(period, startDate = null, endDate = null) {
            let url = `../api/get_dashboard_stats.php?period=${period}`;
            if (period === 'custom_range' && startDate && endDate) {
                url += `&start_date=${startDate}&end_date=${endDate}`;
            }

            try {
                const response = await fetch(url);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                const stats = await response.json();

                if (stats.error) {
                    console.error('API Error:', stats.error, stats.details);
                    alert('Error fetching dashboard statistics. See console for details.');
                    return;
                }

                document.getElementById('totalUsers').textContent = stats.total_users.toLocaleString();
               
                document.getElementById('newUsersToday').textContent = stats.new_users_today.toLocaleString();
                document.getElementById('activeListings').textContent = stats.active_listings.toLocaleString();
                document.getElementById('pendingListings').textContent = stats.pending_listings.toLocaleString();
                document.getElementById('totalTransactions').textContent = stats.total_transactions.toLocaleString();
               
                document.getElementById('transactionsToday').textContent = stats.transactions_today.toLocaleString();
                document.getElementById('totalRevenue').textContent = 'R' + stats.total_revenue.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                
                document.getElementById('revenueToday').textContent = 'R' + stats.revenue_today.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

                
                const unreadAdminMessagesBadge = document.querySelector('.header-messages .badge');
                if (unreadAdminMessagesBadge && stats.unread_admin_messages !== undefined) {
                    unreadAdminMessagesBadge.textContent = stats.unread_admin_messages;
                }
                const unreadNotificationsBadge = document.querySelector('.header-notifications .badge');
                if (unreadNotificationsBadge && stats.unread_notifications !== undefined) {
                    unreadNotificationsBadge.textContent = stats.unread_notifications;
                }


            } catch (error) {
                console.error('Failed to fetch dashboard stats:', error);
                alert('Could not update dashboard statistics.');
            }
        }

       
        dateFilter.addEventListener('change', function() {
            const selectedPeriod = this.value;
          
            if (selectedPeriod === 'custom_range') {
                alert('Custom range functionality not yet implemented. Please select another option.');
                
                this.value = 'all_time';
                fetchDashboardStats('all_time');
            } else {
                fetchDashboardStats(selectedPeriod);
            }
        });

       
    }
});
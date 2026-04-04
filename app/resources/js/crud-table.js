/**
 * Universal CRUD Table Component
 * 
 * Handles data loading, filtering, sorting, and pagination for CRUD tables
 */
class CrudTable {
    constructor(tableId, config) {
        this.tableId = tableId;
        this.config = config;
        this.currentPage = 1;
        this.currentSort = config.defaultSort || 'id';
        this.currentOrder = config.defaultOrder || 'desc';
        this.currentSearch = '';
        this.currentLanguageId = '';
        this.currentLanguageCode = '';
        this.currentFilters = {}; // Dynamic filters
        
        this.init();
    }
    
    init() {
        // Register instance globally
        if (typeof window !== 'undefined') {
            window.crudTables = window.crudTables || {};
            window.crudTables[this.tableId] = this;
        }
        
        // Set default language filter before binding events and loading data
        this.setDefaultLanguageFilter();
        
        this.bindEvents();
        
        // Load data after a short delay to ensure filter is set
        setTimeout(() => {
            this.loadData();
            
            // Update dependent filters after initial load
            if (this._defaultLanguageId) {
                setTimeout(() => {
                    this.updateDependentFilters('language_id', this._defaultLanguageId);
                }, 200);
            }
        }, 50);
    }
    
    setDefaultLanguageFilter() {
        const languageValueInput = document.getElementById(`${this.tableId}-language-value`);
        if (languageValueInput) {
            const defaultLanguageId = languageValueInput.getAttribute('data-default-language-id');
            if (defaultLanguageId && defaultLanguageId !== '') {
                languageValueInput.value = defaultLanguageId;
                this.currentLanguageId = defaultLanguageId;
                this.currentFilters['language_id'] = defaultLanguageId;
                
                // Update display
                const dropdown = document.getElementById(`${this.tableId}-language-dropdown`);
                if (dropdown) {
                    const selectedOption = dropdown.querySelector(`[data-value="${defaultLanguageId}"]`);
                    if (selectedOption) {
                        const code = selectedOption.getAttribute('data-code') || '';
                        const flagCode = selectedOption.getAttribute('data-flag-code') || 'xx';
                        this.currentLanguageCode = code;
                        
                        // Update display
                        const flagDisplay = document.getElementById(`${this.tableId}-language-flag-display`);
                        const textDisplay = document.getElementById(`${this.tableId}-language-text`);
                        if (flagDisplay) {
                            flagDisplay.className = 'fi fi-' + flagCode;
                        }
                        if (textDisplay) {
                            textDisplay.textContent = code.toUpperCase();
                        }
                    }
                }
                
                // Update dependent filters (categories) - will be called after loadData
                // Store for later use
                this._defaultLanguageId = defaultLanguageId;
            }
        }
    }
    
    bindEvents() {
        const searchInput = document.getElementById(`${this.tableId}-search`);
        if (searchInput) {
            let searchTimeout;
            searchInput.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    this.currentSearch = e.target.value;
                    this.currentPage = 1;
                    this.loadData();
                }, 300); // Debounce 300ms
            });
        }
        
        // Language filter - support both old select and new custom dropdown
        const languageSelect = document.getElementById(`${this.tableId}-language`);
        const languageValueInput = document.getElementById(`${this.tableId}-language-value`);
        
        if (languageSelect) {
            // Old select element
            languageSelect.addEventListener('change', (e) => {
                const value = e.target.value;
                this.currentLanguageId = value;
                if (value) {
                    this.currentFilters['language_id'] = value;
                } else {
                    delete this.currentFilters['language_id'];
                }
                const selectedOption = e.target.options[e.target.selectedIndex];
                this.currentLanguageCode = selectedOption.getAttribute('data-code') || '';
                // Update dependent filters (categories)
                this.updateDependentFilters('language_id', value);
                this.currentPage = 1;
                this.loadData();
            });
        } else if (languageValueInput) {
            // New custom dropdown - listen to hidden input changes
            languageValueInput.addEventListener('change', (e) => {
                const value = e.target.value;
                this.currentLanguageId = value;
                if (value) {
                    this.currentFilters['language_id'] = value;
                } else {
                    delete this.currentFilters['language_id'];
                }
                
                // Get code from selected option
                const dropdown = document.getElementById(`${this.tableId}-language-dropdown`);
                if (dropdown) {
                    const selectedOption = dropdown.querySelector(`[data-value="${value}"]`);
                    if (selectedOption) {
                        this.currentLanguageCode = selectedOption.getAttribute('data-code') || '';
                    } else {
                        this.currentLanguageCode = '';
                    }
                } else {
                    this.currentLanguageCode = '';
                }
                
                // Update dependent filters (categories)
                this.updateDependentFilters('language_id', value);
                this.currentPage = 1;
                this.loadData();
            });
        }
        
        // Handle sortable column headers
        if (this.config.enableSort) {
            const tableHead = document.getElementById(`${this.tableId}-table-head`);
            if (tableHead) {
                const sortableHeaders = tableHead.querySelectorAll('th[data-sort-key]');
                sortableHeaders.forEach(header => {
                    header.addEventListener('click', () => {
                        const sortKey = header.getAttribute('data-sort-key');
                        const isCurrentSort = header.getAttribute('data-current-sort') === 'true';
                        
                        // If clicking on the same column, toggle order
                        if (isCurrentSort && this.currentSort === sortKey) {
                            this.currentOrder = this.currentOrder === 'asc' ? 'desc' : 'asc';
                        } else {
                            // If clicking on a different column, set it as sort and default to asc
                            this.currentSort = sortKey;
                            this.currentOrder = 'asc';
                        }
                        
                        // Update UI
                        this.updateSortHeaders();
                        
                        this.currentPage = 1;
                        this.loadData();
                    });
                });
            }
        }
        
        // Initialize sort header UI
        this.updateSortHeaders();
        
        // Define filter dependencies (parent -> children)
        this.filterDependencies = {
            'language_id': ['category_id'],  // When language changes, update categories
            'continent_id': ['region_id']    // When continent changes, update regions
        };
        
        // Bind dynamic filters
        if (this.config.filters) {
            Object.keys(this.config.filters).forEach(filterKey => {
                const filterConfig = this.config.filters[filterKey];
                const filterType = filterConfig.type || 'select';
                
                if (filterType === 'select' || filterType === 'user-select') {
                    const filterSelect = document.getElementById(`${this.tableId}-filter-${filterKey}`);
                    
                    // Handle regular select filters
                    if (filterSelect) {
                        filterSelect.addEventListener('change', (e) => {
                            const value = e.target.value;
                            if (value) {
                                this.currentFilters[filterKey] = value;
                            } else {
                                delete this.currentFilters[filterKey];
                            }
                            // Update dependent filters
                            this.updateDependentFilters(filterKey, value);
                            this.currentPage = 1;
                            this.loadData();
                        });
                    }
                } else if (filterType === 'date-range') {
                    const filterFrom = document.getElementById(`${this.tableId}-filter-${filterKey}-from`);
                    const filterTo = document.getElementById(`${this.tableId}-filter-${filterKey}-to`);
                    const clearBtn = document.querySelector(`[data-table-id="${this.tableId}"][data-filter-key="${filterKey}"].date-range-clear-btn`);
                    
                    if (filterFrom) {
                        filterFrom.addEventListener('change', () => {
                            this.updateDateRangeFilter(filterKey, filterFrom, filterTo);
                        });
                    }
                    
                    if (filterTo) {
                        filterTo.addEventListener('change', () => {
                            this.updateDateRangeFilter(filterKey, filterFrom, filterTo);
                        });
                    }
                    
                    // Clear button handler
                    if (clearBtn) {
                        clearBtn.addEventListener('click', () => {
                            if (filterFrom) filterFrom.value = '';
                            if (filterTo) filterTo.value = '';
                            if (filterFrom) {
                                filterFrom.dispatchEvent(new Event('change'));
                            }
                        });
                    }
                }
            });
        }
    }
    
    async updateDependentFilters(parentFilterKey, parentValue) {
        // Check if this filter has dependencies
        if (!this.filterDependencies[parentFilterKey]) {
            return;
        }
        
        const dependentFilters = this.filterDependencies[parentFilterKey];
        
        // Update each dependent filter
        for (const dependentKey of dependentFilters) {
            await this.loadFilterOptions(dependentKey, parentFilterKey, parentValue);
            
            // Clear the dependent filter value if parent was cleared
            if (!parentValue) {
                delete this.currentFilters[dependentKey];
            }
        }
    }
    
    async loadFilterOptions(filterKey, parentFilterKey, parentValue) {
        // Map filter keys to API filter types
        const filterTypeMap = {
            'category_id': 'categories',
            'region_id': 'regions'
        };
        
        const filterType = filterTypeMap[filterKey];
        if (!filterType) {
            return; // Unknown filter type
        }
        
        // Build API URL
        const lang = window.currentLang || 'sr';
        let apiUrl = `/${lang}/api/dashboard/filter-options/${filterType}`;
        const params = new URLSearchParams();
        
        // Add parent filter value to params
        if (parentFilterKey === 'language_id' && parentValue) {
            params.append('language_id', parentValue);
        } else if (parentFilterKey === 'continent_id' && parentValue) {
            params.append('continent_id', parentValue);
        }
        
        if (params.toString()) {
            apiUrl += '?' + params.toString();
        }
        
        try {
            const response = await fetch(apiUrl, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });
            
            if (!response.ok) {
                console.warn(`Failed to load filter options for ${filterKey}`);
                return;
            }
            
            const result = await response.json();
            
            if (result.success && result.data) {
                this.updateFilterOptions(filterKey, result.data);
            }
        } catch (error) {
            console.error(`Error loading filter options for ${filterKey}:`, error);
        }
    }
    
    updateFilterOptions(filterKey, options) {
        // Check if this is a custom dropdown (user-select) or regular select
        const filterSelect = document.getElementById(`${this.tableId}-filter-${filterKey}`);
        const customDropdown = document.getElementById(`${this.tableId}-${filterKey}-dropdown`);
        
        if (filterSelect) {
            // Regular select - update options
            const currentValue = filterSelect.value;
            
            // Clear existing options except "All"
            filterSelect.innerHTML = '';
            // Get placeholder from data attribute, or generate a short version
            let placeholder = filterSelect.getAttribute('data-placeholder');
            if (!placeholder) {
                // Generate short placeholder: "category_id" -> "All Cat..."
                const keyParts = filterKey.split('_');
                if (keyParts.length > 0) {
                    const firstPart = keyParts[0];
                    placeholder = `All ${firstPart.charAt(0).toUpperCase() + firstPart.slice(1)}...`;
                } else {
                    placeholder = `All ${filterKey.replace('_', ' ')}`;
                }
            }
            const allOption = document.createElement('option');
            allOption.value = '';
            allOption.textContent = placeholder;
            filterSelect.appendChild(allOption);
            
            // Add new options
            options.forEach(option => {
                const optionEl = document.createElement('option');
                optionEl.value = option.value;
                optionEl.textContent = option.label || option.name || option.value;
                if (option.code) {
                    optionEl.setAttribute('data-code', option.code);
                }
                filterSelect.appendChild(optionEl);
            });
            
            // Restore selection if still valid
            if (currentValue && options.some(opt => opt.value == currentValue)) {
                filterSelect.value = currentValue;
            } else {
                filterSelect.value = '';
                delete this.currentFilters[filterKey];
            }
        } else if (customDropdown) {
            // Custom dropdown (user-select) - update options
            const currentValue = document.getElementById(`${this.tableId}-filter-${filterKey}`)?.value || '';
            
            // Clear existing options except "All"
            const optionsContainer = customDropdown.querySelector('.p-2');
            if (optionsContainer) {
                const allOption = optionsContainer.querySelector('.user-filter-option[data-value=""]');
                optionsContainer.innerHTML = '';
                if (allOption) {
                    optionsContainer.appendChild(allOption);
                }
                
                // Add new options
                options.forEach(option => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'w-full flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-slate-800/60 transition-colors user-filter-option';
                    button.setAttribute('data-value', option.value);
                    button.setAttribute('data-label', option.label || option.name || option.value);
                    button.setAttribute('data-avatar', option.avatar || '');
                    button.setAttribute('data-initial', option.initial || '');
                    
                    if (option.avatar) {
                        button.innerHTML = `
                            <img src="${this.escapeHtml(option.avatar)}" alt="${this.escapeHtml(option.label)}" class="w-6 h-6 rounded-full object-cover flex-shrink-0">
                            <span class="text-sm font-medium text-slate-300 truncate">${this.escapeHtml(option.label)}</span>
                        `;
                    } else {
                        const initial = option.initial || (option.label ? option.label.charAt(0).toUpperCase() : 'U');
                        button.innerHTML = `
                            <div class="w-6 h-6 rounded-full bg-theme-primary/20 border border-theme-primary/50 flex items-center justify-center flex-shrink-0">
                                <span class="text-theme-primary font-semibold text-xs">${this.escapeHtml(initial)}</span>
                            </div>
                            <span class="text-sm font-medium text-slate-300 truncate">${this.escapeHtml(option.label)}</span>
                        `;
                    }
                    
                    // Re-bind click handler
                    button.addEventListener('click', (e) => {
                        e.stopPropagation();
                        const value = button.getAttribute('data-value') || '';
                        const label = button.getAttribute('data-label') || '';
                        const avatar = button.getAttribute('data-avatar') || '';
                        const initial = button.getAttribute('data-initial') || '';
                        
                        const hiddenInput = document.getElementById(`${this.tableId}-filter-${filterKey}`);
                        const avatarDisplay = document.getElementById(`${this.tableId}-${filterKey}-avatar-display`);
                        const textDisplay = document.getElementById(`${this.tableId}-${filterKey}-text`);
                        const dropdown = document.getElementById(`${this.tableId}-${filterKey}-dropdown`);
                        const toggle = document.getElementById(`${this.tableId}-${filterKey}-toggle`);
                        
                        if (hiddenInput) {
                            hiddenInput.value = value;
                        }
                        
                        if (value === '') {
                            if (avatarDisplay) {
                                avatarDisplay.innerHTML = '<span class="text-theme-primary font-semibold text-xs"></span>';
                            }
                            if (textDisplay) {
                                textDisplay.textContent = 'All Authors';
                            }
                        } else {
                            if (avatarDisplay) {
                                if (avatar) {
                                    avatarDisplay.innerHTML = `<img src="${this.escapeHtml(avatar)}" alt="${this.escapeHtml(label)}" class="w-6 h-6 rounded-full object-cover">`;
                                } else {
                                    avatarDisplay.innerHTML = `<div class="w-6 h-6 rounded-full bg-theme-primary/20 border border-theme-primary/50 flex items-center justify-center"><span class="text-theme-primary font-semibold text-xs">${this.escapeHtml(initial)}</span></div>`;
                                }
                            }
                            if (textDisplay) {
                                textDisplay.textContent = label;
                            }
                        }
                        
                        if (dropdown) {
                            dropdown.style.display = 'none';
                            dropdown.classList.add('hidden');
                        }
                        if (toggle) {
                            toggle.setAttribute('aria-expanded', 'false');
                        }
                        
                        if (hiddenInput) {
                            const changeEvent = new Event('change', { bubbles: true });
                            hiddenInput.dispatchEvent(changeEvent);
                        }
                    });
                    
                    optionsContainer.appendChild(button);
                });
                
                // Restore selection if still valid
                if (!currentValue || !options.some(opt => opt.value == currentValue)) {
                    const hiddenInput = document.getElementById(`${this.tableId}-filter-${filterKey}`);
                    if (hiddenInput) {
                        hiddenInput.value = '';
                    }
                    delete this.currentFilters[filterKey];
                }
            }
        }
    }
    
    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    updateDateRangeFilter(filterKey, fromInput, toInput) {
        const fromValue = fromInput ? fromInput.value : '';
        const toValue = toInput ? toInput.value : '';
        
        if (fromValue || toValue) {
            if (!this.currentFilters[filterKey]) {
                this.currentFilters[filterKey] = {};
            }
            if (fromValue) {
                this.currentFilters[filterKey].from = fromValue;
            } else {
                delete this.currentFilters[filterKey].from;
            }
            if (toValue) {
                this.currentFilters[filterKey].to = toValue;
            } else {
                delete this.currentFilters[filterKey].to;
            }
            
            // Remove filter if both are empty
            if (!this.currentFilters[filterKey].from && !this.currentFilters[filterKey].to) {
                delete this.currentFilters[filterKey];
            }
        } else {
            delete this.currentFilters[filterKey];
        }
        
        this.currentPage = 1;
        this.loadData();
    }
    
    updateSortHeaders() {
        if (!this.config.enableSort) return;
        
        const tableHead = document.getElementById(`${this.tableId}-table-head`);
        if (!tableHead) return;
        
        const sortableHeaders = tableHead.querySelectorAll('th[data-sort-key]');
        sortableHeaders.forEach(header => {
            const sortKey = header.getAttribute('data-sort-key');
            const isCurrentSort = this.currentSort === sortKey;
            const div = header.querySelector('div');
            if (!div) return;
            
            // Remove existing icon
            const existingIcon = div.querySelector('ion-icon');
            if (existingIcon) {
                existingIcon.remove();
            }
            
            // Add appropriate icon
            if (isCurrentSort) {
                const icon = document.createElement('ion-icon');
                icon.setAttribute('name', this.currentOrder === 'asc' ? 'arrow-up-outline' : 'arrow-down-outline');
                icon.className = 'ml-1 text-theme-primary';
                div.appendChild(icon);
                header.setAttribute('data-current-sort', 'true');
                header.setAttribute('data-current-order', this.currentOrder);
            } else {
                const icon = document.createElement('ion-icon');
                icon.setAttribute('name', 'swap-vertical-outline');
                icon.className = 'ml-1 text-slate-500 opacity-50';
                div.appendChild(icon);
                header.setAttribute('data-current-sort', 'false');
                header.setAttribute('data-current-order', '');
            }
        });
    }
    
    async loadData() {
        const loadingEl = document.getElementById(`${this.tableId}-loading`);
        const tableContainer = document.getElementById(`${this.tableId}-table-container`);
        const emptyEl = document.getElementById(`${this.tableId}-empty`);
        
        // Show loading
        if (loadingEl) loadingEl.classList.remove('hidden');
        if (tableContainer) tableContainer.classList.add('hidden');
        if (emptyEl) emptyEl.classList.add('hidden');
        
        // Build API URL
        const params = new URLSearchParams({
            page: this.currentPage,
            limit: this.config.perPage || 50,
            sort: this.currentSort,
            order: this.currentOrder
        });
        
        if (this.currentSearch) {
            params.append('search', this.currentSearch);
        }
        
        // Use language_id from filters if available, otherwise fall back to currentLanguageId
        const languageId = this.currentFilters['language_id'] || this.currentLanguageId;
        if (languageId) {
            params.append('language_id', languageId);
        } else if (this.currentLanguageCode) {
            params.append('language_code', this.currentLanguageCode);
        }
        
        // Add dynamic filters
        if (this.currentFilters && Object.keys(this.currentFilters).length > 0) {
            Object.keys(this.currentFilters).forEach(filterKey => {
                if (filterKey === 'language_id' || filterKey === 'language_code') {
                    return;
                }

                const filterValue = this.currentFilters[filterKey];
                
                if (typeof filterValue === 'object' && filterValue !== null) {
                    // Date range filter
                    if (filterValue.from) {
                        params.append(`${filterKey}_from`, filterValue.from);
                    }
                    if (filterValue.to) {
                        params.append(`${filterKey}_to`, filterValue.to);
                    }
                } else {
                    // Simple filter value
                    params.append(filterKey, filterValue);
                }
            });
        }
        
        // Add API flag to ensure backend recognizes this as an API request
        params.append('api', '1');
        params.append('format', 'json');
        
        try {
            const response = await fetch(`${this.config.apiUrl}?${params.toString()}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });
            
            // Check if response is JSON
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                throw new Error(`Expected JSON but got ${contentType}. Response: ${text.substring(0, 200)}`);
            }
            
            const result = await response.json();
            
            if (result.success && result.data) {
                this.renderTable(result.data.data || []);
                this.renderPagination(result.data.pagination || {});
                
                if (loadingEl) loadingEl.classList.add('hidden');
                if (tableContainer) tableContainer.classList.remove('hidden');
                
                if ((result.data.data || []).length === 0) {
                    if (tableContainer) tableContainer.classList.add('hidden');
                    if (emptyEl) emptyEl.classList.remove('hidden');
                }
            } else {
                throw new Error(result.message || 'Failed to load data');
            }
        } catch (error) {
            console.error('Error loading data:', error);
            if (loadingEl) loadingEl.classList.add('hidden');
            if (emptyEl) {
                emptyEl.innerHTML = `<p class="text-red-400">Error: ${error.message}</p>`;
                emptyEl.classList.remove('hidden');
            }
        }
    }
    
    renderTable(data) {
        const tbody = document.getElementById(`${this.tableId}-table-body`);
        if (!tbody) return;
        
        tbody.innerHTML = '';
        
        data.forEach(row => {
            const tr = document.createElement('tr');
            tr.className = 'hover:bg-slate-800/30 transition-colors';
            
            // Render columns
            this.config.columns.forEach((column, index) => {
                const td = document.createElement('td');
                td.className = 'px-6 py-4 text-slate-300';
                
                // Check if column has render function (from window.crudTableRenderFunctions)
                const originalCol = this.config.originalColumns && this.config.originalColumns[index];
                
                if (originalCol && originalCol.hasRender && typeof originalCol.render === 'string') {
                    // Render function is specified by name (string)
                    const renderFuncName = originalCol.render;
                    const renderFunc = window.crudTableRenderFunctions && 
                                     window.crudTableRenderFunctions[this.tableId] && 
                                     window.crudTableRenderFunctions[this.tableId][renderFuncName];
                    
                    if (renderFunc && typeof renderFunc === 'function') {
                        td.innerHTML = renderFunc(row);
                    } else {
                        // Fallback: if we have an object, try to display it properly
                        const columnKey = originalCol.key || column.key;
                        // Try both getNestedValue and direct access
                        let value = this.getNestedValue(row, columnKey);
                        if (value === null || value === undefined) {
                            value = row[columnKey];
                        }
                        
                        // Debug log for date columns
                        if (columnKey === 'created_at' || columnKey === 'updated_at' || columnKey === 'published_at') {
                            console.log('[CRUD Table] Date column fallback:', {
                                tableId: this.tableId,
                                columnKey: columnKey,
                                value: value,
                                valueType: typeof value,
                                hasValue: !!value,
                                rowKeys: Object.keys(row),
                                directAccess: row[columnKey],
                                originalCol: originalCol,
                                column: column
                            });
                        }
                        
                        // Check if this is a date column (created_at, updated_at, published_at)
                        if ((columnKey === 'created_at' || columnKey === 'updated_at' || columnKey === 'published_at') && value) {
                            // Try to format as date
                            let timestamp;
                            if (typeof value === 'number') {
                                timestamp = value;
                            } else if (typeof value === 'string') {
                                const dateStr = value.trim();
                                if (/^\d{4}-\d{2}-\d{2}[\sT]\d{2}:\d{2}:\d{2}/.test(dateStr)) {
                                    const date = new Date(dateStr.replace(' ', 'T'));
                                    timestamp = !isNaN(date.getTime()) ? Math.floor(date.getTime() / 1000) : null;
                                } else {
                                    const date = new Date(dateStr);
                                    timestamp = !isNaN(date.getTime()) ? Math.floor(date.getTime() / 1000) : null;
                                }
                            }
                            
                            // Debug: log timestamp parsing
                            if (columnKey === 'created_at' || columnKey === 'updated_at' || columnKey === 'published_at') {
                                console.log('[CRUD Table] Date parsing:', {
                                    tableId: this.tableId,
                                    columnKey: columnKey,
                                    value: value,
                                    timestamp: timestamp,
                                    hasTimestamp: !!timestamp
                                });
                            }
                            
                            if (timestamp) {
                                const now = Math.floor(Date.now() / 1000);
                                const diff = now - timestamp;
                                let relativeTime = '';
                                if (diff < 60) relativeTime = 'pre ' + diff + ' sekundi';
                                else if (diff < 3600) relativeTime = 'pre ' + Math.floor(diff / 60) + (Math.floor(diff / 60) === 1 ? ' minute' : ' minuta');
                                else if (diff < 86400) relativeTime = 'pre ' + Math.floor(diff / 3600) + (Math.floor(diff / 3600) === 1 ? ' sata' : ' sati');
                                else if (diff < 2592000) relativeTime = 'pre ' + Math.floor(diff / 86400) + (Math.floor(diff / 86400) === 1 ? ' dana' : ' dana');
                                else if (diff < 31536000) relativeTime = 'pre ' + Math.floor(diff / 2592000) + (Math.floor(diff / 2592000) === 1 ? ' meseca' : ' meseci');
                                else relativeTime = 'pre ' + Math.floor(diff / 31536000) + (Math.floor(diff / 31536000) === 1 ? ' godine' : ' godina');
                                const date = new Date(timestamp * 1000);
                                const formattedDate = `${String(date.getDate()).padStart(2, '0')}.${String(date.getMonth() + 1).padStart(2, '0')}.${date.getFullYear()} ${String(date.getHours()).padStart(2, '0')}:${String(date.getMinutes()).padStart(2, '0')}:${String(date.getSeconds()).padStart(2, '0')}`;
                                const htmlContent = `<div class="flex flex-col">
                                    <span class="text-sm text-slate-300">${relativeTime}</span>
                                    <span class="text-xs text-slate-500">${formattedDate}</span>
                                </div>`;
                                td.innerHTML = htmlContent;
                                console.log('[CRUD Table] Date rendered:', {
                                    tableId: this.tableId,
                                    columnKey: columnKey,
                                    htmlContent: htmlContent,
                                    tdInnerHTML: td.innerHTML
                                });
                                // Don't return here - we need to continue to add td to tr
                            } else {
                                // Debug: log when date parsing fails
                                console.log('[CRUD Table] Date fallback failed:', {
                                    tableId: this.tableId,
                                    columnKey: columnKey,
                                    value: value,
                                    valueType: typeof value,
                                    timestamp: timestamp
                                });
                            }
                        }
                        
                        // Only render if td.innerHTML is not already set (e.g., by date formatting)
                        if (!td.innerHTML) {
                            if (value && typeof value === 'object' && !Array.isArray(value)) {
                                // If it's an object, try to extract meaningful data
                                if (value.code || value.name) {
                                    // This is likely a language object - try to render it
                                    const langCode = (value.code || '').toLowerCase();
                                    const langName = value.name || value.code || '';
                                    // Try to get flag code from global map if available
                                    const flagCodeMap = window.flagCodeMap || {};
                                    const flagCode = flagCodeMap[langCode] || langCode || 'xx';
                                    console.log('[CRUD Table] Fallback render for language:', {
                                        tableId: this.tableId,
                                        columnKey: columnKey,
                                        langCode: langCode,
                                        flagCode: flagCode,
                                        flagCodeMap: flagCodeMap,
                                        value: value
                                    });
                                    td.innerHTML = `<div class="flex items-center gap-2">
                                        <span class="fi fi-${flagCode}" style="font-size: 1.25rem;"></span>
                                        <span class="text-sm text-slate-300">${langName}</span>
                                    </div>`;
                                } else {
                                    td.textContent = JSON.stringify(value);
                                }
                            } else {
                                td.textContent = value !== null && value !== undefined ? value : '';
                            }
                        }
                    }
                } else if (column.render && typeof column.render === 'function') {
                    // Direct function (not from window.crudTableRenderFunctions)
                    td.innerHTML = column.render(row);
                } else {
                    const value = this.getNestedValue(row, column.key);
                    // Check if this is a date column (created_at, updated_at, published_at)
                    if ((column.key === 'created_at' || column.key === 'updated_at' || column.key === 'published_at') && value) {
                        // Try to format as date
                        let timestamp;
                        if (typeof value === 'number') {
                            timestamp = value;
                        } else if (typeof value === 'string') {
                            const dateStr = value.trim();
                            if (/^\d{4}-\d{2}-\d{2}[\sT]\d{2}:\d{2}:\d{2}/.test(dateStr)) {
                                const date = new Date(dateStr.replace(' ', 'T'));
                                timestamp = !isNaN(date.getTime()) ? Math.floor(date.getTime() / 1000) : null;
                            } else {
                                const date = new Date(dateStr);
                                timestamp = !isNaN(date.getTime()) ? Math.floor(date.getTime() / 1000) : null;
                            }
                        }
                        if (timestamp) {
                            const now = Math.floor(Date.now() / 1000);
                            const diff = now - timestamp;
                            let relativeTime = '';
                            if (diff < 60) relativeTime = 'pre ' + diff + ' sekundi';
                            else if (diff < 3600) relativeTime = 'pre ' + Math.floor(diff / 60) + (Math.floor(diff / 60) === 1 ? ' minute' : ' minuta');
                            else if (diff < 86400) relativeTime = 'pre ' + Math.floor(diff / 3600) + (Math.floor(diff / 3600) === 1 ? ' sata' : ' sati');
                            else if (diff < 2592000) relativeTime = 'pre ' + Math.floor(diff / 86400) + (Math.floor(diff / 86400) === 1 ? ' dana' : ' dana');
                            else if (diff < 31536000) relativeTime = 'pre ' + Math.floor(diff / 2592000) + (Math.floor(diff / 2592000) === 1 ? ' meseca' : ' meseci');
                            else relativeTime = 'pre ' + Math.floor(diff / 31536000) + (Math.floor(diff / 31536000) === 1 ? ' godine' : ' godina');
                            const date = new Date(timestamp * 1000);
                            const formattedDate = `${String(date.getDate()).padStart(2, '0')}.${String(date.getMonth() + 1).padStart(2, '0')}.${date.getFullYear()} ${String(date.getHours()).padStart(2, '0')}:${String(date.getMinutes()).padStart(2, '0')}:${String(date.getSeconds()).padStart(2, '0')}`;
                            td.innerHTML = `<div class="flex flex-col">
                                <span class="text-sm text-slate-300">${relativeTime}</span>
                                <span class="text-xs text-slate-500">${formattedDate}</span>
                            </div>`;
                        } else {
                            td.textContent = value !== null && value !== undefined ? value : '';
                        }
                    } else {
                        td.textContent = value !== null && value !== undefined ? value : '';
                    }
                }
                
                tr.appendChild(td);
            });
            
            // Render actions - check if there's a custom actions column first
            const hasCustomActionsColumn = this.config.columns.some(col => col.key === '_actions');
            
            // Check if custom actions render function is defined
            const customActionsRender = this.config.customActions && 
                                       this.config.customActions.render && 
                                       typeof this.config.customActions.render === 'string' &&
                                       window.crudTableRenderFunctions && 
                                       window.crudTableRenderFunctions[this.tableId] && 
                                       window.crudTableRenderFunctions[this.tableId][this.config.customActions.render];
            
            if (!hasCustomActionsColumn) {
                const td = document.createElement('td');
                td.className = 'px-6 py-4 whitespace-nowrap text-right text-sm font-medium';
                
                if (customActionsRender && typeof customActionsRender === 'function') {
                    // Use custom actions render function
                    td.innerHTML = customActionsRender(row);
                } else if (this.config.editUrl || this.config.deleteUrl) {
                    // Use default actions
                    const actionsDiv = document.createElement('div');
                    actionsDiv.className = 'flex items-center justify-end gap-2';
                
                if (this.config.editUrl) {
                    const editUrl = this.config.editUrl.replace('{id}', row.id);
                    const editLink = document.createElement('a');
                    editLink.href = editUrl;
                    editLink.className = 'text-theme-primary hover:text-theme-primary/80 transition-colors';
                    editLink.title = 'Edit';
                    editLink.innerHTML = '<ion-icon name="create-outline" class="text-xl"></ion-icon>';
                    actionsDiv.appendChild(editLink);
                }
                
                if (this.config.deleteUrl) {
                    const deleteForm = document.createElement('form');
                    deleteForm.method = 'POST';
                    deleteForm.action = this.config.deleteUrl.replace('{id}', row.id);
                    deleteForm.className = 'inline';
                    deleteForm.onsubmit = (e) => {
                        if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
                            e.preventDefault();
                            return false;
                        }
                    };
                    
                    // Add CSRF token
                    const csrfInput = document.createElement('input');
                    csrfInput.type = 'hidden';
                    csrfInput.name = '_csrf_token';
                    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
                    if (csrfMeta) {
                        csrfInput.value = csrfMeta.getAttribute('content');
                    }
                    deleteForm.appendChild(csrfInput);
                    
                    const deleteButton = document.createElement('button');
                    deleteButton.type = 'submit';
                    deleteButton.className = 'text-red-400 hover:text-red-300 transition-colors';
                    deleteButton.title = 'Delete';
                    deleteButton.innerHTML = '<ion-icon name="trash-outline" class="text-xl"></ion-icon>';
                    deleteForm.appendChild(deleteButton);
                    
                    actionsDiv.appendChild(deleteForm);
                }
                
                td.appendChild(actionsDiv);
                }
                
                tr.appendChild(td);
            }
            
            tbody.appendChild(tr);
        });
    }
    
    renderPagination(pagination) {
        const paginationEl = document.getElementById(`${this.tableId}-pagination`);
        if (!paginationEl) return;
        
        const { current_page, last_page, total, per_page } = pagination;
        
        if (last_page <= 1) {
            paginationEl.innerHTML = `<div class="text-sm text-slate-400">Total: ${total} items</div>`;
            return;
        }
        
        let html = `<div class="text-sm text-slate-400">Showing ${((current_page - 1) * per_page) + 1} to ${Math.min(current_page * per_page, total)} of ${total} items</div>`;
        html += '<div class="flex items-center gap-2">';
        
        // Previous button
        if (current_page > 1) {
            html += `<button onclick="window.crudTables['${this.tableId}'].goToPage(${current_page - 1})" class="px-3 py-1 bg-slate-700/50 hover:bg-slate-700 text-white rounded transition-colors">Previous</button>`;
        } else {
            html += '<button disabled class="px-3 py-1 bg-slate-700/30 text-slate-500 rounded cursor-not-allowed">Previous</button>';
        }
        
        // Page numbers
        const startPage = Math.max(1, current_page - 2);
        const endPage = Math.min(last_page, current_page + 2);
        
        if (startPage > 1) {
            html += `<button onclick="window.crudTables['${this.tableId}'].goToPage(1)" class="px-3 py-1 bg-slate-700/50 hover:bg-slate-700 text-white rounded transition-colors">1</button>`;
            if (startPage > 2) {
                html += '<span class="text-slate-500">...</span>';
            }
        }
        
        for (let i = startPage; i <= endPage; i++) {
            if (i === current_page) {
                html += `<button class="px-3 py-1 bg-theme-primary text-white rounded">${i}</button>`;
            } else {
                html += `<button onclick="window.crudTables['${this.tableId}'].goToPage(${i})" class="px-3 py-1 bg-slate-700/50 hover:bg-slate-700 text-white rounded transition-colors">${i}</button>`;
            }
        }
        
        if (endPage < last_page) {
            if (endPage < last_page - 1) {
                html += '<span class="text-slate-500">...</span>';
            }
            html += `<button onclick="window.crudTables['${this.tableId}'].goToPage(${last_page})" class="px-3 py-1 bg-slate-700/50 hover:bg-slate-700 text-white rounded transition-colors">${last_page}</button>`;
        }
        
        // Next button
        if (current_page < last_page) {
            html += `<button onclick="window.crudTables['${this.tableId}'].goToPage(${current_page + 1})" class="px-3 py-1 bg-slate-700/50 hover:bg-slate-700 text-white rounded transition-colors">Next</button>`;
        } else {
            html += '<button disabled class="px-3 py-1 bg-slate-700/30 text-slate-500 rounded cursor-not-allowed">Next</button>';
        }
        
        html += '</div>';
        paginationEl.innerHTML = html;
    }
    
    goToPage(page) {
        this.currentPage = page;
        this.loadData();
    }
    
    getNestedValue(obj, path) {
        return path.split('.').reduce((current, key) => {
            return current && current[key] !== undefined ? current[key] : null;
        }, obj);
    }
}

// Initialize global registry
if (typeof window !== 'undefined') {
    window.crudTables = window.crudTables || {};
    window.CrudTable = CrudTable;
}

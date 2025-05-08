const app = {
    config: {
        api: 'http://localhost:8080/api',
        defaultImage: 'img/no-image.svg',
        perPage: 5
    },
    
    state: {
        brands: [],
        currentPage: 1,
        totalPages: 1,
        editingId: null,
        loading: false,
        userCountry: 'XX'
    },
    
    init() {
        this.setupEventListeners();
        this.detectUserCountry().then(() => {
            this.loadBrands();
        });
    },

    async detectUserCountry() {
        console.log('Detecting user country...');
        try {
            const response = await fetch('https://ipapi.co/json/');
            const data = await response.json();
            this.state.userCountry = data.country_code || 'XX';
            console.log('Country detected:', this.state.userCountry);
            document.getElementById('userCountry').textContent = this.state.userCountry;
        } catch (error) {
            this.state.userCountry = 'XX';
            console.log('Error detecting country, using default:', this.state.userCountry);
            document.getElementById('userCountry').textContent = 'XX';
        }
    },
    
    //Event listener configuration
    setupEventListeners() {
        const form = document.getElementById('brandForm');
        const saveBtn = document.getElementById('saveBrand');
        
        if (form) {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                if (saveBtn) this.saveBrand(saveBtn);
            });
        }
        
        const deleteBtn = document.getElementById('confirmDelete');
        if (deleteBtn) {
            deleteBtn.addEventListener('click', () => {
                if (this.state.editingId) {
                    this.deleteBrand(this.state.editingId, deleteBtn);
                }
            });
        }
        
        const addModal = document.getElementById('addBrandModal');
        if (addModal) {
            addModal.addEventListener('hidden.bs.modal', () => {
                this.resetForm();
            });
        }
    },

    //get default recovery
    getDefaultFetchOptions(method = 'GET', body = null) {
        console.log('Current userCountry:', this.state.userCountry);
        const options = {
            method,
            mode: 'cors',
            credentials: 'include',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'CF-IPCountry': this.state.userCountry || 'XX'
            }
        };

        if (body) {
            options.body = JSON.stringify(body);
        }

        console.log('Request headers:', options.headers);
        return options;
    },
    
    //load brands
    async loadBrands(page = 1) {
        try {
            this.toggleLoading(true);
            console.log('Loading brands with country:', this.state.userCountry);
            
            const options = this.getDefaultFetchOptions('GET');
            console.log('Request options:', options);
            
            const response = await fetch(
                `${this.config.api}/brands?page=${page}&per_page=${this.config.perPage}`,
                options
            );
            
            if (!response.ok) throw new Error('Error loading casinos');
            
            const data = await response.json();
            
            this.state.brands = data.data || [];
            this.state.currentPage = data.current_page || 1;
            this.state.totalPages = data.last_page || 1;
            this.state.total = data.total || this.state.brands.length;
            
            this.renderBrands();
            this.updatePagination();
            
        } catch (error) {
            this.showNotification(error.message, 'error');
        } finally {
            this.toggleLoading(false);
        }
    },
    
    async saveBrand(button) {
        const form = document.getElementById('brandForm');
        
        if (!form || !form.checkValidity()) {
            form?.classList.add('was-validated');
            return;
        }
        
        try {
            this.toggleButtonLoading(button, true);
            
            const formData = {
                brand_name: document.getElementById('brand_name').value.trim(),
                brand_image: document.getElementById('brand_image').value.trim() || null,
                rating: parseInt(document.getElementById('rating').value),
                
                country_code: this.state.userCountry
            };
            
            const isEdit = !!this.state.editingId;
            const method = isEdit ? 'PUT' : 'POST';
            const url = `${this.config.api}/brands${isEdit ? '/' + this.state.editingId : ''}`;
            
            const response = await fetch(url, this.getDefaultFetchOptions(method, formData));
            
            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.message || 'Error saving');
            }
            
            this.showNotification(
                `Casino ${isEdit ? 'updated' : 'added'} successfully`
            );
            
            bootstrap.Modal.getInstance(document.getElementById('addBrandModal')).hide();
            if (isEdit) {
                this.loadBrands(this.state.currentPage);
            } else {
                this.state.currentPage = 1;
                this.loadBrands(1);
            }
            this.resetForm();
            
        } catch (error) {
            this.showNotification(error.message, 'error');
        } finally {
            this.toggleButtonLoading(button, false);
        }
    },
    
    renderBrands() {
        const container = document.getElementById('brandsList');
        if (!container) return;
        
        if (!this.state.brands.length) {
            container.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center py-5">
                        <i class="bi bi-inbox display-1 text-muted"></i>
                        <h3 class="mt-3 text-muted">No casinos available</h3>
                        <p class="text-muted">Add your first casino to get started</p>
                    </td>
                </tr>
            `;
            return;
        }
        
        container.innerHTML = this.state.brands.map((brand, index) => `
            <tr>
                <td class="text-center">
                    ${index + 1}
                    ${index === 0 ? '<span class="casino-badge badge-popular">BEST RATED</span>' : ''}
                </td>
                <td>
                    <div class="d-flex align-items-center">
                        <img src="${brand.brand_image || this.config.defaultImage}" 
                             class="casino-logo me-3" 
                             alt="${brand.brand_name}"
                             onerror="this.src='${this.config.defaultImage}'">
                        <div>
                            <a href="#" class="casino-name">${brand.brand_name}</a>
                            <div class="mt-1">
                                <span class="casino-badge badge-exclusive">EXCLUSIVE</span>
                            </div>
                        </div>
                    </div>
                </td>

                <td>
                    <div class="fw-bold">Up to €500</div>
                    <div class="text-muted">+ 200 Free Spins</div>
                </td>
                <td>
                    <div class="rating">
                        ${this.generateStars(brand.rating)}
                    </div>
                </td>
                <td>
                    <div class="btn-group">
                        <button class="btn btn-sm btn-outline-primary" onclick="app.editBrand(${brand.id})">
                            <i class="bi bi-pencil-square"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="app.confirmDelete(${brand.id})">
                            <i class="bi bi-trash3"></i>
                        </button>
                    </div>
                </td>
                <td>
                    <a href="#" class="btn btn-bonus w-100">
                        <i class="bi bi-gift"></i> Get the bonus
                    </a>
                </td>
            </tr>
        `).join('');
    },
    
    async editBrand(id) {
        try {
            this.toggleLoading(true);
            
            const response = await fetch(`${this.config.api}/brands/${id}`, this.getDefaultFetchOptions('GET'));
            
            if (!response.ok) throw new Error('Casino not found');
            
            const brand = await response.json();
            
            document.getElementById('brand_name').value = brand.brand_name || '';
            document.getElementById('brand_image').value = brand.brand_image || '';
            document.getElementById('rating').value = brand.rating || 5;
            
            this.state.editingId = brand.id;
            document.getElementById('modalTitle').innerHTML = 
                '<i class="bi bi-pencil-square"></i> Edit Casino';
            
            new bootstrap.Modal(document.getElementById('addBrandModal')).show();
            
        } catch (error) {
            this.showNotification(error.message, 'error');
        } finally {
            this.toggleLoading(false);
        }
    },
    
    async deleteBrand(id, button) {
        try {
            this.toggleButtonLoading(button, true);
            
            const response = await fetch(`${this.config.api}/brands/${id}`, this.getDefaultFetchOptions('DELETE'));
            
            if (!response.ok) throw new Error('Error deleting');
            
            this.showNotification('Casino deleted successfully');
            bootstrap.Modal.getInstance(document.getElementById('deleteConfirmModal')).hide();
            this.loadBrands(this.state.currentPage);
            
        } catch (error) {
            this.showNotification(error.message, 'error');
        } finally {
            this.toggleButtonLoading(button, false);
            this.state.editingId = null;
        }
    },
    
    updatePagination() {
        const container = document.getElementById('pagination');
        if (!container) return;
        
        const { currentPage, totalPages } = this.state;
        
        container.innerHTML = `
            <nav>
                <ul class="pagination justify-content-center mb-0">
                    <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                        <a class="page-link" href="#" onclick="app.loadBrands(${currentPage - 1})">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </li>
                    ${Array.from({ length: totalPages }, (_, i) => i + 1)
                        .map(page => `
                            <li class="page-item ${page === currentPage ? 'active' : ''}">
                                <a class="page-link" href="#" onclick="app.loadBrands(${page})">
                                    ${page}
                                </a>
                            </li>
                        `).join('')}
                    <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                        <a class="page-link" href="#" onclick="app.loadBrands(${currentPage + 1})">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        `;
    },
    
    generateStars(rating) {
        return Array(5).fill()
            .map((_, i) => `
                <i class="bi ${i < rating ? 'bi-star-fill' : 'bi-star'}"></i>
            `).join('');
    },
    
    confirmDelete(id) {
        this.state.editingId = id;
        new bootstrap.Modal(document.getElementById('deleteConfirmModal')).show();
    },
    
    resetForm() {
        const form = document.getElementById('brandForm');
        if (!form) return;
        
        form.reset();
        form.classList.remove('was-validated');
        this.state.editingId = null;
        
        const title = document.getElementById('modalTitle');
        if (title) {
            title.innerHTML = '<i class="bi bi-plus-circle"></i> Add a Casino';
        }
    },
    
    toggleLoading(show) {
        const overlay = document.querySelector('.loading-overlay');
        if (!overlay) return;
        
        this.state.loading = show;
        overlay.classList.toggle('show', show);
    },
    
    toggleButtonLoading(button, loading) {
        if (!button || !(button instanceof HTMLElement)) {
            console.error('Invalid button element provided');
            return;
        }
        
        const spinner = button.querySelector('.spinner-border');
        const icon = button.querySelector('.bi');
        
        if (spinner) spinner.classList.toggle('d-none', !loading);
        if (icon) icon.classList.toggle('d-none', loading);
        
        button.disabled = loading;
    },
    
    showNotification(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `toast-notification ${type}`;
        toast.textContent = message;
        
        document.body.appendChild(toast);
        
        requestAnimationFrame(() => {
            toast.style.opacity = '1';
            toast.style.transform = 'translateY(0)';
        });
        
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(20px)';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
};

document.addEventListener('DOMContentLoaded', () => app.init());
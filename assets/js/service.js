/**
 * Service Page (Stadium Listing) — wired to real backend data.
 * Preserves all original UI behaviors (filter panel toggle, favorites,
 * view toggle, search) but replaces the mock `stadiumsData` array with
 * a real fetch to backend/api/locations.php.
 */

(function () {
    'use strict';

    const API_BASE = 'backend/api';

    let allLocations = [];   // full fetched dataset
    let filtered      = [];   // after applying current filters
    let visibleCount  = 9;    // "load more" page size

    /* ═══════════════════════════════════════════════════════════
       FETCH
    ═══════════════════════════════════════════════════════════ */
    async function fetchLocations() {
        const skeleton = document.getElementById('loadingSkeleton');
        const grid     = document.getElementById('stadiumGrid');

        try {
            const res  = await fetch(`${API_BASE}/locations.php?action=list`);
            const data = await res.json();

            if (!data.success) {
                throw new Error(data.message || 'خطا در دریافت اطلاعات');
            }

            allLocations = data.data || [];
            populateDistrictFilter();
            applyAllFilters();

            document.getElementById('bannerTotalCount').textContent = allLocations.length;
        } catch (err) {
            console.error(err);
            window.Popup?.error('خطا در بارگذاری', 'دریافت لیست استادیوم‌ها با مشکل مواجه شد.');
        } finally {
            skeleton.style.display = 'none';
            grid.style.display     = '';
        }
    }

    /** Populate the district ("منطقه") dropdown from real, observed values. */
    function populateDistrictFilter() {
        const select = document.getElementById('location');
        const seen   = new Set();

        allLocations.forEach(loc => {
            if (loc.district && !seen.has(loc.district)) {
                seen.add(loc.district);
                const opt = document.createElement('option');
                opt.value = loc.district;
                opt.textContent = loc.district;
                select.appendChild(opt);
            }
        });
    }

    /* ═══════════════════════════════════════════════════════════
       CARD RENDERING
    ═══════════════════════════════════════════════════════════ */
    function surfaceLabel(type) {
        return ({ artificial: 'چمن مصنوعی', natural: 'چمن طبیعی', indoor: 'سالن سرپوشیده', outdoor: 'فضای باز' })[type] || type;
    }

    const AMENITY_ICON = {
        'پارکینگ': 'fa-parking', 'دوش': 'fa-shower', 'نورافکن': 'fa-lightbulb',
        'بوفه': 'fa-coffee', 'کافه': 'fa-coffee', 'رختکن': 'fa-tshirt',
        'کمد': 'fa-lock', 'وای': 'fa-wifi',
    };

    function amenityIcon(name) {
        const key = Object.keys(AMENITY_ICON).find(k => name.includes(k));
        return key ? AMENITY_ICON[key] : 'fa-check';
    }

    function renderCard(loc, index) {
        const amenities  = Array.isArray(loc.amenities) ? loc.amenities : [];
        const shown      = amenities.slice(0, 4);
        const remaining  = amenities.length - shown.length;
        const imgUrl     = loc.primary_image_url || 'assets/images/placeholder-stadium.jpg';
        const isFav      = FavoriteManager.has(loc.id);

        const amenityIcons = shown.map(a =>
            `<span class="amenity-icon" data-tooltip="${escapeHtml(a)}"><i class="fas ${amenityIcon(a)}"></i></span>`
        ).join('');

        return `
        <div class="stadium-card" data-aos="fade-up" data-aos-delay="${(index % 3) * 100}">
            <div class="card-image">
                <img src="${escapeHtml(imgUrl)}" alt="${escapeHtml(loc.title)}" onerror="this.src='assets/images/placeholder-stadium.jpg'">
                <div class="image-overlay">
                    <button class="btn-favorite ${isFav ? 'active' : ''}" data-id="${loc.id}">
                        <i class="${isFav ? 'fas' : 'far'} fa-heart"></i>
                    </button>
                </div>
                <div class="status-indicator online">
                    <span class="pulse"></span>
                    <span class="text">فعال</span>
                </div>
            </div>
            <div class="card-content">
                <div class="card-header">
                    <h3 class="card-title">${escapeHtml(loc.title)}</h3>
                    <div class="card-rating">
                        <i class="fas fa-star"></i>
                        <span>${Number(loc.rating_avg || 0).toFixed(1)}</span>
                    </div>
                </div>
                <p class="card-location">
                    <i class="fas fa-map-marker-alt"></i>
                    ${escapeHtml(loc.district || loc.city || '')} — ${escapeHtml(loc.address || '')}
                </p>
                <div class="card-features">
                    <span class="feature-badge surface">
                        <i class="fas fa-layer-group"></i> ${surfaceLabel(loc.surface_type)}
                    </span>
                    <span class="feature-badge size">
                        <i class="fas fa-expand-arrows-alt"></i> ${loc.capacity} نفره
                    </span>
                </div>
                <div class="card-amenities">
                    ${amenityIcons}
                    ${remaining > 0 ? `<span class="amenity-more">+${remaining}</span>` : ''}
                </div>
                <div class="card-footer">
                    <div class="price-info">
                        <span class="price-label">قیمت هر سانس</span>
                        <span class="price-value">${Number(loc.price_per_session).toLocaleString('fa-IR')} تومان</span>
                    </div>
                    <a href="details.php?id=${loc.id}" class="btn-book">
                        <span>رزرو کن</span>
                        <i class="fas fa-arrow-left"></i>
                    </a>
                </div>
            </div>
        </div>`;
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str ?? '';
        return div.innerHTML;
    }

    function renderGrid() {
        const grid          = document.getElementById('stadiumGrid');
        const noResults     = document.getElementById('noResults');
        const loadMoreWrap  = document.getElementById('loadMoreWrapper');

        document.getElementById('resultsCount').textContent = filtered.length;

        if (filtered.length === 0) {
            grid.innerHTML = '';
            noResults.style.display = '';
            loadMoreWrap.style.display = 'none';
            return;
        }

        noResults.style.display = 'none';

        const slice = filtered.slice(0, visibleCount);
        grid.innerHTML = slice.map((loc, i) => renderCard(loc, i)).join('');

        loadMoreWrap.style.display = filtered.length > visibleCount ? '' : 'none';

        if (typeof AOS !== 'undefined') AOS.refresh();
        bindCardEvents();
    }

    function bindCardEvents() {
        document.querySelectorAll('.btn-favorite').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const id = parseInt(btn.dataset.id, 10);
                FavoriteManager.toggle(id);
                const isFav = FavoriteManager.has(id);
                btn.classList.toggle('active', isFav);
                btn.querySelector('i').className = isFav ? 'fas fa-heart' : 'far fa-heart';
            });
        });
    }

    /* ═══════════════════════════════════════════════════════════
       FAVORITES (localStorage — no backend needed)
    ═══════════════════════════════════════════════════════════ */
    const FavoriteManager = {
        KEY: 'stadium_favorites',
        get() {
            try { return JSON.parse(localStorage.getItem(this.KEY)) || []; }
            catch { return []; }
        },
        has(id) { return this.get().includes(id); },
        toggle(id) {
            let favs = this.get();
            favs = favs.includes(id) ? favs.filter(f => f !== id) : [...favs, id];
            localStorage.setItem(this.KEY, JSON.stringify(favs));
        }
    };

    /* ═══════════════════════════════════════════════════════════
       FILTERING (all client-side, against real fetched fields)
    ═══════════════════════════════════════════════════════════ */
    function applyAllFilters() {
        const search   = document.getElementById('searchInput').value.trim().toLowerCase();
        const surface  = document.getElementById('surfaceType').value;
        const district = document.getElementById('location').value;
        const priceRange = document.getElementById('priceRange').value;
        const minRating  = parseFloat(document.getElementById('rating').value) || 0;
        const checkedAmenities = Array.from(document.querySelectorAll('input[name="amenity"]:checked')).map(c => c.value);

        const [minPrice, maxPrice] = priceRange ? priceRange.split('-').map(Number) : [0, Infinity];

        filtered = allLocations.filter(loc => {
            if (search) {
                const haystack = `${loc.title} ${loc.address} ${loc.district} ${loc.city}`.toLowerCase();
                if (!haystack.includes(search)) return false;
            }
            if (surface && loc.surface_type !== surface) return false;
            if (district && loc.district !== district) return false;
            if (loc.price_per_session < minPrice || loc.price_per_session > maxPrice) return false;
            if (minRating && Number(loc.rating_avg) < minRating) return false;

            if (checkedAmenities.length > 0) {
                const locAmenities = Array.isArray(loc.amenities) ? loc.amenities : [];
                const hasAll = checkedAmenities.every(keyword =>
                    locAmenities.some(a => a.includes(keyword))
                );
                if (!hasAll) return false;
            }
            return true;
        });

        sortResults();
        visibleCount = 9;
        renderGrid();
        updateFilterBadge();
    }

    function sortResults() {
        const sortBy = document.getElementById('sortBy').value;
        filtered.sort((a, b) => {
            switch (sortBy) {
                case 'rating':     return b.rating_avg - a.rating_avg;
                case 'price-low':  return a.price_per_session - b.price_per_session;
                case 'price-high': return b.price_per_session - a.price_per_session;
                case 'newest':     return b.id - a.id;
                default:           return (b.rating_count || 0) - (a.rating_count || 0);
            }
        });
    }

    function updateFilterBadge() {
        let count = 0;
        if (document.getElementById('surfaceType').value) count++;
        if (document.getElementById('location').value) count++;
        if (document.getElementById('priceRange').value) count++;
        if (document.getElementById('rating').value) count++;
        count += document.querySelectorAll('input[name="amenity"]:checked').length;
        document.getElementById('filterBadge').textContent = count;
    }

    function resetAllFilters() {
        document.getElementById('searchInput').value = '';
        document.getElementById('surfaceType').value = '';
        document.getElementById('location').value = '';
        document.getElementById('priceRange').value = '';
        document.getElementById('rating').value = '';
        document.querySelectorAll('input[name="amenity"]').forEach(c => c.checked = false);
        applyAllFilters();
    }

    /* ═══════════════════════════════════════════════════════════
       UI WIRING
    ═══════════════════════════════════════════════════════════ */
    function initUI() {
        document.getElementById('filterToggleBtn')?.addEventListener('click', () => {
            document.getElementById('advancedFilters')?.classList.toggle('active');
        });

        document.getElementById('applyFilters')?.addEventListener('click', applyAllFilters);
        document.getElementById('resetFilters')?.addEventListener('click', resetAllFilters);
        document.getElementById('btnResetAll')?.addEventListener('click', resetAllFilters);

        document.getElementById('searchInput')?.addEventListener('input', debounce(applyAllFilters, 300));
        document.getElementById('clearSearch')?.addEventListener('click', () => {
            document.getElementById('searchInput').value = '';
            applyAllFilters();
        });

        document.getElementById('sortBy')?.addEventListener('change', applyAllFilters);

        document.querySelectorAll('.view-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.view-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                document.getElementById('stadiumGrid').classList.toggle('list-view', btn.dataset.view === 'list');
            });
        });

        document.getElementById('loadMore')?.addEventListener('click', () => {
            visibleCount += 9;
            renderGrid();
        });
    }

    function debounce(fn, delay) {
        let timer;
        return (...args) => {
            clearTimeout(timer);
            timer = setTimeout(() => fn(...args), delay);
        };
    }

    /* ═══════════════════════════════════════════════════════════
       BOOTSTRAP
    ═══════════════════════════════════════════════════════════ */
    document.addEventListener('DOMContentLoaded', () => {
        initUI();
        fetchLocations();
    });

})();

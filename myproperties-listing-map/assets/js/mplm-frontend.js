(function () {
    'use strict';

    /**
     * Normalize strings from SVG attributes (id, name, title) to match Sri Lanka's 25 districts.
     * Supports ISO 3166-2 (LK11 / LK-11), English names, and SimpleMaps Sinhala romanizations.
     */
    var DISTRICT_ALIASES = {
        // Western Province
        'lk11': 'colombo', '11': 'colombo', 'colombo': 'colombo', 'kolamba': 'colombo', 'kolomba': 'colombo',
        'lk12': 'gampaha', '12': 'gampaha', 'gampaha': 'gampaha',
        'lk13': 'kalutara', '13': 'kalutara', 'kalutara': 'kalutara', 'kaluthara': 'kalutara',

        // Central Province
        'lk21': 'kandy', '21': 'kandy', 'kandy': 'kandy', 'mahanuvara': 'kandy', 'mahanuwara': 'kandy',
        'lk22': 'matale', '22': 'matale', 'matale': 'matale', 'mathale': 'matale',
        'lk23': 'nuwara-eliya', '23': 'nuwara-eliya', 'nuwaraeliya': 'nuwara-eliya', 'nuvaraeliya': 'nuwara-eliya',

        // Southern Province
        'lk31': 'galle', '31': 'galle', 'galle': 'galle', 'galla': 'galle',
        'lk32': 'matara', '32': 'matara', 'matara': 'matara', 'mathara': 'matara',
        'lk33': 'hambantota', '33': 'hambantota', 'hambantota': 'hambantota', 'hambanthota': 'hambantota',

        // Northern Province
        'lk41': 'jaffna', '41': 'jaffna', 'jaffna': 'jaffna', 'yapanaya': 'jaffna',
        'lk42': 'kilinochchi', '42': 'kilinochchi', 'kilinochchi': 'kilinochchi', 'kilinochchiya': 'kilinochchi',
        'lk43': 'mannar', '43': 'mannar', 'mannar': 'mannar', 'mannarama': 'mannar',
        'lk44': 'vavuniya', '44': 'vavuniya', 'vavuniya': 'vavuniya', 'vavuniyava': 'vavuniya', 'vavuniyawa': 'vavuniya',
        'lk45': 'mullaitivu', '45': 'mullaitivu', 'mullaitivu': 'mullaitivu', 'mullaittivu': 'mullaitivu', 'mulativ': 'mullaitivu', 'mulaitivu': 'mullaitivu',

        // Eastern Province
        'lk51': 'batticaloa', '51': 'batticaloa', 'batticaloa': 'batticaloa', 'madakalapuva': 'batticaloa', 'madakalapuwa': 'batticaloa',
        'lk52': 'ampara', '52': 'ampara', 'ampara': 'ampara',
        'lk53': 'trincomalee', '53': 'trincomalee', 'trincomalee': 'trincomalee', 'trikunamalaya': 'trincomalee',

        // North Western Province
        'lk61': 'kurunegala', '61': 'kurunegala', 'kurunegala': 'kurunegala', 'kurunaegala': 'kurunegala', 'kurunagala': 'kurunegala',
        'lk62': 'puttalam', '62': 'puttalam', 'puttalam': 'puttalam', 'puttalama': 'puttalam',

        // North Central Province
        'lk71': 'anuradhapura', '71': 'anuradhapura', 'anuradhapura': 'anuradhapura',
        'lk72': 'polonnaruwa', '72': 'polonnaruwa', 'polonnaruwa': 'polonnaruwa', 'polonnaruva': 'polonnaruwa',

        // Uva Province
        'lk81': 'badulla', '81': 'badulla', 'badulla': 'badulla',
        'lk82': 'monaragala', '82': 'monaragala', 'monaragala': 'monaragala', 'moneragala': 'monaragala',

        // Sabaragamuwa Province
        'lk91': 'ratnapura', '91': 'ratnapura', 'ratnapura': 'ratnapura', 'rathnapura': 'ratnapura',
        'lk92': 'kegalle', '92': 'kegalle', 'kegalle': 'kegalle', 'kegalla': 'kegalle', 'kaegalla': 'kegalle', 'kagalla': 'kegalle'
    };

    function normalizeKey(str) {
        if (!str) return '';
        var s = String(str).toLowerCase();
        // Replace ligature æ before stripping diacritics
        s = s.replace(/\u00e6/g, 'ae');
        if (s.normalize) {
            s = s.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        }
        return s.replace(/[^a-z0-9]/g, '');
    }

    function detectDistrictSlug(el) {
        var candidates = [
            el.getAttribute('id'),
            el.getAttribute('data-id'),
            el.getAttribute('data-code'),
            el.getAttribute('name'),
            el.getAttribute('data-name'),
            el.getAttribute('title'),
            el.getAttribute('aria-label')
        ];

        var titleTag = el.querySelector('title');
        if (titleTag && titleTag.textContent) {
            candidates.push(titleTag.textContent);
        }

        for (var i = 0; i < candidates.length; i++) {
            var key = normalizeKey(candidates[i]);
            if (key && DISTRICT_ALIASES[key]) {
                return DISTRICT_ALIASES[key];
            }
            // Handle prefixed ids like "district_colombo" or "path_lk11"
            if (key) {
                var stripped = key.replace(/^(district|region|path|area)/, '');
                if (DISTRICT_ALIASES[stripped]) {
                    return DISTRICT_ALIASES[stripped];
                }
            }
        }
        return null;
    }

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text == null ? '' : String(text);
        return div.innerHTML;
    }

    function initInstance(wrapper) {
        var rawConfig = wrapper.getAttribute('data-mplm-config');
        if (!rawConfig) return;

        var config;
        try {
            config = JSON.parse(rawConfig);
        } catch (e) {
            return;
        }

        var data = config.data || {};
        var districts = data.districts || {};
        var provinces = data.provinces || [];
        var totals = data.totals || { all: 0, Sell: 0, Rent: 0 };

        var state = {
            adType: config.defaultType || 'all',
            selectedDistrict: null,
            openProvinces: {},
            searchQuery: ''
        };

        function ensureVisibleOpenProvince() {
            var hasVisibleOpen = false;
            var firstWithAds = null;

            for (var p = 0; p < provinces.length; p++) {
                var prov = provinces[p];
                var hasAds = (prov.counts && prov.counts[state.adType] > 0);
                if (!firstWithAds && hasAds) {
                    firstWithAds = prov.slug;
                }
                if (state.openProvinces[prov.slug] && (!config.hideEmptyDistricts || hasAds)) {
                    hasVisibleOpen = true;
                }
            }

            if (!hasVisibleOpen) {
                if (firstWithAds) {
                    state.openProvinces[firstWithAds] = true;
                } else if (provinces.length > 0) {
                    state.openProvinces[provinces[0].slug] = true;
                }
            }
        }

        ensureVisibleOpenProvince();

        var mapStage = wrapper.querySelector('[data-mplm-map-stage]');
        var svgHolder = wrapper.querySelector('[data-mplm-svg-holder]');
        var tooltip = wrapper.querySelector('[data-mplm-tooltip]');
        var ttDistrict = wrapper.querySelector('[data-mplm-tt-district]');
        var ttProvince = wrapper.querySelector('[data-mplm-tt-province]');
        var ttCount = wrapper.querySelector('[data-mplm-tt-count]');
        var totalPill = wrapper.querySelector('[data-mplm-total]');
        var typeSelect = wrapper.querySelector('[data-mplm-type-select]');
        var panelBody = wrapper.querySelector('[data-mplm-panel-body]');
        var searchInput = wrapper.querySelector('[data-mplm-search]');

        var shapeMap = {}; // slug => array of SVG elements

        function buildSearchUrl(termId, isExactCity) {
            var base = config.searchUrl || '/search/';
            var sep = base.indexOf('?') === -1 ? '?' : '&';
            var url = base + sep + 'country_id=' + encodeURIComponent(termId);
            if (state.adType === 'Sell' || state.adType === 'Rent') {
                url += '&ad_type=' + encodeURIComponent(state.adType);
            }
            if (isExactCity && config.expandDistrictSearch) {
                url += '&mplm_exact=1';
            }
            return url;
        }

        function formatAdLabel(count) {
            var typeLabel = state.adType === 'all' ? '' : (state.adType + ' ');
            if (count === 0) {
                return 'No ' + typeLabel + 'Ads Available';
            }
            return count + ' ' + typeLabel + (count === 1 ? 'Ad Available' : 'Ads Available');
        }

        function updateTotalPill() {
            if (!totalPill) return;
            var count = totals[state.adType] || 0;
            totalPill.textContent = formatAdLabel(count);
        }

        function setDistrictHighlight(slug, className, active) {
            var els = shapeMap[slug] || [];
            for (var i = 0; i < els.length; i++) {
                els[i].classList.toggle(className, active);
            }
        }

        function clearAllSelected() {
            Object.keys(shapeMap).forEach(function (slug) {
                setDistrictHighlight(slug, 'is-selected', false);
            });
        }

        function showTooltipForDistrict(slug, clientX, clientY, anchorEl) {
            if (!tooltip || !mapStage || !districts[slug]) return;

            var d = districts[slug];
            var count = (d.counts && d.counts[state.adType]) ? d.counts[state.adType] : 0;

            ttDistrict.textContent = d.name;
            ttProvince.textContent = d.province_name;
            ttCount.textContent = formatAdLabel(count);

            var stageRect = mapStage.getBoundingClientRect();
            var x, y;

            if (typeof clientX === 'number' && typeof clientY === 'number') {
                x = clientX - stageRect.left;
                y = clientY - stageRect.top;
            } else if (anchorEl) {
                var box = anchorEl.getBoundingClientRect();
                x = (box.left + box.width / 2) - stageRect.left;
                y = (box.top + box.height / 3) - stageRect.top;
            } else {
                x = stageRect.width / 2;
                y = stageRect.height / 2;
            }

            // Keep tooltip safely inside the map stage horizontally
            var pad = 95;
            x = Math.max(pad, Math.min(stageRect.width - pad, x));
            y = Math.max(70, Math.min(stageRect.height - 16, y));

            tooltip.style.left = x + 'px';
            tooltip.style.top = y + 'px';
            tooltip.classList.add('is-visible');
            tooltip.setAttribute('aria-hidden', 'false');
        }

        function hideTooltip() {
            if (!tooltip) return;
            tooltip.classList.remove('is-visible');
            tooltip.setAttribute('aria-hidden', 'true');
        }

        function selectDistrict(slug, fromMapClick) {
            if (!districts[slug]) return;

            var d = districts[slug];
            if (fromMapClick && config.districtClickAction === 'redirect') {
                window.location.href = buildSearchUrl(d.district_term_id);
                return;
            }

            // Second click on already-selected district opens search page directly
            if (fromMapClick && state.selectedDistrict === slug) {
                window.location.href = buildSearchUrl(d.district_term_id);
                return;
            }

            state.selectedDistrict = slug;
            state.searchQuery = '';
            if (searchInput) searchInput.value = '';

            clearAllSelected();
            setDistrictHighlight(slug, 'is-selected', true);
            renderPanel();
        }

        function setupSvg() {
            if (!svgHolder) return;
            var svg = svgHolder.querySelector('svg');
            if (!svg) return;

            // Ensure SVG is responsive via viewBox (normalize lowercase viewbox if present)
            var existingViewBox = svg.getAttribute('viewBox') || svg.getAttribute('viewbox');
            if (existingViewBox) {
                svg.setAttribute('viewBox', existingViewBox);
                svg.removeAttribute('viewbox');
            } else {
                var w = parseFloat(svg.getAttribute('width')) || 1000;
                var h = parseFloat(svg.getAttribute('height')) || 1000;
                svg.setAttribute('viewBox', '0 0 ' + w + ' ' + h);
            }
            svg.removeAttribute('width');
            svg.removeAttribute('height');

            // Create solid base shadow silhouette behind interactive districts
            var shadowGroup = document.createElementNS('http://www.w3.org/2000/svg', 'g');
            shadowGroup.setAttribute('class', 'mplm-shadow-layer');
            shadowGroup.setAttribute('aria-hidden', 'true');

            var candidates = svg.querySelectorAll('path, polygon');
            var matchedCount = 0;

            candidates.forEach(function (el) {
                // Skip decorative helper/marker groups
                if (el.closest('#points, #label_points')) {
                    return;
                }
                var parentG = el.closest('g');
                var slug = detectDistrictSlug(el);
                if (!slug && parentG) {
                    slug = detectDistrictSlug(parentG);
                }
                if (!slug || !districts[slug]) {
                    return;
                }

                matchedCount++;

                // Clone into shadow silhouette group
                var clone = el.cloneNode(false);
                clone.removeAttribute('id');
                clone.removeAttribute('class');
                clone.removeAttribute('style');
                shadowGroup.appendChild(clone);

                // Strip inline fill/stroke attributes so CSS variables always control styling
                el.removeAttribute('fill');
                el.removeAttribute('stroke');
                el.removeAttribute('style');
                el.classList.add('mplm-district-shape');
                el.setAttribute('data-district', slug);
                el.setAttribute('tabindex', '0');
                el.setAttribute('role', 'button');
                el.setAttribute('aria-label', districts[slug].name + ' District');

                if (!shapeMap[slug]) {
                    shapeMap[slug] = [];
                }
                shapeMap[slug].push(el);

                el.addEventListener('mouseenter', function (e) {
                    setDistrictHighlight(slug, 'is-hovered', true);
                    showTooltipForDistrict(slug, e.clientX, e.clientY, el);
                });

                el.addEventListener('mousemove', function (e) {
                    showTooltipForDistrict(slug, e.clientX, e.clientY, el);
                });

                el.addEventListener('mouseleave', function () {
                    setDistrictHighlight(slug, 'is-hovered', false);
                    hideTooltip();
                });

                el.addEventListener('focus', function () {
                    setDistrictHighlight(slug, 'is-hovered', true);
                    showTooltipForDistrict(slug, null, null, el);
                });

                el.addEventListener('blur', function () {
                    setDistrictHighlight(slug, 'is-hovered', false);
                    hideTooltip();
                });

                el.addEventListener('click', function (e) {
                    e.preventDefault();
                    // On touch/mobile devices, show tooltip cleanly on tap too
                    if (window.matchMedia('(hover: none)').matches) {
                        showTooltipForDistrict(slug, null, null, el);
                    }
                    selectDistrict(slug, true);
                });

                el.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        selectDistrict(slug, true);
                    } else if (e.key === 'Escape') {
                        hideTooltip();
                    }
                });
            });

            if (matchedCount > 0) {
                svg.insertBefore(shadowGroup, svg.firstChild);
            }
        }

        function renderPanel() {
            if (!panelBody) return;
            var relAttr = config.nofollowLinks ? ' rel="nofollow"' : '';

            // 1. Search Mode
            if (state.searchQuery.trim() !== '') {
                var q = state.searchQuery.trim().toLowerCase();
                var matchedDistricts = [];
                var matchedTowns = [];

                Object.keys(districts).forEach(function (slug) {
                    var d = districts[slug];
                    var dCount = (d.counts && d.counts[state.adType]) ? d.counts[state.adType] : 0;
                    if (d.name.toLowerCase().indexOf(q) !== -1) {
                        if (!config.hideEmptyDistricts || dCount > 0) {
                            matchedDistricts.push({ district: d, count: dCount });
                        }
                    }

                    (d.towns || []).forEach(function (t) {
                        var tCount = (t.counts && t.counts[state.adType]) ? t.counts[state.adType] : 0;
                        if (t.name.toLowerCase().indexOf(q) !== -1) {
                            if (!config.hideEmptyTowns || tCount > 0) {
                                matchedTowns.push({ town: t, district: d, count: tCount });
                            }
                        }
                    });
                });

                var html = '';
                if (matchedDistricts.length > 0) {
                    html += '<div class="mplm-search-group-title">Matching Districts</div>';
                    matchedDistricts.forEach(function (item) {
                        html += '<div class="mplm-district-row" data-mplm-pick-district="' + escapeHtml(item.district.slug) + '">' +
                            '<span class="mplm-district-row-name">' + escapeHtml(item.district.name) + ' <small style="opacity:.65;">(' + escapeHtml(item.district.province_name) + ')</small></span>' +
                            '<span class="mplm-district-row-right"><span class="mplm-count-badge">' + item.count + '</span> &rarr;</span>' +
                        '</div>';
                    });
                }

                if (matchedTowns.length > 0) {
                    html += '<div class="mplm-search-group-title" style="margin-top:14px;">Matching Towns</div>';
                    html += '<div class="mplm-town-chips">';
                    matchedTowns.forEach(function (item) {
                        html += '<a class="mplm-town-link" href="' + escapeHtml(buildSearchUrl(item.town.term_id, !!item.town.is_district)) + '"' + relAttr + '>' +
                            escapeHtml(item.town.name) + ' <small style="opacity:.65;">(' + escapeHtml(item.district.name) + ')</small>' +
                            '<span>' + item.count + '</span>' +
                        '</a>';
                    });
                    html += '</div>';
                }

                if (matchedDistricts.length === 0 && matchedTowns.length === 0) {
                    html = '<div class="mplm-empty-note">No locations with available ads match "' + escapeHtml(state.searchQuery) + '".</div>';
                }

                panelBody.innerHTML = html;
                bindPanelEvents();
                return;
            }

            // 2. Selected District View (Shows District CTA + Available Town Hyperlinks)
            if (state.selectedDistrict && districts[state.selectedDistrict]) {
                var d = districts[state.selectedDistrict];
                var dCount = (d.counts && d.counts[state.adType]) ? d.counts[state.adType] : 0;
                var visibleTowns = (d.towns || []).filter(function (t) {
                    var tc = (t.counts && t.counts[state.adType]) ? t.counts[state.adType] : 0;
                    return !config.hideEmptyTowns || tc > 0;
                }).sort(function (a, b) {
                    var ca = (a.counts && a.counts[state.adType]) ? a.counts[state.adType] : 0;
                    var cb = (b.counts && b.counts[state.adType]) ? b.counts[state.adType] : 0;
                    if (cb !== ca) return cb - ca;
                    return a.name.localeCompare(b.name);
                });

                var detailHtml = '<div class="mplm-detail-card">' +
                    '<button type="button" class="mplm-back-btn" data-mplm-back>&larr; All Provinces</button>' +
                    '<div class="mplm-detail-province">' + escapeHtml(d.province_name) + '</div>' +
                    '<h3 class="mplm-detail-heading">' + escapeHtml(d.name) + ' District</h3>' +
                    '<p class="mplm-detail-summary">' + escapeHtml(formatAdLabel(dCount)) + '</p>';

                if (dCount > 0) {
                    detailHtml += '<a class="mplm-primary-cta" href="' + escapeHtml(buildSearchUrl(d.district_term_id, false)) + '"' + relAttr + '>' +
                        'View All ' + dCount + ' Ads in ' + escapeHtml(d.name) + ' &rarr;</a>';
                }

                if (visibleTowns.length > 0) {
                    detailHtml += '<div class="mplm-towns-heading">Available Towns in ' + escapeHtml(d.name) + '</div>' +
                        '<div class="mplm-town-chips">';
                    visibleTowns.forEach(function (t) {
                        var tc = (t.counts && t.counts[state.adType]) ? t.counts[state.adType] : 0;
                        detailHtml += '<a class="mplm-town-link" href="' + escapeHtml(buildSearchUrl(t.term_id, !!t.is_district)) + '"' + relAttr + '>' +
                            escapeHtml(t.name) + '<span>' + tc + '</span>' +
                        '</a>';
                    });
                    detailHtml += '</div>';
                } else {
                    detailHtml += '<div class="mplm-empty-note">No ' + (state.adType === 'all' ? '' : escapeHtml(state.adType) + ' ') +
                        'ads available in ' + escapeHtml(d.name) + ' right now.</div>';
                }

                detailHtml += '</div>';
                panelBody.innerHTML = detailHtml;
                bindPanelEvents();
                return;
            }

            // 3. Default Province -> District Overview
            var provHtml = '';
            var visibleProvinces = 0;

            provinces.forEach(function (prov) {
                var pCount = (prov.counts && prov.counts[state.adType]) ? prov.counts[state.adType] : 0;
                var visibleDistricts = (prov.districts || []).filter(function (slug) {
                    var dist = districts[slug];
                    if (!dist) return false;
                    var dc = (dist.counts && dist.counts[state.adType]) ? dist.counts[state.adType] : 0;
                    return !config.hideEmptyDistricts || dc > 0;
                });

                if (config.hideEmptyDistricts && visibleDistricts.length === 0) {
                    return;
                }

                visibleProvinces++;
                var isOpen = !!state.openProvinces[prov.slug];

                provHtml += '<div class="mplm-province-card' + (isOpen ? ' is-open' : '') + '">' +
                    '<button type="button" class="mplm-province-btn" data-mplm-toggle-province="' + escapeHtml(prov.slug) + '">' +
                        '<span>' + escapeHtml(prov.name) + '</span>' +
                        '<span class="mplm-province-meta">' +
                            '<span class="mplm-count-badge">' + pCount + '</span>' +
                            '<span class="mplm-chevron">&#9660;</span>' +
                        '</span>' +
                    '</button>' +
                    '<div class="mplm-district-list">';

                visibleDistricts.forEach(function (slug) {
                    var dist = districts[slug];
                    var dc = (dist.counts && dist.counts[state.adType]) ? dist.counts[state.adType] : 0;
                    provHtml += '<div class="mplm-district-row" data-mplm-pick-district="' + escapeHtml(slug) + '">' +
                        '<span class="mplm-district-row-name">' + escapeHtml(dist.name) + '</span>' +
                        '<span class="mplm-district-row-right">' +
                            '<span class="mplm-count-badge">' + dc + (dc === 1 ? ' Ad' : ' Ads') + '</span> &rarr;' +
                        '</span>' +
                    '</div>';
                });

                provHtml += '</div></div>';
            });

            if (visibleProvinces === 0) {
                provHtml = '<div class="mplm-empty-note">No ' + (state.adType === 'all' ? '' : escapeHtml(state.adType) + ' ') + 'ads available yet.</div>';
            }

            panelBody.innerHTML = provHtml;
            bindPanelEvents();
        }

        function bindPanelEvents() {
            var backBtn = panelBody.querySelector('[data-mplm-back]');
            if (backBtn) {
                backBtn.addEventListener('click', function () {
                    state.selectedDistrict = null;
                    clearAllSelected();
                    renderPanel();
                });
            }

            panelBody.querySelectorAll('[data-mplm-toggle-province]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var pSlug = btn.getAttribute('data-mplm-toggle-province');
                    state.openProvinces[pSlug] = !state.openProvinces[pSlug];
                    renderPanel();
                });
            });

            panelBody.querySelectorAll('[data-mplm-pick-district]').forEach(function (row) {
                var slug = row.getAttribute('data-mplm-pick-district');
                row.addEventListener('mouseenter', function () {
                    setDistrictHighlight(slug, 'is-hovered', true);
                });
                row.addEventListener('mouseleave', function () {
                    setDistrictHighlight(slug, 'is-hovered', false);
                });
                row.addEventListener('click', function () {
                    selectDistrict(slug, false);
                });
            });
        }

        // Top-right All / Sell / Rent filter change
        if (typeSelect) {
            typeSelect.addEventListener('change', function () {
                state.adType = typeSelect.value;
                ensureVisibleOpenProvince();
                updateTotalPill();
                hideTooltip();
                renderPanel();
            });
        }

        // Live panel search input
        if (searchInput) {
            searchInput.addEventListener('input', function () {
                state.searchQuery = searchInput.value;
                renderPanel();
            });
        }

        // Hide tooltip when tapping outside map on mobile
        document.addEventListener('click', function (e) {
            if (mapStage && !mapStage.contains(e.target)) {
                hideTooltip();
            }
        });

        setupSvg();
        updateTotalPill();
        renderPanel();
    }

    function boot() {
        document.querySelectorAll('.mplm-wrapper[data-mplm-config]').forEach(initInstance);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();

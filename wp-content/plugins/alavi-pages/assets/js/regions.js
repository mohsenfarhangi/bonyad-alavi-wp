/* Alavi Regions Table — vanilla JS, no dependencies */
(function () {
    'use strict';

    var PER_PAGE    = 50;
    var allData     = [];
    var filtered    = [];
    var currentPage = 1;
    var debounce;

    /* همه المان‌ها رو هر بار از DOM زنده می‌خونیم */
    function el(id) { return document.getElementById(id); }

    /* ─ شروع بعد از آماده‌شدن کامل DOM ─ */
    function init() {
        if ( ! el('alavi-regions-tbody') ) return;

        /* URL فایل JSON رو از مسیر خود script میسازیم */
        var dataUrl = (function () {
            var scripts = document.querySelectorAll('script[src*="regions.js"]');
            if ( ! scripts.length ) return '';
            return scripts[scripts.length - 1].src.replace(/assets\/js\/regions\.js[^?#]*/, 'data/regions.json');
        })();

        if ( ! dataUrl ) { return; }

        fetch(dataUrl)
            .then(function (r) { return r.json(); })
            .then(function (data) {
                allData = data;
                buildProvinceList();
                applyFilters();
                var loading = el('alavi-loading');
                var wrapper = el('alavi-table-wrapper');
                if (loading) loading.style.display = 'none';
                if (wrapper) wrapper.style.display  = 'block';
            })
            .catch(function () {
                var loading = el('alavi-loading');
                if (loading) loading.innerHTML = '<p style="color:#c0392b">خطا در بارگذاری داده‌ها.</p>';
            });

        /* Event delegation روی document */
        document.addEventListener('change', function (e) {
            var t = e.target;
            if ( ! t ) return;
            if ( t.id === 'filter-province' || t.name === 'filter-type' ) {
                applyFilters();
            }
        });
        document.addEventListener('input', function (e) {
            if ( e.target && e.target.id === 'filter-search' ) {
                clearTimeout(debounce);
                debounce = setTimeout(applyFilters, 300);
            }
        });

        /* Polling برای select هایی که بدون event تغییر می‌کنن (custom dropdown) */
        var _lastProv = '', _lastType = '';
        setInterval(function () {
            var ps   = document.getElementById('filter-province');
            var pVal = ps ? ps.value : '';
            var tVal = '';
            document.querySelectorAll('input[name="filter-type"]').forEach(function (r) {
                if (r.checked) tVal = r.value;
            });
            if (pVal !== _lastProv || tVal !== _lastType) {
                _lastProv = pVal;
                _lastType = tVal;
                applyFilters();
            }
        }, 250);
    }

    /* ─ لیست استان‌ها ─ */
    function buildProvinceList() {
        var sel = el('filter-province');
        if ( ! sel ) return;
        var seen = {};
        allData.forEach(function (r) {
            if ( ! seen[r.p] ) {
                seen[r.p] = true;
                var opt = document.createElement('option');
                opt.value       = r.p;
                opt.textContent = r.p;
                sel.appendChild(opt);
            }
        });
    }

    /* ─ فیلتر ─ */
    function applyFilters() {
        var sel      = el('filter-province');
        var si       = el('filter-search');
        var province = sel ? sel.value : '';
        var type     = '';
        document.querySelectorAll('input[name="filter-type"]').forEach(function (r) {
            if (r.checked) type = r.value;
        });
        var search = si ? si.value.trim() : '';

        var provNorm   = normalize(province);
        var searchNorm = normalize(search);

        filtered = allData.filter(function (row) {
            if (provNorm   && normalize(row.p) !== provNorm) return false;
            if (type       && row.t !== type)                return false;
            if (searchNorm) {
                if ( normalize(row.p).indexOf(searchNorm) < 0 &&
                     normalize(row.c).indexOf(searchNorm) < 0 &&
                     normalize(row.n).indexOf(searchNorm) < 0 ) return false;
            }
            return true;
        });

        currentPage = 1;
        render();
    }

    /* ─ رندر ─ */
    function render() {
        var tbody      = el('alavi-regions-tbody');
        var countEl    = el('filter-count');
        var pagination = el('alavi-pagination');

        if ( ! tbody ) return;

        if (countEl) countEl.textContent = toFaNum(filtered.length);

        var start = (currentPage - 1) * PER_PAGE;
        var page  = filtered.slice(start, start + PER_PAGE);

        tbody.innerHTML = '';
        if (page.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:24px;color:#777">موردی یافت نشد.</td></tr>';
        } else {
            page.forEach(function (row, i) {
                var tr   = document.createElement('tr');
                var num  = toFaNum(start + i + 1);
                var type = row.t === 'R'
                    ? '<span class="badge badge-rural">روستایی و عشایری</span>'
                    : '<span class="badge badge-urban">محله شهری</span>';
                tr.innerHTML =
                    '<td>' + num         + '</td>' +
                    '<td>' + esc(row.p)  + '</td>' +
                    '<td>' + esc(row.c)  + '</td>' +
                    '<td>' + type        + '</td>' +
                    '<td>' + esc(row.n)  + '</td>';
                tbody.appendChild(tr);
            });
        }

        renderPagination(pagination);
    }

    function renderPagination(pagination) {
        if ( ! pagination ) return;
        pagination.innerHTML = '';
        var total = Math.ceil(filtered.length / PER_PAGE);
        if (total <= 1) return;

        pageRange(currentPage, total).forEach(function (p) {
            var btn = document.createElement('button');
            if (p === '…') {
                btn.textContent = '…';
                btn.disabled    = true;
            } else {
                btn.textContent = toFaNum(p);
                if (p === currentPage) btn.classList.add('active');
                btn.addEventListener('click', function () {
                    currentPage = p;
                    render();
                    var wrapper = el('alavi-table-wrapper');
                    if (wrapper) window.scrollTo(0, wrapper.offsetTop - 80);
                });
            }
            pagination.appendChild(btn);
        });
    }

    function pageRange(cur, total) {
        var pages = [];
        if (total <= 9) {
            for (var i = 1; i <= total; i++) pages.push(i);
            return pages;
        }
        pages.push(1);
        if (cur > 4) pages.push('…');
        var s = Math.max(2, cur - 2), e = Math.min(total - 1, cur + 2);
        for (var j = s; j <= e; j++) pages.push(j);
        if (cur < total - 3) pages.push('…');
        pages.push(total);
        return pages;
    }

    /* ─ Helpers ─ */
    function esc(s) {
        return String(s || '')
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    function normalize(s) {
        return String(s || '')
            .replace(/ي/g, 'ی').replace(/ى/g, 'ی')
            .replace(/ك/g, 'ک').replace(/ة/g, 'ه')
            .replace(/أ/g, 'ا').replace(/إ/g, 'ا')
            .replace(/ؤ/g, 'و').replace(/ئ/g, 'ی')
            .replace(/‌/g, ' ').trim();
    }

    var FA = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
    function toFaNum(n) {
        return String(n).replace(/\d/g, function (d) { return FA[d]; });
    }

    /* ─ اجرا بعد از load کامل صفحه ─ */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();

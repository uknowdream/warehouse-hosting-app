function toggleSidebar() {
  document.getElementById('sidebar')?.classList.toggle('open');
}

function bindSidebarVisibility() {
  const app = document.querySelector('.app');
  const buttons = Array.from(document.querySelectorAll('[data-sidebar-toggle]'));
  if (!app || !buttons.length) return;

  const storageKey = 'warehouse.sidebar.hidden';
  const apply = function (hidden) {
    document.documentElement.classList.toggle('pref-sidebar-hidden', hidden);
    app.classList.toggle('sidebar-hidden', hidden);
    buttons.forEach(function (button) {
      const label = hidden ? button.dataset.showLabel : button.dataset.hideLabel;
      const labelTarget = button.querySelector('[data-sidebar-toggle-text]');
      if (label && labelTarget) labelTarget.textContent = label;
      button.setAttribute('aria-pressed', hidden ? 'true' : 'false');
      if (label) {
        button.setAttribute('aria-label', label);
        button.setAttribute('title', label);
      }
    });
  };

  apply(localStorage.getItem(storageKey) === '1');

  buttons.forEach(function (button) {
    button.addEventListener('click', function () {
      const hidden = !app.classList.contains('sidebar-hidden');
      apply(hidden);
      try {
        localStorage.setItem(storageKey, hidden ? '1' : '0');
      } catch (error) {}
    });
  });
}

function bindSidebarGroups() {
  const groups = Array.from(document.querySelectorAll('[data-nav-group]'));
  if (!groups.length) return;

  const storageKey = 'warehouse.sidebar.groups';
  let state = {};
  try {
    state = JSON.parse(localStorage.getItem(storageKey) || '{}') || {};
  } catch (error) {
    state = {};
  }

  const save = function () {
    try {
      localStorage.setItem(storageKey, JSON.stringify(state));
    } catch (error) {}
  };

  groups.forEach(function (group) {
    const key = group.dataset.navGroup;
    const toggle = group.querySelector('.nav-group-toggle');
    const hasActive = group.classList.contains('has-active');
    const collapsed = state[key] === true && !hasActive;

    group.classList.toggle('is-collapsed', collapsed);
    if (toggle) toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');

    toggle?.addEventListener('click', function () {
      const nextCollapsed = !group.classList.contains('is-collapsed');
      group.classList.toggle('is-collapsed', nextCollapsed);
      toggle.setAttribute('aria-expanded', nextCollapsed ? 'false' : 'true');
      state[key] = nextCollapsed;
      save();
    });
  });
}

document.addEventListener('click', function (event) {
  const sidebar = document.getElementById('sidebar');
  const button = document.querySelector('.mobile-menu-btn');
  if (!sidebar || !button) return;
  if (window.innerWidth <= 900 && !sidebar.contains(event.target) && !button.contains(event.target)) {
    sidebar.classList.remove('open');
  }
});

function normalizeSearchText(value) {
  return String(value || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim();
}

function applyListSearch(input, target) {
  if (!input || !target) return;
  const query = normalizeSearchText(input.value);
  let rows = Array.from(target.querySelectorAll('[data-search-row]'));
  if (!rows.length) rows = Array.from(target.querySelectorAll('tbody tr:not([data-search-empty])'));
  if (!rows.length) rows = Array.from(target.children).filter(function (child) {
    return !child.matches('[data-search-empty]');
  });

  let visibleCount = 0;
  rows.forEach(function (row) {
    const haystack = normalizeSearchText(row.dataset.search || row.textContent);
    const visible = !query || haystack.includes(query);
    row.hidden = !visible;
    row.style.display = visible ? '' : 'none';
    if (visible) visibleCount += 1;
  });

  target.querySelectorAll('[data-search-empty]').forEach(function (empty) {
    empty.hidden = visibleCount !== 0;
    empty.style.display = visibleCount === 0 ? '' : 'none';
  });

  if (target.id) {
    document.querySelectorAll('[data-search-count="' + target.id + '"]').forEach(function (count) {
      count.textContent = visibleCount;
    });
  }
}

function filterTable(inputId, tableId) {
  applyListSearch(document.getElementById(inputId), document.getElementById(tableId));
}

function bindListSearches() {
  document.querySelectorAll('[data-search-input]').forEach(function (input) {
    const target = document.getElementById(input.dataset.searchInput);
    const apply = function () { applyListSearch(input, target); };
    input.addEventListener('input', apply);
    apply();
  });
}

function buildQRLabels() {
  if (!window.QRCode) return;
  document.querySelectorAll('[data-qr]').forEach(function (element) {
    element.innerHTML = '';
    new QRCode(element, {
      text: element.dataset.qr,
      width: 128,
      height: 128,
      correctLevel: QRCode.CorrectLevel.M
    });
  });
}

function bindDashboardFilters() {
  const warehouse = document.getElementById('dashboardWarehouse');
  const location = document.getElementById('dashboardLocation');
  const dateFrom = document.getElementById('dashboardDateFrom');
  const dateTo = document.getElementById('dashboardDateTo');
  const form = document.querySelector('.dashboard-filters');

  if (warehouse && location) {
    const syncLocations = function () {
      const selectedWarehouse = warehouse.value;
      Array.from(location.options).forEach(function (option) {
        const visible = !selectedWarehouse || !option.dataset.warehouse || option.dataset.warehouse === selectedWarehouse;
        option.hidden = !visible;
        option.disabled = !visible;
      });
      if (location.selectedOptions[0]?.disabled) location.value = '';
    };
    warehouse.addEventListener('change', syncLocations);
    syncLocations();
  }

  document.querySelectorAll('[data-date-from][data-date-to]').forEach(function (button) {
    button.addEventListener('click', function () {
      if (!dateFrom || !dateTo) return;
      dateFrom.value = button.dataset.dateFrom;
      dateTo.value = button.dataset.dateTo;
      if (form) form.submit();
    });
  });
}

function bindActionConfirm() {
  const modal = document.getElementById('confirmModal');
  const title = document.getElementById('confirmTitle');
  const message = document.getElementById('confirmMessage');
  const icon = document.getElementById('confirmIcon');
  const continueButton = document.getElementById('confirmContinue');
  let pendingForm = null;

  if (!modal || !title || !message || !continueButton) return;

  const close = function () {
    modal.classList.remove('is-open', 'tone-danger', 'tone-success', 'tone-primary');
    modal.setAttribute('aria-hidden', 'true');
    pendingForm = null;
  };

  const open = function (form) {
    const tone = form.dataset.confirmTone || 'primary';
    pendingForm = form;
    title.textContent = form.dataset.confirmTitle || 'Konfirmasi aksi';
    message.textContent = form.dataset.confirmMessage || 'Pastikan data sudah benar sebelum melanjutkan.';
    icon.textContent = tone === 'success' ? 'OK' : '!';
    continueButton.textContent = tone === 'danger' ? 'Ya, lanjutkan' : 'Lanjutkan';
    continueButton.className = 'btn ' + (tone === 'danger' ? 'btn-danger' : tone === 'success' ? 'btn-success' : 'btn-primary');
    modal.classList.add('is-open', 'tone-' + tone);
    modal.setAttribute('aria-hidden', 'false');
    continueButton.focus();
  };

  document.addEventListener('submit', function (event) {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || form.dataset.confirm !== 'true') return;
    if (form.dataset.confirmed === 'true') return;
    event.preventDefault();
    open(form);
  });

  continueButton.addEventListener('click', function () {
    if (!pendingForm) return;
    pendingForm.dataset.confirmed = 'true';
    pendingForm.submit();
  });

  document.querySelectorAll('[data-confirm-cancel]').forEach(function (element) {
    element.addEventListener('click', close);
  });

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && modal.classList.contains('is-open')) close();
  });
}

function bindRoleManagement() {
  const search = document.getElementById('permissionSearch');
  const applyToggle = function (scope, checked) {
    scope.querySelectorAll('input[type="checkbox"][name="permissions[]"]:not(:disabled)').forEach(function (checkbox) {
      checkbox.checked = checked;
    });
  };

  document.querySelectorAll('[data-permission-toggle]').forEach(function (button) {
    button.addEventListener('click', function () {
      const mode = button.dataset.permissionToggle;
      if (mode === 'all-on' || mode === 'all-off') {
        const form = button.closest('form');
        if (form) applyToggle(form, mode === 'all-on');
      }
      if (mode === 'group-on' || mode === 'group-off') {
        const group = button.closest('[data-permission-group]');
        if (group) applyToggle(group, mode === 'group-on');
      }
    });
  });

  if (search) {
    search.addEventListener('input', function () {
      const query = search.value.toLowerCase().trim();
      document.querySelectorAll('[data-permission-item]').forEach(function (item) {
        item.style.display = (item.textContent || '').toLowerCase().includes(query) ? '' : 'none';
      });
    });
  }
}

function bindQRSearch() {
  const search = document.querySelector('[data-qr-search]');
  const labels = Array.from(document.querySelectorAll('[data-qr-label]'));
  const count = document.querySelector('[data-qr-visible-count]');
  const empty = document.querySelector('[data-qr-empty]');
  if (!search && !labels.length) return;

  const apply = function () {
    const query = normalizeSearchText(search?.value || '');
    let visibleCount = 0;

    labels.forEach(function (label) {
      const haystack = normalizeSearchText(label.dataset.search || label.textContent);
      const visible = !query || haystack.includes(query);
      label.hidden = !visible;
      if (visible) visibleCount += 1;
    });

    if (count) count.textContent = visibleCount;
    if (empty) empty.hidden = visibleCount !== 0;
  };

  if (search) search.addEventListener('input', apply);
  apply();
}

document.addEventListener('DOMContentLoaded', function () {
  bindSidebarVisibility();
  bindSidebarGroups();
  buildQRLabels();
  bindDashboardFilters();
  bindActionConfirm();
  bindRoleManagement();
  bindListSearches();
  bindQRSearch();
});

let html5Qr = null;

function startScanner() {
  if (!window.Html5Qrcode) {
    alert('Library scanner gagal dimuat. Gunakan input manual.');
    return;
  }
  const reader = document.getElementById('reader');
  if (!reader) return;
  html5Qr = new Html5Qrcode('reader');
  html5Qr.start(
    { facingMode: 'environment' },
    { fps: 10, qrbox: { width: 260, height: 260 } },
    function (text) {
      handleScanText(text);
      stopScanner();
    },
    function () {}
  ).catch(function () {
    alert('Kamera tidak bisa dibuka. Gunakan HTTPS/localhost atau input manual.');
  });
}

function stopScanner() {
  if (html5Qr) {
    html5Qr.stop().catch(function () {});
    html5Qr = null;
  }
}

function manualScan() {
  handleScanText(document.getElementById('manual_scan')?.value || '');
}

function handleScanText(text) {
  let sku = text;
  let itemId = '';
  let locationId = '';
  try {
    const payload = JSON.parse(text);
    sku = payload.sku || payload.kode || text;
    itemId = payload.id || '';
    locationId = payload.location_id || '';
  } catch (error) {}

  const itemSelect = document.getElementById('op_item_id');
  const locationSelect = document.getElementById('op_location_id');

  if (itemSelect) {
    let found = false;
    Array.from(itemSelect.options).forEach(function (option) {
      const optionText = (option.dataset.sku || option.textContent).toLowerCase();
      if ((itemId && option.value == itemId) || optionText.includes(String(sku).toLowerCase())) {
        itemSelect.value = option.value;
        found = true;
      }
    });
    if (!found) alert('Barang tidak ditemukan di master.');
  }

  if (locationId && locationSelect) locationSelect.value = locationId;
  document.getElementById('op_physical_qty')?.focus();
}

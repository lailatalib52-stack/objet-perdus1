// ============================================================
// OBJETSÉCOLE — main.js
// ============================================================

document.addEventListener('DOMContentLoaded', () => {

  // ── Upload photo preview ──────────────────────────────────
  const photoInput = document.getElementById('photoInput');
  const previewImg = document.getElementById('previewImg');
  const placeholder = document.getElementById('uploadPlaceholder');
  const uploadZone = document.getElementById('uploadZone');

  if (photoInput && previewImg) {
    photoInput.addEventListener('change', () => {
      const file = photoInput.files[0];
      if (!file) return;
      if (file.size > 2 * 1024 * 1024) {
        alert('La photo dépasse 2 Mo. Veuillez choisir une image plus petite.');
        photoInput.value = '';
        return;
      }
      const reader = new FileReader();
      reader.onload = e => {
        previewImg.src = e.target.result;
        previewImg.style.display = 'block';
        if (placeholder) placeholder.style.display = 'none';
      };
      reader.readAsDataURL(file);
    });
  }

  // Drag & drop sur la zone upload
  if (uploadZone) {
    uploadZone.addEventListener('dragover', e => {
      e.preventDefault();
      uploadZone.style.borderColor = 'var(--accent)';
      uploadZone.style.background = 'var(--accent-light)';
    });
    uploadZone.addEventListener('dragleave', () => {
      uploadZone.style.borderColor = '';
      uploadZone.style.background = '';
    });
    uploadZone.addEventListener('drop', e => {
      e.preventDefault();
      uploadZone.style.borderColor = '';
      uploadZone.style.background = '';
      if (e.dataTransfer.files.length && photoInput) {
        photoInput.files = e.dataTransfer.files;
        photoInput.dispatchEvent(new Event('change'));
      }
    });
  }

  // ── Type cards (declare form) ─────────────────────────────
  document.querySelectorAll('.type-card input[type=radio]').forEach(radio => {
    radio.addEventListener('change', () => {
      document.querySelectorAll('.type-card').forEach(c => c.classList.remove('selected'));
      if (radio.checked) radio.closest('.type-card').classList.add('selected');
    });
    if (radio.checked) radio.closest('.type-card').classList.add('selected');
  });

  // ── Category cards ────────────────────────────────────────
  document.querySelectorAll('.cat-card input[type=radio]').forEach(radio => {
    radio.addEventListener('change', () => {
      document.querySelectorAll('.cat-card').forEach(c => c.classList.remove('selected'));
      if (radio.checked) radio.closest('.cat-card').classList.add('selected');
    });
    if (radio.checked) radio.closest('.cat-card').classList.add('selected');
  });

  // ── Auto-dismiss alerts ───────────────────────────────────
  document.querySelectorAll('.alert-success').forEach(alert => {
    setTimeout(() => {
      alert.style.transition = 'opacity .5s';
      alert.style.opacity = '0';
      setTimeout(() => alert.remove(), 500);
    }, 5000);
  });

  // ── Smooth anchor scroll (sidebar) ───────────────────────
  document.querySelectorAll('a[href^="#"]').forEach(link => {
    link.addEventListener('click', e => {
      const target = document.querySelector(link.getAttribute('href'));
      if (target) {
        e.preventDefault();
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    });
  });

  // ── Helper: Go Back with Fallback ──────────────────────────
  window.goBack = function(fallbackUrl) {
    if (document.referrer && document.referrer.indexOf(window.location.host) !== -1) {
      window.history.back();
    } else {
      window.location.href = fallbackUrl || './index.php';
    }
  };

  // ── Admin table row click → navigate ─────────────────────
  document.querySelectorAll('.admin-table tbody tr[data-href]').forEach(row => {
    row.style.cursor = 'pointer';
    row.addEventListener('click', e => {
      if (!e.target.closest('a, button, form')) {
        window.location.href = row.dataset.href;
      }
    });
  });

  // ── Confirm dangerous actions ─────────────────────────────
  document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', e => {
      if (!confirm(el.dataset.confirm)) e.preventDefault();
    });
  });

  // ── Déclaration form validation ───────────────────────────
  const declareForm = document.getElementById('declareForm');
  if (declareForm) {
    declareForm.addEventListener('submit', function (e) {
      const type = declareForm.querySelector('input[name="type"]:checked');
      const cat = declareForm.querySelector('input[name="categorie_id"]:checked');
      const lieu = declareForm.querySelector('select[name="lieu_id"]');
      const desc = declareForm.querySelector('textarea[name="description"]');

      let firstError = null;
      let msg = '';

      if (!type) {
        firstError = declareForm.querySelector('.type-choice');
        msg = 'Veuillez choisir le type d\'annonce (Perdu ou Trouvé).';
      } else if (!cat) {
        firstError = declareForm.querySelector('.cat-grid');
        msg = 'Veuillez sélectionner une catégorie.';
      } else if ((!lieu || !lieu.value) && (!type || type.value === 'trouve')) {
        firstError = lieu;
        msg = 'Veuillez choisir un lieu pour cet objet trouvé.';
      } else if (!desc || desc.value.trim().length < 10) {
        firstError = desc;
        msg = 'La description doit contenir au moins 10 caractères.';
      }

      if (firstError) {
        e.preventDefault();
        // Scroll to first error
        firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
        firstError.style.outline = '2px solid #e53e3e';
        setTimeout(() => firstError.style.outline = '', 2500);
        // Show alert
        let existing = declareForm.querySelector('.js-error-msg');
        if (!existing) {
          existing = document.createElement('div');
          existing.className = 'alert alert-error js-error-msg';
          declareForm.prepend(existing);
        }
        existing.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + msg;
        existing.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    });
  }

  // ── Dropdown Menus ────────────────────────────────────────
  document.addEventListener('click', e => {
    const isDropdownBtn = e.target.closest('.btn-more');
    if (!isDropdownBtn && e.target.closest('.dropdown-menu') != null) {
      return; 
    }
    
    // Fermer tous les menus sauf le courant
    const currentDropdownBtn = isDropdownBtn ? e.target.closest('.dropdown') : null;
    document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
      if (!currentDropdownBtn || menu !== currentDropdownBtn.querySelector('.dropdown-menu')) {
        menu.classList.remove('show');
        const row = menu.closest('tr');
        if (row) row.style.zIndex = '';
      }
    });
    
    // Toggle menu courant
    if (isDropdownBtn) {
      const dropdown = isDropdownBtn.closest('.dropdown');
      if (dropdown) {
        const menu = dropdown.querySelector('.dropdown-menu');
        if (menu) {
          menu.classList.toggle('show');
          const row = dropdown.closest('tr');
          if (row) {
            row.style.position = 'relative';
            row.style.zIndex = menu.classList.contains('show') ? '100' : '';
          }
        }
      }
    }
  });

});

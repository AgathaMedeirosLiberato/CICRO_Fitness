
if (!window._adminProfileInit) {
  window._adminProfileInit = true;

  document.addEventListener('DOMContentLoaded', () => {
    const headerAvatarEls = () => Array.from(document.querySelectorAll('.profile-avatar, #headerAvatar'));
    const profileNameHeader = document.getElementById('profileName');
    const adminMessageEl = document.getElementById('adminMessage');

    const homeAvatarContainer = document.querySelector('.relative.group .rounded-full');
    const homeAdminTitle = document.querySelector('h2.text-gray-500');

    const avatarInput = document.getElementById('avatarInput');
    const avatarImg = document.getElementById('avatarImg');
    const removeAvatarBtn = document.getElementById('removeAvatarBtn');
    const perfilForm = document.getElementById('perfilForm');
    const adminNameEl = document.getElementById('adminName');
    const adminEmailEl = document.getElementById('adminEmail');
    const adminBioEl = document.getElementById('adminBio');
    const cancelBtn = document.getElementById('cancelBtn');

    const PLACEHOLDER_AVATAR = '../../images/Support.png';

    function safeParse(json) {
      try {
        return JSON.parse(json || '{}');
      } catch (e) {
        return {};
      }
    }

    function getSavedProfile() {
      return safeParse(localStorage.getItem('adminProfile'));
    }

    function saveProfileObject(obj) {
      localStorage.setItem('adminProfile', JSON.stringify(obj || {}));
    }

    function updateAvatars(dataUrl) {
      const els = headerAvatarEls();
      if (els.length) {
        els.forEach(img => {
          if (img && img.tagName === 'IMG') img.src = dataUrl;
        });
      }
      
      if (avatarImg && avatarImg.tagName === 'IMG') {
        avatarImg.src = dataUrl;
      }

      if (homeAvatarContainer) {
        homeAvatarContainer.innerHTML = `<img src="${dataUrl}" alt="Avatar do Admin" class="w-full h-full object-cover">`;
      }
    }

    function updateHeaderName(name) {
      if (profileNameHeader) profileNameHeader.textContent = name || 'ADMINISTRADOR';
      if (adminMessageEl) adminMessageEl.textContent = name ? `BEM - VINDO(A), ${name.toUpperCase()}` : 'BEM - VINDO(A), ADMINISTRADOR';
      
      if (homeAdminTitle) {
        homeAdminTitle.textContent = name ? name.toUpperCase() : 'ADMINISTRADOR';
      }
    }

    (function initFromStorage() {
      const saved = getSavedProfile();
      
      if (saved.avatarDataUrl) {
        updateAvatars(saved.avatarDataUrl);
      } else {
        updateAvatars(PLACEHOLDER_AVATAR);
      }

      if (saved.name) {
        updateHeaderName(saved.name);
      }
      
      if (adminNameEl && saved.name) adminNameEl.value = saved.name;
      if (adminEmailEl && saved.email) adminEmailEl.value = saved.email;
      if (adminBioEl && saved.bio) adminBioEl.value = saved.bio;
    })();

    if (avatarInput) {
      avatarInput.addEventListener('change', (e) => {
        const file = e.target.files && e.target.files[0];
        if (!file) return;

        if (!file.type.startsWith('image/')) {
          alert('Por favor selecione um arquivo de imagem valido.');
          avatarInput.value = '';
          return;
        }
        
        const maxSizeMB = 3;
        if (file.size > maxSizeMB * 1024 * 1024) {
          alert(`Imagem muito grande. Tamanho máximo: ${maxSizeMB} MB.`);
          avatarInput.value = '';
          return;
        }

        const reader = new FileReader();
        reader.onload = function(evt) {
          const dataUrl = evt.target.result;
          if (avatarImg) avatarImg.src = dataUrl;
          
          const current = getSavedProfile();
          current.avatarDataUrl = dataUrl;
          saveProfileObject(current);
          
          updateAvatars(dataUrl);
        };
        reader.readAsDataURL(file);
      });
    }

    if (removeAvatarBtn) {
      removeAvatarBtn.addEventListener('click', () => {
        const current = getSavedProfile();
        delete current.avatarDataUrl;
        saveProfileObject(current);
        
        updateAvatars(PLACEHOLDER_AVATAR);
        if (avatarInput) avatarInput.value = '';
      });
    }

    if (perfilForm) {
      perfilForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const name = adminNameEl ? adminNameEl.value.trim() : '';
        const email = adminEmailEl ? adminEmailEl.value.trim() : '';
        const bio = adminBioEl ? adminBioEl.value.trim() : '';

        if (!name) {
          alert('Por favor preencha o nome.');
          if (adminNameEl) adminNameEl.focus();
          return;
        }

        if (email && !/^\S+@\S+\.\S+$/.test(email)) {
          alert('E-mail inválido.');
          if (adminEmailEl) adminEmailEl.focus();
          return;
        }

        const formData = new FormData(perfilForm);
        if (avatarInput && avatarInput.files[0]) {
          formData.append('avatarInput', avatarInput.files[0]);
        }

        const saveBtn = perfilForm.querySelector('.save-btn');
        const originalText = saveBtn ? saveBtn.textContent : '';

        try {
          if (saveBtn) {
            saveBtn.textContent = 'Salvando...';
            saveBtn.disabled = true;
          }

          const response = await fetch('../../controllers/atualizar-perfil.php', {
            method: 'POST',
            body: formData
          });

          const result = await response.json();

          if (result.sucesso) {
            const current = getSavedProfile();
            current.name = name;
            current.email = email;
            current.bio = bio;
            
            saveProfileObject(current);
            updateHeaderName(name);

            if (saveBtn) {
              saveBtn.textContent = 'Salvo no Banco!';
              setTimeout(() => {
                saveBtn.textContent = originalText;
                saveBtn.disabled = false;
              }, 1200);
            }
          } else {
            alert(result.mensagem || 'Erro ao salvar no banco de dados.');
            if (saveBtn) {
              saveBtn.textContent = originalText;
              saveBtn.disabled = false;
            }
          }
        } catch (error) {
          console.warn('Backend PHP inacessível. Salvo localmente.', error);
          
          const current = getSavedProfile();
          current.name = name;
          current.email = email;
          current.bio = bio;
          saveProfileObject(current);
          updateHeaderName(name);

          if (saveBtn) {
            saveBtn.textContent = 'Salvo (Local)';
            setTimeout(() => {
              saveBtn.textContent = originalText;
              saveBtn.disabled = false;
            }, 1200);
          }
        }
      });
    }

    if (cancelBtn) {
      cancelBtn.addEventListener('click', () => {
        const saved = getSavedProfile();
        if (adminNameEl) adminNameEl.value = saved.name || '';
        if (adminEmailEl) adminEmailEl.value = saved.email || '';
        if (adminBioEl) adminBioEl.value = saved.bio || '';
        
        updateAvatars(saved.avatarDataUrl || PLACEHOLDER_AVATAR);
        if (avatarInput) avatarInput.value = '';
      });
    }
  });
}
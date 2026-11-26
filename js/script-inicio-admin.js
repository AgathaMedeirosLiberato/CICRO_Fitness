 lucide.createIcons();

        const modal = document.getElementById('infoModal');
        const modalContent = document.getElementById('modalContent');
        const modalTitle = document.getElementById('modalTitle');
        const modalDescription = document.getElementById('modalDescription');

        function openModal(title, description) {
            modalTitle.textContent = title;
            modalDescription.textContent = description;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            
            setTimeout(() => {
                modalContent.classList.remove('scale-95');
                modalContent.classList.add('scale-100');
            }, 10);
        }

        function closeModal() {
            modalContent.classList.remove('scale-100');
            modalContent.classList.add('scale-95');
            setTimeout(() => {
                modal.classList.remove('flex');
                modal.classList.add('hidden');
            }, 150);
        }

        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeModal();
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
                closeModal();
            }
        });

        let toastTimeout;
        function showToast(message) {
            const toast = document.getElementById('toast');
            const toastMessage = document.getElementById('toastMessage');
            
            toastMessage.textContent = message;
            toast.classList.remove('translate-y-20', 'opacity-0');
            
            clearTimeout(toastTimeout);
            toastTimeout = setTimeout(() => {
                toast.classList.add('translate-y-20', 'opacity-0');
            }, 3000);

            closeModal();
        }

        document.getElementById('notificationBtn').addEventListener('click', () => {
            showToast('Nenhuma nova notificação no momento.');
        });

        document.addEventListener('DOMContentLoaded', () => {
  const avatarContainer = document.querySelector('.relative.group .rounded-full');
  const adminTitle = document.querySelector('h2.text-gray-500');

  const savedAvatar = localStorage.getItem('adminAvatar');
  if (savedAvatar && avatarContainer) {
    avatarContainer.innerHTML = `<img src="${savedAvatar}" alt="Foto de Perfil" class="w-full h-full object-cover">`;
  }

  const savedName = localStorage.getItem('adminName');
  if (savedName && adminTitle) {
    adminTitle.textContent = savedName.toUpperCase();
  }
});
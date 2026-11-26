document.addEventListener('DOMContentLoaded', function() {
    const fotoInput = document.getElementById('foto-input');
    const uploadContent = document.getElementById('upload-content');
    const carouselContainer = document.getElementById('carousel-container');
    const carouselSlides = document.getElementById('carousel-slides');
    const btnPrev = document.getElementById('btn-prev');
    const btnNext = document.getElementById('btn-next');
    const btnRemove = document.getElementById('btn-remove');

    let imageList = [];
    let originals = [];
    let objectUrls = [];
    let currentSlideIndex = 0;
    const coresFotos = document.getElementById('cores-fotos');
    const corAjuda = document.getElementById('cor-ajuda');
    const modeloCor = document.getElementById('modelo-cor');
    let linhasCor = [];

    function syncInput() {
        const dt = new DataTransfer();
        imageList.forEach(file => dt.items.add(file));
        fotoInput.files = dt.files;
    }

    fotoInput.addEventListener('change', async function(e) {
        const files = Array.from(e.target.files);

        for (const file of files) {
            if (!file.type.startsWith('image/')) continue;

            const repetida = originals.some(f =>
                f.name === file.name && f.size === file.size && f.lastModified === file.lastModified
            );
            if (repetida) continue;

            const pronta = window.abrirRecorteImagem ? await window.abrirRecorteImagem(file) : file;
            if (!pronta) continue;
            imageList.push(pronta);
            linhasCor.push(criarLinhaCor());
            originals.push(file);
            currentSlideIndex = imageList.length - 1;
        }

        syncInput();
        renderCarousel();
    });

    function criarLinhaCor() {
        const linha = document.createElement('div');
        linha.className = 'cor-linha';
        const miniatura = document.createElement('img');
        miniatura.alt = '';
        linha.append(miniatura, document.createElement('span'), modeloCor.content.cloneNode(true));
        return linha;
    }

    function renderCarousel() {
        objectUrls.forEach(url => URL.revokeObjectURL(url));
        objectUrls = [];
        coresFotos.innerHTML = '';
        corAjuda.hidden = imageList.length > 0;

        if (imageList.length === 0) {
            uploadContent.style.display = 'flex';
            carouselContainer.style.display = 'none';
            carouselSlides.innerHTML = '';
            return;
        }

        uploadContent.style.display = 'none';
        carouselContainer.style.display = 'block';

        carouselSlides.innerHTML = '';
        imageList.forEach((file, index) => {
            const url = URL.createObjectURL(file);
            objectUrls.push(url);

            const img = document.createElement('img');
            img.src = url;
            img.alt = `Imagem ${index + 1}`;
            if (index === currentSlideIndex) {
                img.classList.add('active');
            }
            carouselSlides.appendChild(img);

            const linha = linhasCor[index];
            linha.querySelector('img').src = url;
            linha.querySelector('span').textContent = `Foto ${index + 1}`;
            coresFotos.appendChild(linha);
        });
    }

    function showSlide(index) {
        if (imageList.length === 0) return;

        if (index >= imageList.length) {
            currentSlideIndex = 0;
        } else if (index < 0) {
            currentSlideIndex = imageList.length - 1;
        } else {
            currentSlideIndex = index;
        }

        const slides = carouselSlides.querySelectorAll('img');
        slides.forEach(img => img.classList.remove('active'));
        if (slides[currentSlideIndex]) {
            slides[currentSlideIndex].classList.add('active');
        }
    }

    btnRemove.addEventListener('click', function() {
        if (imageList.length > 0) {
            imageList.splice(currentSlideIndex, 1);
            originals.splice(currentSlideIndex, 1);
            linhasCor.splice(currentSlideIndex, 1);

            if (currentSlideIndex >= imageList.length) {
                currentSlideIndex = Math.max(0, imageList.length - 1);
            }

            syncInput();
            renderCarousel();
            showSlide(currentSlideIndex);
        }
    });

    if (window.criarBotaoRecortar) {
        const btnRecortar = window.criarBotaoRecortar('Recortar / centralizar');
        carouselContainer.appendChild(btnRecortar);
        btnRecortar.addEventListener('click', async function() {
            if (imageList.length === 0) return;
            const idx = currentSlideIndex;
            const refeita = await window.abrirRecorteImagem(originals[idx]);
            if (!refeita) return;
            imageList[idx] = refeita;
            syncInput();
            renderCarousel();
            showSlide(idx);
        });
    }

    btnNext.addEventListener('click', function() {
        showSlide(currentSlideIndex + 1);
    });

    btnPrev.addEventListener('click', function() {
        showSlide(currentSlideIndex - 1);
    });
});
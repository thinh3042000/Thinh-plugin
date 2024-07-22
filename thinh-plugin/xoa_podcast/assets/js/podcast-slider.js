document.addEventListener('DOMContentLoaded', function() {
    const sliderWrapper = document.querySelector('.podcast-slider-wrapper');
    const slider = document.querySelector('.podcast-slider');
    const prevButton = document.querySelector('.slider-prev');
    const nextButton = document.querySelector('.slider-next');
    let slideWidth = sliderWrapper.offsetWidth / 4;
    let currentSlide = 0;

    function updateSlider() {
        slideWidth = sliderWrapper.offsetWidth / 4;
        slider.style.transition = 'none';
        slider.style.transform = 'translateX(' + (-currentSlide * slideWidth) + 'px)';
        setTimeout(() => {
            slider.style.transition = 'transform 0.5s ease-in-out';
        }, 0);
    }

    function moveToSlide(slideIndex) {
        currentSlide = slideIndex;
        slider.style.transform = 'translateX(' + (-currentSlide * slideWidth) + 'px)';
    }

    function cloneSlides() {
        const slides = Array.from(slider.children);
        const firstClones = slides.slice(0, 6).map(slide => slide.cloneNode(true));
        const lastClones = slides.slice(-6).map(slide => slide.cloneNode(true));
        firstClones.forEach(clone => slider.appendChild(clone));
        lastClones.reverse().forEach(clone => slider.insertBefore(clone, slider.firstChild));
    }

    function handleInfiniteLoop() {
        const slides = Array.from(slider.children);
        const slideCount = slides.length / 3; // Original slides count
        if (currentSlide < 0) {
            currentSlide = slideCount - 1;
            slider.style.transition = 'none';
            slider.style.transform = 'translateX(' + (-currentSlide * slideWidth) + 'px)';
            setTimeout(() => {
                slider.style.transition = 'transform 0.5s ease-in-out';
                currentSlide--;
                moveToSlide(currentSlide);
            }, 20);
        } else if (currentSlide >= slideCount) {
            currentSlide = 0;
            slider.style.transition = 'none';
            slider.style.transform = 'translateX(' + (-currentSlide * slideWidth) + 'px)';
            setTimeout(() => {
                slider.style.transition = 'transform 0.5s ease-in-out';
                currentSlide++;
                moveToSlide(currentSlide);
            }, 20);
        }
    }

    prevButton.addEventListener('click', function() {
        currentSlide--;
        handleInfiniteLoop();
        moveToSlide(currentSlide);
    });

    nextButton.addEventListener('click', function() {
        currentSlide++;
        handleInfiniteLoop();
        moveToSlide(currentSlide);
    });

    window.addEventListener('resize', function() {
        updateSlider();
    });

    function loadSliderContent(term = '') {
        const baseUrl = window.location.origin;
        // alert(baseUrl);
        const xhr = new XMLHttpRequest();
        xhr.open('GET', `${baseUrl}/wp-admin/admin-ajax.php?action=load_podcast_slider&term=${term}`);
        xhr.onload = function() {
            if (xhr.status === 200) {
                slider.innerHTML = xhr.responseText;
                currentSlide = 0;
                cloneSlides();
                updateSlider();
            }
        };
        xhr.send();
    }

    document.querySelectorAll('.podcast-category-filter').forEach(button => {
        button.addEventListener('click', function() {
            document.querySelectorAll('.podcast-category-filter').forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            const term = this.getAttribute('data-term');
            loadSliderContent(term);
        });
    });

    let isDown = false;
    let startX;
    let scrollLeft;

    sliderWrapper.addEventListener('mousedown', (e) => {
        isDown = true;
        sliderWrapper.classList.add('active');
        startX = e.pageX - sliderWrapper.offsetLeft;
        scrollLeft = sliderWrapper.scrollLeft;
    });

    sliderWrapper.addEventListener('mouseleave', () => {
        isDown = false;
        sliderWrapper.classList.remove('active');
    });

    sliderWrapper.addEventListener('mouseup', () => {
        isDown = false;
        sliderWrapper.classList.remove('active');
    });

    sliderWrapper.addEventListener('mousemove', (e) => {
        if (!isDown) return;
        e.preventDefault();
        const x = e.pageX - sliderWrapper.offsetLeft;
        const walk = (x - startX) * 3; //scroll-fast
        sliderWrapper.scrollLeft = scrollLeft - walk;
    });

    sliderWrapper.addEventListener('touchstart', (e) => {
        isDown = true;
        startX = e.touches[0].pageX - sliderWrapper.offsetLeft;
        scrollLeft = sliderWrapper.scrollLeft;
    });

    sliderWrapper.addEventListener('touchend', () => {
        isDown = false;
    });

    sliderWrapper.addEventListener('touchmove', (e) => {
        if (!isDown) return;
        e.preventDefault();
        const x = e.touches[0].pageX - sliderWrapper.offsetLeft;
        const walk = (x - startX) * 3; //scroll-fast
        sliderWrapper.scrollLeft = scrollLeft - walk;
    });

    cloneSlides();
    updateSlider();
});

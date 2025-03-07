document.addEventListener('DOMContentLoaded', () => {
    // Inicializar librería para efectos 3D en tarjetas
    if (typeof VanillaTilt !== "undefined") {
        VanillaTilt.init(document.querySelectorAll("[data-tilt]"), {
            max: 15,
            speed: 400,
            glare: true,
            "max-glare": 0.3,
        });
    }

    // Efecto de partículas en hero section
    createParticles();
    
    // Side Navigation
    const scrollContainer = document.documentElement;
    const sections = document.querySelectorAll('.section');
    const dots = document.querySelectorAll('.nav__dot');
    const navIndicator = document.querySelector('.nav__indicator');
    
    function updateNavigation() {
        const scrollPosition = window.pageYOffset || document.documentElement.scrollTop;
        const windowHeight = window.innerHeight;
        
        sections.forEach((section, index) => {
            const sectionTop = section.offsetTop;
            const sectionHeight = section.offsetHeight;
            
            // If we're within the section's boundaries
            if (scrollPosition >= sectionTop - windowHeight / 3 && 
                scrollPosition < sectionTop + sectionHeight - windowHeight / 3) {
                
                // Update navigation dots
                dots.forEach(dot => dot.classList.remove('nav__dot--active'));
                dots[index].classList.add('nav__dot--active');
                
                // Update indicator position - adjusted for proper alignment with the new styling
                const activeDot = dots[index];
                const dotPosition = activeDot.offsetTop;
                const dotHeight = activeDot.offsetHeight;
                // Center the indicator to the active dot
                navIndicator.style.transform = `translateY(${dotPosition - (40 - dotHeight) / 2}px)`;
                
                // Update section active state
                sections.forEach(s => s.classList.remove('hero--active'));
                section.classList.add('hero--active');
            }
        });

        // Update progress bar
        const docHeight = Math.max(
            document.body.scrollHeight,
            document.body.offsetHeight,
            document.documentElement.clientHeight,
            document.documentElement.scrollHeight,
            document.documentElement.offsetHeight
        );
        const scrollPercentage = (scrollPosition / (docHeight - windowHeight)) * 100;
        const progressBar = document.querySelector('.scroll-progress__bar');
        if (progressBar) {
            progressBar.style.width = `${Math.min(scrollPercentage, 100)}%`;
            progressBar.setAttribute('aria-valuenow', Math.round(scrollPercentage));
        }
    }
    
    // Mejorar el evento click para scroll suave
    dots.forEach((dot, index) => {
        dot.addEventListener('click', () => {
            // Scroll suave a la sección
            scrollContainer.scrollTo({
                top: sections[index].offsetTop,
                behavior: 'smooth'
            });
            
            // Actualizar navegación inmediatamente para mejor feedback
            dots.forEach(d => d.classList.remove('nav__dot--active'));
            dot.classList.add('nav__dot--active');
            const activeDot = dots[index];
            const dotPosition = activeDot.offsetTop;
            const dotHeight = activeDot.offsetHeight;
            // Center the indicator to the active dot - same logic as in updateNavigation
            navIndicator.style.transform = `translateY(${dotPosition - (40 - dotHeight) / 2}px)`;
        });
    });
    
    // Improve scroll event handling
    let ticking = false;
    window.addEventListener('scroll', () => {
        if (!ticking) {
            window.requestAnimationFrame(() => {
                updateNavigation();
                revealOnScroll();
                updateScrollIndicator();
                ticking = false;
            });
            ticking = true;
        }
    });
    
    // Inicializar navegación
    updateNavigation();
    
    // Control de visibilidad del indicador de scroll
    const scrollIndicator = document.querySelector('.scroll-indicator');
    function updateScrollIndicator() {
        const scrollPosition = window.pageYOffset || document.documentElement.scrollTop;
        const heroSection = document.querySelector('#hero');
        const heroHeight = heroSection.offsetHeight;
        const scrollThreshold = heroHeight * 0.2; // 20% of hero height
        
        if (scrollPosition > scrollThreshold) {
            scrollIndicator.style.opacity = '0';
            scrollIndicator.style.transform = 'translateX(-50%) scale(0.8)';
            scrollIndicator.style.pointerEvents = 'none';
        } else {
            scrollIndicator.style.opacity = '1';
            scrollIndicator.style.transform = 'translateX(-50%) scale(1)';
            scrollIndicator.style.pointerEvents = 'auto';
        }
    }
    
    // Enhance reveal on scroll
    function revealOnScroll() {
        const elementsToReveal = document.querySelectorAll('.feature-card, .section-title, .movie-grid, .features__stats, .download-app');
        const featuresSection = document.querySelector('.features');
        
        // Check if features section is in viewport
        if (featuresSection && !featuresSection.dataset.revealed) {
            const featuresSectionTop = featuresSection.getBoundingClientRect().top;
            const featuresSectionVisible = window.innerHeight / 1.3;
            
            if (featuresSectionTop < featuresSectionVisible) {
                featuresSection.classList.add('revealed');
                featuresSection.dataset.revealed = 'true';
            }
        }
        
        // Check other elements
        elementsToReveal.forEach(element => {
            if (!element.dataset.revealed) { // Only check elements that haven't been revealed yet
                const elementTop = element.getBoundingClientRect().top;
                const elementVisible = window.innerHeight / 1.3;
                
                if (elementTop < elementVisible) {
                    element.classList.add('revealed');
                    element.dataset.revealed = 'true';
                }
            }
        });
    }
    
    // Inicializar revelación
    revealOnScroll();
    
    // Implementación mejorada del stack de tarjetas FAQ
    initFaqCardStack();
    
    function initFaqCardStack() {
        const faqCards = document.querySelectorAll('.faq-card');
        const faqNavBtns = document.querySelectorAll('.faq-nav-btn');
        const prevBtn = document.querySelector('.faq-control-btn[data-control="prev"]');
        const nextBtn = document.querySelector('.faq-control-btn[data-control="next"]');
        const faqSection = document.getElementById('faq');
        
        let currentFaqIndex = 0;
        let isAnimating = false;
        
        if (faqCards.length === 0) return;
        
        // Initialize
        updateFaqCardStack();
        
        // Add intersection observer to trigger animation when FAQ section is visible
        const faqObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    faqSection.classList.add('hero--active');
                    // Trigger initial card animation with slight delay
                    setTimeout(() => {
                        updateFaqCardStack();
                    }, 300);
                }
            });
        }, { threshold: 0.2 });
        
        faqObserver.observe(faqSection);
        
        function updateFaqCardStack() {
            if (isAnimating) return;
            isAnimating = true;
            
            faqCards.forEach((card, index) => {
                card.classList.remove('active', 'prev', 'next');
                
                if (index === currentFaqIndex) {
                    card.classList.add('active');
                } else if (index === getPrevIndex()) {
                    card.classList.add('prev');
                } else if (index === getNextIndex()) {
                    card.classList.add('next');
                }
            });
            
            // Update navigation dots
            faqNavBtns.forEach((btn, index) => {
                btn.classList.toggle('active', index === currentFaqIndex);
            });
            
            // Reduce the animation lock duration to match the CSS transition
            setTimeout(() => {
                isAnimating = false;
            }, 500);
        }
        
        function getPrevIndex() {
            return (currentFaqIndex - 1 + faqCards.length) % faqCards.length;
        }
        
        function getNextIndex() {
            return (currentFaqIndex + 1) % faqCards.length;
        }
        
        function goToCard(index) {
            if (isAnimating || index === currentFaqIndex) return;
            currentFaqIndex = index;
            updateFaqCardStack();
        }
        
        function goToNextCard() {
            if (isAnimating) return;
            currentFaqIndex = getNextIndex();
            updateFaqCardStack();
        }
        
        function goToPrevCard() {
            if (isAnimating) return;
            currentFaqIndex = getPrevIndex();
            updateFaqCardStack();
        }
        
        // Event listeners
        if (prevBtn) {
            prevBtn.addEventListener('click', (e) => {
                e.preventDefault();
                goToPrevCard();
            });
        }
        
        if (nextBtn) {
            nextBtn.addEventListener('click', (e) => {
                e.preventDefault();
                goToNextCard();
            });
        }
        
        faqNavBtns.forEach((btn, index) => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                goToCard(index);
            });
        });
    
        // Auto rotate FAQ cards every 5 seconds
        let autoRotate = setInterval(goToNextCard, 5000);
    
        // Stop auto-rotation on hover/interaction
        const faqCardStack = document.querySelector('.faq-card-stack');
        if (faqCardStack) {
            faqCardStack.addEventListener('mouseenter', () => {
                clearInterval(autoRotate);
            });
    
            faqCardStack.addEventListener('mouseleave', () => {
                autoRotate = setInterval(goToNextCard, 5000);
            });
        }
    }
    
    // Añadir efecto de partículas en el fondo
    function createParticles() {
        const hero = document.querySelector('.hero');
        if (!hero) return;
        
        const particlesContainer = document.createElement('div');
        particlesContainer.className = 'particles-container';
        hero.appendChild(particlesContainer);
        
        // Crear partículas
        for (let i = 0; i < 50; i++) {
            createParticle(particlesContainer);
        }
    }
    
    function createParticle(container) {
        const particle = document.createElement('div');
        particle.className = 'particle';
        
        // Establecer posición aleatoria
        const posX = Math.random() * 100;
        const posY = Math.random() * 100;
        particle.style.left = `${posX}%`;
        particle.style.top = `${posY}%`;
        
        // Tamaño aleatorio
        const size = Math.random() * 5 + 1;
        particle.style.width = `${size}px`;
        particle.style.height = `${size}px`;
        
        // Animación aleatoria
        const duration = Math.random() * 20 + 10;
        const delay = Math.random() * 5;
        particle.style.animationDuration = `${duration}s`;
        particle.style.animationDelay = `${delay}s`;
        
        // Añadir al contenedor
        container.appendChild(particle);
    }
    
    // Tab functionality for movie collection
    const tabButtons = document.querySelectorAll('.tab-button');
    const movieGrids = document.querySelectorAll('.movie-grid');
    
    tabButtons.forEach((button, index) => {
        button.addEventListener('click', () => {
            // Toggle active class for tabs
            tabButtons.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');
            
            // Show corresponding grid
            movieGrids.forEach((grid, gridIndex) => {
                grid.style.display = index === gridIndex ? 'grid' : 'none';
                
                // Reset and trigger reveal animation
                if (index === gridIndex) {
                    grid.classList.remove('revealed');
                    setTimeout(() => {
                        grid.classList.add('revealed');
                    }, 50);
                }
            });
        });
    });
    
    // Initialize first tab as active
    if (tabButtons.length > 0 && movieGrids.length > 0) {
        tabButtons[0].classList.add('active');
        movieGrids[0].style.display = 'grid';
        setTimeout(() => {
            movieGrids[0].classList.add('revealed');
        }, 500);
    }
    
    // Añadir efectos a los botones
    const buttons = document.querySelectorAll('.cta-button, .login-btn');
    buttons.forEach(button => {
        button.addEventListener('mousemove', (e) => {
            const rect = button.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            button.style.setProperty('--x-pos', `${x}px`);
            button.style.setProperty('--y-pos', `${y}px`);
        });
    });

    // Efecto de escritura para los títulos de sección
    const titles = document.querySelectorAll('.section-title');
    titles.forEach(title => {
        title.classList.add('typing-effect');
    });
    
    // Inicializar todos los tooltips
    const tooltips = document.querySelectorAll('[data-tooltip]');
    tooltips.forEach(tooltip => {
        tooltip.addEventListener('mouseenter', () => {
            const tooltipText = tooltip.getAttribute('data-tooltip');
            const tooltipElement = document.createElement('div');
            tooltipElement.className = 'tooltip';
            tooltipElement.textContent = tooltipText;
            tooltip.appendChild(tooltipElement);
        });
        
        tooltip.addEventListener('mouseleave', () => {
            const tooltipElement = tooltip.querySelector('.tooltip');
            if (tooltipElement) {
                tooltipElement.remove();
            }
        });
    });
    
    // Cinema showcase interaction - pause rotation on hover
    const cinemaReel = document.querySelector('.cinema-showcase__reel');
    if (cinemaReel) {
        cinemaReel.addEventListener('mouseenter', () => {
            cinemaReel.style.animationPlayState = 'paused';
        });
        
        cinemaReel.addEventListener('mouseleave', () => {
            cinemaReel.style.animationPlayState = 'running';
        });
        
        // Add click interaction to cinema items
        document.querySelectorAll('.cinema-showcase__item').forEach(item => {
            item.addEventListener('click', () => {
                // Add a brief zoom effect
                const currentTransform = item.style.transform;
                item.style.transform = `${currentTransform} scale(1.1)`;
                setTimeout(() => {
                    item.style.transform = currentTransform;
                }, 300);
            });
        });
    }
    
    // Animate statistics counters
    animateStats();
    
    function animateStats() {
        const stats = document.querySelectorAll('.features__stat-number');
        
        stats.forEach(stat => {
            const target = parseInt(stat.getAttribute('data-count'), 10);
            const duration = 2000; // 2 seconds
            const step = target / (duration / 30); // Update every 30ms
            let current = 0;
            
            // Only start animation when element is in viewport
            const observer = new IntersectionObserver(entries => {
                if (entries[0].isIntersecting) {
                    const timer = setInterval(() => {
                        current += step;
                        if (current >= target) {
                            stat.textContent = target.toLocaleString();
                            clearInterval(timer);
                        } else {
                            stat.textContent = Math.round(current).toLocaleString();
                        }
                    }, 30);
                    observer.disconnect();
                }
            });
            
            observer.observe(stat);
        });
    }
});

// Función para hacer smooth scroll al cargar la página si hay un hash en la URL
window.addEventListener('load', () => {
    if (window.location.hash) {
        const targetSection = document.querySelector(window.location.hash);
        if (targetSection) {
            setTimeout(() => {
                targetSection.scrollIntoView({ behavior: 'smooth' });
            }, 300);
        }
    }
});

function handleFAQSection() {
    const faqSection = document.querySelector('.faq-section');
    const faqCards = document.querySelectorAll('.faq-card');
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                faqSection.classList.add('revealed');
                // Show FAQ cards with staggered delay
                faqCards.forEach((card, index) => {
                    setTimeout(() => {
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0) scale(1)';
                        card.style.pointerEvents = 'auto';
                    }, index * 200);
                });
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.3 });

    observer.observe(faqSection);
}

// Initialize FAQ section animations
document.addEventListener('DOMContentLoaded', () => {
    handleFAQSection();
});

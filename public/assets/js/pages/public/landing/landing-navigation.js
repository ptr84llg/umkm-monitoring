(function () {
    'use strict';

    const Landing = window.UMKMLanding;
    const S = Landing.SELECTORS;

    Landing.initHeaderAndNavigation = function () {
        const header = Landing.qs(S.header);
        const canvasMenu = Landing.qs(S.menuCanvas);
        const toTop = Landing.qs(S.toTop);

        function updateHeader() {
            if (header) {
                header.classList.toggle('is-scrolled', window.scrollY > 18);
            }

            if (toTop) {
                toTop.classList.toggle('is-visible', window.scrollY > 420);
            }
        }

        function closeBootstrapOffcanvas() {
            if (!canvasMenu || !window.bootstrap || !window.bootstrap.Offcanvas) {
                return;
            }

            const instance = window.bootstrap.Offcanvas.getInstance(canvasMenu);

            if (instance) {
                instance.hide();
            }
        }

        if (!Landing.headerScrollBound) {
            Landing.headerScrollBound = true;
            window.addEventListener('scroll', updateHeader, { passive: true });
        }

        updateHeader();

        if (canvasMenu) {
            Landing.qsa(S.menuClose, canvasMenu).forEach(function (item) {
                if (item.dataset.menuCloseBound === 'true') {
                    return;
                }

                item.dataset.menuCloseBound = 'true';
                item.addEventListener('click', closeBootstrapOffcanvas);
            });
        }

        if (toTop && toTop.dataset.toTopBound !== 'true') {
            toTop.dataset.toTopBound = 'true';
            toTop.addEventListener('click', function () {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        }
    };

    Landing.initRevealAnimation = function () {
        const items = Landing.qsa(S.reveal).filter(function (item) {
            return item.dataset.revealBound !== 'true' && !item.classList.contains('is-visible');
        });

        if (!items.length) {
            return;
        }

        if (!('IntersectionObserver' in window)) {
            items.forEach(function (item) {
                item.dataset.revealBound = 'true';
                item.classList.add('is-visible');
            });
            return;
        }

        const observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) {
                    return;
                }

                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            });
        }, { threshold: 0.14 });

        items.forEach(function (item) {
            item.dataset.revealBound = 'true';
            observer.observe(item);
        });
    };

    Landing.initParallax = function () {
        if (Landing.parallaxBound) {
            return;
        }

        const items = Landing.qsa(S.parallax);
        let ticking = false;

        if (!items.length) {
            return;
        }

        Landing.parallaxBound = true;

        function updateParallax() {
            const scrollY = window.scrollY || window.pageYOffset || 0;

            items.forEach(function (item) {
                const speed = Number(item.dataset.parallax || 0);
                item.style.transform = 'translate3d(0, ' + (scrollY * speed) + 'px, 0)';
            });

            ticking = false;
        }

        window.addEventListener('scroll', function () {
            if (ticking) {
                return;
            }

            window.requestAnimationFrame(updateParallax);
            ticking = true;
        }, { passive: true });
    };

    Landing.initCounters = function () {
        const counters = Landing.qsa(S.counter).filter(function (counter) {
            return counter.dataset.counterBound !== 'true';
        });

        if (!counters.length || !('IntersectionObserver' in window)) {
            return;
        }

        const observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) {
                    return;
                }

                const element = entry.target;
                const target = Number(element.dataset.count || 0);
                const duration = 1100;
                const startedAt = performance.now();

                function animate(now) {
                    const progress = Math.min((now - startedAt) / duration, 1);
                    const eased = 1 - Math.pow(1 - progress, 3);
                    element.textContent = Landing.formatNumber(Math.round(target * eased));

                    if (progress < 1) {
                        window.requestAnimationFrame(animate);
                    }
                }

                window.requestAnimationFrame(animate);
                observer.unobserve(element);
            });
        }, { threshold: 0.62 });

        counters.forEach(function (counter) {
            counter.dataset.counterBound = 'true';
            observer.observe(counter);
        });
    };

    Landing.initTiltCard = function () {
        const tiltCard = Landing.qs(S.tiltCard);

        if (!tiltCard || tiltCard.dataset.tiltCardBound === 'true') {
            return;
        }

        const boardWindow = tiltCard.querySelector('.board-window');

        if (!boardWindow) {
            return;
        }

        tiltCard.dataset.tiltCardBound = 'true';

        tiltCard.addEventListener('mousemove', function (event) {
            const rect = tiltCard.getBoundingClientRect();
            const x = event.clientX - rect.left;
            const y = event.clientY - rect.top;
            const rotateY = ((x / rect.width) - 0.5) * 7;
            const rotateX = ((y / rect.height) - 0.5) * -7;

            boardWindow.style.transform = 'rotateX(' + rotateX + 'deg) rotateY(' + rotateY + 'deg)';
        });

        tiltCard.addEventListener('mouseleave', function () {
            boardWindow.style.transform = 'rotateX(0deg) rotateY(0deg)';
        });
    };
})();

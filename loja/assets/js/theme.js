/**
 * Akti Store Theme — Base JavaScript
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(function (anchor) {
            anchor.addEventListener('click', function (e) {
                var target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    e.preventDefault();
                    target.scrollIntoView({ behavior: 'smooth' });
                }
            });
        });

        // Lazy loading for images
        if ('IntersectionObserver' in window) {
            var lazyImages = document.querySelectorAll('img[data-src]');
            var imageObserver = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        var img = entry.target;
                        img.src = img.dataset.src;
                        img.removeAttribute('data-src');
                        imageObserver.unobserve(img);
                    }
                });
            });
            lazyImages.forEach(function (img) {
                imageObserver.observe(img);
            });
        }
    });
})();

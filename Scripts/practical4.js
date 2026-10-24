/* StudentHub Practical 4 - DOM manipulation, events and localStorage */
"use strict";

document.addEventListener("DOMContentLoaded", () => {
    // 0. Accessible light/dark theme switcher persisted across pages.
    const themeToggle = document.getElementById("theme-toggle");
    const savedTheme = localStorage.getItem("studenthub-theme");
    const applyTheme = (theme) => {
        const isDark = theme === "dark";
        document.body.classList.toggle("dark-theme", isDark);
        if (themeToggle) {
            themeToggle.textContent = isDark ? "☀️ Light mode" : "🌙 Dark mode";
            themeToggle.setAttribute("aria-pressed", String(isDark));
        }
    };
    applyTheme(savedTheme === "dark" ? "dark" : "light");
    if (themeToggle) {
        themeToggle.addEventListener("click", () => {
            const nextTheme = document.body.classList.contains("dark-theme") ? "light" : "dark";
            localStorage.setItem("studenthub-theme", nextTheme);
            applyTheme(nextTheme);
        });
    }


    // 1. Responsive hamburger navigation
    const menuToggle = document.getElementById("menu-toggle");
    const mainNav = document.getElementById("main-nav");

    if (menuToggle && mainNav) {
        menuToggle.addEventListener("click", () => {
            const isOpen = mainNav.classList.toggle("menu-open");
            menuToggle.setAttribute("aria-expanded", String(isOpen));
        });

        // Close the mobile menu after a navigation link is selected.
        mainNav.querySelectorAll("a").forEach((link) => {
            link.addEventListener("click", () => {
                mainNav.classList.remove("menu-open");
                menuToggle.setAttribute("aria-expanded", "false");
            });
        });

        // Ensure the desktop navigation is visible when resizing upward.
        window.addEventListener("resize", () => {
            if (window.innerWidth > 600) {
                mainNav.classList.remove("menu-open");
                menuToggle.setAttribute("aria-expanded", "false");
            }
        });
    }

    // 2. Collapsible FAQ
    document.querySelectorAll(".faq-question").forEach((question) => {
        question.addEventListener("click", () => {
            const answerId = question.getAttribute("aria-controls");
            const answer = answerId ? document.getElementById(answerId) : null;
            if (!answer) return;

            const willOpen = answer.hidden;
            answer.hidden = !willOpen;
            question.setAttribute("aria-expanded", String(willOpen));
        });
    });

    // 3. Dismissible notification banner
    const notification = document.getElementById("notification-banner");
    const dismissNotification = document.getElementById("dismiss-notification");
    if (notification && dismissNotification) {
        dismissNotification.addEventListener("click", () => {
            notification.hidden = true;
        });
    }

    // 4. Modal popup
    const modal = document.getElementById("info-modal");
    const openModal = document.getElementById("open-modal");
    const closeModal = document.getElementById("close-modal");
    let lastFocusedElement = null;

    const hideModal = () => {
        if (!modal) return;
        modal.hidden = true;
        if (lastFocusedElement) lastFocusedElement.focus();
    };

    if (modal && openModal && closeModal) {
        openModal.addEventListener("click", () => {
            lastFocusedElement = document.activeElement;
            modal.hidden = false;
            closeModal.focus();
        });

        closeModal.addEventListener("click", hideModal);

        modal.addEventListener("click", (event) => {
            if (event.target === modal) hideModal();
        });

        document.addEventListener("keydown", (event) => {
            if (event.key === "Escape" && !modal.hidden) hideModal();
        });
    }

    // 5. Content slider
    const slides = Array.from(document.querySelectorAll(".slide"));
    const previousSlide = document.getElementById("previous-slide");
    const nextSlide = document.getElementById("next-slide");
    const slideCounter = document.getElementById("slide-counter");
    let currentSlide = 0;

    const showSlide = (index) => {
        if (slides.length === 0) return;
        currentSlide = (index + slides.length) % slides.length;
        slides.forEach((slide, i) => {
            slide.hidden = i !== currentSlide;
        });
        if (slideCounter) {
            slideCounter.textContent = `Slide ${currentSlide + 1} of ${slides.length}`;
        }
    };

    if (slides.length > 0) {
        showSlide(0);
        if (previousSlide) previousSlide.addEventListener("click", () => showSlide(currentSlide - 1));
        if (nextSlide) nextSlide.addEventListener("click", () => showSlide(currentSlide + 1));
    }

});

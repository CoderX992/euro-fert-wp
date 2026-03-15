"use strict";
/* Updated : #1 bug : False Navigation active link  */

function qs(selector, root) {
  return (root || document).querySelector(selector);
}

function qsa(selector, root) {
  return Array.prototype.slice.call((root || document).querySelectorAll(selector));
}

function addListener(el, eventName, handler, options) {
  if (!el) return;
  el.addEventListener(eventName, handler, options || false);
}

/* -----------------------------
   Entry point (runs once)
------------------------------ */
document.addEventListener("DOMContentLoaded", function () {
  safeInit(initScrollHeaderAndBackToTop, "initScrollHeaderAndBackToTop");
  safeInit(initMobileMenuAndDropdown, "initMobileMenuAndDropdown");
  safeInit(initTestimonialCarousel, "initTestimonialCarousel");
  safeInit(initContactFormValidation, "initContactFormValidation");
  safeInit(initViewportAnimations, "initViewportAnimations");
  safeInit(initProductDrawerNavDash, "initProductDrawerNavDash");
});

function safeInit(fn, name) {
  try {
    fn();
  } catch (e) {
    console.error(name + " failed:", e);
  }
}
/* -----------------------------
   Keep CSS --header-offset in sync with real header height
------------------------------ */
function syncHeaderOffset() {
  var header = qs("#header") || qs(".header");
  if (!header) return;

  var h = Math.ceil(header.getBoundingClientRect().height);
  document.documentElement.style.setProperty("--header-offset", h + "px");
}

/* -----------------------------
   1) Header scroll behavior + Back-to-top
   - Safe even if elements are missing
------------------------------ */
function initScrollHeaderAndBackToTop() {
  var header = qs("#header");
  var backToTopBtn = qs(".back-to-top");
  if (!header) return;

  var lastScrollY = window.scrollY || 0;
  var isScrolled = null; // track header--scrolled state

  function updateHeaderOnScroll() {
    var currentY = window.scrollY || 0;

    // 1) shrink header (resync offset only when state changes)
    var shouldBeScrolled = currentY > 50;
    if (shouldBeScrolled !== isScrolled) {
      header.classList.toggle("header--scrolled", shouldBeScrolled);
      isScrolled = shouldBeScrolled;
      syncHeaderOffset();
    }

    // if menu open, do not auto-hide
    var menuOpen = document.body.classList.contains("menu-open");
    if (menuOpen) {
      lastScrollY = currentY;
      return;
    }

    // 2) auto-hide on scroll down, show on scroll up
    if (currentY > lastScrollY && currentY > 120) {
      header.classList.add("header--hidden");
    } else {
      header.classList.remove("header--hidden");
    }

    lastScrollY = currentY;
  }

  function updateBackToTopVisibility() {
    if (!backToTopBtn) return;
    backToTopBtn.classList.toggle("active", (window.scrollY || 0) > 300);
  }

  addListener(window, "scroll", updateHeaderOnScroll, { passive: true });
  addListener(window, "scroll", updateBackToTopVisibility, { passive: true });

  addListener(window, "resize", function () {
    syncHeaderOffset();
  });

  addListener(window, "load", function () {
    syncHeaderOffset();
  });

  addListener(backToTopBtn, "click", function (e) {
    e.preventDefault();
    window.scrollTo({ top: 0, behavior: "smooth" });
  });

  // initial state
  updateHeaderOnScroll();
  syncHeaderOffset();
  updateBackToTopVisibility();
}

/* -----------------------------
   2) Mobile menu (Bootstrap Collapse) + Dropdown submenu
   - Works for WP templates that include your header
------------------------------ */
function initMobileMenuAndDropdown() {
  // Highlight active page link (desktop & mobile)
  setActiveNavLink();

  // Dropdown open/close behavior for "Product Categories"
  initHeaderDropdownToggles();

  // --- Optional but recommended: keep "menu-open" in sync with the real menu state ---
  // This makes your hide-on-scroll header stop hiding while the menu is open.
  var collapseEl = qs("#navbarSupportedContent") || qs(".navbar-collapse");

  if (collapseEl && typeof bootstrap !== "undefined") {
    addListener(collapseEl, "shown.bs.collapse", function () {
      document.body.classList.add("menu-open");
      syncHeaderOffset();
    });

    addListener(collapseEl, "hidden.bs.collapse", function () {
      document.body.classList.remove("menu-open");
      syncHeaderOffset();
    });
  }

  // --- Single document click listener (replaces the two you currently have) ---
  addListener(document, "click", function (e) {
    var target = e.target;

    // 1) Navbar toggler clicked
    var toggler = target && target.closest && target.closest(".navbar-toggler");
    if (toggler) {
      // If Bootstrap events exist, they'll handle syncing precisely.
      // Fallback: sync after the UI has had time to update.
      if (!collapseEl) {
        setTimeout(syncHeaderOffset, 250);
      } else {
        // Even with Bootstrap events, a micro-sync is harmless and can help on some themes
        setTimeout(syncHeaderOffset, 0);
      }
      return;
    }

    // 2) Dropdown opener / parent link clicked (submenu can change header height on desktop)
    var opener = target && target.closest && target.closest(".nav-opener, .parent-link");
    if (opener) {
      setTimeout(syncHeaderOffset, 0);
    }
  });
}

function setActiveNavLink() {
  var currentPath = (window.location.pathname || "").replace(/\/+$/, "");

  qsa(".nav-link:not(.parent-link)").forEach(function (link) {
    var linkPath = "";
    try {
      linkPath = new URL(link.href, window.location.origin).pathname.replace(/\/+$/, "");
    } catch (e) {
      linkPath = (link.getAttribute("href") || "").replace(/\/+$/, "");
    }
    link.classList.toggle("active", linkPath && linkPath === currentPath);
  });
}

/* Dropdown open/close behavior: toggles .open on .nav-item.has-dropdown */
function initHeaderDropdownToggles() {
  var dropdownParents = qsa(".nav-item.has-dropdown");
  if (!dropdownParents.length) return;

  function closeAllDropdowns(exceptItem) {
    dropdownParents.forEach(function (item) {
      if (item !== exceptItem) item.classList.remove("open");
    });
  }

  dropdownParents.forEach(function (parentItem) {
    var opener = qs(".nav-opener", parentItem);
    var parentLink = qs(".parent-link", parentItem);

    function toggleSubmenu(e) {
      // Important: this stops <a href=""> from navigating/reloading
      e.preventDefault();
      e.stopPropagation();

      var willOpen = !parentItem.classList.contains("open");
      closeAllDropdowns(parentItem);
      parentItem.classList.toggle("open", willOpen);
    }

    addListener(opener, "click", toggleSubmenu);
    addListener(parentLink, "click", toggleSubmenu);
  });

  // Desktop only: click outside closes dropdown
  addListener(document, "click", function (event) {
    var isDesktop = window.matchMedia && window.matchMedia("(min-width: 768px)").matches;
    if (!isDesktop) return;

    var clickedInside = event.target.closest(".nav-item.has-dropdown");
    if (!clickedInside) {
      dropdownParents.forEach(function (item) {
        item.classList.remove("open");
      });
    }
  });
}

/* -----------------------------
   3) Bootstrap Testimonial Carousel
------------------------------ */
function initTestimonialCarousel() {
  var el = qs("#testimonialCarousel");
  if (!el) return;

  if (typeof bootstrap === "undefined" || !bootstrap.Carousel) return;

  // eslint-disable-next-line no-unused-vars
  new bootstrap.Carousel(el, { interval: 5000, wrap: true });
}

/* -----------------------------
   4) Contact form validation (only runs if contact form exists)
------------------------------ */
function initContactFormValidation() {
  var form = qs(".contact-form form");
  if (!form) return;

  addListener(form, "submit", function (e) {
    e.preventDefault();

    var isValid = true;
    var requiredFields = qsa("[required]", form);

    requiredFields.forEach(function (field) {
      var ok = field.value && field.value.trim() !== "";
      field.classList.toggle("is-invalid", !ok);
      if (!ok) isValid = false;
    });

    var emailField = qs("#email", form);
    if (emailField && emailField.value) {
      var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      var okEmail = emailRegex.test(emailField.value);
      emailField.classList.toggle("is-invalid", !okEmail);
      if (!okEmail) isValid = false;
    }

    if (isValid) {
      form.reset();
      alert("Thank you! Your message has been sent successfully.");
    }
  });

  qsa("input, textarea", form).forEach(function (input) {
    addListener(input, "input", function () {
      if (input.hasAttribute("required")) {
        input.classList.toggle("is-invalid", input.value.trim() === "");
      }
      if (input.type === "email" && input.value) {
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        input.classList.toggle("is-invalid", !emailRegex.test(input.value));
      }
    });
  });
}

/* -----------------------------
   5) Viewport animations (fade/slide/scale)
------------------------------ */
function initViewportAnimations() {
  var animated = qsa(".fade-in, .slide-up, .scale-in, .product-grid-item");
  if (!animated.length) return;

  if ("IntersectionObserver" in window) {
    var io = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) activateEl(entry.target);
        });
      },
      { threshold: 0.15 }
    );

    animated.forEach(function (el) {
      io.observe(el);
    });
    return;
  }

  function isInViewport(el) {
    var rect = el.getBoundingClientRect();
    return rect.top <= (window.innerHeight || document.documentElement.clientHeight) * 0.85 && rect.bottom >= 0;
  }

  function check() {
    animated.forEach(function (el) {
      if (isInViewport(el)) activateEl(el);
    });
  }

  check();
  addListener(window, "scroll", check, { passive: true });
}

function activateEl(el) {
  // .product-grid-item uses .animate in animations.css
  if (el.classList.contains("product-grid-item")) {
    el.classList.add("animate");
    return;
  }
  // other viewport animations use .active
  el.classList.add("active");
}

/* drawer navigation*/
function initProductDrawerNavDash() {
  const panel = document.querySelector(".product-navigation-panel");
  if (!panel) return;

  const links = Array.from(panel.querySelectorAll('.nav-panel-link[href^="#"]'));
  if (!links.length) return;

  // 1. STATE LOCK: Prevents observer from firing while we scroll manually
  // THIS IS THE MISSING PART THAT FIXES THE FLICKER
  let isManualScrolling = false;
  let manualScrollTimer = null;

  const getHeaderOffset = () => {
    const cssVar = getComputedStyle(document.documentElement).getPropertyValue("--header-offset").trim();
    const n = parseInt(cssVar, 10);
    return Number.isFinite(n) ? n : 72;
  };

  const setActive = (link) => {
    links.forEach((a) => a.classList.toggle("active", a === link));
  };

  links.forEach((a) => {
    a.addEventListener("click", (e) => {
      e.preventDefault();

      const id = a.getAttribute("href").slice(1);
      const target = document.getElementById(id);
      if (!target) return;

      // 2. LOCK ON: Stop the camera immediately
      isManualScrolling = true;
      setActive(a);

      // 3. Scroll: We use scrollIntoView so it respects your CSS 'scroll-margin-top'
      target.scrollIntoView({ behavior: "smooth", block: "start" });

      // 4. LOCK OFF: Restart camera after scroll finishes (approx 1000ms)
      clearTimeout(manualScrollTimer);
      manualScrollTimer = setTimeout(() => {
        isManualScrolling = false;
        // Update URL hash safely
        history.replaceState(null, "", `#${id}`);
      }, 1000);
    });
  });

  const sections = links.map((a) => document.getElementById(a.getAttribute("href").slice(1))).filter(Boolean);

  if ("IntersectionObserver" in window && sections.length) {
    const observer = new IntersectionObserver(
      (entries) => {
        // 5. THE GUARD: If we are manually scrolling, STOP here.
        if (isManualScrolling) return;

        const visibleEntries = entries.filter((en) => en.isIntersecting);
        if (visibleEntries.length === 0) return;

        // Sort by how much is visible (Intersection Ratio)
        const best = visibleEntries.sort((a, b) => b.intersectionRatio - a.intersectionRatio)[0];

        // 6. NOISE FILTER: Ignore tiny slivers (less than 10% visible)
        if (best.intersectionRatio < 0.1) return;

        if (best) {
          const link = links.find((a) => a.getAttribute("href") === `#${best.target.id}`);
          if (link) setActive(link);
        }
      },
      {
        threshold: [0, 0.1, 0.25, 0.5, 0.75, 1],
        // 7. SYNC: Matches your CSS offset (Header + 24px)
        rootMargin: `-${getHeaderOffset() + 24}px 0px -50% 0px`
      }
    );

    sections.forEach((sec) => observer.observe(sec));
  }

  // Initial active state check
  const initial = (location.hash && links.find((a) => a.getAttribute("href") === location.hash)) || links[0];
  if (initial) requestAnimationFrame(() => setActive(initial));
}

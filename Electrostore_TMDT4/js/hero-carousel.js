(() => {
  const carousel = document.querySelector('[data-hero-carousel]');
  if (!carousel) return;
  const slides = Array.from(carousel.querySelectorAll('[data-hero-slide]'));
  const dotsRoot = carousel.querySelector('[data-hero-dots]');
  const counter = carousel.querySelector('[data-hero-counter]');
  const previous = carousel.querySelector('[data-hero-prev]');
  const next = carousel.querySelector('[data-hero-next]');
  if (slides.length < 2) {
    if (previous) previous.hidden = true;
    if (next) next.hidden = true;
    return;
  }
  let active = Math.max(0, slides.findIndex(slide => slide.classList.contains('is-active')));
  let timer = null;
  let paused = false;
  const interval = Math.max(4000, Number(carousel.dataset.interval) || 6000);
  const dots = slides.map((slide, index) => {
    const dot = document.createElement('button');
    dot.type = 'button';
    dot.className = 'hero-dot';
    dot.setAttribute('aria-label', 'Xem slide ' + (index + 1));
    dot.addEventListener('click', () => show(index, true));
    dotsRoot.append(dot);
    return dot;
  });
  const show = (index, restart) => {
    active = (index + slides.length) % slides.length;
    slides.forEach((slide, i) => {
      const selected = i === active;
      slide.classList.toggle('is-active', selected);
      slide.setAttribute('aria-hidden', String(!selected));
    });
    dots.forEach((dot, i) => {
      dot.classList.toggle('is-active', i === active);
      dot.setAttribute('aria-current', i === active ? 'true' : 'false');
    });
    if (counter) counter.textContent = String(active + 1).padStart(2, '0') + ' / ' + String(slides.length).padStart(2, '0');
    if (restart) startTimer();
  };
  const stopTimer = () => { if (timer) window.clearInterval(timer); timer = null; };
  const startTimer = () => {
    stopTimer();
    if (paused || document.hidden || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    timer = window.setInterval(() => show(active + 1, false), interval);
  };
  previous.addEventListener('click', () => show(active - 1, true));
  next.addEventListener('click', () => show(active + 1, true));
  carousel.addEventListener('mouseenter', () => { paused = true; stopTimer(); });
  carousel.addEventListener('mouseleave', () => { paused = false; startTimer(); });
  carousel.addEventListener('focusin', () => { paused = true; stopTimer(); });
  carousel.addEventListener('focusout', event => {
    if (!carousel.contains(event.relatedTarget)) { paused = false; startTimer(); }
  });
  carousel.addEventListener('keydown', event => {
    if (event.key === 'ArrowLeft') show(active - 1, true);
    if (event.key === 'ArrowRight') show(active + 1, true);
  });
  document.addEventListener('visibilitychange', startTimer);
  show(active, false);
  startTimer();
})();
/* =====================================================================
   FEDER · Interacciones globales
   ===================================================================== */
(() => {
  'use strict';

  const $ = (s, c = document) => c.querySelector(s);
  const $$ = (s, c = document) => Array.from(c.querySelectorAll(s));
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* ---------- Tema (claro / nocturno) ---------- */
  const root = document.documentElement;
  $$('[data-theme-toggle]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const next = root.dataset.theme === 'dark' ? 'light' : 'dark';
      root.dataset.theme = next;
      try { localStorage.setItem('feder-theme', next); } catch (e) { /* modo privado */ }
      btn.setAttribute('aria-pressed', String(next === 'dark'));
    });
  });

  /* ---------- Cabecera ---------- */
  const header = $('.site-header');
  if (header) {
    const onScroll = () => header.classList.toggle('is-scrolled', window.scrollY > 8);
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
  }

  /* ---------- Menú móvil ---------- */
  const toggle = $('.nav-toggle');
  const nav = $('#site-nav');
  if (toggle && nav) {
    const close = () => { nav.classList.remove('is-open'); toggle.setAttribute('aria-expanded', 'false'); };
    toggle.addEventListener('click', () => {
      const open = nav.classList.toggle('is-open');
      toggle.setAttribute('aria-expanded', String(open));
    });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') close(); });
    document.addEventListener('click', (e) => { if (!nav.contains(e.target) && !toggle.contains(e.target)) close(); });
    window.addEventListener('resize', () => { if (window.innerWidth > 980) close(); });
  }

  /* ---------- Flash: auto-cierre ---------- */
  $$('.flash').forEach((f, i) => {
    const kill = () => { f.classList.add('is-leaving'); setTimeout(() => f.remove(), 350); };
    $('button', f)?.addEventListener('click', kill);
    setTimeout(kill, 5200 + i * 600);
  });

  /* ---------- Reveal on scroll ---------- */
  const revealEls = $$('.reveal, .curve-svg, [data-animate]');
  if ('IntersectionObserver' in window && !reduceMotion) {
    const io = new IntersectionObserver((entries) => {
      entries.forEach((en) => {
        if (en.isIntersecting) {
          en.target.classList.add('is-visible');
          io.unobserve(en.target);
        }
      });
    }, { threshold: 0.15, rootMargin: '0px 0px -40px 0px' });
    revealEls.forEach((el) => io.observe(el));
  } else {
    revealEls.forEach((el) => el.classList.add('is-visible'));
  }

  /* ---------- Contadores animados (prueba social) ---------- */
  const counters = $$('[data-count]');
  const runCounter = (el) => {
    const target = parseInt(el.dataset.count, 10) || 0;
    if (reduceMotion) { el.textContent = target.toLocaleString('es'); return; }
    const t0 = performance.now();
    const dur = 1400;
    const tick = (t) => {
      const p = Math.min(1, (t - t0) / dur);
      const eased = 1 - Math.pow(1 - p, 3);
      el.textContent = Math.round(target * eased).toLocaleString('es');
      if (p < 1) requestAnimationFrame(tick);
    };
    requestAnimationFrame(tick);
  };
  if ('IntersectionObserver' in window) {
    const co = new IntersectionObserver((ents) => ents.forEach((en) => {
      if (en.isIntersecting) { runCounter(en.target); co.unobserve(en.target); }
    }), { threshold: 0.5 });
    counters.forEach((c) => co.observe(c));
  } else counters.forEach(runCounter);

  /* ---------- Barras: animación de entrada ---------- */
  requestAnimationFrame(() => {
    $$('[data-w]').forEach((el) => { el.style.setProperty('--w', el.dataset.w + '%'); });
    $$('[data-p]').forEach((el) => { el.style.setProperty('--p', el.dataset.p); });
    $$('[data-h]').forEach((el) => { el.style.setProperty('--h', el.dataset.h + '%'); });
  });

  /* ---------- Rellenar cuentas demo ---------- */
  $$('[data-demo]').forEach((b) => b.addEventListener('click', () => {
    const [email, pass] = b.dataset.demo.split('|');
    const em = $('#email'); const pw = $('#password');
    if (em && pw) { em.value = email; pw.value = pass; pw.focus(); }
  }));

  /* ---------- Confirmaciones ---------- */
  $$('[data-confirm]').forEach((el) => el.addEventListener('submit', (e) => {
    if (!window.confirm(el.dataset.confirm)) e.preventDefault();
  }));

  /* ---------- Mostrar contraseña ---------- */
  $$('[data-reveal-pass]').forEach((b) => b.addEventListener('click', () => {
    const input = document.getElementById(b.dataset.revealPass);
    if (!input) return;
    input.type = input.type === 'password' ? 'text' : 'password';
    b.setAttribute('aria-pressed', String(input.type === 'text'));
  }));

  /* ---------- Envío: evitar doble clic ---------- */
  $$('form[data-once]').forEach((f) => f.addEventListener('submit', () => {
    $$('button[type="submit"]', f).forEach((b) => { b.disabled = true; b.dataset.label = b.textContent; });
  }));

  /* ---------- API global ---------- */
  const csrf = $('meta[name="csrf-token"]')?.content || '';
  window.Feder = {
    async api(url, data = {}) {
      const res = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf, Accept: 'application/json' },
        body: JSON.stringify(data),
        credentials: 'same-origin',
      });
      let json = {};
      try { json = await res.json(); } catch (e) { json = { ok: false, error: 'Respuesta inválida del servidor.' }; }
      if (!res.ok && json.ok !== false) json.ok = false;
      return json;
    },

    toast(msg, type = 'info') {
      let stack = $('.flash-stack');
      if (!stack) { stack = document.createElement('div'); stack.className = 'flash-stack'; document.body.appendChild(stack); }
      const el = document.createElement('div');
      el.className = `flash flash--${type}`;
      el.setAttribute('role', 'status');
      el.innerHTML = '<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M10 3.5 11.6 8 16 9.5l-4.4 1.6L10 15.5l-1.6-4.4L4 9.5 8.4 8 10 3.5Z"/></svg><p></p>';
      $('p', el).textContent = msg;
      stack.appendChild(el);
      setTimeout(() => { el.classList.add('is-leaving'); setTimeout(() => el.remove(), 350); }, 4200);
    },

    /* Confeti ligero en canvas: recompensa visual por micro-victoria */
    confetti(amount = 140) {
      if (reduceMotion) return;
      let cv = document.getElementById('confetti');
      if (!cv) { cv = document.createElement('canvas'); cv.id = 'confetti'; document.body.appendChild(cv); }
      const ctx = cv.getContext('2d');
      const dpr = Math.min(window.devicePixelRatio || 1, 2);
      cv.width = innerWidth * dpr; cv.height = innerHeight * dpr;
      ctx.scale(dpr, dpr);
      const colors = ['#4F46E5', '#7C3AED', '#FFB547', '#FF7A45', '#0F9D7A', '#A5B4FC'];
      const parts = Array.from({ length: amount }, () => ({
        x: innerWidth / 2 + (Math.random() - 0.5) * 120,
        y: innerHeight * 0.38,
        vx: (Math.random() - 0.5) * 14,
        vy: -Math.random() * 14 - 4,
        s: Math.random() * 7 + 4,
        r: Math.random() * Math.PI,
        vr: (Math.random() - 0.5) * 0.3,
        c: colors[(Math.random() * colors.length) | 0],
        life: 0,
      }));
      let frame = 0;
      const draw = () => {
        ctx.clearRect(0, 0, innerWidth, innerHeight);
        parts.forEach((p) => {
          p.vy += 0.38; p.vx *= 0.99; p.x += p.vx; p.y += p.vy; p.r += p.vr; p.life++;
          ctx.save(); ctx.translate(p.x, p.y); ctx.rotate(p.r);
          ctx.fillStyle = p.c; ctx.globalAlpha = Math.max(0, 1 - p.life / 140);
          ctx.fillRect(-p.s / 2, -p.s / 4, p.s, p.s / 2);
          ctx.restore();
        });
        if (++frame < 150) requestAnimationFrame(draw);
        else ctx.clearRect(0, 0, innerWidth, innerHeight);
      };
      requestAnimationFrame(draw);
    },

    animateNumber(el, to, dur = 1100) {
      if (!el) return;
      if (reduceMotion) { el.textContent = to; return; }
      const from = 0; const t0 = performance.now();
      const step = (t) => {
        const p = Math.min(1, (t - t0) / dur);
        el.textContent = Math.round(from + (to - from) * (1 - Math.pow(1 - p, 3)));
        if (p < 1) requestAnimationFrame(step);
      };
      requestAnimationFrame(step);
    },

    escape(s) {
      return String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    },
  };
})();

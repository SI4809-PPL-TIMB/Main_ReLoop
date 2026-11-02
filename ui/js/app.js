// shortcut: ikon 3D sementara dari Fluent Emoji (MIT) via CDN; ganti EMOJI_BASE/emojiUrl saat folder ilustrasi final masuk.
const EMOJI_BASE = 'https://cdn.jsdelivr.net/gh/microsoft/fluentui-emoji@main/assets/';
const emojiUrl = (name) => `${EMOJI_BASE}${encodeURIComponent(name)}/3D/${name.toLowerCase().replace(/ /g, '_')}_3d.png`;

// shortcut: PLACEHOLDER=true menampilkan kotak abu sebagai pengganti ikon/ilustrasi; set false (atau ganti emojiUrl) saat aset final ada.
const PLACEHOLDER = true;
const PH_ICON = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" style="width:42%;height:42%"><rect x="3" y="4" width="18" height="16" rx="2.5"/><circle cx="9" cy="10" r="1.8"/><path d="m4 18 5-5 4 4 3-3 4 4"/></svg>';
document.querySelectorAll('img[data-emoji]').forEach((img) => {
  if (!PLACEHOLDER) {
    img.src = emojiUrl(img.dataset.emoji);
    img.loading = 'lazy';
    if (!img.hasAttribute('alt')) img.alt = '';
    return;
  }
  const ph = document.createElement('span');
  ph.className = img.className + ' grid shrink-0 place-items-center rounded-[28%] bg-slate-200/80 text-slate-400';
  ph.title = img.dataset.emoji; // nama aset yang nanti dipasang di sini
  ph.setAttribute('aria-hidden', 'true');
  // Ikon kecil (<= size-6) cukup kotak polos; yang lebih besar diberi glyph gambar.
  const size = Number((img.className.match(/size-(\d+)/) || [])[1] || 0);
  if (size > 6) ph.innerHTML = PH_ICON;
  img.replaceWith(ph);
});

// Bottom nav dirender sekali di sini; di Laravel jadi partial Blade.
const NAV_ICONS = {
  home: '<path d="M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6h-6v6H4a1 1 0 0 1-1-1z"/>',
  search: '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
  recycle: '<path d="M4 12a8 8 0 0 1 14-5.3L20 9"/><path d="M20 4v5h-5"/><path d="M20 12a8 8 0 0 1-14 5.3L4 15"/><path d="M4 20v-5h5"/>',
  list: '<rect x="4" y="3" width="16" height="18" rx="3"/><path d="M8 8h8M8 12h8M8 16h5"/>',
  user: '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
  chat: '<path d="M4 5h16v11H9l-5 4z"/><path d="M8 9h8M8 12h5"/>',
};
// Lima tab: aktif = stroke ikon hijau Reloop, label gelap.
const NAV = [
  ['index.html', 'Beranda', 'home'],
  ['cari.html', 'Cari', 'search'],
  ['aktivitas.html', 'Aktivitas', 'list'],
  ['pesan.html', 'Pesan', 'chat'],
  ['profil.html', 'Akun', 'user'],
];
const nav = document.getElementById('bottom-nav');
if (nav) {
  const active = nav.dataset.active;
  nav.className = 'fixed bottom-0 left-1/2 -translate-x-1/2 w-full max-w-[420px] z-30 bg-white border-t border-slate-200 px-2 pt-2 pb-[max(12px,env(safe-area-inset-bottom))]';
  nav.setAttribute('aria-label', 'Navigasi utama');
  nav.innerHTML = '<ul class="grid grid-cols-5">' + NAV.map(([href, label, icon]) => {
    const on = icon === active;
    return `<li><a href="${href}" ${on ? 'aria-current="page"' : ''} class="flex flex-col items-center gap-1 text-[11px] leading-none ${on ? 'font-semibold text-ink' : 'font-medium text-slate-400'}"><span class="grid h-8 w-14 place-items-center"><svg class="size-[22px]" viewBox="0 0 24 24" fill="none" stroke="${on ? '#9be600' : 'currentColor'}" stroke-width="${on ? 2 : 1.8}" stroke-linecap="round" stroke-linejoin="round">${NAV_ICONS[icon]}</svg></span>${label}</a></li>`;
  }).join('') + '</ul>';
}

const rupiah = (n) => 'Rp ' + Math.round(n).toLocaleString('id-ID');

/* ===== Interaksi mockup lewat atribut data-* (di Laravel diganti Alpine/Livewire) =====
  data-seg="nama" data-on="kelas aktif" data-off="kelas pasif"   grup tab/chip; anak <button value="x"> (nama huruf kecil satu kata, jangan bentrok dengan atribut lain di sini).
      Elemen [data-nama="x y"] tampil bila nilai aktif ada di daftarnya (beberapa grup digabung AND). [data-group] sembunyi bila isinya habis. Nilai awal: ?nama=x, tombol aria-selected/aria-pressed="true", atau tombol pertama.
      data-optional: ketuk tombol aktif = batal pilih (semua tampil). [data-nama-empty] tampil bila tak ada item grup itu yang terlihat.
  data-toggle (+ data-on/data-off di tombol)   tombol on/off (favorit, chip multi).
  data-open="idDialog" | data-close            buka/tutup <dialog>. Ketuk backdrop juga menutup.
  data-toast="Teks"                            munculkan toast saat diklik.
  data-copy="teks"                             salin ke clipboard + toast.
  data-hide="selektor" | data-show="selektor"  sembunyikan/tampilkan elemen lain.
  data-remove="selektor-induk"                 hapus induk terdekat (mis. "li").
  form[data-done="idDialog|idElemen"] / form[data-go="url"] / form[action]
      validasi bawaan, tombol submit "Memproses…", lalu buka sukses / pindah halaman.
  ?state=ok|loading|empty|error  →  [data-state="…"] tampil sesuai state (bawaan "ok"). ?state=loading di halaman tanpa [data-state=loading] sendiri: skeleton generik 1,2 dtk.
      Isi kosong otomatis: data-skeleton="list|grid", data-blank="Judul" data-desc data-cta data-href (empty/error).
      [data-state-go="loading"] → loading lalu ok setelah 1,2 dtk (tombol "Coba lagi").
  data-flow="n" di <body>/<main> + ?step=n  →  [data-at="1 2"] tampil di langkah itu; [data-tl="n"] dapat data-s="done|now|todo";
      [data-step="n"] pindah langkah. Timeline pakai varian Tailwind data-[s=done]: / group-data-[s=now]:.
  data-countdown="detik" [data-end="state"]    hitung mundur jj:mm:dd; habis → setState(data-end). ?cd=n memendekkan semua hitung mundur (uji).
*/
const $$ = (sel, root = document) => [...root.querySelectorAll(sel)];
const params = new URLSearchParams(location.search);
const swap = (el, on, g = el) => {
  (el.dataset.on || g.dataset.on || '').split(' ').filter(Boolean).forEach((c) => el.classList.toggle(c, on));
  (el.dataset.off || g.dataset.off || '').split(' ').filter(Boolean).forEach((c) => el.classList.toggle(c, !on));
};

function toast(msg) {
  let t = document.getElementById('toast');
  if (!t) {
    t = document.createElement('div');
    t.id = 'toast';
    t.setAttribute('role', 'status');
    t.className = 'pointer-events-none fixed bottom-28 left-1/2 z-50 max-w-[340px] -translate-x-1/2 rounded-full bg-ink px-4 py-2.5 text-center text-sm font-medium text-white opacity-0 transition-opacity';
    document.body.append(t);
  }
  t.textContent = msg;
  t.style.opacity = 1;
  clearTimeout(t._h);
  t._h = setTimeout(() => (t.style.opacity = 0), 2200);
}

// Tab/chip: semua grup digabung, item tampil bila lolos semua grup yang ia punya.
const segs = {};
const seen = (el) => el.getClientRects().length > 0;
function applySegs() {
  const names = Object.keys(segs);
  const pass = (el) => names.every((n) => !segs[n] || el.dataset[n] === undefined || el.dataset[n].split(' ').includes(segs[n]));
  $$(names.map((n) => `[data-${n}]`).join(',')).forEach((el) => (el.hidden = !pass(el)));
  // Pembungkus [data-group] (mis. judul tanggal + daftar) ikut sembunyi bila isinya habis.
  $$('[data-group]').forEach((g) => (g.hidden = !pass(g) || !$$(names.map((n) => `[data-${n}]`).join(','), g).some((i) => !i.hidden)));
  names.forEach((n) => $$(`[data-${n}-empty]`).forEach((el) => (el.hidden = $$(`[data-${n}]`).some(seen))));
}
$$('[data-seg]').forEach((g) => {
  const name = g.dataset.seg;
  const btns = $$('button[value]', g);
  const set = (v) => {
    segs[name] = v;
    btns.forEach((b) => {
      const on = b.value === v;
      b.setAttribute(b.getAttribute('role') === 'tab' ? 'aria-selected' : 'aria-pressed', on);
      swap(b, on, g);
    });
    applySegs();
  };
  g.addEventListener('click', (e) => {
    const b = e.target.closest('button[value]');
    if (!b) return;
    e.preventDefault(); // tombol di dalam form jangan submit
    const on = b.getAttribute('aria-selected') === 'true' || b.getAttribute('aria-pressed') === 'true';
    set(on && 'optional' in g.dataset ? '' : b.value);
  });
  const pre = btns.find((b) => b.getAttribute('aria-selected') === 'true' || b.getAttribute('aria-pressed') === 'true');
  set(params.get(name) ?? (pre ? pre.value : 'optional' in g.dataset ? '' : btns[0]?.value));
});

// Halaman: state & langkah
function setState(s) {
  $$('[data-state]').forEach((el) => (el.hidden = !el.dataset.state.split(' ').includes(s)));
  if (s === 'loading' && setState.auto) setTimeout(() => setState('ok'), 1200);
}
$$('[data-skeleton]').forEach((el) => {
  const row = '<div class="flex gap-3 rounded-2xl border border-slate-200 p-4"><div class="size-12 shrink-0 animate-pulse rounded-lg bg-slate-200"></div><div class="flex-1 space-y-2 py-1"><div class="h-3 w-3/4 animate-pulse rounded bg-slate-200"></div><div class="h-3 w-1/2 animate-pulse rounded bg-slate-100"></div></div></div>';
  const card = '<div><div class="aspect-square animate-pulse rounded-2xl bg-slate-200"></div><div class="mt-2 h-3 w-3/4 animate-pulse rounded bg-slate-200"></div><div class="mt-1.5 h-3 w-1/2 animate-pulse rounded bg-slate-100"></div></div>';
  const grid = el.dataset.skeleton === 'grid';
  el.className += grid ? ' grid grid-cols-2 gap-3 px-4' : ' space-y-3 px-4';
  el.setAttribute('aria-label', 'Memuat');
  el.innerHTML = (grid ? card : row).repeat(grid ? 4 : 3);
});
$$('[data-blank]').forEach((el) => {
  const err = el.dataset.state === 'error';
  el.className += ' px-8 py-12 text-center';
  el.innerHTML = `<div class="ph mx-auto size-28 rounded-3xl"></div>
    <p class="mt-4 text-lg font-bold">${el.dataset.blank}</p>
    ${el.dataset.desc ? `<p class="mt-1 text-sm text-slate-500">${el.dataset.desc}</p>` : ''}
    ${err ? '<button data-state-go="loading" class="mt-5 rounded-full bg-ink px-6 py-3 text-sm font-semibold text-white">Coba lagi</button>'
      : el.dataset.cta ? `<a href="${el.dataset.href || '#'}" class="mt-5 inline-block rounded-full bg-ink px-6 py-3 text-sm font-semibold text-white">${el.dataset.cta}</a>` : ''}`;
});
if (document.querySelector('[data-state]')) setState(params.get('state') || 'ok');
setState.auto = true;

// Loading generik: halaman tanpa [data-state~=loading] sendiri dapat skeleton sementara lewat ?state=loading.
if (params.get('state') === 'loading' && !document.querySelector('[data-state~="loading"]')) {
  const isi = document.querySelector('main, form.space-y-5');
  if (isi) {
    const bar = '<div class="h-3 animate-pulse rounded bg-slate-200"></div>';
    const sk = document.createElement('div');
    sk.className = 'space-y-4 px-4 pt-4';
    sk.setAttribute('aria-label', 'Memuat');
    sk.innerHTML = '<div class="h-28 animate-pulse rounded-2xl bg-slate-200"></div>' + [3, 2, 3].map((n) => `<div class="space-y-2 rounded-2xl border border-slate-200 p-4"><div class="h-4 w-1/3 animate-pulse rounded bg-slate-200"></div>${bar.repeat(n)}</div>`).join('');
    const bars = $$('.fixed.bottom-0:not(#bottom-nav)');
    isi.before(sk);
    [isi, ...bars].forEach((el) => (el.hidden = true));
    setTimeout(() => { sk.remove(); [isi, ...bars].forEach((el) => (el.hidden = false)); }, 1200);
  }
}

const flow = document.querySelector('[data-flow]');
function setStep(n) {
  n = Number(n);
  $$('[data-at]').forEach((el) => (el.hidden = !el.dataset.at.split(' ').map(Number).includes(n)));
  $$('[data-tl]').forEach((el) => (el.dataset.s = el.dataset.tl < n ? 'done' : el.dataset.tl == n ? 'now' : 'todo'));
  if (flow) {
    const u = new URL(location);
    u.searchParams.set('step', n);
    history.replaceState(null, '', u);
  }
}
if (flow) setStep(params.get('step') || flow.dataset.flow);

$$('[data-countdown]').forEach((el) => {
  let s = Number(params.get('cd') || el.dataset.countdown);
  const tick = () => {
    const p = (x) => String(x).padStart(2, '0');
    el.textContent = `${p(Math.floor(s / 3600))}:${p(Math.floor((s % 3600) / 60))}:${p(s % 60)}`;
    if (s-- > 0) setTimeout(tick, 1000);
    else if (el.dataset.end) setState(el.dataset.end);
  };
  tick();
});

// Klik: satu delegasi untuk semua aksi
document.addEventListener('click', (e) => {
  const d = e.target.closest('[data-open],[data-close],[data-toast],[data-copy],[data-hide],[data-show],[data-remove],[data-toggle],[data-state-go],[data-step]');
  if (e.target.tagName === 'DIALOG') e.target.close(); // ketuk backdrop
  if (!d) return;
  const ds = d.dataset;
  if ('toggle' in ds) {
    const on = d.getAttribute('aria-pressed') !== 'true';
    d.setAttribute('aria-pressed', on);
    swap(d, on);
  }
  if (ds.open) document.getElementById(ds.open)?.showModal();
  if ('close' in ds) d.closest('dialog')?.close();
  if (ds.hide) $$(ds.hide).forEach((el) => (el.hidden = true));
  if (ds.show) $$(ds.show).forEach((el) => (el.hidden = false));
  if (ds.stateGo) setState(ds.stateGo);
  if (ds.step) setStep(ds.step);
  if (ds.copy) navigator.clipboard?.writeText(ds.copy).catch(() => {});
  if (ds.toast || ds.copy) toast(ds.toast || 'Disalin');
  if (ds.remove) {
    const item = d.closest(ds.remove);
    const list = item?.parentElement;
    item?.remove();
    applySegs();
    if (list && !list.children.length) list.dispatchEvent(new CustomEvent('emptied', { bubbles: true }));
  }
});
// Daftar yang jadi kosong setelah hapus → tampilkan [data-empty-of] milik daftar itu
document.addEventListener('emptied', (e) => {
  if (e.target.id) $$(`[data-empty-of="${e.target.id}"]`).forEach((el) => (el.hidden = false));
});

// Form: validasi bawaan, tombol memproses, lalu sukses/pindah
$$('form[data-done],form[data-go],form[action]').forEach((f) => {
  f.addEventListener('submit', (e) => {
    e.preventDefault();
    if (!f.reportValidity()) return;
    const btn = f.querySelector('[type=submit],button:not([type=button])') || document.querySelector(`[form="${f.id}"]`);
    const label = btn?.innerHTML;
    if (btn) { btn.disabled = true; btn.innerHTML = 'Memproses…'; btn.classList.add('opacity-70'); }
    setTimeout(() => {
      if (btn) { btn.disabled = false; btn.innerHTML = label; btn.classList.remove('opacity-70'); }
      const done = f.dataset.done && document.getElementById(f.dataset.done);
      if (done) done.tagName === 'DIALOG' ? done.showModal() : (done.hidden = false);
      else location.href = f.dataset.go || f.getAttribute('action');
    }, 700);
  });
});

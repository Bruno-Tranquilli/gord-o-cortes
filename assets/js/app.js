const state = {
  user: null,
  cuts: [],
  appointments: [],
  authMode: 'login',
};

const $ = (id) => document.getElementById(id);

function currency(value) {
  return Number(value).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
}

async function api(action, method = 'GET', data = null) {
  const options = { method, headers: { 'Content-Type': 'application/json' } };
  if (data) options.body = JSON.stringify(data);

  const res = await fetch(`api.php?action=${action}`, options);
  const json = await res.json();
  if (!json.ok) throw new Error(json.message || 'Erro inesperado');
  return json;
}

function toast(message) {
  alert(message);
}

function makeTimes() {
  const times = [];
  for (let h = 8; h <= 20; h++) {
    ['00', '30'].forEach((m) => {
      if (h === 20 && m === '30') return;
      times.push(`${String(h).padStart(2, '0')}:${m}`);
    });
  }
  return times;
}

function renderCuts() {
  const container = $('cuts-grid');
  const cuts = state.cuts.filter((c) => Number(c.active) === 1);

  if (!cuts.length) {
    container.innerHTML = '<div class="panel">Nenhum corte cadastrado ainda.</div>';
    return;
  }

  container.innerHTML = cuts
    .map((cut) => {
      const image = cut.image_url?.trim()
        ? cut.image_url
        : 'https://images.unsplash.com/photo-1585747860715-ebba7e41a43b?auto=format&fit=crop&w=800&q=80';
      return `
        <article class="cut-card">
          <img class="cut-image" src="${image}" alt="${cut.name}" />
          <div class="cut-content">
            <h4>${cut.name}</h4>
            <p>${cut.description || 'Sem descrição.'}</p>
            <strong>${currency(cut.price)} • ${cut.duration_minutes} min</strong>
          </div>
        </article>
      `;
    })
    .join('');
}

function renderBookingForm() {
  const logged = !!state.user;
  $('booking-form').classList.toggle('hidden', !logged);

  if (!logged) {
    $('booking-message').textContent = 'Faça login para agendar seu horário.';
  } else if (state.user.is_admin) {
    $('booking-message').innerHTML = 'Você está logado como admin. Use a <a href="admin.php">Área Admin</a> para gerenciar.';
    $('booking-form').classList.add('hidden');
  } else {
    $('booking-message').textContent = `Logado como ${state.user.name}. Escolha seu corte e horário.`;
  }

  const cutSelect = $('booking-cut');
  cutSelect.innerHTML = state.cuts
    .filter((c) => Number(c.active) === 1)
    .map((c) => `<option value="${c.id}">${c.name} - ${currency(c.price)}</option>`)
    .join('');

  const timeSelect = $('booking-time');
  timeSelect.innerHTML = makeTimes().map((t) => `<option value="${t}">${t}</option>`).join('');

  const today = new Date().toISOString().slice(0, 10);
  $('booking-date').min = today;
  $('booking-date').value = today;
}

function renderAppointments() {
  const container = $('appointments');

  if (!state.user) {
    container.innerHTML = '<p>Você precisa estar logado.</p>';
    return;
  }

  if (state.user.is_admin) {
    container.innerHTML = '<p>Conta admin ativa. Acesse a <a href="admin.php">Área Admin</a>.</p>';
    return;
  }

  if (!state.appointments.length) {
    container.innerHTML = '<p>Nenhum agendamento encontrado.</p>';
    return;
  }

  container.innerHTML = state.appointments
    .map((a) => `
      <div class="list-item">
        <strong>${a.haircut_name}</strong><br />
        ${a.appointment_date} às ${a.appointment_time}<br />
        Status: <b>${a.status}</b>
      </div>
    `)
    .join('');
}

function setAuthMode(mode) {
  state.authMode = mode === 'register' ? 'register' : 'login';
  const isRegister = state.authMode === 'register';

  $('auth-title').textContent = isRegister ? 'Cadastrar' : 'Entrar';
  $('auth-name-wrap').classList.toggle('hidden', !isRegister);
  $('auth-name').required = isRegister;
  $('auth-submit').textContent = isRegister ? 'Cadastrar' : 'Entrar';

  $('mode-login').classList.toggle('btn-primary', !isRegister);
  $('mode-login').classList.toggle('btn-outline', isRegister);
  $('mode-register').classList.toggle('btn-primary', isRegister);
  $('mode-register').classList.toggle('btn-outline', !isRegister);
}

async function loadSession() {
  const res = await api('state');
  state.user = res.user ? { ...res.user, is_admin: !!res.user.is_admin } : null;

  $('auth-open').classList.toggle('hidden', !!state.user);
  $('logout-btn').classList.toggle('hidden', !state.user);
}

async function loadCuts() {
  const res = await api('cuts');
  state.cuts = res.cuts || [];
  renderCuts();
  renderBookingForm();
}

async function loadAppointments() {
  if (!state.user || state.user.is_admin) {
    state.appointments = [];
    renderAppointments();
    return;
  }

  const res = await api('appointments');
  state.appointments = res.appointments || [];
  renderAppointments();
}

async function reloadAll() {
  await loadSession();
  await loadCuts();
  await loadAppointments();
}

function bindEvents() {
  $('auth-open').addEventListener('click', () => {
    setAuthMode('login');
    $('auth-modal').classList.remove('hidden');
  });

  $('auth-close').addEventListener('click', () => {
    $('auth-modal').classList.add('hidden');
    $('auth-form').reset();
  });

  $('mode-login').addEventListener('click', () => setAuthMode('login'));
  $('mode-register').addEventListener('click', () => setAuthMode('register'));

  $('auth-form').addEventListener('submit', async (ev) => {
    ev.preventDefault();
    try {
      if (state.authMode === 'register') {
        await api('register', 'POST', {
          name: $('auth-name').value.trim(),
          email: $('auth-email').value.trim(),
          password: $('auth-password').value,
        });
      } else {
        await api('login', 'POST', {
          email: $('auth-email').value.trim(),
          password: $('auth-password').value,
        });
      }

      $('auth-modal').classList.add('hidden');
      $('auth-form').reset();
      await reloadAll();
      toast(state.authMode === 'register' ? 'Conta criada com sucesso.' : 'Login realizado com sucesso.');
    } catch (e) {
      toast(e.message);
    }
  });

  $('logout-btn').addEventListener('click', async () => {
    await api('logout', 'POST');
    state.user = null;
    await reloadAll();
  });

  $('booking-form').addEventListener('submit', async (ev) => {
    ev.preventDefault();
    try {
      await api('book', 'POST', {
        haircut_id: Number($('booking-cut').value),
        appointment_date: $('booking-date').value,
        appointment_time: $('booking-time').value,
        notes: $('booking-notes').value.trim(),
      });
      $('booking-notes').value = '';
      await loadAppointments();
      toast('Horário agendado.');
    } catch (e) {
      toast(e.message);
    }
  });
}

(async function init() {
  bindEvents();
  setAuthMode('login');
  try {
    await reloadAll();
  } catch (e) {
    toast(e.message);
  }
})();

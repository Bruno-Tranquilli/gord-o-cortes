const state = {
  user: null,
  cuts: [],
  users: [],
  appointments: [],
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

function showGuard(html) {
  $('admin-guard').innerHTML = html;
  $('admin-guard').classList.remove('hidden');
  $('admin-content').classList.add('hidden');
}

function showAdminContent() {
  $('admin-guard').classList.add('hidden');
  $('admin-content').classList.remove('hidden');
}

function fillCutForm(cut) {
  $('cut-id').value = cut.id;
  $('cut-name').value = cut.name;
  $('cut-description').value = cut.description || '';
  $('cut-duration').value = cut.duration_minutes;
  $('cut-price').value = cut.price;
  $('cut-image').value = cut.image_url || '';
  $('cut-active').checked = Number(cut.active) === 1;
}

function resetCutForm() {
  $('cut-form').reset();
  $('cut-id').value = '';
  $('cut-duration').value = '30';
  $('cut-active').checked = true;
}

function renderAdminCuts() {
  const container = $('admin-cuts');
  if (!state.cuts.length) {
    container.innerHTML = '<p>Nenhum corte cadastrado.</p>';
    return;
  }

  container.innerHTML = state.cuts
    .map(
      (cut) => `
      <div class="list-item">
        <strong>${cut.name}</strong> - ${currency(cut.price)}<br />
        ${cut.duration_minutes} min | ${Number(cut.active) ? 'Ativo' : 'Inativo'}
        <div class="list-actions">
          <button class="btn btn-outline" data-edit-cut="${cut.id}">Editar</button>
          <button class="btn btn-danger" data-delete-cut="${cut.id}">Excluir</button>
        </div>
      </div>`
    )
    .join('');
}

function renderAdminUsers() {
  const container = $('admin-users');
  if (!state.users.length) {
    container.innerHTML = '<p>Sem usuários.</p>';
    return;
  }

  container.innerHTML = state.users
    .map(
      (u) => `
      <div class="list-item">
        <strong>${u.name}</strong><br />${u.email}<br />
        Perfil: ${u.role} | ${Number(u.active) ? 'Ativo' : 'Inativo'}
        <div class="list-actions">
          <button class="btn btn-outline" data-toggle-role="${u.id}">Trocar Perfil</button>
          <button class="btn btn-outline" data-toggle-active="${u.id}">Ativar/Inativar</button>
        </div>
      </div>`
    )
    .join('');
}

function renderAdminAppointments() {
  const container = $('admin-appointments');
  if (!state.appointments.length) {
    container.innerHTML = '<p>Sem agendamentos.</p>';
    return;
  }

  container.innerHTML = state.appointments
    .map(
      (a) => `
      <div class="list-item">
        <strong>${a.user_name || ''} ${a.email ? `(${a.email})` : ''}</strong><br />
        ${a.haircut_name} - ${a.appointment_date} ${a.appointment_time}<br />
        Status: <b>${a.status}</b>
        <div class="list-actions">
          <button class="btn btn-outline" data-status="${a.id}" data-value="confirmed">Confirmar</button>
          <button class="btn btn-outline" data-status="${a.id}" data-value="done">Concluir</button>
          <button class="btn btn-danger" data-status="${a.id}" data-value="cancelled">Cancelar</button>
        </div>
      </div>`
    )
    .join('');
}

async function loadSession() {
  const res = await api('state');
  state.user = res.user ? { ...res.user, is_admin: !!res.user.is_admin } : null;

  if (!state.user) {
    showGuard('<h3>Acesso restrito</h3><p>Faça login no site principal para acessar o painel admin.</p><a class="btn btn-primary" href="index.php">Ir para login</a>');
    return false;
  }

  if (!state.user.is_admin) {
    showGuard('<h3>Acesso negado</h3><p>Sua conta não é administradora.</p><a class="btn btn-primary" href="index.php">Voltar ao cardápio</a>');
    return false;
  }

  showAdminContent();
  return true;
}

async function loadCuts() {
  const res = await api('cuts');
  state.cuts = res.cuts || [];
  renderAdminCuts();
}

async function loadUsers() {
  const res = await api('admin-users');
  state.users = res.users || [];
  renderAdminUsers();
}

async function loadAppointments() {
  const res = await api('appointments');
  state.appointments = res.appointments || [];
  renderAdminAppointments();
}

async function reloadAll() {
  const canAccess = await loadSession();
  if (!canAccess) return;

  await loadCuts();
  await loadUsers();
  await loadAppointments();
}

function bindEvents() {
  $('logout-btn').addEventListener('click', async () => {
    await api('logout', 'POST');
    window.location.href = 'index.php';
  });

  $('cut-form').addEventListener('submit', async (ev) => {
    ev.preventDefault();
    try {
      await api('admin-cut-save', 'POST', {
        id: Number($('cut-id').value || 0),
        name: $('cut-name').value.trim(),
        description: $('cut-description').value.trim(),
        duration_minutes: Number($('cut-duration').value),
        price: Number($('cut-price').value),
        image_url: $('cut-image').value.trim(),
        active: $('cut-active').checked,
      });
      resetCutForm();
      await loadCuts();
      toast('Corte salvo.');
    } catch (e) {
      toast(e.message);
    }
  });

  document.body.addEventListener('click', async (ev) => {
    const target = ev.target;

    if (target.matches('[data-edit-cut]')) {
      const id = Number(target.dataset.editCut);
      const cut = state.cuts.find((c) => Number(c.id) === id);
      if (cut) fillCutForm(cut);
      return;
    }

    if (target.matches('[data-delete-cut]')) {
      const id = Number(target.dataset.deleteCut);
      if (!confirm('Excluir este corte?')) return;
      try {
        await api('admin-cut-delete', 'POST', { id });
        await loadCuts();
      } catch (e) {
        toast(e.message);
      }
      return;
    }

    if (target.matches('[data-toggle-role]')) {
      const id = Number(target.dataset.toggleRole);
      const user = state.users.find((u) => Number(u.id) === id);
      if (!user) return;

      const newRole = user.role === 'admin' ? 'client' : 'admin';
      try {
        await api('admin-user-save', 'POST', {
          id,
          role: newRole,
          active: Number(user.active) === 1,
        });
        await loadUsers();
      } catch (e) {
        toast(e.message);
      }
      return;
    }

    if (target.matches('[data-toggle-active]')) {
      const id = Number(target.dataset.toggleActive);
      const user = state.users.find((u) => Number(u.id) === id);
      if (!user) return;

      try {
        await api('admin-user-save', 'POST', {
          id,
          role: user.role,
          active: !(Number(user.active) === 1),
        });
        await loadUsers();
      } catch (e) {
        toast(e.message);
      }
      return;
    }

    if (target.matches('[data-status]')) {
      const id = Number(target.dataset.status);
      const status = target.dataset.value;
      try {
        await api('admin-appointment-status', 'POST', { id, status });
        await loadAppointments();
      } catch (e) {
        toast(e.message);
      }
    }
  });
}

(async function init() {
  bindEvents();
  try {
    await reloadAll();
  } catch (e) {
    toast(e.message);
  }
})();

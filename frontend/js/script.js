tailwind.config = {
    theme: {
        extend: {
            colors: {
                brand: {
                    blue: '#1771BF',
                    green: '#09E51D',
                    yellow: '#FAC51F',
                    red: '#F21D1D',
                    grayLight: '#FAFAFA',
                    grayBg: '#F2F2F2',
                    graySecondary: '#F2EAE2'
                }
            },
            fontFamily: {
                sans: ['Roboto', 'sans-serif'],
                title: ['Montserrat', 'sans-serif']
            }
        }
    }
};

// =====================================================================
//  CONFIGURAÇÃO DA API
//  A URL base pode ser sobrescrita definindo window.SGA_API_BASE antes
//  deste script (ex.: em ambientes de produção).
// =====================================================================
const API_BASE = (window.SGA_API_BASE || 'http://localhost:8000/api').replace(/\/$/, '');
const TOKEN_KEY = 'SGA_token';

// ESTADOS DE SESSÃO ATIVA
let usuarioAtivo = null;
let secaoAtiva = 'admin-dashboard';
// Cache em memória da sessão (preenchido pela API)
let cacheAlunos = [];     // perfil completo quando disponível
let cacheOcorrencias = [];

// ---------------------------------------------------------------------
//  CAMADA HTTP
// ---------------------------------------------------------------------
function getToken() {
    return localStorage.getItem(TOKEN_KEY);
}

async function apiFetch(path, options = {}) {
    const headers = Object.assign(
        { 'Accept': 'application/json' },
        options.headers || {}
    );
    const token = getToken();
    if (token) headers['Authorization'] = 'Bearer ' + token;

    // Só define Content-Type JSON quando o corpo não for FormData
    if (options.body && !(options.body instanceof FormData) && !headers['Content-Type']) {
        headers['Content-Type'] = 'application/json';
    }

    let resp;
    try {
        resp = await fetch(API_BASE + path, Object.assign({}, options, { headers }));
    } catch (e) {
        throw new ApiError('Falha de conexão com o servidor. Verifique se a API está no ar.', 0);
    }

    if (resp.status === 401 && !path.endsWith('/login')) {
        // Token expirado/invalidado -> volta ao login
        localStorage.removeItem(TOKEN_KEY);
        realizarLogout();
        throw new ApiError('Sessão expirada. Faça login novamente.', 401);
    }

    let data = null;
    const text = await resp.text();
    if (text) {
        try { data = JSON.parse(text); } catch (_) { data = { message: text }; }
    }

    if (!resp.ok) {
        const msg = (data && data.message) ? data.message : 'Erro na requisição (' + resp.status + ').';
        throw new ApiError(msg, resp.status, data);
    }
    return data;
}

class ApiError extends Error {
    constructor(message, status, data) {
        super(message);
        this.status = status;
        this.data = data;
    }
}

// ---------------------------------------------------------------------
//  AUTENTICAÇÃO (UC01)
// ---------------------------------------------------------------------
async function realizarLogin(event) {
    event.preventDefault();
    const email = document.getElementById('login-email').value;
    const senha = document.getElementById('login-senha').value;
    const erroDiv = document.getElementById('login-erro');
    erroDiv.classList.add('hidden');

    try {
        const resp = await apiFetch('/login', {
            method: 'POST',
            body: JSON.stringify({ login: email, password: senha })
        });

        localStorage.setItem(TOKEN_KEY, resp.access_token);
        aplicarUsuario(resp.user);
        const destino = usuarioAtivo.perfil === 'Admin' ? 'admin-dashboard' : 'monitora-dashboard';
        await loginSucesso(destino);
    } catch (e) {
        erroDiv.querySelector ? null : null;
        erroDiv.textContent = '';
        const icone = document.createElement('i');
        icone.className = 'fa-solid fa-triangle-exclamation mr-1';
        erroDiv.appendChild(icone);
        erroDiv.appendChild(document.createTextNode(' ' + (e.message || 'Credenciais inválidas.')));
        erroDiv.classList.remove('hidden');
    }
}

function aplicarUsuario(user) {
    const perfil = user.perfil === 'admin' ? 'Admin' : 'Monitora';
    const detalhes = user.detalhes || {};
    const nome = user.login;
    usuarioAtivo = {
        id: user.id,
        login: user.login,
        nome: nome,
        cargo: perfil === 'Admin'
            ? ('Administrador' + (detalhes.setor ? ' · ' + detalhes.setor : ''))
            : ('Monitora' + (detalhes.classes ? ' · ' + detalhes.classes : '')),
        perfil: perfil,
        avatar: (nome || 'U').substring(0, 2).toUpperCase()
    };
}

function alternarVisibilidadeSenha(botao) {
    const input = document.getElementById('login-senha');
    const icone = botao.querySelector('i');
    const visivel = input.type === 'text';
    input.type = visivel ? 'password' : 'text';
    icone.classList.toggle('fa-eye', visivel);
    icone.classList.toggle('fa-eye-slash', !visivel);
}

async function loginSucesso(destino) {
    document.getElementById('tela-login').classList.add('hidden');
    document.getElementById('sistema-dashboard').classList.remove('hidden');
    document.getElementById('login-erro').classList.add('hidden');

    document.getElementById('nome-usuario-sidebar').innerText = usuarioAtivo.nome;
    document.getElementById('cargo-usuario-sidebar').innerText = usuarioAtivo.cargo;
    document.getElementById('avatar-usuario').innerText = usuarioAtivo.avatar;

    const badge = document.getElementById('badge-perfil-sidebar');
    badge.innerText = usuarioAtivo.perfil;
    if (usuarioAtivo.perfil === 'Admin') {
        badge.className = "text-[9px] uppercase font-bold tracking-wider px-2 py-0.5 rounded-full bg-blue-100 text-brand-blue";
        document.getElementById('menu-admin').classList.remove('hidden');
        document.getElementById('menu-monitora').classList.add('hidden');
    } else {
        badge.className = "text-[9px] uppercase font-bold tracking-wider px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700";
        document.getElementById('menu-admin').classList.add('hidden');
        document.getElementById('menu-monitora').classList.remove('hidden');
    }

    await sincronizarDados();
    navegarPara(destino);
}

async function realizarLogout() {
    try {
        if (getToken()) await apiFetch('/logout', { method: 'POST' });
    } catch (_) { /* ignora erros de logout */ }
    localStorage.removeItem(TOKEN_KEY);
    usuarioAtivo = null;
    cacheAlunos = [];
    cacheOcorrencias = [];
    document.getElementById('tela-login').classList.remove('hidden');
    document.getElementById('sistema-dashboard').classList.add('hidden');
    const emailEl = document.getElementById('login-email');
    const senhaEl = document.getElementById('login-senha');
    if (emailEl) emailEl.value = '';
    if (senhaEl) senhaEl.value = '';
}

// ---------------------------------------------------------------------
//  NAVEGAÇÃO (SPA)
// ---------------------------------------------------------------------
function navegarPara(secaoId) {
    secaoAtiva = secaoId;
    const secoes = [
        'sec-admin-dashboard', 'sec-admin-cadastro', 'sec-admin-laudos', 'sec-admin-monitoras', 'sec-admin-ocorrencias',
        'sec-monitora-dashboard', 'sec-monitora-perfil', 'sec-monitora-ocorrencia', 'sec-monitora-historico'
    ];
    secoes.forEach(sec => {
        const el = document.getElementById(sec);
        if (!el) return;
        if (sec === `sec-${secaoId}`) el.classList.remove('hidden');
        else el.classList.add('hidden');
    });

    const todosBotoes = document.querySelectorAll('aside nav button');
    todosBotoes.forEach(btn => {
        btn.className = "w-full text-left px-4 py-2.5 rounded-lg text-sm font-medium flex items-center gap-3 text-gray-600 hover:bg-gray-50 hover:text-gray-900";
    });
    const activeBtn = document.getElementById(`btn-${secaoId}`);
    if (activeBtn && usuarioAtivo) {
        activeBtn.className = usuarioAtivo.perfil === 'Admin'
            ? "w-full text-left px-4 py-2.5 rounded-lg text-sm font-medium flex items-center gap-3 bg-blue-50 text-brand-blue"
            : "w-full text-left px-4 py-2.5 rounded-lg text-sm font-medium flex items-center gap-3 bg-emerald-50 text-emerald-700";
    }

    // Carregamentos sob demanda ao abrir cada seção
    if (secaoId === 'monitora-historico') renderizarHistoricoOcorrencias();
    if (secaoId === 'admin-monitoras') carregarMonitoras();
    if (secaoId === 'admin-ocorrencias') carregarOcorrenciasAdmin();
}

// ---------------------------------------------------------------------
//  SINCRONIZAÇÃO DE DADOS (via API)
// ---------------------------------------------------------------------
async function sincronizarDados() {
    try {
        const resp = await apiFetch('/alunos?per_page=100');
        const lista = resp.data || [];
        // Normaliza para o formato usado pelos renderizadores
        cacheAlunos = lista.map(mapearAlunoResumo);
    } catch (e) {
        cacheAlunos = [];
        mostrarAlertaErro('Nao foi possivel carregar os alunos: ' + e.message);
    }

    atualizarMetricas();
    renderizarTabelaAlunosAdmin();
    renderizarDropdownsAlunos();
    renderizarCardsAlunosMonitora();
    renderizarListaBuscaPerfilMonitora();
    carregarUsuariosPermissao();
}

function mapearAlunoResumo(a) {
    return {
        id: a.id,
        nome: a.nome,
        dataNascimento: a.data_nascimento,
        necessidadesEspeciais: a.necessidades_especiais,
        observacoesGerais: a.observacoes_gerais,
        // info operacional é carregada no detalhe (show)
        gatilho: null,
        acao: null,
        prioridade: null
    };
}

function atualizarMetricas() {
    const totalAlunos = cacheAlunos.length;
    setText('card-total-alunos', totalAlunos);
    // Ocorrências e laudos: somatório obtido via detalhe seria custoso aqui;
    // usamos o total conhecido do cache de histórico quando carregado.
    setText('card-total-ocorrencias', cacheOcorrencias.length || '—');
    const laudos = cacheAlunos.reduce((s, a) => s + (a._laudos || 0), 0);
    setText('card-total-laudos', laudos || '—');
}

function setText(id, value) {
    const el = document.getElementById(id);
    if (el) el.innerText = value;
}

// ---------------------------------------------------------------------
//  RENDERIZADORES
// ---------------------------------------------------------------------
function renderizarTabelaAlunosAdmin() {
    const tbody = document.getElementById('tabela-alunos-dashboard');
    if (!tbody) return;
    tbody.innerHTML = '';
    cacheAlunos.forEach(aluno => {
        const tr = document.createElement('tr');
        tr.className = "hover:bg-gray-50";
        tr.innerHTML = `
            <td class="p-3 font-bold text-gray-400">#${aluno.id}</td>
            <td class="p-3 font-semibold text-gray-800">${escapeHtml(aluno.nome)}</td>
            <td class="p-3 text-gray-500">${formatarData(aluno.dataNascimento)}</td>
            <td class="p-3 text-gray-600"><span class="bg-blue-50 text-brand-blue px-2 py-0.5 rounded text-[10px] font-medium">${escapeHtml(aluno.necessidadesEspeciais || '—')}</span></td>
            <td class="p-3"><span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-50 text-slate-600">Ver perfil</span></td>
        `;
        tbody.appendChild(tr);
    });
}

function renderizarDropdownsAlunos() {
    const laudosSelect = document.getElementById('laudo-aluno-id');
    const ocorrenciasSelect = document.getElementById('oc-aluno-id');
    const historicoSelect = document.getElementById('hist-filtro-aluno');

    if (laudosSelect) laudosSelect.innerHTML = '<option value="">Selecione um Aluno...</option>';
    if (ocorrenciasSelect) ocorrenciasSelect.innerHTML = '<option value="">Selecione um Aluno...</option>';
    if (historicoSelect) historicoSelect.innerHTML = '<option value="todos">Todos os Alunos</option>';

    cacheAlunos.forEach(aluno => {
        const option = `<option value="${aluno.id}">${escapeHtml(aluno.nome)} (ID: #${aluno.id})</option>`;
        if (laudosSelect) laudosSelect.innerHTML += option;
        if (ocorrenciasSelect) ocorrenciasSelect.innerHTML += option;
        if (historicoSelect) historicoSelect.innerHTML += option;
    });
}

function renderizarCardsAlunosMonitora() {
    const container = document.getElementById('cards-alunos-monitora');
    if (!container) return;
    container.innerHTML = '';
    if (cacheAlunos.length === 0) {
        container.innerHTML = '<p class="text-xs text-gray-400">Nenhum aluno designado.</p>';
        return;
    }
    cacheAlunos.forEach(aluno => {
        const card = document.createElement('div');
        card.className = "bg-white p-4 rounded-lg border border-gray-150 shadow-sm hover:shadow-md transition space-y-3 cursor-pointer";
        card.onclick = () => carregarPerfilAluno(aluno.id);
        card.innerHTML = `
            <div class="flex justify-between items-start">
                <h4 class="text-xs font-bold text-gray-800">${escapeHtml(aluno.nome)}</h4>
            </div>
            <div class="text-[11px] text-gray-500">
                <span class="block"><strong>Necessidade:</strong> ${escapeHtml(aluno.necessidadesEspeciais || '—')}</span>
            </div>
        `;
        container.appendChild(card);
    });
}

function renderizarListaBuscaPerfilMonitora() {
    const container = document.getElementById('lista-alunos-perfil-busca');
    if (!container) return;
    container.innerHTML = '';
    cacheAlunos.forEach(aluno => {
        const btn = document.createElement('button');
        btn.className = "w-full text-left p-3 rounded-lg text-xs font-medium border border-gray-100 hover:bg-gray-50 transition flex justify-between items-center bg-white";
        btn.onclick = () => carregarPerfilAluno(aluno.id);
        btn.innerHTML = `<span>${escapeHtml(aluno.nome)}</span><i class="fa-solid fa-chevron-right text-gray-300"></i>`;
        container.appendChild(btn);
    });
}

async function carregarPerfilAluno(id) {
    let aluno;
    try {
        const resp = await apiFetch('/alunos/' + id);
        aluno = resp.data;
    } catch (e) {
        mostrarAlertaErro('Erro ao carregar perfil: ' + e.message);
        return;
    }
    if (!aluno) return;

    document.getElementById('perfil-vazio').classList.add('hidden');
    document.getElementById('perfil-ativo').classList.remove('hidden');

    const nome = aluno.nome || '';
    document.getElementById('perfil-iniciais').innerText =
        nome.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
    document.getElementById('perfil-nome').innerText = nome;

    const idade = aluno.data_nascimento ? calcularIdade(aluno.data_nascimento) : '—';
    document.getElementById('perfil-dados-basicos').innerText =
        `ID: #${aluno.id} | Idade: ${idade} anos | ${aluno.necessidades_especiais || '—'}`;
    document.getElementById('perfil-necessidades-texto').innerText = aluno.necessidades_especiais || '—';
    document.getElementById('perfil-obs-texto').innerText = aluno.observacoes_gerais || 'Nenhuma observação cadastrada.';

    // Protocolo (info operacional) — primeiro registro
    const info = (aluno.info_operacionais && aluno.info_operacionais[0]) || null;
    document.getElementById('perfil-gatilho').innerText = info ? info.gatilho_comportamental : 'Nenhum protocolo vinculado';
    document.getElementById('perfil-acao').innerText = info ? info.acao_recomendada : '—';

    const prioridade = info ? (info.prioridade || 'media') : 'media';
    const priBadge = document.getElementById('perfil-prioridade-badge');
    priBadge.innerText = `Prioridade ${prioridade}`;
    if (String(prioridade).toLowerCase() === 'alta') {
        priBadge.className = "bg-brand-red text-white text-[9px] font-bold px-2.5 py-0.5 rounded uppercase";
    } else {
        priBadge.className = "bg-brand-yellow text-slate-800 text-[9px] font-bold px-2.5 py-0.5 rounded uppercase";
    }

    navegarPara('monitora-perfil');
}

async function renderizarHistoricoOcorrencias() {
    const container = document.getElementById('container-lista-historico');
    const filtroEl = document.getElementById('hist-filtro-aluno');
    if (!container || !filtroEl) return;
    const filtro = filtroEl.value;

    // Define quais alunos buscar
    const alvos = filtro === 'todos' ? cacheAlunos.map(a => a.id) : [filtro];

    container.innerHTML = '<div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 text-center text-gray-400 text-xs">Carregando ocorrências...</div>';

    let todas = [];
    try {
        for (const alunoId of alvos) {
            const resp = await apiFetch('/alunos/' + alunoId + '/ocorrencias');
            const nomeAluno = resp.aluno_nome || '';
            (resp.ocorrencias || []).forEach(o => {
                todas.push({
                    id: o.id,
                    alunoId: resp.aluno_id,
                    alunoNome: nomeAluno,
                    monitoraNome: o.monitora && o.monitora.user ? o.monitora.user.login : 'Administração',
                    dataHora: o.data_hora,
                    intervencao: o.tipo_intervencao,
                    descricao: o.descricao_fatos
                });
            });
        }
    } catch (e) {
        container.innerHTML = `<div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 text-center text-red-500 text-xs">Erro ao carregar: ${escapeHtml(e.message)}</div>`;
        return;
    }

    cacheOcorrencias = todas;
    atualizarMetricas();

    container.innerHTML = '';
    if (todas.length === 0) {
        container.innerHTML = `<div class="bg-white p-8 rounded-xl shadow-sm border border-gray-100 text-center text-gray-400">Nenhuma ocorrência registrada.</div>`;
        return;
    }

    todas.sort((a, b) => new Date(b.dataHora) - new Date(a.dataHora));
    todas.forEach(oc => {
        const card = document.createElement('div');
        card.className = "bg-white p-6 rounded-xl shadow-sm border border-gray-100 space-y-4 relative overflow-hidden";
        card.innerHTML = `
            <div class="flex justify-between items-start border-b border-gray-100 pb-3">
                <div>
                    <span class="text-xs font-bold text-gray-500 uppercase tracking-wider block">Aluno: <span class="text-brand-blue">${escapeHtml(oc.alunoNome)} (ID: #${oc.alunoId})</span></span>
                    <span class="text-[10px] text-gray-400 mt-1 block"><i class="fa-regular fa-clock"></i> Registrado em: ${formatarDataHora(oc.dataHora)}</span>
                </div>
                <span class="bg-emerald-50 text-emerald-700 text-[10px] font-bold px-2 py-1 rounded border border-emerald-150 flex items-center gap-1">
                    <i class="fa-solid fa-lock-open text-[9px]"></i> Decifrado pela API
                </span>
            </div>
            <div class="space-y-2 text-xs">
                <p class="text-gray-500"><strong>Intervenção imediata:</strong> <span class="text-gray-800 font-medium">${escapeHtml(oc.intervencao || '—')}</span></p>
                <p class="text-gray-700 leading-relaxed bg-slate-50 p-3 rounded border border-slate-100"><strong>Fatos relatados:</strong> ${escapeHtml(oc.descricao || '—')}</p>
            </div>
            <div class="text-[10px] text-gray-400 flex justify-end gap-1.5 items-center">
                <i class="fa-regular fa-user"></i> Autor do relato: <strong class="text-gray-600">${escapeHtml(oc.monitoraNome)}</strong>
            </div>
        `;
        container.appendChild(card);
    });
}

// ---------------------------------------------------------------------
//  AÇÕES DE ESCRITA (via API)
// ---------------------------------------------------------------------
async function cadastrarAlunoCompleto(event) {
    event.preventDefault();
    const payload = {
        nome: valueOf('cad-nome'),
        data_nascimento: valueOf('cad-nasc'),
        necessidades_especiais: valueOf('cad-necessidades'),
        observacoes_gerais: valueOf('cad-obs'),
        consentimento_lgpd_assinado: true,
        versao_termo_lgpd: '1.0'
    };

    const gatilho = valueOf('cad-gatilho');
    const acao = valueOf('cad-acao');
    const prioridadeRaw = (valueOf('cad-prioridade') || 'media').toLowerCase()
        .normalize('NFD').replace(/[\u0300-\u036f]/g, ''); // remove acentos
    const prioridade = ['alta', 'media', 'baixa'].includes(prioridadeRaw) ? prioridadeRaw : 'media';

    try {
        const resp = await apiFetch('/alunos', { method: 'POST', body: JSON.stringify(payload) });
        const alunoId = resp.data.id;

        // UC03: vincula protocolo operacional, se informado
        if (gatilho || acao) {
            await apiFetch('/alunos/' + alunoId + '/info-operacional', {
                method: 'POST',
                body: JSON.stringify({
                    gatilho_comportamental: gatilho,
                    acao_recomendada: acao,
                    prioridade: prioridade
                })
            });
        }

        event.target.reset();
        await sincronizarDados();
        mostrarAlertaSucesso(`Aluno #${alunoId} cadastrado com sucesso e persistido no PostgreSQL.`);
        navegarPara('admin-dashboard');
    } catch (e) {
        mostrarAlertaErro('Erro ao cadastrar: ' + e.message);
    }
}

async function registrarLaudo(event) {
    event.preventDefault();
    const alunoId = valueOf('laudo-aluno-id');
    const crm = valueOf('laudo-crm');
    const especialidade = valueOf('laudo-especialidade');
    const fileInput = document.getElementById('laudo-arquivo');

    if (!alunoId || !fileInput.files[0]) {
        mostrarAlertaErro('Selecione um aluno e um arquivo PDF.');
        return;
    }

    const form = new FormData();
    form.append('arquivo', fileInput.files[0]);
    form.append('crm_medico', crm);
    form.append('especialidade', especialidade);

    try {
        await apiFetch('/alunos/' + alunoId + '/laudos', { method: 'POST', body: form });
        event.target.reset();
        setText('pdf-upload-nome', 'Arraste ou clique para anexar arquivo PDF');
        const icon = document.getElementById('pdf-icon-upload');
        if (icon) icon.className = "fa-regular fa-file-pdf text-3xl text-gray-400 mb-2 block";
        mostrarAlertaSucesso('Laudo enviado e armazenado em storage privado com sucesso.');
    } catch (e) {
        mostrarAlertaErro('Erro ao enviar laudo: ' + e.message);
    }
}

async function registrarNovaOcorrencia(event) {
    event.preventDefault();
    const alunoId = valueOf('oc-aluno-id');
    const intervencao = valueOf('oc-intervencao');
    const fatos = valueOf('oc-descricao');
    if (!alunoId) { mostrarAlertaErro('Selecione um aluno.'); return; }

    const btn = document.getElementById('btn-salvar-ocorrencia');
    if (btn) btn.disabled = true;

    try {
        await apiFetch('/ocorrencias', {
            method: 'POST',
            body: JSON.stringify({
                aluno_id: alunoId,
                descricao_fatos: fatos,
                tipo_intervencao: intervencao
            })
        });
        event.target.reset();
        mostrarAlertaSucesso('Relato sensível cifrado (AES-256) e salvo com sucesso.');
        navegarPara('monitora-dashboard');
    } catch (e) {
        mostrarAlertaErro('Erro ao registrar ocorrência: ' + e.message);
    } finally {
        if (btn) btn.disabled = false;
    }
}

// UC07 - Permissões
async function carregarUsuariosPermissao() {
    const select = document.getElementById('perm-usuario');
    if (!select || usuarioAtivo.perfil !== 'Admin') return;
    try {
        const resp = await apiFetch('/usuarios');
        const usuarios = resp.data || resp || [];
        select.innerHTML = '<option value="">Selecione um usuário...</option>';
        (Array.isArray(usuarios) ? usuarios : (usuarios.data || [])).forEach(u => {
            const nivel = (u.administrador && u.administrador.nivel_acesso) || 1;
            select.innerHTML += `<option value="${u.id}" data-perfil="${u.perfil}" data-nivel="${nivel}">${escapeHtml(u.login)} (ID: #${u.id} - ${u.perfil})</option>`;
        });
    } catch (_) { /* lista de permissões é opcional na UI */ }
}

async function alterarPermissao(event) {
    event.preventDefault();
    const usuarioId = valueOf('perm-usuario');
    const nivel = valueOf('perm-nivel');
    if (!usuarioId) { mostrarAlertaErro('Selecione um usuário.'); return; }
    try {
        await apiFetch('/usuarios/' + usuarioId + '/permissoes', {
            method: 'PUT',
            body: JSON.stringify({ nivel_acesso: parseInt(nivel, 10) })
        });
        mostrarAlertaSucesso('Permissões atualizadas com sucesso.');
        event.target.reset();
    } catch (e) {
        mostrarAlertaErro('Erro ao atualizar permissões: ' + e.message);
    }
}

function carregarNivelAtual(select) {
    const selectedOpt = select.options[select.selectedIndex];
    if (selectedOpt && selectedOpt.value) {
        const nivel = selectedOpt.getAttribute('data-nivel');
        const nivelEl = document.getElementById('perm-nivel');
        if (nivelEl && nivel) nivelEl.value = nivel;
    }
}

// ---------------------------------------------------------------------
//  MODAL GENÉRICO (substitui prompt/confirm nativos)
// ---------------------------------------------------------------------
let _modalResolver = null;

/**
 * Exibe um modal. Retorna uma Promise que resolve com:
 *  - false, se cancelado;
 *  - true, se confirmado e não houver campos;
 *  - um objeto { nomeCampo: valor }, se houver campos.
 * opts: { titulo, texto, tipo: 'perigo'|'padrao', confirmarTexto, campos: [{name,label,type,value,required,minlength}] }
 */
function abrirModal(opts) {
    const overlay = document.getElementById('sga-modal-overlay');
    const camposWrap = document.getElementById('sga-modal-campos');
    const btnOk = document.getElementById('sga-modal-confirmar');
    const btnCancel = document.getElementById('sga-modal-cancelar');
    const iconeEl = document.getElementById('sga-modal-icone');

    setText('sga-modal-titulo', opts.titulo || 'Confirmar');
    const textoEl = document.getElementById('sga-modal-texto');
    textoEl.textContent = opts.texto || '';
    textoEl.classList.toggle('hidden', !opts.texto);

    // Ícone e cor conforme o tipo
    const perigo = opts.tipo === 'perigo';
    iconeEl.className = 'w-9 h-9 rounded-full flex items-center justify-center text-base shrink-0 ' +
        (perigo ? 'bg-red-50 text-brand-red' : 'bg-blue-50 text-brand-blue');
    iconeEl.innerHTML = `<i class="fa-solid ${perigo ? 'fa-triangle-exclamation' : 'fa-circle-question'}"></i>`;

    btnOk.textContent = opts.confirmarTexto || 'Confirmar';
    btnOk.className = 'px-5 py-2 rounded text-xs font-bold text-white font-title transition ' +
        (perigo ? 'bg-brand-red hover:opacity-90' : 'bg-brand-blue hover:opacity-90');

    // Monta os campos (se houver)
    camposWrap.innerHTML = '';
    (opts.campos || []).forEach((c, i) => {
        const wrap = document.createElement('div');
        const inputId = 'sga-modal-campo-' + i;
        const isArea = c.type === 'textarea';
        wrap.innerHTML = `
            <label class="block text-[11px] font-bold text-gray-500 uppercase mb-1" for="${inputId}">${escapeHtml(c.label || c.name)}</label>
            ${isArea
                ? `<textarea id="${inputId}" rows="3" class="w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded text-sm focus:outline-none focus:ring-1 focus:ring-brand-blue"></textarea>`
                : `<input id="${inputId}" type="${c.type || 'text'}" class="w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded text-sm focus:outline-none focus:ring-1 focus:ring-brand-blue">`}
        `;
        camposWrap.appendChild(wrap);
        const el = document.getElementById(inputId);
        el.value = c.value != null ? c.value : '';
        el.dataset.name = c.name;
        if (c.required) el.dataset.required = '1';
        if (c.minlength) el.dataset.minlength = String(c.minlength);
    });

    overlay.classList.remove('hidden');
    // Foco no primeiro campo (ou no botão confirmar)
    const primeiro = camposWrap.querySelector('input, textarea');
    setTimeout(() => (primeiro || btnOk).focus(), 50);

    return new Promise((resolve) => {
        _modalResolver = resolve;

        const fechar = (valor) => {
            overlay.classList.add('hidden');
            btnOk.onclick = null; btnCancel.onclick = null;
            overlay.onclick = null; document.onkeydown = null;
            _modalResolver = null;
            resolve(valor);
        };

        btnCancel.onclick = () => fechar(false);
        overlay.onclick = (e) => { if (e.target === overlay) fechar(false); };
        document.onkeydown = (e) => { if (e.key === 'Escape') fechar(false); };

        btnOk.onclick = () => {
            if (!opts.campos || opts.campos.length === 0) return fechar(true);
            // Coleta e valida os campos
            const resultado = {};
            const inputs = camposWrap.querySelectorAll('input, textarea');
            for (const el of inputs) {
                const v = el.value.trim();
                if (el.dataset.required && v === '') {
                    el.classList.add('border-brand-red');
                    el.focus();
                    return;
                }
                if (el.dataset.minlength && v.length < parseInt(el.dataset.minlength, 10)) {
                    el.classList.add('border-brand-red');
                    el.focus();
                    return;
                }
                resultado[el.dataset.name] = v;
            }
            fechar(resultado);
        };
    });
}

function modalConfirmar(titulo, texto, opts = {}) {
    return abrirModal({ titulo, texto, tipo: opts.tipo || 'perigo', confirmarTexto: opts.confirmarTexto || 'Confirmar' });
}

function modalFormulario(titulo, campos, opts = {}) {
    return abrirModal({ titulo, texto: opts.texto, tipo: 'padrao', confirmarTexto: opts.confirmarTexto || 'Salvar', campos });
}

// ---------------------------------------------------------------------
//  AUXILIARES
// ---------------------------------------------------------------------
function valueOf(id) {
    const el = document.getElementById(id);
    return el ? el.value : '';
}

function formatarData(dataStr) {
    if (!dataStr) return "-";
    const parts = String(dataStr).substring(0, 10).split('-');
    if (parts.length !== 3) return dataStr;
    return `${parts[2]}/${parts[1]}/${parts[0]}`;
}

function formatarDataHora(dataHoraStr) {
    const dt = new Date(dataHoraStr);
    if (isNaN(dt)) return dataHoraStr || '-';
    return dt.toLocaleString('pt-BR');
}

function calcularIdade(dataNasc) {
    const hoje = new Date();
    const nasc = new Date(dataNasc);
    let idade = hoje.getFullYear() - nasc.getFullYear();
    const m = hoje.getMonth() - nasc.getMonth();
    if (m < 0 || (m === 0 && hoje.getDate() < nasc.getDate())) idade--;
    return idade;
}

function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function mostrarArquivoSelecionado(input) {
    const nameSpan = document.getElementById('pdf-upload-nome');
    const icon = document.getElementById('pdf-icon-upload');
    if (input.files && input.files[0]) {
        nameSpan.innerText = `Anexado: ${input.files[0].name} (${(input.files[0].size / 1024).toFixed(1)} KB)`;
        icon.className = "fa-solid fa-file-pdf text-3xl text-emerald-500 mb-2 block";
    }
}

function mostrarAlertaSucesso(texto) {
    const alert = document.getElementById('alerta-sucesso');
    if (!alert) return;
    alert.className = alert.className.replace(/bg-\S+|border-\S+|text-\S+/g, '');
    alert.classList.add('bg-emerald-50', 'border-l-4', 'border-brand-green', 'text-emerald-800',
        'p-4', 'rounded-r', 'shadow-md', 'mb-6', 'flex', 'justify-between', 'items-center');
    setText('alerta-sucesso-texto', texto);
    alert.classList.remove('hidden');
    setTimeout(fecharAlerta, 6000);
}

function mostrarAlertaErro(texto) {
    const alert = document.getElementById('alerta-sucesso');
    if (!alert) { console.error(texto); return; }
    alert.className = alert.className.replace(/bg-\S+|border-\S+|text-\S+/g, '');
    alert.classList.add('bg-red-50', 'border-l-4', 'border-brand-red', 'text-red-800',
        'p-4', 'rounded-r', 'shadow-md', 'mb-6', 'flex', 'justify-between', 'items-center');
    setText('alerta-sucesso-texto', texto);
    alert.classList.remove('hidden');
    setTimeout(fecharAlerta, 8000);
}

function fecharAlerta() {
    const el = document.getElementById('alerta-sucesso');
    if (el) el.classList.add('hidden');
}

// ---------------------------------------------------------------------
//  GESTÃO DE MONITORAS (Frente A) + VÍNCULOS
// ---------------------------------------------------------------------
let _monitoraVinculoAtual = null;

async function criarMonitora(event) {
    event.preventDefault();
    const payload = {
        login: valueOf('mon-login'),
        password: valueOf('mon-senha'),
        perfil: 'monitora',
        cpf: valueOf('mon-cpf'),
        classes: valueOf('mon-classes')
    };
    try {
        await apiFetch('/usuarios', { method: 'POST', body: JSON.stringify(payload) });
        event.target.reset();
        mostrarAlertaSucesso('Monitora cadastrada com sucesso.');
        carregarMonitoras();
    } catch (e) {
        mostrarAlertaErro('Erro ao criar monitora: ' + e.message);
    }
}

async function carregarMonitoras() {
    const tbody = document.getElementById('tabela-monitoras');
    if (!tbody) return;
    tbody.innerHTML = '<tr><td colspan="4" class="p-3 text-gray-400">Carregando...</td></tr>';
    try {
        const resp = await apiFetch('/monitoras?per_page=100');
        const lista = resp.data || [];
        tbody.innerHTML = '';
        if (lista.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="p-3 text-gray-400">Nenhuma monitora cadastrada.</td></tr>';
            return;
        }
        lista.forEach(m => {
            const login = m.user ? m.user.login : '—';
            const tr = document.createElement('tr');
            tr.className = 'hover:bg-gray-50';
            tr.innerHTML = `
                <td class="p-3 font-semibold text-gray-800">${escapeHtml(login)}</td>
                <td class="p-3 text-gray-600">${escapeHtml(m.classes || '—')}</td>
                <td class="p-3"><span class="bg-blue-50 text-brand-blue px-2 py-0.5 rounded text-[10px] font-bold">${m.alunos_count != null ? m.alunos_count : '—'}</span></td>
                <td class="p-3 text-right space-x-2 whitespace-nowrap">
                    <button class="text-emerald-600 hover:underline" title="Vincular alunos"><i class="fa-solid fa-link"></i></button>
                    <button class="text-blue-600 hover:underline" title="Redefinir senha"><i class="fa-solid fa-key"></i></button>
                    <button class="text-red-600 hover:underline" title="Remover"><i class="fa-solid fa-trash"></i></button>
                </td>`;
            const [btnVinc, btnSenha, btnDel] = tr.querySelectorAll('button');
            btnVinc.onclick = () => abrirVinculos(m.id, login);
            btnSenha.onclick = () => redefinirSenhaMonitora(m.user.id, login);
            btnDel.onclick = () => removerMonitora(m.user.id, login);
            tbody.appendChild(tr);
        });
    } catch (e) {
        tbody.innerHTML = `<tr><td colspan="4" class="p-3 text-red-500">Erro: ${escapeHtml(e.message)}</td></tr>`;
    }
}

async function removerMonitora(userId, login) {
    const ok = await modalConfirmar(
        'Remover monitora',
        `Deseja desativar a monitora ${login}? Ela deixará de acessar o sistema. O histórico é preservado.`,
        { confirmarTexto: 'Remover' }
    );
    if (!ok) return;
    try {
        await apiFetch('/usuarios/' + userId, { method: 'DELETE' });
        mostrarAlertaSucesso('Monitora desativada com sucesso.');
        carregarMonitoras();
    } catch (e) { mostrarAlertaErro('Erro ao remover: ' + e.message); }
}

async function redefinirSenhaMonitora(userId, login) {
    const res = await modalFormulario(
        'Redefinir senha',
        [{ name: 'senha', label: `Nova senha para ${login}`, type: 'text', required: true, minlength: 6 }],
        { texto: 'A senha deve ter ao menos 6 caracteres. Os tokens ativos serão revogados.', confirmarTexto: 'Redefinir' }
    );
    if (!res) return;
    try {
        await apiFetch('/usuarios/' + userId + '/senha', { method: 'PUT', body: JSON.stringify({ password: res.senha }) });
        mostrarAlertaSucesso('Senha redefinida com sucesso.');
    } catch (e) { mostrarAlertaErro('Erro ao redefinir senha: ' + e.message); }
}

async function abrirVinculos(monitoraId, login) {
    _monitoraVinculoAtual = monitoraId;
    document.getElementById('painel-vinculos').classList.remove('hidden');
    setText('vinc-monitora-nome', login);
    // preenche o select com todos os alunos
    const sel = document.getElementById('vinc-aluno-select');
    sel.innerHTML = '';
    cacheAlunos.forEach(a => { sel.innerHTML += `<option value="${a.id}">${escapeHtml(a.nome)}</option>`; });
    await renderizarVinculos(monitoraId);
}

async function renderizarVinculos(monitoraId) {
    const lista = document.getElementById('vinc-lista');
    lista.innerHTML = '<span class="text-xs text-gray-400">Carregando...</span>';
    try {
        const resp = await apiFetch('/monitoras/' + monitoraId + '/alunos');
        const alunos = resp.data || [];
        lista.innerHTML = '';
        if (alunos.length === 0) { lista.innerHTML = '<span class="text-xs text-gray-400">Nenhum aluno vinculado.</span>'; return; }
        alunos.forEach(a => {
            const row = document.createElement('div');
            row.className = 'flex justify-between items-center bg-gray-50 px-3 py-1.5 rounded text-xs';
            row.innerHTML = `<span>${escapeHtml(a.nome)}</span>`;
            const btn = document.createElement('button');
            btn.className = 'text-red-500 hover:text-red-700';
            btn.innerHTML = '<i class="fa-solid fa-xmark"></i>';
            btn.onclick = () => desvincularAlunoMonitora(monitoraId, a.id);
            row.appendChild(btn);
            lista.appendChild(row);
        });
    } catch (e) { lista.innerHTML = `<span class="text-xs text-red-500">${escapeHtml(e.message)}</span>`; }
}

async function vincularAlunoMonitora() {
    const alunoId = document.getElementById('vinc-aluno-select').value;
    if (!alunoId || !_monitoraVinculoAtual) return;
    try {
        await apiFetch('/monitoras/' + _monitoraVinculoAtual + '/alunos', { method: 'POST', body: JSON.stringify({ aluno_id: alunoId }) });
        await renderizarVinculos(_monitoraVinculoAtual);
        carregarMonitoras();
    } catch (e) { mostrarAlertaErro('Erro ao vincular: ' + e.message); }
}

async function desvincularAlunoMonitora(monitoraId, alunoId) {
    try {
        await apiFetch('/monitoras/' + monitoraId + '/alunos/' + alunoId, { method: 'DELETE' });
        await renderizarVinculos(monitoraId);
        carregarMonitoras();
    } catch (e) { mostrarAlertaErro('Erro ao desvincular: ' + e.message); }
}

function fecharVinculos() {
    _monitoraVinculoAtual = null;
    document.getElementById('painel-vinculos').classList.add('hidden');
}

// ---------------------------------------------------------------------
//  TODAS AS OCORRÊNCIAS (visão admin, com editar/remover)
// ---------------------------------------------------------------------
async function carregarOcorrenciasAdmin() {
    const container = document.getElementById('container-ocorrencias-admin');
    if (!container) return;
    container.innerHTML = '<div class="bg-white p-6 rounded-xl border border-gray-100 text-center text-gray-400 text-xs">Carregando...</div>';
    try {
        const resp = await apiFetch('/ocorrencias?per_page=100');
        const lista = resp.data || [];
        container.innerHTML = '';
        if (lista.length === 0) {
            container.innerHTML = '<div class="bg-white p-6 rounded-xl border border-gray-100 text-center text-gray-400">Nenhuma ocorrência registrada.</div>';
            return;
        }
        lista.forEach(oc => {
            const autor = oc.monitora && oc.monitora.user ? oc.monitora.user.login : 'Administração';
            const alunoNome = oc.aluno ? oc.aluno.nome : ('#' + oc.aluno_id);
            const card = document.createElement('div');
            card.className = 'bg-white p-5 rounded-xl shadow-sm border border-gray-100 space-y-3';
            card.innerHTML = `
                <div class="flex justify-between items-start border-b border-gray-100 pb-2">
                    <div>
                        <span class="text-xs font-bold text-gray-500 uppercase block">Aluno: <span class="text-brand-blue">${escapeHtml(alunoNome)}</span></span>
                        <span class="text-[10px] text-gray-400"><i class="fa-regular fa-clock"></i> ${formatarDataHora(oc.data_hora)} · por ${escapeHtml(autor)}</span>
                    </div>
                    <div class="space-x-2 whitespace-nowrap">
                        <button class="text-blue-600 hover:underline text-xs" title="Editar"><i class="fa-solid fa-pen"></i></button>
                        <button class="text-red-600 hover:underline text-xs" title="Remover"><i class="fa-solid fa-trash"></i></button>
                    </div>
                </div>
                <p class="text-xs text-gray-500"><strong>Intervenção:</strong> <span class="text-gray-800">${escapeHtml(oc.tipo_intervencao || '—')}</span></p>
                <p class="text-xs text-gray-700 bg-slate-50 p-3 rounded border border-slate-100"><strong>Fatos:</strong> ${escapeHtml(oc.descricao_fatos || '—')}</p>`;
            const [btnEdit, btnDel] = card.querySelectorAll('button');
            btnEdit.onclick = () => editarOcorrencia(oc);
            btnDel.onclick = () => removerOcorrencia(oc.id);
            container.appendChild(card);
        });
    } catch (e) {
        container.innerHTML = `<div class="bg-white p-6 rounded-xl border border-gray-100 text-center text-red-500 text-xs">${escapeHtml(e.message)}</div>`;
    }
}

async function editarOcorrencia(oc) {
    const res = await modalFormulario(
        'Editar ocorrência',
        [
            { name: 'descricao_fatos', label: 'Fatos relatados', type: 'textarea', value: oc.descricao_fatos || '', required: true },
            { name: 'tipo_intervencao', label: 'Tipo de intervenção', type: 'text', value: oc.tipo_intervencao || '', required: true },
        ],
        { confirmarTexto: 'Salvar alterações' }
    );
    if (!res) return;
    try {
        await apiFetch('/ocorrencias/' + oc.id, {
            method: 'PUT',
            body: JSON.stringify({ descricao_fatos: res.descricao_fatos, tipo_intervencao: res.tipo_intervencao })
        });
        mostrarAlertaSucesso('Ocorrência atualizada.');
        carregarOcorrenciasAdmin();
    } catch (e) { mostrarAlertaErro('Erro ao editar: ' + e.message); }
}

async function removerOcorrencia(id) {
    const ok = await modalConfirmar(
        'Remover ocorrência',
        'Esta ação é registrada na trilha de auditoria. Deseja continuar?',
        { confirmarTexto: 'Remover' }
    );
    if (!ok) return;
    try {
        await apiFetch('/ocorrencias/' + id, { method: 'DELETE' });
        mostrarAlertaSucesso('Ocorrência removida.');
        carregarOcorrenciasAdmin();
    } catch (e) { mostrarAlertaErro('Erro ao remover: ' + e.message); }
}

// Restaura sessão se houver token salvo
window.addEventListener('DOMContentLoaded', async () => {
    if (getToken()) {
        try {
            const user = await apiFetch('/me');
            aplicarUsuario(user);
            await loginSucesso(usuarioAtivo.perfil === 'Admin' ? 'admin-dashboard' : 'monitora-dashboard');
        } catch (_) {
            localStorage.removeItem(TOKEN_KEY);
        }
    }
});

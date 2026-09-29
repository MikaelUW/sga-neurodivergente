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
        }

// BANCO DE DADOS EM MEMÓRIA (Persiste no LocalStorage)
        const SEED_ALUNOS = [
            {
                id: 1024,
                nome: "João da Silva",
                dataNascimento: "2018-05-15",
                necessidadesEspeciais: "TEA Nível 1 - Suporte Leve",
                observacoesGerais: "Sensibilidade a barulhos extremos. Gosta de desenhos geométricos para se acalmar.",
                gatilho: "Excesso de ruído (Sinal da escola, gritos coletivos)",
                acao: "Fornecer abafadores de som de forma imediata e encaminhá-lo ao espaço reservado de recursos.",
                prioridade: "Alta"
            },
            {
                id: 1025,
                nome: "Mariana Costa",
                dataNascimento: "2017-11-20",
                necessidadesEspeciais: "TDAH & Sensibilidade Sensorial",
                observacoesGerais: "Dificuldade de foco prolongado. Responde bem a estímulos visuais curtos.",
                gatilho: "Mudanças abruptas de rotina de aula sem aviso prévio",
                acao: "Utilizar o painel visual de rotinas antes de transições para prepará-la.",
                prioridade: "Média"
            }
        ];

        const SEED_OCORRENCIAS = [
            {
                id: 1,
                alunoId: 1024,
                alunoNome: "João da Silva",
                monitoraNome: "Ana Silva",
                dataHora: "2026-05-13T14:30:00",
                intervencao: "Retirada da sala",
                descricaoCifrada: "U2FsdGVkX19r9m9fS8+Vv9QvH7WlD1p2Z8s9df+a8= (Fatos: Aluno apresentou agitação motora intensa após mudança inesperada na rotina da aula de artes. Aplicado protocolo de isolamento acústico.)"
            },
            {
                id: 2,
                alunoId: 1024,
                alunoNome: "João da Silva",
                monitoraNome: "Ana Silva",
                dataHora: "2026-05-02T09:15:00",
                intervencao: "Contenção verbal",
                descricaoCifrada: "U2FsdGVkX18mNzk4Y3g4Zjk4MmRzOGZzOWRmODk= (Fatos: Recusa em participar da atividade em grupo. Redirecionado para atividade individual com sucesso após 10 minutos.)"
            }
        ];

        // INICIALIZAÇÃO DE DADOS
        if (!localStorage.getItem('SGA_alunos')) {
            localStorage.setItem('SGA_alunos', JSON.stringify(SEED_ALUNOS));
        }
        if (!localStorage.getItem('SGA_ocorrencias')) {
            localStorage.setItem('SGA_ocorrencias', JSON.stringify(SEED_OCORRENCIAS));
        }
        if (!localStorage.getItem('SGA_laudos_count')) {
            localStorage.setItem('SGA_laudos_count', "0");
        }

        // ESTADOS DE SESSÃO ATIVA
        let usuarioAtivo = null;
        let secaoAtiva = 'admin-dashboard';

        // FUNÇÃO DE LOGIN
        function realizarLogin(event) {
            event.preventDefault();
            const email = document.getElementById('login-email').value;
            const senha = document.getElementById('login-senha').value;
            const erroDiv = document.getElementById('login-erro');

            if (email === 'admin@instituicao.edu.br' && senha === 'admin123') {
                usuarioAtivo = {
                    nome: "Gabriella Céspedes (Admin)",
                    cargo: "Administrador",
                    perfil: "Admin",
                    avatar: "GC"
                };
                loginSucesso('admin-dashboard');
            } else if (email === 'monitora@instituicao.edu.br' && senha === 'monitora123') {
                usuarioAtivo = {
                    nome: "Ana Silva",
                    cargo: "Monitora Operacional",
                    perfil: "Monitora",
                    avatar: "AS"
                };
                loginSucesso('monitora-dashboard');
            } else {
                erroDiv.classList.remove('hidden');
            }
        }

        // Alterna a visibilidade da senha no campo de login (ícone de olho)
        function alternarVisibilidadeSenha(botao) {
            const input = document.getElementById('login-senha');
            const icone = botao.querySelector('i');
            const visivel = input.type === 'text';

            input.type = visivel ? 'password' : 'text';
            icone.classList.toggle('fa-eye', visivel);
            icone.classList.toggle('fa-eye-slash', !visivel);
        }

        function preencherCredenciais(perfil) {
            if (perfil === 'admin') {
                document.getElementById('login-email').value = 'admin@instituicao.edu.br';
                document.getElementById('login-senha').value = 'admin123';
            } else {
                document.getElementById('login-email').value = 'monitora@instituicao.edu.br';
                document.getElementById('login-senha').value = 'monitora123';
            }
        }

        function loginSucesso(destino) {
            document.getElementById('tela-login').classList.add('hidden');
            document.getElementById('sistema-dashboard').classList.remove('hidden');
            document.getElementById('login-erro').classList.add('hidden');
            
            // Sidebar dinâmico
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

            // Carregar e Renderizar dados das tabelas
            sincronizarDados();
            navegarPara(destino);
        }

        function realizarLogout() {
            usuarioAtivo = null;
            document.getElementById('tela-login').classList.remove('hidden');
            document.getElementById('sistema-dashboard').classList.add('hidden');
            document.getElementById('login-email').value = '';
            document.getElementById('login-senha').value = '';
        }

        // SIMULADOR RÁPIDO DE MODO (Para facilitar a correção da banca do PI)
        function simularPerfil(perfil) {
            if (perfil === 'login') {
                realizarLogout();
            } else if (perfil === 'admin') {
                usuarioAtivo = {
                    nome: "Gabriella Céspedes (Admin)",
                    cargo: "Administrador",
                    perfil: "Admin",
                    avatar: "GC"
                };
                loginSucesso('admin-dashboard');
            } else if (perfil === 'monitora') {
                usuarioAtivo = {
                    nome: "Ana Silva",
                    cargo: "Monitora Operacional",
                    perfil: "Monitora",
                    avatar: "AS"
                };
                loginSucesso('monitora-dashboard');
            }
        }

        // NAVEGAÇÃO DE TELA (SPA)
        function navegarPara(secaoId) {
            secaoAtiva = secaoId;
            const secoes = [
                'sec-admin-dashboard', 'sec-admin-cadastro', 'sec-admin-laudos',
                'sec-monitora-dashboard', 'sec-monitora-perfil', 'sec-monitora-ocorrencia', 'sec-monitora-historico'
            ];
            
            secoes.forEach(sec => {
                const el = document.getElementById(sec);
                if (sec === `sec-${secaoId}`) {
                    el.classList.remove('hidden');
                } else {
                    el.classList.add('hidden');
                }
            });

            // Atualização de Estados do Menu Lateral
            const todosBotoes = document.querySelectorAll('aside nav button');
            todosBotoes.forEach(btn => {
                btn.className = "w-full text-left px-4 py-2.5 rounded-lg text-sm font-medium flex items-center gap-3 text-gray-600 hover:bg-gray-50 hover:text-gray-900";
            });

            const activeBtn = document.getElementById(`btn-${secaoId}`);
            if (activeBtn) {
                if (usuarioAtivo.perfil === 'Admin') {
                    activeBtn.className = "w-full text-left px-4 py-2.5 rounded-lg text-sm font-medium flex items-center gap-3 bg-blue-50 text-brand-blue";
                } else {
                    activeBtn.className = "w-full text-left px-4 py-2.5 rounded-lg text-sm font-medium flex items-center gap-3 bg-emerald-50 text-emerald-700";
                }
            }
        }

        // SINCRONIZAR E ATUALIZAR VIEWS
        function sincronizarDados() {
            const alunos = JSON.parse(localStorage.getItem('SGA_alunos'));
            const ocorrencias = JSON.parse(localStorage.getItem('SGA_ocorrencias'));
            const laudosCount = parseInt(localStorage.getItem('SGA_laudos_count'));

            // Métricas
            document.getElementById('card-total-alunos').innerText = alunos.length;
            document.getElementById('card-total-ocorrencias').innerText = ocorrencias.length;
            document.getElementById('card-total-laudos').innerText = laudosCount;

            // Renderizar Tabelas & Dropdowns
            renderizarTabelaAlunosAdmin();
            renderizarDropdownsAlunos();
            renderizarCardsAlunosMonitora();
            renderizarListaBuscaPerfilMonitora();
            renderizarHistoricoOcorrencias();
        }

        // RENDERS DINÂMICOS
        function renderizarTabelaAlunosAdmin() {
            const alunos = JSON.parse(localStorage.getItem('SGA_alunos'));
            const tbody = document.getElementById('tabela-alunos-dashboard');
            tbody.innerHTML = '';

            alunos.forEach(aluno => {
                const tr = document.createElement('tr');
                tr.className = "hover:bg-gray-50";
                tr.innerHTML = `
                    <td class="p-3 font-bold text-gray-400">#${aluno.id}</td>
                    <td class="p-3 font-semibold text-gray-800">${aluno.nome}</td>
                    <td class="p-3 text-gray-500">${formatarData(aluno.dataNascimento)}</td>
                    <td class="p-3 text-gray-600"><span class="bg-blue-50 text-brand-blue px-2 py-0.5 rounded text-[10px] font-medium">${aluno.necessidadesEspeciais}</span></td>
                    <td class="p-3">
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold ${aluno.prioridade === 'Alta' ? 'bg-red-50 text-brand-red' : 'bg-amber-50 text-amber-700'}">
                            Gatilho: ${aluno.gatilho ? aluno.gatilho : 'Não vinculado'}
                        </span>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }

        function renderizarDropdownsAlunos() {
            const alunos = JSON.parse(localStorage.getItem('SGA_alunos'));
            const laudosSelect = document.getElementById('laudo-aluno-id');
            const ocorrenciasSelect = document.getElementById('oc-aluno-id');
            const historicoSelect = document.getElementById('hist-filtro-aluno');

            // Limpar
            laudosSelect.innerHTML = '<option value="">Selecione um Aluno...</option>';
            ocorrenciasSelect.innerHTML = '<option value="">Selecione um Aluno...</option>';
            historicoSelect.innerHTML = '<option value="todos">Todos os Alunos</option>';

            alunos.forEach(aluno => {
                const option = `<option value="${aluno.id}">${aluno.nome} (ID: #${aluno.id})</option>`;
                laudosSelect.innerHTML += option;
                ocorrenciasSelect.innerHTML += option;
                historicoSelect.innerHTML += option;
            });
        }

        function renderizarCardsAlunosMonitora() {
            const alunos = JSON.parse(localStorage.getItem('SGA_alunos'));
            const container = document.getElementById('cards-alunos-monitora');
            container.innerHTML = '';

            alunos.forEach(aluno => {
                const card = document.createElement('div');
                card.className = "bg-white p-4 rounded-lg border border-gray-150 shadow-sm hover:shadow-md transition space-y-3 cursor-pointer";
                card.onclick = () => {
                    carregarPerfilAluno(aluno.id);
                };
                card.innerHTML = `
                    <div class="flex justify-between items-start">
                        <h4 class="text-xs font-bold text-gray-800">${aluno.nome}</h4>
                        <span class="text-[9px] font-bold px-2 py-0.5 rounded uppercase ${aluno.prioridade === 'Alta' ? 'bg-red-50 text-brand-red' : 'bg-amber-50 text-amber-700'}">${aluno.prioridade}</span>
                    </div>
                    <div class="text-[11px] text-gray-500">
                        <span class="block"><strong>Necessidade:</strong> ${aluno.necessidadesEspeciais}</span>
                        <span class="block mt-1 truncate"><strong>Gatilho:</strong> <span class="text-amber-700 font-medium">${aluno.gatilho}</span></span>
                    </div>
                `;
                container.appendChild(card);
            });
        }

        function renderizarListaBuscaPerfilMonitora() {
            const alunos = JSON.parse(localStorage.getItem('SGA_alunos'));
            const container = document.getElementById('lista-alunos-perfil-busca');
            container.innerHTML = '';

            alunos.forEach(aluno => {
                const btn = document.createElement('button');
                btn.className = "w-full text-left p-3 rounded-lg text-xs font-medium border border-gray-100 hover:bg-gray-50 transition flex justify-between items-center bg-white";
                btn.onclick = () => {
                    carregarPerfilAluno(aluno.id);
                };
                btn.innerHTML = `
                    <span>${aluno.nome}</span>
                    <i class="fa-solid fa-chevron-right text-gray-300"></i>
                `;
                container.appendChild(btn);
            });
        }

        function carregarPerfilAluno(id) {
            const alunos = JSON.parse(localStorage.getItem('SGA_alunos'));
            const aluno = alunos.find(a => a.id == id);
            
            if (aluno) {
                document.getElementById('perfil-vazio').classList.add('hidden');
                const ativoDiv = document.getElementById('perfil-ativo');
                ativoDiv.classList.remove('hidden');

                document.getElementById('perfil-iniciais').innerText = aluno.nome.split(' ').map(n => n[0]).join('').substring(0,2).toUpperCase();
                document.getElementById('perfil-nome').innerText = aluno.nome;
                
                const idade = calcularIdade(aluno.dataNascimento);
                document.getElementById('perfil-dados-basicos').innerText = `ID: #${aluno.id} | Idade: ${idade} anos | ${aluno.necessidadesEspeciais}`;
                
                document.getElementById('perfil-necessidades-texto').innerText = aluno.necessidadesEspeciais;
                document.getElementById('perfil-obs-texto').innerText = aluno.observacoesGerais || "Nenhuma observação cadastrada.";
                
                document.getElementById('perfil-gatilho').innerText = aluno.gatilho;
                document.getElementById('perfil-acao').innerText = aluno.acao;

                const priBadge = document.getElementById('perfil-prioridade-badge');
                priBadge.innerText = `Prioridade ${aluno.prioridade}`;
                if (aluno.prioridade === 'Alta') {
                    priBadge.className = "bg-brand-red text-white text-[9px] font-bold px-2.5 py-0.5 rounded uppercase";
                } else {
                    priBadge.className = "bg-brand-yellow text-slate-800 text-[9px] font-bold px-2.5 py-0.5 rounded uppercase";
                }

                navegarPara('monitora-perfil');
            }
        }

        function renderizarHistoricoOcorrencias() {
            const ocorrencias = JSON.parse(localStorage.getItem('SGA_ocorrencias'));
            const container = document.getElementById('container-lista-historico');
            const filtro = document.getElementById('hist-filtro-aluno').value;

            container.innerHTML = '';
            
            const ocorrenciasFiltradas = filtro === 'todos' 
                ? ocorrencias 
                : ocorrencias.filter(o => o.alunoId == filtro);

            if (ocorrenciasFiltradas.length === 0) {
                container.innerHTML = `
                    <div class="bg-white p-8 rounded-xl shadow-sm border border-gray-100 text-center text-gray-400">
                        Nenhuma ocorrência registrada para este aluno.
                    </div>
                `;
                return;
            }

            // Exibir em ordem reversa (mais recentes primeiro)
            ocorrenciasFiltradas.slice().reverse().forEach(oc => {
                const card = document.createElement('div');
                card.className = "bg-white p-6 rounded-xl shadow-sm border border-gray-100 space-y-4 relative overflow-hidden";
                
                // Texto Claro decifrado do texto fictício criptografado
                const relatoClaro = decifrarTextoFicticio(oc.descricaoCifrada);

                card.innerHTML = `
                    <div class="flex justify-between items-start border-b border-gray-100 pb-3">
                        <div>
                            <span class="text-xs font-bold text-gray-500 uppercase tracking-wider block">Aluno: <span class="text-brand-blue">${oc.alunoNome} (ID: #${oc.alunoId})</span></span>
                            <span class="text-[10px] text-gray-400 mt-1 block"><i class="fa-regular fa-clock"></i> Registrado em: ${formatarDataHora(oc.dataHora)}</span>
                        </div>
                        <span class="bg-emerald-50 text-emerald-700 text-[10px] font-bold px-2 py-1 rounded border border-emerald-150 flex items-center gap-1">
                            <i class="fa-solid fa-lock-open text-[9px]"></i> Decifrado via CriptografiaService
                        </span>
                    </div>
                    <div class="space-y-2 text-xs">
                        <p class="text-gray-500"><strong>Intervenção imediata:</strong> <span class="text-gray-800 font-medium">${oc.intervencao}</span></p>
                        <p class="text-gray-700 leading-relaxed bg-slate-50 p-3 rounded border border-slate-100">
                            <strong>Fatos relatados:</strong> ${relatoClaro}
                        </p>
                    </div>
                    <div class="text-[10px] text-gray-400 flex justify-end gap-1.5 items-center">
                        <i class="fa-regular fa-user"></i> Autor do relato: <strong class="text-gray-600">${oc.monitoraNome}</strong>
                    </div>
                `;
                container.appendChild(card);
            });
        }


        // AÇÕES DO FORMULÁRIO (Simulação de persistência)
        function cadastrarAlunoCompleto(event) {
            event.preventDefault();
            
            const nome = document.getElementById('cad-nome').value;
            const dataNascimento = document.getElementById('cad-nasc').value;
            const necessidadesEspeciais = document.getElementById('cad-necessidades').value;
            const observacoesGerais = document.getElementById('cad-obs').value;
            
            const gatilho = document.getElementById('cad-gatilho').value;
            const acao = document.getElementById('cad-acao').value;
            const prioridade = document.getElementById('cad-prioridade').value;

            const alunos = JSON.parse(localStorage.getItem('SGA_alunos'));
            
            // Gerar id incremental simples maior que 1025
            const maiorId = alunos.reduce((max, a) => a.id > max ? a.id : max, 1025);
            const novoId = maiorId + 1;

            const novoAluno = {
                id: novoId,
                nome,
                dataNascimento,
                necessidadesEspeciais,
                observacoesGerais,
                gatilho,
                acao,
                prioridade
            };

            alunos.push(novoAluno);
            localStorage.setItem('SGA_alunos', JSON.stringify(alunos));
            
            event.target.reset();
            sincronizarDados();
            mostrarAlertaSucesso(`Cadastro realizado com sucesso! Aluno #${novoId} inserido no Banco PostgreSQL.`);
            navegarPara('admin-dashboard');
        }

        function registrarLaudo(event) {
            event.preventDefault();
            const count = parseInt(localStorage.getItem('SGA_laudos_count'));
            localStorage.setItem('SGA_laudos_count', (count + 1).toString());
            
            event.target.reset();
            document.getElementById('pdf-upload-nome').innerText = "Arraste ou clique para anexar arquivo PDF";
            document.getElementById('pdf-icon-upload').className = "fa-regular fa-file-pdf text-3xl text-gray-400 mb-2 block";
            
            sincronizarDados();
            mostrarAlertaSucesso("Laudo registrado com sucesso! PDF armazenado no Storage seguro.");
        }

        function registrarNovaOcorrencia(event) {
            event.preventDefault();
            
            const AlunoId = document.getElementById('oc-aluno-id').value;
            const intervencao = document.getElementById('oc-intervencao').value;
            const fatosTxt = document.getElementById('oc-descricao').value;

            if (!AlunoId) return;

            const alunos = JSON.parse(localStorage.getItem('SGA_alunos'));
            const ocorrencias = JSON.parse(localStorage.getItem('SGA_ocorrencias'));
            
            const aluno = alunos.find(a => a.id == AlunoId);

            // Simular CriptografiaService na Tela (Spinning loading)
            const simulador = document.getElementById('simulador-cripto');
            document.getElementById('cripto-orig').innerText = fatosTxt;
            document.getElementById('cripto-hash').innerText = "Processando algoritmo AES-256...";
            simulador.classList.remove('hidden');
            
            const btn = document.getElementById('btn-salvar-ocorrencia');
            btn.disabled = true;
            btn.opacity = "0.7";

            setTimeout(() => {
                const hashGerado = btoa(fatosTxt).substring(0, 32) + "...[AES-256-SECURE]";
                document.getElementById('cripto-hash').innerText = hashGerado;

                setTimeout(() => {
                    // Salvar
                    const novaOc = {
                        id: ocorrencias.length + 1,
                        alunoId: AlunoId,
                        alunoNome: aluno.nome,
                        monitoraNome: usuarioAtivo.nome,
                        dataHora: new Date().toISOString(),
                        intervencao,
                        descricaoCifrada: `U2FsdGVkX19... (Fatos: ${fatosTxt})`
                    };

                    ocorrencias.push(novaOc);
                    localStorage.setItem('SGA_ocorrencias', JSON.stringify(ocorrencias));

                    // Resetar tela
                    simulador.classList.add('hidden');
                    btn.disabled = false;
                    btn.opacity = "1";
                    event.target.reset();
                    
                    sincronizarDados();
                    mostrarAlertaSucesso("Relato sensível criptografado e salvo com sucesso!");
                    navegarPara('monitora-dashboard');
                }, 1000);
            }, 1000);
        }

        function alterarPermissao(event) {
            event.preventDefault();
            const usuario = document.getElementById('perm-usuario').value;
            const nivel = document.getElementById('perm-nivel').value;
            
            mostrarAlertaSucesso(`Permissões atualizadas para ${usuario}. Nível configurado para: Nível ${nivel}.`);
            event.target.reset();
        }

        function carregarNivelAtual(select) {
            const selectedOpt = select.options[select.selectedIndex];
            if (selectedOpt && selectedOpt.value) {
                const nivel = selectedOpt.getAttribute('data-nivel');
                document.getElementById('perm-nivel').value = nivel;
            }
        }


        // AUXILIARES
        function formatarData(dataStr) {
            if (!dataStr) return "-";
            const parts = dataStr.split('-');
            return `${parts[2]}/${parts[1]}/${parts[0]}`;
        }

        function formatarDataHora(dataHoraStr) {
            const dt = new Date(dataHoraStr);
            return dt.toLocaleString('pt-BR');
        }

        function calcularIdade(dataNasc) {
            const hoje = new Date();
            const nasc = new Date(dataNasc);
            let idade = hoje.getFullYear() - nasc.getFullYear();
            const m = hoje.getMonth() - nasc.getMonth();
            if (m < 0 || (m === 0 && hoje.getDate() < nasc.getDate())) {
                idade--;
            }
            return idade;
        }

        function decifrarTextoFicticio(str) {
            // Extrai o conteúdo dentro de (Fatos: ...) para simular a decodificação da CriptografiaService
            const regex = /\(Fatos: (.*?)\)/;
            const match = str.match(regex);
            if (match && match[1]) {
                return match[1];
            }
            return str;
        }

        function mostrarArquivoSelecionado(input) {
            const nameSpan = document.getElementById('pdf-upload-nome');
            const icon = document.getElementById('pdf-icon-upload');
            if (input.files && input.files[0]) {
                nameSpan.innerText = `Anexado: ${input.files[0].name} (${(input.files[0].size/1024).toFixed(1)} KB)`;
                icon.className = "fa-solid fa-file-pdf text-3xl text-emerald-500 mb-2 block";
            }
        }

        function mostrarAlertaSucesso(texto) {
            const alert = document.getElementById('alerta-sucesso');
            document.getElementById('alerta-sucesso-texto').innerText = texto;
            alert.classList.remove('hidden');
            setTimeout(() => {
                fecharAlerta();
            }, 6000);
        }

        function fecharAlerta() {
            document.getElementById('alerta-sucesso').classList.add('hidden');
        }

# 🛡️ Pesquisa de Legislação LGPD e Auditoria de Conformidade no SGAN

**Projeto**: SGAN - Sistema de Gestão de Alunos Neurodivergentes (`sgan-api`)  
**Base Legal**: Lei Geral de Proteção de Dados Pessoais (Lei nº 13.709/2018)  
**Data da Auditoria**: 28 de Agosto de 2026  

---

## 📖 PARTE 1: Pesquisa Abrangente sobre a LGPD Aplicada ao SGAN

O **SGAN (Sistema de Gestão de Alunos Neurodivergentes)** é uma plataforma educacional e de apoio comportamental que lida com três categorias de altíssimo risco regulatório:
1. **Dados Pessoais Comuns de Crianças e Adolescentes** (Nome, idade, data de nascimento).
2. **Dados Pessoais Sensíveis de Saúde e Diagnóstico** (TEA, TDAH, laudos médicos, atestados, CIDs, registros de crises comportamentais e rotinas de descompressão).
3. **Público Vulnerável / Menores de Idade** (Proteção reforçada sob o Estatuto da Criança e do Adolescente - ECA e LGPD Art. 14).

Abaixo estão listados detalhadamente todos os artigos, princípios e incisos da **Lei nº 13.709/2018 (LGPD)** pertinentes ao ecossistema do SGAN.

---

### 1. Definição e Enquadramento dos Dados (Artigo 5º)

* **Artigo 5º, Inciso I – Dado Pessoal**: Informação relacionada a pessoa natural identificada ou identificável (ex: Nome do aluno, data de nascimento, foto, e-mail dos pais).
* **Artigo 5º, Inciso II – Dado Pessoal Sensível**: Dado pessoal sobre **saúde** ou vida sexual, dado genético ou biométrico, quando vinculado a uma pessoa natural.
  > 💡 **Aplicação no SGAN**: O diagnóstico de neurodivergência (TEA, TDAH, AH/SD), relatos de ocorrências comportamentais, planos de manejo/descompressão, CRM médico e laudos em PDF enquadram-se **estritamente como dados pessoais sensíveis de saúde**.
* **Artigo 5º, Inciso VIII – Controlador**: Pessoa natural ou jurídica a quem competem as decisões referentes ao tratamento de dados pessoais (a Instituição de Ensino / Escola).
* **Artigo 5º, Inciso IX – Operador**: Pessoa natural ou jurídica que realiza o tratamento de dados em nome do controlador (a equipe de TI / desenvolvedores da plataforma).

---

### 2. Princípios Fundamentais do Tratamento (Artigo 6º)

As atividades de tratamento de dados no SGAN devem obrigatoriamente respeitar os 10 princípios do Art. 6º:

1. **Finalidade (Inciso I)**: Realização do tratamento para propósitos legítimos, específicos, explícitos e informados ao titular (ajustar o plano pedagógico e de apoio comportamental do aluno).
2. **Adequação (Inciso II)**: Compatibilidade do tratamento com as finalidades informadas aos pais/responsáveis.
3. **Necessidade / Minimização (Inciso III)**: Limitação do tratamento ao **mínimo necessário** para a realização de suas finalidades, com abrangência dos dados pertinentes, proporcionais e não excessivos.
4. **Livre Acesso (Inciso IV)**: Garantia aos pais/responsáveis de consulta facilitada e gratuita sobre a forma e a duração do tratamento, bem como sobre a integralidade de seus dados pessoais.
5. **Qualidade dos Dados (Inciso V)**: Garantia de exatidão, clareza e atualização dos dados dos alunos.
6. **Transparência (Inciso VI)**: Informações claras, precisas e facilmente acessíveis sobre a realização do tratamento.
7. **Segurança (Inciso VII)**: Utilização de medidas técnicas e administrativas aptas a proteger os dados pessoais de acessos não autorizados e de situações acidentais ou ilícitas.
8. **Prevenção (Inciso VIII)**: Adoção de medidas para prevenir a ocorrência de danos em virtude do tratamento de dados de alunos neurodivergentes.
9. **Não Discriminação (Inciso IX)**: Impossibilidade de realização do tratamento para fins discriminatórios ilícitos ou abusivos.
10. **Responsabilização e Prestação de Contas (Inciso X)**: Demonstração, pelo agente, da eficácia das medidas adotadas.

---

### 3. Tratamento de Dados de Crianças e Adolescentes (Artigo 14)

Este é um dos pilares mais críticos para o SGAN, pois grande parte dos alunos são menores de idade.

* **Art. 14, Caput**: O tratamento de dados pessoais de crianças e adolescentes deverá ser realizado em seu **melhor interesse**.
* **Art. 14, § 1º**: O tratamento de dados pessoais de crianças deverá ser realizado com o **consentimento específico e em destaque dado por pelo menos um dos pais ou pelo responsável legal**.
* **Art. 14, § 3º**: As informações sobre o tratamento deverão ser fornecidas de maneira simples, clara e acessível, consideradas as características físico-motoras, perceptivas, sensoriais, intelectuais e mentais dos usuários, usando recursos audiovisuais quando adequado, de forma a proporcionar a informação necessária aos pais ou responsável legal.

---

### 4. Hipóteses Legais para Tratamento de Dados Sensíveis (Artigo 11)

O tratamento de dados pessoais sensíveis (saúde/neurodivergência) no SGAN só pode ocorrer nas seguintes hipóteses:

* **Art. 11, Inciso I**: Mediante **consentimento específico e destacado** do titular ou de seu responsável legal, para finalidades específicas.
* **Art. 11, Inciso II, alínea 'a'**: Cumprimento de **obrigação legal ou regulatória** pelo controlador (ex: Lei nº 13.146/2015 - Estatuto da Pessoa com Deficiência e Diretrizes da Educação Inclusiva).
* **Art. 11, Inciso II, alínea 'f'**: Tutela da saúde, exclusivamente, em procedimento realizado por profissionais de saúde, serviços de saúde ou autoridade sanitária.

---

### 5. Registro de Operações e Logs de Auditoria (Artigo 37)

* **Art. 37**: O controlador e o operador devem manter **registro das operações de tratamento de dados pessoais** que realizarem.
  > 💡 **Aplicação no SGAN**: Toda visualização, criação, alteração ou download de ocorrências comportamentais e laudos médicos deve gerar um rastro auditável (Log de Acesso) contendo Usuário, Aluno, Ação, Data e IP.

---

### 6. Segurança e Sigilo dos Dados (Artigos 46 a 50)

* **Art. 46**: Os agentes de tratamento devem adotar medidas de **segurança, técnicas e administrativas** aptas a proteger os dados pessoais.
* **Art. 47**: Os agentes de tratamento de dados têm o dever de garantir a segurança da informação em relação aos dados pessoais.
* **Art. 48**: O controlador deverá comunicar à autoridade nacional (ANPD) e aos titulares a ocorrência de incidente de segurança que possa acarretar risco ou dano relevante aos titulares.

---

### 7. Término do Tratamento e Retenção Legítima (Artigos 15 e 16)

* **Art. 15**: O tratamento de dados pessoais será encerrado quando atingida a finalidade, quando os dados deixarem de ser necessários ou por revogação do consentimento pelos pais.
* **Art. 16**: Os dados pessoais serão eliminados após o término de seu tratamento, autorizada a conservação para:
  * Cumprimento de obrigação legal ou regulatória pelo controlador (prazos de guarda de histórico escolar do MEC);
  * Estudo por órgão de pesquisa (com anonimização dos dados sempre que possível);
  * Uso exclusivo do controlador, vedado seu acesso por terceiro, e desde que anonimizados os dados.

---

## 🔍 PARTE 2: Auditoria de Conformidade do Código-Fonte (`sgan-api`)

Após análise minuciosa de todo o repositório `sgan-api` (Models, Controllers, Services, Migrations, Storage e Rotas), foram identificados **7 pontos de conflito e não conformidade com a LGPD**.

---

### 🚨 Conflito 1: Armazenamento e Exposição Pública de Laudos Médicos (Art. 11 & Art. 46)

* **Local do Código**: [LaudoMedicoController.php](file:///C:/Users/Mikael/.gemini/antigravity-ide/scratch/sgan-api/app/Http/Controllers/Api/LaudoMedicoController.php#L33)
  ```php
  $file = $request->file('arquivo');
  $path = $file->store('laudos', 'public');
  ```
* **Diagnóstico de Conflito**:
  1. O arquivo PDF do laudo médico é armazenado no disco `public` (`storage/app/public/laudos`).
  2. Ao executar `php artisan storage:link`, esses arquivos PDF tornam-se **diretamente acessíveis via URL pública HTTP** (ex: `http://localhost:8000/storage/laudos/nome_arquivo.pdf`), sem necessidade de token de autenticação ou cabeçalho `Authorization`.
  3. O arquivo PDF no disco rígido é gravado em texto puro (não criptografado).
* **Risco LGPD**: **Vazamento gravíssimo de dados sensíveis de saúde de crianças**. Qualquer pessoa com o link direto do PDF consegue baixar diagnósticos médicos confidenciais.

---

### 🚨 Conflito 2: Ausência de Registro de Consentimento dos Pais/Responsáveis (Art. 14, §1º)

* **Local do Código**: [Aluno.php](file:///C:/Users/Mikael/.gemini/antigravity-ide/scratch/sgan-api/app/Models/Aluno.php) e migration `2026_01_01_000004_create_alunos_table.php`
* **Diagnóstico de Conflito**:
  1. A tabela `alunos` possui apenas os campos: `nome`, `data_nascimento`, `necessidades_especiais`, `observacoes_gerais`.
  2. Não há identificação do Pai/Mãe/Responsável Legal (Nome, CPF, Contato).
  3. Não existe nenhum campo ou tabela para registrar o **Termo de Consentimento LGPD** (data da assinatura, versão do termo, aceite dos pais).
* **Risco LGPD**: Violação direta do **Art. 14, §1º da LGPD**, que condiciona a licitude do tratamento de dados de menores ao consentimento explícito e destacado dos pais.

---

### 🚨 Conflito 3: Ausência de Audit Trail / Logs de Acesso a Prontuários e Ocorrências (Art. 37 & Art. 46)

* **Local do Código**: [AlunoController.php](file:///C:/Users/Mikael/.gemini/antigravity-ide/scratch/sgan-api/app/Http/Controllers/Api/AlunoController.php#L48), [OcorrenciaController.php](file:///C:/Users/Mikael/.gemini/antigravity-ide/scratch/sgan-api/app/Http/Controllers/Api/OcorrenciaController.php#L50) e [LaudoMedicoController.php](file:///C:/Users/Mikael/.gemini/antigravity-ide/scratch/sgan-api/app/Http/Controllers/Api/LaudoMedicoController.php#L51)
* **Diagnóstico de Conflito**:
  1. Quando um usuário (Admin ou Monitora) consulta o perfil completo do aluno, o histórico de ocorrências ou realiza o download do laudo médico, o sistema decifra e entrega os dados sem gerar **nenhum registro de log em banco de dados**.
* **Risco LGPD**: Caso um funcionário mal-intencionado vazou dados ou fez downloads indevidos, a escola não tem como comprovar responsabilidades perante a ANPD nem cumprir o **Artigo 37**.

---

### 🚨 Conflito 4: Fallback Silencioso no `CriptografiaService` com Risco de Exposição (Art. 46)

* **Local do Código**: [CriptografiaService.php](file:///C:/Users/Mikael/.gemini/antigravity-ide/scratch/sgan-api/app/Services/CriptografiaService.php#L24-L26)
  ```php
  try {
      return Crypt::encryptString($texto);
  } catch (Exception $e) {
      return $texto; // ⚠️ Retorna texto claro em caso de exceção!
  }
  ```
* **Diagnóstico de Conflito**:
  1. Se ocorrer uma falha na cifra (ex: problema na extensão OpenSSL ou chave truncada), a função captura a exceção e **retorna o texto sem criptografia**.
  2. O Eloquent salva esse texto puro diretamente na coluna do banco de dados, acreditando ter criptografado.
* **Risco LGPD**: Gravação acidental de dados sensíveis em texto claro no banco PostgreSQL.

---

### 🚨 Conflito 5: Falta de Nível de Permissão Granular (Princípio da Minimização - Art. 6º, III)

* **Local do Código**: [routes/api.php](file:///C:/Users/Mikael/.gemini/antigravity-ide/scratch/sgan-api/routes/api.php#L28-L35) e [CheckPerfil.php](file:///C:/Users/Mikael/.gemini/antigravity-ide/scratch/sgan-api/app/Http/Middleware/CheckPerfil.php)
* **Diagnóstico de Conflito**:
  1. Qualquer usuário com perfil `monitora` tem autorização para visualizar a lista completa de alunos (`GET /api/alunos`), o perfil detalhado (`GET /api/alunos/{id}`) e todo o histórico de ocorrências e laudos de **qualquer aluno da instituição**.
  2. Não há controle de vínculo pedagógico (ex: Monitora A só deve visualizar alunos da Turma 1 ou alunos atribuídos a ela).
* **Risco LGPD**: Violação do **Princípio da Minimização e Necessidade (Art. 6º, III)**. O acesso a dados de saúde deve ser estritamente restrito a quem necessita deles para o atendimento direto do aluno.

---

### 🚨 Conflito 6: Política de Exclusão de Alunos sem Retenção Regulatória (Art. 15 e 16)

* **Local do Código**: [AlunoController.php](file:///C:/Users/Mikael/.gemini/antigravity-ide/scratch/sgan-api/app/Http/Controllers/Api/AlunoController.php#L101)
  ```php
  $aluno->delete(); // Hard delete direto no banco!
  ```
* **Diagnóstico de Conflito**:
  1. O método `destroy` executa um **Hard Delete** (exclusão física permanente no banco).
  2. O modelo `Aluno` não utiliza `SoftDeletes`.
  3. Ao deletar o aluno, os arquivos físicos de laudos médicos em PDF continuam salvos no disco do servidor como "arquivos órfãos", sem expurgo programado.
  4. Por outro lado, a exclusão física imediata impede a escola de cumprir o prazo legal de guarda de históricos escolares exigido pelo MEC/CNE.
* **Risco LGPD**: Incompatibilidade entre o Direito ao Esquecimento e o Cumprimento de Obrigação Legal Regulatória (Art. 16, I).

---

### 🚨 Conflito 7: Ausência de Mecanismo de Portabilidade / Exportação de Prontuário para os Pais (Art. 18, Incisos V e I)

* **Local do Código**: Ausente em toda a API.
* **Diagnóstico de Conflito**:
  1. O Artigo 18 da LGPD garante aos titulares (ou seus pais/responsáveis) o direito de requerer a confirmação, acesso e **portabilidade dos dados** em formato estruturado (ex: PDF consolidado ou JSON).
  2. A API atual não fornece nenhum endpoint onde o pai/responsável possa baixar o relatório consolidado do prontuário do seu filho.

---

## 🛠️ PARTE 3: Plano de Ação e Adequação Técnica Recomendado

Para tornar o **SGAN 100% em conformidade com a LGPD**, recomendam-se as seguintes alterações de código:

### 1. Adequação dos Laudos Médicos
* Mudar a gravação dos laudos do disco `public` para o disco `local` / `private` (`storage/app/laudos_privados`).
* Criptografar o arquivo PDF no momento do upload ou armazená-lo com nome UUID randômico fora da pasta web pública.
* Adicionar validação no endpoint de download para verificar se o usuário autenticado possui permissão explícita para aquele aluno.

### 2. Gestão de Consentimento e Responsáveis
* Criar a tabela `responsaveis` (nome, CPF, e-mail, telefone) e a pivot `aluno_responsavel`.
* Adicionar os campos na tabela `alunos`: `consentimento_lgpd_assinado` (boolean), `data_consentimento` (timestamp), `versao_termo_lgpd` (string).

### 3. Tabela de Logs de Auditoria (`acesso_logs`)
* Criar a tabela `acesso_logs` (`id`, `user_id`, `aluno_id`, `recurso_acessado`, `acao`, `ip_address`, `created_at`).
* Criar um Middleware ou Observer no Laravel que registre automaticamente cada acesso aos dados do aluno, laudos e ocorrências.

### 4. Ajuste no `CriptografiaService`
* Em vez de retornar o texto claro em caso de exceção no `catch`, lançar uma exceção personalizada (`CriptografiaException`) para impedir que dados não criptografados sejam persistidos no banco.

### 5. Atribuição de Monitores a Alunos/Turmas
* Implementar o vínculo `monitora_aluno` para que uma monitora veja apenas os alunos que estão sob seus cuidados.

### 6. Implementação de `SoftDeletes` e Ciclo de Vida de Dados
* Adicionar a trait `SoftDeletes` na model `Aluno`, mantendo o histórico para fins legais e criando um job de expurgo físico de arquivos PDF quando a retenção legal expirar.

### 7. Endpoint de Portabilidade (Exportação LGPD)
* Criar a rota `GET /api/alunos/{id}/exportar-lgpd` gerando um relatório em PDF/JSON para atendimento de solicitações dos pais.

---
*Relatório elaborado para fins de auditoria de conformidade LGPD no SGAN API.*

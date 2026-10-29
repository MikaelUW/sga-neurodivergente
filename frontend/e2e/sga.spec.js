// @ts-check
const { test, expect } = require('@playwright/test');

const ADMIN = { email: 'admin@escola.edu.br', senha: 'admin123' };
const MONITORA = { email: 'monitora@escola.edu.br', senha: 'monitora123' };

async function login(page, cred) {
  await page.goto('/');
  await page.fill('#login-email', cred.email);
  await page.fill('#login-senha', cred.senha);
  // Prepara os waiters ANTES do clique para não perder as respostas
  const loginResp = page.waitForResponse(r => r.url().includes('/api/login'));
  const alunosResp = page.waitForResponse(r => r.url().includes('/api/alunos'));
  await page.click('button[type="submit"]');
  await loginResp;
  await expect(page.locator('#sistema-dashboard')).toBeVisible();
  await alunosResp.catch(() => {});
  await page.waitForTimeout(300);
}

test.describe('Autenticação', () => {
  test('login inválido mostra erro e não entra', async ({ page }) => {
    await page.goto('/');
    await page.fill('#login-email', 'ninguem@x.com');
    await page.fill('#login-senha', 'errada');
    await page.click('button[type="submit"]');
    await expect(page.locator('#login-erro')).toBeVisible();
    await expect(page.locator('#sistema-dashboard')).toBeHidden();
  });

  test('admin faz login e vê menu de administrador', async ({ page }) => {
    await login(page, ADMIN);
    await expect(page.locator('#menu-admin')).toBeVisible();
    await expect(page.locator('#badge-perfil-sidebar')).toHaveText('Admin');
  });

  test('monitora faz login e vê menu de monitora', async ({ page }) => {
    await login(page, MONITORA);
    await expect(page.locator('#menu-monitora')).toBeVisible();
    await expect(page.locator('#badge-perfil-sidebar')).toHaveText('Monitora');
  });

  test('logout retorna à tela de login', async ({ page }) => {
    await login(page, ADMIN);
    await page.click('button:has-text("Sair do Sistema")');
    await expect(page.locator('#tela-login')).toBeVisible();
  });
});

test.describe('Admin - dashboard e alunos', () => {
  test('dashboard carrega alunos do banco (dados reais da API)', async ({ page }) => {
    await login(page, ADMIN);
    // O card de total de alunos deve ser um número >= 1 (seed)
    await expect.poll(async () => {
      const t = await page.locator('#card-total-alunos').innerText();
      return parseInt(t, 10) || 0;
    }).toBeGreaterThanOrEqual(1);
    // A tabela de alunos deve ter ao menos uma linha
    await expect(page.locator('#tabela-alunos-dashboard tr').first()).toBeVisible();
  });

  test('admin cadastra um novo aluno com protocolo', async ({ page }) => {
    await login(page, ADMIN);
    await page.click('#btn-admin-cadastro');
    await expect(page.locator('#sec-admin-cadastro')).toBeVisible();

    const nome = 'Aluno E2E ' + Date.now();
    await page.fill('#cad-nome', nome);
    await page.fill('#cad-nasc', '2016-04-10');
    await page.fill('#cad-necessidades', 'TEA Nivel 1');
    await page.fill('#cad-obs', 'Observacao de teste e2e');
    await page.fill('#cad-gatilho', 'Ruido alto');
    await page.fill('#cad-acao', 'Abafador de som');
    await page.selectOption('#cad-prioridade', 'Alta');
    await page.click('button:has-text("Salvar Registro Completo")');

    // Alerta de sucesso e retorno ao dashboard
    await expect(page.locator('#alerta-sucesso')).toBeVisible();
    await expect(page.locator('#sec-admin-dashboard')).toBeVisible();

    // O novo aluno aparece na tabela
    await expect(page.locator('#tabela-alunos-dashboard')).toContainText(nome);
  });
});

test.describe('Monitora - perfil, ocorrência e histórico', () => {
  test('monitora registra ocorrência e vê no histórico', async ({ page }) => {
    // Primeiro, como admin, garante que a monitora está vinculada a um aluno
    // (o seed já vincula). Login como monitora:
    await login(page, MONITORA);

    // Vai para lançar ocorrência
    await page.click('#btn-monitora-ocorrencia');
    await expect(page.locator('#sec-monitora-ocorrencia')).toBeVisible();

    // Seleciona o primeiro aluno disponível no dropdown
    const options = page.locator('#oc-aluno-id option');
    await expect.poll(async () => await options.count()).toBeGreaterThanOrEqual(2);

    const alunoValue = await options.nth(1).getAttribute('value');
    await page.selectOption('#oc-aluno-id', alunoValue);
    await page.fill('#oc-intervencao', 'Intervencao e2e');
    const relato = 'Relato confidencial e2e ' + Date.now();
    await page.fill('#oc-descricao', relato);
    await page.click('#btn-salvar-ocorrencia');

    await expect(page.locator('#alerta-sucesso')).toBeVisible();

    // Vai ao histórico e confirma que o relato aparece decifrado
    await page.click('#btn-monitora-historico');
    await expect(page.locator('#sec-monitora-historico')).toBeVisible();
    await expect(page.locator('#container-lista-historico')).toContainText(relato);
  });
});

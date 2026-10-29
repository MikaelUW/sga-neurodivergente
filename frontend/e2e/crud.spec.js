// @ts-check
const { test, expect } = require('@playwright/test');

const ADMIN = { email: 'admin@escola.edu.br', senha: 'admin123' };

async function loginAdmin(page) {
  await page.goto('/');
  await page.fill('#login-email', ADMIN.email);
  await page.fill('#login-senha', ADMIN.senha);
  const loginResp = page.waitForResponse(r => r.url().includes('/api/login'));
  const alunosResp = page.waitForResponse(r => r.url().includes('/api/alunos'));
  await page.click('button[type="submit"]');
  await loginResp;
  await expect(page.locator('#sistema-dashboard')).toBeVisible();
  await alunosResp.catch(() => {});
  await page.waitForTimeout(300);
}

test.describe('Admin - gerenciar monitoras', () => {
  test('cria uma monitora e ela aparece na lista', async ({ page }) => {
    await loginAdmin(page);
    await page.click('#btn-admin-monitoras');
    await expect(page.locator('#sec-admin-monitoras')).toBeVisible();

    const email = `mon.e2e.${Date.now()}@escola.edu.br`;
    const cpf = String(Date.now()).slice(-9).replace(/(\d{3})(\d{3})(\d{3})/, '$1.$2.$3-00');
    await page.fill('#mon-login', email);
    await page.fill('#mon-senha', 'senha123');
    await page.fill('#mon-cpf', cpf);
    await page.fill('#mon-classes', 'Turma E2E');

    const criarResp = page.waitForResponse(r => r.url().includes('/api/usuarios') && r.request().method() === 'POST');
    await page.click('button:has-text("Cadastrar Monitora")');
    await criarResp;

    await expect(page.locator('#tabela-monitoras')).toContainText(email);
  });

  test('abre painel de vínculos de uma monitora', async ({ page }) => {
    await loginAdmin(page);
    await page.click('#btn-admin-monitoras');
    await expect(page.locator('#tabela-monitoras tr').first()).toBeVisible();
    // Clica no botão de vincular (ícone de link) da primeira monitora
    await page.locator('#tabela-monitoras tr').first().locator('button[title="Vincular alunos"]').click();
    await expect(page.locator('#painel-vinculos')).toBeVisible();
    await expect(page.locator('#vinc-aluno-select')).toBeVisible();
  });

  test('admin cria e depois remove uma monitora pela interface', async ({ page }) => {
    await loginAdmin(page);
    await page.click('#btn-admin-monitoras');
    await expect(page.locator('#sec-admin-monitoras')).toBeVisible();

    const email = `mon.del.${Date.now()}@escola.edu.br`;
    const cpf = String(Date.now()).slice(-9).replace(/(\d{3})(\d{3})(\d{3})/, '$1.$2.$3-11');
    await page.fill('#mon-login', email);
    await page.fill('#mon-senha', 'senha123');
    await page.fill('#mon-cpf', cpf);
    await page.fill('#mon-classes', 'Turma Del');
    const criar = page.waitForResponse(r => r.url().includes('/api/usuarios') && r.request().method() === 'POST');
    await page.click('button:has-text("Cadastrar Monitora")');
    await criar;
    await expect(page.locator('#tabela-monitoras')).toContainText(email);

    // Remove essa monitora (linha que contém o email)
    const linha = page.locator('#tabela-monitoras tr', { hasText: email });
    await linha.locator('button[title="Remover"]').click();
    // Modal de confirmação estilizado
    await expect(page.locator('#sga-modal-overlay')).toBeVisible();
    const del = page.waitForResponse(r => r.url().includes('/api/usuarios/') && r.request().method() === 'DELETE');
    await page.click('#sga-modal-confirmar');
    await del;
    await page.waitForTimeout(500);
    await expect(page.locator('#tabela-monitoras')).not.toContainText(email);
  });
});

test.describe('Admin - todas as ocorrências', () => {
  test('lista a visão geral de ocorrências', async ({ page }) => {
    await loginAdmin(page);
    await page.click('#btn-admin-ocorrencias');
    await expect(page.locator('#sec-admin-ocorrencias')).toBeVisible();
    // O container deve renderizar (cards ou estado vazio), sem erro
    await expect(page.locator('#container-ocorrencias-admin')).not.toContainText('Carregando...');
  });

  test('admin remove uma ocorrência pela interface', async ({ page }) => {
    await loginAdmin(page);
    await page.click('#btn-admin-ocorrencias');
    await page.waitForResponse(r => r.url().includes('/api/ocorrencias'));
    await expect(page.locator('#container-ocorrencias-admin > div').first()).toBeVisible();

    const antes = await page.locator('#container-ocorrencias-admin > div').count();
    await page.locator('#container-ocorrencias-admin > div').first().locator('button[title="Remover"]').click();
    // Modal de confirmação estilizado
    await expect(page.locator('#sga-modal-overlay')).toBeVisible();
    const del = page.waitForResponse(r => r.url().includes('/api/ocorrencias/') && r.request().method() === 'DELETE');
    await page.click('#sga-modal-confirmar');
    await del;
    await page.waitForTimeout(500);
    const depois = await page.locator('#container-ocorrencias-admin > div').count();
    expect(depois).toBeLessThan(antes);
  });

  test('admin edita uma ocorrência pelo modal', async ({ page }) => {
    await loginAdmin(page);
    await page.click('#btn-admin-ocorrencias');
    await page.waitForResponse(r => r.url().includes('/api/ocorrencias'));
    await expect(page.locator('#container-ocorrencias-admin > div').first()).toBeVisible();

    await page.locator('#container-ocorrencias-admin > div').first().locator('button[title="Editar"]').click();
    await expect(page.locator('#sga-modal-overlay')).toBeVisible();

    const novoTexto = 'Fatos editados via modal ' + Date.now();
    // primeiro campo do modal = descricao_fatos (textarea)
    await page.locator('#sga-modal-campos textarea').fill(novoTexto);
    const put = page.waitForResponse(r => r.url().includes('/api/ocorrencias/') && r.request().method() === 'PUT');
    await page.click('#sga-modal-confirmar');
    await put;
    await page.waitForTimeout(500);
    await expect(page.locator('#container-ocorrencias-admin')).toContainText(novoTexto);
  });
});

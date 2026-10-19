<?php

declare(strict_types=1);

/**
 * Suite de Testes Automatizados - Sistema de Gestão Social e Assistencial
 * 
 * Executa testes unitários e de integração de regras de negócio sem dependência
 * de bibliotecas externas ou conexões com bancos de dados.
 */

define('ROOT_PATH', dirname(__DIR__));

// Define diretório de sessão temporário para ambiente CLI / Windows
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.save_path', sys_get_temp_dir());
}

require_once ROOT_PATH . '/app/Core/Autoloader.php';
require_once ROOT_PATH . '/app/Core/polyfills.php';
\App\Core\Autoloader::register();
\App\Core\Autoloader::addNamespace('App', ROOT_PATH . '/app');

\App\Core\Session::start();

// Carrega injeção de dependências
require_once ROOT_PATH . '/config/app.php';

use App\Core\Container;
use App\Core\Session;
use App\Helpers\SecurityHelper;
use App\Models\Usuario;
use App\Services\AtendimentoService;
use App\Services\AvaliacaoService;
use App\Services\BeneficioService;
use App\Services\DoacaoService;
use App\Services\EstoqueService;
use App\Services\FamiliaService;
use App\Services\PessoaService;
use App\Services\ProjetoService;
use App\Validators\FormValidator;

class SimpleTestRunner
{
    private int $passed = 0;
    private int $failed = 0;
    private array $errors = [];

    public function run(string $description, callable $test): void
    {
        echo "Executando: {$description}... ";
        try {
            $test();
            echo "\033[32m[PASSOU]\033[0m\n";
            $this->passed++;
        } catch (\Throwable $e) {
            echo "\033[31m[FALHOU]\033[0m\n";
            echo "   Erro: " . $e->getMessage() . " (" . $e->getFile() . ":" . $e->getLine() . ")\n";
            $this->failed++;
            $this->errors[] = [
                'test' => $description,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ];
        }
    }

    public function assert(bool $condition, string $message = 'Asserção falhou'): void
    {
        if (!$condition) {
            throw new \AssertionError($message);
        }
    }

    public function assertEquals(mixed $expected, mixed $actual, string $message = ''): void
    {
        if ($expected !== $actual) {
            $expStr = var_export($expected, true);
            $actStr = var_export($actual, true);
            throw new \AssertionError($message ?: "Esperado {$expStr}, obtido {$actStr}");
        }
    }

    public function printSummary(): int
    {
        echo "\n" . str_repeat('=', 60) . "\n";
        echo "RESUMO DA SUÍTE DE TESTES\n";
        echo str_repeat('=', 60) . "\n";
        echo "Total de testes executados: " . ($this->passed + $this->failed) . "\n";
        echo "Sucessos: \033[32m{$this->passed}\033[0m\n";
        echo "Falhas:   " . ($this->failed > 0 ? "\033[31m{$this->failed}\033[0m" : "0") . "\n";

        if ($this->failed > 0) {
            echo "\nDetalhes das Falhas:\n";
            foreach ($this->errors as $err) {
                echo "- {$err['test']}: {$err['message']}\n";
            }
            return 1;
        }

        echo "\n\033[32mTODAS AS REGRAS DE NEGÓCIO E VALIDAÇÕES PASSARAM COM SUCESSO!\033[0m\n\n";
        return 0;
    }
}

$t = new SimpleTestRunner();

// ==========================================
// 1. VALIDAÇÃO DE ENTRADAS E SEGURANÇA
// ==========================================

$t->run('FormValidator: Campos obrigatórios', function() use ($t) {
    $v = new FormValidator(['nome' => '']);
    $v->required('nome', 'Nome');
    $t->assert(!$v->isValid(), 'Deveria falhar para string vazia');
    $t->assert(!empty($v->getErrors()['nome']), 'Deveria conter erro no campo nome');

    $v2 = new FormValidator(['nome' => 'Maria Silva']);
    $v2->required('nome', 'Nome');
    $t->assert($v2->isValid(), 'Deveria passar com string preenchida');
});

$t->run('FormValidator: Validação de CPF com dígito verificador e opcionalidade', function() use ($t) {
    // CPF é opcional no sistema socioassistencial
    $v = new FormValidator(['cpf_opcional' => '']);
    $v->cpf('cpf_opcional', 'CPF', false);
    $t->assert($v->isValid(), 'CPF vazio não deve gerar erro quando opcional');

    // CPF válido (algoritmo Módulo 11)
    $vValido = new FormValidator(['cpf' => '111.444.777-35']);
    $vValido->cpf('cpf', 'CPF', false);
    $t->assert($vValido->isValid(), 'CPF com dígito verificador válido deveria passar');

    // CPF falso com dígitos repetidos ou soma errada
    $vInvalido = new FormValidator(['cpf' => '111.111.111-11']);
    $vInvalido->cpf('cpf', 'CPF', false);
    $t->assert(!$vInvalido->isValid(), 'CPF de dígitos repetidos deve ser rejeitado');

    $vInvalido2 = new FormValidator(['cpf' => '123.456.789-00']);
    $vInvalido2->cpf('cpf', 'CPF', false);
    $t->assert(!$vInvalido2->isValid(), 'CPF com DV errado deve ser rejeitado');
});

$t->run('FormValidator: Validação de CEP brasileiro', function() use ($t) {
    $v = new FormValidator(['cep' => '01001-000']);
    $v->cep('cep', 'CEP');
    $t->assert($v->isValid(), 'CEP 01001-000 deveria ser válido');

    $v2 = new FormValidator(['cep' => '1234']);
    $v2->cep('cep', 'CEP');
    $t->assert(!$v2->isValid(), 'CEP incompleto deve falhar');
});

$t->run('SecurityHelper: Escape de XSS e hashing seguro de senhas', function() use ($t) {
    $xss = '<script>alert("hack")</script>';
    $escaped = SecurityHelper::escape($xss);
    $t->assertEquals('&lt;script&gt;alert(&quot;hack&quot;)&lt;/script&gt;', $escaped, 'Deve converter tags em entidades HTML');

    $senha = 'SenhaForte123!';
    $hash = SecurityHelper::hashPassword($senha);
    $t->assert(SecurityHelper::verifyPassword($senha, $hash), 'Hash deve verificar com password_verify');
    $t->assert(!SecurityHelper::verifyPassword('senhaErrada', $hash), 'Senha incorreta não deve verificar');
});

$t->run('CSRF: Geração e validação de token de sessão', function() use ($t) {
    $token = Session::csrfToken();
    $t->assert(!empty($token), 'Token não pode ser vazio');
    $t->assert(Session::validateCsrf($token), 'Token gerado deve ser válido');
    $t->assert(!Session::validateCsrf('token_forjado_invalido'), 'Token falso deve ser rejeitado');
});

// ==========================================
// 2. MÓDULOS DE DOMÍNIO E REPOSITÓRIOS DEMO
// ==========================================

$t->run('FamiliaService: Criação de família com código sequencial', function() use ($t) {
    /** @var FamiliaService $service */
    $service = Container::resolve(FamiliaService::class);

    $res = $service->criar([
        'pessoaReferenciaNome' => 'Joana dos Santos Teste',
        'cep' => '04000-000',
        'logradouro' => 'Rua Teste Unitário',
        'numero' => '100',
        'bairro' => 'Centro',
        'municipio' => 'São Paulo',
        'uf' => 'SP',
        'rendaFamiliar' => '1200.00',
        'despesasMensais' => '800.00',
        'situacaoMoradia' => 'Alugada',
        'situacaoCadastral' => 'Ativa'
    ]);

    $t->assert($res['success'] === true, 'Criação da família deve retornar sucesso');
    $fam = $res['familia'];
    $t->assert($fam->id > 0, 'ID gerado deve ser maior que zero');
    $t->assertEquals('Joana dos Santos Teste', $fam->pessoaReferenciaNome);
    $t->assert(str_starts_with($fam->codigo, 'FAM-'), 'Código deve ter prefixo FAM-');
});

$t->run('PessoaService & Vínculos: Preservação de vínculos históricos ao desvincular', function() use ($t) {
    /** @var FamiliaService $familiaService */
    $familiaService = Container::resolve(FamiliaService::class);
    /** @var PessoaService $pessoaService */
    $pessoaService = Container::resolve(PessoaService::class);

    // 1. Cadastra nova família
    $resFam = $familiaService->criar([
        'pessoaReferenciaNome' => 'Família Teste Vínculo',
        'cep' => '01001-000',
        'logradouro' => 'Rua Teste Vínculo',
        'numero' => '50',
        'bairro' => 'Bela Vista',
        'municipio' => 'São Paulo',
        'uf' => 'SP',
        'rendaFamiliar' => '1500.00',
        'despesasMensais' => '900.00',
        'situacaoMoradia' => 'Alugada',
        'situacaoCadastral' => 'Ativa'
    ]);
    $familiaId = $resFam['familia']->id;

    // 2. Vincula integrante à Família
    $resVinculo = $familiaService->vincularIntegrante($familiaId, [
        'nomeCompleto' => 'Pedro Teste Histórico',
        'dataNascimento' => '2010-05-15',
        'cpf' => '', // Sem CPF (permitido)
        'parentesco' => 'Filho(a)',
        'escolaridade' => 'Ensino Fundamental Incompleto',
        'ocupacao' => 'Estudante'
    ]);

    $t->assert($resVinculo['success'] === true, 'Vínculo do integrante deve ser criado');
    $vinculo = $resVinculo['vinculo'];
    $pessoaId = $vinculo->pessoaId;

    // 3. Verifica vínculo ativo na ficha da família
    $integrantesAtivos = $familiaService->obterPorId($familiaId);
    $ficha = $familiaService->obterFichaCompleta($familiaId);
    $encontrado = false;
    foreach ($ficha['integrantes'] as $item) {
        if ($item->pessoaId === $pessoaId && $item->ativo) {
            $encontrado = true;
            break;
        }
    }
    $t->assert($encontrado, 'Pessoa deve constar nos integrantes ativos da família');

    // 4. Desvincula da Família (ex: emancipação ou mudança de núcleo)
    $desvinculou = $familiaService->desvincularIntegrante($vinculo->id, 'Mudança de residência');
    $t->assert($desvinculou, 'Desvinculação deve retornar true');

    // 5. REGRA CRÍTICA: Histórico de vínculos passados da pessoa NÃO pode ser apagado!
    $historico = $pessoaService->obterHistoricoVinculos($pessoaId);
    $t->assert(count($historico) > 0, 'Histórico de vínculos da pessoa deve ser preservado');
    $t->assert(!$historico[0]['vinculo']->ativo, 'Vínculo histórico deve estar marcado como inativo');
    $t->assert(!empty($historico[0]['vinculo']->dataTermino), 'Data de término do vínculo deve estar preenchida');
});

$t->run('AvaliacaoService: Cálculo de renda per capita e sugestão de vulnerabilidade', function() use ($t) {
    /** @var AvaliacaoService $service */
    $service = Container::resolve(AvaliacaoService::class);

    // Teste 1: Renda per capita R$ 150 (extrema pobreza), insegurança grave, área de risco -> Crítica
    $classificacao = $service->calcularSugestaoVulnerabilidade(
        150.0,
        'Insegurança Grave',
        'Área de Risco',
        'Desemprego total',
        true,
        true,
        false,
        ['Alimentação', 'Saúde']
    );
    $t->assertEquals('Crítica', $classificacao, 'Critérios graves devem sugerir vulnerabilidade Crítica');

    // Teste 2: Renda per capita de R$ 600, insegurança leve, moradia regular -> Média
    $classificacao2 = $service->calcularSugestaoVulnerabilidade(
        600.0,
        'Insegurança Leve',
        'Regular',
        'Informal / Bicos',
        false,
        false,
        false,
        ['Capacitação']
    );
    $t->assertEquals('Média', $classificacao2, 'Renda moderada deve sugerir vulnerabilidade Média');

    // Teste 3: Renda per capita de R$ 1500, segurança alimentar -> Baixa
    $classificacao3 = $service->calcularSugestaoVulnerabilidade(
        1500.0,
        'Segurança Alimentar',
        'Adequada',
        'Formal',
        false,
        false,
        false,
        []
    );
    $t->assertEquals('Baixa', $classificacao3, 'Renda per capita estável deve sugerir vulnerabilidade Baixa');
});

$t->run('BeneficioService: Proibição de confirmação de entrega sem itens (REGRA ESSENCIAL)', function() use ($t) {
    /** @var BeneficioService $service */
    $service = Container::resolve(BeneficioService::class);

    // Tentar criar entrega efetivada com lista de itens vazia DEVE falhar
    $res = $service->registrarEntrega([
        'familiaId' => 1,
        'tipoBeneficio' => 'Cesta Básica',
        'dataEntrega' => date('Y-m-d'),
        'responsavelDistribuicao' => 'Assistente Social',
        'situacao' => 'Efetivada',
        'itens' => [] // VAZIO
    ]);

    $t->assert($res['success'] === false, 'Entrega efetivada sem itens não pode ter sucesso');
    $t->assert(!empty($res['errors']['itens']), 'Deve conter erro explícito de itens');
});

$t->run('BeneficioService: Efetivação de entrega com baixa automática no estoque demonstrativo', function() use ($t) {
    /** @var BeneficioService $service */
    $service = Container::resolve(BeneficioService::class);
    /** @var EstoqueService $estoqueService */
    $estoqueService = Container::resolve(EstoqueService::class);

    // Produto 1 tem estoque inicial
    $produtoAntes = $estoqueService->obterProdutoPorId(1);
    $saldoAntes = $produtoAntes->estoqueAtual;

    // Criar e efetivar entrega de 2 unidades
    $res = $service->registrarEntrega([
        'familiaId' => 1,
        'tipoBeneficio' => 'Cesta Básica Familiar',
        'dataEntrega' => date('Y-m-d'),
        'responsavelDistribuicao' => 'Assistente Social Teste',
        'situacao' => 'Efetivada',
        'itens' => [
            ['produtoId' => 1, 'quantidade' => 2, 'lote' => 'LOTE-TESTE']
        ]
    ]);

    $t->assert($res['success'] === true, 'Entrega com itens válidos deve ser registrada');
    $entrega = $res['entrega'];
    $t->assertEquals('Efetivada', $entrega->situacao, 'Entrega deve estar Efetivada');

    // Verifica que o saldo foi debitado em 2 unidades no estoque
    $produtoDepois = $estoqueService->obterProdutoPorId(1);
    $t->assertEquals($saldoAntes - 2, $produtoDepois->estoqueAtual, 'Saldo de estoque deve ter sido reduzido em 2 unidades');
});

$t->run('EstoqueService: Registro de movimentação e verificação de estoque baixo', function() use ($t) {
    /** @var EstoqueService $service */
    $service = Container::resolve(EstoqueService::class);

    // Produto 4 (Leite em Pó) tem estoque mínimo 30 e saldo 8 -> Crítico
    $alertas = $service->obterProdutosCriticos();
    $encontrouAlerta = false;
    foreach ($alertas as $p) {
        if ($p->id === 4) {
            $encontrouAlerta = true;
            break;
        }
    }
    $t->assert($encontrouAlerta, 'Produto com estoque abaixo do mínimo deve constar em alertas');

    $produtoAntes = $service->obterProdutoPorId(4);
    $saldoAntes = $produtoAntes->estoqueAtual;

    // Registrar Entrada de 50 latas de Leite
    $resMov = $service->registrarMovimentacao([
        'produtoId' => 4,
        'tipo' => 'Entrada',
        'quantidade' => 50,
        'responsavel' => 'Operador Teste',
        'justificativa' => 'Doação da Comunidade',
        'data' => date('Y-m-d')
    ]);

    $t->assert($resMov['success'] === true, 'Movimentação de entrada deve ser registrada com sucesso');
    $produtoAtualizado = $service->obterProdutoPorId(4);
    $t->assertEquals($saldoAntes + 50, $produtoAtualizado->estoqueAtual, 'Saldo após entrada deve somar 50 ao saldo anterior');
    $t->assert(!$produtoAtualizado->isEstoqueBaixo(), 'Com saldo atualizado acima do mínimo, não deve mais estar em alerta');
});

$t->run('DoacaoService: Segregação conceitual entre doação financeira e de produtos', function() use ($t) {
    /** @var DoacaoService $service */
    $service = Container::resolve(DoacaoService::class);

    // Doação Financeira (sem itens físicos)
    $resFin = $service->registrar([
        'nomeDoador' => 'Empresa Solidária LTDA',
        'tipoDoador' => 'Pessoa Jurídica',
        'tipoDoacao' => 'Financeira',
        'valorEstimado' => 5000.00,
        'descricao' => 'Aporte para reforma de salas comunitárias',
        'dataRecebimento' => date('Y-m-d'),
        'responsavel' => 'Coordenador Financeiro'
    ]);
    $t->assert($resFin['success'] === true, 'Doação financeira deve ser registrada');
    $doacaoFin = $resFin['doacao'];
    $t->assertEquals('Financeira', $doacaoFin->tipoDoacao);
    $t->assertEquals(5000.00, $doacaoFin->valorEstimado);

    // Doação de Produtos
    $resProd = $service->registrar([
        'nomeDoador' => 'Supermercado Central',
        'tipoDoador' => 'Pessoa Jurídica',
        'tipoDoacao' => 'Produtos',
        'descricao' => 'Alimentos e leite para assistência',
        'dataRecebimento' => date('Y-m-d'),
        'responsavel' => 'Operador Almoxarifado',
        'itens' => [
            ['produtoId' => 1, 'nomeProduto' => 'Cesta Básica Tipo 1', 'quantidade' => 10, 'unidadeMedida' => 'Cesta'],
            ['produtoId' => 4, 'nomeProduto' => 'Leite em Pó 400g', 'quantidade' => 20, 'unidadeMedida' => 'Lata']
        ]
    ]);
    $t->assert($resProd['success'] === true, 'Doação de produtos deve ser registrada');
    $doacaoProd = $service->obterPorId($resProd['doacao']->id);
    $t->assertEquals('Produtos', $doacaoProd->tipoDoacao);
    $t->assertEquals(2, count($doacaoProd->itens), 'Doação de produtos deve possuir itens discriminados');
});

$t->run('ProjetoService: Progressão de participantes (Inscrito -> Convocado -> Atendido -> Ausente)', function() use ($t) {
    /** @var ProjetoService $service */
    $service = Container::resolve(ProjetoService::class);

    // Inscrever Pessoa no Projeto 1
    $resInscricao = $service->inscreverParticipante(1, [
        'nomePessoa' => 'Ana Beatriz Teste',
        'situacaoParticipacao' => 'Inscrito'
    ]);
    $t->assert($resInscricao['success'] === true, 'Participante deve ser inscrito');
    $participante = $resInscricao['participante'];
    $t->assertEquals('Inscrito', $participante->situacaoParticipacao);

    // Convocação
    $service->atualizarStatusParticipante($participante->id, 'Convocado');
    $proj = $service->obterPorId(1);
    $parts = $proj->participantes;
    $pAtualizado = null;
    foreach ($parts as $p) {
        if ($p->id === $participante->id) {
            $pAtualizado = $p;
            break;
        }
    }
    $t->assert($pAtualizado !== null, 'Participante deve ser localizado no projeto');
    $t->assertEquals('Convocado', $pAtualizado->situacaoParticipacao);

    // Efetivamente Atendido
    $service->atualizarStatusParticipante($participante->id, 'Efetivamente Atendido');
    $proj2 = $service->obterPorId(1);
    $pAtendido = null;
    foreach ($proj2->participantes as $p) {
        if ($p->id === $participante->id) {
            $pAtendido = $p;
            break;
        }
    }
    $t->assertEquals('Efetivamente Atendido', $pAtendido->situacaoParticipacao);
    $t->assert(!empty($pAtendido->dataPresenca), 'Data de presença deve ser registrada ao ser efetivamente atendido');
});

$t->run('RBAC: Controle de acesso e autorização por perfil de usuário', function() use ($t) {
    $admin = new Usuario(1, 'Admin', 'admin@org.br', Usuario::PERFIL_ADMIN);
    $assistente = new Usuario(3, 'Assistente Social', 'social@org.br', Usuario::PERFIL_ASSISTENTE_SOCIAL);
    $estoque = new Usuario(4, 'Estoque', 'estoque@org.br', Usuario::PERFIL_ESTOQUE);
    $consulta = new Usuario(6, 'Consulta', 'consulta@org.br', Usuario::PERFIL_CONSULTA);

    // Admin pode tudo
    $t->assert($admin->can('familias.create'), 'Admin deve ter permissão');
    $t->assert($admin->can('avaliacoes.sensitive'), 'Admin deve ter permissão');
    $t->assert($admin->can('estoque.move'), 'Admin deve ter permissão');

    // Assistente Social
    $t->assert($assistente->can('avaliacoes.sensitive'), 'Assistente social deve acessar dados sensíveis');
    $t->assert($assistente->can('familias.create'), 'Assistente social pode cadastrar famílias');
    $t->assert(!$assistente->can('estoque.move'), 'Assistente social NÃO deve movimentar almoxarifado diretamente');

    // Estoque
    $t->assert($estoque->can('estoque.move'), 'Estoque pode movimentar almoxarifado');
    $t->assert(!$estoque->can('familias.create'), 'Estoque NÃO pode criar famílias');
    $t->assert(!$estoque->can('avaliacoes.sensitive'), 'Estoque NÃO pode ver avaliações sensíveis');

    // Consulta
    $t->assert($consulta->can('familias.view'), 'Consulta pode ver famílias');
    $t->assert(!$consulta->can('familias.create'), 'Consulta NÃO pode criar famílias');
    $t->assert(!$consulta->can('beneficios.create'), 'Consulta NÃO pode criar entregas');
});

$t->run('LGPD & Anonimização: Mascaramento de dados pessoais (Art. 13 da LGPD)', function() use ($t) {
    // Pseudonimização de nomes
    $nome = 'Maria de Souza Silva';
    $anonimizado = \App\Helpers\FormatHelper::anonymizeName($nome);
    $t->assert(str_contains($anonimizado, 'M.') && str_contains($anonimizado, 'S.'), 'Deve conter iniciais das palavras');
    $t->assert(!str_contains($anonimizado, 'Souza'), 'Não deve exibir sobrenome em texto claro');
    $t->assert(str_contains($anonimizado, 'LGPD'), 'Deve carregar indicador de conformidade LGPD');

    // Mascaramento de CPF
    $cpf = '12345678901';
    $maskedCpf = \App\Helpers\FormatHelper::maskCpf($cpf);
    $t->assertEquals('***.456.789-**', $maskedCpf, 'CPF deve ser mascarado preservando apenas dígitos centrais');
});

$t->run('RelatorioService: Consolidação de indicadores gerenciais socioassistenciais', function() use ($t) {
    /** @var \App\Services\RelatorioService $service */
    $service = \App\Core\Container::resolve(\App\Services\RelatorioService::class);
    $indicadores = $service->obterIndicadoresGerais();

    $t->assert(isset($indicadores['totalFamilias']), 'Deve retornar total de famílias');
    $t->assert(isset($indicadores['totalPessoas']), 'Deve retornar total de pessoas');
    $t->assert(isset($indicadores['criancasInfancia']), 'Deve retornar quantitativo de crianças');
    $t->assert(isset($indicadores['totalEntregas']), 'Deve retornar total de entregas');
    $t->assert(isset($indicadores['totalEstoque']), 'Deve retornar total de itens no almoxarifado');
    $t->assert($indicadores['totalFamilias'] >= 0, 'Total de famílias deve ser não-negativo');
});

$t->run('Auditoria & Rastreabilidade LGPD: Registro de visualização e exportação de relatórios', function() use ($t) {
    /** @var \App\Services\AuditLogService $auditService */
    $auditService = \App\Core\Container::resolve(\App\Services\AuditLogService::class);
    
    // Simula log de consulta
    $auditService->log('CONSULTA_RELATORIO', 'Relatorio', 'familias', 'Consulta teste de relatório LGPD');
    $logs = $auditService->listarTodos(['entidade' => 'Relatorio', 'operacao' => 'CONSULTA_RELATORIO']);

    $t->assert(count($logs) > 0, 'Deve registrar e permitir filtro por entidade Relatorio');
    $ultimo = $logs[0];
    $t->assertEquals('CONSULTA_RELATORIO', $ultimo->operacao);
    $t->assertEquals('Relatorio', $ultimo->entidade);
});

$t->run('FormValidator: Validação de endereço de e-mail', function() use ($t) {
    $v1 = new FormValidator(['email' => '']);
    $v1->email('email', 'E-mail', false);
    $t->assert($v1->isValid(), 'E-mail vazio opcional deve ser válido');

    $v2 = new FormValidator(['email' => '']);
    $v2->email('email', 'E-mail', true);
    $t->assert(!$v2->isValid(), 'E-mail obrigatório vazio deve falhar');

    $v3 = new FormValidator(['email' => 'contato@gestaosocial.org.br']);
    $v3->email('email', 'E-mail', true);
    $t->assert($v3->isValid(), 'E-mail em formato RFC válido deve passar');

    $v4 = new FormValidator(['email' => 'invalido@@email..com']);
    $v4->email('email', 'E-mail', true);
    $t->assert(!$v4->isValid(), 'E-mail com formato inválido deve ser rejeitado');
});

$t->run('FormatHelper: Tratamento de data e datetime com retrocompatibilidade', function() use ($t) {
    $dt = '2026-10-19 14:30:00';
    $t->assertEquals('19/10/2026', \App\Helpers\FormatHelper::date($dt), 'date padrão deve formatar d/m/Y');
    $t->assertEquals('19/10/2026 14:30', \App\Helpers\FormatHelper::datetime($dt), 'datetime deve formatar d/m/Y H:i');
    $t->assertEquals('19/10/2026 14:30', \App\Helpers\FormatHelper::date($dt, true), 'date com true deve delegar para datetime');
    $t->assertEquals('-', \App\Helpers\FormatHelper::date(null), 'date com null deve retornar traço');
});

$t->run('AuthService: Autenticação segura com verificação de senha bcrypt', function() use ($t) {
    /** @var \App\Services\AuthService $auth */
    $auth = \App\Core\Container::resolve(\App\Services\AuthService::class);

    // Tentativa com senha errada
    $falha = $auth->login('admin@gestaosocial.org', 'senha_errada');
    $t->assert(!$falha, 'Login com senha incorreta deve retornar false');

    // Tentativa com credenciais válidas do usuário demo
    $sucesso = $auth->login('admin@gestaosocial.org', 'demo123');
    $t->assert($sucesso, 'Login com senha correta deve ter sucesso');
    $t->assert($auth->check(), 'Auth::check deve retornar true após login');
    $t->assertEquals('Administrador', $auth->user()->perfil);

    // Logout
    $auth->logout();
    $t->assert(!$auth->check(), 'Auth::check deve retornar false após logout');
});

// Finaliza e imprime relatório
$exitCode = $t->printSummary();
exit($exitCode);


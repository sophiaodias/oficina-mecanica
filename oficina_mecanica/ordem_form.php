<?php
require_once __DIR__ . '/includes/cabecalho.php';

$clientes = listarClientes($pdo);
$mecanicos = listarMecanicos($pdo);
$ordemAtual = null;

if (isset($_GET['id']) && (int)$_GET['id'] > 0) {
    $id = (int)$_GET['id'];
    $sql = 'SELECT * FROM ordens_servico WHERE id = :id LIMIT 1';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    $ordemAtual = $stmt->fetch();

    if (!$ordemAtual) {
        $_SESSION['mensagem'] = 'Ordem de serviço não encontrada.';
        header('Location: ordens.php');
        exit;
    }

    if ($_SESSION['usuario_perfil'] === 'mecanico' && (int)$ordemAtual['mecanico_id'] !== (int)$_SESSION['usuario_id']) {
        $_SESSION['mensagem'] = 'Você só pode alterar ordens atribuídas a você.';
        header('Location: ordens.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $clienteId = (int)($_POST['cliente_id'] ?? 0);
    $veiculoId = (int)($_POST['veiculo_id'] ?? 0);
    $mecanicoId = (int)($_POST['mecanico_id'] ?? 0);
    $descricaoServico = trim($_POST['descricao_servico'] ?? '');
    $dataAgendada = $_POST['data_agendada'] ?? '';
    $horarioAgendado = $_POST['horario_agendado'] ?? '';
    $situacao = $_POST['situacao'] ?? 'Pendente';
    $observacoesExecucao = trim($_POST['observacoes_execucao'] ?? '');

    if ($_SESSION['usuario_perfil'] === 'mecanico') {
        if ($id <= 0 || (int)($ordemAtual['mecanico_id'] ?? 0) !== (int)$_SESSION['usuario_id']) {
            $_SESSION['mensagem'] = 'Você não tem permissão para alterar ordens de serviço.';
            header('Location: ordens.php');
            exit;
        }
        $mecanicoId = (int)$_SESSION['usuario_id'];
    } elseif ($_SESSION['usuario_perfil'] !== 'administrador') {
        $_SESSION['mensagem'] = 'Você não tem permissão para alterar ordens de serviço.';
        header('Location: ordens.php');
        exit;
    }

    if ($clienteId <= 0 || $veiculoId <= 0 || $mecanicoId <= 0 || $descricaoServico === '' || $dataAgendada === '' || $horarioAgendado === '') {
        $_SESSION['mensagem'] = 'Preencha todos os campos da ordem de serviço.';
        header('Location: ordem_form.php' . ($id > 0 ? '?id=' . $id : ''));
        exit;
    }

    if (verificarConflitoAgendamento($pdo, $mecanicoId, $dataAgendada, $horarioAgendado, $id > 0 ? $id : null)) {
        $_SESSION['mensagem'] = 'Agendamento não permitido: o mecânico selecionado já possui uma ordem de serviço nesse horário. Selecione outro horário ou mecânico.';
        header('Location: ordem_form.php' . ($id > 0 ? '?id=' . $id : ''));
        exit;
    }

    if ($id > 0) {
        $sql = 'UPDATE ordens_servico SET cliente_id = :cliente_id, veiculo_id = :veiculo_id, mecanico_id = :mecanico_id, descricao_servico = :descricao_servico, data_agendada = :data_agendada, horario_agendado = :horario_agendado, situacao = :situacao, observacoes_execucao = :observacoes_execucao WHERE id = :id';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':cliente_id' => $clienteId,
            ':veiculo_id' => $veiculoId,
            ':mecanico_id' => $mecanicoId,
            ':descricao_servico' => $descricaoServico,
            ':data_agendada' => $dataAgendada,
            ':horario_agendado' => $horarioAgendado,
            ':situacao' => $situacao,
            ':observacoes_execucao' => $observacoesExecucao,
            ':id' => $id,
        ]);
        $acao = 'Atualização de ordem';
        $descricao = 'Atualizou a ordem ' . $id . '.';
    } else {
        $numeroOrdem = gerarNumeroOrdem($pdo);
        $sql = 'INSERT INTO ordens_servico (numero_ordem, cliente_id, veiculo_id, mecanico_id, descricao_servico, data_agendada, horario_agendado, situacao, observacoes_execucao)
                VALUES (:numero_ordem, :cliente_id, :veiculo_id, :mecanico_id, :descricao_servico, :data_agendada, :horario_agendado, :situacao, :observacoes_execucao)';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':numero_ordem' => $numeroOrdem,
            ':cliente_id' => $clienteId,
            ':veiculo_id' => $veiculoId,
            ':mecanico_id' => $mecanicoId,
            ':descricao_servico' => $descricaoServico,
            ':data_agendada' => $dataAgendada,
            ':horario_agendado' => $horarioAgendado,
            ':situacao' => $situacao,
            ':observacoes_execucao' => $observacoesExecucao,
        ]);
        $acao = 'Cadastro de ordem';
        $descricao = 'Cadastrou a ordem ' . $numeroOrdem . '.';
    }

    registrarOperacao($pdo, $_SESSION['usuario_id'], $acao, $descricao);
    $_SESSION['mensagem'] = 'Ordem de serviço salva com sucesso.';
    header('Location: ordens.php');
    exit;
}

$clienteAtivo = isset($_GET['cliente']) ? (int)$_GET['cliente'] : ((int)($ordemAtual['cliente_id'] ?? 0));
$ordemMecanicoAtivo = (int)($ordemAtual['mecanico_id'] ?? 0);

if ($clienteAtivo > 0) {
    $veiculosFiltrados = $pdo->prepare('SELECT * FROM veiculos WHERE cliente_id = :cliente_id ORDER BY marca ASC, modelo ASC');
    $veiculosFiltrados->execute([':cliente_id' => $clienteAtivo]);
    $veiculosLista = $veiculosFiltrados->fetchAll();
} else {
    $veiculosLista = [];
}
?>

<div class="painel">
    <h2><?php echo $ordemAtual ? 'Editar ordem de serviço' : 'Cadastrar ordem de serviço'; ?></h2>

    <form method="POST" class="formulario">
        <input type="hidden" name="id" value="<?php echo (int)($ordemAtual['id'] ?? 0); ?>">

        <div class="linha-dupla">
            <div>
                <label for="cliente_id">Cliente</label>
                <select id="cliente_id" name="cliente_id" required onchange="carregarVeiculos(this.value)">
                    <option value="">Selecione</option>
                    <?php foreach ($clientes as $cliente): ?>
                        <option value="<?php echo (int)$cliente['id']; ?>" <?php echo ((int)$clienteAtivo === (int)$cliente['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cliente['nome']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="veiculo_id">Veículo</label>
                <select id="veiculo_id" name="veiculo_id" required>
                    <option value="">Selecione</option>
                    <?php foreach ($veiculosLista as $veiculo): ?>
                        <option value="<?php echo (int)$veiculo['id']; ?>" <?php echo ((int)($ordemAtual['veiculo_id'] ?? 0) === (int)$veiculo['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($veiculo['marca'] . ' ' . $veiculo['modelo'] . ' - ' . $veiculo['placa']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div>
            <label for="descricao_servico">Descrição do serviço</label>
            <textarea id="descricao_servico" name="descricao_servico" rows="4" required><?php echo htmlspecialchars($ordemAtual['descricao_servico'] ?? ''); ?></textarea>
        </div>

        <div class="linha-dupla">
            <div>
                <label for="mecanico_id">Mecânico responsável</label>
                <select id="mecanico_id" name="mecanico_id" required <?php echo ($_SESSION['usuario_perfil'] === 'mecanico') ? 'disabled' : ''; ?>>
                    <option value="">Selecione</option>
                    <?php foreach ($mecanicos as $mecanico): ?>
                        <?php $mecanicoSelecionado = ($_SESSION['usuario_perfil'] === 'mecanico') ? (int)$_SESSION['usuario_id'] : (int)($ordemAtual['mecanico_id'] ?? 0); ?>
                        <option value="<?php echo (int)$mecanico['id']; ?>" <?php echo ((int)$mecanicoSelecionado === (int)$mecanico['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($mecanico['nome']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="situacao">Situação do serviço</label>
                <select id="situacao" name="situacao" required>
                    <?php $situacoes = ['Pendente', 'Em andamento', 'Concluída']; ?>
                    <?php foreach ($situacoes as $situacao): ?>
                        <option value="<?php echo $situacao; ?>" <?php echo (($ordemAtual['situacao'] ?? 'Pendente') === $situacao) ? 'selected' : ''; ?>>
                            <?php echo $situacao; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="linha-dupla">
            <div>
                <label for="data_agendada">Data agendada</label>
                <input type="date" id="data_agendada" name="data_agendada" value="<?php echo htmlspecialchars($ordemAtual['data_agendada'] ?? ''); ?>" required>
            </div>
            <div>
                <label for="horario_agendado">Horário agendado</label>
                <input type="time" id="horario_agendado" name="horario_agendado" value="<?php echo htmlspecialchars($ordemAtual['horario_agendado'] ?? ''); ?>" required>
            </div>
        </div>

        <div>
            <label for="observacoes_execucao">Observações da execução</label>
            <textarea id="observacoes_execucao" name="observacoes_execucao" rows="3"><?php echo htmlspecialchars($ordemAtual['observacoes_execucao'] ?? ''); ?></textarea>
        </div>

        <button type="submit" class="botao botao-principal">
            <?php echo $ordemAtual ? 'Salvar alterações' : 'Cadastrar ordem'; ?>
        </button>
    </form>
</div>

<script>
function carregarVeiculos(clienteId) {
    const veiculoSelect = document.getElementById('veiculo_id');
    if (!clienteId) {
        veiculoSelect.innerHTML = '<option value="">Selecione</option>';
        return;
    }

    const ordemId = Number(document.querySelector('input[name="id"]').value || 0);
    const destino = ordemId > 0 ? 'ordem_form.php?id=' + ordemId + '&cliente=' + clienteId : 'ordem_form.php?cliente=' + clienteId;
    window.location.href = destino;
}
</script>

<?php require_once __DIR__ . '/includes/rodape.php'; ?>
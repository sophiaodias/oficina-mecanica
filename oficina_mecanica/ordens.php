<?php
require_once __DIR__ . '/includes/cabecalho.php';

if (!in_array($_SESSION['usuario_perfil'] ?? '', ['administrador', 'mecanico'], true)) {
    $_SESSION['mensagem'] = 'Acesso negado.';
    header('Location: login.php');
    exit;
}

if (isset($_POST['acao']) && $_POST['acao'] === 'excluir') {
    if ($_SESSION['usuario_perfil'] !== 'administrador') {
        $_SESSION['mensagem'] = 'Você não tem permissão para excluir ordens de serviço.';
        header('Location: ordens.php');
        exit;
    }

    $id = (int)($_POST['id'] ?? 0);
    $sql = 'DELETE FROM ordens_servico WHERE id = :id';
    $stmt = $pdo->prepare($sql);
    try {
        $stmt->execute([':id' => $id]);
        registrarOperacao($pdo, $_SESSION['usuario_id'], 'Exclusão de ordem', 'Excluiu a ordem de código ' . $id . '.');
        $_SESSION['mensagem'] = 'Ordem de serviço excluída com sucesso.';
    } catch (PDOException $erro) {
        $_SESSION['mensagem'] = 'Não foi possível excluir a ordem de serviço.';
    }

    header('Location: ordens.php');
    exit;
}

$clienteFiltro = trim($_GET['cliente'] ?? '');
$dataFiltro = trim($_GET['data'] ?? '');
$pagina = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
$porPagina = 10;
$offset = ($pagina - 1) * $porPagina;

$condicoes = [];
$params = [];

if ($clienteFiltro !== '') {
    $condicoes[] = 'c.nome LIKE :cliente';
    $params[':cliente'] = '%' . $clienteFiltro . '%';
}

if ($dataFiltro !== '') {
    $condicoes[] = 'os.data_agendada = :data';
    $params[':data'] = $dataFiltro;
}

if ($_SESSION['usuario_perfil'] === 'mecanico') {
    $condicoes[] = 'os.mecanico_id = :usuario_id';
    $params[':usuario_id'] = (int)$_SESSION['usuario_id'];
}

$where = $condicoes ? ' WHERE ' . implode(' AND ', $condicoes) : '';

$sqlTotal = "SELECT COUNT(*) AS total
             FROM ordens_servico os
             INNER JOIN clientes c ON c.id = os.cliente_id
             {$where}";

$stmtTotal = $pdo->prepare($sqlTotal);
$stmtTotal->execute($params);
$totalRegistros = (int)$stmtTotal->fetch()['total'];
$totalPaginas = (int)ceil($totalRegistros / $porPagina);

$sqlLista = "SELECT os.*, c.nome AS cliente, u.nome AS mecanico
             FROM ordens_servico os
             INNER JOIN clientes c ON c.id = os.cliente_id
             LEFT JOIN usuarios u ON u.id = os.mecanico_id
             {$where}
             ORDER BY os.data_agendada DESC, os.horario_agendado DESC
             LIMIT :limite OFFSET :offset";

$stmtLista = $pdo->prepare($sqlLista);
foreach ($params as $chave => $valor) {
    $tipo = is_int($valor) ? PDO::PARAM_INT : PDO::PARAM_STR;
    $stmtLista->bindValue($chave, $valor, $tipo);
}
$stmtLista->bindValue(':limite', $porPagina, PDO::PARAM_INT);
$stmtLista->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmtLista->execute();
$ordens = $stmtLista->fetchAll();
?>

<div class="painel">
    <h2>Ordens de serviço</h2>

    <form method="GET" class="filtro">
        <div class="linha-dupla">
            <div>
                <label for="cliente">Filtrar por cliente</label>
                <input type="text" id="cliente" name="cliente" value="<?php echo htmlspecialchars($clienteFiltro); ?>">
            </div>
            <div>
                <label for="data">Filtrar por data</label>
                <input type="date" id="data" name="data" value="<?php echo htmlspecialchars($dataFiltro); ?>">
            </div>
        </div>
        <button type="submit" class="botao botao-principal">Filtrar</button>
    </form>

    <?php if ($_SESSION['usuario_perfil'] === 'administrador'): ?>
        <div class="acoes-topo">
            <a href="ordem_form.php" class="botao botao-principal">Nova ordem</a>
        </div>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th>Número</th>
                <th>Cliente</th>
                <th>Veículo</th>
                <th>Descrição</th>
                <th>Mecânico</th>
                <th>Data</th>
                <th>Hora</th>
                <th>Situação</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($ordens as $ordem): ?>
                <?php
                $veiculoNome = '-';
                $veiculo = $pdo->prepare('SELECT marca, modelo, placa FROM veiculos WHERE id = :id LIMIT 1');
                $veiculo->execute([':id' => $ordem['veiculo_id']]);
                $veiculoDados = $veiculo->fetch();
                if ($veiculoDados) {
                    $veiculoNome = $veiculoDados['marca'] . ' ' . $veiculoDados['modelo'] . ' / ' . $veiculoDados['placa'];
                }
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($ordem['numero_ordem']); ?></td>
                    <td><?php echo htmlspecialchars($ordem['cliente']); ?></td>
                    <td><?php echo htmlspecialchars($veiculoNome); ?></td>
                    <td><?php echo htmlspecialchars($ordem['descricao_servico']); ?></td>
                    <td><?php echo htmlspecialchars($ordem['mecanico'] ?? '-'); ?></td>
                    <td><?php echo formatarData($ordem['data_agendada']); ?></td>
                    <td><?php echo formatarHora($ordem['horario_agendado']); ?></td>
                    <td><?php echo htmlspecialchars($ordem['situacao']); ?></td>
                    <td>
                        <a href="ordem_form.php?id=<?php echo (int)$ordem['id']; ?>" class="botao">Editar</a>
                        <?php if ($_SESSION['usuario_perfil'] === 'administrador'): ?>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Deseja realmente excluir esta ordem?');">
                                <input type="hidden" name="acao" value="excluir">
                                <input type="hidden" name="id" value="<?php echo (int)$ordem['id']; ?>">
                                <button type="submit" class="botao botao-danger">Excluir</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if ($totalPaginas > 1): ?>
        <div class="paginacao">
            <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                <a href="ordens.php?cliente=<?php echo urlencode($clienteFiltro); ?>&data=<?php echo urlencode($dataFiltro); ?>&pagina=<?php echo $i; ?>" class="pagina <?php echo $i === $pagina ? 'ativa' : ''; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/rodape.php'; ?>
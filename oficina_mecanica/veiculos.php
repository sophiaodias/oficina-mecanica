<?php
require_once __DIR__ . '/includes/cabecalho.php';

if (!in_array($_SESSION['usuario_perfil'] ?? '', ['administrador', 'mecanico'], true)) {
    $_SESSION['mensagem'] = 'Acesso negado.';
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar'])) {
    if ($_SESSION['usuario_perfil'] !== 'administrador') {
        $_SESSION['mensagem'] = 'Você não tem permissão para cadastrar veículos.';
        header('Location: veiculos.php');
        exit;
    }

    $id = (int)($_POST['id'] ?? 0);
    $clienteId = (int)($_POST['cliente_id'] ?? 0);
    $marca = trim($_POST['marca'] ?? '');
    $modelo = trim($_POST['modelo'] ?? '');
    $placa = trim($_POST['placa'] ?? '');
    $ano = (int)($_POST['ano'] ?? 0);

    if ($clienteId <= 0 || $marca === '' || $modelo === '' || $placa === '' || $ano <= 0) {
        $_SESSION['mensagem'] = 'Preencha todos os dados do veículo.';
        header('Location: veiculos.php');
        exit;
    }

    if ($id > 0) {
        $sql = 'UPDATE veiculos SET cliente_id = :cliente_id, marca = :marca, modelo = :modelo, placa = :placa, ano = :ano WHERE id = :id';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':cliente_id' => $clienteId,
            ':marca' => $marca,
            ':modelo' => $modelo,
            ':placa' => $placa,
            ':ano' => $ano,
            ':id' => $id,
        ]);
        $acao = 'Atualização de veículo';
        $descricao = 'Atualizou o veículo ' . $modelo . ' - ' . $placa . '.';
    } else {
        $sql = 'INSERT INTO veiculos (cliente_id, marca, modelo, placa, ano) VALUES (:cliente_id, :marca, :modelo, :placa, :ano)';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':cliente_id' => $clienteId,
            ':marca' => $marca,
            ':modelo' => $modelo,
            ':placa' => $placa,
            ':ano' => $ano,
        ]);
        $acao = 'Cadastro de veículo';
        $descricao = 'Cadastrou o veículo ' . $modelo . ' - ' . $placa . '.';
    }

    registrarOperacao($pdo, $_SESSION['usuario_id'], $acao, $descricao);
    $_SESSION['mensagem'] = 'Veículo salvo com sucesso.';
    header('Location: veiculos.php');
    exit;
}

if (isset($_GET['acao']) && $_GET['acao'] === 'excluir' && isset($_GET['id'])) {
    if ($_SESSION['usuario_perfil'] !== 'administrador') {
        $_SESSION['mensagem'] = 'Você não tem permissão para excluir veículos.';
        header('Location: veiculos.php');
        exit;
    }

    $id = (int)$_GET['id'];
    $sql = 'DELETE FROM veiculos WHERE id = :id';
    $stmt = $pdo->prepare($sql);
    try {
        $stmt->execute([':id' => $id]);
        registrarOperacao($pdo, $_SESSION['usuario_id'], 'Exclusão de veículo', 'Excluiu o veículo de código ' . $id . '.');
        $_SESSION['mensagem'] = 'Veículo excluído com sucesso.';
    } catch (PDOException $erro) {
        $_SESSION['mensagem'] = 'Não foi possível excluir o veículo porque ele está relacionado a uma ordem de serviço.';
    }

    header('Location: veiculos.php');
    exit;
}

$veiculoEdicao = null;
if (isset($_GET['acao']) && $_GET['acao'] === 'editar' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $sql = 'SELECT * FROM veiculos WHERE id = :id LIMIT 1';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    $veiculoEdicao = $stmt->fetch();
}

$clientes = listarClientes($pdo);
$sql = 'SELECT v.*, c.nome AS nome_cliente FROM veiculos v INNER JOIN clientes c ON c.id = v.cliente_id ORDER BY c.nome ASC, v.modelo ASC';
$veiculos = $pdo->query($sql)->fetchAll();
?>

<div class="painel">
    <h2>Veículos</h2>

    <?php if ($_SESSION['usuario_perfil'] === 'administrador'): ?>
        <form method="POST" class="formulario">
            <input type="hidden" name="id" value="<?php echo (int)($veiculoEdicao['id'] ?? 0); ?>">

            <div class="linha-dupla">
                <div>
                    <label for="cliente_id">Cliente proprietário</label>
                    <select id="cliente_id" name="cliente_id" required>
                        <option value="">Selecione</option>
                        <?php foreach ($clientes as $cliente): ?>
                            <option value="<?php echo (int)$cliente['id']; ?>" <?php echo ((int)($veiculoEdicao['cliente_id'] ?? 0) === (int)$cliente['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cliente['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="marca">Marca</label>
                    <input type="text" id="marca" name="marca" value="<?php echo htmlspecialchars($veiculoEdicao['marca'] ?? ''); ?>" required>
                </div>
            </div>

            <div class="linha-dupla">
                <div>
                    <label for="modelo">Modelo</label>
                    <input type="text" id="modelo" name="modelo" value="<?php echo htmlspecialchars($veiculoEdicao['modelo'] ?? ''); ?>" required>
                </div>
                <div>
                    <label for="placa">Placa</label>
                    <input type="text" id="placa" name="placa" value="<?php echo htmlspecialchars($veiculoEdicao['placa'] ?? ''); ?>" required>
                </div>
            </div>

            <div class="linha-dupla">
                <div>
                    <label for="ano">Ano</label>
                    <input type="number" id="ano" name="ano" min="1900" max="2100" value="<?php echo htmlspecialchars((string)($veiculoEdicao['ano'] ?? '')); ?>" required>
                </div>
                <div></div>
            </div>

            <button type="submit" name="salvar" class="botao botao-principal">
                <?php echo $veiculoEdicao ? 'Atualizar veículo' : 'Cadastrar veículo'; ?>
            </button>
        </form>
    <?php endif; ?>

    <h3>Consulta de veículos</h3>
    <table>
        <thead>
            <tr>
                <th>Cliente</th>
                <th>Marca</th>
                <th>Modelo</th>
                <th>Placa</th>
                <th>Ano</th>
                <?php if ($_SESSION['usuario_perfil'] === 'administrador'): ?>
                    <th>Ações</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($veiculos as $veiculo): ?>
                <tr>
                    <td><?php echo htmlspecialchars($veiculo['nome_cliente']); ?></td>
                    <td><?php echo htmlspecialchars($veiculo['marca']); ?></td>
                    <td><?php echo htmlspecialchars($veiculo['modelo']); ?></td>
                    <td><?php echo htmlspecialchars($veiculo['placa']); ?></td>
                    <td><?php echo (int)$veiculo['ano']; ?></td>
                    <?php if ($_SESSION['usuario_perfil'] === 'administrador'): ?>
                        <td>
                            <a href="veiculos.php?acao=editar&id=<?php echo (int)$veiculo['id']; ?>" class="botao">Editar</a>
                            <a href="veiculos.php?acao=excluir&id=<?php echo (int)$veiculo['id']; ?>" class="botao botao-danger" data-confirmar-exclusao="Deseja realmente excluir este veículo?">Excluir</a>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/includes/rodape.php'; ?>
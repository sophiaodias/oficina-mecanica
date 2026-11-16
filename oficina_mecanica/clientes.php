<?php
require_once __DIR__ . '/includes/cabecalho.php';

if (!in_array($_SESSION['usuario_perfil'] ?? '', ['administrador', 'mecanico'], true)) {
    $_SESSION['mensagem'] = 'Acesso negado.';
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar'])) {
    if ($_SESSION['usuario_perfil'] !== 'administrador') {
        $_SESSION['mensagem'] = 'Você não tem permissão para cadastrar clientes.';
        header('Location: clientes.php');
        exit;
    }

    $id = (int)($_POST['id'] ?? 0);
    $nome = trim($_POST['nome'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $cpf = trim($_POST['cpf'] ?? '');

    if ($nome === '' || $cpf === '') {
        $_SESSION['mensagem'] = 'Nome e CPF são obrigatórios.';
        header('Location: clientes.php');
        exit;
    }

    if ($id > 0) {
        $sql = 'UPDATE clientes SET nome = :nome, telefone = :telefone, email = :email, cpf = :cpf WHERE id = :id';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nome' => $nome,
            ':telefone' => $telefone,
            ':email' => $email,
            ':cpf' => $cpf,
            ':id' => $id,
        ]);
        $acao = 'Atualização de cliente';
        $descricao = 'Atualizou o cliente ' . $nome . '.';
    } else {
        $sql = 'INSERT INTO clientes (nome, telefone, email, cpf) VALUES (:nome, :telefone, :email, :cpf)';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nome' => $nome,
            ':telefone' => $telefone,
            ':email' => $email,
            ':cpf' => $cpf,
        ]);
        $acao = 'Cadastro de cliente';
        $descricao = 'Cadastrou o cliente ' . $nome . '.';
    }

    registrarOperacao($pdo, $_SESSION['usuario_id'], $acao, $descricao);
    $_SESSION['mensagem'] = 'Cliente salvo com sucesso.';
    header('Location: clientes.php');
    exit;
}

if (isset($_GET['acao']) && $_GET['acao'] === 'excluir' && isset($_GET['id'])) {
    if ($_SESSION['usuario_perfil'] !== 'administrador') {
        $_SESSION['mensagem'] = 'Você não tem permissão para excluir clientes.';
        header('Location: clientes.php');
        exit;
    }

    $id = (int)$_GET['id'];
    $sql = 'DELETE FROM clientes WHERE id = :id';
    $stmt = $pdo->prepare($sql);
    try {
        $stmt->execute([':id' => $id]);
        registrarOperacao($pdo, $_SESSION['usuario_id'], 'Exclusão de cliente', 'Excluiu o cliente de código ' . $id . '.');
        $_SESSION['mensagem'] = 'Cliente excluído com sucesso.';
    } catch (PDOException $erro) {
        $_SESSION['mensagem'] = 'Não foi possível excluir o cliente porque ele está relacionado a outros registros.';
    }

    header('Location: clientes.php');
    exit;
}

$clienteEdicao = null;
if (isset($_GET['acao']) && $_GET['acao'] === 'editar' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $sql = 'SELECT * FROM clientes WHERE id = :id LIMIT 1';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    $clienteEdicao = $stmt->fetch();
}

$sql = 'SELECT * FROM clientes ORDER BY nome ASC';
$clientes = $pdo->query($sql)->fetchAll();
?>

<div class="painel">
    <h2>Clientes</h2>

    <?php if ($_SESSION['usuario_perfil'] === 'administrador'): ?>
        <form method="POST" class="formulario">
            <input type="hidden" name="id" value="<?php echo (int)($clienteEdicao['id'] ?? 0); ?>">

            <div class="linha-dupla">
                <div>
                    <label for="nome">Nome</label>
                    <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($clienteEdicao['nome'] ?? ''); ?>" required>
                </div>
                <div>
                    <label for="telefone">Telefone</label>
                    <input type="text" id="telefone" name="telefone" value="<?php echo htmlspecialchars($clienteEdicao['telefone'] ?? ''); ?>">
                </div>
            </div>

            <div class="linha-dupla">
                <div>
                    <label for="email">E-mail</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($clienteEdicao['email'] ?? ''); ?>">
                </div>
                <div>
                    <label for="cpf">CPF</label>
                    <input type="text" id="cpf" name="cpf" value="<?php echo htmlspecialchars($clienteEdicao['cpf'] ?? ''); ?>" required>
                </div>
            </div>

            <button type="submit" name="salvar" class="botao botao-principal">
                <?php echo $clienteEdicao ? 'Atualizar cliente' : 'Cadastrar cliente'; ?>
            </button>
        </form>
    <?php endif; ?>

    <h3>Consulta de clientes</h3>
    <table>
        <thead>
            <tr>
                <th>Nome</th>
                <th>Telefone</th>
                <th>E-mail</th>
                <th>CPF</th>
                <?php if ($_SESSION['usuario_perfil'] === 'administrador'): ?>
                    <th>Ações</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($clientes as $cliente): ?>
                <tr>
                    <td><?php echo htmlspecialchars($cliente['nome']); ?></td>
                    <td><?php echo htmlspecialchars($cliente['telefone'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($cliente['email'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($cliente['cpf']); ?></td>
                    <?php if ($_SESSION['usuario_perfil'] === 'administrador'): ?>
                        <td>
                            <a href="clientes.php?acao=editar&id=<?php echo (int)$cliente['id']; ?>" class="botao">Editar</a>
                            <a href="clientes.php?acao=excluir&id=<?php echo (int)$cliente['id']; ?>" class="botao botao-danger" data-confirmar-exclusao="Deseja realmente excluir este cliente?">Excluir</a>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/includes/rodape.php'; ?>
<?php
require_once __DIR__ . '/includes/cabecalho.php';
exigirPerfil(['administrador']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar'])) {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $perfil = $_POST['perfil'] ?? 'mecanico';

    if ($nome === '' || $email === '' || $senha === '') {
        $_SESSION['mensagem'] = 'Preencha todos os campos do usuário.';
        header('Location: usuarios.php');
        exit;
    }

    if (!in_array($perfil, ['administrador', 'mecanico'], true)) {
        $_SESSION['mensagem'] = 'Perfil inválido.';
        header('Location: usuarios.php');
        exit;
    }

    $sqlExiste = 'SELECT id FROM usuarios WHERE email = :email LIMIT 1';
    $stmtExiste = $pdo->prepare($sqlExiste);
    $stmtExiste->execute([':email' => $email]);

    if ($stmtExiste->fetch()) {
        $_SESSION['mensagem'] = 'Já existe um usuário com este e-mail.';
        header('Location: usuarios.php');
        exit;
    }

    $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
    $sql = 'INSERT INTO usuarios (nome, email, senha, perfil) VALUES (:nome, :email, :senha, :perfil)';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':nome' => $nome,
        ':email' => $email,
        ':senha' => $senhaHash,
        ':perfil' => $perfil,
    ]);

    registrarOperacao($pdo, $_SESSION['usuario_id'], 'Cadastro de usuário', 'Cadastrou o usuário ' . $nome . ' com perfil ' . $perfil . '.');
    $_SESSION['mensagem'] = 'Usuário cadastrado com sucesso.';
    header('Location: usuarios.php');
    exit;
}

if (isset($_GET['acao']) && $_GET['acao'] === 'excluir' && isset($_GET['id'])) {
    $idUsuario = (int)$_GET['id'];

    if ($idUsuario === $_SESSION['usuario_id']) {
        $_SESSION['mensagem'] = 'Você não pode excluir o próprio usuário.';
        header('Location: usuarios.php');
        exit;
    }

    $sql = 'DELETE FROM usuarios WHERE id = :id';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $idUsuario]);

    registrarOperacao($pdo, $_SESSION['usuario_id'], 'Exclusão de usuário', 'Excluiu o usuário de código ' . $idUsuario . '.');
    $_SESSION['mensagem'] = 'Usuário removido com sucesso.';
    header('Location: usuarios.php');
    exit;
}

$sql = 'SELECT * FROM usuarios ORDER BY nome ASC';
$usuarios = $pdo->query($sql)->fetchAll();
?>

<div class="painel">
    <h2>Cadastro de usuários</h2>

    <form method="POST" class="formulario">
        <div class="linha-dupla">
            <div>
                <label for="nome">Nome</label>
                <input type="text" id="nome" name="nome" required>
            </div>
            <div>
                <label for="perfil">Perfil</label>
                <select id="perfil" name="perfil">
                    <option value="administrador">Administrador</option>
                    <option value="mecanico" selected>Mecânico</option>
                </select>
            </div>
        </div>

        <div class="linha-dupla">
            <div>
                <label for="email">E-mail</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div>
                <label for="senha">Senha</label>
                <input type="password" id="senha" name="senha" required>
            </div>
        </div>

        <button type="submit" name="salvar" class="botao botao-principal">Salvar usuário</button>
    </form>

    <h3>Usuários cadastrados</h3>

    <table>
        <thead>
            <tr>
                <th>Nome</th>
                <th>E-mail</th>
                <th>Perfil</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($usuarios as $usuario): ?>
                <tr>
                    <td><?php echo htmlspecialchars($usuario['nome']); ?></td>
                    <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                    <td><?php echo htmlspecialchars(ucfirst($usuario['perfil'])); ?></td>
                    <td>
                        <?php if ((int)$usuario['id'] !== (int)$_SESSION['usuario_id']): ?>
                            <a href="usuarios.php?acao=excluir&id=<?php echo (int)$usuario['id']; ?>" class="botao botao-danger" data-confirmar-exclusao="Deseja realmente excluir este usuário?">Excluir</a>
                        <?php else: ?>
                            <span class="texto-auxiliar">Usuário atual</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/includes/rodape.php'; ?>
<?php
session_start();

if (isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/config/conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if ($email === '' || $senha === '') {
        $_SESSION['mensagem'] = 'Informe o e-mail e a senha.';
    } else {
        $sql = 'SELECT * FROM usuarios WHERE email = :email LIMIT 1';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':email' => $email]);
        $usuario = $stmt->fetch();

        $senhaValida = false;

        if ($usuario) {
            $senhaValida = password_verify($senha, $usuario['senha']) || $usuario['senha'] === $senha;
        }

        if ($senhaValida) {
            $_SESSION['usuario_id'] = (int)$usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            $_SESSION['usuario_email'] = $usuario['email'];
            $_SESSION['usuario_perfil'] = $usuario['perfil'];
            header('Location: index.php');
            exit;
        }

        $_SESSION['mensagem'] = 'E-mail ou senha inválidos.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Oficina Mecânica</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="pagina-login">
    <div class="login-box">
        <h2>Login</h2>
        <p class="subtitulo">Sistema de gestão da oficina</p>

        <?php if (isset($_SESSION['mensagem'])): ?>
            <div class="alerta">
                <?php
                echo htmlspecialchars($_SESSION['mensagem']);
                unset($_SESSION['mensagem']);
                ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <label for="email">E-mail</label>
            <input type="email" id="email" name="email" required>

            <label for="senha">Senha</label>
            <input type="password" id="senha" name="senha" required>

            <button type="submit" class="botao botao-principal">Entrar</button>
        </form>

        <p class="texto-auxiliar">Usuário inicial: admin@oficina.com / admin123</p>
    </div>
</body>
</html>
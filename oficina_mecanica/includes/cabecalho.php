<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/funcoes.php';

if (basename($_SERVER['PHP_SELF']) !== 'login.php' && !isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$usuarioNome = $_SESSION['usuario_nome'] ?? 'Usuário';
$usuarioPerfil = $_SESSION['usuario_perfil'] ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Oficina Mecânica</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <header class="cabecalho">
            <div>
                <h1>Oficina Mecânica</h1>
            </div>
            <?php if (isset($_SESSION['usuario_id'])): ?>
                <div class="usuario-info">
                    <span>Olá, <?php echo htmlspecialchars($usuarioNome); ?> (<?php echo htmlspecialchars(ucfirst($usuarioPerfil)); ?>)</span>
                    <a href="sair.php" class="botao-link">Sair</a>
                </div>
            <?php endif; ?>
        </header>

        <?php if (isset($_SESSION['usuario_id'])): ?>
            <nav class="menu">
                <a href="index.php">Início</a>
                <a href="clientes.php">Clientes</a>
                <a href="veiculos.php">Veículos</a>
                <a href="ordens.php">Ordens</a>
            </nav>
        <?php endif; ?>

        <?php if (isset($_SESSION['mensagem'])): ?>
            <div class="alerta">
                <?php
                echo htmlspecialchars($_SESSION['mensagem']);
                unset($_SESSION['mensagem']);
                ?>
            </div>
        <?php endif; ?>

        <main class="conteudo">
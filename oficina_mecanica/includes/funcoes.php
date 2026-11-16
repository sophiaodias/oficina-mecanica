<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function protegerPagina() {
    if (!isset($_SESSION['usuario_id'])) {
        header('Location: login.php');
        exit;
    }
}

function exigirPerfil(array $perfisPermitidos) {
    protegerPagina();

    if (!in_array($_SESSION['usuario_perfil'], $perfisPermitidos, true)) {
        $_SESSION['mensagem'] = 'Acesso negado para este perfil.';
        header('Location: index.php');
        exit;
    }
}

function registrarOperacao(PDO $pdo, int $usuarioId, string $acao, string $descricao): void {
    if ($usuarioId <= 0) {
        return;
    }

    $verificaColuna = $pdo->query("SHOW COLUMNS FROM registros_operacoes LIKE 'horario_operacao'");
    $temHorarioOperacao = $verificaColuna && $verificaColuna->fetch() !== false;

    if ($temHorarioOperacao) {
        $sql = "INSERT INTO registros_operacoes (usuario_id, acao, descricao, data_operacao, horario_operacao)
                VALUES (?, ?, ?, CURDATE(), CURTIME())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$usuarioId, $acao, $descricao]);
        return;
    }

    $sql = "INSERT INTO registros_operacoes (usuario_id, acao, descricao, data_operacao)
            VALUES (?, ?, ?, CURDATE())";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$usuarioId, $acao, $descricao]);
}

function formatarData(string $data): string {
    if (empty($data) || $data === '0000-00-00') {
        return '-';
    }

    return date('d/m/Y', strtotime($data));
}

function formatarHora(string $hora): string {
    if (empty($hora) || $hora === '00:00:00') {
        return '-';
    }

    return date('H:i', strtotime($hora));
}

function verificarConflitoAgendamento(PDO $pdo, int $mecanicoId, string $dataAgendada, string $horarioAgendado, ?int $ordemAtualId = null): bool {
    $sql = "SELECT id FROM ordens_servico
            WHERE mecanico_id = :mecanico_id
              AND data_agendada = :data_agendada
              AND horario_agendado = :horario_agendado";

    if ($ordemAtualId !== null) {
        $sql .= " AND id != :ordem_atual_id";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':mecanico_id', $mecanicoId, PDO::PARAM_INT);
    $stmt->bindValue(':data_agendada', $dataAgendada);
    $stmt->bindValue(':horario_agendado', $horarioAgendado);

    if ($ordemAtualId !== null) {
        $stmt->bindValue(':ordem_atual_id', $ordemAtualId, PDO::PARAM_INT);
    }

    $stmt->execute();

    return $stmt->fetchColumn() !== false;
}

function listarClientes(PDO $pdo): array {
    $sql = "SELECT * FROM clientes ORDER BY nome ASC";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}

function listarMecanicos(PDO $pdo): array {
    $sql = "SELECT id, nome FROM usuarios WHERE perfil = 'mecanico' ORDER BY nome ASC";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}

function listarVeiculos(PDO $pdo): array {
    $sql = "SELECT v.*, c.nome AS nome_cliente
            FROM veiculos v
            INNER JOIN clientes c ON c.id = v.cliente_id
            ORDER BY c.nome ASC, v.modelo ASC";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}

function gerarNumeroOrdem(PDO $pdo): string {
    $sql = "SELECT MAX(id) AS maior_id FROM ordens_servico";
    $resultado = $pdo->query($sql)->fetch();
    $proximoId = (int)($resultado['maior_id'] ?? 0) + 1;

    return 'OS-' . date('Ymd') . '-' . str_pad((string)$proximoId, 4, '0', STR_PAD_LEFT);
}
?>
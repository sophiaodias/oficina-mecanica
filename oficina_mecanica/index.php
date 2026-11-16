<?php
require_once __DIR__ . '/includes/cabecalho.php';

$perfilUsuario = $_SESSION['usuario_perfil'];

$sqlClientes = 'SELECT COUNT(*) AS total FROM clientes';
$totalClientes = $pdo->query($sqlClientes)->fetch()['total'];

$sqlVeiculos = 'SELECT COUNT(*) AS total FROM veiculos';
$totalVeiculos = $pdo->query($sqlVeiculos)->fetch()['total'];

if ($perfilUsuario === 'administrador') {
    $sqlOrdens = 'SELECT COUNT(*) AS total FROM ordens_servico';
    $sqlUltimasOrdens = "SELECT os.*, c.nome AS cliente, u.nome AS mecanico
    FROM ordens_servico os
    INNER JOIN clientes c ON c.id = os.cliente_id
    INNER JOIN usuarios u ON u.id = os.mecanico_id
    ORDER BY os.data_agendada DESC, os.horario_agendado DESC
    LIMIT 5";
} else {
    $sqlOrdens = 'SELECT COUNT(*) AS total FROM ordens_servico WHERE mecanico_id = :usuario_id';
    $stmt = $pdo->prepare($sqlOrdens);
    $stmt->execute([':usuario_id' => $_SESSION['usuario_id']]);
    $totalOrdens = $stmt->fetch()['total'];

    $sqlUltimasOrdens = "SELECT os.*, c.nome AS cliente, u.nome AS mecanico
    FROM ordens_servico os
    INNER JOIN clientes c ON c.id = os.cliente_id
    INNER JOIN usuarios u ON u.id = os.mecanico_id
    WHERE os.mecanico_id = :usuario_id
    ORDER BY os.data_agendada DESC, os.horario_agendado DESC
    LIMIT 5";
}

if ($perfilUsuario === 'administrador') {
    $totalOrdens = $pdo->query($sqlOrdens)->fetch()['total'];
}

$ultimasOrdens = $pdo->prepare($sqlUltimasOrdens);
if ($perfilUsuario !== 'administrador') {
    $ultimasOrdens->execute([':usuario_id' => $_SESSION['usuario_id']]);
} else {
    $ultimasOrdens->execute();
}
$listaOrdens = $ultimasOrdens->fetchAll();
?>

<div class="painel">
    <h2>Painel principal</h2>

    <div class="cards">
        <div class="card">
            <span class="titulo-card">Clientes</span>
            <strong><?php echo (int)$totalClientes; ?></strong>
        </div>
        <div class="card">
            <span class="titulo-card">Veículos</span>
            <strong><?php echo (int)$totalVeiculos; ?></strong>
        </div>
        <div class="card">
            <span class="titulo-card">Ordens</span>
            <strong><?php echo (int)$totalOrdens; ?></strong>
        </div>
    </div>

    <h3>Últimas ordens</h3>

    <?php if (empty($listaOrdens)): ?>
        <p>Nenhuma ordem cadastrada.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Número</th>
                    <th>Cliente</th>
                    <th>Mecânico</th>
                    <th>Data</th>
                    <th>Hora</th>
                    <th>Situação</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($listaOrdens as $ordem): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($ordem['numero_ordem']); ?></td>
                        <td><?php echo htmlspecialchars($ordem['cliente']); ?></td>
                        <td><?php echo htmlspecialchars($ordem['mecanico']); ?></td>
                        <td><?php echo formatarData($ordem['data_agendada']); ?></td>
                        <td><?php echo formatarHora($ordem['horario_agendado']); ?></td>
                        <td><?php echo htmlspecialchars($ordem['situacao']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/rodape.php'; ?>
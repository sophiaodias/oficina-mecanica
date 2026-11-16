CREATE DATABASE oficina_mecanica;
USE oficina_mecanica;

CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    senha VARCHAR(255) NOT NULL,
    perfil VARCHAR(30) NOT NULL
);

CREATE TABLE clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    telefone VARCHAR(20),
    email VARCHAR(150),
    cpf VARCHAR(14)
);

CREATE TABLE veiculos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    marca VARCHAR(50) NOT NULL,
    modelo VARCHAR(80) NOT NULL,
    placa VARCHAR(10) NOT NULL,
    ano INT NOT NULL,

    FOREIGN KEY (cliente_id) REFERENCES clientes(id)
);

CREATE TABLE ordens_servico (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_ordem VARCHAR(20) NOT NULL,
    cliente_id INT NOT NULL,
    veiculo_id INT NOT NULL,
    mecanico_id INT NOT NULL,
    descricao_servico TEXT NOT NULL,
    data_agendada DATE NOT NULL,
    horario_agendado TIME NOT NULL,
    situacao VARCHAR(30) DEFAULT 'Pendente',
    observacoes_execucao TEXT,

    FOREIGN KEY (cliente_id) REFERENCES clientes(id),
    FOREIGN KEY (veiculo_id) REFERENCES veiculos(id),
    FOREIGN KEY (mecanico_id) REFERENCES usuarios(id)
);

CREATE TABLE registros_operacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    acao VARCHAR(100) NOT NULL,
    descricao TEXT,
    data_operacao DATE,
    horario_operacao TIME,

    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

INSERT INTO usuarios (nome, email, senha, perfil)
VALUES ('Administrador', 'admin@oficina.com', '123456', 'administrador'),
('João Pereira', 'joao@oficina.com', '234567', 'mecanico'),
('Maria Silva', 'maria@oficina.com', '345678', 'mecanico');

INSERT INTO clientes (nome, telefone, email, cpf) VALUES
('Ana Paula Souza', '(11) 98765-4321', 'ana.souza@email.com', '123.456.789-09'),
('Carlos Eduardo Lima', '(11) 97654-3210', 'carlos.lima@email.com', '234.567.890-12'),
('Mariana Oliveira', '(11) 96543-2109', 'mariana.oliveira@email.com', '345.678.901-23'),
('Roberto Santos', '(11) 95432-1098', 'roberto.santos@email.com', '456.789.012-34'),

INSERT INTO veiculos (cliente_id, marca, modelo, placa, ano) VALUES
(1, 'Chevrolet', 'Onix', 'ABC1D23', 2020),
(2, 'Volkswagen', 'Gol', 'DEF4G56', 2018),
(3, 'Fiat', 'Argo', 'HIJ7K89', 2022),
(4, 'Toyota', 'Corolla', 'LMN1O23', 2021),

INSERT INTO ordens_servico (numero_ordem, cliente_id, veiculo_id, mecanico_id, descricao_servico, data_agendada, horario_agendado, situacao, observacoes_execucao)
VALUES
('OS-202611-0001', 1, 1, 2, 'Troca de óleo e revisão geral', '2026-11-12', '09:00:00', 'Pendente', 'Cliente solicitou revisão preventiva.'),
('OS-202611-0002', 2, 2, 3, 'Alinhamento e balanceamento', '2026-11-12', '11:00:00', 'Em andamento', 'Pneus em bom estado.');
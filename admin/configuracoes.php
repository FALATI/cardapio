<?php
require_once '../config/config.php';
require_once 'verificar_admin.php';

$mensagem = '';

// Buscar configurações atuais
$configs = [];
$sql = "SELECT chave, valor FROM configuracoes";
$result = $conn->query($sql);

if ($result === false) {
    error_log("Erro ao buscar configurações: " . $conn->error);
} else {
    while($row = $result->fetch_assoc()) {
        $configs[$row['chave']] = $row['valor'];
    }
}

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Upload do logo
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
            $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png'])) {
                throw new Exception("Formato de imagem não permitido");
            }
            
            $novo_nome = 'logo.' . $ext;
            $destino = "../uploads/logo/" . $novo_nome;
            
            if (!is_dir("../uploads/logo/")) {
                mkdir("../uploads/logo/", 0777, true);
            }
            
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $destino)) {
                atualizarConfig('logo', $novo_nome);
            }
        }

        // Upload do favicon
        if (isset($_FILES['favicon']) && $_FILES['favicon']['error'] == 0) {
            $ext = strtolower(pathinfo($_FILES['favicon']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['ico', 'png'])) {
                throw new Exception("Formato de favicon não permitido");
            }
            
            $novo_nome = 'favicon.' . $ext;
            $destino = "../uploads/favicon/" . $novo_nome;
            
            if (!is_dir("../uploads/favicon/")) {
                mkdir("../uploads/favicon/", 0777, true);
            }
            
            if (move_uploaded_file($_FILES['favicon']['tmp_name'], $destino)) {
                atualizarConfig('favicon', $novo_nome);
            }
        }

        // Atualizar dias de funcionamento
        if (isset($_POST['dias_funcionamento']) && is_array($_POST['dias_funcionamento'])) {
            $dias = implode(',', $_POST['dias_funcionamento']);
            atualizarConfig('dias_funcionamento', $dias);
        }

        // Processar horários
        $horarios = [];
        if (isset($_POST['horarios']) && is_array($_POST['horarios'])) {
            foreach ($_POST['horarios'] as $dia => $config) {
                $horarios[$dia] = [
                    'status' => isset($config['status']) ? 1 : 0,
                    'abertura' => $config['abertura'] ?? '09:00:00',
                    'fechamento' => $config['fechamento'] ?? '18:00:00'
                ];
            }
            atualizarConfig('horarios', json_encode($horarios));
        }

        // Processar endereços
        if (isset($_POST['enderecos_entrega'])) {
            atualizarConfig('enderecos_entrega', $_POST['enderecos_entrega']);
        }

        if (isset($_POST['valor_entrega'])) {
            atualizarConfig('valor_entrega', $_POST['valor_entrega']);
        }

        // Processar endereços existentes
        if (isset($_POST['endereco_ids']) && is_array($_POST['endereco_ids'])) {
            foreach ($_POST['endereco_ids'] as $i => $id) {
                $bairro = $_POST['bairros'][$i];
                $valor = $_POST['valores'][$i];
                $status = isset($_POST['status']) && in_array($id, $_POST['status']) ? 1 : 0;
                
                $sql = "UPDATE enderecos_entrega SET bairro = ?, valor_entrega = ?, status = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sdii", $bairro, $valor, $status, $id);
                $stmt->execute();
            }
        }

        // Processar novo endereço
        if (!empty($_POST['novo_bairro']) && !empty($_POST['novo_valor'])) {
            $novo_bairro = $_POST['novo_bairro'];
            $novo_valor = $_POST['novo_valor'];
            $novo_status = isset($_POST['novo_status']) ? 1 : 0;
            
            $sql = "INSERT INTO enderecos_entrega (bairro, valor_entrega, status) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sdi", $novo_bairro, $novo_valor, $novo_status);
            $stmt->execute();
        }

        // Atualizar outras configurações
        $campos = [
            'site_titulo' => 'text',
            'site_subtitulo' => 'text',
            'horarios_funcionamento' => 'textarea',
            'facebook_url' => 'text',
            'instagram_url' => 'text',
            'whatsapp_url' => 'text'  // Adicionado campos de redes sociais
        ];

        foreach ($campos as $campo => $tipo) {
            if (isset($_POST[$campo])) {
                atualizarConfig($campo, $_POST[$campo]);
            }
        }

        $mensagem = "Configurações atualizadas com sucesso!";
        
        // Recarregar configurações
        $sql = "SELECT chave, valor FROM configuracoes";
        $result = $conn->query($sql);
        
        if ($result) {
            while($row = $result->fetch_assoc()) {
                $configs[$row['chave']] = $row['valor'];
            }
        }

        // Processar alterações do administrador
        if (!empty($_POST['admin_nome']) || !empty($_POST['admin_email']) || !empty($_POST['admin_senha'])) {
            // Buscar ID do admin
            $sql = "SELECT id FROM usuarios WHERE tipo = 'admin' LIMIT 1";
            $result = $conn->query($sql);
            
            if (!$result) {
                throw new Exception("Erro ao buscar admin: " . $conn->error);
            }
            
            $admin = $result->fetch_assoc();
            
            if ($admin) {
                $updates = [];
                $params = [];
                $types = '';
                
                // Preparar atualizações
                if (!empty($_POST['admin_nome'])) {
                    $updates[] = "nome = ?";
                    $params[] = $_POST['admin_nome'];
                    $types .= 's';
                }
                
                if (!empty($_POST['admin_email'])) {
                    $updates[] = "email = ?";
                    $params[] = $_POST['admin_email'];
                    $types .= 's';
                }
                
                if (!empty($_POST['admin_senha'])) {
                    $updates[] = "senha = ?";
                    $params[] = password_hash($_POST['admin_senha'], PASSWORD_DEFAULT);
                    $types .= 's';
                }
                
                // Executar atualização se houver mudanças
                if (!empty($updates)) {
                    $sql = "UPDATE usuarios SET " . implode(', ', $updates) . " WHERE id = ? AND tipo = 'admin'";
                    $params[] = $admin['id'];
                    $types .= 'i';
                    
                    $stmt = $conn->prepare($sql);
                    if (!$stmt) {
                        throw new Exception("Erro ao preparar query: " . $conn->error);
                    }
                    
                    $stmt->bind_param($types, ...$params);
                    
                    if (!$stmt->execute()) {
                        throw new Exception("Erro ao atualizar dados: " . $stmt->error);
                    }
                    
                    // Atualizar mensagem de sucesso
                    $mensagem = "Dados do administrador atualizados com sucesso!";
                    
                    // Atualizar dados da sessão
                    if (!empty($_POST['admin_nome'])) {
                        $_SESSION['usuario_nome'] = $_POST['admin_nome'];
                    }
                    if (!empty($_POST['admin_email'])) {
                        $_SESSION['usuario_email'] = $_POST['admin_email'];
                    }
                    
                    // Recarregar dados do admin
                    $sql = "SELECT id, nome, email FROM usuarios WHERE tipo = 'admin' LIMIT 1";
                    $result = $conn->query($sql);
                    if ($result) {
                        $admin = $result->fetch_assoc();
                    }
                }
            }
        }
        
    } catch (Exception $e) {
        $mensagem = "Erro: " . $e->getMessage();
        error_log($mensagem);
    }
}

function atualizarConfig($chave, $valor) {
    global $conn;
    $sql = "INSERT INTO configuracoes (chave, valor) VALUES (?, ?)
            ON DUPLICATE KEY UPDATE valor = VALUES(valor)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $chave, $valor);
    return $stmt->execute();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_TITLE; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --bs-primary: #3491D0;
            --bs-primary-rgb: 52, 145, 208;
            --bs-primary-hover: #2C475D;
        }

        body {
            background-color: #f8f9fa;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 250px;
            background: linear-gradient(135deg, #2C475D 0%, #3491D0 100%);
            padding-top: 1rem;
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 0.8rem 1.2rem;
            margin: 0.2rem 1rem;
            border-radius: 8px;
            transition: all 0.3s;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: #fff;
            background: rgba(255, 255, 255, 0.1);
        }

        /* Conteúdo Principal */
        .main-content {
            margin-left: 250px;
            padding: 2rem;
            transition: margin 0.3s ease;
        }

        /* Cards */
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            margin-bottom: 1.5rem;
        }

        .card-header {
            background: #f8f9fa;
            border-bottom: 1px solid rgba(0,0,0,0.1);
            padding: 1rem 1.5rem;
        }

        .card-body {
            padding: 1.5rem;
        }

        /* Formulários */
        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--bs-primary);
            box-shadow: 0 0 0 0.2rem rgba(52, 145, 208, 0.25);
        }

        /* Tabelas */
        .table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #495057;
        }

        .table td {
            vertical-align: middle;
        }

        /* Previews */
        .preview-logo {
            max-width: 200px;
            max-height: 100px;
            object-fit: contain;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            padding: 0.5rem;
            background: white;
            transition: all 0.3s;
        }

        .preview-logo:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        /* Botões */
        .btn {
            border-radius: 8px;
            padding: 0.5rem 1rem;
        }

        .btn-primary {
            background: var(--bs-primary);
            border-color: var(--bs-primary);
        }

        .btn-primary:hover {
            background: var(--bs-primary-hover);
            border-color: var(--bs-primary-hover);
        }

        /* Responsividade */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
                padding: 1rem;
            }

            .card-body {
                padding: 1rem;
            }
        }

        /* Animações */
        .card {
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        /* Alerts */
        .alert {
            border-radius: 10px;
            border: none;
        }

        /* Ícones */
        .bi {
            margin-right: 0.5rem;
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="container-fluid px-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">
                    <i class="bi bi-gear me-2"></i>
                    Configurações do Site
                </h2>
            </div>

            <?php if ($mensagem): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $mensagem; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <!-- Logo e Favicon -->
                        <div class="row">
                            <!-- Logo existente -->
                            <div class="col-md-6 mb-4">
                                <label class="form-label">Logo do Site</label>
                                <?php if (!empty($configs['logo'])): ?>
                                    <div class="mb-2">
                                        <img src="../uploads/logo/<?php echo $configs['logo']; ?>" 
                                             class="preview-logo" alt="Logo atual">
                                    </div>
                                <?php endif; ?>
                                <input type="file" class="form-control" name="logo" accept="image/*">
                                <div class="form-text">Recomendado: PNG transparente, máximo 2MB</div>
                            </div>

                            <!-- Novo campo para Favicon -->
                            <div class="col-md-6 mb-4">
                                <label class="form-label">Favicon do Site</label>
                                <?php if (!empty($configs['favicon'])): ?>
                                    <div class="mb-2">
                                        <img src="../uploads/favicon/<?php echo $configs['favicon']; ?>" 
                                             style="width: 32px; height: 32px;" alt="Favicon atual">
                                    </div>
                                <?php endif; ?>
                                <input type="file" class="form-control" name="favicon" accept="image/x-icon,image/png">
                                <div class="form-text">Recomendado: ICO ou PNG 32x32px</div>
                            </div>
                        </div>

                        <!-- Título e Subtítulo -->
                        <div class="row">
                            <div class="col-md-12 mb-4">
                                <label class="form-label" for="site_titulo">Nome do Site</label>
                                <input type="text" class="form-control" id="site_titulo" name="site_titulo"
                                       value="<?php echo $configs['site_titulo'] ?? ''; ?>"
                                       placeholder="Ex: Restaurante XYZ">
                                <div class="form-text">Este nome será usado como identificação do seu estabelecimento</div>
                            </div>

                            <div class="col-md-12 mb-4">
                                <label class="form-label" for="site_subtitulo">Subtítulo do Site</label>
                                <input type="text" class="form-control" id="site_subtitulo" name="site_subtitulo"
                                       value="<?php echo $configs['site_subtitulo'] ?? ''; ?>"
                                       placeholder="Ex: Sabor e qualidade em cada prato">
                                <div class="form-text">Este texto aparecerá abaixo do logo/título na página inicial</div>
                            </div>
                        </div>

                        <!-- Horários -->
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">
                                    <i class="bi bi-clock me-2"></i>
                                    Horários de Funcionamento
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Dia</th>
                                                <th>Status</th>
                                                <th>Horário de Abertura</th>
                                                <th>Horário de Fechamento</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $dias = [
                                                1 => 'Domingo',
                                                2 => 'Segunda-feira',
                                                3 => 'Terça-feira',
                                                4 => 'Quarta-feira',
                                                5 => 'Quinta-feira',
                                                6 => 'Sexta-feira',
                                                7 => 'Sábado'
                                            ];
                                            
                                            $horarios = json_decode($configs['horarios'] ?? '{}', true);
                                            
                                            foreach ($dias as $num => $nome): 
                                                $horario = $horarios[$num] ?? [
                                                    'status' => 0,
                                                    'abertura' => '09:00:00',
                                                    'fechamento' => '18:00:00'
                                                ];
                                            ?>
                                                <tr>
                                                    <td><?php echo $nome; ?></td>
                                                    <td class="text-center">
                                                        <div class="form-check d-inline-block">
                                                            <input class="form-check-input" type="checkbox" 
                                                                   name="horarios[<?php echo $num; ?>][status]" 
                                                                   value="1"
                                                                   <?php echo $horario['status'] ? 'checked' : ''; ?>>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <select class="form-select" name="horarios[<?php echo $num; ?>][abertura]">
                                                            <?php for ($hora = 0; $hora < 24; $hora++): ?>
                                                                <?php for ($minuto = 0; $minuto < 60; $minuto += 30): ?>
                                                                    <?php 
                                                                    $time = sprintf('%02d:%02d:00', $hora, $minuto);
                                                                    $selected = $horario['abertura'] == $time ? 'selected' : '';
                                                                    ?>
                                                                    <option value="<?php echo $time; ?>" <?php echo $selected; ?>>
                                                                        <?php echo substr($time, 0, 5); ?>
                                                                    </option>
                                                                <?php endfor; ?>
                                                            <?php endfor; ?>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <select class="form-select" name="horarios[<?php echo $num; ?>][fechamento]">
                                                            <?php for ($hora = 0; $hora < 24; $hora++): ?>
                                                                <?php for ($minuto = 0; $minuto < 60; $minuto += 30): ?>
                                                                    <?php 
                                                                    $time = sprintf('%02d:%02d:00', $hora, $minuto);
                                                                    $selected = $horario['fechamento'] == $time ? 'selected' : '';
                                                                    ?>
                                                                    <option value="<?php echo $time; ?>" <?php echo $selected; ?>>
                                                                        <?php echo substr($time, 0, 5); ?>
                                                                    </option>
                                                                <?php endfor; ?>
                                                            <?php endfor; ?>
                                                        </select>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Endereços -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Endereços e Valores de Entrega</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Bairro</th>
                                                <th>Valor Entrega</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $sql = "SELECT * FROM enderecos_entrega ORDER BY bairro";
                                            $result = $conn->query($sql);
                                            while($endereco = $result->fetch_assoc()):
                                            ?>
                                            <tr>
                                                <td>
                                                    <input type="text" name="bairros[]" class="form-control" 
                                                           value="<?php echo htmlspecialchars($endereco['bairro']); ?>">
                                                    <input type="hidden" name="endereco_ids[]" value="<?php echo $endereco['id']; ?>">
                                                </td>
                                                <td>
                                                    <input type="number" name="valores[]" class="form-control" step="0.01" 
                                                           value="<?php echo $endereco['valor_entrega']; ?>">
                                                </td>
                                                <td>
                                                    <input type="checkbox" name="status[]" class="form-check-input" 
                                                           value="<?php echo $endereco['id']; ?>" 
                                                           <?php echo $endereco['status'] ? 'checked' : ''; ?>>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                            <!-- Linha para novo endereço -->
                                            <tr>
                                                <td>
                                                    <input type="text" name="novo_bairro" class="form-control" placeholder="Novo bairro">
                                                </td>
                                                <td>
                                                    <input type="number" name="novo_valor" class="form-control" 
                                                           step="0.01" placeholder="Valor da entrega">
                                                </td>
                                                <td>
                                                    <input type="checkbox" name="novo_status" class="form-check-input" checked>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Redes Sociais -->
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">
                                    <i class="bi bi-share me-2"></i>
                                    Redes Sociais
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">
                                            <i class="bi bi-facebook me-2"></i>Facebook
                                        </label>
                                        <input type="url" class="form-control" name="facebook_url" 
                                               value="<?php echo htmlspecialchars($configs['facebook_url'] ?? ''); ?>" 
                                               placeholder="https://facebook.com/sua-pagina">
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">
                                            <i class="bi bi-instagram me-2"></i>Instagram
                                        </label>
                                        <input type="url" class="form-control" name="instagram_url" 
                                               value="<?php echo htmlspecialchars($configs['instagram_url'] ?? ''); ?>"
                                               placeholder="https://instagram.com/seu-perfil">
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">
                                            <i class="bi bi-whatsapp me-2"></i>WhatsApp
                                        </label>
                                        <input type="url" class="form-control" name="whatsapp_url" 
                                               value="<?php echo htmlspecialchars($configs['whatsapp_url'] ?? ''); ?>"
                                               placeholder="https://wa.me/seu-numero">
                                        <div class="form-text">Formato: https://wa.me/5511999999999</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Credenciais do Administrador -->
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">
                                    <i class="bi bi-shield-lock me-2"></i>
                                    Credenciais do Administrador
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php
                                // Buscar dados do admin
                                $sql = "SELECT id, nome, email FROM usuarios WHERE tipo = 'admin' LIMIT 1";
                                $result = $conn->query($sql);
                                $admin = $result->fetch_assoc();
                                ?>
                                
                                <!-- Dados atuais -->
                                <div class="alert alert-info mb-4">
                                    <div class="d-flex align-items-center">
                                        <div>
                                            <i class="bi bi-person-circle me-2"></i>
                                            Admin atual: <strong><?php echo htmlspecialchars($admin['nome']); ?></strong>
                                            <small class="text-muted ms-2">(<?php echo htmlspecialchars($admin['email']); ?>)</small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Formulário de edição -->
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Nome</label>
                                        <input type="text" class="form-control" name="admin_nome" 
                                               value="<?php echo htmlspecialchars($admin['nome']); ?>">
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" name="admin_email" 
                                               value="<?php echo htmlspecialchars($admin['email']); ?>">
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Nova Senha</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" name="admin_senha" 
                                                   placeholder="Digite para alterar a senha">
                                            <button class="btn btn-outline-secondary" type="button" onclick="toggleSenha(this)">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </div>
                                        <div class="form-text">Deixe em branco para manter a senha atual</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-2"></i>
                            Salvar Configurações
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <button class="btn btn-primary d-md-none position-fixed top-0 start-0 mt-2 ms-2 rounded-circle" 
            onclick="document.querySelector('.sidebar').classList.toggle('show')" 
            style="z-index: 1001; width: 42px; height: 42px;">
        <i class="bi bi-list"></i>
    </button>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
document.getElementById('formEndereco').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'add');
    
    const btnSubmit = this.querySelector('button[type="submit"]');
    const btnText = btnSubmit.innerHTML;
    btnSubmit.disabled = true;
    btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
    
    fetch('enderecos_ajax.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Erro ao adicionar endereço');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao processar requisição');
    })
    .finally(() => {
        btnSubmit.disabled = false;
        btnSubmit.innerHTML = btnText;
    });
});

function excluirEndereco(id) {
    if (!confirm('Tem certeza que deseja excluir este endereço?')) return;
    
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('id', id);
    
    fetch('enderecos_ajax.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            alert('Erro ao excluir endereço');
        }
    });
}

function toggleSenha(button) {
    const input = button.parentElement.querySelector('input');
    const icon = button.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    }
}
</script>
</body>
</html>
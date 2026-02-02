<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Importação de CSV - Joana</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #6366f1;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --dark-bg: #1f2937;
            --light-bg: #f9fafb;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .main-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .card {
            border-radius: 1rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            border: none;
            backdrop-filter: blur(10px);
            background-color: rgba(255, 255, 255, 0.95);
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, #8b5cf6 100%);
            color: white;
            border-radius: 1rem 1rem 0 0 !important;
            padding: 1.5rem;
            border: none;
        }

        .upload-zone {
            border: 3px dashed #cbd5e1;
            border-radius: 1rem;
            padding: 3rem 2rem;
            text-align: center;
            transition: all 0.3s ease;
            background-color: #f8fafc;
            cursor: pointer;
        }

        .upload-zone:hover {
            border-color: var(--primary-color);
            background-color: #f1f5f9;
            transform: translateY(-2px);
        }

        .upload-zone.dragover {
            border-color: var(--primary-color);
            background-color: #e0e7ff;
            transform: scale(1.02);
        }

        .upload-icon {
            font-size: 4rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .file-item {
            background-color: white;
            border: 1px solid #e5e7eb;
            border-radius: 0.75rem;
            padding: 1rem;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.2s ease;
        }

        .file-item:hover {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transform: translateX(4px);
        }

        .file-info {
            display: flex;
            align-items: center;
            flex: 1;
        }

        .file-icon {
            font-size: 2rem;
            margin-right: 1rem;
            color: var(--primary-color);
        }

        .progress {
            height: 0.5rem;
            border-radius: 0.5rem;
            background-color: #e5e7eb;
        }

        .progress-bar {
            background: linear-gradient(90deg, var(--primary-color) 0%, #8b5cf6 100%);
            border-radius: 0.5rem;
            transition: width 0.3s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, #8b5cf6 100%);
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 0.75rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.4);
        }

        .status-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-pending { background-color: #fef3c7; color: #92400e; }
        .status-processing { background-color: #dbeafe; color: #1e40af; }
        .status-completed { background-color: #d1fae5; color: #065f46; }
        .status-failed { background-color: #fee2e2; color: #991b1b; }

        .table-container {
            max-height: 400px;
            overflow-y: auto;
            border-radius: 0.75rem;
        }

        .table {
            margin-bottom: 0;
        }

        .table thead {
            position: sticky;
            top: 0;
            background-color: #f8fafc;
            z-index: 10;
        }

        .table thead th {
            border-bottom: 2px solid #e5e7eb;
            font-weight: 600;
            color: #475569;
            padding: 1rem;
        }

        .table tbody tr:hover {
            background-color: #f8fafc;
        }

        .table tbody tr {
            transition: background-color 0.2s ease;
        }

        .status-badge {
            transition: all 0.3s ease;
        }

        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.7;
            }
        }

        .status-processing {
            animation: pulse 2s ease-in-out infinite;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: #64748b;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: #cbd5e1;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .spinner {
            animation: spin 1s linear infinite;
        }

        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .stats-card h3 {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .stats-card p {
            margin: 0;
            opacity: 0.9;
        }

        /* Notification styles */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            min-width: 300px;
            max-width: 500px;
            padding: 1rem 1.5rem;
            border-radius: 0.75rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            z-index: 9999;
            animation: slideInRight 0.3s ease-out;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        @keyframes slideInRight {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes slideOutRight {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(400px);
                opacity: 0;
            }
        }

        .notification.hide {
            animation: slideOutRight 0.3s ease-out forwards;
        }

        .notification-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }

        .notification-error {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }

        .notification-info {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
        }

        .notification-icon {
            font-size: 1.5rem;
        }

        .notification-content {
            flex: 1;
        }

        .notification-title {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .notification-message {
            font-size: 0.875rem;
            opacity: 0.95;
        }

        .notification-close {
            background: none;
            border: none;
            color: white;
            cursor: pointer;
            padding: 0.25rem;
            opacity: 0.8;
            transition: opacity 0.2s;
        }

        .notification-close:hover {
            opacity: 1;
        }

        /* Progress bar styles */
        .upload-progress-container {
            display: none;
            margin-top: 1.5rem;
            padding: 1.5rem;
            background: white;
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .upload-progress-container.show {
            display: block;
        }

        .progress-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .progress-title {
            font-weight: 600;
            color: #1f2937;
        }

        .progress-percentage {
            font-weight: bold;
            color: var(--primary-color);
            font-size: 1.25rem;
        }

        .progress-bar-container {
            height: 1rem;
            background-color: #e5e7eb;
            border-radius: 0.5rem;
            overflow: hidden;
            position: relative;
        }

        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary-color) 0%, #8b5cf6 100%);
            border-radius: 0.5rem;
            transition: width 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .progress-bar-fill::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(
                90deg,
                transparent,
                rgba(255, 255, 255, 0.3),
                transparent
            );
            animation: shimmer 2s infinite;
        }

        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        .progress-info {
            display: flex;
            justify-content: space-between;
            margin-top: 0.75rem;
            font-size: 0.875rem;
            color: #6b7280;
        }

        .progress-files {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .progress-status {
            font-weight: 500;
            color: var(--primary-color);
        }
    </style>
</head>
<body>
    <!-- Notification Container -->
    <div id="notificationContainer"></div>

    <div class="container main-container">
        <div class="text-center mb-4">
            <h1 class="text-white fw-bold mb-2">
                <i class="bi bi-cloud-upload"></i> Importação de Arquivos CSV
            </h1>
            <p class="text-white opacity-75">Sistema de importação de dados fiscais - Joana</p>
        </div>

        <!-- Upload Card -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-file-earmark-arrow-up"></i> Upload de Arquivos</h5>
            </div>
            <div class="card-body">
                <div class="upload-zone" id="uploadZone">
                    <i class="bi bi-cloud-arrow-up upload-icon"></i>
                    <h5>Arraste arquivos CSV aqui</h5>
                    <p class="text-muted mb-3">ou clique para selecionar</p>
                    <input type="file" id="fileInput" multiple accept=".csv,.txt" style="display: none;">
                    <button class="btn btn-primary" onclick="document.getElementById('fileInput').click()">
                        <i class="bi bi-folder2-open"></i> Selecionar Arquivos
                    </button>
                    <p class="text-muted mt-3 mb-0">
                        <small>Máximo de 11 arquivos por vez • Tamanho máximo: 50MB por arquivo</small>
                    </p>
                </div>

                <div id="fileList" class="mt-4" style="display: none;">
                    <h6 class="mb-3">Arquivos Selecionados:</h6>
                    <div id="fileItems"></div>
                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <button class="btn btn-outline-danger" onclick="clearFiles()">
                            <i class="bi bi-trash"></i> Limpar
                        </button>
                        <button class="btn btn-primary" onclick="uploadFiles()">
                            <i class="bi bi-upload"></i> Enviar Arquivos
                        </button>
                    </div>
                </div>

                <!-- Upload Progress Bar -->
                <div id="uploadProgress" class="upload-progress-container">
                    <div class="progress-header">
                        <span class="progress-title">
                            <i class="bi bi-cloud-upload"></i> Enviando arquivos...
                        </span>
                        <span class="progress-percentage" id="progressPercentage">0%</span>
                    </div>
                    <div class="progress-bar-container">
                        <div class="progress-bar-fill" id="progressBarFill" style="width: 0%"></div>
                    </div>
                    <div class="progress-info">
                        <div class="progress-files">
                            <i class="bi bi-file-earmark-spreadsheet"></i>
                            <span id="progressFilesText">0 de 0 arquivos</span>
                        </div>
                        <span class="progress-status" id="progressStatus">Preparando...</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Imports Card -->
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-clock-history"></i> Importações Recentes</h5>
                    <button class="btn btn-sm btn-light" onclick="refreshImports()">
                        <i class="bi bi-arrow-clockwise"></i> Atualizar
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-container">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Arquivo</th>
                                <th>Status</th>
                                <th>Total Linhas</th>
                                <th>Importadas</th>
                                <th>Data</th>
                            </tr>
                        </thead>
                        <tbody id="importsTableBody">
                            @forelse($recentImports as $import)
                            <tr data-import-id="{{ $import->id }}">
                                <td>
                                    <i class="bi bi-file-earmark-text text-primary"></i>
                                    {{ $import->filename }}
                                </td>
                                <td>
                                    <span class="status-badge status-{{ $import->status }}">
                                        @switch($import->status)
                                            @case('pending')
                                                PENDENTE
                                                @break
                                            @case('processing')
                                                PROCESSANDO
                                                @break
                                            @case('completed')
                                                CONCLUÍDO
                                                @break
                                            @case('failed')
                                                FALHOU
                                                @break
                                            @default
                                                {{ strtoupper($import->status) }}
                                        @endswitch
                                    </span>
                                </td>
                                <td>{{ $import->total_rows ?? '-' }}</td>
                                <td>{{ $import->imported_rows ?? '-' }}</td>
                                <td>{{ $import->created_at->format('d/m/Y H:i') }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5">
                                    <div class="empty-state">
                                        <i class="bi bi-inbox"></i>
                                        <p>Nenhuma importação encontrada</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let selectedFiles = [];
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        // Status translations to Portuguese
        const statusTranslations = {
            'pending': 'PENDENTE',
            'processing': 'PROCESSANDO',
            'completed': 'CONCLUÍDO',
            'failed': 'FALHOU'
        };

        function translateStatus(status) {
            return statusTranslations[status.toLowerCase()] || status.toUpperCase();
        }

        // Notification system
        function showNotification(type, title, message, duration = 5000) {
            const container = document.getElementById('notificationContainer');
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;

            const icons = {
                success: 'bi-check-circle-fill',
                error: 'bi-x-circle-fill',
                info: 'bi-info-circle-fill'
            };

            notification.innerHTML = `
                <i class="bi ${icons[type]} notification-icon"></i>
                <div class="notification-content">
                    <div class="notification-title">${title}</div>
                    <div class="notification-message">${message}</div>
                </div>
                <button class="notification-close" onclick="this.parentElement.remove()">
                    <i class="bi bi-x-lg"></i>
                </button>
            `;

            container.appendChild(notification);

            if (duration > 0) {
                setTimeout(() => {
                    notification.classList.add('hide');
                    setTimeout(() => notification.remove(), 300);
                }, duration);
            }

            return notification;
        }

        // Progress bar functions
        function showProgress() {
            document.getElementById('uploadProgress').classList.add('show');
            updateProgress(0, 'Preparando upload...');
        }

        function hideProgress() {
            setTimeout(() => {
                document.getElementById('uploadProgress').classList.remove('show');
                updateProgress(0, 'Preparando...');
            }, 1000);
        }

        function updateProgress(percentage, status, current = 0, total = 0) {
            document.getElementById('progressPercentage').textContent = Math.round(percentage) + '%';
            document.getElementById('progressBarFill').style.width = percentage + '%';
            document.getElementById('progressStatus').textContent = status;

            if (total > 0) {
                document.getElementById('progressFilesText').textContent = `${current} de ${total} arquivo(s)`;
            }
        }

        // Drag and drop handlers
        const uploadZone = document.getElementById('uploadZone');

        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            uploadZone.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            uploadZone.addEventListener(eventName, () => {
                uploadZone.classList.add('dragover');
            }, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            uploadZone.addEventListener(eventName, () => {
                uploadZone.classList.remove('dragover');
            }, false);
        });

        uploadZone.addEventListener('drop', handleDrop, false);

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            handleFiles(files);
        }

        // File input handler
        document.getElementById('fileInput').addEventListener('change', function(e) {
            handleFiles(e.target.files);
        });

        function handleFiles(files) {
            if (files.length > 11) {
                showNotification('error', 'Limite excedido', 'Você pode enviar no máximo 11 arquivos por vez!');
                return;
            }

            selectedFiles = Array.from(files).filter(file => {
                const ext = file.name.split('.').pop().toLowerCase();
                return ext === 'csv' || ext === 'txt';
            });

            if (selectedFiles.length === 0) {
                showNotification('error', 'Formato inválido', 'Por favor, selecione apenas arquivos CSV!');
                return;
            }

            if (selectedFiles.length !== files.length) {
                const rejected = files.length - selectedFiles.length;
                showNotification('info', 'Arquivos filtrados', `${rejected} arquivo(s) foram ignorados (apenas CSV são aceitos)`);
            }

            displayFiles();
        }

        function displayFiles() {
            const fileList = document.getElementById('fileList');
            const fileItems = document.getElementById('fileItems');

            if (!fileList || !fileItems) {
                return;
            }

            fileItems.innerHTML = '';

            selectedFiles.forEach((file, index) => {
                const fileItem = document.createElement('div');
                fileItem.className = 'file-item';
                fileItem.innerHTML = `
                    <div class="file-info">
                        <i class="bi bi-file-earmark-spreadsheet file-icon"></i>
                        <div>
                            <div class="fw-bold">${file.name}</div>
                            <small class="text-muted">${formatFileSize(file.size)}</small>
                        </div>
                    </div>
                    <button class="btn btn-sm btn-outline-danger" onclick="removeFile(${index})">
                        <i class="bi bi-x-lg"></i>
                    </button>
                `;
                fileItems.appendChild(fileItem);
            });

            fileList.style.display = 'block';
        }

        function removeFile(index) {
            selectedFiles.splice(index, 1);
            if (selectedFiles.length === 0) {
                document.getElementById('fileList').style.display = 'none';
            } else {
                displayFiles();
            }
        }

        function clearFiles() {
            selectedFiles = [];
            document.getElementById('fileList').style.display = 'none';
            document.getElementById('fileInput').value = '';
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        }

        async function uploadFiles() {
            if (selectedFiles.length === 0) {
                showNotification('error', 'Nenhum arquivo', 'Por favor, selecione pelo menos um arquivo!');
                return;
            }

            // Hide file list and show progress
            document.getElementById('fileList').style.display = 'none';
            showProgress();

            const totalFiles = selectedFiles.length;
            let successCount = 0;
            let errorCount = 0;
            const allUploadedFiles = [];
            const allErrors = [];

            // Upload files one by one to avoid 413 error when total size > 100MB
            for (let i = 0; i < selectedFiles.length; i++) {
                const file = selectedFiles[i];
                const currentFile = i + 1;

                try {
                    updateProgress(
                        Math.round(((i) / totalFiles) * 100),
                        `Enviando arquivo ${currentFile} de ${totalFiles}: ${file.name}...`,
                        i,
                        totalFiles
                    );

                    const formData = new FormData();
                    formData.append('files[]', file);

                    const response = await fetch('/joana/upload', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: formData
                    });

                    // Check if response is OK
                    if (!response.ok) {
                        let errorMessage = 'Erro ao enviar arquivo';

                        if (response.status === 413) {
                            errorMessage = `Arquivo muito grande! ${file.name} excede 100MB.`;
                        } else if (response.status === 422) {
                            errorMessage = `Validação falhou para ${file.name}`;
                        } else if (response.status === 500) {
                            errorMessage = `Erro no servidor ao processar ${file.name}`;
                        }

                        allErrors.push({ filename: file.name, error: errorMessage });
                        errorCount++;
                        continue;
                    }

                    const result = await response.json();

                    if (result.success) {
                        successCount++;
                        if (result.files) {
                            allUploadedFiles.push(...result.files);
                        }
                    } else {
                        errorCount++;
                        allErrors.push({
                            filename: file.name,
                            error: result.message || 'Erro desconhecido'
                        });
                    }

                    if (result.errors && result.errors.length > 0) {
                        allErrors.push(...result.errors);
                        errorCount++;
                    }

                } catch (error) {
                    console.error('Error uploading file:', file.name, error);
                    errorCount++;
                    allErrors.push({
                        filename: file.name,
                        error: 'Erro de conexão ao enviar arquivo'
                    });
                }
            }

            // Show final results
            updateProgress(100, 'Upload concluído!', totalFiles, totalFiles);

            if (successCount > 0) {
                showNotification('success', 'Upload realizado!',
                    `${successCount} de ${totalFiles} arquivo(s) enviado(s) com sucesso`, 5000);
            }

            if (allErrors.length > 0) {
                allErrors.forEach(error => {
                    showNotification('error', 'Erro no arquivo',
                        `${error.filename}: ${error.error}`, 8000);
                });
            }

            clearFiles();

            // Start polling for updates
            if (allUploadedFiles.length > 0) {
                allUploadedFiles.forEach((file, index) => {
                    setTimeout(() => {
                        pollImportStatus(file.import_log_id);
                    }, 500 + (index * 100));
                });
            }

            setTimeout(() => {
                hideProgress();
            }, 2000);
        }

        async function refreshImports() {
            try {
                const response = await fetch('/joana/recent');
                const result = await response.json();

                if (result.success) {
                    updateImportsTable(result.data);
                }
            } catch (error) {
                console.error('Error refreshing imports:', error);
            }
        }

        function updateImportsTable(imports) {
            const tbody = document.getElementById('importsTableBody');

            if (imports.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="5">
                            <div class="empty-state">
                                <i class="bi bi-inbox"></i>
                                <p>Nenhuma importação encontrada</p>
                            </div>
                        </td>
                    </tr>
                `;
                return;
            }

            // Build HTML for all imports
            const rows = imports.map(imp => {
                const statusClass = `status-${imp.status}`;
                const statusText = translateStatus(imp.status);
                const totalRows = imp.total_rows || '-';
                const importedRows = imp.imported_rows || '-';

                return `
                    <tr data-import-id="${imp.id}">
                        <td>
                            <i class="bi bi-file-earmark-text text-primary"></i>
                            ${imp.filename}
                        </td>
                        <td>
                            <span class="status-badge ${statusClass}">
                                ${statusText}
                            </span>
                        </td>
                        <td>${totalRows}</td>
                        <td>${importedRows}</td>
                        <td>${formatDate(imp.created_at)}</td>
                    </tr>
                `;
            }).join('');

            tbody.innerHTML = rows;
        }

        function updateSingleImport(importData) {
            const row = document.querySelector(`tr[data-import-id="${importData.id}"]`);

            if (!row) {
                // If row doesn't exist, add it to the table
                addNewImportRow(importData);
                return;
            }

            // Update only the status, total_rows, and imported_rows cells
            const statusCell = row.cells[1];
            const totalRowsCell = row.cells[2];
            const importedRowsCell = row.cells[3];

            const statusClass = `status-${importData.status}`;
            const statusText = translateStatus(importData.status);

            // Add highlight effect when status changes
            const oldStatus = statusCell.querySelector('.status-badge')?.textContent.trim();

            if (oldStatus && oldStatus !== statusText) {
                row.style.backgroundColor = '#e0e7ff';
                setTimeout(() => {
                    row.style.backgroundColor = '';
                }, 1000);
            }

            statusCell.innerHTML = `<span class="status-badge ${statusClass}">${statusText}</span>`;
            totalRowsCell.textContent = importData.total_rows || '-';
            importedRowsCell.textContent = importData.imported_rows || '-';
        }

        function addNewImportRow(importData) {
            const tbody = document.getElementById('importsTableBody');

            // Check if row already exists by ID - if yes, just update it
            const existingRowById = tbody.querySelector(`tr[data-import-id="${importData.id}"]`);
            if (existingRowById) {
                updateSingleImportInternal(importData);
                return;
            }

            // Also check by filename to catch any edge cases
            const allRows = tbody.querySelectorAll('tr[data-import-id]');
            for (let row of allRows) {
                const fileNameCell = row.cells[0];
                if (fileNameCell && fileNameCell.textContent.includes(importData.filename)) {
                    // Update the row's ID attribute to match
                    row.setAttribute('data-import-id', importData.id);
                    updateSingleImportInternal(importData);
                    return;
                }
            }

            // Check if empty state exists
            const emptyState = tbody.querySelector('.empty-state');
            if (emptyState) {
                tbody.innerHTML = '';
            }

            const statusClass = `status-${importData.status}`;
            const statusText = translateStatus(importData.status);
            const totalRows = importData.total_rows || '-';
            const importedRows = importData.imported_rows || '-';


            const newRow = document.createElement('tr');
            newRow.setAttribute('data-import-id', importData.id);
            newRow.innerHTML = `
                <td>
                    <i class="bi bi-file-earmark-text text-primary"></i>
                    ${importData.filename}
                </td>
                <td>
                    <span class="status-badge ${statusClass}">
                        ${statusText}
                    </span>
                </td>
                <td>${totalRows}</td>
                <td>${importedRows}</td>
                <td>${formatDate(importData.created_at)}</td>
            `;

            // Insert at the beginning
            tbody.insertBefore(newRow, tbody.firstChild);

            // Keep only 20 rows
            while (tbody.children.length > 20) {
                tbody.removeChild(tbody.lastChild);
            }
        }

        function updateSingleImportInternal(importData) {
            // Internal function that only updates, never adds
            const row = document.querySelector(`tr[data-import-id="${importData.id}"]`);

            if (!row) {
                return; // Don't add, just skip
            }

            // Update only the status, total_rows, and imported_rows cells
            const statusCell = row.cells[1];
            const totalRowsCell = row.cells[2];
            const importedRowsCell = row.cells[3];

            const statusClass = `status-${importData.status}`;
            const statusText = translateStatus(importData.status);

            // Add highlight effect when status changes
            const oldStatus = statusCell.querySelector('.status-badge')?.textContent.trim();
            if (oldStatus && oldStatus !== statusText) {
                row.style.backgroundColor = '#e0e7ff';
                setTimeout(() => {
                    row.style.backgroundColor = '';
                }, 1000);
            }

            statusCell.innerHTML = `<span class="status-badge ${statusClass}">${statusText}</span>`;
            totalRowsCell.textContent = importData.total_rows || '-';
            importedRowsCell.textContent = importData.imported_rows || '-';
        }

        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('pt-BR') + ' ' + date.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
        }

        function pollImportStatus(importLogId) {
            let pollCount = 0;
            const maxPolls = 150; // 5 minutes (150 * 2s = 300s)

            const interval = setInterval(async () => {
                pollCount++;

                try {
                    const response = await fetch(`/joana/status/${importLogId}`);
                    const result = await response.json();

                    if (result.success) {
                        const status = result.data.status;

                        // Update only this specific import row
                        updateSingleImport(result.data);

                        // If completed or failed, stop polling
                        if (status === 'completed' || status === 'failed') {
                            clearInterval(interval);
                        }

                        // If stuck in pending for too long (5 minutes), stop polling and mark as timeout
                        if (status === 'pending' && pollCount >= maxPolls) {
                            clearInterval(interval);

                            // Update row to show it's stuck
                            const row = document.querySelector(`tr[data-import-id="${importLogId}"]`);
                            if (row) {
                                row.style.opacity = '0.5';
                                row.title = 'Importação travada - Tente novamente';
                            }
                        }
                    }
                } catch (error) {
                    console.error('Error polling status:', error);
                    clearInterval(interval);
                }
            }, 2000); // Poll every 2 seconds
        }

        // Auto-refresh disabled - using polling instead
        // setInterval(refreshImports, 10000);
    </script>
</body>
</html>

<?php
require_once '../conexion.php';

// Obtener catálogo de especialidades para el filtro
$stmtEsp = $conexion->query("SELECT id_especialidad, nombre FROM especialidades ORDER BY nombre ASC");
$listaEspecialidades = $stmtEsp->fetchAll(PDO::FETCH_ASSOC);

// Parámetros de filtrado
$buscar = trim($_GET['buscar'] ?? '');
$filtroEstado = trim($_GET['estado'] ?? '');
$filtroEspecialidad = trim($_GET['especialidad'] ?? '');
$filtroContrato = trim($_GET['contrato'] ?? '');

// Consulta base con JOINs
$sql = "SELECT d.id_persona,
               d.codigo_docente,
               d.fecha_contratacion,
               d.estado_laboral,
               p.nombres,
               p.apellidos,
               p.documento_identidad,
               p.telefono,
               p.correo,
               esp.nombre AS especialidad,
               esp.id_especialidad,
               cd.titulo,
               cd.estado_verificacion
        FROM docentes d
        INNER JOIN personas p ON d.id_persona = p.id_persona
        LEFT JOIN docente_especialidad de ON d.id_persona = de.id_docente
        LEFT JOIN especialidades esp ON de.id_especialidad = esp.id_especialidad
        LEFT JOIN credenciales_docentes cd ON d.id_persona = cd.id_docente
        WHERE 1=1";

$params = [];

if ($buscar !== '') {
    $sql .= " AND (p.nombres LIKE :b1 
               OR p.apellidos LIKE :b2 
               OR p.documento_identidad LIKE :b3 
               OR d.codigo_docente LIKE :b4 
               OR cd.titulo LIKE :b5)";
    $paramBuscar = "%$buscar%";
    $params[':b1'] = $paramBuscar;
    $params[':b2'] = $paramBuscar;
    $params[':b3'] = $paramBuscar;
    $params[':b4'] = $paramBuscar;
    $params[':b5'] = $paramBuscar;
}

if ($filtroEstado !== '') {
    $sql .= " AND d.estado_laboral = :estado";
    $params[':estado'] = $filtroEstado;
}

if ($filtroEspecialidad !== '') {
    $sql .= " AND esp.id_especialidad = :especialidad";
    $params[':especialidad'] = $filtroEspecialidad;
}

// Filtro de Contratación / Renovación
if ($filtroContrato === 'renovacion') {
    // Contratados hace más de 1 año (requieren revisión/renovación anual de gestión)
    $sql .= " AND d.fecha_contratacion < DATE_SUB(CURDATE(), INTERVAL 1 YEAR) AND d.estado_laboral != 'RETIRADO'";
} elseif ($filtroContrato === 'recientes') {
    // Contratados en la gestión actual (2026)
    $sql .= " AND YEAR(d.fecha_contratacion) = YEAR(CURDATE())";
} elseif ($filtroContrato === 'vigentes') {
    $sql .= " AND d.estado_laboral = 'ACTIVO'";
}

$sql .= " ORDER BY d.id_persona DESC";

$stmt = $conexion->prepare($sql);
$stmt->execute($params);
$docentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Contadores para métricas
$totalDocentes = (int)$conexion->query("SELECT COUNT(*) FROM docentes")->fetchColumn();
$totalActivos = (int)$conexion->query("SELECT COUNT(*) FROM docentes WHERE estado_laboral = 'ACTIVO'")->fetchColumn();
$totalLicencia = (int)$conexion->query("SELECT COUNT(*) FROM docentes WHERE estado_laboral = 'LICENCIA'")->fetchColumn();
$totalPorRenovar = (int)$conexion->query("SELECT COUNT(*) FROM docentes WHERE fecha_contratacion < DATE_SUB(CURDATE(), INTERVAL 1 YEAR) AND estado_laboral != 'RETIRADO'")->fetchColumn();

// Mensajes de alerta
$mensaje = '';
$tipoMensaje = 'success';
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'creado') $mensaje = '✅ Docente contratado y registrado exitosamente.';
    elseif ($_GET['msg'] === 'actualizado') $mensaje = '✅ Ficha del docente actualizada con éxito.';
    elseif ($_GET['msg'] === 'renovado') $mensaje = '🎉 ¡Contrato docente renovado exitosamente para la nueva gestión!';
    elseif ($_GET['msg'] === 'eliminado') $mensaje = '🗑️ Registro docente dado de baja correctamente.';
    elseif ($_GET['msg'] === 'error') {
        $mensaje = '❌ Ocurrió un error al procesar los datos.';
        $tipoMensaje = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plana Docente | Colegio Pedagógico</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-body: #0b0f19;
            --card-bg: rgba(22, 29, 47, 0.75);
            --card-border: rgba(255, 255, 255, 0.08);
            --text-main: #f3f4f6;
            --text-muted: #9ca3af;
            --primary: #6366f1;
            --primary-hover: #4f46e5;
            --emerald: #10b981;
            --amber: #f59e0b;
            --rose: #ef4444;
            --cyan: #06b6d4;
            --chip-bg: rgba(255, 255, 255, 0.04);
            --table-hover: rgba(255, 255, 255, 0.03);
            --metric-bg: rgba(15, 23, 42, 0.6);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--bg-body);
            color: var(--text-main);
            min-height: 100vh;
            padding: 2.5rem 1.5rem;
            position: relative;
        }

        .container {
            max-width: 1280px;
            margin: 0 auto;
        }

        /* Ambient Glow */
        body::before {
            content: '';
            position: fixed;
            top: -15%;
            left: 20%;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(16, 185, 129, 0.12) 0%, rgba(0,0,0,0) 70%);
            z-index: 0;
            pointer-events: none;
        }

        /* Header Navigation */
        .header-nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1.5rem;
            margin-bottom: 2rem;
            position: relative;
            z-index: 1;
        }

        .header-title {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .icon-badge {
            width: 50px;
            height: 50px;
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #34d399;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
        }

        .header-title h1 {
            font-size: 1.65rem;
            font-weight: 800;
            letter-spacing: -0.02em;
        }

        .header-title p {
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.65rem 1.25rem;
            border-radius: 10px;
            font-size: 0.875rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s ease;
            cursor: pointer;
            border: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--emerald), #059669);
            color: #ffffff;
            box-shadow: 0 4px 14px rgba(16, 185, 129, 0.35);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.5);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--card-border);
            color: var(--text-main);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        /* Alert message */
        .alert {
            padding: 1rem 1.25rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
            z-index: 1;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.12);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #34d399;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.12);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #f87171;
        }

        /* Metrics / KPIs */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
            position: relative;
            z-index: 1;
        }

        .metric-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 16px;
            padding: 1.15rem 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .metric-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--text-muted);
            margin-bottom: 0.25rem;
        }

        .metric-value {
            font-size: 1.35rem;
            font-weight: 800;
            color: #ffffff;
            font-family: 'JetBrains Mono', monospace;
        }

        .metric-badge {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }

        /* Filter Panel */
        .filter-card {
            background: var(--card-bg);
            backdrop-filter: blur(14px);
            border: 1px solid var(--card-border);
            border-radius: 18px;
            padding: 1.25rem 1.5rem;
            margin-bottom: 1.5rem;
            position: relative;
            z-index: 1;
        }

        .filter-title {
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #34d399;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .filters-form {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr auto;
            gap: 0.85rem;
            align-items: center;
        }

        @media (max-width: 1024px) {
            .filters-form {
                grid-template-columns: 1fr 1fr;
            }
        }
        @media (max-width: 640px) {
            .filters-form {
                grid-template-columns: 1fr;
            }
        }

        .filter-input {
            width: 100%;
            background: rgba(15, 23, 42, 0.7);
            border: 1px solid var(--card-border);
            border-radius: 10px;
            padding: 0.65rem 0.95rem;
            font-size: 0.875rem;
            color: #ffffff;
            outline: none;
            transition: all 0.2s ease;
            font-family: inherit;
        }

        .filter-input:focus {
            border-color: #34d399;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2);
        }

        select.filter-input option {
            background: #111827;
            color: #ffffff;
        }

        .filter-btn-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-filter {
            background: #10b981;
            color: #ffffff;
            padding: 0.65rem 1.15rem;
            border-radius: 10px;
            font-size: 0.85rem;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: background 0.15s ease;
        }
        .btn-filter:hover {
            background: #059669;
        }

        .btn-reset {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--card-border);
            color: var(--text-muted);
            padding: 0.65rem 0.85rem;
            border-radius: 10px;
            font-size: 0.85rem;
            text-decoration: none;
            transition: all 0.15s ease;
        }
        .btn-reset:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #ffffff;
        }

        /* Table Card */
        .table-card {
            background: var(--card-bg);
            backdrop-filter: blur(16px);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
            overflow: hidden;
            position: relative;
            z-index: 1;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        thead {
            background: rgba(15, 23, 42, 0.7);
            border-bottom: 1px solid var(--card-border);
        }

        th {
            padding: 1rem 1.15rem;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
        }

        td {
            padding: 1.05rem 1.15rem;
            font-size: 0.875rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
            color: #d1d5db;
        }

        tbody tr:hover {
            background: var(--table-hover);
        }

        .col-code {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.8rem;
            font-weight: 600;
            color: #6ee7b7;
        }

        .col-name {
            font-weight: 600;
            color: #ffffff;
        }

        .badge-especialidad {
            background: rgba(6, 182, 212, 0.12);
            border: 1px solid rgba(6, 182, 212, 0.3);
            color: #67e8f9;
            padding: 0.3rem 0.65rem;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 500;
            display: inline-block;
        }

        /* Status badges */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.25rem 0.65rem;
            border-radius: 9999px;
            font-size: 0.725rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .status-activo {
            background: rgba(16, 185, 129, 0.12);
            color: #34d399;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .status-licencia {
            background: rgba(245, 158, 11, 0.12);
            color: #fbbf24;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }

        .status-retirado {
            background: rgba(239, 68, 68, 0.12);
            color: #f87171;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        /* Renovation alert chip */
        .renovacion-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            font-size: 0.75rem;
            color: #fbbf24;
            background: rgba(245, 158, 11, 0.1);
            border: 1px solid rgba(245, 158, 11, 0.25);
            padding: 0.2rem 0.5rem;
            border-radius: 6px;
            margin-top: 0.25rem;
        }

        /* Action buttons */
        .actions-cell {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            justify-content: flex-end;
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.35rem 0.7rem;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.15s ease;
            border: 1px solid transparent;
            cursor: pointer;
        }

        .btn-action.ver {
            background: rgba(99, 102, 241, 0.12);
            border-color: rgba(99, 102, 241, 0.3);
            color: #a5b4fc;
        }
        .btn-action.ver:hover {
            background: var(--primary);
            color: #ffffff;
        }

        .btn-action.renovar {
            background: rgba(16, 185, 129, 0.15);
            border-color: rgba(16, 185, 129, 0.35);
            color: #34d399;
        }
        .btn-action.renovar:hover {
            background: var(--emerald);
            color: #ffffff;
        }

        .btn-action.editar {
            background: rgba(59, 130, 246, 0.12);
            border-color: rgba(59, 130, 246, 0.3);
            color: #93c5fd;
        }
        .btn-action.editar:hover {
            background: #2563eb;
            color: #ffffff;
        }

        .btn-action.eliminar {
            background: rgba(239, 68, 68, 0.12);
            border-color: rgba(239, 68, 68, 0.3);
            color: #fca5a5;
        }
        .btn-action.eliminar:hover {
            background: var(--rose);
            color: #ffffff;
        }

        /* Modal de Renovación */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.75);
            backdrop-filter: blur(8px);
            z-index: 999;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .modal-content {
            background: #111827;
            border: 1px solid var(--card-border);
            border-radius: 20px;
            max-width: 480px;
            width: 100%;
            padding: 2rem;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5);
            animation: modalFadeIn 0.25s ease-out;
        }

        @keyframes modalFadeIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }

        .empty-state {
            padding: 3.5rem 1.5rem;
            text-align: center;
            color: var(--text-muted);
        }
        .empty-state span {
            font-size: 2.5rem;
            display: block;
            margin-bottom: 0.75rem;
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Header -->
    <header class="header-nav">
        <div class="header-title">
            <div class="icon-badge">👨‍🏫</div>
            <div>
                <h1>Gestión de Plana Docente</h1>
                <p>Colegio Pedagógico &bull; Contrataciones, Renovaciones y Especialidades</p>
            </div>
        </div>

        <div class="header-actions">
            <a href="../index.php" class="btn btn-secondary">
                <span>&larr; Menú Principal</span>
            </a>
            <a href="crear.php" class="btn btn-primary">
                <span>+ Contratar Docente</span>
            </a>
        </div>
    </header>

    <!-- Feedback alert -->
    <?php if ($mensaje): ?>
        <div class="alert alert-<?= $tipoMensaje ?>">
            <span><?= htmlspecialchars($mensaje) ?></span>
            <button onclick="this.parentElement.remove();" style="background:none;border:none;color:inherit;cursor:pointer;font-size:1.1rem;">&times;</button>
        </div>
    <?php endif; ?>

    <!-- KPIs de Contratación y Estado -->
    <div class="metrics-grid">
        <div class="metric-card">
            <div>
                <div class="metric-label">Total Docentes</div>
                <div class="metric-value"><?= $totalDocentes ?></div>
            </div>
            <div class="metric-badge" style="background: rgba(99, 102, 241, 0.15); color: #818cf8;">👥</div>
        </div>

        <div class="metric-card">
            <div>
                <div class="metric-label">Contratos Activos</div>
                <div class="metric-value" style="color: #34d399;"><?= $totalActivos ?></div>
            </div>
            <div class="metric-badge" style="background: rgba(16, 185, 129, 0.15); color: #34d399;">✓</div>
        </div>

        <div class="metric-card">
            <div>
                <div class="metric-label">En Licencia</div>
                <div class="metric-value" style="color: #fbbf24;"><?= $totalLicencia ?></div>
            </div>
            <div class="metric-badge" style="background: rgba(245, 158, 11, 0.15); color: #fbbf24;">⏸</div>
        </div>

        <div class="metric-card">
            <div>
                <div class="metric-label">Para Renovación</div>
                <div class="metric-value" style="color: #f59e0b;"><?= $totalPorRenovar ?></div>
            </div>
            <div class="metric-badge" style="background: rgba(245, 158, 11, 0.15); color: #fbbf24;">🔄</div>
        </div>
    </div>

    <!-- Panel de Búsqueda y Filtros Múltiples -->
    <div class="filter-card">
        <div class="filter-title">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
            </svg>
            <span>Filtros de Búsqueda y Contratación</span>
        </div>

        <form method="GET" action="index.php" class="filters-form">
            <!-- 1. Búsqueda por texto libre -->
            <div>
                <input type="text" name="buscar" value="<?= htmlspecialchars($buscar) ?>" class="filter-input" placeholder="Buscar por nombre, CI, código o título...">
            </div>

            <!-- 2. Filtro por Estado Laboral -->
            <div>
                <select name="estado" class="filter-input">
                    <option value="">-- Estado Laboral --</option>
                    <option value="ACTIVO" <?= $filtroEstado === 'ACTIVO' ? 'selected' : '' ?>>ACTIVO</option>
                    <option value="LICENCIA" <?= $filtroEstado === 'LICENCIA' ? 'selected' : '' ?>>LICENCIA</option>
                    <option value="RETIRADO" <?= $filtroEstado === 'RETIRADO' ? 'selected' : '' ?>>RETIRADO</option>
                </select>
            </div>

            <!-- 3. Filtro por Especialidad -->
            <div>
                <select name="especialidad" class="filter-input">
                    <option value="">-- Especialidad --</option>
                    <?php foreach ($listaEspecialidades as $esp): ?>
                        <option value="<?= $esp['id_especialidad'] ?>" <?= $filtroEspecialidad == $esp['id_especialidad'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($esp['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- 4. Filtro por Estado del Contrato -->
            <div>
                <select name="contrato" class="filter-input">
                    <option value="">-- Renovación / Vigencia --</option>
                    <option value="renovacion" <?= $filtroContrato === 'renovacion' ? 'selected' : '' ?>>Pendientes de Renovación (&gt; 1 año)</option>
                    <option value="recientes" <?= $filtroContrato === 'recientes' ? 'selected' : '' ?>>Contrataciones 2026</option>
                    <option value="vigentes" <?= $filtroContrato === 'vigentes' ? 'selected' : '' ?>>Solo Activos</option>
                </select>
            </div>

            <!-- Botones -->
            <div class="filter-btn-group">
                <button type="submit" class="btn-filter">Filtrar</button>
                <?php if ($buscar || $filtroEstado || $filtroEspecialidad || $filtroContrato): ?>
                    <a href="index.php" class="btn-reset" title="Limpiar filtros">&times;</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Table Card -->
    <div class="table-card">
        <?php if (!empty($docentes)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Docente (Identidad)</th>
                        <th>Especialidad / Título</th>
                        <th>Fecha Contratación</th>
                        <th>Estado Laboral</th>
                        <th style="text-align: right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($docentes as $doc): ?>
                        <?php
                            // Evaluar necesidad de renovación (> 1 año de contrato)
                            $fContratacion = new DateTime($doc['fecha_contratacion']);
                            $hoy = new DateTime();
                            $antiguedad = $fContratacion->diff($hoy);
                            $requiereRenovacion = ($antiguedad->y >= 1 && $doc['estado_laboral'] === 'ACTIVO');
                        ?>
                        <tr>
                            <td class="col-code">
                                <?= htmlspecialchars($doc['codigo_docente']) ?>
                            </td>

                            <td>
                                <div class="col-name">
                                    <?= htmlspecialchars(trim($doc['nombres'] . ' ' . $doc['apellidos'])) ?>
                                </div>
                                <div style="font-size: 0.75rem; color: var(--text-muted); font-family: 'JetBrains Mono', monospace;">
                                    CI: <?= htmlspecialchars($doc['documento_identidad'] ?? 'S/D') ?> &bull; Tel: <?= htmlspecialchars($doc['telefono'] ?? '-') ?>
                                </div>
                            </td>

                            <td>
                                <span class="badge-especialidad">
                                    <?= htmlspecialchars($doc['especialidad'] ?? 'General') ?>
                                </span>
                                <?php if (!empty($doc['titulo'])): ?>
                                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem;">
                                        🎓 <?= htmlspecialchars($doc['titulo']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <td>
                                <div style="font-family: 'JetBrains Mono', monospace; font-size: 0.85rem;">
                                    <?= htmlspecialchars($doc['fecha_contratacion']) ?>
                                </div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">
                                    <?= $antiguedad->y ?>a <?= $antiguedad->m ?>m de servicio
                                </div>
                                <?php if ($requiereRenovacion): ?>
                                    <span class="renovacion-chip" title="Contrato con más de 1 año lectivo">
                                        ⚠️ Revisar Renovación
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php
                                    $est = strtoupper($doc['estado_laboral']);
                                    $clase = 'status-retirado';
                                    if ($est === 'ACTIVO') $clase = 'status-activo';
                                    elseif ($est === 'LICENCIA') $clase = 'status-licencia';
                                ?>
                                <span class="status-badge <?= $clase ?>">
                                    <?= htmlspecialchars($est) ?>
                                </span>
                            </td>

                            <td>
                                <div class="actions-cell">
                                    <!-- Botón rápido de Renovación -->
                                    <button onclick="abrirModalRenovacion(<?= $doc['id_persona'] ?>, '<?= htmlspecialchars(addslashes($doc['codigo_docente'])) ?>', '<?= htmlspecialchars(addslashes($doc['nombres'] . ' ' . $doc['apellidos'])) ?>', '<?= $doc['fecha_contratacion'] ?>')" 
                                            class="btn-action renovar" 
                                            title="Renovar Contrato">
                                        🔄 Renovar
                                    </button>

                                    <a class="btn-action ver" href="ver.php?id=<?= $doc['id_persona'] ?>" title="Ver Ficha y Currículum">
                                        Ver
                                    </a>

                                    <a class="btn-action editar" href="editar.php?id=<?= $doc['id_persona'] ?>" title="Editar">
                                        Editar
                                    </a>

                                    <a class="btn-action eliminar" 
                                       href="eliminar.php?id=<?= $doc['id_persona'] ?>" 
                                       onclick="return confirm('¿Confirma que desea eliminar el registro docente de <?= htmlspecialchars(addslashes($doc['nombres'] . ' ' . $doc['apellidos'])) ?>?');" 
                                       title="Eliminar">
                                        Eliminar
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">
                <span>🔍</span>
                <h3>No se encontraron docentes</h3>
                <p>No hay registros que coincidan con los filtros aplicados. Intenta restablecer los filtros.</p>
                <a href="index.php" class="btn btn-secondary" style="margin-top: 1rem;">Limpiar Filtros</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal de Renovación Rápida de Contrato -->
<div id="modal-renovacion" class="modal">
    <div class="modal-content">
        <h2 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 0.5rem; color: #ffffff;">🔄 Renovar Contrato Docente</h2>
        <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1.5rem;">
            Actualiza el periodo de vigencia laboral para el año escolar activo.
        </p>

        <form action="procesar.php" method="POST">
            <input type="hidden" name="accion" value="renovar">
            <input type="hidden" name="id_persona" id="modal-id-persona">

            <div style="margin-bottom: 1rem; background: rgba(255,255,255,0.03); padding: 0.85rem; border-radius: 10px; border: 1px solid var(--card-border);">
                <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase;">Docente</div>
                <div id="modal-nombre-docente" style="font-weight: 700; color: #ffffff; font-size: 1rem;"></div>
                <div id="modal-codigo-docente" style="font-size: 0.8rem; color: #6ee7b7; font-family: 'JetBrains Mono', monospace;"></div>
            </div>

            <div style="margin-bottom: 1.25rem;">
                <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #d1d5db; margin-bottom: 0.4rem;">Nueva Fecha de Inicio / Renovación de Contrato</label>
                <input type="date" name="fecha_contratacion" id="modal-fecha-contratacion" value="<?= date('Y-m-d') ?>" class="filter-input" required>
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #d1d5db; margin-bottom: 0.4rem;">Estado Laboral al Renovar</label>
                <select name="estado_laboral" class="filter-input" required>
                    <option value="ACTIVO" selected>ACTIVO (Contrato Vigente)</option>
                    <option value="LICENCIA">LICENCIA (Permiso Especial)</option>
                </select>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                <button type="button" onclick="cerrarModalRenovacion()" class="btn btn-secondary">Cancelar</button>
                <button type="submit" class="btn btn-primary">Confirmar Renovación</button>
            </div>
        </form>
    </div>
</div>

<script>
function abrirModalRenovacion(id, codigo, nombre, fechaActual) {
    document.getElementById('modal-id-persona').value = id;
    document.getElementById('modal-codigo-docente').textContent = codigo;
    document.getElementById('modal-nombre-docente').textContent = nombre;
    document.getElementById('modal-renovacion').style.display = 'flex';
}

function cerrarModalRenovacion() {
    document.getElementById('modal-renovacion').style.display = 'none';
}

window.onclick = function(event) {
    const modal = document.getElementById('modal-renovacion');
    if (event.target === modal) {
        cerrarModalRenovacion();
    }
}
</script>

</body>
</html>
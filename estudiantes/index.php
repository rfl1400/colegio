<?php
require_once '../conexion.php';

// Obtener grados y paralelos para los filtros
$grados = $conexion->query("SELECT id_grado, nombre FROM grados ORDER BY id_grado ASC")->fetchAll(PDO::FETCH_ASSOC);
$paralelos = $conexion->query("SELECT id_paralelo, nombre FROM paralelos ORDER BY id_paralelo ASC")->fetchAll(PDO::FETCH_ASSOC);
$niveles = $conexion->query("SELECT id_nivel, nombre FROM niveles_academicos ORDER BY orden ASC")->fetchAll(PDO::FETCH_ASSOC);

// Parámetros de búsqueda y filtros
$buscar          = trim($_GET['buscar'] ?? '');
$filtroNivel     = trim($_GET['nivel'] ?? '');
$filtroGrado     = trim($_GET['grado'] ?? '');
$filtroParalelo  = trim($_GET['paralelo'] ?? '');
$filtroEstado    = trim($_GET['estado'] ?? '');

// Consulta combinando estudiantes, personas, matrículas, ofertas y tutores
$sql = "SELECT e.id_persona,
               e.codigo_estudiante,
               e.fecha_ingreso,
               e.observaciones,
               p.nombres,
               p.apellidos,
               p.documento_identidad,
               p.telefono,
               p.correo,
               p.fecha_nacimiento,
               n.nombre AS nivel,
               gr.nombre AS grado,
               pa.nombre AS paralelo,
               m.estado AS estado_matricula,
               m.tipo_ingreso,
               m.fecha_matricula,
               c.nombre_completo AS tutor_nombre,
               c.telefono AS tutor_telefono,
               c.parentesco AS tutor_parentesco,
               (SELECT COUNT(*) FROM contactos_estudiantes csub 
                WHERE TRIM(csub.telefono) = TRIM(c.telefono) AND csub.telefono != '' AND c.telefono IS NOT NULL) AS total_hermanos
        FROM estudiantes e
        INNER JOIN personas p ON e.id_persona = p.id_persona
        LEFT JOIN matriculas m ON e.id_persona = m.id_estudiante
        LEFT JOIN ofertas_academicas o ON m.id_oferta = o.id_oferta
        LEFT JOIN grados gr ON o.id_grado = gr.id_grado
        LEFT JOIN niveles_academicos n ON gr.id_nivel = n.id_nivel
        LEFT JOIN paralelos pa ON o.id_paralelo = pa.id_paralelo
        LEFT JOIN contactos_estudiantes c ON e.id_persona = c.id_estudiante AND c.es_representante = 1
        WHERE 1=1";

$params = [];

if ($buscar !== '') {
    $sql .= " AND (p.nombres LIKE :b1 
               OR p.apellidos LIKE :b2 
               OR p.documento_identidad LIKE :b3 
               OR e.codigo_estudiante LIKE :b4
               OR c.nombre_completo LIKE :b5)";
    $paramBuscar = "%$buscar%";
    $params[':b1'] = $paramBuscar;
    $params[':b2'] = $paramBuscar;
    $params[':b3'] = $paramBuscar;
    $params[':b4'] = $paramBuscar;
    $params[':b5'] = $paramBuscar;
}

if ($filtroNivel !== '') {
    $sql .= " AND n.nombre = :nivel";
    $params[':nivel'] = $filtroNivel;
}

if ($filtroGrado !== '') {
    $sql .= " AND gr.id_grado = :grado";
    $params[':grado'] = $filtroGrado;
}

if ($filtroParalelo !== '') {
    $sql .= " AND pa.nombre = :paralelo";
    $params[':paralelo'] = $filtroParalelo;
}

if ($filtroEstado !== '') {
    $sql .= " AND m.estado = :estado";
    $params[':estado'] = $filtroEstado;
}

$sql .= " ORDER BY e.id_persona DESC";

$stmt = $conexion->prepare($sql);
$stmt->execute($params);
$estudiantes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Métricas de Estudiantes
$totalEstudiantes = (int)$conexion->query("SELECT COUNT(*) FROM estudiantes")->fetchColumn();
$totalPrimaria    = (int)$conexion->query("SELECT COUNT(*) FROM matriculas m JOIN ofertas_academicas o ON m.id_oferta=o.id_oferta JOIN grados g ON o.id_grado=g.id_grado WHERE g.id_nivel = 2 AND m.estado='ACTIVA'")->fetchColumn();
$totalSecundaria  = (int)$conexion->query("SELECT COUNT(*) FROM matriculas m JOIN ofertas_academicas o ON m.id_oferta=o.id_oferta JOIN grados g ON o.id_grado=g.id_grado WHERE g.id_nivel = 3 AND m.estado='ACTIVA'")->fetchColumn();
$totalNuevos      = (int)$conexion->query("SELECT COUNT(*) FROM matriculas WHERE tipo_ingreso = 'NUEVO' AND estado='ACTIVA'")->fetchColumn();

// Mensajes de Alerta
$mensaje = '';
$tipoMensaje = 'success';
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'creado') $mensaje = '✅ Estudiante matriculado y registrado exitosamente.';
    elseif ($_GET['msg'] === 'actualizado') $mensaje = '✅ Datos del estudiante actualizados con éxito.';
    elseif ($_GET['msg'] === 'eliminado') $mensaje = '🗑️ Estudiante eliminado del registro escolar.';
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
    <title>Estudiantes Matriculados | Colegio Pedagógico</title>
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
            --primary: #3b82f6;
            --primary-hover: #2563eb;
            --emerald: #10b981;
            --amber: #f59e0b;
            --rose: #ef4444;
            --purple: #8b5cf6;
            --chip-bg: rgba(255, 255, 255, 0.04);
            --table-hover: rgba(255, 255, 255, 0.03);
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
            max-width: 1320px;
            margin: 0 auto;
        }

        /* Ambient Glow */
        body::before {
            content: '';
            position: fixed;
            top: -15%;
            left: 20%;
            width: 650px;
            height: 650px;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.12) 0%, rgba(0,0,0,0) 70%);
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
            background: rgba(59, 130, 246, 0.15);
            border: 1px solid rgba(59, 130, 246, 0.3);
            color: #60a5fa;
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
            background: linear-gradient(135deg, var(--primary), var(--primary-hover));
            color: #ffffff;
            box-shadow: 0 4px 14px rgba(59, 130, 246, 0.35);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.5);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--card-border);
            color: var(--text-main);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        /* Alert */
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

        /* Metrics */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
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
            color: #60a5fa;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .filters-form {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr 1fr auto;
            gap: 0.85rem;
            align-items: center;
        }

        @media (max-width: 1024px) {
            .filters-form {
                grid-template-columns: 1fr 1fr 1fr;
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
            border-color: #60a5fa;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
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
            background: #3b82f6;
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
            background: #2563eb;
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
            color: #93c5fd;
        }

        .col-name {
            font-weight: 600;
            color: #ffffff;
        }

        .badge-curso {
            background: rgba(99, 102, 241, 0.15);
            border: 1px solid rgba(99, 102, 241, 0.3);
            color: #c7d2fe;
            padding: 0.3rem 0.65rem;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }

        .badge-paralelo {
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            margin-left: 0.35rem;
            font-weight: 700;
        }

        .badge-paralelo-rojo {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.35);
            color: #f87171;
        }

        .badge-paralelo-azul {
            background: rgba(59, 130, 246, 0.15);
            border: 1px solid rgba(59, 130, 246, 0.35);
            color: #60a5fa;
        }

        .badge-paralelo-blanco {
            background: rgba(243, 244, 246, 0.15);
            border: 1px solid rgba(243, 244, 246, 0.35);
            color: #f3f4f6;
        }

        .badge-hermano {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            background: rgba(99, 102, 241, 0.18);
            border: 1px solid rgba(99, 102, 241, 0.35);
            color: #c7d2fe;
            padding: 0.15rem 0.45rem;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: 700;
            margin-top: 0.3rem;
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

        .status-activa {
            background: rgba(16, 185, 129, 0.12);
            color: #34d399;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .status-trasladada {
            background: rgba(245, 158, 11, 0.12);
            color: #fbbf24;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }

        .status-retirada {
            background: rgba(239, 68, 68, 0.12);
            color: #f87171;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .tipo-ingreso {
            font-size: 0.75rem;
            color: #9ca3af;
            font-family: 'JetBrains Mono', monospace;
            margin-top: 0.2rem;
        }

        /* Actions */
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
        }

        .btn-action.ver {
            background: rgba(99, 102, 241, 0.12);
            border-color: rgba(99, 102, 241, 0.3);
            color: #a5b4fc;
        }
        .btn-action.ver:hover {
            background: #4f46e5;
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
            <div class="icon-badge">🎓</div>
            <div>
                <h1>Gestión de Estudiantes y Matrículas</h1>
                <p>Colegio Pedagógico &bull; Registro Escolar y Cursos Gestión 2026</p>
            </div>
        </div>

        <div class="header-actions">
            <a href="../index.php" class="btn btn-secondary">
                <span>&larr; Menú Principal</span>
            </a>
            <a href="crear.php" class="btn btn-primary">
                <span>+ Matricular Estudiante</span>
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

    <!-- KPIs de Estudiantes -->
    <div class="metrics-grid">
        <div class="metric-card">
            <div>
                <div class="metric-label">Total Alumnos</div>
                <div class="metric-value"><?= $totalEstudiantes ?></div>
            </div>
            <div class="metric-badge" style="background: rgba(59, 130, 246, 0.15); color: #60a5fa;">🎒</div>
        </div>

        <div class="metric-card">
            <div>
                <div class="metric-label">Nivel Primaria</div>
                <div class="metric-value" style="color: #38bdf8;"><?= $totalPrimaria ?></div>
            </div>
            <div class="metric-badge" style="background: rgba(56, 189, 248, 0.15); color: #38bdf8;">📘</div>
        </div>

        <div class="metric-card">
            <div>
                <div class="metric-label">Nivel Secundaria</div>
                <div class="metric-value" style="color: #a78bfa;"><?= $totalSecundaria ?></div>
            </div>
            <div class="metric-badge" style="background: rgba(167, 139, 250, 0.15); color: #a78bfa;">🔬</div>
        </div>

        <div class="metric-card">
            <div>
                <div class="metric-label">Nuevos Ingresos</div>
                <div class="metric-value" style="color: #34d399;"><?= $totalNuevos ?></div>
            </div>
            <div class="metric-badge" style="background: rgba(16, 185, 129, 0.15); color: #34d399;">🌟</div>
        </div>
    </div>

    <!-- Filtros de Búsqueda Avanzados -->
    <div class="filter-card">
        <div class="filter-title">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
            </svg>
            <span>Filtros Académicos y de Búsqueda</span>
        </div>

        <form method="GET" action="index.php" class="filters-form">
            <!-- 1. Texto libre -->
            <div>
                <input type="text" name="buscar" value="<?= htmlspecialchars($buscar) ?>" class="filter-input" placeholder="Buscar por alumno, CI/RUDE, código o apoderado...">
            </div>

            <!-- 2. Nivel -->
            <div>
                <select name="nivel" class="filter-input">
                    <option value="">-- Nivel --</option>
                    <?php foreach ($niveles as $nv): ?>
                        <option value="<?= $nv['nombre'] ?>" <?= $filtroNivel === $nv['nombre'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($nv['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- 3. Grado -->
            <div>
                <select name="grado" class="filter-input">
                    <option value="">-- Grado / Curso --</option>
                    <?php foreach ($grados as $gr): ?>
                        <option value="<?= $gr['id_grado'] ?>" <?= $filtroGrado == $gr['id_grado'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($gr['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- 4. Paralelo -->
            <div>
                <select name="paralelo" class="filter-input">
                    <option value="">-- Paralelo --</option>
                    <?php foreach ($paralelos as $pa): ?>
                        <option value="<?= $pa['nombre'] ?>" <?= $filtroParalelo === $pa['nombre'] ? 'selected' : '' ?>>
                            Paralelo <?= htmlspecialchars($pa['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- 5. Estado Matrícula -->
            <div>
                <select name="estado" class="filter-input">
                    <option value="">-- Estado Matrícula --</option>
                    <option value="ACTIVA" <?= $filtroEstado === 'ACTIVA' ? 'selected' : '' ?>>ACTIVA</option>
                    <option value="TRASLADADA" <?= $filtroEstado === 'TRASLADADA' ? 'selected' : '' ?>>TRASLADADA</option>
                    <option value="RETIRADA" <?= $filtroEstado === 'RETIRADA' ? 'selected' : '' ?>>RETIRADA</option>
                </select>
            </div>

            <!-- Botones -->
            <div class="filter-btn-group">
                <button type="submit" class="btn-filter">Filtrar</button>
                <?php if ($buscar || $filtroNivel || $filtroGrado || $filtroParalelo || $filtroEstado): ?>
                    <a href="index.php" class="btn-reset" title="Limpiar filtros">&times;</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Table Card -->
    <div class="table-card">
        <?php if (!empty($estudiantes)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Código RUDE</th>
                        <th>Estudiante (Identidad)</th>
                        <th>Curso Asignado</th>
                        <th>Tutor / Apoderado</th>
                        <th>Ingreso / Matrícula</th>
                        <th>Estado</th>
                        <th style="text-align: right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($estudiantes as $est): ?>
                        <tr>
                            <td class="col-code">
                                <?= htmlspecialchars($est['codigo_estudiante']) ?>
                            </td>

                            <td>
                                <div class="col-name">
                                    <?= htmlspecialchars(trim($est['nombres'] . ' ' . $est['apellidos'])) ?>
                                </div>
                                <div style="font-size: 0.75rem; color: var(--text-muted); font-family: 'JetBrains Mono', monospace;">
                                    CI: <?= htmlspecialchars($est['documento_identidad'] ?? 'S/D') ?> &bull; Tel: <?= htmlspecialchars($est['telefono'] ?? '-') ?>
                                </div>
                            </td>

                            <td>
                                <?php if (!empty($est['grado'])): 
                                    $par = $est['paralelo'] ?? '';
                                    $parClass = 'badge-paralelo-azul';
                                    if ($par === 'Rojo') $parClass = 'badge-paralelo-rojo';
                                    elseif ($par === 'Blanco') $parClass = 'badge-paralelo-blanco';
                                ?>
                                    <span class="badge-curso">
                                        <?= htmlspecialchars($est['grado']) ?>
                                    </span>
                                    <span class="badge-paralelo <?= $parClass ?>">
                                        <?= htmlspecialchars($est['paralelo']) ?>
                                    </span>
                                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.2rem;">
                                        Nivel: <?= htmlspecialchars($est['nivel']) ?>
                                    </div>
                                <?php else: ?>
                                    <span style="color: var(--text-muted); font-style: italic;">Sin curso asignado</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php if (!empty($est['tutor_nombre'])): ?>
                                    <div style="font-weight: 600; color: #f3f4f6;">
                                        <?= htmlspecialchars($est['tutor_nombre']) ?>
                                    </div>
                                    <div style="font-size: 0.75rem; color: #a5b4fc;">
                                        <?= htmlspecialchars($est['tutor_parentesco']) ?> &bull; 📞 <?= htmlspecialchars($est['tutor_telefono']) ?>
                                    </div>
                                    <?php if (!empty($est['total_hermanos']) && (int)$est['total_hermanos'] > 1): ?>
                                        <div class="badge-hermano">
                                            👨‍👩‍👧‍👦 Hermano/a en colegio
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span style="color: var(--text-muted); font-style: italic;">No registrado</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <div style="font-family: 'JetBrains Mono', monospace; font-size: 0.85rem;">
                                    <?= htmlspecialchars($est['fecha_matricula'] ?? $est['fecha_ingreso']) ?>
                                </div>
                                <div class="tipo-ingreso">
                                    <?= htmlspecialchars($est['tipo_ingreso'] ?? 'REGULAR') ?>
                                </div>
                            </td>

                            <td>
                                <?php
                                    $estMat = strtoupper($est['estado_matricula'] ?? 'ACTIVA');
                                    $clase = 'status-retirada';
                                    if ($estMat === 'ACTIVA') $clase = 'status-activa';
                                    elseif ($estMat === 'TRASLADADA') $clase = 'status-trasladada';
                                ?>
                                <span class="status-badge <?= $clase ?>">
                                    <?= htmlspecialchars($estMat) ?>
                                </span>
                            </td>

                            <td>
                                <div class="actions-cell">
                                    <a class="btn-action ver" href="ver.php?id=<?= $est['id_persona'] ?>" title="Ver Ficha Escolar">
                                        Ver
                                    </a>

                                    <a class="btn-action editar" href="editar.php?id=<?= $est['id_persona'] ?>" title="Editar">
                                        Editar
                                    </a>

                                    <a class="btn-action eliminar" 
                                       href="eliminar.php?id=<?= $est['id_persona'] ?>" 
                                       onclick="return confirm('¿Confirma que desea eliminar el registro del estudiante <?= htmlspecialchars(addslashes($est['nombres'] . ' ' . $est['apellidos'])) ?>?');" 
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
                <span>🎒</span>
                <h3>No se encontraron estudiantes</h3>
                <p>No hay alumnos que coincidan con los criterios de búsqueda.</p>
                <a href="index.php" class="btn btn-secondary" style="margin-top: 1rem;">Limpiar Filtros</a>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
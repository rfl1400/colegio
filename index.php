<?php
/**
 * Portal Principal - Sistema de Gestión Escolar
 * Colegio Pedagógico
 */

$conexionOk = false;
$totalEstudiantes = 0;
$totalDocentes = 0;
$totalAdministrativos = 0;

try {
    require_once __DIR__ . '/conexion.php';
    if (isset($conexion) && $conexion instanceof PDO) {
        $conexionOk = true;

        // Intentar obtener conteos básicos si las tablas existen
        try {
            $totalEstudiantes = (int)$conexion->query("SELECT COUNT(*) FROM estudiantes")->fetchColumn();
        } catch (Exception $e) {}

        try {
            $totalDocentes = (int)$conexion->query("SELECT COUNT(*) FROM docentes")->fetchColumn();
        } catch (Exception $e) {}

        try {
            $totalAdministrativos = (int)$conexion->query("SELECT COUNT(*) FROM administrativos")->fetchColumn();
        } catch (Exception $e) {}
    }
} catch (Exception $e) {
    $conexionOk = false;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Gestión Escolar | Colegio Pedagógico</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-body: #0b0f19;
            --card-bg: rgba(22, 29, 47, 0.7);
            --card-border: rgba(255, 255, 255, 0.08);
            --text-main: #f3f4f6;
            --text-muted: #9ca3af;
            --primary: #6366f1;
            --primary-light: #818cf8;
            --emerald: #10b981;
            --rose: #ef4444;
            --amber: #f59e0b;
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
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            top: -20%;
            left: 20%;
            width: 700px;
            height: 700px;
            background: radial-gradient(circle, rgba(99, 102, 241, 0.12) 0%, rgba(0,0,0,0) 70%);
            z-index: 0;
            pointer-events: none;
        }

        .container {
            max-width: 1100px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        /* Topbar */
        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1.5rem;
            margin-bottom: 3rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--card-border);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .brand-logo {
            width: 52px;
            height: 52px;
            background: linear-gradient(135deg, #4f46e5, #06b6d4);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            box-shadow: 0 10px 25px rgba(79, 70, 229, 0.35);
        }

        .brand-text h1 {
            font-size: 1.5rem;
            font-weight: 800;
            letter-spacing: -0.02em;
        }

        .brand-text p {
            font-size: 0.875rem;
            color: var(--text-muted);
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            padding: 0.5rem 1rem;
            border-radius: 9999px;
            font-size: 0.85rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .status-pill.online {
            background: rgba(16, 185, 129, 0.12);
            color: #34d399;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .status-pill.offline {
            background: rgba(239, 68, 68, 0.12);
            color: #f87171;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .status-pill:hover {
            transform: translateY(-2px);
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }

        .status-pill.online .status-dot {
            background: var(--emerald);
            box-shadow: 0 0 10px var(--emerald);
        }

        .status-pill.offline .status-dot {
            background: var(--rose);
            box-shadow: 0 0 10px var(--rose);
        }

        /* Hero */
        .hero {
            margin-bottom: 2.5rem;
        }

        .hero h2 {
            font-size: 2rem;
            font-weight: 800;
            letter-spacing: -0.03em;
            margin-bottom: 0.5rem;
        }

        .hero p {
            color: var(--text-muted);
            font-size: 1.05rem;
            max-width: 600px;
        }

        /* Modules Grid */
        .modules-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2.5rem;
        }

        .module-card {
            background: var(--card-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            padding: 1.75rem;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .module-card:hover {
            border-color: rgba(99, 102, 241, 0.4);
            transform: translateY(-4px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.35);
        }

        .module-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.25rem;
        }

        .module-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .icon-estudiantes { background: rgba(59, 130, 246, 0.15); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.25); }
        .icon-docentes { background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.25); }
        .icon-admin { background: rgba(245, 158, 11, 0.15); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.25); }

        .module-title h3 {
            font-size: 1.2rem;
            font-weight: 700;
            color: #ffffff;
        }

        .module-title span {
            font-size: 0.8rem;
            color: var(--text-muted);
            font-family: 'JetBrains Mono', monospace;
        }

        .module-body p {
            font-size: 0.9rem;
            color: var(--text-muted);
            line-height: 1.5;
            margin-bottom: 1.5rem;
        }

        .module-links {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.65rem;
        }

        .module-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.65rem 0.85rem;
            border-radius: 10px;
            font-size: 0.85rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s ease;
            text-align: center;
        }

        .module-link.primary {
            background: rgba(99, 102, 241, 0.15);
            border: 1px solid rgba(99, 102, 241, 0.3);
            color: #c7d2fe;
        }

        .module-link.primary:hover {
            background: var(--primary);
            color: #ffffff;
        }

        .module-link.secondary {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--card-border);
            color: #9ca3af;
        }

        .module-link.secondary:hover {
            background: rgba(255, 255, 255, 0.08);
            color: #ffffff;
        }

        /* Tools bar */
        .tools-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            padding: 1.5rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1.25rem;
        }

        .tools-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .tools-info h4 {
            font-size: 1rem;
            font-weight: 700;
        }

        .tools-info p {
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        .tools-buttons {
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
        }

        .btn-test {
            background: linear-gradient(135deg, #4f46e5, #6366f1);
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
        }

        .btn-test:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(79, 70, 229, 0.5);
        }

        .btn-db {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--card-border);
            color: #e5e7eb;
        }

        .btn-db:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #ffffff;
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Topbar -->
    <header class="topbar">
        <div class="brand">
            <div class="brand-logo">🏫</div>
            <div class="brand-text">
                <h1>Colegio Pedagógico</h1>
                <p>Plataforma de Gestión Escolar</p>
            </div>
        </div>

        <a href="probar_conexion.php" class="status-pill <?= $conexionOk ? 'online' : 'offline' ?>">
            <span class="status-dot"></span>
            <span><?= $conexionOk ? 'BD Conectada' : 'Error en Base de Datos' ?></span>
        </a>
    </header>

    <!-- Hero -->
    <div class="hero">
        <h2>Panel de Módulos</h2>
        <p>Selecciona un módulo para gestionar registros académicos y administrativos de la institución.</p>
    </div>

    <!-- Modules -->
    <div class="modules-grid">
        <!-- Estudiantes -->
        <div class="module-card">
            <div>
                <div class="module-header">
                    <div class="module-icon icon-estudiantes">🎓</div>
                    <div class="module-title">
                        <h3>Estudiantes</h3>
                        <span><?= $totalEstudiantes ?> registrados</span>
                    </div>
                </div>
                <div class="module-body">
                    <p>Gestión de alumnos, matrículas, calificaciones, asistencias e información de contactos de tutores.</p>
                </div>
            </div>
            <div class="module-links">
                <a href="estudiantes/index.php" class="module-link primary">Listado</a>
                <a href="estudiantes/crear.php" class="module-link secondary">+ Nuevo</a>
            </div>
        </div>

        <!-- Docentes -->
        <div class="module-card">
            <div>
                <div class="module-header">
                    <div class="module-icon icon-docentes">👨‍🏫</div>
                    <div class="module-title">
                        <h3>Docentes</h3>
                        <span><?= $totalDocentes ?> registrados</span>
                    </div>
                </div>
                <div class="module-body">
                    <p>Administración de la plana docente, especialidades académicas, asignación de materias y credenciales.</p>
                </div>
            </div>
            <div class="module-links">
                <a href="docentes/index.php" class="module-link primary">Listado</a>
                <a href="docentes/crear.php" class="module-link secondary">+ Nuevo</a>
            </div>
        </div>

        <!-- Administrativos -->
        <div class="module-card">
            <div>
                <div class="module-header">
                    <div class="module-icon icon-admin">💼</div>
                    <div class="module-title">
                        <h3>Personal Administrativo</h3>
                        <span><?= $totalAdministrativos ?> registrados</span>
                    </div>
                </div>
                <div class="module-body">
                    <p>Gestión del equipo administrativo institucional, cargos, horarios y roles del sistema.</p>
                </div>
            </div>
            <div class="module-links">
                <a href="administrativos/index.php" class="module-link primary">Listado</a>
                <a href="administrativos/crear.php" class="module-link secondary">+ Nuevo</a>
            </div>
        </div>
    </div>

    <!-- System Diagnostics & Quick Tools -->
    <div class="tools-card">
        <div class="tools-info">
            <span style="font-size: 1.5rem;">⚙️</span>
            <div>
                <h4>Diagnóstico y Base de Datos</h4>
                <p>Verifica el estado del motor MySQL, las 27 tablas y la configuración de PDO.</p>
            </div>
        </div>
        <div class="tools-buttons">
            <a href="probar_conexion.php" class="btn btn-test">
                <span>🔍 Diagnosticar Conexión</span>
            </a>
            <a href="http://localhost/phpmyadmin" target="_blank" class="btn btn-db">
                <span>phpMyAdmin</span>
            </a>
        </div>
    </div>
</div>

</body>
</html>

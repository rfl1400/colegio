<?php
require_once '../conexion.php';

// Generar sugerencia de siguiente código administrativo (ej. ADM-2026-008)
$siguienteCodigo = 'ADM-' . date('Y') . '-001';
try {
    $ultimo = $conexion->query("SELECT codigo_administrativo FROM administrativos ORDER BY id_persona DESC LIMIT 1")->fetchColumn();
    if ($ultimo && preg_match('/(\d+)$/', $ultimo, $coincidencias)) {
        $num = intval($coincidencias[1]) + 1;
        $siguienteCodigo = 'ADM-' . date('Y') . '-' . str_pad($num, 3, '0', STR_PAD_LEFT);
    }
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Administrativo | Colegio Pedagógico</title>
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
            --primary: #f59e0b;
            --primary-hover: #d97706;
            --field-bg: rgba(15, 23, 42, 0.6);
            --field-border: rgba(255, 255, 255, 0.1);
            --field-focus: #fbbf24;
            --section-color: #fbbf24;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-body);
            color: var(--text-main);
            min-height: 100vh;
            padding: 2.5rem 1.5rem;
        }

        .form-container {
            width: 100%;
            max-width: 920px;
            margin: 0 auto;
        }

        .card {
            background: var(--card-bg);
            backdrop-filter: blur(16px);
            border: 1px solid var(--card-border);
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            padding: 2.5rem;
        }

        .header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--card-border);
        }

        .icon-badge {
            width: 52px; height: 52px;
            background: rgba(245, 158, 11, 0.15);
            border: 1px solid rgba(245, 158, 11, 0.35);
            color: #fbbf24;
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.6rem;
            flex-shrink: 0;
        }

        .header h1 { font-size: 1.5rem; font-weight: 800; }
        .header p { font-size: 0.85rem; color: var(--text-muted); margin-top: 0.2rem; }

        .section-subtitle {
            font-size: 0.82rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--section-color);
            margin: 2rem 0 1.1rem 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid rgba(245,158,11,0.15);
        }

        .section-box {
            background: rgba(15, 23, 42, 0.35);
            border: 1px solid rgba(255,255,255,0.05);
            border-radius: 16px;
            padding: 1.25rem 1.5rem;
            margin-bottom: 0.5rem;
        }

        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1.1rem; }
        .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.1rem; }
        .grid-4 { display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 1.1rem; }

        @media (max-width: 720px) {
            .grid-2, .grid-3, .grid-4 { grid-template-columns: 1fr; }
        }
        @media (max-width: 900px) {
            .grid-4 { grid-template-columns: 1fr 1fr; }
            .grid-3 { grid-template-columns: 1fr 1fr; }
        }

        .form-group { margin-bottom: 1rem; }

        label {
            display: block;
            font-size: 0.78rem;
            font-weight: 600;
            color: #d1d5db;
            margin-bottom: 0.4rem;
        }
        label span.req { color: #ef4444; }

        input[type="text"],
        input[type="date"],
        input[type="email"],
        input[type="number"],
        input[type="tel"],
        select,
        textarea {
            width: 100%;
            background: var(--field-bg);
            border: 1px solid var(--field-border);
            border-radius: 10px;
            padding: 0.65rem 0.9rem;
            font-size: 0.875rem;
            color: #ffffff;
            font-family: inherit;
            outline: none;
            transition: all 0.2s ease;
        }

        input:focus, select:focus, textarea:focus {
            border-color: var(--field-focus);
            box-shadow: 0 0 0 3px rgba(251, 191, 36, 0.2);
        }

        select option { background: #111827; color: #ffffff; }
        textarea { resize: vertical; min-height: 70px; }

        /* Contact blocks */
        .contact-block {
            background: rgba(245, 158, 11, 0.04);
            border: 1px solid rgba(245, 158, 11, 0.15);
            border-radius: 14px;
            padding: 1.15rem 1.25rem;
            margin-bottom: 0.85rem;
            position: relative;
        }

        .contact-block-title {
            font-size: 0.8rem;
            font-weight: 700;
            color: #fbbf24;
            margin-bottom: 0.85rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .btn-add-contact {
            background: rgba(245, 158, 11, 0.08);
            border: 1px dashed rgba(245, 158, 11, 0.4);
            color: #fbbf24;
            padding: 0.6rem 1.2rem;
            border-radius: 10px;
            font-size: 0.82rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            margin-top: 0.5rem;
        }

        .btn-add-contact:hover {
            background: rgba(245, 158, 11, 0.15);
        }

        .btn-remove {
            position: absolute;
            top: 0.75rem;
            right: 0.75rem;
            background: rgba(239, 68, 68, 0.12);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #f87171;
            border-radius: 8px;
            padding: 0.25rem 0.6rem;
            font-size: 0.75rem;
            cursor: pointer;
            transition: all 0.15s;
        }

        .btn-remove:hover { background: #ef4444; color: #fff; }

        /* Form actions */
        .form-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--card-border);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.4rem;
            border-radius: 12px;
            font-size: 0.875rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s ease;
            cursor: pointer;
            border: none;
            font-family: inherit;
        }

        .btn-primary {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: #000;
            box-shadow: 0 4px 14px rgba(245, 158, 11, 0.4);
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(245, 158, 11, 0.55); }

        .btn-cancel {
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--card-border);
            color: var(--text-muted);
        }
        .btn-cancel:hover { background: rgba(255,255,255,0.1); color: #fff; }

        .hint {
            font-size: 0.73rem;
            color: var(--text-muted);
            margin-top: 0.3rem;
        }
    </style>
</head>
<body>

<div class="form-container">
    <div class="card">
        <header class="header">
            <div class="icon-badge">💼</div>
            <div>
                <h1>Registrar Nuevo Administrativo</h1>
                <p>Datos personales completos • Contactos • Datos laborales e institucionales</p>
            </div>
        </header>

        <form action="procesar.php" method="POST" id="formAdm">

            <!-- =============================================
                 SECCIÓN 1: IDENTIDAD PERSONAL
            ============================================== -->
            <div class="section-subtitle">👤 1. Datos de Identidad Personal</div>
            <div class="section-box">
                <div class="grid-2">
                    <div class="form-group">
                        <label>Nombres <span class="req">*</span></label>
                        <input type="text" name="nombres" placeholder="Ej. Roberto Carlos" required>
                    </div>
                    <div class="form-group">
                        <label>Apellidos <span class="req">*</span></label>
                        <input type="text" name="apellidos" placeholder="Ej. Mendoza Quisbert" required>
                    </div>
                </div>

                <div class="grid-3">
                    <div class="form-group">
                        <label>Documento de Identidad (CI) <span class="req">*</span></label>
                        <input type="text" name="documento_identidad" placeholder="Ej. 4892019-LP" required>
                    </div>
                    <div class="form-group">
                        <label>Fecha de Nacimiento</label>
                        <input type="date" name="fecha_nacimiento">
                    </div>
                    <div class="form-group">
                        <label>Sexo</label>
                        <select name="sexo">
                            <option value="NO_ESPECIFICA">No especifica</option>
                            <option value="F">Femenino</option>
                            <option value="M">Masculino</option>
                            <option value="OTRO">Otro</option>
                        </select>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label>Estado Civil</label>
                        <select name="estado_civil">
                            <option value="">-- No especificado --</option>
                            <option value="SOLTERO">Soltero/a</option>
                            <option value="CASADO">Casado/a</option>
                            <option value="DIVORCIADO">Divorciado/a</option>
                            <option value="VIUDO">Viudo/a</option>
                            <option value="UNION_LIBRE">Unión Libre</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Ocupación / Profesión</label>
                        <input type="text" name="ocupacion" placeholder="Ej. Contador Público, Abogado...">
                    </div>
                </div>

                <div class="grid-4">
                    <div class="form-group">
                        <label>Teléfono Principal</label>
                        <input type="tel" name="telefono" placeholder="Ej. 71524310">
                    </div>
                    <div class="form-group">
                        <label>Teléfono Secundario / WhatsApp</label>
                        <input type="tel" name="telefono2" placeholder="Ej. 79123456">
                    </div>
                    <div class="form-group" style="grid-column: span 2;">
                        <label>Correo Electrónico</label>
                        <input type="email" name="correo" placeholder="ejemplo@colegio.edu.bo">
                    </div>
                </div>

                <div class="form-group">
                    <label>Dirección Domiciliaria</label>
                    <input type="text" name="direccion" placeholder="Ej. Zona Miraflores, Av. Las Américas #340, Piso 2">
                </div>
            </div>

            <!-- =============================================
                 SECCIÓN 2: CONTACTOS ADICIONALES
            ============================================== -->
            <div class="section-subtitle">📞 2. Contactos de Referencia o Emergencia</div>
            <div id="contactos-container">
                <div class="contact-block" id="contacto-1">
                    <div class="contact-block-title">📋 Contacto de Emergencia 1</div>
                    <div class="grid-3">
                        <div class="form-group">
                            <label>Nombre Completo</label>
                            <input type="text" name="contacto_nombre[]" placeholder="Ej. María Quisbert">
                        </div>
                        <div class="form-group">
                            <label>Relación / Parentesco</label>
                            <input type="text" name="contacto_relacion[]" list="lista-relaciones" placeholder="Ej. Cónyuge, Hermano/a">
                            <datalist id="lista-relaciones">
                                <option value="Cónyuge"><option value="Hijo/a"><option value="Padre/Madre">
                                <option value="Hermano/a"><option value="Tío/a"><option value="Amigo/a">
                            </datalist>
                        </div>
                        <div class="form-group">
                            <label>Teléfono</label>
                            <input type="tel" name="contacto_telefono[]" placeholder="Ej. 70112233">
                        </div>
                    </div>
                    <div class="grid-2">
                        <div class="form-group" style="margin-bottom:0;">
                            <label>Correo (opcional)</label>
                            <input type="email" name="contacto_correo[]" placeholder="contacto@gmail.com">
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label>Dirección (opcional)</label>
                            <input type="text" name="contacto_direccion[]" placeholder="Dirección del contacto">
                        </div>
                    </div>
                </div>
            </div>

            <button type="button" class="btn-add-contact" onclick="agregarContacto()">
                ＋ Agregar Otro Contacto de Emergencia
            </button>

            <!-- =============================================
                 SECCIÓN 3: DATOS LABORALES / CONTRATACIÓN
            ============================================== -->
            <div class="section-subtitle">📋 3. Cargo y Contratación Institucional</div>
            <div class="section-box">
                <div class="grid-2">
                    <div class="form-group">
                        <label>Código Administrativo <span class="req">*</span></label>
                        <input type="text" name="codigo_administrativo" value="<?= htmlspecialchars($siguienteCodigo) ?>" maxlength="30" required style="font-family: 'JetBrains Mono', monospace; font-weight: 600; color: #fbbf24;">
                    </div>
                    <div class="form-group">
                        <label>Cargo Asignado <span class="req">*</span></label>
                        <input type="text" name="cargo" list="lista-cargos" placeholder="Selecciona o escribe el cargo" maxlength="100" required>
                        <datalist id="lista-cargos">
                            <option value="Director General">
                            <option value="Subdirector Académico">
                            <option value="Secretaria Académica">
                            <option value="Secretaria de Dirección">
                            <option value="Contador General">
                            <option value="Auxiliar Contable">
                            <option value="Regente Escolar">
                            <option value="Encargada de Biblioteca">
                            <option value="Administrador de Sistemas / TIC">
                            <option value="Psicopedagogo/a Institucional">
                            <option value="Jefe de Recursos Humanos">
                            <option value="Auxiliar de Mantenimiento">
                            <option value="Portero / Vigilancia">
                        </datalist>
                    </div>
                </div>

                <div class="grid-3">
                    <div class="form-group">
                        <label>Departamento / Área</label>
                        <input type="text" name="departamento" list="lista-depts" placeholder="Ej. Secretaría, Administración">
                        <datalist id="lista-depts">
                            <option value="Dirección General">
                            <option value="Secretaría Académica">
                            <option value="Administración y Finanzas">
                            <option value="Recursos Humanos">
                            <option value="Biblioteca">
                            <option value="Sistemas TIC">
                            <option value="Orientación Psicopedagógica">
                            <option value="Mantenimiento y Servicios">
                        </datalist>
                    </div>
                    <div class="form-group">
                        <label>Turno de Trabajo</label>
                        <select name="turno">
                            <option value="MANANA">Mañana</option>
                            <option value="TARDE">Tarde</option>
                            <option value="COMPLETO">Completo (Doble)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Salario Mensual (Bs.)</label>
                        <input type="number" name="salario" step="0.01" min="0" placeholder="Ej. 3500.00">
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label>Fecha de Contratación <span class="req">*</span></label>
                        <input type="date" name="fecha_contratacion" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Estado Laboral <span class="req">*</span></label>
                        <select name="estado_laboral" required>
                            <option value="activo" selected>Activo</option>
                            <option value="licencia">Licencia</option>
                            <option value="inactivo">Inactivo / Retirado</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- =============================================
                 SECCIÓN 4: DATOS ACADÉMICOS / INSTITUCIÓN
            ============================================== -->
            <div class="section-subtitle">🎓 4. Formación Académica e Institución de Referencia</div>
            <div class="section-box">
                <div class="grid-2">
                    <div class="form-group">
                        <label>Nivel de Formación</label>
                        <select name="nivel_formacion">
                            <option value="">-- Seleccionar --</option>
                            <option value="Bachiller">Bachiller</option>
                            <option value="Técnico Medio">Técnico Medio</option>
                            <option value="Técnico Superior">Técnico Superior</option>
                            <option value="Licenciatura">Licenciatura</option>
                            <option value="Maestría">Maestría</option>
                            <option value="Doctorado">Doctorado</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Título / Carrera Profesional</label>
                        <input type="text" name="titulo_formacion" placeholder="Ej. Licenciatura en Administración de Empresas">
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label>Institución Educativa (donde estudió)</label>
                        <input type="text" name="institucion_formacion" placeholder="Ej. UMSA, UCB, UPEA, UPDS...">
                        <p class="hint">Universidad, instituto o normal de procedencia</p>
                    </div>
                    <div class="form-group">
                        <label>Institución Anterior (donde trabajó antes)</label>
                        <input type="text" name="institucion_anterior" placeholder="Ej. Colegio San Agustín, Municipio de La Paz...">
                    </div>
                </div>
            </div>

            <!-- =============================================
                 SECCIÓN 5: OBSERVACIONES
            ============================================== -->
            <div class="section-subtitle">📝 5. Observaciones / Notas Adicionales</div>
            <div class="form-group">
                <textarea name="observaciones" rows="3" placeholder="Información adicional: condiciones especiales, permisos, becas, convenios, historia laboral relevante..."></textarea>
            </div>

            <!-- Botones -->
            <div class="form-actions">
                <a href="index.php" class="btn btn-cancel">← Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <span>💾 Guardar Administrativo</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let contactCount = 1;

function agregarContacto() {
    contactCount++;
    const container = document.getElementById('contactos-container');
    const div = document.createElement('div');
    div.className = 'contact-block';
    div.id = 'contacto-' + contactCount;
    div.style.marginTop = '0.85rem';
    div.innerHTML = `
        <div class="contact-block-title">📋 Contacto de Emergencia ${contactCount}</div>
        <button type="button" class="btn-remove" onclick="this.parentElement.remove()">✕ Quitar</button>
        <div class="grid-3">
            <div class="form-group">
                <label>Nombre Completo</label>
                <input type="text" name="contacto_nombre[]" placeholder="Ej. Ana López">
            </div>
            <div class="form-group">
                <label>Relación / Parentesco</label>
                <input type="text" name="contacto_relacion[]" list="lista-relaciones" placeholder="Ej. Hermano/a">
            </div>
            <div class="form-group">
                <label>Teléfono</label>
                <input type="tel" name="contacto_telefono[]" placeholder="Ej. 70998877">
            </div>
        </div>
        <div class="grid-2">
            <div class="form-group" style="margin-bottom:0;">
                <label>Correo (opcional)</label>
                <input type="email" name="contacto_correo[]" placeholder="correo@gmail.com">
            </div>
            <div class="form-group" style="margin-bottom:0;">
                <label>Dirección (opcional)</label>
                <input type="text" name="contacto_direccion[]" placeholder="Dirección del contacto">
            </div>
        </div>
    `;
    container.appendChild(div);
}
</script>

</body>
</html>
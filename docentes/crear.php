<?php
require_once '../conexion.php';

// Cargar catálogos
$especialidades  = $conexion->query("SELECT id_especialidad, nombre FROM especialidades ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
$tiposCredencial = $conexion->query("SELECT id_tipo_credencial, nombre FROM tipos_credencial ORDER BY id_tipo_credencial ASC")->fetchAll(PDO::FETCH_ASSOC);

// Generar siguiente código docente correlativo
$siguienteCodigo = 'DOC-' . date('Y') . '-001';
try {
    $ultimo = $conexion->query("SELECT codigo_docente FROM docentes ORDER BY id_persona DESC LIMIT 1")->fetchColumn();
    if ($ultimo && preg_match('/(\d+)$/', $ultimo, $coincidencias)) {
        $num = intval($coincidencias[1]) + 1;
        $siguienteCodigo = 'DOC-' . date('Y') . '-' . str_pad($num, 3, '0', STR_PAD_LEFT);
    }
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contratar Nuevo Docente | Colegio Pedagógico</title>
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
            --primary: #10b981;
            --primary-hover: #059669;
            --field-bg: rgba(15, 23, 42, 0.6);
            --field-border: rgba(255, 255, 255, 0.1);
            --field-focus: #34d399;
            --section-color: #34d399;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-body);
            color: var(--text-main);
            min-height: 100vh;
            padding: 2.5rem 1.5rem;
        }

        .form-container { width: 100%; max-width: 920px; margin: 0 auto; }

        .card {
            background: var(--card-bg);
            backdrop-filter: blur(16px);
            border: 1px solid var(--card-border);
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            padding: 2.5rem;
        }

        .header { display: flex; align-items: center; gap: 1rem; margin-bottom: 2rem; padding-bottom: 1.5rem; border-bottom: 1px solid var(--card-border); }

        .icon-badge {
            width: 52px; height: 52px;
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.35);
            color: #34d399;
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.6rem; flex-shrink: 0;
        }

        .header h1 { font-size: 1.5rem; font-weight: 800; }
        .header p { font-size: 0.85rem; color: var(--text-muted); margin-top: 0.2rem; }

        .section-subtitle {
            font-size: 0.82rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em;
            color: var(--section-color); margin: 2rem 0 1.1rem 0;
            display: flex; align-items: center; gap: 0.5rem;
            padding-bottom: 0.5rem; border-bottom: 1px solid rgba(16,185,129,0.15);
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

        @media (max-width: 720px) { .grid-2, .grid-3, .grid-4 { grid-template-columns: 1fr; } }
        @media (max-width: 900px) { .grid-4 { grid-template-columns: 1fr 1fr; } .grid-3 { grid-template-columns: 1fr 1fr; } }

        .form-group { margin-bottom: 1rem; }

        label { display: block; font-size: 0.78rem; font-weight: 600; color: #d1d5db; margin-bottom: 0.4rem; }
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
            box-shadow: 0 0 0 3px rgba(52, 211, 153, 0.2);
        }

        select option { background: #111827; color: #ffffff; }
        textarea { resize: vertical; min-height: 70px; }

        /* Credenciales adicionales */
        .cred-block {
            background: rgba(16, 185, 129, 0.04);
            border: 1px solid rgba(16, 185, 129, 0.15);
            border-radius: 14px;
            padding: 1.15rem 1.25rem;
            margin-bottom: 0.85rem;
            position: relative;
        }

        .cred-block-title {
            font-size: 0.8rem;
            font-weight: 700;
            color: #34d399;
            margin-bottom: 0.85rem;
        }

        .btn-add {
            background: rgba(16, 185, 129, 0.08);
            border: 1px dashed rgba(16, 185, 129, 0.4);
            color: #34d399;
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
            font-family: inherit;
        }

        .btn-add:hover { background: rgba(16, 185, 129, 0.15); }

        .btn-remove {
            position: absolute; top: 0.75rem; right: 0.75rem;
            background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.3);
            color: #f87171; border-radius: 8px; padding: 0.25rem 0.6rem;
            font-size: 0.75rem; cursor: pointer; transition: all 0.15s;
        }
        .btn-remove:hover { background: #ef4444; color: #fff; }

        .form-actions {
            display: flex; align-items: center; justify-content: space-between;
            flex-wrap: wrap; gap: 1rem; margin-top: 2rem;
            padding-top: 1.5rem; border-top: 1px solid var(--card-border);
        }

        .btn {
            display: inline-flex; align-items: center; gap: 0.5rem;
            padding: 0.75rem 1.4rem; border-radius: 12px; font-size: 0.875rem;
            font-weight: 600; text-decoration: none; transition: all 0.2s ease;
            cursor: pointer; border: none; font-family: inherit;
        }

        .btn-primary { background: linear-gradient(135deg, #10b981, #059669); color: #fff; box-shadow: 0 4px 14px rgba(16,185,129,0.4); }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(16,185,129,0.55); }
        .btn-cancel { background: rgba(255,255,255,0.05); border: 1px solid var(--card-border); color: var(--text-muted); }
        .btn-cancel:hover { background: rgba(255,255,255,0.1); color: #fff; }

        .hint { font-size: 0.73rem; color: var(--text-muted); margin-top: 0.3rem; }
    </style>
</head>
<body>

<div class="form-container">
    <div class="card">
        <header class="header">
            <div class="icon-badge">📝</div>
            <div>
                <h1>Contratación de Personal Docente</h1>
                <p>Registro integral: identidad, contrato laboral, credenciales académicas e institución</p>
            </div>
        </header>

        <form action="procesar.php" method="POST" id="formDocente">

            <!-- =============================================
                 SECCIÓN 1: IDENTIDAD PERSONAL
            ============================================== -->
            <div class="section-subtitle">👤 1. Datos Personales del Docente</div>
            <div class="section-box">
                <div class="grid-2">
                    <div class="form-group">
                        <label>Nombres <span class="req">*</span></label>
                        <input type="text" name="nombres" placeholder="Ej. Juan Carlos" required>
                    </div>
                    <div class="form-group">
                        <label>Apellidos <span class="req">*</span></label>
                        <input type="text" name="apellidos" placeholder="Ej. Morales Condori" required>
                    </div>
                </div>

                <div class="grid-3">
                    <div class="form-group">
                        <label>Documento de Identidad (CI) <span class="req">*</span></label>
                        <input type="text" name="documento_identidad" placeholder="Ej. 3321450-LP" required>
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
                        <label>Dirección Domiciliaria</label>
                        <input type="text" name="direccion" placeholder="Ej. Zona Sopocachi, Calle 15 #430">
                    </div>
                </div>

                <div class="grid-4">
                    <div class="form-group">
                        <label>Teléfono / WhatsApp Principal</label>
                        <input type="tel" name="telefono" placeholder="Ej. 71234567">
                    </div>
                    <div class="form-group">
                        <label>Teléfono Secundario</label>
                        <input type="tel" name="telefono2" placeholder="Ej. 79998877">
                    </div>
                    <div class="form-group" style="grid-column: span 2;">
                        <label>Correo Electrónico</label>
                        <input type="email" name="correo" placeholder="docente@colegio.edu.bo">
                    </div>
                </div>
            </div>

            <!-- =============================================
                 SECCIÓN 2: CONTACTO DE EMERGENCIA
            ============================================== -->
            <div class="section-subtitle">📞 2. Contacto de Emergencia Personal</div>
            <div id="contactos-container">
                <div class="cred-block" id="contacto-1">
                    <div class="cred-block-title">📋 Contacto de Emergencia 1</div>
                    <div class="grid-3">
                        <div class="form-group">
                            <label>Nombre Completo</label>
                            <input type="text" name="contacto_nombre[]" placeholder="Ej. María Morales">
                        </div>
                        <div class="form-group">
                            <label>Relación / Parentesco</label>
                            <input type="text" name="contacto_relacion[]" placeholder="Ej. Cónyuge, Hijo/a">
                        </div>
                        <div class="form-group">
                            <label>Teléfono</label>
                            <input type="tel" name="contacto_telefono[]" placeholder="Ej. 70112233">
                        </div>
                    </div>
                </div>
            </div>
            <button type="button" class="btn-add" onclick="agregarContacto()">＋ Agregar Otro Contacto</button>

            <!-- =============================================
                 SECCIÓN 3: TÉRMINOS DEL CONTRATO
            ============================================== -->
            <div class="section-subtitle">📋 3. Términos del Contrato Laboral</div>
            <div class="section-box">
                <div class="grid-3">
                    <div class="form-group">
                        <label>Código Docente <span class="req">*</span></label>
                        <input type="text" name="codigo_docente" value="<?= htmlspecialchars($siguienteCodigo) ?>" maxlength="30" required style="font-family: 'JetBrains Mono', monospace; font-weight: 600; color: #6ee7b7;">
                    </div>
                    <div class="form-group">
                        <label>Fecha de Contratación / Inicio <span class="req">*</span></label>
                        <input type="date" name="fecha_contratacion" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Estado Laboral <span class="req">*</span></label>
                        <select name="estado_laboral" required>
                            <option value="ACTIVO" selected>ACTIVO (Contrato Vigente)</option>
                            <option value="LICENCIA">LICENCIA (Permiso Temporal)</option>
                            <option value="RETIRADO">RETIRADO</option>
                        </select>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label>Turno de Trabajo</label>
                        <select name="turno">
                            <option value="MANANA">Mañana</option>
                            <option value="TARDE">Tarde</option>
                            <option value="AMBOS">Ambos Turnos</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Número de Items / Ítems Docentes</label>
                        <input type="text" name="num_items" placeholder="Ej. Ítem 1, Ítem 2...">
                        <p class="hint">Referencia a ítems del Estado si corresponde</p>
                    </div>
                </div>
            </div>

            <!-- =============================================
                 SECCIÓN 4: FORMACIÓN ACADÉMICA Y CREDENCIALES
            ============================================== -->
            <div class="section-subtitle">🎓 4. Formación Académica y Credenciales</div>

            <div id="credenciales-container">
                <div class="cred-block" id="cred-1">
                    <div class="cred-block-title">📜 Credencial / Título Principal</div>
                    <div class="grid-2">
                        <div class="form-group">
                            <label>Área / Especialidad Principal <span class="req">*</span></label>
                            <select name="id_especialidad" required>
                                <option value="">-- Seleccionar Especialidad --</option>
                                <?php foreach ($especialidades as $esp): ?>
                                    <option value="<?= $esp['id_especialidad'] ?>"><?= htmlspecialchars($esp['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Grado / Tipo de Credencial <span class="req">*</span></label>
                            <select name="id_tipo_credencial" required>
                                <?php foreach ($tiposCredencial as $tc): ?>
                                    <option value="<?= $tc['id_tipo_credencial'] ?>" <?= $tc['nombre'] === 'Licenciatura' ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($tc['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label>Título Profesional Oficial <span class="req">*</span></label>
                            <input type="text" name="titulo" placeholder="Ej. Licenciatura en Educación Matemática" required>
                        </div>
                        <div class="form-group">
                            <label>Institución Emisora / Normal / Universidad <span class="req">*</span></label>
                            <input type="text" name="institucion_emisora" list="lista-instituciones" placeholder="Ej. ESFM Simón Bolívar / UMSA" required>
                            <datalist id="lista-instituciones">
                                <option value="ESFM Simón Bolívar">
                                <option value="ESFM Mariscal Sucre">
                                <option value="ESFM Franz Tamayo">
                                <option value="UMSA - Universidad Mayor de San Andrés">
                                <option value="UMSS - Universidad Mayor de San Simón">
                                <option value="UCB - Universidad Católica Boliviana">
                                <option value="UPEA - Universidad Pública de El Alto">
                                <option value="UNIFRANZ">
                                <option value="NUR - Universidad Privada del Norte">
                                <option value="UNITEPC">
                            </datalist>
                        </div>
                    </div>

                    <div class="grid-3">
                        <div class="form-group">
                            <label>Número de Diploma / Resolución</label>
                            <input type="text" name="numero_diploma[]" placeholder="Ej. 234/2018">
                        </div>
                        <div class="form-group">
                            <label>Fecha de Emisión del Título</label>
                            <input type="date" name="fecha_emision[]">
                        </div>
                        <div class="form-group">
                            <label>Estado de Verificación</label>
                            <select name="estado_verificacion[]">
                                <option value="VERIFICADA" selected>Verificada</option>
                                <option value="PENDIENTE">Pendiente</option>
                                <option value="RECHAZADA">Rechazada</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <button type="button" class="btn-add" onclick="agregarCredencial()">＋ Agregar Otra Credencial / Posgrado</button>

            <!-- =============================================
                 SECCIÓN 5: INSTITUCIONES ADICIONALES
            ============================================== -->
            <div class="section-subtitle">🏛️ 5. Experiencia en Otras Instituciones</div>
            <div class="section-box">
                <div class="grid-2">
                    <div class="form-group">
                        <label>Institución Educativa Anterior</label>
                        <input type="text" name="inst_anterior_1" placeholder="Ej. Colegio San Agustín, Colegio Adventista...">
                    </div>
                    <div class="form-group">
                        <label>Años de Experiencia Total</label>
                        <input type="number" name="anios_experiencia" min="0" max="50" placeholder="Ej. 8">
                    </div>
                </div>
                <div class="form-group">
                    <label>Otras Instituciones / Referencias Laborales</label>
                    <textarea name="otras_instituciones" rows="2" placeholder="Ej. Colegio Nacional XXXXXXX (2015-2019), Ministerio de Educación (2020-2022)..."></textarea>
                </div>
            </div>

            <!-- =============================================
                 SECCIÓN 6: OBSERVACIONES
            ============================================== -->
            <div class="section-subtitle">📝 6. Observaciones / Notas Adicionales</div>
            <div class="form-group">
                <textarea name="observaciones" rows="3" placeholder="Información adicional: logros docentes, especializaciones, reconocimientos, condiciones médicas relevantes..."></textarea>
            </div>

            <!-- Botones -->
            <div class="form-actions">
                <a href="index.php" class="btn btn-cancel">← Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <span>💾 Guardar y Contratar Docente</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let contactCount = 1;
let credCount = 1;

function agregarContacto() {
    contactCount++;
    const container = document.getElementById('contactos-container');
    const div = document.createElement('div');
    div.className = 'cred-block';
    div.style.marginTop = '0.85rem';
    div.innerHTML = `
        <div class="cred-block-title">📋 Contacto de Emergencia ${contactCount}</div>
        <button type="button" class="btn-remove" onclick="this.parentElement.remove()">✕ Quitar</button>
        <div class="grid-3">
            <div class="form-group">
                <label>Nombre Completo</label>
                <input type="text" name="contacto_nombre[]" placeholder="Ej. Ana López">
            </div>
            <div class="form-group">
                <label>Relación / Parentesco</label>
                <input type="text" name="contacto_relacion[]" placeholder="Ej. Cónyuge, Hermano/a">
            </div>
            <div class="form-group">
                <label>Teléfono</label>
                <input type="tel" name="contacto_telefono[]" placeholder="Ej. 70998877">
            </div>
        </div>
    `;
    container.appendChild(div);
}

function agregarCredencial() {
    credCount++;
    const container = document.getElementById('credenciales-container');
    const div = document.createElement('div');
    div.className = 'cred-block';
    div.style.marginTop = '0.85rem';
    div.innerHTML = `
        <div class="cred-block-title">📜 Credencial Adicional / Posgrado ${credCount}</div>
        <button type="button" class="btn-remove" onclick="this.parentElement.remove()">✕ Quitar</button>
        <div class="grid-2">
            <div class="form-group">
                <label>Título / Posgrado Adicional</label>
                <input type="text" name="titulo_adicional[]" placeholder="Ej. Maestría en Evaluación Educativa">
            </div>
            <div class="form-group">
                <label>Institución Emisora</label>
                <input type="text" name="inst_adicional[]" list="lista-instituciones" placeholder="Ej. UMSA, FLACSO...">
            </div>
        </div>
        <div class="grid-3">
            <div class="form-group">
                <label>Número de Diploma / Resolución</label>
                <input type="text" name="numero_diploma[]" placeholder="Ej. 234/2018">
            </div>
            <div class="form-group">
                <label>Fecha de Emisión</label>
                <input type="date" name="fecha_emision[]">
            </div>
            <div class="form-group">
                <label>Estado de Verificación</label>
                <select name="estado_verificacion[]">
                    <option value="VERIFICADA">Verificada</option>
                    <option value="PENDIENTE" selected>Pendiente</option>
                </select>
            </div>
        </div>
    `;
    container.appendChild(div);
}
</script>

</body>
</html>
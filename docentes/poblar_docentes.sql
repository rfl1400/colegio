-- ============================================================
-- SCRIPT DE POBLADO ("POBLIS"): DATOS PARA DOCENTES
-- Especialidades, Credenciales y Contrataciones
-- Base de Datos: colegio_pedagogico
-- ============================================================

USE colegio_pedagogico;

-- 1. Catálogo de Especialidades Docentes
INSERT INTO especialidades (id_especialidad, nombre, descripcion)
VALUES
(1, 'Matemáticas y Cálculo', 'Enseñanza de aritmética, álgebra, geometría, trigonometría y cálculo superior.'),
(2, 'Lenguaje y Literatura', 'Gramática, redacción, comprensión lectora y literatura universal y nacional.'),
(3, 'Física y Química', 'Ciencias exactas experimentales, laboratorio y física clásica.'),
(4, 'Biología y Geografía', 'Ciencias de la vida, anatomía, botánica y geografía física y humana.'),
(5, 'Ciencias Sociales e Historia', 'Historia universal, historia de Bolivia, cívica y sociología.'),
(6, 'Lengua Extranjera (Inglés)', 'Enseñanza del idioma inglés desde nivel básico hasta intermedio-avanzado.'),
(7, 'Educación Física y Deportes', 'Acondicionamiento físico, atletismo, fútbol, baloncesto y voleibol.'),
(8, 'Educación Musical', 'Teoría musical, solfeo, coro e instrumentos tradicionales.'),
(9, 'Filosofía y Psicología', 'Pensamiento crítico, lógica, ética y desarrollo psicológico escolar.'),
(10, 'Computación y Robótica', 'Alfabetización digital, ofimática y principios de programación y robótica educativa.')
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), descripcion = VALUES(descripcion);

-- 2. Insertar Personas (Docentes)
INSERT INTO personas (id_persona, documento_identidad, nombres, apellidos, fecha_nacimiento, sexo, telefono, correo, direccion, estado)
VALUES
(11, '3321450-LP', 'Juan Carlos', 'Morales Condori', '1982-04-12', 'M', '71234567', 'jcmorales@colegiopedagogico.edu.bo', 'Av. Buenos Aires #890', 'ACTIVO'),
(12, '4567890-CB', 'María Elena', 'Gutiérrez Benítez', '1985-09-23', 'F', '72345678', 'mgutierrez@colegiopedagogico.edu.bo', 'Calle Jordán #234', 'ACTIVO'),
(13, '5678901-SC', 'David Rodrigo', 'Peñaranda Soliz', '1980-11-05', 'M', '73456789', 'dpenaranda@colegiopedagogico.edu.bo', 'Av. Cristóbal de Mendoza #112', 'ACTIVO'),
(14, '6789012-OR', 'Patricia Andrea', 'Siles Fernández', '1988-06-18', 'F', '74567890', 'psiles@colegiopedagogico.edu.bo', 'Calle Bolívar #560', 'ACTIVO'),
(15, '7890123-PT', 'Nelson Gonzalo', 'Romero Tórrez', '1979-02-28', 'M', '75678901', 'nromero@colegiopedagogico.edu.bo', 'Av. Universitaria #430', 'ACTIVO'),
(16, '8901234-TJ', 'Wendy Stephanie', 'Cooper Mercado', '1992-08-14', 'F', '76789012', 'wcooper@colegiopedagogico.edu.bo', 'Calle Madrid #125', 'ACTIVO'),
(17, '9012345-CH', 'Jorge Luis', 'Mamani Choque', '1986-12-30', 'M', '77890123', 'jmamani@colegiopedagogico.edu.bo', 'Zona San Antonio #78', 'ACTIVO'),
(18, '1023456-BN', 'Andrea Pamela', 'Miranda Salinas', '1991-03-22', 'F', '78901234', 'amiranda@colegiopedagogico.edu.bo', 'Av. 6 de Agosto #45', 'ACTIVO'),
(19, '2134567-PD', 'Carlos Eduardo', 'Pinto Vaca', '1976-10-10', 'M', '79012345', 'cpinto@colegiopedagogico.edu.bo', 'Calle Comercio #90', 'INACTIVO')
ON DUPLICATE KEY UPDATE 
    nombres = VALUES(nombres),
    apellidos = VALUES(apellidos),
    documento_identidad = VALUES(documento_identidad),
    telefono = VALUES(telefono),
    correo = VALUES(correo);

-- 3. Insertar Docentes (Contratos y Estados Laborales)
INSERT INTO docentes (id_persona, codigo_docente, fecha_contratacion, estado_laboral)
VALUES
(11, 'DOC-2023-010', '2023-02-01', 'ACTIVO'),
(12, 'DOC-2023-011', '2023-02-01', 'ACTIVO'),
(13, 'DOC-2024-012', '2024-02-01', 'ACTIVO'),
(14, 'DOC-2024-013', '2024-02-15', 'ACTIVO'),
(15, 'DOC-2025-014', '2025-02-01', 'ACTIVO'),
(16, 'DOC-2025-015', '2025-02-01', 'ACTIVO'),
(17, 'DOC-2025-016', '2025-08-01', 'LICENCIA'),
(18, 'DOC-2026-017', '2026-01-15', 'ACTIVO'),
(19, 'DOC-2022-005', '2022-02-01', 'RETIRADO')
ON DUPLICATE KEY UPDATE 
    codigo_docente = VALUES(codigo_docente),
    fecha_contratacion = VALUES(fecha_contratacion),
    estado_laboral = VALUES(estado_laboral);

-- 4. Asociar Especialidades a los Docentes
INSERT INTO docente_especialidad (id_docente, id_especialidad)
VALUES
(11, 1), -- Juan Carlos -> Matemáticas
(12, 2), -- María Elena -> Lenguaje
(13, 3), -- David Rodrigo -> Física y Química
(14, 4), -- Patricia Andrea -> Biología
(15, 5), -- Nelson Gonzalo -> Ciencias Sociales
(16, 6), -- Wendy Stephanie -> Inglés
(17, 7), -- Jorge Luis -> Educación Física
(18, 9), -- Andrea Pamela -> Filosofía y Psicología
(19, 8)  -- Carlos Eduardo -> Educación Musical
ON DUPLICATE KEY UPDATE id_especialidad = VALUES(id_especialidad);

-- 5. Insertar Credenciales y Títulos Profesionales
INSERT INTO credenciales_docentes (id_docente, id_tipo_credencial, titulo, institucion_emisora, especialidad, fecha_emision, numero_documento, estado_verificacion)
VALUES
(11, 2, 'Licenciatura en Educación Matemática', 'Escuela Superior de Formación de Maestros Simón Bolívar', 'Matemáticas', '2010-12-15', 'TIT-2010-984', 'VERIFICADA'),
(12, 5, 'Maestría en Didáctica de la Lengua y Literatura', 'Universidad Mayor de San Simón', 'Lenguaje y Literatura', '2018-07-20', 'MAE-2018-112', 'VERIFICADA'),
(13, 2, 'Licenciatura en Ciencias Físicas', 'Universidad Mayor de San Andrés', 'Física Pura y Aplicada', '2012-11-10', 'TIT-2012-654', 'VERIFICADA'),
(14, 2, 'Licenciatura en Biología', 'Universidad Autónoma Gabriel René Moreno', 'Ciencias Naturales', '2015-06-18', 'TIT-2015-321', 'VERIFICADA'),
(15, 1, 'Profesor de Estado en Ciencias Sociales', 'Escuela Superior de Maestros Mariscal Sucre', 'Historia y Geografía', '2008-12-05', 'TIT-2008-876', 'VERIFICADA'),
(16, 2, 'Licenciatura en Lingüística e Idiomas (Inglés)', 'Universidad Católica Boliviana', 'Enseñanza del Inglés (TEFL)', '2019-10-30', 'TIT-2019-450', 'VERIFICADA'),
(17, 1, 'Profesor de Educación Física y Deportes', 'Instituto Superior de Educación Física (INSEF)', 'Deportes Colectivos', '2014-11-25', 'TIT-2014-990', 'VERIFICADA'),
(18, 4, 'Especialidad en Orientación Psicopedagógica', 'Universidad Pedagógica Nacional', 'Psicología Educativa', '2023-08-15', 'ESP-2023-771', 'VERIFICADA'),
(19, 2, 'Licenciatura en Artes Musicales', 'Conservatorio Plurinacional de Música', 'Educación Musical', '2005-12-20', 'TIT-2005-102', 'VERIFICADA')
ON DUPLICATE KEY UPDATE titulo = VALUES(titulo), estado_verificacion = VALUES(estado_verificacion);

<?php
session_start();

// ✅ Verificar con el nombre correcto
if (!isset($_SESSION['user_id'])) {
    error_log("❌ Acceso denegado a miperfil.php - No hay user_id en sesión");
    error_log("Session ID: " . session_id());
    error_log("Contenido sesión: " . print_r($_SESSION, true));
    
    header('Location: iniciosesion.php');
    exit;
}
require_once '../../config/database.php';

$mensaje = '';
$tipo_mensaje = '';

try {
    $db = getDBConnection();
    $user_id = $_SESSION['user_id']; // ✅ Usar user_id
    
    error_log("✅ Acceso permitido a miperfil.php - User ID: " . $user_id);
    
    // Obtener datos del usuario
    $stmt = $db->prepare("SELECT id_usuario, codigo_paciente, nombre, apellidos, correo, telefono, foto_perfil, rol FROM Usuarios WHERE id_usuario = ?");
    $stmt->execute([$user_id]);
    $usuario = $stmt->fetch();
    
    if (!$usuario) {
        error_log("❌ Usuario no encontrado en BD con ID: " . $user_id);
        header('Location: iniciosesion.php');
        exit;
    }
    
    // Procesar actualización de perfil
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_perfil'])) {
        $nombre = trim($_POST['nombre']);
        $apellidos = trim($_POST['apellidos']);
        $telefono = trim($_POST['telefono']);
        
        // Validaciones
        if (empty($nombre) || empty($apellidos)) {
            $mensaje = 'El nombre y apellidos son obligatorios';
            $tipo_mensaje = 'error';
        } elseif (!preg_match('/^[0-9]{10}$/', $telefono)) {
            $mensaje = 'El teléfono debe tener 10 dígitos';
            $tipo_mensaje = 'error';
        } else {
            // Actualizar datos
            $stmt = $db->prepare("UPDATE Usuarios SET nombre = ?, apellidos = ?, telefono = ? WHERE id_usuario = ?");
            if ($stmt->execute([$nombre, $apellidos, $telefono, $user_id])) {
                $mensaje = 'Perfil actualizado correctamente';
                $tipo_mensaje = 'success';
                
                // Actualizar sesión
                $_SESSION['user_name'] = $nombre . ' ' . $apellidos;
                
                // Recargar datos
                $stmt = $db->prepare("SELECT id_usuario, codigo_paciente, nombre, apellidos, correo, telefono, foto_perfil, rol FROM Usuarios WHERE id_usuario = ?");
                $stmt->execute([$user_id]);
                $usuario = $stmt->fetch();
            } else {
                $mensaje = 'Error al actualizar el perfil';
                $tipo_mensaje = 'error';
            }
        }
    }
    
    // Procesar cambio de contraseña
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_contrasena'])) {
        $contrasena_actual = $_POST['contrasena_actual'];
        $nueva_contrasena = $_POST['nueva_contrasena'];
        $confirmar_contrasena = $_POST['confirmar_contrasena'];
        
        // Obtener contraseña actual de BD
        $stmt = $db->prepare("SELECT contrasena FROM Usuarios WHERE id_usuario = ?");
        $stmt->execute([$user_id]);
        $user_data = $stmt->fetch();
        
        if (!password_verify($contrasena_actual, $user_data['contrasena'])) {
            $mensaje = 'La contraseña actual es incorrecta';
            $tipo_mensaje = 'error';
        } elseif (strlen($nueva_contrasena) < 6) {
            $mensaje = 'La nueva contraseña debe tener al menos 6 caracteres';
            $tipo_mensaje = 'error';
        } elseif ($nueva_contrasena !== $confirmar_contrasena) {
            $mensaje = 'Las contraseñas no coinciden';
            $tipo_mensaje = 'error';
        } else {
            $hash = password_hash($nueva_contrasena, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE Usuarios SET contrasena = ? WHERE id_usuario = ?");
            if ($stmt->execute([$hash, $user_id])) {
                $mensaje = 'Contraseña actualizada correctamente';
                $tipo_mensaje = 'success';
            } else {
                $mensaje = 'Error al actualizar la contraseña';
                $tipo_mensaje = 'error';
            }
        }
    }
    
    // Procesar actualización de foto
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['foto_perfil'])) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['foto_perfil']['name'];
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);
        
        // Validar tipo MIME real del archivo
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $_FILES['foto_perfil']['tmp_name']);
        finfo_close($finfo);
        
        $allowed_mimes = ['image/jpeg', 'image/png', 'image/gif'];
        
        if (!in_array(strtolower($filetype), $allowed)) {
            $mensaje = 'Solo se permiten archivos JPG, JPEG, PNG y GIF';
            $tipo_mensaje = 'error';
        } elseif (!in_array($mime, $allowed_mimes)) {
            $mensaje = 'El archivo no es una imagen válida';
            $tipo_mensaje = 'error';
        } elseif ($_FILES['foto_perfil']['size'] > 2097152) { // 2MB
            $mensaje = 'El archivo es demasiado grande (máximo 2MB)';
            $tipo_mensaje = 'error';
        } else {
            $target_dir = "uploads/perfiles/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            $new_filename = 'perfil_' . $user_id . '_' . time() . '.' . $filetype;
            $target_file = $target_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $target_file)) {
                // Eliminar foto anterior si existe
                if (!empty($usuario['foto_perfil']) && file_exists($usuario['foto_perfil'])) {
                    unlink($usuario['foto_perfil']);
                }
                
                $stmt = $db->prepare("UPDATE Usuarios SET foto_perfil = ? WHERE id_usuario = ?");
                if ($stmt->execute([$target_file, $user_id])) {
                    $mensaje = 'Foto de perfil actualizada correctamente';
                    $tipo_mensaje = 'success';
                    $usuario['foto_perfil'] = $target_file;
                } else {
                    $mensaje = 'Error al guardar la foto en la base de datos';
                    $tipo_mensaje = 'error';
                }
            } else {
                $mensaje = 'Error al subir el archivo';
                $tipo_mensaje = 'error';
            }
        }
    }
    
} catch (Exception $e) {
    $mensaje = 'Error del servidor: ' . $e->getMessage();
    $tipo_mensaje = 'error';
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - Dentix</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/js/all.min.js"></script>
    <style>
        .bg-dental-gradient { background: linear-gradient(135deg, #00b4d8 0%, #0077b6 100%); }
        .dental-accent { color: #0077b6; }
    </style>
</head>
<body class="bg-gray-50">
    
    <!-- Main Content -->
    <main class="container mx-auto px-4 py-8">
        <?php if (!empty($mensaje)): ?>
        <div class="mb-6 p-4 rounded-lg <?php echo $tipo_mensaje === 'success' ? 'bg-green-100 text-green-700 border border-green-300' : 'bg-red-100 text-red-700 border border-red-300'; ?>">
            <i class="fas <?php echo $tipo_mensaje === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mr-2"></i>
            <?php echo htmlspecialchars($mensaje); ?>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Sidebar - Foto de Perfil -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="text-center">
                        <div class="mb-4">
                            <?php if (!empty($usuario['foto_perfil']) && file_exists($usuario['foto_perfil'])): ?>
                                <img src="<?php echo htmlspecialchars($usuario['foto_perfil']); ?>" alt="Foto de perfil" class="w-32 h-32 rounded-full mx-auto object-cover border-4 border-blue-500">
                            <?php else: ?>
                                <div class="w-32 h-32 rounded-full mx-auto bg-dental-gradient flex items-center justify-center text-white text-4xl">
                                    <i class="fas fa-user"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <h2 class="text-xl font-bold mb-1"><?php echo htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellidos']); ?></h2>
                        <p class="text-gray-600 text-sm mb-1">
                            <i class="fas fa-user-tag dental-accent mr-1"></i>
                            <?php echo $usuario['rol'] === 'Odontologo' ? 'Odontólogo' : 'Paciente'; ?>
                        </p>
                        <?php if (!empty($usuario['codigo_paciente'])): ?>
                        <p class="text-gray-600 text-sm">
                            <i class="fas fa-id-card dental-accent mr-1"></i>
                            Código: <?php echo htmlspecialchars($usuario['codigo_paciente']); ?>
                        </p>
                        <?php endif; ?>
                        
                        <!-- Formulario para cambiar foto -->
                        <form method="POST" enctype="multipart/form-data" class="mt-4">
                            <input type="file" name="foto_perfil" id="foto_perfil" accept="image/*" class="hidden" onchange="this.form.submit()">
                            <label for="foto_perfil" class="cursor-pointer bg-dental-gradient text-white px-4 py-2 rounded hover:opacity-90 inline-block text-sm">
                                <i class="fas fa-camera mr-1"></i>Cambiar Foto
                            </label>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Main Content - Información del Perfil -->
            <div class="lg:col-span-2">
                <!-- Datos Personales -->
                <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <h3 class="text-lg font-bold mb-4 flex items-center">
                        <i class="fas fa-user dental-accent mr-2"></i>
                        Datos Personales
                    </h3>
                    <form method="POST">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Nombre(s)</label>
                                <input type="text" name="nombre" value="<?php echo htmlspecialchars($usuario['nombre']); ?>" required
                                    class="w-full px-4 py-2 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Apellidos</label>
                                <input type="text" name="apellidos" value="<?php echo htmlspecialchars($usuario['apellidos']); ?>" required
                                    class="w-full px-4 py-2 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500">
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Correo Electrónico</label>
                            <input type="email" value="<?php echo htmlspecialchars($usuario['correo']); ?>" disabled
                                class="w-full px-4 py-2 border-2 border-gray-200 rounded-lg bg-gray-100 text-gray-500 cursor-not-allowed">
                            <p class="text-xs text-gray-500 mt-1">El correo no se puede modificar</p>
                        </div>
                        
                        <div class="mb-4">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Teléfono</label>
                            <input type="tel" name="telefono" value="<?php echo htmlspecialchars($usuario['telefono']); ?>" 
                                pattern="[0-9]{10}" maxlength="10" required
                                class="w-full px-4 py-2 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500">
                        </div>
                        
                        <button type="submit" name="actualizar_perfil" class="bg-dental-gradient text-white px-6 py-2 rounded-lg hover:opacity-90">
                            <i class="fas fa-save mr-2"></i>Guardar Cambios
                        </button>
                    </form>
                </div>

                <!-- Cambiar Contraseña -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-bold mb-4 flex items-center">
                        <i class="fas fa-lock dental-accent mr-2"></i>
                        Cambiar Contraseña
                    </h3>
                    <form method="POST">
                        <div class="mb-4 relative">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Contraseña Actual</label>
                            <input type="password" id="contrasena_actual" name="contrasena_actual" required
                                class="w-full px-4 py-2 pr-12 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500">
                            <i id="toggle-contrasena-actual" class="fas fa-eye absolute right-4 top-11 cursor-pointer text-gray-500 hover:text-cyan-600 transition-colors" onclick="togglePasswordVisibility('contrasena_actual', 'toggle-contrasena-actual')"></i>
                        </div>
                        
                        <div class="mb-4 relative">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Nueva Contraseña</label>
                            <input type="password" id="nueva_contrasena" name="nueva_contrasena" minlength="6" required
                                class="w-full px-4 py-2 pr-12 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500">
                            <i id="toggle-nueva-contrasena" class="fas fa-eye absolute right-4 top-11 cursor-pointer text-gray-500 hover:text-cyan-600 transition-colors" onclick="togglePasswordVisibility('nueva_contrasena', 'toggle-nueva-contrasena')"></i>
                            <p class="text-xs text-gray-500 mt-1">Mínimo 6 caracteres</p>
                        </div>
                        
                        <div class="mb-4 relative">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Confirmar Nueva Contraseña</label>
                            <input type="password" id="confirmar_contrasena" name="confirmar_contrasena" minlength="6" required
                                class="w-full px-4 py-2 pr-12 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500">
                            <i id="toggle-confirmar-contrasena" class="fas fa-eye absolute right-4 top-11 cursor-pointer text-gray-500 hover:text-cyan-600 transition-colors" onclick="togglePasswordVisibility('confirmar_contrasena', 'toggle-confirmar-contrasena')"></i>
                        </div>
                        
                        <button type="submit" name="cambiar_contrasena" class="bg-dental-gradient text-white px-6 py-2 rounded-lg hover:opacity-90">
                            <i class="fas fa-key mr-2"></i>Actualizar Contraseña
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <script>
        function togglePasswordVisibility(inputId, iconId) {
            const passwordField = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            
            if (passwordField.type === "password") {
                passwordField.type = "text";
                icon.classList.remove("fa-eye");
                icon.classList.add("fa-eye-slash");
            } else {
                passwordField.type = "password";
                icon.classList.remove("fa-eye-slash");
                icon.classList.add("fa-eye");
            }
        }

function confirmarCerrarSesion() {
    document.getElementById('modal-cerrar-sesion').classList.remove('hidden');
    document.getElementById('modal-cerrar-sesion').classList.add('flex');
}

async function cerrarSesion() {
    try {
        // Primero, cerrar sesión en el servidor
        const response = await fetch('../../api/logout.php');
        const data = await response.json();
        
        if (data.success) {
            // Limpiar datos locales
            localStorage.clear();
            
            // Cerrar modal
            document.getElementById('modal-cerrar-sesion').classList.add('hidden');
            document.getElementById('modal-cerrar-sesion').classList.remove('flex');
            
            // Redirigir al inicio de sesión
            window.location.href = 'iniciosesion.php';
        } else {
            alert('Error al cerrar sesión');
        }
    } catch (error) {
        console.error('Error:', error);
        // Fallback: limpiar localStorage y redirigir de todas formas
        localStorage.clear();
        window.location.href = 'iniciosesion.php';
    }
}

function cancelarCerrarSesion() {
    document.getElementById('modal-cerrar-sesion').classList.add('hidden');
    document.getElementById('modal-cerrar-sesion').classList.remove('flex');
}
    </script>
</body>

<!-- Modal de confirmación de cerrar sesión -->
<div id="modal-cerrar-sesion" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <div class="text-center">
            <i class="fas fa-sign-out-alt text-4xl text-red-500 mb-4"></i>
            <h3 class="text-lg font-bold text-gray-900 mb-2">¿Cerrar Sesión?</h3>
            <p class="text-gray-600 mb-6">¿Estás seguro de que quieres cerrar tu sesión actual?</p>
            
            <div class="flex space-x-3">
                <button onclick="cerrarSesion()" class="flex-1 bg-red-500 text-white py-2 px-4 rounded-lg hover:bg-red-600 transition-colors">
                    <i class="fas fa-sign-out-alt mr-2"></i>Sí, cerrar sesión
                </button>
                <button onclick="cancelarCerrarSesion()" class="flex-1 bg-gray-300 text-gray-700 py-2 px-4 rounded-lg hover:bg-gray-400 transition-colors">
                    <i class="fas fa-times mr-2"></i>Cancelar
                </button>
            </div>
        </div>
    </div>
</div>

</html>
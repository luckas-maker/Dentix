<?php
session_start();

// Verificar autenticación con user_id
if(!isset($_SESSION['user_id'])) {
    header('Location: ../../MODULO1/HTML/iniciosesion.php');
    exit();
}

// Obtener datos del usuario
require_once '../../config/database.php';
$db = getDBConnection();

$query = "SELECT nombre, apellidos FROM usuarios WHERE id_usuario = ?";
$stmt = $db->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$usuario = $stmt->fetch();

if (!$usuario) {
    header('Location: ../../MODULO1/HTML/iniciosesion.php');
    exit();
}

// ✅ Verificar con el nombre correcto
if (!isset($_SESSION['user_id'])) {
   error_log("❌ Acceso denegado a veragendas.php - No hay user_id en sesión");
   error_log("Session ID: " . session_id());
   error_log("Contenido sesión: " . print_r($_SESSION, true));
    
   header('Location: ../../MODULO1/HTML/iniciosesion.php');
    exit;
}

require_once '../../config/database.php';

try {
    $db = getDBConnection();
    $user_id = $_SESSION['user_id']; // ✅ Usar user_id
    
    error_log("✅ Acceso permitido a veragendas.php - User ID: " . $user_id);
    
    // Obtener datos del usuario
    $stmt = $db->prepare("SELECT nombre, apellidos FROM usuarios WHERE id_usuario = ?");
    $stmt->execute([$user_id]);
   $usuario = $stmt->fetch();
    
   if (!$usuario) {
       error_log("❌ Usuario no encontrado en BD con ID: " . $user_id);
       header('Location: ../../MODULO1/HTML/iniciosesion.php');
    exit;
    }
    
} catch (Exception $e) {
   error_log("❌ Error en veragendas.php: " . $e->getMessage());
   header('Location: ../../MODULO1/HTML/iniciosesion.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultorio Dental Dentix</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/js/all.min.js"></script>
    <style>
       body {
            margin: 0;
            padding: 0;
            height: 100vh;
            overflow: hidden;
        }
        #content {
            height: calc(100vh - 80px); 
        }
        iframe {
            width: 100%;
            height: 100%;
            border: none;
        }
          .bg-dental-gradient { background: linear-gradient(135deg, #00b4d8 0%, #0077b6 100%); }
        .dental-accent { color: #0077b6; }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-dental-gradient text-white shadow-lg">
        <div class="container mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-4">
                    <a href="#" onclick="loadContent('../../MODULO3/HTML/gestiondisponibilidad.php')">
                        <img src="../../assets/img/logo-writopeks.jpg" alt="Writo Peks Consultorio Dental" class="h-8 w-auto">
                    </a>
                    <h1 class="text-xl font-bold">Consultorio Dental Dentix</h1>
                    <nav class="hidden md:flex space-x-4 ml-8">
                        <button onclick="loadContent('../../MODULO3/HTML/agenda.php')" class="text-white font-bold hover:text-gray-200 transition-colors">Agendar citas</button>
                        <button onclick="loadContent('../../MODULO3/HTML/citasolicitadas.php')" class="text-white font-bold hover:text-gray-200 transition-colors">Citas solicitadas</button>
                        <button onclick="loadContent('../../MODULO3/HTML/gestiondisponibilidad.php')" class="text-white font-bold hover:text-gray-200 transition-colors">Gestion de disponibilidad</button>
                        <button onclick="loadContent('../../MODULO3/HTML/historial.php')" class="text-white font-bold hover:text-gray-200 transition-colors">Historial de citas</button>
                        <button onclick="loadContent('../../MODULO4/HTML/Panel_Admin_Cuentas.php')" class="text-white font-bold hover:text-gray-200 transition-colors">Panel Admin</button>
                    </nav>
                </div>
                <div class="flex items-center space-x-4">
                    <button onclick="loadContent('../../MODULO1/HTML/miperfilodontologo.php')" class="bg-blue-600 hover:bg-blue-700 px-3 py-1 rounded text-sm transition-colors">
                        <i class="fas fa-user mr-1"></i>Mi Perfil
                    </button>
                    <span class="text-sm"><?php echo htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellidos']); ?></span>
                    <button onclick="confirmarCerrarSesion()" class="bg-red-500 hover:bg-red-600 px-3 py-1 rounded text-sm transition-colors">
                        <i class="fas fa-sign-out-alt mr-1"></i>Cerrar Sesión
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content Area -->
    <main id="content" class="w-full">
        <!-- Contenido se carga aquí -->
    </main>

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

    <script>
        // Cargar automaticamente el contenido de "mi Perfil"
        document.addEventListener('DOMContentLoaded', function() {
            loadContent('../../MODULO3/HTML/gestiondisponibilidad.php');
        });

        function loadContent(page) {
            document.getElementById('content').innerHTML = `<iframe src="${page}" style="width:100%; height:100%; border:none;"></iframe>`;
        }

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
                    window.location.href = '../../MODULO1/HTML/iniciosesion.php';
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
</html>

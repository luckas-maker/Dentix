<?php
session_start();

// --- SEGURIDAD: VERIFICACIÓN DE ROL DE ODONTÓLOGO ---

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../MODULO1/HTML/iniciosesion.php');
    exit;
}

// Verificar ROL
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Odontologo') {
    // Si no es Odontólogo, redirigir
    header('Location: ../../MODULO2/HTML/dashboardclient.php');
    exit;
}

require_once '../../config/database.php';

try {
    $db = getDBConnection();
    $user_id = $_SESSION['user_id'];
    
    // --- CONSULTA SQL: Agregamos 'foto_perfil' ---
    $stmt = $db->prepare("SELECT nombre, apellidos, foto_perfil FROM usuarios WHERE id_usuario = ?");
    $stmt->execute([$user_id]);
    $usuario = $stmt->fetch();
    
    if (!$usuario) {
        session_destroy();
        header('Location: ../../MODULO1/HTML/iniciosesion.php');
        exit;
    }
    
} catch (Exception $e) {
    error_log("Error en dashboard odontologo: " . $e->getMessage());
    header('Location: ../../MODULO1/HTML/iniciosesion.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Odontólogo - Dentix</title>
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
            height: calc(100vh - 72px); /* Ajuste altura header */
            width: 100%;
        }
        iframe {
            width: 100%;
            height: 100%;
            border: none;
        }
        .bg-dental-gradient { 
            background: linear-gradient(135deg, #00b4d8 0%, #0077b6 100%); 
        }
        /* Efecto hover sutil para los enlaces del menú */
        .nav-link {
            position: relative;
            opacity: 0.9;
            transition: opacity 0.2s;
            white-space: nowrap; /* Evita que el texto se rompa en dos líneas */
        }
        .nav-link:hover {
            opacity: 1;
        }
        .nav-link::after {
            content: '';
            position: absolute;
            bottom: -4px;
            left: 0;
            width: 0;
            height: 2px;
            background-color: white;
            transition: width 0.3s;
        }
        .nav-link:hover::after {
            width: 100%;
        }
    </style>
</head>

<body class="h-full bg-gray-50 font-sans flex flex-col">
    
    <header class="bg-dental-gradient text-white shadow-lg z-50 relative h-[72px]">
        <div class="max-w-7xl mx-auto px-4 h-full">
            <div class="flex justify-between items-center h-full">
                
                <div class="flex items-center gap-6 lg:gap-8">
                    <div class="flex items-center gap-3 cursor-pointer" onclick="loadContent('../../MODULO3/HTML/gestiondisponibilidad.php')">
                        <div class="bg-white/90 p-1.5 rounded-lg shadow-sm">
                            <img src="../../assets/img/logo-writopeks.jpg" alt="Logo" class="h-8 w-auto rounded">
                        </div>
                        <div class="flex flex-col hidden lg:flex"> <h1 class="text-xl font-extrabold tracking-wide leading-none">DENTIX</h1>
                            <span class="text-[10px] uppercase tracking-wider opacity-90 font-medium">Odontología</span>
                        </div>
                    </div>

                    <nav class="hidden md:flex items-center gap-4 lg:gap-6">
                        <button onclick="loadContent('../../MODULO3/HTML/agenda.php')" class="nav-link text-white font-bold text-sm tracking-wide">
                            Agenda
                        </button>
                        
                        <button onclick="loadContent('../../MODULO3/HTML/citasolicitadas.php')" class="nav-link text-white font-bold text-sm tracking-wide" title="Citas pendientes de aceptar">
                            Solicitudes
                        </button>
                        
                        <button onclick="loadContent('../../MODULO3/HTML/gestiondisponibilidad.php')" class="nav-link text-white font-bold text-sm tracking-wide" title="Crear Horarios">
                            Horarios
                        </button>
                        
                        <button onclick="loadContent('../../MODULO3/HTML/historial.php')" class="nav-link text-white font-bold text-sm tracking-wide">
                            Historial
                        </button>
                        
                        <button onclick="loadContent('../../MODULO4/HTML/Panel_Admin_Cuentas.php')" class="nav-link text-white font-bold text-sm tracking-wide" title="Administrar Usuarios">
                            Usuarios
                        </button>
                    </nav>
                </div>

                <div class="flex items-center gap-4">
                    
                    <span class="text-sm font-medium hidden sm:block text-blue-50">
                        Dr. <strong><?php echo htmlspecialchars($usuario['nombre']); ?></strong>
                    </span>

                    <button onclick="loadContent('../../MODULO1/HTML/miperfilodontologo.php')" class="h-10 w-10 rounded-full bg-white text-blue-600 flex items-center justify-center font-bold text-lg shadow-md border-2 border-transparent hover:border-blue-200 transition-all overflow-hidden" title="Mi Perfil">
                        <?php if (!empty($usuario['foto_perfil'])): ?>
                            <img src="../../MODULO1/HTML/<?php echo htmlspecialchars($usuario['foto_perfil']); ?>" alt="Perfil" class="h-full w-full object-cover">
                        <?php else: ?>
                            <?php echo strtoupper(substr($usuario['nombre'], 0, 1)); ?>
                        <?php endif; ?>
                    </button>

                    <div class="h-8 w-px bg-white/20 mx-1 hidden sm:block"></div>

                    <button onclick="confirmarCerrarSesion()" class="bg-red-500 hover:bg-red-600 px-4 py-2 rounded-lg text-sm font-bold transition-colors shadow-sm flex items-center gap-2">
                        <i class="fas fa-sign-out-alt"></i>
                        <span class="hidden sm:inline">Salir</span>
                    </button>
                </div>

            </div>
        </div>
    </header>

    <main id="content" class="flex-1 bg-gray-50">
        </main>

    <div id="modal-cerrar-sesion" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white rounded-2xl p-6 max-w-sm w-full mx-4 shadow-2xl transform transition-all">
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                    <i class="fas fa-sign-out-alt text-xl text-red-600"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">¿Cerrar Sesión?</h3>
                <p class="text-sm text-gray-500 mb-6">¿Estás seguro de que quieres salir de tu cuenta?</p>
                
                <div class="flex space-x-3">
                    <button onclick="cancelarCerrarSesion()" class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 font-medium transition-colors">
                        Cancelar
                    </button>
                    <button onclick="cerrarSesion()" class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 font-medium transition-colors">
                        Sí, Salir
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Cargar contenido por defecto (Gestión de disponibilidad / Horarios)
        document.addEventListener('DOMContentLoaded', function() {
            loadContent('../../MODULO3/HTML/gestiondisponibilidad.php');
        });

        function loadContent(page) {
            document.getElementById('content').innerHTML = `<iframe src="${page}" style="width:100%; height:100%; border:none;" allowtransparency="true"></iframe>`;
        }

        function confirmarCerrarSesion() {
            document.getElementById('modal-cerrar-sesion').classList.remove('hidden');
            document.getElementById('modal-cerrar-sesion').classList.add('flex');
        }

        async function cerrarSesion() {
            try {
                const response = await fetch('../../api/logout.php');
                const data = await response.json();
                
                if (data.success) {
                    localStorage.clear();
                    document.getElementById('modal-cerrar-sesion').classList.add('hidden');
                    window.location.href = '../../MODULO1/HTML/iniciosesion.php';
                } else {
                    alert('Error al cerrar sesión');
                }
            } catch (error) {
                console.error('Error:', error);
                localStorage.clear();
                window.location.href = '../../MODULO1/HTML/iniciosesion.php';
            }
        }

        function cancelarCerrarSesion() {
            document.getElementById('modal-cerrar-sesion').classList.add('hidden');
            document.getElementById('modal-cerrar-sesion').classList.remove('flex');
        }
    </script>
</body>
</html>
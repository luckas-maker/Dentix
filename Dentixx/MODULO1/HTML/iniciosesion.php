<?php
session_start();

// Si ya está logueado, redirigir automáticamente
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_role'] === 'Odontologo') {
        header('Location: ../../MODULO3/HTML/dashboard.php');
    } else {
        header('Location: ../../MODULO2/HTML/dashboardclient.php');
    }
    exit;
}

// Mensaje opcional para logout
$mensaje = '';
if (isset($_GET['logout'])) {
    $mensaje = 'Sesión cerrada correctamente.';
    session_destroy();
}
?>
<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenido a Dentix</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/js/all.min.js"></script>
    <style>
        @keyframes gradient { 0% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } 100% { background-position: 0% 50%; } }
        .animated-gradient { background: linear-gradient(-45deg, #00b4d8, #0077b6, #48cae4, #90e0ef); background-size: 400% 400%; animation: gradient 15s ease infinite; }
        .glass-effect { background: rgba(255, 255, 255, 0.98); backdrop-filter: blur(10px); }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.6); justify-content: center; align-items: center; z-index: 10000; backdrop-filter: blur(5px); }
        .modal-content { background-color: #fff; padding: 2rem; border-radius: 20px; max-width: 500px; width: 90%; box-shadow: 0 25px 50px -12px rgba(0, 119, 182, 0.3); max-height: 90vh; overflow-y: auto; }
        @media (max-width: 640px) { .modal-content { padding: 1.5rem; max-height: 85vh; } }
        .floating-animation { animation: float 6s ease-in-out infinite; }
        @keyframes float { 0%, 100% { transform: translateY(0px); } 50% { transform: translateY(-20px); } }
        .pulse-shadow { animation: pulse-shadow 2s ease-in-out infinite; }
        @keyframes pulse-shadow { 0%, 100% { box-shadow: 0 10px 40px rgba(0, 180, 216, 0.4); } 50% { box-shadow: 0 15px 60px rgba(0, 180, 216, 0.6); } }
        input:focus { transform: translateY(-2px); transition: all 0.3s ease; }
        .btn-dental { background: linear-gradient(135deg, #0077b6 0%, #00b4d8 100%); transition: all 0.3s ease; }
        .btn-dental:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(0, 119, 182, 0.4); }
        .input-modern { transition: all 0.3s ease; }
        .input-modern:focus { box-shadow: 0 0 0 3px rgba(0, 180, 216, 0.1); }
        .dental-accent { color: #0077b6; }
        .bg-dental-gradient { background: linear-gradient(135deg, #00b4d8 0%, #0077b6 100%); }
    </style>
</head>
<body class="h-full animated-gradient font-sans">
    <div class="min-h-screen flex items-center justify-center px-4 py-6 sm:py-8 md:py-12">
        <div class="max-w-6xl w-full flex flex-col lg:flex-row rounded-3xl shadow-2xl overflow-hidden">
            <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-cyan-600 via-blue-700 to-sky-500 p-12 flex-col justify-center items-center text-white relative overflow-hidden">
                <div class="absolute inset-0 opacity-10">
                    <div class="absolute top-0 left-0 w-72 h-72 bg-white rounded-full mix-blend-overlay filter blur-xl animate-pulse"></div>
                    <div class="absolute bottom-0 right-0 w-96 h-96 bg-white rounded-full mix-blend-overlay filter blur-xl animate-pulse delay-1000"></div>
                </div>
                
                <div class="relative z-10 text-center floating-animation">
                    <div class="hidden lg:flex justify-center mb-8">
                        <img src="../../assets/img/logo-writopeks.jpg" alt="Writo Peks Consultorio Dental"
                            class="w-50 h-32 object-cover border-4 border-white shadow-lg rounded-2xl" />
                    </div>

                    <h2 class="text-4xl font-bold mb-4">Tu Sonrisa Es Nuestra Misión</h2>
                    <p class="text-xl mb-8 opacity-90">Consultorio Dental Dentix</p>
                    <div class="space-y-4 text-left max-w-md mx-auto">
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-calendar-check text-2xl"></i>
                            <span>Agenda tu cita en línea</span>
                        </div>
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-user-md text-2xl"></i>
                            <span>Atención profesional especializada</span>
                        </div>
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-shield-alt text-2xl"></i>
                            <span>Tecnología y seguridad garantizada</span>
                        </div>
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-smile-beam text-2xl"></i>
                            <span>Resultados que te harán sonreír</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="w-full lg:w-1/2 glass-effect p-6 sm:p-8 md:p-12">
                <div class="flex justify-center mb-6 lg:hidden">
                    <img src="../../assets/img/logo-writopeks.jpg" alt="Writo Peks Consultorio Dental"
                        class="w-24 h-24 object-cover shadow-lg rounded-xl" />
                </div>
                
                <div class="hidden lg:flex justify-center mb-8">
                    <img src="../../assets/img/logo-writopeks.jpg" alt="Writo Peks Consultorio Dental"
                        class="w-50 h-32 object-cover shadow-lg rounded-2xl pulse-shadow" />
                </div>
                
                <div class="text-center mb-6 sm:mb-8">
                    <h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold bg-gradient-to-r from-cyan-600 to-blue-700 bg-clip-text text-transparent mb-2">
                        Consultorio Dental "Dentix"
                    </h1>
                    <p class="text-sm sm:text-base text-gray-600">
                        <i class="fas fa-tooth dental-accent mr-1"></i>
                        Cuidando de tu sonrisa, siempre
                    </p>
                </div>

                
                <form id="login-form" class="space-y-5 sm:space-y-6" onsubmit="return validarLogin(event)">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-envelope dental-accent mr-2"></i>
                            Correo Electrónico
                        </label>
                        <input type="email" id="email" required 
                            class="input-modern w-full px-4 py-3 sm:py-4 border-2 border-gray-200 rounded-xl shadow-sm focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 transition-all text-sm sm:text-base"
                            placeholder="tu@email.com">
                        <div id="email-error" class="hidden text-sm text-red-600 mt-2 flex items-center">
                            <i class="fas fa-exclamation-circle mr-1"></i>
                            Por favor ingresa un correo electrónico válido.
                        </div>
                    </div>
                    
                    <div class="relative">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-lock dental-accent mr-2"></i>
                            Contraseña
                        </label>
                        <input type="password" id="password" required 
                            class="input-modern w-full px-4 py-3 sm:py-4 border-2 border-gray-200 rounded-xl shadow-sm focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 transition-all pr-12 text-sm sm:text-base"
                            placeholder="••••••••">
                        <i id="toggle-password" class="fas fa-eye absolute right-4 top-11 sm:top-12 cursor-pointer text-gray-500 hover:text-cyan-600 transition-colors" onclick="togglePassword()"></i>
                        <div id="password-error" class="hidden text-sm text-red-600 mt-2 flex items-center">
                            <i class="fas fa-exclamation-circle mr-1"></i>
                            La contraseña es obligatoria.
                        </div>
                    </div>
                    
                    <button type="submit" 
                        class="btn-dental w-full py-3 sm:py-4 px-4 text-white font-bold rounded-xl shadow-lg text-sm sm:text-base">
                        <i class="fas fa-sign-in-alt mr-2"></i>
                        Iniciar Sesión
                    </button>
                </form>
                
                <div id="alert-login" class="hidden mt-4 text-center p-3 rounded-xl"></div>
                
                <div class="text-center mt-6">
                    <button onclick="mostrarRecuperacion()" class="text-sm sm:text-base font-semibold dental-accent hover:text-blue-800 transition-colors inline-flex items-center">
                        <i class="fas fa-key mr-2"></i>
                        ¿Olvidaste tu contraseña?
                    </button>
                </div>
                
                <div class="text-center mt-6 pt-6 border-t-2 border-gray-200">
                    <p class="text-sm sm:text-base text-gray-600">
                        ¿Primera vez en Dentix? 
                        <button onclick="mostrarRegistro()" 
                            class="font-bold dental-accent hover:text-blue-800 transition-colors">
                            Regístrate aquí
                        </button>
                    </p>
                </div>

                <div class="text-center mt-6 pt-6 border-t-2 border-gray-200">
                    <p class="text-xs text-gray-500">
                        <i class="fas fa-shield-alt mr-1"></i>
                        Tus datos están protegidos y seguros
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div id="registro-modal" class="modal">
        <div class="modal-content">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl sm:text-2xl font-bold bg-gradient-to-r from-cyan-600 to-blue-700 bg-clip-text text-transparent">
                    <i class="fas fa-user-plus mr-2"></i>
                    Nuevo Paciente
                </h2>
                <button onclick="ocultarRegistro()" class="text-gray-400 hover:text-gray-600 text-2xl transition-colors">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="registro-formulario" class="space-y-4" onsubmit="return validarRegistro(event)">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-user dental-accent mr-2"></i>
                            Nombre(s)
                        </label>
                        <input type="text" id="nombres" required 
                            class="input-modern w-full px-4 py-3 border-2 border-gray-200 rounded-xl shadow-sm focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500"
                            placeholder="Ej: Juan Carlos">
                        <div id="nombres-error" class="hidden text-sm text-red-600 mt-2 flex items-center">
                            <i class="fas fa-exclamation-circle mr-1"></i>
                            Por favor ingresa solo letras.
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-user-tag dental-accent mr-2"></i>
                            Apellidos
                        </label>
                        <input type="text" id="apellidos" required 
                            class="input-modern w-full px-4 py-3 border-2 border-gray-200 rounded-xl shadow-sm focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500"
                            placeholder="Ej: Pérez García">
                        <div id="apellidos-error" class="hidden text-sm text-red-600 mt-2 flex items-center">
                            <i class="fas fa-exclamation-circle mr-1"></i>
                            Por favor ingresa solo letras.
                        </div>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-envelope dental-accent mr-2"></i>
                        Correo Electrónico
                    </label>
                    <input type="email" id="registro-email" required 
                        class="input-modern w-full px-4 py-3 border-2 border-gray-200 rounded-xl shadow-sm focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500"
                        placeholder="tu@email.com">
                    
                    <div id="registro-email-error" class="hidden text-sm text-red-600 mt-2 flex items-center">
                        <i class="fas fa-exclamation-circle mr-1"></i>
                        Por favor ingresa un correo electrónico válido.
                    </div>
                    <div id="email-existente-error" class="hidden text-sm text-red-600 mt-2 flex items-center">
                        <i class="fas fa-exclamation-circle mr-1"></i>
                        Este correo ya está registrado.
                    </div>
                </div>
                
                <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-phone-alt dental-accent mr-2"></i>
                    Teléfono (10 dígitos)
                </label>
                <input type="tel" id="registro-telefono" name="telefono" required 
                    pattern="[0-9]{10}" 
                    maxlength="10"
                    class="input-modern w-full px-4 py-3 border-2 border-gray-200 rounded-xl shadow-sm focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500"
                    placeholder="7541234567">
                    
                <div id="registro-telefono-error" class="hidden text-sm text-red-600 mt-2 flex items-center">
                    <i class="fas fa-exclamation-circle mr-1"></i>
                    Ingresa un número de teléfono válido de 10 dígitos.
                </div>
                
                <div id="telefono-existente-error" class="hidden text-sm text-red-600 mt-2 flex items-center">
                    <i class="fas fa-exclamation-circle mr-1"></i>
                    Este número ya ha sido registrado.
                </div>
                </div>


                <div class="relative">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-lock dental-accent mr-2"></i>
                        Contraseña
                    </label>
                    <input type="password" id="registro-password" required 
                        class="input-modern w-full px-4 py-3 border-2 border-gray-200 rounded-xl shadow-sm focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 pr-12"
                        placeholder="Mínimo 6 caracteres">
                    <i id="toggle-registro-password" class="fas fa-eye absolute right-4 top-11 cursor-pointer text-gray-500 hover:text-cyan-600 transition-colors" onclick="togglePasswordRegistro()"></i>
                    <div id="registro-password-error" class="hidden text-sm text-red-600 mt-2 flex items-center">
                        <i class="fas fa-exclamation-circle mr-1"></i>
                        La contraseña debe tener más de 6 caracteres.
                    </div>
                </div>
                
                <div class="relative">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-lock dental-accent mr-2"></i>
                        Confirmar Contraseña
                    </label>
                    <input type="password" id="confirmar-password" required 
                        class="input-modern w-full px-4 py-3 border-2 border-gray-200 rounded-xl shadow-sm focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 pr-12"
                        placeholder="Repite tu contraseña">
                    <i id="toggle-confirmar-password" class="fas fa-eye absolute right-4 top-11 cursor-pointer text-gray-500 hover:text-cyan-600 transition-colors" onclick="toggleConfirmarPassword()"></i>
                    <div id="confirmar-password-error" class="hidden text-sm text-red-600 mt-2 flex items-center">
                        <i class="fas fa-exclamation-circle mr-1"></i>
                        Las contraseñas no coinciden.
                    </div>
                </div>
                
                <button type="submit" 
                    class="btn-dental w-full py-3 px-4 text-white font-bold rounded-xl shadow-lg">
                    <i class="fas fa-user-check mr-2"></i>
                    Registrarme
                </button>
            </form>
            
            <div id="alert-registro" class="hidden mt-4 text-center p-3 rounded-xl"></div>
            
            <div class="text-center mt-6">
                <button onclick="ocultarRegistro()" class="text-sm font-semibold dental-accent hover:text-blue-800 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Ya tengo cuenta
                </button>
            </div>
        </div>
    </div>

    <div id="recuperacion-modal" class="modal">
        <div class="modal-content">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl sm:text-2xl font-bold bg-gradient-to-r from-cyan-600 to-blue-700 bg-clip-text text-transparent">
                    <i class="fas fa-key mr-2"></i>
                    Recuperar Acceso
                </h2>
                <button onclick="ocultarRecuperacion()" class="text-gray-400 hover:text-gray-600 text-2xl transition-colors">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <p class="text-gray-600 mb-6 text-sm sm:text-base">Ingresa tu correo electrónico y te enviaremos un código de verificación.</p>
            
            <form id="recuperacion-formulario" class="space-y-4" onsubmit="return enviarCodigoRecuperacion(event)">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-envelope dental-accent mr-2"></i>
                        Correo Electrónico
                    </label>
                    <input type="email" id="recuperacion-email" required
                        class="input-modern w-full px-4 py-3 border-2 border-gray-200 rounded-xl shadow-sm focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500"
                        placeholder="tu@email.com">
                    <div id="recuperacion-email-error" class="hidden text-sm text-red-600 mt-2 flex items-center">
                        <i class="fas fa-exclamation-circle mr-1"></i>
                        Ingresa un correo válido.
                    </div>
                </div>
                
                <button type="submit" 
                    class="btn-dental w-full py-3 px-4 text-white font-bold rounded-xl shadow-lg">
                    <i class="fas fa-paper-plane mr-2"></i>
                    Enviar Código
                </button>
            </form>
            
            <div id="alert-recuperacion" class="hidden mt-4 text-center p-3 rounded-xl"></div>
            
            <div class="text-center mt-6">
                <button onclick="ocultarRecuperacion()" class="text-sm font-semibold dental-accent hover:text-blue-800 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Cancelar
                </button>
            </div>
        </div>
    </div>

    <div id="verificar-modal" class="modal">
        <div class="modal-content">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl sm:text-2xl font-bold bg-gradient-to-r from-cyan-600 to-blue-700 bg-clip-text text-transparent">
                    <i class="fas fa-shield-alt mr-2"></i>
                    Código de Seguridad
                </h2>
                <button onclick="ocultarVerificacion()" class="text-gray-400 hover:text-gray-600 text-2xl transition-colors">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <p class="text-gray-600 mb-6 text-sm sm:text-base">Ingresa el código de 6 dígitos que enviamos a tu correo.</p>
            
            <form id="verificar-formulario" class="space-y-4" onsubmit="return verificarCodigo(event)">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2 text-center">Código de Verificación</label>
                    <input type="text" id="codigo" required maxlength="6"
                        class="input-modern w-full px-4 py-4 border-2 border-gray-200 rounded-xl shadow-sm focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 text-center text-2xl tracking-widest font-bold"
                        placeholder="000000">
                    <div id="codigo-error" class="hidden text-sm text-red-600 mt-2 flex items-center justify-center">
                        <i class="fas fa-exclamation-circle mr-1"></i>
                        Ingresa el código.
                    </div>
                </div>
                
                <button type="submit" 
                    class="btn-dental w-full py-3 px-4 text-white font-bold rounded-xl shadow-lg">
                    <i class="fas fa-check-circle mr-2"></i>
                    Verificar Código
                </button>
            </form>
            
            <div id="alert-verificar" class="hidden mt-4 text-center p-3 rounded-xl"></div>
            
            <div class="text-center mt-6">
                <button onclick="ocultarVerificacion()" class="text-sm font-semibold dental-accent hover:text-blue-800 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Cancelar
                </button>
            </div>
        </div>
    </div>

    <div id="verificar-email-modal" class="modal">
        <div class="modal-content">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl sm:text-2xl font-bold bg-gradient-to-r from-cyan-600 to-blue-700 bg-clip-text text-transparent">
                    <i class="fas fa-envelope-check mr-2"></i>
                    Verificar Correo
                </h2>
                <button onclick="ocultarVerificacionEmail()" class="text-gray-400 hover:text-gray-600 text-2xl transition-colors">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <p class="text-gray-600 mb-6 text-sm sm:text-base">
                Se ha enviado un código de 6 dígitos a 
                <strong id="email-a-verificar" class="text-gray-800">tu@email.com</strong>.
                <br>
                <span class="text-xs text-gray-500">Por favor, revisa tu bandeja de entrada (y spam).</span>
            </p>
            
            <form id="verificar-email-formulario" class="space-y-4" onsubmit="return verificarCodigoEmail(event)">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2 text-center">Código de Verificación</label>
                    <input type="text" id="codigo-email" required maxlength="6"
                        class="input-modern w-full px-4 py-4 border-2 border-gray-200 rounded-xl shadow-sm focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 text-center text-2xl tracking-widest font-bold"
                        placeholder="000000">
                    <div id="codigo-email-error" class="hidden text-sm text-red-600 mt-2 flex items-center justify-center">
                        <i class="fas fa-exclamation-circle mr-1"></i>
                        Código incorrecto. Intenta de nuevo.
                    </div>
                </div>
                
                <button type="submit" 
                    class="btn-dental w-full py-3 px-4 text-white font-bold rounded-xl shadow-lg">
                    <i class="fas fa-check-circle mr-2"></i>
                    Confirmar Código
                </button>
            </form>
            
            <div id="alert-verificar-email" class="hidden mt-4 text-center p-3 rounded-xl"></div>
            
            <div class="text-center mt-6">
                <button onclick="ocultarVerificacionEmail()" class="text-sm font-semibold dental-accent hover:text-blue-800 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Cancelar
                </button>
            </div>
        </div>
    </div>


    <div id="cambiar-modal" class="modal">
        <div class="modal-content">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl sm:text-2xl font-bold bg-gradient-to-r from-cyan-600 to-blue-700 bg-clip-text text-transparent">
                    <i class="fas fa-lock mr-2"></i>
                    Nueva Contraseña
                </h2>
                <button onclick="ocultarCambio()" class="text-gray-400 hover:text-gray-600 text-2xl transition-colors">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <p class="text-gray-600 mb-6 text-sm sm:text-base">Crea una nueva contraseña segura para tu cuenta.</p>
            
            <form id="cambiar-formulario" class="space-y-4" onsubmit="return cambiarContrasena(event)">
                <div class="relative">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-lock dental-accent mr-2"></i>
                        Nueva Contraseña
                    </label>
                    <input type="password" id="nueva-contrasena" required
                        class="input-modern w-full px-4 py-3 border-2 border-gray-200 rounded-xl shadow-sm focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 pr-12"
                        placeholder="Mínimo 6 caracteres">
                    <i id="toggle-nueva-password" class="fas fa-eye absolute right-4 top-11 cursor-pointer text-gray-500 hover:text-cyan-600 transition-colors" onclick="toggleNuevaPassword()"></i>
                    <div id="nueva-error" class="hidden text-sm text-red-600 mt-2 flex items-center">
                        <i class="fas fa-exclamation-circle mr-1"></i>
                        Debe tener más de 6 caracteres.
                    </div>
                </div>
                
                <div class="relative">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-lock dental-accent mr-2"></i>
                        Confirmar Contraseña
                    </label>
                    <input type="password" id="confirmar-nueva" required
                        class="input-modern w-full px-4 py-3 border-2 border-gray-200 rounded-xl shadow-sm focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 pr-12"
                        placeholder="Repite tu contraseña">
                    <i id="toggle-confirmar-nueva-password" class="fas fa-eye absolute right-4 top-11 cursor-pointer text-gray-500 hover:text-cyan-600 transition-colors" onclick="toggleConfirmarNuevaPassword()"></i>
                    <div id="confirmar-error" class="hidden text-sm text-red-600 mt-2 flex items-center">
                        <i class="fas fa-exclamation-circle mr-1"></i>
                        Las contraseñas no coinciden.
                    </div>
                </div>
                
                <button type="submit" 
                    class="btn-dental w-full py-3 px-4 text-white font-bold rounded-xl shadow-lg">
                    <i class="fas fa-save mr-2"></i>
                    Cambiar Contraseña
                </button>
            </form>
            
            <div id="alert-cambiar" class="hidden mt-4 text-center p-3 rounded-xl"></div>
            
            <div class="text-center mt-6">
                <button onclick="ocultarCambio()" class="text-sm font-semibold dental-accent hover:text-blue-800 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Cancelar
                </button>
            </div>
        </div>
    </div>

<script>
    // --- VARIABLES GLOBALES ---
    let emailRecuperacion = ""; // Para el flujo de recuperación
    // MODIFICACIÓN: emailVerificado y tokenRegistro ya no son necesarios globalmente
    let emailEnVerificacion = ""; // Guarda el email entre el registro y la verificación

    // --- FUNCIONES DE MODALES (Mantenidas) ---
    function mostrarRegistro() { document.getElementById("registro-modal").style.display = "flex"; }
    function ocultarRegistro() { document.getElementById("registro-modal").style.display = "none"; }
    function mostrarRecuperacion() { document.getElementById("recuperacion-modal").style.display = "flex"; }
    function ocultarRecuperacion() { document.getElementById("recuperacion-modal").style.display = "none"; }
    function mostrarVerificacion() { document.getElementById("verificar-modal").style.display = "flex"; }
    function ocultarVerificacion() { document.getElementById("verificar-modal").style.display = "none"; }
    function mostrarVerificacionEmail() { document.getElementById("verificar-email-modal").style.display = "flex"; }
    function ocultarVerificacionEmail() { document.getElementById("verificar-email-modal").style.display = "none"; }
    function mostrarCambio() { document.getElementById("cambiar-modal").style.display = "flex"; }
    function ocultarCambio() { document.getElementById("cambiar-modal").style.display = "none"; }


    // --- LÓGICA DE RECUPERACIÓN DE CONTRASEÑA (Mantenida como simulación) ---
    // (Esta es tu lógica original, la dejamos intacta)
    async function enviarCodigoRecuperacion(event) {
        event.preventDefault();
        const email = document.getElementById("recuperacion-email").value;
        const errorDiv = document.getElementById("recuperacion-email-error");

        if (!email) {
            errorDiv.classList.remove("hidden");
            return false;
        }
        errorDiv.classList.add("hidden");
        
        mostrarAlerta("success", "Enviando código...", "alert-recuperacion");

        try {
            // RUTA CORREGIDA: Subir dos niveles
            const response = await fetch('../../api/send-recovery-code.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ email: email })
            });
            const data = await response.json();

            if (data.success) {
                emailRecuperacion = email; // Guardar email para el siguiente paso
                mostrarAlerta("success", data.message, "alert-recuperacion");
                setTimeout(() => {
                    ocultarRecuperacion();
                    mostrarVerificacion(); // Mostrar modal de 6 dígitos
                }, 1500);
            } else {
                mostrarAlerta("error", data.message, "alert-recuperacion");
            }
        } catch (error) {
            mostrarAlerta("error", "Error de conexión.", "alert-recuperacion");
        }
        return false;
    }

    async function verificarCodigo(event) {
        event.preventDefault();
        const codigo = document.getElementById("codigo").value;
        const errorDiv = document.getElementById("codigo-error");

        if (!codigo || codigo.length !== 6) {
            errorDiv.classList.remove("hidden");
            return false;
        }
        errorDiv.classList.add("hidden");
        
        mostrarAlerta("success", "Verificando...", "alert-verificar");

        try {
            // RUTA CORREGIDA: Subir dos niveles
            const response = await fetch('../../api/verify-recovery-code.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ email: emailRecuperacion, code: codigo })
            });
            const data = await response.json();

            if (data.success) {
                mostrarAlerta("success", "Código verificado.", "alert-verificar");
                setTimeout(() => {
                    ocultarVerificacion();
                    mostrarCambio(); // Mostrar modal para nueva contraseña
                }, 1500);
            } else {
                mostrarAlerta("error", data.message, "alert-verificar");
            }
        } catch (error) {
            mostrarAlerta("error", "Error de conexión.", "alert-verificar");
        }
        return false;
    }

    async function cambiarContrasena(event) {
        event.preventDefault();
        const nueva = document.getElementById("nueva-contrasena").value;
        const confirmar = document.getElementById("confirmar-nueva").value;
        let valido = true;
        
        if (nueva.length < 6) {
            document.getElementById("nueva-error").classList.remove("hidden");
            valido = false;
        } else {
            document.getElementById("nueva-error").classList.add("hidden");
        }
        
        if (nueva !== confirmar) {
            document.getElementById("confirmar-error").classList.remove("hidden");
            valido = false;
        } else {
            document.getElementById("confirmar-error").classList.add("hidden");
        }
        
        if (!valido) return false;
        
        mostrarAlerta("success", "Actualizando contraseña...", "alert-cambiar");

        try {
            // RUTA CORREGIDA: Subir dos niveles
            const response = await fetch('../../api/change-password.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ new_password: nueva }) // El email ya está en la sesión del backend
            });
            const data = await response.json();

            if (data.success) {
                mostrarAlerta("success", "Contraseña cambiada exitosamente.", "alert-cambiar");
                setTimeout(() => {
                    ocultarCambio();
                    // Opcional: mostrar éxito en el login
                    mostrarAlerta("success", "Contraseña actualizada. Inicia sesión.", "alert-login");
                }, 2000);
            } else {
                mostrarAlerta("error", data.message, "alert-cambiar");
            }
        } catch (error) {
            mostrarAlerta("error", "Error de conexión.", "alert-cambiar");
        }
        return false;
    }


    // --- LÓGICA DE REGISTRO (MODIFICADA SEGÚN TU NUEVO FLUJO) ---
    async function validarRegistro(event) {
        event.preventDefault(); 
        
        // 1. Recolectar datos del formulario
        const nombres = document.getElementById("nombres").value.trim();
        const apellidos = document.getElementById("apellidos").value.trim();
        const email = document.getElementById("registro-email").value;
        const telefono = document.getElementById("registro-telefono").value;
        const password = document.getElementById("registro-password").value;
        const confirmarPassword = document.getElementById("confirmar-password").value;
        let valid = true;

        // 2. Validaciones locales
        const regexNombre = /^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]+$/;
        if (!regexNombre.test(nombres) || nombres.length < 2) {
            document.getElementById("nombres-error").classList.remove("hidden"); valid = false;
        } else { document.getElementById("nombres-error").classList.add("hidden"); }
        
        if (!regexNombre.test(apellidos) || apellidos.length < 2) {
            document.getElementById("apellidos-error").classList.remove("hidden"); valid = false;
        } else { document.getElementById("apellidos-error").classList.add("hidden"); }
    
        const regexEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!regexEmail.test(email)) {
            document.getElementById("registro-email-error").classList.remove("hidden"); valid = false;
        } else { document.getElementById("registro-email-error").classList.add("hidden"); }
        
        const regexTelefono = /^[0-9]{10}$/;
        if (!regexTelefono.test(telefono)) {
            document.getElementById("registro-telefono-error").classList.remove("hidden"); valid = false;
        } else { document.getElementById("registro-telefono-error").classList.add("hidden"); }
        
        if (password.length < 6) {
            document.getElementById("registro-password-error").classList.remove("hidden"); valid = false;
        } else { document.getElementById("registro-password-error").classList.add("hidden"); }
        
        if (password !== confirmarPassword) {
            document.getElementById("confirmar-password-error").classList.remove("hidden"); valid = false;
        } else { document.getElementById("confirmar-password-error").classList.add("hidden"); }

        if (!valid) return false;

        // 3. Llamar a la API de Registro (que ahora crea usuario 'Pendiente' y envía el correo)
        mostrarAlerta("success", "Procesando registro y enviando código...", "alert-registro");
        
        try {
            // RUTA CORREGIDA: Subir dos niveles (desde MODULO 1/HTML/ -> api/)
            const response = await fetch('../../api/register.php', { 
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ 
                    nombres: nombres,
                    apellidos: apellidos,
                    email: email, 
                    telefono: telefono, 
                    password: password 
                })
            });

            const data = await response.json();

            if (data.success) {
                // ÉXITO: Usuario creado como 'Pendiente' y correo enviado.
                emailEnVerificacion = data.email; // Guardar email globalmente
                
                ocultarRegistro();
                document.getElementById("email-a-verificar").innerText = emailEnVerificacion;
                mostrarVerificacionEmail(); // <-- MUESTRA EL MODAL DE CÓDIGO
                
                document.getElementById("registro-formulario").reset();

            } else {
                // Fracaso (Ej. Correo ya existe o error de DB)
                mostrarAlerta("error", data.message, "alert-registro");
            }
        } catch (error) {
            mostrarAlerta("error", "Error de conexión. Intenta de nuevo.", "alert-registro");
            console.error("Error en validarRegistro:", error);
        }
        
        return false;
    }

    // --- LÓGICA DE VERIFICACIÓN (MODIFICADA) ---
    async function verificarCodigoEmail(event) {
        event.preventDefault();
        const codigoInput = document.getElementById("codigo-email");
        const codigo = codigoInput.value;
        const email = emailEnVerificacion; // Usar el email guardado
        
        if (!codigo || codigo.length !== 6) {
             mostrarAlerta("error", "El código debe tener 6 dígitos.", "alert-verificar-email");
             return false;
        }

        mostrarAlerta("success", "Verificando código...", "alert-verificar-email");

        try {
            // RUTA CORREGIDA: Subir dos niveles
            const response = await fetch('../../api/verify-code.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ email: email, code: codigo })
            });
            
            const data = await response.json();

            if (data.success) {
                // Éxito: El código fue correcto y la cuenta fue activada
                ocultarVerificacionEmail();
                // Mostrar mensaje de éxito en la pantalla de LOGIN
                mostrarAlerta("success", "¡Cuenta activada! Ya puedes iniciar sesión.", "alert-login");
            } else {
                // Fracaso (Código incorrecto o expirado)
                mostrarAlerta("error", data.message, "alert-verificar-email");
            }
        } catch (error) {
            mostrarAlerta("error", "Error de conexión con el servidor.", "alert-verificar-email");
        }
        
        return false;
    }

    // --- LÓGICA DE LOGIN (MODIFICADA) ---
    async function validarLogin(event) {
        event.preventDefault();
        const email = document.getElementById("email").value;
        const password = document.getElementById("password").value;

        if (!email || !password) {
            if (!email) {
                document.getElementById("email-error").classList.remove("hidden");
            } else { document.getElementById("email-error").classList.add("hidden"); }
            if (!password) {
                document.getElementById("password-error").classList.remove("hidden");
            } else { document.getElementById("password-error").classList.add("hidden"); }
            return false;
        }

        document.getElementById("email-error").classList.add("hidden");
    document.getElementById("password-error").classList.add("hidden");

    try {
        // 1. (Ruta Corregida) Llama a la API de login
        const response = await fetch('../../api/login.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email, password })
        });

        const data = await response.json();

        if (data.success) {
            mostrarAlerta("success", data.message, "alert-login");
            
            setTimeout(() => {
                // 2. (Lógica de Redirección Corregida)
                if (data.user.rol === 'Odontologo') {
                    // Odontólogo va a miperfil.php (en el mismo directorio)
                    window.location.href = '../../MODULO3/HTML/dashboard.php'; 
                } else {
                    // Paciente va al Módulo 2
                    // Esta es la ruta correcta, no uses file:///
                    window.location.href = '../../MODULO2/HTML/dashboardclient.php';
                }
            }, 1500);
            
        } else {
            // Muestra errores (Ej. "Cuenta Pendiente", "Correo incorrecto")
            mostrarAlerta("error", data.message, "alert-login");
        }
    } catch (error) {
        mostrarAlerta("error", "Error de conexión. Intenta de nuevo.", "alert-login");
    }
    
    return false;
    }

    // --- FUNCIONES DE AYUDA (TOGGLES - MANTENIDAS) ---
    function togglePassword() {
        const passwordField = document.getElementById("password");
        const icon = document.getElementById("toggle-password");
        if (passwordField.type === "password") {
            passwordField.type = "text";
            icon.classList.remove("fa-eye"); icon.classList.add("fa-eye-slash");
        } else {
            passwordField.type = "password";
            icon.classList.remove("fa-eye-slash"); icon.classList.add("fa-eye");
        }
    }
    function togglePasswordRegistro() {
        const passwordField = document.getElementById("registro-password");
        const icon = document.getElementById("toggle-registro-password");
        if (passwordField.type === "password") {
            passwordField.type = "text";
            icon.classList.remove("fa-eye"); icon.classList.add("fa-eye-slash");
        } else {
            passwordField.type = "password";
            icon.classList.remove("fa-eye-slash"); icon.classList.add("fa-eye");
        }
    }
    function toggleConfirmarPassword() {
        const passwordField = document.getElementById("confirmar-password");
        const icon = document.getElementById("toggle-confirmar-password");
        if (passwordField.type === "password") {
            passwordField.type = "text";
            icon.classList.remove("fa-eye"); icon.classList.add("fa-eye-slash");
        } else {
            passwordField.type = "password";
            icon.classList.remove("fa-eye-slash"); icon.classList.add("fa-eye");
        }
    }
    function toggleNuevaPassword() {
        const passwordField = document.getElementById("nueva-contrasena");
        const icon = document.getElementById("toggle-nueva-password");
        if (passwordField.type === "password") {
            passwordField.type = "text";
            icon.classList.remove("fa-eye"); icon.classList.add("fa-eye-slash");
        } else {
            passwordField.type = "password";
            icon.classList.remove("fa-eye-slash"); icon.classList.add("fa-eye");
        }
    }
    function toggleConfirmarNuevaPassword() {
        const passwordField = document.getElementById("confirmar-nueva");
        const icon = document.getElementById("toggle-confirmar-nueva-password");
        if (passwordField.type === "password") {
            passwordField.type = "text";
            icon.classList.remove("fa-eye"); icon.classList.add("fa-eye-slash");
        } else {
            passwordField.type = "password";
            icon.classList.remove("fa-eye-slash"); icon.classList.add("fa-eye");
        }
    }
    
    // --- FUNCIONES ELIMINADAS (Ya no se usan) ---
    // function iniciarVerificacionEmail() { ... }
    // function bloquearCampoEmail() { ... }
    // function resetVerificacionEmail() { ... }
    // function desbloquearCampoEmail() { ... }


    // --- FUNCIÓN DE ALERTA (MANTENIDA) ---
    function mostrarAlerta(tipo, mensaje, ubicacion) {
        const alertDiv = document.getElementById(ubicacion);
        if (!alertDiv) {
            console.error("No se encontró el div de alerta:", ubicacion);
            return;
        }
        alertDiv.className = "mt-4 p-4 rounded-xl text-center font-semibold shadow-lg flex items-center justify-center";
        
        if (tipo === "error") {
            alertDiv.classList.add("bg-red-100", "text-red-700", "border-2", "border-red-300");
            alertDiv.innerHTML = `<i class="fas fa-exclamation-circle mr-2"></i>${mensaje}`;
        } else {
            alertDiv.classList.add("bg-green-100", "text-green-700", "border-2", "border-green-300");
            alertDiv.innerHTML = `<i class="fas fa-check-circle mr-2"></i>${mensaje}`;
        }
        
        alertDiv.classList.remove("hidden");
        
        setTimeout(() => {
            alertDiv.classList.add("hidden");
        }, 3000);
    }
    
    // Mostrar alerta de logout si vienes de cerrar sesión
    <?php if (!empty($mensaje)): ?>
        document.addEventListener('DOMContentLoaded', (event) => {
            mostrarAlerta("success", "<?php echo $mensaje; ?>", "alert-login");
        });
    <?php endif; ?>

</script>
</body>
</html>


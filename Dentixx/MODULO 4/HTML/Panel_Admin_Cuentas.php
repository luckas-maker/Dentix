<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Panel de Administración - Agenda Dental</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    body { box-sizing: border-box; }
    .table-row { transition: background-color 0.2s ease, transform 0.2s ease; }
    .table-row:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
    .highlight-red { color: #dc2626; font-weight: bold; }
    .highlight-yellow { background-color: #fef9c3; }
    .loading { opacity: 0.6; pointer-events: none; }
  </style>
</head>

<body class="h-full bg-gradient-to-br from-purple-50 to-indigo-100 font-sans">
  <div class="min-h-full p-6">
    <!-- Header -->
    <div class="max-w-7xl mx-auto mb-8">
      <div class="bg-white rounded-2xl shadow-lg p-6 border border-purple-100 flex justify-between items-center">
        <div>
          <h1 class="text-3xl font-bold text-gray-800 mb-2">🦷 Panel de Administración</h1>
          <p class="text-gray-600">Gestione el estado y asistencia de los pacientes</p>
        </div>
        <div class="text-right">
          <div class="text-sm text-gray-500">Fecha actual</div>
          <div id="currentDate" class="text-lg font-semibold text-purple-600"></div>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto">
      <div class="bg-white rounded-2xl shadow-lg p-6 border border-purple-100 overflow-x-auto">
        <div class="flex justify-between items-center mb-4">
          <h2 class="text-xl font-bold text-gray-800">📋 Lista de Pacientes</h2>
          <button onclick="cargarPacientes()" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 flex items-center">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            Actualizar
          </button>
        </div>
        <table class="min-w-full text-sm text-left text-gray-700">
          <thead class="bg-purple-100 text-purple-800 uppercase text-xs">
            <tr>
              <th class="px-4 py-2">ID</th>
              <th class="px-4 py-2">Código</th>
              <th class="px-4 py-2">Nombre Completo</th>
              <th class="px-4 py-2">Correo</th>
              <th class="px-4 py-2">Teléfono</th>
              <th class="px-4 py-2">Última Cita</th>
              <th class="px-4 py-2">Motivo</th>
              <th class="px-4 py-2">Faltas</th>
              <th class="px-4 py-2">Estado</th>
              <th class="px-4 py-2 text-center">Acciones</th>
            </tr>
          </thead>
          <tbody id="patientsTable">
            <tr>
              <td colspan="10" class="px-4 py-8 text-center text-gray-500">
                <div class="flex justify-center">
                  <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600"></div>
                </div>
                <p class="mt-2">Cargando pacientes...</p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

// Scripts
<script>
    let pacientes = [];
    let dropdownAbierto = null;

    // Fecha actual
    document.getElementById("currentDate").textContent = new Date().toLocaleDateString("es-ES", { 
        weekday: "long", 
        year: "numeric", 
        month: "long", 
        day: "numeric" 
    });

    // Cerrar dropdown al hacer clic fuera
    document.addEventListener('click', function(event) {
        if (dropdownAbierto) {
            const dropdown = document.getElementById(dropdownAbierto);
            const button = document.querySelector(`button[onclick="toggleDropdown(${dropdownAbierto.split('-')[1]})"]`);
            
            // Si el clic NO fue en el dropdown ni en el botón que lo abre, cerrarlo
            if (!dropdown.contains(event.target) && !button.contains(event.target)) {
                dropdown.classList.add('hidden');
                dropdownAbierto = null;
            }
        }
    });

    // Cerrar dropdown al presionar ESC
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && dropdownAbierto) {
            const dropdown = document.getElementById(dropdownAbierto);
            dropdown.classList.add('hidden');
            dropdownAbierto = null;
        }
    });

    // Toggle dropdown mejorado
    function toggleDropdown(id, event) {
        event.stopPropagation(); // Prevenir que el clic se propague
        
        const dropdownId = `dropdown-${id}`;
        const dropdown = document.getElementById(dropdownId);
        
        // Cerrar dropdown anterior si existe
        if (dropdownAbierto && dropdownAbierto !== dropdownId) {
            const prevDropdown = document.getElementById(dropdownAbierto);
            if (prevDropdown) prevDropdown.classList.add('hidden');
        }
        
        // Toggle dropdown actual
        dropdown.classList.toggle('hidden');
        dropdownAbierto = dropdown.classList.contains('hidden') ? null : dropdownId;
    }

    // Cargar pacientes desde la base de datos
    async function cargarPacientes() {
        const tbody = document.getElementById("patientsTable");
        tbody.innerHTML = `
            <tr>
                <td colspan="10" class="px-4 py-8 text-center text-gray-500">
                    <div class="flex justify-center">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600"></div>
                    </div>
                    <p class="mt-2">Cargando pacientes...</p>
                </td>
            </tr>
        `;

        try {
            const response = await fetch('obtener_pacientes.php');
            
            // Verificar si la respuesta es JSON válido
            const text = await response.text();
            let data;
            
            try {
                data = JSON.parse(text);
            } catch (e) {
                console.error('Respuesta no JSON:', text);
                throw new Error('El servidor respondió con formato incorrecto');
            }
            
            if (data.success) {
                pacientes = data.pacientes;
                renderTable();
                checkWarnings();
            } else {
                throw new Error(data.message);
            }
        } catch (error) {
            console.error('Error:', error);
            tbody.innerHTML = `
                <tr>
                    <td colspan="10" class="px-4 py-8 text-center text-red-500">
                        ❌ Error al cargar pacientes: ${error.message}
                    </td>
                </tr>
            `;
        }
    }

    // Renderizar tabla
    function renderTable() {
        const tbody = document.getElementById("patientsTable");
        tbody.innerHTML = "";

        if (pacientes.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="10" class="px-4 py-8 text-center text-gray-500">
                        No hay pacientes registrados
                    </td>
                </tr>
            `;
            return;
        }

        pacientes.forEach(p => {
            const tr = document.createElement("tr");
            tr.className = "table-row border-b";
            tr.id = `paciente-${p.id_usuario}`;

            // Colores condicionales
            if (p.estado_cuenta === "Bloqueado") tr.classList.add("highlight-yellow");
            const faltasClass = p.faltas_consecutivas >= 3 ? "highlight-red" : "";

            tr.innerHTML = `
                <td class="px-4 py-2 font-medium text-gray-800">${p.id_usuario}</td>
                <td class="px-4 py-2 font-mono text-sm">${p.codigo_paciente || 'N/A'}</td>
                <td class="px-4 py-2">${p.nombre} ${p.apellidos}</td>
                <td class="px-4 py-2">${p.correo}</td>
                <td class="px-4 py-2">${p.telefono || 'N/A'}</td>
                <td class="px-4 py-2">${p.ultima_cita_fecha || 'Sin citas'}</td>
                <td class="px-4 py-2">${p.ultima_cita_motivo || 'N/A'}</td>
                <td class="px-4 py-2 ${faltasClass}">${p.faltas_consecutivas}</td>
                <td class="px-4 py-2 font-semibold">
                    <span class="px-2 py-1 rounded-full text-xs ${
                        p.estado_cuenta === 'Activo' ? 'bg-green-100 text-green-800' :
                        p.estado_cuenta === 'Bloqueado' ? 'bg-yellow-100 text-yellow-800' :
                        'bg-gray-100 text-gray-800'
                    }">
                        ${p.estado_cuenta}
                    </span>
                </td>
                <td class="px-4 py-2 text-center">
                    <div class="relative inline-block text-left">
                        <button onclick="toggleDropdown(${p.id_usuario}, event)" class="px-3 py-1 bg-purple-600 text-white rounded-lg hover:bg-purple-700 text-xs flex items-center">
                            Acciones
                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div id="dropdown-${p.id_usuario}" class="hidden absolute right-0 mt-1 w-48 bg-white rounded-lg shadow-xl border border-gray-200 z-50">
                            <button onclick="event.stopPropagation(); registrarAsistencia(${p.id_usuario})" class="block w-full text-left px-4 py-2 text-sm text-green-700 hover:bg-green-50 rounded-t-lg border-b border-gray-100">
                                ✅ Registrar Asistencia
                            </button>
                            <button onclick="event.stopPropagation(); bloquearPaciente(${p.id_usuario})" class="block w-full text-left px-4 py-2 text-sm text-yellow-700 hover:bg-yellow-50 border-b border-gray-100">
                                ⚠️ Bloquear Cuenta
                            </button>
                            <button onclick="event.stopPropagation(); desbloquearPaciente(${p.id_usuario})" class="block w-full text-left px-4 py-2 text-sm text-blue-700 hover:bg-blue-50 border-b border-gray-100">
                                🔓 Desbloquear Cuenta
                            </button>
                            <button onclick="event.stopPropagation(); eliminarPaciente(${p.id_usuario})" class="block w-full text-left px-4 py-2 text-sm text-red-700 hover:bg-red-50 rounded-b-lg">
                                🗑️ Eliminar Paciente
                            </button>
                        </div>
                    </div>
                </td>
            `;
            tbody.appendChild(tr);
        });
    }

    // Verificar advertencias de faltas
    function checkWarnings() {
        const faltantes = pacientes.filter(p => p.faltas_consecutivas >= 3);
        if (faltantes.length > 0) {
            const nombres = faltantes.map(p => `${p.nombre} ${p.apellidos}`).join(", ");
            showModal(`⚠️ Advertencia`, `Los siguientes pacientes tienen 3 o más faltas consecutivas:<br><strong>${nombres}</strong>`);
        }
    }

    // Funciones de acciones
    async function ejecutarAccion(accion, id_usuario) {
        // Cerrar dropdown antes de ejecutar la acción
        if (dropdownAbierto) {
            const dropdown = document.getElementById(dropdownAbierto);
            dropdown.classList.add('hidden');
            dropdownAbierto = null;
        }

        const paciente = pacientes.find(p => p.id_usuario === id_usuario);
        if (!paciente) return;

        try {
            const response = await fetch('acciones_admin.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    accion: accion,
                    id_usuario: id_usuario
                })
            });

            const text = await response.text();
            let data;
            
            try {
                data = JSON.parse(text);
            } catch (e) {
                console.error('Respuesta no JSON:', text);
                throw new Error('El servidor respondió con formato incorrecto');
            }
            
            if (data.success) {
                alert(data.message);
                await cargarPacientes(); // Recargar datos
            } else {
                alert('Error: ' + data.message);
            }
        } catch (error) {
            alert('Error de conexión: ' + error.message);
        }
    }

    // Registrar asistencia
    function registrarAsistencia(id) {
        if (!confirm('¿Está seguro de marcar la asistencia de este paciente?')) return;
        ejecutarAccion('registrar_asistencia', id);
    }

    // Bloquear paciente
    function bloquearPaciente(id) {
        if (!confirm('¿Está seguro de bloquear a este paciente?')) return;
        ejecutarAccion('bloquear_paciente', id);
    }

    // Desbloquear paciente
    function desbloquearPaciente(id) {
        if (!confirm('¿Está seguro de desbloquear a este paciente?')) return;
        ejecutarAccion('desbloquear_paciente', id);
    }

    // Eliminar paciente
    function eliminarPaciente(id) {
        const paciente = pacientes.find(p => p.id_usuario === id);
        if (!paciente) return;
        
        if (!confirm(`¿Está seguro de eliminar a ${paciente.nombre} ${paciente.apellidos}? Esta acción no se puede deshacer.`)) return;
        ejecutarAccion('eliminar_paciente', id);
    }

    // Modal de advertencia
    function showModal(title, message) {
        const modal = document.createElement("div");
        modal.className = "fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50";
        modal.innerHTML = `
            <div class="bg-white rounded-2xl shadow-2xl p-6 max-w-md w-full mx-4">
                <h3 class="text-lg font-bold text-gray-800 mb-4">${title}</h3>
                <p class="text-gray-700 mb-6">${message}</p>
                <div class="text-right">
                    <button onclick="this.closest('.fixed').remove()" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">Cerrar</button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    }

    // Inicialización
    document.addEventListener('DOMContentLoaded', function() {
        cargarPacientes();
    });
</script>
</body>
</html>
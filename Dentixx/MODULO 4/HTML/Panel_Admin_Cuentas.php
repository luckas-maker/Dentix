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
    .search-highlight { background-color: #fff3cd; font-weight: bold; }
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
        <!-- Barra de búsqueda y botones -->
        <div class="flex justify-between items-center mb-6">
          <div class="flex-1 max-w-md">
            <div class="relative">
              <input 
                type="text" 
                id="searchInput" 
                placeholder="Buscar por código, nombre, apellidos, correo..." 
                class="w-full px-4 py-2 pl-10 pr-4 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
              >
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
              </div>
              <div id="searchClear" class="absolute inset-y-0 right-0 pr-3 flex items-center cursor-pointer hidden">
                <svg class="h-5 w-5 text-gray-400 hover:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
              </div>
            </div>
            <div id="searchCount" class="text-xs text-gray-500 mt-1 hidden">
              Mostrando <span id="filteredCount">0</span> de <span id="totalCount">0</span> pacientes
            </div>
          </div>
          
          <div class="flex space-x-3 ml-4">
            <!-- Botón de Sincronizar Asistencias -->
            <button onclick="sincronizarAsistencias()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 flex items-center">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
              </svg>
              Sincronizar Asistencias
            </button>
            
            <button onclick="mostrarAdvertencias()" class="px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 flex items-center">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.35 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
              </svg>
              Ver Advertencias
            </button>
            <button onclick="cargarPacientes()" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 flex items-center">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
              </svg>
              Actualizar
            </button>
          </div>
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

  <!-- Scripts -->
  <script>
    let pacientes = [];
    let dropdownAbierto = null;
    let pacientesFiltrados = [];

    // Fecha actual
    document.getElementById("currentDate").textContent = new Date().toLocaleDateString("es-ES", { 
        weekday: "long", 
        year: "numeric", 
        month: "long", 
        day: "numeric" 
    });

    // Elementos del buscador
    const searchInput = document.getElementById('searchInput');
    const searchClear = document.getElementById('searchClear');
    const searchCount = document.getElementById('searchCount');
    const filteredCount = document.getElementById('filteredCount');
    const totalCount = document.getElementById('totalCount');

    // Eventos del buscador
    searchInput.addEventListener('input', function() {
        filtrarPacientes(this.value);
        searchClear.classList.toggle('hidden', this.value === '');
    });

    searchClear.addEventListener('click', function() {
        searchInput.value = '';
        filtrarPacientes('');
        searchClear.classList.add('hidden');
        searchInput.focus();
    });

    // Cerrar dropdown al hacer clic fuera
    document.addEventListener('click', function(event) {
        if (dropdownAbierto) {
            const dropdown = document.getElementById(dropdownAbierto);
            const button = document.querySelector(`button[onclick="toggleDropdown(${dropdownAbierto.split('-')[1]}, event)"]`);
            
            if (dropdown && button) {
                if (!dropdown.contains(event.target) && !button.contains(event.target)) {
                    dropdown.classList.add('hidden');
                    dropdownAbierto = null;
                }
            }
        }
    });

    // Cerrar dropdown al presionar ESC
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && dropdownAbierto) {
            const dropdown = document.getElementById(dropdownAbierto);
            if (dropdown) {
                dropdown.classList.add('hidden');
                dropdownAbierto = null;
            }
        }
    });

    // Toggle dropdown mejorado
    function toggleDropdown(id, event) {
        event.stopPropagation();
        
        const dropdownId = `dropdown-${id}`;
        const dropdown = document.getElementById(dropdownId);
        
        // Cerrar dropdown anterior si existe
        if (dropdownAbierto && dropdownAbierto !== dropdownId) {
            const prevDropdown = document.getElementById(dropdownAbierto);
            if (prevDropdown) prevDropdown.classList.add('hidden');
        }
        
        // Toggle dropdown actual
        if (dropdown) {
            dropdown.classList.toggle('hidden');
            dropdownAbierto = dropdown.classList.contains('hidden') ? null : dropdownId;
        }
    }

    // Filtrar pacientes en tiempo real
    function filtrarPacientes(termino) {
        if (!termino.trim()) {
            pacientesFiltrados = [...pacientes];
            renderTable();
            searchCount.classList.add('hidden');
            return;
        }

        const terminoLower = termino.toLowerCase();
        pacientesFiltrados = pacientes.filter(paciente => {
            const textoBusqueda = `
                ${paciente.codigo_paciente || ''}
                ${paciente.nombre} 
                ${paciente.apellidos}
                ${paciente.correo}
                ${paciente.telefono || ''}
            `.toLowerCase();

            return textoBusqueda.includes(terminoLower);
        });

        renderTable();
        
        // Actualizar contador
        filteredCount.textContent = pacientesFiltrados.length;
        totalCount.textContent = pacientes.length;
        searchCount.classList.remove('hidden');
    }

    // Resaltar texto en los resultados de búsqueda
    function resaltarTexto(texto, termino) {
        if (!termino.trim()) return texto;
        
        const regex = new RegExp(`(${termino.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
        return texto.replace(regex, '<span class="search-highlight">$1</span>');
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
                pacientesFiltrados = [...pacientes];
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

    // Función para sincronizar asistencias
    async function sincronizarAsistencias() {
        try {
            // Mostrar indicador de carga
            const botonSincronizar = event.target;
            const textoOriginal = botonSincronizar.innerHTML;
            botonSincronizar.innerHTML = `
                <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
                Sincronizando...
            `;
            botonSincronizar.disabled = true;

            const response = await fetch('sincronizar_asistencias.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                }
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
                // Mostrar mensaje de éxito
                showSuccessMessage('✅ ' + data.message);
                
                // Recargar los datos de pacientes
                await cargarPacientes();
            } else {
                throw new Error(data.message);
            }
            
        } catch (error) {
            console.error('Error:', error);
            showErrorMessage('❌ Error al sincronizar: ' + error.message);
        } finally {
            // Restaurar el botón
            setTimeout(() => {
                botonSincronizar.innerHTML = `
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Sincronizar Asistencias
                `;
                botonSincronizar.disabled = false;
            }, 1000);
        }
    }

    // Renderizar tabla
    function renderTable() {
        const tbody = document.getElementById("patientsTable");
        const terminoBusqueda = searchInput.value.toLowerCase();
        tbody.innerHTML = "";

        const pacientesARenderizar = pacientesFiltrados.length > 0 ? pacientesFiltrados : pacientes;

        if (pacientesARenderizar.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="10" class="px-4 py-8 text-center text-gray-500">
                        ${terminoBusqueda ? 'No se encontraron pacientes que coincidan con la búsqueda' : 'No hay pacientes registrados'}
                    </td>
                </tr>
            `;
            return;
        }

        pacientesARenderizar.forEach(p => {
            const tr = document.createElement("tr");
            tr.className = "table-row border-b";
            tr.id = `paciente-${p.id_usuario}`;

            // Colores condicionales
            if (p.estado_cuenta === "Bloqueado") tr.classList.add("highlight-yellow");
            const faltasClass = p.faltas_consecutivas >= 3 ? "highlight-red" : "";

            // Resaltar texto si hay búsqueda
            const nombreCompleto = terminoBusqueda ? 
                resaltarTexto(`${p.nombre} ${p.apellidos}`, terminoBusqueda) : 
                `${p.nombre} ${p.apellidos}`;
            
            const codigoPaciente = terminoBusqueda && p.codigo_paciente ? 
                resaltarTexto(p.codigo_paciente, terminoBusqueda) : 
                (p.codigo_paciente || 'N/A');
            
            const correoPaciente = terminoBusqueda ? 
                resaltarTexto(p.correo, terminoBusqueda) : 
                p.correo;

            tr.innerHTML = `
                <td class="px-4 py-2 font-medium text-gray-800">${p.id_usuario}</td>
                <td class="px-4 py-2 font-mono text-sm">${codigoPaciente}</td>
                <td class="px-4 py-2">${nombreCompleto}</td>
                <td class="px-4 py-2">${correoPaciente}</td>
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

    // Mostrar advertencias de pacientes con 3+ faltas
    function mostrarAdvertencias() {
        const pacientesConFaltas = pacientes.filter(p => p.faltas_consecutivas >= 3);
        
        if (pacientesConFaltas.length === 0) {
            showModal('✅ Sin Advertencias', 'No hay pacientes con 3 o más faltas consecutivas.');
            return;
        }

        const listaPacientes = pacientesConFaltas.map(p => 
            `• <strong>${p.nombre} ${p.apellidos}</strong> (${p.codigo_paciente}) - ${p.faltas_consecutivas} faltas`
        ).join('<br>');

        showModal(
            '⚠️ Pacientes con 3+ Faltas Consecutivas', 
            `Los siguientes pacientes tienen 3 o más faltas consecutivas:<br><br>${listaPacientes}`
        );
    }

    // Verificar advertencias de faltas al cargar
    function checkWarnings() {
        const pacientesConFaltas = pacientes.filter(p => p.faltas_consecutivas >= 3);
        if (pacientesConFaltas.length > 0) {
            const nombres = pacientesConFaltas.map(p => `${p.nombre} ${p.apellidos}`).join(", ");
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
                <div class="text-gray-700 mb-6 max-h-96 overflow-y-auto">${message}</div>
                <div class="text-right">
                    <button onclick="this.closest('.fixed').remove()" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">Cerrar</button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    }

    // Función para mostrar mensajes de éxito
    function showSuccessMessage(message) {
        const toast = document.createElement("div");
        toast.className = "fixed top-4 right-4 bg-green-600 text-white px-6 py-3 rounded-lg shadow-lg z-50 transform translate-x-full transition-transform duration-300";
        toast.textContent = message;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.classList.remove("translate-x-full");
        }, 100);
        
        setTimeout(() => {
            toast.classList.add("translate-x-full");
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    // Función para mostrar mensajes de error
    function showErrorMessage(message) {
        const toast = document.createElement("div");
        toast.className = "fixed top-4 right-4 bg-red-600 text-white px-6 py-3 rounded-lg shadow-lg z-50 transform translate-x-full transition-transform duration-300";
        toast.textContent = message;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.classList.remove("translate-x-full");
        }, 100);
        
        setTimeout(() => {
            toast.classList.add("translate-x-full");
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    // Inicialización
    document.addEventListener('DOMContentLoaded', function() {
        cargarPacientes();
    });
  </script>
</body>
</html>
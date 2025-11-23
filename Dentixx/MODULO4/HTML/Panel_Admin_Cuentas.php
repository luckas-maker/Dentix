<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: ../../MODULO1/HTML/iniciosesion.php'); exit(); }
?>
<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Administración - Dentix</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/js/all.min.js"></script>
  <style>
    body { box-sizing: border-box; }
    .table-row:hover { background-color: #f9fafb; }
    .fa-spin { animation: fa-spin 1s infinite linear; }
    @keyframes fa-spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    .custom-scrollbar::-webkit-scrollbar { width: 6px; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
  </style>
</head>
<body class="h-full bg-gray-50 flex flex-col">
    
    <main class="flex-1 p-6 w-full">
        <div class="max-w-7xl mx-auto">
            <div class="bg-white rounded-2xl shadow-lg p-6 border border-blue-100 mb-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-800 mb-2">Gestión de Pacientes</h1>
                        <p class="text-gray-600">Panel de control administrativo para administrar usuarios</p>
                    </div>
                    <div class="text-right">
                        <div class="text-sm text-gray-500">Fecha actual</div>
                        <div class="text-lg font-semibold text-blue-600 capitalize" id="currentDate"></div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm p-4 mb-6 border border-gray-100 flex justify-between items-center">
                <div class="relative w-96">
                    <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                    <input type="text" id="searchInput" placeholder="Buscar..." class="pl-10 pr-4 py-2 border rounded-lg w-full text-sm focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="flex gap-2">
                    <button onclick="mostrarAdvertencias()" class="px-4 py-2 bg-yellow-100 text-yellow-700 rounded-lg text-sm font-medium flex items-center hover:bg-yellow-200">
                        <i class="fas fa-exclamation-triangle mr-2"></i> Advertencias
                    </button>
                    <button onclick="cargarPacientes()" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium flex items-center hover:bg-blue-700">
                        <i class="fas fa-sync-alt mr-2"></i> Actualizar
                    </button>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Paciente</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Contacto</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Última Cita</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Faltas</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Estado</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="patientsTable" class="bg-white divide-y divide-gray-200 text-sm"></tbody>
                </table>
            </div>
            <div id="tableFooter" class="p-4 text-center text-gray-500 text-sm">Cargando...</div>
        </div>
    </main>

    <div id="modalAcciones" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white rounded-2xl shadow-2xl p-6 max-w-sm w-full mx-4">
            <div class="flex justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-800">Gestionar</h3>
                <button onclick="cerrarModalAcciones()" class="text-gray-400 hover:text-gray-600 text-2xl">×</button>
            </div>
            <p class="text-gray-600 mb-6 text-center font-medium text-lg" id="modalPatientName"></p>
            
            <div class="space-y-3">
                <button onclick="verCitasConfirmadas()" class="w-full px-4 py-3 bg-blue-50 text-blue-700 border border-blue-100 rounded-xl hover:bg-blue-100 transition-colors flex items-center font-medium group">
                    <div class="w-8 h-8 bg-blue-200 rounded-lg flex items-center justify-center mr-3 text-blue-700 group-hover:bg-blue-300"><i class="fas fa-calendar-check"></i></div>
                    Visualizar Citas Confirmadas
                </button>
                
                <div class="h-px bg-gray-200 my-2"></div>

                <div class="grid grid-cols-2 gap-3">
                    <button onclick="confirmarAccion('bloquear_paciente')" class="px-3 py-3 bg-white border border-gray-200 hover:border-yellow-300 hover:bg-yellow-50 text-yellow-600 rounded-xl flex flex-col items-center justify-center">
                        <i class="fas fa-lock mb-1 text-xl"></i><span class="text-xs font-bold">Bloquear</span>
                    </button>
                    <button onclick="confirmarAccion('desbloquear_paciente')" class="px-3 py-3 bg-white border border-gray-200 hover:border-blue-300 hover:bg-blue-50 text-blue-600 rounded-xl flex flex-col items-center justify-center">
                        <i class="fas fa-unlock mb-1 text-xl"></i><span class="text-xs font-bold">Desbloquear</span>
                    </button>
                </div>
                
                <button onclick="confirmarAccion('eliminar_paciente')" class="w-full py-2 text-gray-400 hover:text-red-600 text-sm font-medium flex items-center justify-center">
                    <i class="fas fa-trash-alt mr-2"></i> Eliminar Paciente
                </button>
            </div>
        </div>
    </div>

    <div id="modalCitas" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-[60]">
        <div class="bg-white rounded-2xl shadow-2xl p-6 max-w-md w-full mx-4">
            <div class="flex justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-800">Citas Confirmadas</h3>
                <button onclick="document.getElementById('modalCitas').classList.add('hidden')" class="text-gray-400 text-2xl">×</button>
            </div>
            <div id="listaCitasContainer" class="max-h-96 overflow-y-auto custom-scrollbar space-y-3">
                </div>
            <div class="mt-4 text-right">
                <button onclick="document.getElementById('modalCitas').classList.add('hidden')" class="text-sm text-gray-500 hover:text-gray-700">Cerrar</button>
            </div>
        </div>
    </div>

    <div id="confirmation-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-[70]">
        <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4 text-center">
            <i class="fas fa-question-circle text-4xl text-blue-500 mb-4"></i>
            <h3 class="text-lg font-bold text-gray-900 mb-2" id="confirm-title">Confirmar</h3>
            <p class="text-gray-600 mb-6" id="confirm-message">¿Proceder?</p>
            <div class="flex justify-center space-x-3">
                <button onclick="document.getElementById('confirmation-modal').classList.add('hidden')" class="px-4 py-2 bg-gray-200 rounded-lg">Cancelar</button>
                <button id="btn-confirm-action" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center"><span class="btn-text">Confirmar</span><span class="hidden btn-loading ml-2"><i class="fas fa-spinner fa-spin"></i></span></button>
            </div>
        </div>
    </div>
    
    <div id="alert-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-[80]">
        <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4 text-center">
            <i id="alert-icon" class="fas fa-info-circle text-4xl text-blue-500 mb-4"></i>
            <h3 id="alert-title" class="text-lg font-bold mb-2">Aviso</h3>
            <p id="alert-message" class="text-gray-600 mb-6"></p>
            <button onclick="document.getElementById('alert-modal').classList.add('hidden')" class="bg-blue-600 text-white py-2 px-6 rounded-lg">Entendido</button>
        </div>
    </div>

    <div id="modalAdvertencias" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-[60]">
        <div class="bg-white rounded-2xl shadow-2xl p-6 max-w-lg w-full mx-4">
            <div class="flex justify-between mb-4">
                <div class="flex items-center text-yellow-600"><i class="fas fa-exclamation-triangle text-2xl mr-2"></i><h3 class="text-xl font-bold">Alertas</h3></div>
                <button onclick="document.getElementById('modalAdvertencias').classList.add('hidden')" class="text-2xl">×</button>
            </div>
            <div id="advertenciasContent" class="max-h-80 overflow-y-auto custom-scrollbar space-y-3 mb-6"></div>
        </div>
    </div>

    <script>
        let pacientes = [];
        let selectedPatientId = null;
        let selectedPatientName = '';

        document.getElementById("currentDate").textContent = new Date().toLocaleDateString("es-ES", { weekday: "long", year: "numeric", month: "long", day: "numeric" });

        async function cargarPacientes() {
            const tbody = document.getElementById("patientsTable");
            tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-10 text-center text-gray-500"><i class="fas fa-spinner fa-spin text-2xl mb-2"></i><br>Cargando...</td></tr>';
            try {
                const response = await fetch('obtener_pacientes.php');
                const data = await response.json();
                if (data.success) {
                    pacientes = data.pacientes;
                    renderTable(pacientes);
                    document.getElementById("tableFooter").innerText = `${pacientes.length} pacientes registrados`;
                } else {
                    tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-red-500">Error al cargar</td></tr>';
                }
            } catch (error) { tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-red-500">Error de conexión</td></tr>'; }
        }

        function renderTable(lista) {
            const tbody = document.getElementById("patientsTable");
            tbody.innerHTML = "";
            if(lista.length === 0) { tbody.innerHTML = '<tr><td colspan="6" class="text-center py-10 text-gray-400">No se encontraron pacientes</td></tr>'; return; }

            lista.forEach(p => {
                const tr = document.createElement("tr");
                tr.className = "table-row border-b border-gray-100";
                tr.innerHTML = `
                    <td class="px-6 py-4"><div class="font-medium text-gray-900">${p.nombre} ${p.apellidos}</div><div class="text-xs text-gray-500">${p.codigo_paciente || 'N/A'}</div></td>
                    <td class="px-6 py-4 text-gray-500 text-sm"><div>${p.correo}</div><div class="text-xs">${p.telefono || ''}</div></td>
                    <td class="px-6 py-4 text-gray-500 text-sm">${p.ultima_cita_fecha || '-'}</td>
                    <td class="px-6 py-4 text-center text-sm"><span class="${p.faltas_consecutivas >= 3 ? 'text-red-600 font-bold' : ''}">${p.faltas_consecutivas}</span></td>
                    <td class="px-6 py-4 text-center"><span class="px-2 py-1 text-xs font-semibold rounded-full ${p.estado_cuenta === 'Activo' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'}">${p.estado_cuenta}</span></td>
                    <td class="px-6 py-4 text-center"><button onclick="abrirModalAcciones(${p.id_usuario}, '${p.nombre} ${p.apellidos}')" class="text-blue-600 bg-blue-50 px-3 py-1 rounded-lg hover:bg-blue-100">Gestionar</button></td>
                `;
                tbody.appendChild(tr);
            });
        }

        document.getElementById('searchInput').addEventListener('input', function(e) {
            const term = e.target.value.toLowerCase();
            renderTable(pacientes.filter(p => p.nombre.toLowerCase().includes(term) || p.apellidos.toLowerCase().includes(term) || p.correo.toLowerCase().includes(term)));
        });

        function abrirModalAcciones(id, nombre) {
            selectedPatientId = id;
            selectedPatientName = nombre;
            document.getElementById('modalPatientName').innerText = nombre;
            document.getElementById('modalAcciones').classList.remove('hidden');
            document.getElementById('modalAcciones').classList.add('flex');
        }

        function cerrarModalAcciones() {
            document.getElementById('modalAcciones').classList.add('hidden');
            document.getElementById('modalAcciones').classList.remove('flex');
        }

        // --- NUEVA LÓGICA: VISUALIZAR CITAS ---
        async function verCitasConfirmadas() {
            cerrarModalAcciones();
            const container = document.getElementById('listaCitasContainer');
            container.innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin text-blue-500"></i> Cargando...</div>';
            document.getElementById('modalCitas').classList.remove('hidden');
            document.getElementById('modalCitas').classList.add('flex');

            try {
                const response = await fetch('acciones_admin.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ accion: 'obtener_citas_usuario', id_usuario: selectedPatientId })
                });
                const data = await response.json();

                container.innerHTML = '';
                if (data.success && data.citas.length > 0) {
                    data.citas.forEach(cita => {
                        const div = document.createElement('div');
                        div.className = 'p-4 border border-gray-200 rounded-lg bg-gray-50';
                        div.innerHTML = `
                            <div class="flex justify-between items-center mb-2">
                                <span class="font-bold text-gray-800">${cita.fecha} ${cita.hora_inicio}</span>
                                <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">Confirmada</span>
                            </div>
                            <div class="text-sm text-gray-600 mb-3">${cita.tipo_servicio}</div>
                            <div class="flex gap-2">
                                <button onclick="confirmarAccionCita('marcar_asistencia_cita', ${cita.id_cita})" class="flex-1 py-1 bg-green-500 text-white rounded text-xs hover:bg-green-600">✅ Asistió</button>
                                <button onclick="confirmarAccionCita('marcar_no_asistio_cita', ${cita.id_cita})" class="flex-1 py-1 bg-red-500 text-white rounded text-xs hover:bg-red-600">❌ No Asistió</button>
                            </div>
                        `;
                        container.appendChild(div);
                    });
                } else {
                    container.innerHTML = '<div class="text-center text-gray-500 py-4">No hay citas confirmadas pendientes.</div>';
                }
            } catch (e) {
                container.innerHTML = '<div class="text-center text-red-500">Error de conexión.</div>';
            }
        }

        // --- LÓGICA DE ACCIÓN GENERAL (Bloquear/Eliminar) ---
        function confirmarAccion(accion) {
            cerrarModalAcciones();
            let title = 'Confirmar', msg = '¿Proceder?', btnClass = 'bg-blue-600';
            
            if (accion === 'bloquear_paciente') {
                title = 'Bloquear Cuenta'; msg = `¿Bloquear a ${selectedPatientName}? Se cancelarán sus citas pendientes y se le notificará por correo.`; btnClass = 'bg-yellow-600';
            } else if (accion === 'desbloquear_paciente') {
                title = 'Desbloquear Cuenta'; msg = `¿Desbloquear a ${selectedPatientName}? Se le notificará.`;
            } else if (accion === 'eliminar_paciente') {
                title = 'Eliminar Paciente'; msg = '¿Eliminar permanentemente? Irreversible.'; btnClass = 'bg-gray-800';
            }

            prepararConfirmacion(title, msg, btnClass, { accion: accion, id_usuario: selectedPatientId });
        }

        // --- LÓGICA DE ACCIÓN POR CITA ---
        function confirmarAccionCita(accion, idCita) {
            document.getElementById('modalCitas').classList.add('hidden');
            let title = '', msg = '', btnClass = '';
            if (accion === 'marcar_asistencia_cita') {
                title = 'Confirmar Asistencia'; msg = '¿Marcar que el paciente asistió?'; btnClass = 'bg-green-600';
            } else {
                title = 'Marcar Inasistencia'; msg = '¿Marcar que NO ASISTIÓ? Se enviará un correo de aviso.'; btnClass = 'bg-red-600';
            }
            prepararConfirmacion(title, msg, btnClass, { accion: accion, id_cita: idCita });
        }

        function prepararConfirmacion(title, msg, btnClass, payload) {
            document.getElementById('confirm-title').innerText = title;
            document.getElementById('confirm-message').innerText = msg;
            const btn = document.getElementById('btn-confirm-action');
            btn.className = `px-4 py-2 text-white rounded-lg flex items-center ${btnClass}`;
            
            const modal = document.getElementById('confirmation-modal');
            modal.classList.remove('hidden'); modal.classList.add('flex');

            btn.onclick = async function() {
                const thisBtn = this;
                thisBtn.disabled = true;
                thisBtn.querySelector('.btn-text').innerText = 'Procesando...';
                thisBtn.querySelector('.btn-loading').classList.remove('hidden');

                showAlert('process', 'Procesando', 'Por favor espere...');
                modal.classList.add('hidden');

                try {
                    const response = await fetch('acciones_admin.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });
                    const res = await response.json();
                    
                    document.getElementById('alert-modal').classList.add('hidden'); // Ocultar procesando
                    
                    if(res.success) {
                        showAlert('success', 'Éxito', res.message);
                        cargarPacientes();
                    } else {
                        showAlert('error', 'Error', res.message);
                    }
                } catch(e) {
                    document.getElementById('alert-modal').classList.add('hidden');
                    showAlert('error', 'Error', 'Fallo de conexión.');
                }
                thisBtn.disabled = false;
                thisBtn.querySelector('.btn-text').innerText = 'Confirmar';
                thisBtn.querySelector('.btn-loading').classList.add('hidden');
            };
        }

        // UI Helpers
        function showAlert(type, title, msg) {
            const m = document.getElementById('alert-modal');
            const icon = document.getElementById('alert-icon');
            const btn = m.querySelector('button');
            
            if (type === 'process') {
                icon.className = 'fas fa-spinner fa-spin text-4xl text-blue-500 mb-4';
                btn.classList.add('hidden');
            } else {
                icon.className = type === 'success' ? "fas fa-check-circle text-4xl text-green-500 mb-4" : "fas fa-exclamation-triangle text-4xl text-red-500 mb-4";
                btn.classList.remove('hidden');
            }
            document.getElementById('alert-title').innerText = title;
            document.getElementById('alert-message').innerText = msg;
            m.classList.remove('hidden'); m.classList.add('flex');
        }

        function mostrarAdvertencias() {
            const warnings = pacientes.filter(p => p.faltas_consecutivas >= 3);
            const container = document.getElementById('advertenciasContent');
            const modal = document.getElementById('modalAdvertencias');
            container.innerHTML = '';
            if(warnings.length > 0) {
                warnings.forEach(p => {
                    container.innerHTML += `<div class="p-3 bg-red-50 border border-red-100 rounded-lg flex justify-between"><div><p class="font-bold">${p.nombre} ${p.apellidos}</p><p class="text-xs">${p.correo}</p></div><span class="text-red-700 font-bold">${p.faltas_consecutivas} Faltas</span></div>`;
                });
            } else {
                container.innerHTML = '<p class="text-center text-gray-500">Sin advertencias</p>';
            }
            modal.classList.remove('hidden'); modal.classList.add('flex');
        }

        cargarPacientes();
    </script>
</body>
</html>
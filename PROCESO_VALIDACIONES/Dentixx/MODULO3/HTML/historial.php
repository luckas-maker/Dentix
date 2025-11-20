<?php
include '../../config/database.php';

// Función para obtener las citas anteriores junto con todos los datos
function getAppointmentHistory() {
    $pdo = getDBConnection();
    $appointments = [];
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                c.id_cita,
                c.id_paciente,
                u.nombre,
                u.apellidos,
                u.telefono,
                c.tipo_servicio as treatment,
                f.fecha,
                f.hora_inicio as time,
                c.estado_cita as status,
                c.fecha_creacion
            FROM citas c
            LEFT JOIN usuarios u ON c.id_paciente = u.id_usuario
            LEFT JOIN franjasdisponibles f ON c.id_franja = f.id_franja
            WHERE c.estado_cita IN ('Asistida', 'No Asistio')
            ORDER BY f.fecha DESC, f.hora_inicio DESC
        ");
        $stmt->execute();
        
        $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("Error getting appointment history: " . $e->getMessage());
    }
    
    return $appointments;
}

// obtener citas desde la base de datos
$dbAppointments = getAppointmentHistory();

// convertir la información en el formato para en frond end
$historyData = [];
foreach ($dbAppointments as $dbAppt) {
    $dateStr = $dbAppt['fecha'];
    $patientName = $dbAppt['nombre'] . ' ' . $dbAppt['apellidos'];
    
    // convertir el status de la cita al formato del frontend
    $status = 'completed'; // default
    if ($dbAppt['status'] === 'No Asistio') {
        $status = 'absent';
    }
    
    if (!isset($historyData[$dateStr])) {
        $historyData[$dateStr] = [];
    }
    
    $historyData[$dateStr][] = [
        'id_cita' => $dbAppt['id_cita'],
        'time' => substr($dbAppt['time'], 0, 5),
        'patient' => $patientName,
        'treatment' => $dbAppt['treatment'],
        'phone' => $dbAppt['telefono'],
        'status' => $status,
        'db_status' => $dbAppt['status']
    ];
}
?>

<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de Citas - Agenda Dental</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/js/all.min.js"></script>

    <style>
        body {
            box-sizing: border-box;
        }
        .history-item {
            transition: all 0.2s ease;
        }
        .history-item:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
    </style>
</head>
<body class="h-full bg-gradient-to-br from-purple-50 to-indigo-100 font-sans">
    <div class="min-h-full p-6">
        <!-- Header -->
        <div class="max-w-7xl mx-auto mb-8">
            <div class="bg-white rounded-2xl shadow-lg p-6 border border-purple-100">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-800 mb-2">📚 Historial de Citas</h1>
                        <p class="text-gray-600">Revise todas las citas completadas y ausentes del consultorio</p>
                    </div>
                    <div class="text-right">
                        <div class="text-sm text-gray-500">Fecha actual</div>
                        <div class="text-lg font-semibold text-blue-600" id="currentDate"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-4 gap-6">
            <!-- Aqui va el historial -->
            <div class="lg:col-span-3">
                <div class="bg-white rounded-2xl shadow-lg p-6 border border-purple-100">
                    <!-- Filtros -->
                    <div class="flex flex-wrap gap-2 mb-6">
                        <button onclick="filterHistory('all')" id="filterAll" class="px-4 py-2 bg-purple-600 text-white rounded-lg text-sm font-medium transition-colors">
                            📋 Todas las Citas
                        </button>
                        <button onclick="filterHistory('completed')" id="filterCompleted" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-300 transition-colors">
                            ✅ Solo Asistidas
                        </button>
                        <button onclick="filterHistory('absent')" id="filterAbsent" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-300 transition-colors">
                            👻 Solo No Asistieron
                        </button>
                    </div>
                    
                    <!-- Search Bar -->
                    <div class="mb-6">
                        <div class="relative">
                            <input type="text" id="searchInput" placeholder="Buscar por paciente, tratamiento o fecha (YYYY-MM-DD)..." 
                                   class="w-full px-4 py-3 pl-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                   oninput="searchHistory()">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-400">🔍</span>
                            </div>
                        </div>
                    </div>
                    
                    
                    <div id="historyContent" class="space-y-4 max-h-96 overflow-y-auto">
                        <!-- Todas las citas se muestran aqui -->
                    </div>
                </div>
            </div>

            <!-- Panel de estadisticas -->
            <div class="space-y-6">
                <!-- resumen -->
                <div class="bg-white rounded-2xl shadow-lg p-4 border border-purple-100">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">📊 Resumen Total</h3>
                    
                    <div class="space-y-3">
                        <div class="bg-green-50 p-4 rounded-lg border border-green-200">
                            <div class="text-center">
                                <div class="text-3xl font-bold text-green-600" id="completedTotal">0</div>
                                <div class="text-sm text-green-700 font-medium">Citas Asistidas</div>
                            </div>
                        </div>
                        
                        <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200">
                            <div class="text-center">
                                <div class="text-3xl font-bold text-yellow-600" id="absentTotal">0</div>
                                <div class="text-sm text-yellow-700 font-medium">No Asistieron</div>
                            </div>
                        </div>
                        
                        <div class="bg-purple-50 p-4 rounded-lg border border-purple-200">
                            <div class="text-center">
                                <div class="text-3xl font-bold text-purple-600" id="totalProcessed">0</div>
                                <div class="text-sm text-purple-700 font-medium">Total Procesadas</div>
                            </div>
                        </div>
                    </div>
                </div>

             
                <div class="bg-white rounded-2xl shadow-lg p-4 border border-purple-100">
                    <h3 class="text-lg font-bold text-gray-800 mb-3">📅 Este Mes</h3>
                    <div id="monthlyBreakdown" class="space-y-2 text-sm">
                        <!-- estadisticas del mes-->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let historyData = <?php echo json_encode($historyData); ?>;
        let currentFilter = 'all';

        //inicializar el historial
        function initHistory() {
            updateCurrentDate();
            generateHistoryContent('all');
            updateHistorySummary();
            updateMonthlyBreakdown();
        }

        function updateCurrentDate() {
            const today = new Date();
            document.getElementById('currentDate').textContent = today.toLocaleDateString('es-ES', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        }

        function generateHistoryContent(filter) {
            const historyAppointments = [];
            
            // Todas las citas(ausentes y completadas)
            Object.keys(historyData).forEach(dateStr => {
                historyData[dateStr].forEach(appointment => {
                    historyAppointments.push({
                        ...appointment,
                        dateStr: dateStr,
                        date: new Date(dateStr)
                    });
                });
            });
            
            // filtrar segun el filtro seleccionada
            const filteredAppointments = historyAppointments.filter(appointment => {
                if (filter === 'all') return true;
                return appointment.status === filter;
            });
            
            // ordenar por fecha, los más recientes primero
            filteredAppointments.sort((a, b) => b.date - a.date);
            
            const historyContent = document.getElementById('historyContent');
            
            if (filteredAppointments.length === 0) {
                historyContent.innerHTML = '<div class="text-center text-gray-500 py-8">No hay citas en el historial</div>';
                return;
            }
            
            historyContent.innerHTML = filteredAppointments.map(appointment => {
                const dateFormatted = appointment.date.toLocaleDateString('es-ES', {
                    weekday: 'short',
                    day: 'numeric',
                    month: 'short',
                    year: 'numeric'
                });
                
                let bgColor = 'bg-green-50 border-green-300';
                let statusText = '✅ Asistida';
                let statusColor = 'text-green-700';
                
                if (appointment.status === 'absent') {
                    bgColor = 'bg-yellow-50 border-yellow-300';
                    statusText = '❌ No Asistió';
                    statusColor = 'text-yellow-700';
                }
                
                return `
                    <div class="history-item p-4 rounded-lg border ${bgColor} cursor-pointer" onclick="openAppointmentDetails('${appointment.dateStr}', '${appointment.patient}')">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                            <div>
                                <div class="text-sm text-gray-600">Fecha</div>
                                <div class="font-semibold text-gray-800">${dateFormatted}</div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-600">Hora</div>
                                <div class="font-bold text-gray-800">${appointment.time}</div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-600">Paciente</div>
                                <div class="font-semibold text-gray-800">${appointment.patient}</div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-600">Estado</div>
                                <div class="font-medium ${statusColor}">${statusText}</div>
                            </div>
                        </div>
                        <div class="mt-2 pt-2 border-t border-gray-200">
                            <div class="text-sm text-gray-600">Tratamiento</div>
                            <div class="text-gray-800">${appointment.treatment}</div>
                        </div>
                    </div>
                `;
            }).join('');
        }

        function openAppointmentDetails(dateStr, patientName) {
            // encontrar la cita
            const appointment = historyData[dateStr].find(apt => apt.patient === patientName);
            if (!appointment) return;

            // crear el modal
            const modalBackdrop = document.createElement('div');
            modalBackdrop.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
            modalBackdrop.onclick = (e) => {
                if (e.target === modalBackdrop) {
                    modalBackdrop.remove();
                }
            };

            // el contenido del modal de cada cita
            const modalContent = document.createElement('div');
            modalContent.className = 'bg-white rounded-2xl shadow-2xl p-6 max-w-md w-full mx-4';
            
            const appointmentDate = new Date(dateStr);
            const dateFormatted = appointmentDate.toLocaleDateString('es-ES', {
                weekday: 'long',
                day: 'numeric',
                month: 'long',
                year: 'numeric'
            });

            let bgColor = 'bg-green-50 border-green-300';
            let statusText = '✅ Asistida';
            let statusColor = 'text-green-700';
            
            if (appointment.status === 'absent') {
                bgColor = 'bg-yellow-50 border-yellow-300';
                statusText = '❌ No Asistió';
                statusColor = 'text-yellow-700';
            }

            modalContent.innerHTML = `
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-bold text-gray-800">📋 Detalle de Cita</h3>
                    <button onclick="this.closest('.fixed').remove()" class="text-gray-500 hover:text-gray-700 text-2xl">×</button>
                </div>
                
                <div class="mb-6">
                    <div class="p-4 rounded-lg border ${bgColor}">
                        <div class="space-y-3">
                            <div class="flex items-center space-x-2">
                                <span class="text-sm text-gray-600">📅 Fecha:</span>
                                <span class="font-semibold text-gray-800">${dateFormatted}</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="text-sm text-gray-600">🕐 Hora:</span>
                                <span class="font-bold text-lg text-gray-800">${appointment.time}</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="text-sm text-gray-600">👤 Paciente:</span>
                                <span class="font-semibold text-gray-800">${appointment.patient}</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="text-sm text-gray-600">🦷 Tratamiento:</span>
                                <span class="text-gray-800">${appointment.treatment}</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="text-sm text-gray-600">📞 Teléfono:</span>
                                <span class="text-gray-800">${appointment.phone}</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="text-sm text-gray-600">📊 Estado:</span>
                                <span class="font-medium ${statusColor}">${statusText}</span>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            modalBackdrop.appendChild(modalContent);
            document.body.appendChild(modalBackdrop);
        }

        function filterHistory(filter) {
            currentFilter = filter;
            
            document.getElementById('filterAll').className = filter === 'all' 
                ? 'px-4 py-2 bg-purple-600 text-white rounded-lg text-sm font-medium transition-colors'
                : 'px-4 py-2 bg-gray-200 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-300 transition-colors';
            
            document.getElementById('filterCompleted').className = filter === 'completed' 
                ? 'px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-medium transition-colors'
                : 'px-4 py-2 bg-gray-200 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-300 transition-colors';
            
            document.getElementById('filterAbsent').className = filter === 'absent' 
                ? 'px-4 py-2 bg-yellow-600 text-white rounded-lg text-sm font-medium transition-colors'
                : 'px-4 py-2 bg-gray-200 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-300 transition-colors';
            
            // mostrar el contenido acorde al filtro aplicado
            generateHistoryContent(filter);
        }

        function updateHistorySummary() {
            let completedCount = 0;
            let absentCount = 0;
            
            Object.keys(historyData).forEach(dateStr => {
                historyData[dateStr].forEach(appointment => {
                    if (appointment.status === 'completed') {
                        completedCount++;
                    } else if (appointment.status === 'absent') {
                        absentCount++;
                    }
                });
            });
            
            document.getElementById('completedTotal').textContent = completedCount;
            document.getElementById('absentTotal').textContent = absentCount;
            document.getElementById('totalProcessed').textContent = completedCount + absentCount;
        }

        function updateMonthlyBreakdown() {
            const today = new Date();
            const currentMonth = today.getMonth();
            const currentYear = today.getFullYear();
            
            let monthlyCompleted = 0;
            let monthlyAbsent = 0;
            
            Object.keys(historyData).forEach(dateStr => {
                const date = new Date(dateStr);
                if (date.getFullYear() === currentYear && date.getMonth() === currentMonth) {
                    historyData[dateStr].forEach(appointment => {
                        if (appointment.status === 'completed') {
                            monthlyCompleted++;
                        } else if (appointment.status === 'absent') {
                            monthlyAbsent++;
                        }
                    });
                }
            });
            
            document.getElementById('monthlyBreakdown').innerHTML = `
                <div class="flex justify-between items-center p-2 bg-green-50 rounded border border-green-200">
                    <span class="text-green-700">✅ Asistidas:</span>
                    <span class="font-bold text-green-600">${monthlyCompleted}</span>
                </div>
                <div class="flex justify-between items-center p-2 bg-yellow-50 rounded border border-yellow-200">
                    <span class="text-yellow-700">❌ No Asistieron:</span>
                    <span class="font-bold text-yellow-600">${monthlyAbsent}</span>
                </div>
                <div class="flex justify-between items-center p-2 bg-purple-50 rounded border border-purple-200">
                    <span class="text-purple-700">📊 Total:</span>
                    <span class="font-bold text-purple-600">${monthlyCompleted + monthlyAbsent}</span>
                </div>
            `;
        }

        function searchHistory() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase().trim();
            const historyContent = document.getElementById('historyContent');
            
            const historyAppointments = [];
            
            // mostrar todos acorde la busqueda
            Object.keys(historyData).forEach(dateStr => {
                historyData[dateStr].forEach(appointment => {
                    historyAppointments.push({
                        ...appointment,
                        dateStr: dateStr,
                        date: new Date(dateStr)
                    });
                });
            });
            
            // filtrar por termino de busqueda y filtro
            const filteredAppointments = historyAppointments.filter(appointment => {
                const matchesFilter = currentFilter === 'all' || appointment.status === currentFilter;
                
                if (!searchTerm) return matchesFilter;
                
                //buscar el nombre del paciente
                const patientMatch = appointment.patient.toLowerCase().includes(searchTerm);
                
                // buscar por tratamiento
                const treatmentMatch = appointment.treatment.toLowerCase().includes(searchTerm);
                
                //buscar por fecha (format: YYYY-MM-DD)
                const dateMatch = appointment.dateStr.includes(searchTerm);
                
                //buscar por fecha formateada (DD/MM/YYYY)
                const formattedDate = appointment.date.toLocaleDateString('es-ES');
                const formattedDateMatch = formattedDate.includes(searchTerm);
                
                const spanishDate = appointment.date.toLocaleDateString('es-ES', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric'
                });
                const spanishDateMatch = spanishDate.includes(searchTerm);
                
                const matchesSearch = patientMatch || treatmentMatch || dateMatch || formattedDateMatch || spanishDateMatch;
                
                return matchesFilter && matchesSearch;
            });
            
            // ordenados por fecha
            filteredAppointments.sort((a, b) => b.date - a.date);
            
            if (filteredAppointments.length === 0) {
                historyContent.innerHTML = '<div class="text-center text-gray-500 py-8">No se encontraron resultados</div>';
                return;
            }
            
            historyContent.innerHTML = filteredAppointments.map(appointment => {
                const dateFormatted = appointment.date.toLocaleDateString('es-ES', {
                    weekday: 'short',
                    day: 'numeric',
                    month: 'short',
                    year: 'numeric'
                });
                
                let bgColor = 'bg-green-50 border-green-300';
                let statusText = '✅ Asistida';
                let statusColor = 'text-green-700';
                
                if (appointment.status === 'absent') {
                    bgColor = 'bg-yellow-50 border-yellow-300';
                    statusText = '❌ No Asistió';
                    statusColor = 'text-yellow-700';
                }
                
                return `
                    <div class="history-item p-4 rounded-lg border ${bgColor} cursor-pointer" onclick="openAppointmentDetails('${appointment.dateStr}', '${appointment.patient}')">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                            <div>
                                <div class="text-sm text-gray-600">Fecha</div>
                                <div class="font-semibold text-gray-800">${dateFormatted}</div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-600">Hora</div>
                                <div class="font-bold text-gray-800">${appointment.time}</div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-600">Paciente</div>
                                <div class="font-semibold text-gray-800">${appointment.patient}</div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-600">Estado</div>
                                <div class="font-medium ${statusColor}">${statusText}</div>
                            </div>
                        </div>
                        <div class="mt-2 pt-2 border-t border-gray-200">
                            <div class="text-sm text-gray-600">Tratamiento</div>
                            <div class="text-gray-800">${appointment.treatment}</div>
                        </div>
                    </div>
                `;
            }).join('');
        }
                


        function showSuccessMessage(message) {
            const toast = document.createElement('div');
            toast.className = 'f78ixed top-4 right-4 bg-green-600 text-white px-6 py-3 rounded-lg shadow-lg z-50 transform translate-x-full transition-transform';
            toast.textContent = message;
            
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.classList.remove('translate-x-full');
            }, 100);
            
            setTimeout(() => {
                toast.classList.add('translate-x-full');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        // inicializar el historial
        initHistory();
    </script>
</body>
</html>

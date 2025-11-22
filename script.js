// Toast notification
function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    toast.textContent = message;
    toast.className = `toast ${type} show`;

    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}

// Admin Functions
async function loadUsers() {
    const response = await fetch('?action=get_users');
    const users = await response.json();

    const container = document.getElementById('users-list');
    const assignUserSelect = document.getElementById('assign-user');

    if (users.length === 0) {
        container.innerHTML = '<div class="empty-state"><h3>No users found</h3></div>';
        return;
    }

    container.innerHTML = users.map(user => `
    <div class="user-card">
        <h3>${user.name}</h3>
        <p><strong>Email:</strong> ${user.email}</p>
        <p><span class="user-role role-${user.role}">${user.role.toUpperCase()}</span></p>

        ${user.role !== 'admin' ? `
            <button class="btn btn-primary" style="margin-top: 10px; width: 100%;" 
                onclick="makeAdmin(${user.user_id})">Make Admin</button>
            <button class="btn btn-danger" style="margin-top: 10px; width: 100%;" 
                onclick="deleteUser(${user.user_id})">Delete User</button>
        ` : `
            <button class="btn btn-warning" style="margin-top: 10px; width: 100%;" 
                onclick="demoteUser(${user.user_id})">Demote to User</button>
        `}
    </div>
`).join('');



    // Populate assign dropdown (users only)
    if (assignUserSelect) {
        assignUserSelect.innerHTML = '<option value="">Choose user...</option>' +
            users.filter(u => u.role === 'user')
                .map(u => `<option value="${u.user_id}">${u.name}</option>`)
                .join('');
    }
}

async function loadDevices() {
    const response = await fetch('?action=get_devices');
    const devices = await response.json();

    const container = document.getElementById('devices-list');
    const assignDeviceSelect = document.getElementById('assign-device');

    if (devices.length === 0) {
        container.innerHTML = '<div class="empty-state"><h3>No devices found</h3></div>';
        return;
    }

    container.innerHTML = devices.map(device => `
        <div class="device-card">
            <h3>${device.device_name}</h3>
            <p class="device-info"><strong>Provider:</strong> ${device.company_name || 'N/A'}</p>
            <p class="device-info"><strong>Version:</strong> ${device.compatibility_version || 'N/A'}</p>
            <span class="device-status status-${device.status.toLowerCase()}">${device.status}</span>
        </div>
    `).join('');

    // Populate assign dropdown
    if (assignDeviceSelect) {
        assignDeviceSelect.innerHTML = '<option value="">Choose device...</option>' +
            devices.map(d => `<option value="${d.device_id}">${d.device_name}</option>`).join('');
    }
}

async function loadAssignments() {
    const response = await fetch('?action=get_assignments');
    const assignments = await response.json();

    const container = document.getElementById('assignments-list');

    if (assignments.length === 0) {
        container.innerHTML = '<div class="empty-state"><h3>No assignments found</h3></div>';
        return;
    }

    container.innerHTML = `
        <table>
            <thead>
                <tr>
                    <th>User</th>
                    <th>Device</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                ${assignments.map(a => `
                    <tr>
                        <td>${a.user_name}</td>
                        <td>${a.device_name}</td>
                        <td>
                            <button class="btn btn-danger" onclick="removeAssignment(${a.assignment_id})">
                                Remove
                            </button>
                        </td>
                    </tr>
                `).join('')}
            </tbody>
        </table>
    `;
}

async function loadLogs() {
    const response = await fetch('?action=get_logs');
    const logs = await response.json();
    console.log(logs + "📜");

    const container = document.getElementById('logs-list');

    if (logs.length === 0) {
        container.innerHTML = '<div class="empty-state"><h3>No activity logs</h3></div>';
        return;
    }

    container.innerHTML = `
        <table id="user_table_hide">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Device</th>
                    <th>Action</th>
                    <th>Executed By</th>
                    <th>Time</th>
                </tr>
            </thead>
            <tbody>
                ${logs.map(log => `
                    <tr>
                        <td>${log.user_name || 'User'}</td>
                        <td>${log.device_name || 'N/A'}</td>
                        <td><strong>${log.action}</strong></td>
                        <td>${log.executed_by}</td>
                        <td>${new Date(log.timestamp).toLocaleString()}</td>
                    </tr>
                `).join('')}
            </tbody>
        </table>
    `;
}
async function deleteUser(userId) {
    if (!confirm('Are you sure you want to delete this user?')) return;

    const formData = new FormData();
    formData.append('user_id', userId);

    const response = await fetch('?action=delete_user', {
        method: 'POST',
        body: formData
    });
    const data = await response.json();

    if (data.success) {
        showToast('User deleted successfully', 'success');
        loadUsers();
    } else {
        showToast(data.error, 'error');
    }
}

async function removeAssignment(assignmentId) {
    if (!confirm('Remove this device assignment?')) return;

    const formData = new FormData();
    formData.append('assignment_id', assignmentId);

    const response = await fetch('?action=remove_assignment', {
        method: 'POST',
        body: formData
    });
    const data = await response.json();

    if (data.success) {
        showToast('Assignment removed', 'success');
        loadAssignments();
    } else {
        showToast(data.error, 'error');
    }
}

// User Functions
async function loadUserDevices() {
    const response = await fetch('?action=get_devices');
    const devices = await response.json();

    const container = document.getElementById('devices-list');
    const scheduleDeviceSelect = document.getElementById('schedule-device');

    if (devices.length === 0) {
        container.innerHTML = '<div class="empty-state"><h3>No devices assigned</h3><p>Contact admin to assign devices</p></div>';
        return;
    }

    container.innerHTML = devices.map(device => `
        <div class="device-card">
            <h3>${device.device_name}</h3>
            <span class="device-status status-${device.status.toLowerCase()}">${device.status}</span>
            <div class="device-actions">
                <button class="btn ${device.status === 'ON' ? 'btn-danger' : 'btn-success'}" 
                        onclick="toggleDevice(${device.device_id}, '${device.status === 'ON' ? 'OFF' : 'ON'}')">
                    Turn ${device.status === 'ON' ? 'OFF' : 'ON'}
                </button>
            </div>
        </div>
    `).join('');

    // Populate schedule dropdown
    if (scheduleDeviceSelect) {
        scheduleDeviceSelect.innerHTML = '<option value="">Choose device...</option>' +
            devices.map(d => `<option value="${d.device_id}">${d.device_name}</option>`).join('');
    }
}

async function toggleDevice(deviceId, newStatus) {
    const formData = new FormData();
    formData.append('device_id', deviceId);
    formData.append('status', newStatus);

    const response = await fetch('?action=toggle_device', {
        method: 'POST',
        body: formData
    });
    const data = await response.json();

    if (data.success) {
        showToast(`Device turned ${newStatus}`, 'success');
        loadUserDevices();
        loadLogs();
    } else {
        showToast(data.error, 'error');
    }
}

async function loadSchedules() {
    const response = await fetch('?action=get_schedules');
    const schedules = await response.json();

    const container = document.getElementById('schedules-list');

    if (schedules.length === 0) {
        container.innerHTML = '<div class="empty-state"><h3>No schedules found</h3></div>';
        return;
    }

    container.innerHTML = `
        <table>
            <thead>
                <tr>
                    <th>Device</th>
                    <th>Action</th>
                    <th>Scheduled Time</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                ${schedules.map(s => `
                    <tr>
                        <td>${s.device_name}</td>
                        <td><strong>${s.action}</strong></td>
                        <td>${new Date(s.scheduled_time).toLocaleString()}</td>
                        <td><span class="device-status ${s.status === 'executed' ? 'status-on' : 'status-off'}">${s.status}</span></td>
                        <td>
                            ${s.status === 'pending' ? `
                                <button class="btn btn-danger" onclick="deleteSchedule(${s.schedule_id})">
                                    Delete
                                </button>
                            ` : '-'}
                        </td>
                    </tr>
                `).join('')}
            </tbody>
        </table>
    `;
}

async function deleteSchedule(scheduleId) {
    if (!confirm('Delete this schedule?')) return;

    const formData = new FormData();
    formData.append('schedule_id', scheduleId);

    const response = await fetch('?action=delete_schedule', {
        method: 'POST',
        body: formData
    });
    const data = await response.json();

    if (data.success) {
        showToast('Schedule deleted', 'success');
        loadSchedules();
    } else {
        showToast(data.error, 'error');
    }
}

// Provider Functions
async function loadProviderDevices() {
    const response = await fetch('?action=get_devices');
    const devices = await response.json();

    const container = document.getElementById('devices-list');
    const updateDeviceSelect = document.getElementById('update-device');

    if (devices.length === 0) {
        container.innerHTML = '<div class="empty-state"><h3>No devices registered</h3><p>Add your first device above</p></div>';
        return;
    }

    container.innerHTML = devices.map(device => `
        <div class="device-card">
            <h3>${device.device_name}</h3>
            <p class="device-info"><strong>Version:</strong> ${device.compatibility_version || 'N/A'}</p>
            <p class="device-info"><strong>Status:</strong> <span class="device-status status-${device.status.toLowerCase()}">${device.status}</span></p>
            <div style="margin-top: 15px;">
                <input type="text" id="version-${device.device_id}" 
                       value="${device.compatibility_version || ''}" 
                       placeholder="New version"
                       style="width: 100%; padding: 8px; border: 2px solid #e0e0e0; border-radius: 8px; margin-bottom: 10px;">
                <button class="btn btn-warning" style="width: 100%;" 
                        onclick="updateDeviceVersion(${device.device_id})">
                    Update Version
                </button>
            </div>
        </div>
    `).join('');

    // Populate update dropdown
    if (updateDeviceSelect) {
        updateDeviceSelect.innerHTML = '<option value="">Choose device...</option>' +
            devices.map(d => `<option value="${d.device_id}">${d.device_name}</option>`).join('');
    }
}

async function updateDeviceVersion(deviceId) {
    const newVersion = document.getElementById(`version-${deviceId}`).value;
    if (!newVersion) {
        showToast('Please enter a version', 'error');
        return;
    }

    const formData = new FormData();
    formData.append('device_id', deviceId);
    formData.append('compatibility_version', newVersion);

    const response = await fetch('?action=update_device', {
        method: 'POST',
        body: formData
    });
    const data = await response.json();

    if (data.success) {
        showToast('Device version updated', 'success');
        loadProviderDevices();
    } else {
        showToast(data.error, 'error');
    }
}

async function loadUpdates() {
    const response = await fetch('?action=get_updates');
    const updates = await response.json();

    const container = document.getElementById('updates-list');

    if (updates.length === 0) {
        container.innerHTML = '<div class="empty-state"><h3>No updates posted</h3></div>';
        return;
    }

    container.innerHTML = `
        <table>
            <thead>
                <tr>
                    <th>Device</th>
                    <th>Update Description</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                ${updates.map(u => `
                    <tr>
                        <td>${u.device_name}</td>
                        <td>${u.update_description}</td>
                        <td>${new Date(u.update_date).toLocaleString()}</td>
                    </tr>
                `).join('')}
            </tbody>
        </table>
    `;
}

// Scheduler Polling - runs every 30 seconds
let schedulerInterval = null;

function startSchedulerPolling() {
    // Run immediately
    runScheduler();

    // Then run every 30 seconds
    schedulerInterval = setInterval(runScheduler, 30000);
}

async function runScheduler() {
    try {
        const response = await fetch('scheduler.php');
        const data = await response.json();

        if (data.executed_count > 0) {
            console.log(`Scheduler executed ${data.executed_count} tasks at ${data.timestamp}`);

            // Reload devices and schedules to reflect changes
            if (typeof loadUserDevices === 'function') {
                loadUserDevices();
            }
            if (typeof loadSchedules === 'function') {
                loadSchedules();
            }
            if (typeof loadLogs === 'function') {
                loadLogs();
            }

            // Show toast notification
            data.executed.forEach(exec => {
                showToast(`${exec.device_name} turned ${exec.action} by scheduler`, 'success');
            });
        }
    } catch (error) {
        console.error('Scheduler error:', error);
    }
}
async function makeAdmin(user_id) {
    if (!confirm("Make this user an Admin?")) return;

    const formData = new FormData();
    formData.append('user_id', user_id);

    const response = await fetch('?action=make_admin', { method: 'POST', body: formData });
    const data = await response.json();

    if (data.success) {
        showToast('User promoted to Admin', 'success');
        loadUsers();
    } else {
        showToast(data.error, 'error');
    }
}

async function demoteUser(user_id) {
    if (!confirm("Demote this admin to User?")) return;

    const formData = new FormData();
    formData.append('user_id', user_id);

    const response = await fetch('?action=demote_user', { method: 'POST', body: formData });
    const data = await response.json();

    if (data.success) {
        showToast('Admin demoted to User', 'success');
        loadUsers();
    } else {
        showToast(data.error, 'error');
    }
}


// Stop polling when page unloads
window.addEventListener('beforeunload', () => {
    if (schedulerInterval) {
        clearInterval(schedulerInterval);
    }
});

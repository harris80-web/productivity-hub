const addModal = document.getElementById("addModal");
const editModal = document.getElementById("editModal");
const deleteModal = document.getElementById("deleteModal");
const openAddModal = document.getElementById("openAddModal");
const closeAddModal = document.getElementById("closeAddModal");
const closeEditModal = document.getElementById("closeEditModal");
const cancelDelete = document.getElementById("cancelDelete");
const taskList = document.getElementById("taskList"); 
const taskListContent = document.getElementById("taskListContent");
const addTaskForm = document.getElementById("addTaskForm");
const editTaskForm = document.getElementById("editTaskForm");
const deleteMessage = document.getElementById("deleteMessage");

const taskName = document.getElementById('taskName'); 
const taskSubject = document.getElementById('taskSubject');
const taskType = document.getElementById('taskType');
const taskDate = document.getElementById('taskDate');
const taskTime = document.getElementById('taskTime');

const hamburger = document.getElementById('hamburger');
const sidebar = document.querySelector('.sidebar');

if (hamburger && sidebar) {
  hamburger.addEventListener('click', function() {
    hamburger.classList.toggle('active');
    sidebar.classList.toggle('open');
  });

  const navItems = sidebar.querySelectorAll('.navigation li, .dropdown-header');
  navItems.forEach(item => {
    item.addEventListener('click', function() {
      hamburger.classList.remove('active');
      sidebar.classList.remove('open');
    });
  });

  document.addEventListener('click', function(event) {
    if (sidebar.classList.contains('open') && 
        !sidebar.contains(event.target) && 
        !hamburger.contains(event.target)) {
      hamburger.classList.remove('active');
      sidebar.classList.remove('open');
    }
  });
}


function createTaskHtml(task) {
    const isCompleted = task.is_completed == 1; 
    return `
        <article class="task-item" data-id="${task.id}" style="opacity: ${isCompleted ? '0.6' : '1'};">
            <input type="checkbox" class="task-checkbox" ${isCompleted ? 'checked' : ''}>
            <div class="task-content">
                <div class="task-subject">${task.subject}</div>
                <h4 class="task-name">${task.task_name}</h4>
                <div class="task-details">
                    <span class="detail-date">${task.due_date}</span>
                    <span class="detail-time">${task.estimate_min} min</span>
                    <span class="detail-type">${task.task_type}</span>
                </div>
            </div>
            <div class="task-actions">
                <button class="edit" title="Edit">
                    <img src="ASSETS/edit.svg" alt="Edit" style="width: 20px; height: 20px;">
                </button>
                <button class="delete" title="Delete">
                    <img src="ASSETS/delete.svg" alt="Delete" style="width: 20px; height: 20px;">
                </button>
            </div>
        </article>
    `;
}

function loadTasks() {
    if (!taskListContent) {
        return;
    }

    fetch('/api/api.php', {
        method: 'POST',
        body: new URLSearchParams('action=get_tasks') 
    })
    .then(response => {
        return response.json();
    })
    .then(data => {
        taskListContent.innerHTML = '';
        if (data.success && data.tasks && data.tasks.length > 0) {
            data.tasks.forEach(task => {
                taskListContent.innerHTML += createTaskHtml(task);
            });
        }
        updateTaskCounts(new Date()); 
    })
    .catch(error => console.error('Error loading tasks:', error));
}

document.addEventListener('DOMContentLoaded', function() {
    const aiBtn = document.getElementById('aiSuggestBtn');       
    const aiPrompt = document.getElementById('taskName');        
    const aiContainer = document.getElementById('aiSuggestionList');
    const aiPanel = document.getElementById('aiSuggestionPanel');
    const aiCloseBtn = document.getElementById('closeAiPanel');
    const aiSearchInput = document.getElementById('aiSearchInput');
    const aiPanelSuggestBtn = document.getElementById('aiPanelSuggestBtn');

    if (!aiBtn || !aiPrompt || !aiContainer) return;

    const showAiPanel = () => {
        aiPanel && aiPanel.classList.add('show');
    };

    const hideAiPanel = () => {
        aiPanel && aiPanel.classList.remove('show');
    };

    if (aiCloseBtn) {
        aiCloseBtn.addEventListener('click', hideAiPanel);
    }

    const fetchAndRenderSuggestions = async (prompt) => {
        if (!prompt.trim()) {
            aiContainer.innerHTML = '<div style="padding: 20px; text-align: center; color: #aaa;">Enter a task description to get suggestions.</div>';
            return;
        }

        aiContainer.innerHTML = '<div style="padding: 20px; text-align: center; color: #77CFFF;">Loading suggestions...</div>';
        showAiPanel();

        try {
            const res = await fetch('/api/task_generator.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ prompt })
            });

            if (!res.ok) {
                const err = await res.json().catch(() => ({ error: 'Server error' }));
                aiContainer.innerHTML = '<div style="padding: 20px; color: #ff6b6b;">Error: ' + (err.error || JSON.stringify(err)) + '</div>';
                return;
            }

            const data = await res.json();

            if (!data.success || !Array.isArray(data.suggestions)) {
                aiContainer.innerHTML = '<div style="padding: 20px; color: #aaa;">' + (data.message || 'No suggestions returned') + '</div>';
                return;
            }

            aiContainer.innerHTML = '';

            data.suggestions.forEach((s, idx) => {
                const card = document.createElement('div');
                card.className = 'ai-suggestion-card';
                card.innerHTML = `
                    <div class="suggestion-title">${escapeHtml(s.title || 'Suggestion ' + (idx + 1))}</div>
                    <div class="suggestion-meta">
                        <span>📁 ${escapeHtml(s.category || 'General')}</span>
                        <span>⏱️ ${escapeHtml(String(s.estimated_minutes || '—'))}m</span>
                    </div>
                    <button class="use-suggestion" data-idx="${idx}">Use</button>
                `;
                aiContainer.appendChild(card);
            });

            aiContainer.querySelectorAll('.use-suggestion').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    const idx = parseInt(e.target.dataset.idx, 10);
                    const s = data.suggestions[idx];
                    
                    taskName.value = s.title || '';
                    taskSubject.value = s.subject || '';
                    taskType.value = s.category || 'General';
                    taskTime.value = s.estimated_minutes || '';
                    
                    hideAiPanel();
                    addModal.scrollIntoView({ behavior: 'smooth' });
                });
            });

        } catch (err) {
            aiContainer.innerHTML = '<div style="padding: 20px; color: #ff6b6b;">Error: ' + String(err) + '</div>';
        }
    };

    aiBtn.addEventListener('click', async () => {
        const prompt = aiPrompt.value.trim();
        if (!prompt) {
            alert('Please type a short task idea first.');
            return;
        }
        await fetchAndRenderSuggestions(prompt);
    });

    if (aiPanelSuggestBtn) {
        aiPanelSuggestBtn.addEventListener('click', () => {
            const prompt = aiSearchInput.value.trim();
            fetchAndRenderSuggestions(prompt);
        });
    }

    if (aiSearchInput) {
        aiSearchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                const prompt = aiSearchInput.value.trim();
                fetchAndRenderSuggestions(prompt);
            }
        });
    }
});

function escapeHtml(unsafe) {
    if (unsafe === null || unsafe === undefined) return '';
    return String(unsafe)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;").replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;").replace(/'/g, "&#039;");
}

let taskIdToDelete = null;
let taskIdToEdit = null;

openAddModal.onclick = () => addModal.style.display = "flex";
closeAddModal.onclick = () => addModal.style.display = "none";
closeEditModal.onclick = () => editModal.style.display = "none";

window.onclick = e => { 
    if (e.target === addModal) addModal.style.display = "none";
    if (e.target === deleteModal) deleteModal.style.display = "none";
    if (e.target === editModal) editModal.style.display = "none";
}

addTaskForm.addEventListener("submit", e => {
    e.preventDefault();
    
    const formData = new FormData(addTaskForm);
    formData.append('action', 'add_task'); 

    fetch('/api/api.php', {
        method: 'POST',
        body: formData 
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            addModal.style.display = "none";
            addTaskForm.reset();
            loadTasks(); 
        } else {
            console.error('API Error:', data.message);
            alert('Error adding task: ' + (data.message || 'Unknown error.'));
        }
    })
    .catch(error => {
        console.error('Network or Parsing Error:', error);
        alert('Failed to connect to the backend.');
    });
});

if (taskList) {
    taskList.addEventListener('click', (e) => {
        const taskItem = e.target.closest('.task-item');
        if (!taskItem) return;

        const taskId = taskItem.getAttribute('data-id');

        if (e.target.classList.contains('edit')) {
            taskIdToEdit = taskId; 
            
            document.getElementById('editTaskName').value = taskItem.querySelector('.task-name').textContent;
            document.getElementById('editTaskSubject').value = taskItem.querySelector('.task-subject').textContent;
            document.getElementById('editTaskType').value = taskItem.querySelector('.detail-type').textContent.trim();
            document.getElementById('editTaskDate').value = taskItem.querySelector('.detail-date').textContent.trim();
            document.getElementById('editTaskTime').value = taskItem.querySelector('.detail-time').textContent.split(' ')[0];
                            
            editModal.style.display = "flex";

        } else if (e.target.classList.contains('delete')) {
            const taskName = taskItem.querySelector('.task-name').textContent;
            taskIdToDelete = taskId; 
            deleteMessage.textContent = `Are you sure you want to delete this task (${taskName})?`;
            deleteModal.style.display = "flex";
        }
    });
}

deleteModal.querySelector('.delete-confirm').addEventListener('click', () => {
    if (taskIdToDelete) {
        const formData = new URLSearchParams();
        formData.append('action', 'delete_task');
        formData.append('id', taskIdToDelete);

        fetch('/api/api.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const itemToRemove = document.querySelector(`.task-item[data-id="${taskIdToDelete}"]`);
                if (itemToRemove) itemToRemove.remove();
                taskIdToDelete = null;
                deleteModal.style.display = "none";
                updateTaskCounts(new Date()); 
            } else {
                alert('Error deleting task: ' + data.message);
            }
        })
        .catch(error => console.error('Error deleting task:', error));
    }
});

cancelDelete.addEventListener('click', () => {
    taskIdToDelete = null;
    deleteModal.style.display = "none";
});

editTaskForm.addEventListener("submit", e => {
    e.preventDefault();
    
    if (taskIdToEdit) {
        const formData = new FormData(editTaskForm);
        formData.append('action', 'update_task');
        formData.append('id', taskIdToEdit); 

        fetch('/api/api.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                editModal.style.display = "none";
                editTaskForm.reset();
                taskIdToEdit = null;
                loadTasks(); 
            } else {
                alert('Error updating task: ' + data.message);
            }
        })
        .catch(error => console.error('Error updating task:', error));
    }
});

if (taskList) {
    taskList.addEventListener('change', (e) => {
        if (e.target.classList.contains('task-checkbox')) {
            const taskItem = e.target.closest('.task-item');
            const taskId = taskItem.getAttribute('data-id');
            const isCompleted = e.target.checked ? 1 : 0;
            
            const formData = new URLSearchParams();
            formData.append('action', 'toggle_complete');
            formData.append('id', taskId);
            formData.append('is_completed', isCompleted);

            fetch('/api/api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    taskItem.style.opacity = isCompleted ? '0.6' : '1';
                    updateTaskCounts(new Date()); 
                } else {
                    alert('Error updating status: ' + data.message);
                    e.target.checked = !isCompleted; 
                }
            })
            .catch(error => {
                console.error('Error toggling status:', error);
                e.target.checked = !isCompleted; 
            });
        }
    });
}

const clockDisplay = document.getElementById('clockDisplay');
const openCountEl = document.getElementById('openCount');
const overdueCountEl = document.getElementById('overdueCount');
const completedCountEl = document.getElementById('completedCount');
const urgentCountEl = document.getElementById('urgentCount');
const tasksDueEl = document.getElementById('tasksDue');

function pad(n){return n<10? '0'+n : n}
function formatTime(d){
    const h = d.getHours();
    const m = pad(d.getMinutes());
    const hh = (h%12) || 12;
    const ampm = h>=12 ? 'PM' : 'AM';
    return `${hh}:${m} ${ampm}`;
}
function formatDate(d){
    return d.toLocaleDateString(undefined, { weekday: 'long', month: 'long', day: 'numeric' });
}
function yyyyMmDd(d){
    return d.toISOString().slice(0,10);
}

function updateClock(){
    let now = new Date();
    if (clockDisplay) clockDisplay.innerHTML = `${formatTime(now)} <span class="date" id="clockDate">${formatDate(now)}</span>`;
    updateTaskCounts(now);
}

function updateTaskCounts(now){
    const today = yyyyMmDd(now);
    const tasks = Array.from(document.querySelectorAll('.task-item'));
    let open = 0, overdue = 0, completed = 0, urgent = 0, dueToday = 0;
    
    tasks.forEach(item => {
        const chk = item.querySelector('.task-checkbox');
        const date = item.querySelector('.detail-date') ? item.querySelector('.detail-date').textContent.trim() : '';
        const type = item.querySelector('.detail-type') ? item.querySelector('.detail-type').textContent.trim() : '';
        const isChecked = chk && chk.checked;
        
        if (!isChecked) {
            open++;
            if (date === today) dueToday++;
            if (date && date < today) overdue++;
            if (type && type.toLowerCase().includes('urg')) urgent++;
        } else {
            completed++;
        }
    });
    
    openCountEl && (openCountEl.textContent = open);
    overdueCountEl && (overdueCountEl.textContent = overdue);
    completedCountEl && (completedCountEl.textContent = completed);
    urgentCountEl && (urgentCountEl.textContent = urgent);
    tasksDueEl && (tasksDueEl.textContent = dueToday);
}

const tasksDropdown = document.getElementById("tasksDropdown");

if (tasksDropdown) {
    const dropdownParent = tasksDropdown.parentElement;

    tasksDropdown.addEventListener("click", () => {
        dropdownParent.classList.toggle("open");
        const submenu = dropdownParent.querySelector(".submenu");
        submenu.style.display = submenu.style.display === "block" ? "none" : "block";
    });
}

updateClock(); 
setInterval(updateClock, 1000); 

loadTasks();
document.addEventListener('click', (e) => {

    const editButton = e.target.closest('.edit-task-btn'); 
    const deleteButton = e.target.closest('.delete-task-btn'); 
    const taskItem = e.target.closest('.task-card'); 

    if (!taskItem || (!editButton && !deleteButton)) return;
    
    const taskId = taskItem.getAttribute('data-task-id'); 

    if (editButton) {
        
        taskIdToEdit = taskId; 

        document.getElementById('editTaskName').value = taskItem.querySelector('.task-name').textContent;
        document.getElementById('editTaskSubject').value = taskItem.querySelector('.task-category').textContent; 
        
        const taskTypeEl = taskItem.querySelector('.task-type-hidden');
        if (taskTypeEl) {
             document.getElementById('editTaskType').value = taskTypeEl.textContent.trim(); 
        }

        document.getElementById('editTaskDate').value = taskItem.querySelector('.task-date').textContent.trim(); 
        document.getElementById('editTaskTime').value = taskItem.querySelector('.task-time').textContent.split(' ')[0]; 
                        
        editModal.style.display = "flex";

    } else if (deleteButton) {
        
        const taskName = taskItem.querySelector('.task-name').textContent;
        taskIdToDelete = taskId; 
        deleteMessage.textContent = `Are you sure you want to delete this task (${taskName})?`;
        deleteModal.style.display = "flex";
    }
});

document.addEventListener('change', (e) => {
    if (e.target.classList.contains('task-checkbox')) {
        const taskItem = e.target.closest('.task-card'); 
        if (!taskItem) return;

        const taskId = taskItem.getAttribute('data-task-id');
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
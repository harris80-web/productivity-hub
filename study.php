<?php
require_once 'db_connection.php';

$study_tasks = [];
$sql = "SELECT id, task_name, subject, DATE_FORMAT(due_date, '%Y-%m-%d') AS due_date, estimate_min, task_type, is_completed FROM tasks WHERE task_type = 'Study' ORDER BY due_date ASC, id ASC";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $study_tasks[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STUDY</title>
    <link rel="icon" type="image/x-icon" href="ASSETS/logo.svg">
    <link rel="stylesheet" href="sgu.css">
    <script src="script.js" defer></script>
    <script src="sgu.js" defer></script>
</head>
<body>
    <!-- TOP BAR -->
<header class="topbar">
  <div class="hamburger" id="hamburger" aria-label="Toggle navigation">
    <span></span>
    <span></span>
    <span></span>
  </div>
  <div class="topbar-left">
    <a href="index.html">
    <img src="ASSETS/logo.svg" class="topbar-icon">
    <span>Student Productivity Hub</span>
    </a>
  </div>
</header>

  <!-- Sidebar -->
  <aside class="sidebar">
    <button id="openAddModal" class="add-task-btn">+ ADD TASK</button>

    <nav class="navigation">
      <ul>
        <li>
            <a href="index.html">
              <img src="ASSETS/dashboard.svg" class="nav-icon" alt="home">
              DASHBOARD
            </a>
        </li>

        <li class="dropdown">
          <div class="dropdown-header" id="tasksDropdown">
            <img src="ASSETS/task.svg" class="nav-icon" alt="tasks">
            <span>TASKS</span>
            <span class="arrow">▼</span>
          </div>

          <ul class="submenu">
            <a href="study.php">
              <li class="active">
                <img src="ASSETS/study.svg" class="nav-icon" alt="study">
                STUDY
              </li>
            </a>
            
            <a href="general.php">
            <li>
              <img src="ASSETS/general.svg" class="nav-icon" alt="study">
              GENERAL
            </li>
            </a>

            <a href="urgent.php">
            <li>
              <img src="ASSETS/urgent.svg" class="nav-icon" alt="study">
              URGENT
            </li>
            </a>
          </ul>
        </li>
      </ul>
    </nav>
  </aside>

      <!-- Add Task Modal -->
  <div id="addModal" class="modal">
    <div class="modal-box">
      <h2>WHAT TO DO?</h2>

      <form id="addTaskForm">
        <div class="full-row">
          <label>Task Name:</label>
          <input type="text" id="taskName" name="task_name" required>

          <button type="button" id="aiSuggestBtn" class="ai-suggest-btn" aria-label="AI Suggest">
            <img src="ASSETS/AI-icon.svg" alt="AI" />
          </button>
        </div>

        <div class="two-col">
          <div>
            <label>Subject:</label>
            <input type="text" id="taskSubject" name="subject" required>
          </div>
          <div>
            <label>Task Type:</label>
            <select id="taskType" name="task_type" required>
              <option>Study</option>
              <option>General</option>
              <option>Urgent</option>
            </select>
          </div>
        </div>

        <div class="two-col">
          <div>
            <label>Due Date:</label>
            <input type="date" id="taskDate" name="due_date" required>
          </div>
          <div>
            <label>Estimate (min):</label>
            <input type="number" id="taskTime" name="estimate_min" required>
          </div>
        </div>

        <div class="modal-buttons">
          <button type="button" class="cancel-btn" id="closeAddModal">Cancel</button>
          <button type="submit" class="add-btn">Add Task</button>
        </div>
      </form>
    </div>

    <div id="aiSuggestionPanel" class="ai-panel">
      <div class="ai-panel-inner">
        <div class="ai-panel-header">
          <h3>SUGGESTIONS</h3>
          <button type="button" id="closeAiPanel" class="ai-close-btn" aria-label="Close suggestions">✕</button>
        </div>
        <div class="ai-search-box">
          <input type="text" id="aiSearchInput" placeholder="Search for suggestions..." />
          <button type="button" id="aiPanelSuggestBtn" class="ai-search-btn">Al SUGGEST</button>
        </div>
        <div id="aiSuggestionList" class="ai-suggestion-list"></div>
      </div>
    </div>

  </div>

  <!-- Delete Confirmation Modal -->
  <div id="deleteModal" class="modal">
    <div class="modal-box small">
      <h2>DELETE TASK?</h2>
      <p id="deleteMessage"></p>
      <div class="modal-buttons">
        <button class="cancel-btn" id="cancelDelete">Cancel</button>
        <button class="delete-confirm">Confirm</button>
      </div>
    </div>
  </div>

  <!-- Edit Task Modal -->
  <div id="editModal" class="modal">
    <div class="modal-box">
      <h2>WHAT TO CHANGE?</h2>
      <form id="editTaskForm">
        <div class="full-row">
          <label>Task Name:</label>
          <input type="text" id="editTaskName" name="task_name" required>
        </div>

        <div class="two-col">
          <div>
            <label>Subject:</label>
            <input type="text" id="editTaskSubject" name="subject" required>
          </div>
          <div>
            <label>Task Type:</label>
            <select id="editTaskType" name="task_type" required>
              <option>Study</option>
              <option>General</option>
              <option>Urgent</option>
            </select>
          </div>
        </div>

        <div class="two-col">
          <div>
            <label>Due Date:</label>
            <input type="date" id="editTaskDate" name="due_date" required>
          </div>
          <div>
            <label>Estimate (min):</label>
            <input type="number" id="editTaskTime" name="estimate_min" required>
          </div>
        </div>

        <div class="modal-buttons">
          <button type="button" class="cancel-btn" id="closeEditModal">Cancel</button>
          <button type="submit" class="add-btn">Update</button>
        </div>
      </form>
    </div>
  </div>

    <main class="page-main">
      <section class="page-panel">
        <header class="panel-header">
          <div class="panel-title">
            <img src="ASSETS/study2.svg" class="panel-icon" alt="study">
            <h1>STUDY</h1>
          </div>
          <img src="ASSETS/taskList.svg" alt="Task Icon" class="task-list-icon">
        </header>

        <div class="panel-body">
          <div class="tasks-area" id="studyContainer">
            <?php if (!empty($study_tasks)): ?>
                    <?php foreach ($study_tasks as $task): ?>
                      <?php 
                        $is_done = $task['is_completed'] == 1;
                        $card_class = $is_done ? ' task-done' : '';
                      ?>
                      <div class="task-card<?php echo $card_class; ?>" data-task-id="<?php echo $task['id']; ?>">
    <div class="task-info">
        <input type="checkbox" class="task-checkbox" <?php echo $is_done ? 'checked' : ''; ?> />
        <div class="task-text">
            <h4 class="task-name"><?php echo htmlspecialchars($task['task_name']); ?></h4>
            <div class="task-details">
                <span class="task-date"><?php echo $task['due_date']; ?></span>
                <span class="task-time"><?php echo $task['estimate_min']; ?> min</span>
                <span class="task-category"><?php echo htmlspecialchars($task['subject']); ?></span>
                <span class="task-type-hidden" style="display:none;"><?php echo htmlspecialchars($task['task_type']); ?></span>
            </div>
        </div>
    </div>
    <div class="task-actions">
        <button class="edit-task-btn" data-task-id="<?php echo $task['id']; ?>"><img src="ASSETS/edit.svg" alt="Edit" /></button>
        <button class="delete-task-btn" data-task-id="<?php echo $task['id']; ?>"><img src="ASSETS/delete.svg" alt="Delete" /></button>
    </div>
</div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="no-tasks">No study tasks found!</p>
                <?php endif; ?>
          </div>
        </div>
      </section>
    </main>
</body>
</html>
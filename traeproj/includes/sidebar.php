<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link text-white" href="../dashboard.php">
                    <i class="fas fa-tachometer-alt me-2"></i>
                    Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white" href="../students/index.php">
                    <i class="fas fa-user-graduate me-2"></i>
                    Students
                </a>
            </li>
            
            <?php if (in_array(getCurrentUserRole(), ['admin', 'principal', 'hod'])): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'students') !== false ? 'active' : ''; ?>" href="students/index.php">
                    <i class="fas fa-users me-2"></i>
                    Students
                </a>
            </li>
            <?php endif; ?>
            
            <?php if (in_array(getCurrentUserRole(), ['admin', 'principal', 'hod'])): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'teachers') !== false ? 'active' : ''; ?>" href="teachers/index.php">
                    <i class="fas fa-chalkboard-teacher me-2"></i>
                    Teachers
                </a>
            </li>
            <?php endif; ?>
            
            <?php if (in_array(getCurrentUserRole(), ['admin', 'principal', 'hod'])): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'classes') !== false ? 'active' : ''; ?>" href="classes/index.php">
                    <i class="fas fa-school me-2"></i>
                    Classes
                </a>
            </li>
            <?php endif; ?>
            
            <?php if (in_array(getCurrentUserRole(), ['admin', 'principal', 'hod'])): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'subjects') !== false ? 'active' : ''; ?>" href="subjects/index.php">
                    <i class="fas fa-book me-2"></i>
                    Subjects
                </a>
            </li>
            <?php endif; ?>
            
            <?php if (in_array(getCurrentUserRole(), ['teacher', 'admin', 'principal', 'hod'])): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'results') !== false ? 'active' : ''; ?>" href="results/index.php">
                    <i class="fas fa-chart-bar me-2"></i>
                    Results
                </a>
            </li>
            <?php endif; ?>
            
            <?php if (in_array(getCurrentUserRole(), ['admin', 'principal', 'hod'])): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'reports') !== false ? 'active' : ''; ?>" href="reports/index.php">
                    <i class="fas fa-file-alt me-2"></i>
                    Reports
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'analytics') !== false ? 'active' : ''; ?>" href="analytics/index.php">
                    <i class="fas fa-chart-line me-2"></i>
                    Analytics
                </a>
            </li>
            <?php endif; ?>
            
            <?php if (getCurrentUserRole() === 'student'): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'my_results') !== false ? 'active' : ''; ?>" href="students/my_results.php">
                    <i class="fas fa-chart-line me-2"></i>
                    My Results
                </a>
            </li>
            <?php endif; ?>
            
            <?php if (getCurrentUserRole() === 'parent'): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'children') !== false ? 'active' : ''; ?>" href="parents/children.php">
                    <i class="fas fa-child me-2"></i>
                    My Children
                </a>
            </li>
            <?php endif; ?>
            
            <?php if (in_array(getCurrentUserRole(), ['admin'])): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'settings') !== false ? 'active' : ''; ?>" href="settings/index.php">
                    <i class="fas fa-cog me-2"></i>
                    Settings
                </a>
            </li>
            <?php endif; ?>
        </ul>
        
        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
            <span>Quick Actions</span>
        </h6>
        <ul class="nav flex-column mb-2">
            <?php if (in_array(getCurrentUserRole(), ['teacher', 'admin', 'principal', 'hod'])): ?>
            <li class="nav-item">
                <a class="nav-link" href="results/entry.php">
                    <i class="fas fa-plus-circle me-2"></i>
                    Enter Results
                </a>
            </li>
            <?php endif; ?>
            
            <?php if (in_array(getCurrentUserRole(), ['admin', 'principal', 'hod'])): ?>
            <li class="nav-item">
                <a class="nav-link" href="students/add.php">
                    <i class="fas fa-user-plus me-2"></i>
                    Add Student
                </a>
            </li>
            <?php endif; ?>
            
            <li class="nav-item">
                <a class="nav-link" href="reports/generate.php">
                    <i class="fas fa-file-pdf me-2"></i>
                    Generate Report
                </a>
            </li>
        </ul>
        
        <div class="mt-auto p-3">
            <div class="d-flex align-items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-user-circle fa-2x text-secondary"></i>
                </div>
                <div class="flex-grow-1 ms-3">
                    <div class="fw-bold"><?php echo htmlspecialchars(getCurrentUserName()); ?></div>
                    <div class="text-muted small"><?php echo ucfirst(htmlspecialchars(getCurrentUserRole())); ?></div>
                </div>
            </div>
        </div>
    </div>
</nav>
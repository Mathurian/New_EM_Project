<?php use function App\{can_view_nav, is_organizer, is_logged_in, current_user}; ?>
<header>
	<nav>
		<div class="nav-container">
            <div class="nav-left" style="display:flex; align-items:center;">
                <button id="mobile-menu-toggle" class="btn btn-secondary btn-sm" aria-label="Toggle navigation">☰</button>
                <a href="<?= is_logged_in() ? (current_user()['role'] === 'organizer' ? '/admin' : (current_user()['role'] === 'emcee' ? '/emcee' : (current_user()['role'] === 'judge' ? '/judge' : (current_user()['role'] === 'tally_master' ? '/tally-master' : '/')))) : '/' ?>" class="home-link-desktop">Home</a>
				
				<?php if (!empty($_SESSION['user'])): ?>
                    <div id="nav-sections" style="display:flex; gap:14px;">
                    <!-- Home Link (Mobile Only). Hidden on desktop via CSS -->
                    <a href="<?= is_logged_in() ? (current_user()['role'] === 'organizer' ? '/admin' : (current_user()['role'] === 'emcee' ? '/emcee' : (current_user()['role'] === 'judge' ? '/judge' : (current_user()['role'] === 'tally_master' ? '/tally-master' : '/')))) : '/' ?>" class="home-link-mobile">🏠 Home</a>
                    
                    <!-- Contests Accordion -->
					<?php if (can_view_nav('Contests')): ?>
						<div class="nav-dropdown">
							<a href="#" onclick="toggleDropdown('contests')" class="nav-dropdown-trigger">
								Contests ▼
							</a>
							<div id="contests-dropdown" class="dropdown-content" style="display: none;">
								<a href="/contests">All Contests</a>
								<a href="/contests/new">New Contest</a>
								<?php if (($_SESSION['user']['role'] ?? '') === 'organizer'): ?>
									<a href="/admin/archived-contests">Archived Contests</a>
                    <?php endif; ?>
							</div>
						</div>
                    <?php endif; ?>
					
					<!-- Users/People Accordion -->
					<?php if (can_view_nav('People')): ?>
						<div class="nav-dropdown">
							<a href="#" onclick="toggleDropdown('users')" class="nav-dropdown-trigger">
								People ▼
							</a>
							<div id="users-dropdown" class="dropdown-content" style="display: none;">
								<a href="/admin/users">All Users</a>
								<a href="/people">Contestants & Judges</a>
								<?php if (($_SESSION['user']['role'] ?? '') === 'organizer'): ?>
									<a href="/user/new">Add Person</a>
								<?php endif; ?>
							</div>
						</div>
					<?php endif; ?>
					
					<!-- Admin Accordion -->
					<?php if (($_SESSION['user']['role'] ?? '') === 'organizer'): ?>
						<div class="nav-dropdown">
							<a href="#" onclick="toggleDropdown('admin')" class="nav-dropdown-trigger">
								Admin ▼
							</a>
							<div id="admin-dropdown" class="dropdown-content" style="display: none;">
								<a href="/admin">Dashboard</a>
								
								<!-- Settings Submenu -->
								<div class="submenu-item">
									<a href="#" onclick="toggleSubmenu('settings')">
										Settings
									</a>
									<div id="settings-submenu" class="submenu-content" style="display: none;">
										<a href="/admin/settings">System Settings</a>
										<a href="/admin/templates">Templates</a>
										<a href="/admin/emcee-scripts">Emcee Scripts</a>
										<a href="/admin/judges">Judges</a>
									</div>
								</div>
								
								<!-- Logs Submenu -->
								<div class="submenu-item">
									<a href="#" onclick="toggleSubmenu('logs')">
										Logs
									</a>
									<div id="logs-submenu" class="submenu-content" style="display: none;">
										<a href="/admin/logs">Activity Logs</a>
										<a href="/admin/log-files">Log Files</a>
									</div>
								</div>
								
								<!-- Database Submenu -->
								<div class="submenu-item">
									<a href="#" onclick="toggleSubmenu('database')">
										Database
									</a>
									<div id="database-submenu" class="submenu-content" style="display: none;">
										<a href="/admin/backups">Database Backups</a>
										<a href="/admin/database">Database Browser</a>
									</div>
								</div>
								
								<a href="/admin/print-reports">Print Reports</a>
							</div>
						</div>
					<?php endif; ?>
					
					<!-- Results Accordion -->
					<?php if (can_view_nav('Results')): ?>
						<div class="nav-dropdown">
							<a href="#" onclick="toggleDropdown('results')" class="nav-dropdown-trigger">
								Results ▼
							</a>
                            <div id="results-dropdown" class="dropdown-content" style="display: none;">
								<?php if (is_organizer()): ?>
									<div class="dropdown-divider"></div>
									<a href="/results">📋 Complete Results Overview</a>
								<a href="/results/contestants">👤 Contestants</a>
									<a href="/admin/print-reports">🖨️ Print Reports</a>
								<?php endif; ?>
								<?php if (($_SESSION['user']['role'] ?? '') === 'judge'): ?>
									<div class="dropdown-divider"></div>
									<a href="/results">👀 View My Assigned Results</a>
								<?php endif; ?>
                            </div>
						</div>
					<?php endif; ?>
					
                    <!-- User-specific links (My Profile removed; now under username menu) -->
					
					<?php if (($_SESSION['user']['role'] ?? '') === 'judge' && can_view_nav('My Assignments')): ?>
						<a href="/judge" class="role-specific-link">Judgy Time!</a>
					<?php endif; ?>
					
					<!-- Emcee Navigation Items -->
					<?php if (($_SESSION['user']['role'] ?? '') === 'emcee'): ?>
						<a href="/emcee/scripts" class="role-specific-link">Scripts</a>
						<a href="/emcee/judges" class="role-specific-link">Judges</a>
						<a href="/emcee/contestants" class="role-specific-link">Contestants</a>
					<?php endif; ?>
					
					<!-- Tally Master Navigation Items -->
					<?php if (($_SESSION['user']['role'] ?? '') === 'tally_master'): ?>
						<a href="/tally-master/score-review" class="role-specific-link">Score Review</a>
						<a href="/tally-master/certification" class="role-specific-link">Certification</a>
					<?php endif; ?>
                    
                    <!-- User Menu (Mobile Only) -->
                    <div class="nav-dropdown user-menu-mobile">
                        <a href="#" onclick="toggleDropdown('user-menu-mobile')" class="user-menu-mobile-trigger">
                            👤 <strong><?= htmlspecialchars($_SESSION['user']['preferred_name'] ?? $_SESSION['user']['name']) ?></strong>
                            <span class="user-role-badge">
                                <?= htmlspecialchars(ucfirst($_SESSION['user']['role'])) ?>
                            </span>
                        </a>
                        <div id="user-menu-mobile-dropdown" class="dropdown-content">
                            <a href="/profile">My Profile</a>
                            <form method="post" action="/logout" class="logout-form">
                                <button type="submit" class="btn btn-danger btn-sm logout-btn">Logout</button>
                            </form>
                        </div>
                    </div>
                    </div>
				<?php else: ?>
                    <!-- No navigation for logged-out users -->
				<?php endif; ?>
			</div>
			
			<!-- Right side user info and logout -->
			<div class="nav-right">
                <?php if (!empty($_SESSION['user'])): ?>
                    <div class="nav-dropdown">
                        <a href="#" onclick="toggleDropdown('user-menu')" class="nav-dropdown-trigger">
                            <strong><?= htmlspecialchars($_SESSION['user']['preferred_name'] ?? $_SESSION['user']['name']) ?></strong>
                            <span class="user-role-badge-desktop">
                                <?= htmlspecialchars(ucfirst($_SESSION['user']['role'])) ?>
                            </span>
                        </a>
                        <div id="user-menu-dropdown" class="dropdown-content" style="display: none;">
                            <a href="/profile">My Profile</a>
                            <form method="post" action="/logout" class="logout-form-desktop">
                                <button type="submit" class="btn btn-danger btn-sm logout-btn-desktop">Logout</button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
			</div>
		</div>
	</nav>
</header>

<script>
function toggleDropdown(dropdownId) {
	// Close all other dropdowns
	const allDropdowns = document.querySelectorAll('.dropdown-content');
	allDropdowns.forEach(dropdown => {
		if (dropdown.id !== dropdownId + '-dropdown') {
			dropdown.style.display = 'none';
		}
	});
	
	// Close all submenus when opening main dropdown
	if (dropdownId === 'admin') {
		const allSubmenus = document.querySelectorAll('.submenu-content');
		allSubmenus.forEach(submenu => {
			submenu.style.display = 'none';
		});
	}
	
	// Toggle the clicked dropdown
	const dropdown = document.getElementById(dropdownId + '-dropdown');
	
	if (dropdown.style.display === 'none' || dropdown.style.display === '') {
		dropdown.style.display = 'block';
	} else {
		dropdown.style.display = 'none';
	}
}

function toggleSubmenu(submenuId) {
	// Prevent default link behavior
	event.preventDefault();
	event.stopPropagation();
	
	// Close all other submenus first
	const allSubmenus = document.querySelectorAll('.submenu-content');
	allSubmenus.forEach(submenu => {
		if (submenu.id !== submenuId + '-submenu') {
			submenu.style.display = 'none';
		}
	});
	
	// Toggle the clicked submenu
	const submenu = document.getElementById(submenuId + '-submenu');
	
	if (submenu) {
		const currentDisplay = submenu.style.display;
		
		if (currentDisplay === 'none' || currentDisplay === '' || currentDisplay === 'none !important') {
			submenu.style.display = 'block';
		} else {
			submenu.style.display = 'none';
		}
	}
}

// Mobile hamburger toggle
document.addEventListener('DOMContentLoaded', function() {
    var btn = document.getElementById('mobile-menu-toggle');
    var sections = document.getElementById('nav-sections');
    if (btn && sections) {
        // default collapsed on mobile; CSS handles initial state
        btn.setAttribute('aria-expanded', 'false');
        btn.addEventListener('click', function() {
            var isActive = sections.classList.toggle('active');
            btn.setAttribute('aria-expanded', isActive ? 'true' : 'false');
        });
    }
});

// Close dropdowns when clicking outside
document.addEventListener('click', function(event) {
	const dropdowns = document.querySelectorAll('.nav-dropdown');
	dropdowns.forEach(dropdown => {
		if (!dropdown.contains(event.target)) {
			const content = dropdown.querySelector('.dropdown-content');
			if (content) {
				content.style.display = 'none';
			}
			// Also close any submenus
			const submenus = dropdown.querySelectorAll('.submenu-content');
			submenus.forEach(submenu => {
				submenu.style.display = 'none';
			});
		}
	});
});

// Keyboard navigation for dropdowns
document.addEventListener('keydown', function(event) {
	if (event.key === 'Escape') {
		const openDropdowns = document.querySelectorAll('.dropdown-content[style*="block"]');
		openDropdowns.forEach(dropdown => {
			dropdown.style.display = 'none';
		});
		// Also close any open submenus
		const openSubmenus = document.querySelectorAll('.submenu-content[style*="block"]');
		openSubmenus.forEach(submenu => {
			submenu.style.display = 'none';
		});
	}
});

// Theme toggle functionality (moved to profile page)
</script>

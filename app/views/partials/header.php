<?php use function App\{can_view_nav, is_organizer}; ?>
<header>
	<nav>
		<div class="nav-container">
            <div class="nav-left" style="display:flex; align-items:center;">
                <button id="mobile-menu-toggle" class="btn btn-secondary btn-sm" aria-label="Toggle navigation">☰</button>
                <a href="/" style="color: white; text-decoration: none; font-weight: bold; margin-left: 10px; margin-right: 20px;">Home</a>
				
				<?php if (!empty($_SESSION['user'])): ?>
                    <div id="nav-sections" style="display:flex; gap:14px;">
                    <!-- Contests Accordion -->
					<?php if (can_view_nav('Contests')): ?>
						<div class="nav-dropdown">
							<a href="#" onclick="toggleDropdown('contests')" style="color: white; text-decoration: none; cursor: pointer;">
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
							<a href="#" onclick="toggleDropdown('users')" style="color: white; text-decoration: none; cursor: pointer;">
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
							<a href="#" onclick="toggleDropdown('admin')" style="color: white; text-decoration: none; cursor: pointer;">
								Admin ▼
							</a>
                            <div id="admin-dropdown" class="dropdown-content" style="display: none;">
								<a href="/admin">Dashboard</a>
								<a href="/admin/settings">Settings</a>
								<a href="/admin/logs">Activity Logs</a>
                                <a href="/admin/judges">Judges</a>
								<a href="/admin/print-reports">Print Reports</a>
								<a href="/admin/templates">Templates</a>
								<a href="/admin/emcee-scripts">Emcee Scripts</a>
							</div>
						</div>
					<?php endif; ?>
					
					<!-- Results Accordion -->
					<?php if (can_view_nav('Results')): ?>
						<div class="nav-dropdown">
							<a href="#" onclick="toggleDropdown('results')" style="color: white; text-decoration: none; cursor: pointer;">
								Results ▼
							</a>
                            <div id="results-dropdown" class="dropdown-content" style="display: none;">
								<?php if (is_organizer()): ?>
									<div style="border-top: 1px solid #ccc; margin: 5px 0;"></div>
									<a href="/results">📋 Complete Results Overview</a>
								<a href="/results/contestants">👤 Contestants</a>
									<a href="/admin/print-reports">🖨️ Print Reports</a>
								<?php endif; ?>
								<?php if (($_SESSION['user']['role'] ?? '') === 'judge'): ?>
									<div style="border-top: 1px solid #ccc; margin: 5px 0;"></div>
									<a href="/results">👀 View My Assigned Results</a>
								<?php endif; ?>
                            </div>
						</div>
					<?php endif; ?>
					
                    <!-- User-specific links (My Profile removed; now under username menu) -->
					
					<?php if (($_SESSION['user']['role'] ?? '') === 'judge' && can_view_nav('My Assignments')): ?>
						<a href="/judge" style="color: white; text-decoration: none; margin-right: 20px;">Judgy Time!</a>
					<?php endif; ?>
					
					<?php if (($_SESSION['user']['role'] ?? '') === 'emcee' && can_view_nav('Contestant Bios')): ?>
						<a href="/emcee" style="color: white; text-decoration: none; margin-right: 20px;">Contestant Bios</a>
                    <?php endif; ?>
                    </div>
				<?php endif; ?>
			</div>
			
			<!-- Right side user info and logout -->
			<div class="nav-right">
                <?php if (!empty($_SESSION['user'])): ?>
                    <div class="nav-dropdown">
                        <a href="#" onclick="toggleDropdown('user-menu')" style="color: white; text-decoration: none; cursor: pointer;">
                            <strong><?= htmlspecialchars($_SESSION['user']['preferred_name'] ?? $_SESSION['user']['name']) ?></strong>
                            <span style="background: #495057; padding: 2px 6px; border-radius: 3px; font-size: 12px; margin-left: 5px;">
                                <?= htmlspecialchars(ucfirst($_SESSION['user']['role'])) ?>
                            </span>
                        </a>
                        <div id="user-menu-dropdown" class="dropdown-content" style="display: none;">
                            <a href="/profile">My Profile</a>
                            <form method="post" action="/logout" style="padding: 0 12px 8px;">
                                <button type="submit" class="btn btn-danger btn-sm" style="width:100%;">Logout</button>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
					<?php if (can_view_nav('Login')): ?>
						<a href="/login" class="btn btn-primary btn-sm">Login</a>
					<?php endif; ?>
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
	
	// Toggle the clicked dropdown
	const dropdown = document.getElementById(dropdownId + '-dropdown');
	
	if (dropdown.style.display === 'none' || dropdown.style.display === '') {
		dropdown.style.display = 'block';
	} else {
		dropdown.style.display = 'none';
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
	}
});

// Theme toggle functionality (moved to profile page)
</script>

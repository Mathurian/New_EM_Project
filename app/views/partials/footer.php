<footer class="site-footer">
	<div class="footer-content">
		<p class="footer-title">
			<strong>Event Manager</strong>
		</p>
		<p class="footer-description">
			Custom contest judging and scoring platform
		</p>
		<div class="footer-bottom">
			<p class="copyright">
				© <?= date('Y') ?> Revna Technology, LLC. All rights reserved.
			</p>
			<?php if (!empty($_SESSION['user']) && ($_SESSION['user']['role'] ?? '') === 'organizer'): ?>
				<p class="admin-links">
					<a href="/admin/settings" class="footer-link">System Settings</a> |
					<a href="/admin/logs" class="footer-link">Activity Logs</a>
				</p>
			<?php endif; ?>
		</div>
	</div>
</footer>

<style>
.site-footer {
	margin-top: 50px;
	padding: 20px;
	background: var(--bg-secondary);
	border-top: 1px solid var(--border-color);
	text-align: center;
	color: var(--text-secondary);
}

.footer-content {
	max-width: 800px;
	margin: 0 auto;
}

.footer-title {
	margin: 0 0 10px 0;
	color: var(--text-primary);
}

.footer-description {
	margin: 0 0 10px 0;
	font-size: 0.9em;
}

.footer-bottom {
	margin-top: 15px;
	font-size: 0.8em;
}

.copyright {
	margin: 0;
}

.admin-links {
	margin: 5px 0 0 0;
}

.footer-link {
	color: var(--text-secondary);
	text-decoration: none;
	transition: color 0.2s;
}

.footer-link:hover {
	color: var(--accent-color);
	text-decoration: underline;
}
</style>

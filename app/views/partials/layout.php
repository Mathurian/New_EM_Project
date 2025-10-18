<?php 
use function App\{render, can_view_nav, csrf_field, url, redirect, param, post, uuid, is_logged_in, is_organizer, is_judge, is_emcee, current_user, require_login, require_organizer, require_emcee, require_judge, back_url, hierarchical_back_url, home_url}; 

// Set proper Content-Type header
if (!headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
}
?>
<!doctype html>
<html>
	<head>
		<meta charset="utf-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1" />
		<title><?= $title ?? 'Event Manager' ?></title>
		<link rel="stylesheet" href="/assets/css/style.css?v=<?= file_exists(__DIR__ . '/../../public/assets/css/style.css') ? filemtime(__DIR__ . '/../../public/assets/css/style.css') : time() ?>">
        <script>
            // Prevent white flash by setting theme immediately
            (function() {
                try {
                    const savedTheme = localStorage.getItem('theme') || 'light';
                    document.documentElement.setAttribute('data-theme', savedTheme);
                } catch (e) { /* ignore */ }
            })();
        </script>
	</head>
	<body>
		<div class="content-wrapper">
			<?php if (!empty($_SESSION['timeout_message'])): ?>
				<div style="background: #f8d7da; color: #721c24; padding: 10px; text-align: center; border-bottom: 1px solid #f5c6cb;">
					<?= htmlspecialchars($_SESSION['timeout_message']) ?>
					<?php unset($_SESSION['timeout_message']); ?>
				</div>
			<?php endif; ?>
			<?php if (is_logged_in()): ?>
				<?php include __DIR__ . '/header.php'; ?>
			<?php endif; ?>
			<?php include __DIR__ . '/user_creation_modal.php'; ?>
            <main class="content-main">
				<?php 
				// Debug: Check what template is being requested
				$templateName = isset($templateName) ? $templateName : (is_string($template) ? $template : 'home');
				$templateFile = __DIR__ . '/../' . $templateName . '.php';
				
				// Debug output (remove this after fixing)
				if (isset($_GET['debug'])) {
					echo "<!-- Debug: template = " . var_export($template ?? 'undefined', true) . " -->";
					echo "<!-- Debug: templateName = " . htmlspecialchars($templateName) . " -->";
					echo "<!-- Debug: templateFile = " . htmlspecialchars($templateFile) . " -->";
					echo "<!-- Debug: file_exists = " . (file_exists($templateFile) ? 'YES' : 'NO') . " -->";
				}
				
				if (file_exists($templateFile)) {
					include $templateFile;
				} else {
					echo '<p>Template not found: ' . htmlspecialchars($templateName) . '</p>';
					if (isset($_GET['debug'])) {
						echo '<p>Debug: Looking for file: ' . htmlspecialchars($templateFile) . '</p>';
					}
				}
				?>
			</main>
			<?php include __DIR__ . '/footer.php'; ?>
		</div>
	</body>
</html>



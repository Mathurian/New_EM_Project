<?php use function App\{render, can_view_nav}; ?>
<!-- Debug: Log what we have in layout -->
<?php 
if (isset($category)) {
	\App\Logger::debug('layout_debug', 'layout', null, 
		"layout.php - category variable: " . json_encode($category));
}
?>
<!doctype html>
<html>
	<head>
		<meta charset="utf-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1" />
		<title><?= $title ?? 'Event Manager' ?></title>
		<link rel="stylesheet" href="/assets/css/style.css" />
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
			<?php include __DIR__ . '/header.php'; ?>
			<?php include __DIR__ . '/user_creation_modal.php'; ?>
            <main class="content-main">
				<?php 
				$templateName = is_string($template) ? $template : 'home';
				$templateFile = __DIR__ . '/../' . $templateName . '.php';
				if (file_exists($templateFile)) {
					include $templateFile;
				} else {
					echo '<p>Template not found: ' . htmlspecialchars($templateName) . '</p>';
				}
				?>
			</main>
			<?php include __DIR__ . '/footer.php'; ?>
		</div>
	</body>
</html>



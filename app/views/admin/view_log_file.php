<?php
$title = 'View Log File: ' . htmlspecialchars($filename);
?>

<div class="container-fluid">
	<div class="row">
		<div class="col-12">
			<div class="card">
				<div class="card-header">
					<h5 class="card-title">📄 Log File: <code><?= htmlspecialchars($filename) ?></code></h5>
					<div class="card-tools">
						<a href="<?= url('admin/log-files') ?>" class="btn btn-sm btn-outline-secondary">← Back to Log Files</a>
						<a href="<?= hierarchical_back_url() ?>" class="btn btn-sm btn-outline-secondary">← Back</a>
						<a href="<?= home_url() ?>" class="btn btn-sm btn-outline-primary">🏠 Home</a>
					</div>
				</div>
				<div class="card-body">
					<div class="row mb-3">
						<div class="col-md-6">
							<p><strong>📂 File:</strong> <code><?= htmlspecialchars($filename) ?></code></p>
							<p><strong>📊 Lines Shown:</strong> Last <?= $lines ?> lines</p>
						</div>
						<div class="col-md-6">
							<div class="btn-group">
								<a href="<?= url('admin/log-files/' . urlencode($filename) . '?lines=50') ?>" class="btn btn-sm btn-outline-primary">Last 50 lines</a>
								<a href="<?= url('admin/log-files/' . urlencode($filename) . '?lines=100') ?>" class="btn btn-sm btn-outline-primary">Last 100 lines</a>
								<a href="<?= url('admin/log-files/' . urlencode($filename) . '?lines=500') ?>" class="btn btn-sm btn-outline-primary">Last 500 lines</a>
								<a href="<?= url('admin/log-files/' . urlencode($filename) . '/download') ?>" class="btn btn-sm btn-success">⬇️ Download Full File</a>
							</div>
						</div>
					</div>

					<?php if (empty($content)): ?>
						<div class="alert alert-warning">
							<strong>⚠️ No content found.</strong> 
							The log file may be empty or not readable.
						</div>
					<?php else: ?>
						<div class="log-content">
							<pre class="bg-dark text-light p-3 rounded" style="max-height: 600px; overflow-y: auto; font-size: 12px; line-height: 1.4;"><?= htmlspecialchars($content) ?></pre>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>
</div>

<style>
.log-content pre {
	font-family: 'Courier New', Courier, monospace;
	white-space: pre-wrap;
	word-wrap: break-word;
}

.log-content pre::-webkit-scrollbar {
	width: 8px;
}

.log-content pre::-webkit-scrollbar-track {
	background: #2d3748;
}

.log-content pre::-webkit-scrollbar-thumb {
	background: #4a5568;
	border-radius: 4px;
}

.log-content pre::-webkit-scrollbar-thumb:hover {
	background: #718096;
}
</style>

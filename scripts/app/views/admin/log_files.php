<?php 
use function App\{url, hierarchical_back_url, home_url}; 
$title = 'Log Files';
?>

<div class="container-fluid">
	<div class="row">
		<div class="col-12">
			<div class="card">
				<div class="card-header">
					<h5 class="card-title">📁 Log Files Management</h5>
					<div class="card-tools">
						<a href="<?= hierarchical_back_url() ?>" class="btn btn-sm btn-outline-secondary">← Back</a>
						<a href="<?= home_url() ?>" class="btn btn-sm btn-outline-primary">🏠 Home</a>
					</div>
				</div>
				<div class="card-body">
					<?php if (!empty($success)): ?>
						<?php if ($success === 'cleanup_complete'): ?>
							<div class="alert alert-success">
								<strong>✅ Log cleanup completed!</strong> 
								Deleted <?= htmlspecialchars($deleted ?? 0) ?> old log files.
							</div>
						<?php endif; ?>
					<?php endif; ?>

					<?php if (!empty($error)): ?>
						<?php 
						$errorMessages = [
							'no_filename' => 'No filename specified.',
							'invalid_filename' => 'Invalid filename format.',
							'file_not_found' => 'Log file not found or not readable.'
						];
						$errorMessage = $errorMessages[$error] ?? 'An error occurred.';
						?>
						<div class="alert alert-danger">
							<strong>❌ Error:</strong> <?= htmlspecialchars($errorMessage) ?>
						</div>
					<?php endif; ?>

					<div class="row mb-3">
						<div class="col-md-6">
							<p><strong>📂 Log Directory:</strong> <code><?= htmlspecialchars($logDirectory) ?></code></p>
							<p><strong>📊 Total Files:</strong> <?= count($fileInfo) ?></p>
						</div>
						<div class="col-md-6">
							<form method="POST" action="<?= url('admin/log-files/cleanup') ?>" class="d-inline">
								<div class="input-group">
									<input type="number" name="days_to_keep" value="30" min="1" max="365" class="form-control" placeholder="Days to keep">
									<button type="submit" class="btn btn-warning" onclick="return confirm('This will permanently delete old log files. Continue?')">
										🗑️ Cleanup Old Files
									</button>
								</div>
							</form>
						</div>
					</div>

					<?php if (empty($fileInfo)): ?>
						<div class="alert alert-info">
							<strong>ℹ️ No log files found.</strong> 
							Log files will be created automatically as the application runs.
						</div>
					<?php else: ?>
						<div class="table-responsive">
							<table class="table table-striped table-hover">
								<thead>
									<tr>
										<th>📄 Filename</th>
										<th>📏 Size</th>
										<th>📅 Modified</th>
										<th>👁️ Actions</th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ($fileInfo as $file): ?>
										<tr>
											<td>
												<code><?= htmlspecialchars($file['filename']) ?></code>
												<?php if (!$file['readable']): ?>
													<span class="badge bg-danger ms-2">Not Readable</span>
												<?php endif; ?>
											</td>
											<td>
												<?php
												$size = $file['size'];
												if ($size >= 1024 * 1024) {
													echo number_format($size / (1024 * 1024), 2) . ' MB';
												} elseif ($size >= 1024) {
													echo number_format($size / 1024, 2) . ' KB';
												} else {
													echo $size . ' bytes';
												}
												?>
											</td>
											<td>
												<?= date('Y-m-d H:i:s', $file['modified']) ?>
												<small class="text-muted d-block">
													<?= $this->timeAgo($file['modified']) ?>
												</small>
											</td>
											<td>
												<?php if ($file['readable']): ?>
													<a href="<?= url('admin/log-files/' . urlencode($file['filename'])) ?>" class="btn btn-sm btn-primary">
														👁️ View
													</a>
													<a href="<?= url('admin/log-files/' . urlencode($file['filename']) . '/download') ?>" class="btn btn-sm btn-success">
														⬇️ Download
													</a>
												<?php else: ?>
													<span class="text-muted">No actions available</span>
												<?php endif; ?>
											</td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>
</div>

<?php
// Helper function for time ago display
function timeAgo($timestamp) {
	$time = time() - $timestamp;
	
	if ($time < 60) return 'just now';
	if ($time < 3600) return floor($time / 60) . ' minutes ago';
	if ($time < 86400) return floor($time / 3600) . ' hours ago';
	if ($time < 2592000) return floor($time / 86400) . ' days ago';
	if ($time < 31536000) return floor($time / 2592000) . ' months ago';
	return floor($time / 31536000) . ' years ago';
}
?>

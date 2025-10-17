<?php use function App\{url, is_organizer, hierarchical_back_url, home_url}; ?>
<h2>Emcee Script Management</h2>
<div class="navigation-buttons">
	<a href="<?= hierarchical_back_url() ?>" class="btn btn-secondary">← Back</a>
	<a href="<?= home_url() ?>" class="btn btn-outline">🏠 Home</a>
</div>

<?php if (!empty($_GET['success'])): ?>
	<?php 
	$successMessages = [
		'script_uploaded' => 'Script uploaded successfully!',
		'script_deleted' => 'Script deleted successfully!',
		'script_toggled' => 'Script status updated successfully!'
	];
	$successMessage = $successMessages[$_GET['success']] ?? 'Operation completed successfully!';
	?>
	<div class="alert alert-success"><?= htmlspecialchars($successMessage) ?></div>
<?php endif; ?>

<?php if (!empty($_GET['error'])): ?>
	<?php 
	$errorMessages = [
		'title_required' => 'Script title is required.',
		'file_upload_failed' => 'File upload failed. Please try again.',
		'invalid_file_type' => 'Invalid file type. Only PDF, TXT, DOC, and DOCX files are allowed.',
		'file_too_large' => 'File is too large. Maximum size is 10MB.',
		'file_save_failed' => 'Failed to save file. Please try again.',
		'script_not_found' => 'Script not found.',
		'script_deleted' => 'Failed to delete script.'
	];
	$errorMessage = $errorMessages[$_GET['error']] ?? 'An error occurred';
	?>
	<div class="alert alert-danger"><?= htmlspecialchars($errorMessage) ?></div>
<?php endif; ?>

<div class="row">
	<div class="col-6">
		<div class="card">
			<div class="card-header">
				<h3>Upload New Script</h3>
			</div>
			<div class="card-body">
				<form method="post" action="<?= url('admin/emcee-scripts') ?>" enctype="multipart/form-data">
					<label for="title">Script Title:</label>
					<input type="text" id="title" name="title" required style="width: 100%; margin-bottom: 15px;">
					
					<label for="description">Description (Optional):</label>
					<textarea id="description" name="description" rows="3" style="width: 100%; margin-bottom: 15px;"></textarea>
					
					<label for="script_file">Script File:</label>
					<input type="file" id="script_file" name="script_file" accept=".pdf,.txt,.doc,.docx" required style="width: 100%; margin-bottom: 15px;">
					<small style="color: #666;">Supported formats: PDF, TXT, DOC, DOCX (Max 10MB)</small>
					
					<button type="submit" class="btn btn-primary">Upload Script</button>
				</form>
			</div>
		</div>
	</div>
	
	<div class="col-6">
		<div class="card">
			<div class="card-header">
				<h3>Script Information</h3>
			</div>
			<div class="card-body">
				<p><strong>Purpose:</strong> Upload scripts that emcees can view during contests.</p>
				<p><strong>Supported Formats:</strong> PDF, TXT, DOC, DOCX</p>
				<p><strong>File Size Limit:</strong> 10MB maximum</p>
				<p><strong>Active Scripts:</strong> Only active scripts are visible to emcees.</p>
			</div>
		</div>
	</div>
</div>

<div class="card mt-4">
	<div class="card-header">
		<h3>Current Scripts</h3>
	</div>
	<div class="card-body">
		<?php if (empty($scripts)): ?>
			<p>No scripts uploaded yet.</p>
		<?php else: ?>
			<table class="table">
				<thead>
					<tr>
						<th>Title</th>
						<th>Description</th>
						<th>File Name</th>
						<th>File Size</th>
						<th>Uploaded By</th>
						<th>Upload Date</th>
						<th>Status</th>
						<th>Actions</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($scripts as $script): ?>
						<tr>
							<td><?= htmlspecialchars($script['title']) ?></td>
							<td><?= htmlspecialchars($script['description'] ?: 'No description') ?></td>
							<td><?= htmlspecialchars($script['file_name']) ?></td>
							<td><?= number_format($script['file_size'] / 1024, 1) ?> KB</td>
							<td><?= htmlspecialchars($script['uploaded_by_name'] ?: 'Unknown') ?></td>
							<td><?= date('M j, Y g:i A', strtotime($script['uploaded_at'])) ?></td>
							<td>
								<span class="badge <?= $script['is_active'] ? 'badge-success' : 'badge-secondary' ?>">
									<?= $script['is_active'] ? 'Active' : 'Inactive' ?>
								</span>
							</td>
							<td>
								<form method="post" action="<?= url('admin/emcee-scripts/' . urlencode($script['id']) . '/toggle') ?>" style="display: inline;">
									<button type="submit" class="btn btn-sm <?= $script['is_active'] ? 'btn-warning' : 'btn-success' ?>">
										<?= $script['is_active'] ? 'Deactivate' : 'Activate' ?>
									</button>
								</form>
								<form method="post" action="<?= url('admin/emcee-scripts/' . urlencode($script['id']) . '/delete') ?>" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this script?')">
									<button type="submit" class="btn btn-sm btn-danger">Delete</button>
								</form>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>
</div>

<style>
.badge {
	padding: 4px 8px;
	border-radius: 4px;
	font-size: 12px;
	font-weight: bold;
}
.badge-success {
	background-color: #28a745;
	color: white;
}
.badge-secondary {
	background-color: #6c757d;
	color: white;
}
</style>

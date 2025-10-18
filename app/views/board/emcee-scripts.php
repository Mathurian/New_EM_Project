<?php use function App\{url, csrf_field}; ?>
<h2>Emcee Scripts Management</h2>
<div class="navigation-buttons">
	<a href="/board" class="btn btn-outline">🏠 Dashboard</a>
</div>

<?php if (isset($_GET['error'])): ?>
	<div class="alert alert-danger">
		<?php if ($_GET['error'] === 'title_required'): ?>
			Title is required.
		<?php elseif ($_GET['error'] === 'file_upload_failed'): ?>
			File upload failed. Please try again.
		<?php elseif ($_GET['error'] === 'file_validation_failed'): ?>
			File validation failed. Please check file type and size.
		<?php elseif ($_GET['error'] === 'file_save_failed'): ?>
			Failed to save file. <?= isset($_GET['details']) ? htmlspecialchars($_GET['details']) : '' ?>
		<?php else: ?>
			An error occurred: <?= htmlspecialchars($_GET['error']) ?>
		<?php endif; ?>
	</div>
<?php endif; ?>

<?php if (isset($_GET['success'])): ?>
	<div class="alert alert-success">
		<?php if ($_GET['success'] === 'script_uploaded'): ?>
			Script uploaded successfully!
		<?php elseif ($_GET['success'] === 'script_activated'): ?>
			Script activated successfully!
		<?php elseif ($_GET['success'] === 'script_deactivated'): ?>
			Script deactivated successfully!
		<?php elseif ($_GET['success'] === 'script_deleted'): ?>
			Script deleted successfully!
		<?php else: ?>
			Operation completed successfully!
		<?php endif; ?>
	</div>
<?php endif; ?>

<div class="scripts-section">
	<div class="section-header">
		<h3>Upload New Script</h3>
	</div>
	
	<form method="POST" action="/board/emcee-scripts" enctype="multipart/form-data" class="upload-form">
		<?= csrf_field() ?>
		<div class="form-group">
			<label for="title">Script Title:</label>
			<input type="text" id="title" name="title" required class="form-control">
		</div>
		<div class="form-group">
			<label for="description">Description (Optional):</label>
			<textarea id="description" name="description" rows="3" class="form-control"></textarea>
		</div>
		<div class="form-group">
			<label for="script_file">Script File:</label>
			<input type="file" id="script_file" name="script_file" accept=".pdf,.doc,.docx,.txt" required class="form-control">
			<small class="form-text">Accepted formats: PDF, DOC, DOCX, TXT (Max 10MB)</small>
		</div>
		<button type="submit" class="btn btn-primary">Upload Script</button>
	</form>
</div>

<div class="scripts-section">
	<div class="section-header">
		<h3>Existing Scripts</h3>
	</div>
	
	<?php if (empty($scripts)): ?>
		<p>No scripts uploaded yet.</p>
	<?php else: ?>
		<div class="scripts-table">
			<table>
				<thead>
					<tr>
						<th>Title</th>
						<th>Filename</th>
						<th>Uploaded By</th>
						<th>Created</th>
						<th>Status</th>
						<th>Actions</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($scripts as $script): ?>
						<tr>
							<td><?= htmlspecialchars($script['title']) ?></td>
							<td><?= htmlspecialchars($script['filename']) ?></td>
							<td><?= htmlspecialchars($script['uploaded_by_name'] ?? 'Unknown') ?></td>
							<td><?= date('M j, Y', strtotime($script['created_at'])) ?></td>
							<td>
								<?php if ($script['is_active']): ?>
									<span class="status-active">✅ Active</span>
								<?php else: ?>
									<span class="status-inactive">❌ Inactive</span>
								<?php endif; ?>
							</td>
							<td>
								<div class="action-buttons">
									<form method="POST" action="/board/emcee-scripts/<?= $script['id'] ?>/toggle" style="display: inline;">
										<?= csrf_field() ?>
										<button type="submit" class="btn btn-sm <?= $script['is_active'] ? 'btn-warning' : 'btn-success' ?>">
											<?= $script['is_active'] ? 'Deactivate' : 'Activate' ?>
										</button>
									</form>
									<form method="POST" action="/board/emcee-scripts/<?= $script['id'] ?>/delete" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this script?')">
										<?= csrf_field() ?>
										<button type="submit" class="btn btn-sm btn-danger">Delete</button>
									</form>
								</div>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>
</div>

<style>
.alert {
	padding: 12px 16px;
	margin: 20px 0;
	border-radius: 4px;
	border: 1px solid;
}

.alert-danger {
	background-color: #f8d7da;
	border-color: #f5c6cb;
	color: #721c24;
}

.alert-success {
	background-color: #d4edda;
	border-color: #c3e6cb;
	color: #155724;
}

.scripts-section {
	margin: 30px 0;
	padding: 20px;
	background: white;
	border-radius: 8px;
	box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.section-header h3 {
	margin: 0 0 20px 0;
	color: #333;
	border-bottom: 2px solid #007bff;
	padding-bottom: 10px;
}

.upload-form {
	max-width: 500px;
}

.form-group {
	margin-bottom: 15px;
}

.form-group label {
	display: block;
	margin-bottom: 5px;
	font-weight: bold;
	color: #333;
}

.form-control {
	width: 100%;
	padding: 8px 12px;
	border: 1px solid #dee2e6;
	border-radius: 4px;
	font-size: 14px;
}

.form-text {
	font-size: 12px;
	color: #666;
	margin-top: 5px;
}

.btn {
	padding: 8px 16px;
	border: none;
	border-radius: 4px;
	cursor: pointer;
	text-decoration: none;
	display: inline-block;
	font-size: 14px;
	transition: background-color 0.2s;
}

.btn-primary {
	background: #007bff;
	color: white;
}

.btn-primary:hover {
	background: #0056b3;
}

.btn-sm {
	padding: 4px 8px;
	font-size: 12px;
}

.btn-success {
	background: #28a745;
	color: white;
}

.btn-warning {
	background: #ffc107;
	color: #212529;
}

.btn-danger {
	background: #dc3545;
	color: white;
}

.action-buttons {
	display: flex;
	gap: 5px;
}

.scripts-table {
	overflow-x: auto;
	margin-top: 20px;
}

.scripts-table table {
	width: 100%;
	border-collapse: collapse;
	background: white;
	border-radius: 8px;
	overflow: hidden;
}

.scripts-table th,
.scripts-table td {
	padding: 12px 15px;
	text-align: left;
	border-bottom: 1px solid #dee2e6;
}

.scripts-table th {
	background: #f8f9fa;
	font-weight: bold;
	color: #495057;
}

.scripts-table tr:hover {
	background: #f8f9fa;
}

.status-active {
	color: #28a745;
	font-weight: bold;
}

.status-inactive {
	color: #dc3545;
	font-weight: bold;
}

@media (max-width: 768px) {
	.scripts-table {
		font-size: 0.9em;
	}
	
	.action-buttons {
		flex-direction: column;
		gap: 2px;
	}
}
</style>

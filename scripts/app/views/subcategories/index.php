<?php use function App\{is_organizer, url}; ?>
<h2>Subcategories for <?= htmlspecialchars($category['name']) ?></h2>
<p><a href="<?= url('contests/' . urlencode($category['contest_id'] ?? '') . '/categories') ?>">Back</a></p>

<?php if (!empty($_SESSION['success_message'])): ?>
	<p style="color: green; font-weight: bold;"><?= htmlspecialchars($_SESSION['success_message']) ?></p>
	<?php unset($_SESSION['success_message']); ?>
<?php endif; ?>

<?php if (!empty($_GET['success'])): ?>
	<?php 
	$successMessages = [
		'subcategories_deleted' => 'Selected subcategories deleted successfully!',
		'subcategories_updated' => 'Subcategories updated successfully!'
	];
	$successMessage = $successMessages[$_GET['success']] ?? 'Operation completed successfully!';
	?>
	<p style="color: green; font-weight: bold;"><?= htmlspecialchars($successMessage) ?></p>
<?php endif; ?>

<?php if (!empty($_GET['error'])): ?>
	<?php 
	$errorMessages = [
		'no_subcategories_selected' => 'No subcategories selected for operation',
		'delete_failed' => 'Failed to delete subcategories',
		'update_failed' => 'Failed to update subcategories',
		'no_updates' => 'No updates provided'
	];
	$errorMessage = $errorMessages[$_GET['error']] ?? 'An error occurred';
	?>
	<p style="color: red; font-weight: bold;"><?= htmlspecialchars($errorMessage) ?></p>
<?php endif; ?>

<?php if (is_organizer()): ?>
	<div style="margin-bottom: 20px;">
		<a href="<?= url('categories/' . urlencode($category['id']) . '/subcategories/new') ?>">+ New Subcategory</a> | 
		<a href="<?= url('categories/' . urlencode($category['id']) . '/subcategories/templates') ?>">+ From Template</a> | 
		<a href="<?= url('categories/' . urlencode($category['id']) . '/assign') ?>">Category Assignments</a>
		
		<form method="post" action="<?= url('categories/' . urlencode($category['id']) . '/subcategories/bulk-delete') ?>" style="display: inline-block; margin-left: 20px;" id="bulkDeleteForm">
			<button type="submit" onclick="return confirm('Are you sure you want to delete the selected subcategories? This will also delete all associated scores, criteria, and assignments.')" disabled id="bulkDeleteBtn">
				Delete Selected
			</button>
		</form>
		
		<form method="post" action="<?= url('categories/' . urlencode($category['id']) . '/subcategories/bulk-update') ?>" style="display: inline-block; margin-left: 10px;" id="bulkUpdateForm">
			<button type="submit" onclick="return confirm('Are you sure you want to update the selected subcategories?')" disabled id="bulkUpdateBtn">
				Update Selected
			</button>
		</form>
	</div>
<?php endif; ?>

<table style="width: 100%; border-collapse: collapse;">
	<tr style="background: #f8f9fa;">
		<?php if (is_organizer()): ?><th style="border: 1px solid #dee2e6; padding: 8px;"><input type="checkbox" id="selectAll" /></th><?php endif; ?>
		<th style="border: 1px solid #dee2e6; padding: 8px;">Name</th>
		<th style="border: 1px solid #dee2e6; padding: 8px;">Description</th>
		<th style="border: 1px solid #dee2e6; padding: 8px;">Score Cap</th>
		<th style="border: 1px solid #dee2e6; padding: 8px;">Actions</th>
	</tr>
	<?php foreach ($subcategories as $sc): ?>
		<tr>
			<?php if (is_organizer()): ?>
				<td style="border: 1px solid #dee2e6; padding: 8px;"><input type="checkbox" class="subcategory-checkbox" value="<?= htmlspecialchars($sc['id']) ?>" /></td>
			<?php endif; ?>
			<td style="border: 1px solid #dee2e6; padding: 8px;">
				<?php if (is_organizer()): ?>
					<input type="text" name="updates[<?= htmlspecialchars($sc['id']) ?>][name]" 
						   value="<?= htmlspecialchars($sc['name']) ?>" 
						   class="subcategory-name-input" data-subcategory-id="<?= htmlspecialchars($sc['id']) ?>" />
				<?php else: ?>
					<?= htmlspecialchars($sc['name']) ?>
				<?php endif; ?>
			</td>
			<td style="border: 1px solid #dee2e6; padding: 8px;">
				<?php if (is_organizer()): ?>
					<input type="text" name="updates[<?= htmlspecialchars($sc['id']) ?>][description]" 
						   value="<?= htmlspecialchars($sc['description'] ?? '') ?>" 
						   class="subcategory-description-input" data-subcategory-id="<?= htmlspecialchars($sc['id']) ?>" />
				<?php else: ?>
					<?= htmlspecialchars($sc['description'] ?? '') ?>
				<?php endif; ?>
			</td>
			<td style="border: 1px solid #dee2e6; padding: 8px;">
				<?php if (is_organizer()): ?>
					<input type="number" name="updates[<?= htmlspecialchars($sc['id']) ?>][score_cap]" 
						   value="<?= htmlspecialchars($sc['score_cap'] ?? '') ?>" min="1" step="1" 
						   class="subcategory-score-cap-input" data-subcategory-id="<?= htmlspecialchars($sc['id']) ?>" />
				<?php else: ?>
					<?= htmlspecialchars($sc['score_cap'] ?? '-') ?>
				<?php endif; ?>
			</td>
			<td style="border: 1px solid #dee2e6; padding: 8px;">
				<a href="<?= url('subcategories/' . urlencode($sc['id']) . '/assign') ?>">Assign</a> |
				<a href="<?= url('subcategories/' . urlencode($sc['id']) . '/criteria') ?>">Criteria</a> |
				<a href="<?= url('score/' . urlencode($sc['id'])) ?>">Score</a> |
				<a href="<?= url('results/' . urlencode($sc['id'])) ?>">Results</a> |
				<a href="<?= url('results/' . urlencode($sc['id']) . '/detailed') ?>">Detailed</a>
				<?php if (is_organizer()): ?>
					| <a href="<?= url('subcategories/' . urlencode($sc['id']) . '/admin') ?>">Admin</a>
					| <form method="post" action="<?= url('categories/' . urlencode($category['id']) . '/subcategories/bulk-delete') ?>" style="display: inline-block;">
						<input type="hidden" name="subcategory_ids[]" value="<?= htmlspecialchars($sc['id']) ?>" />
						<button type="submit" onclick="return confirm('Are you sure you want to delete this subcategory?')" style="background: #dc3545; color: white; border: none; padding: 2px 6px; border-radius: 3px; font-size: 11px;">
							Delete
						</button>
					</form>
				<?php endif; ?>
			</td>
		</tr>
	<?php endforeach; ?>
</table>

<?php if (is_organizer()): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
	const selectAllCheckbox = document.getElementById('selectAll');
	const subcategoryCheckboxes = document.querySelectorAll('.subcategory-checkbox');
	const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
	const bulkUpdateBtn = document.getElementById('bulkUpdateBtn');
	const bulkDeleteForm = document.getElementById('bulkDeleteForm');
	const bulkUpdateForm = document.getElementById('bulkUpdateForm');
	
	// Select all functionality
	selectAllCheckbox.addEventListener('change', function() {
		subcategoryCheckboxes.forEach(checkbox => {
			checkbox.checked = this.checked;
		});
		updateBulkButtons();
	});
	
	// Individual checkbox change
	subcategoryCheckboxes.forEach(checkbox => {
		checkbox.addEventListener('change', function() {
			updateBulkButtons();
			updateSelectAllState();
		});
	});
	
	function updateBulkButtons() {
		const selectedCount = document.querySelectorAll('.subcategory-checkbox:checked').length;
		bulkDeleteBtn.disabled = selectedCount === 0;
		bulkUpdateBtn.disabled = selectedCount === 0;
	}
	
	function updateSelectAllState() {
		const totalCheckboxes = subcategoryCheckboxes.length;
		const checkedCheckboxes = document.querySelectorAll('.subcategory-checkbox:checked').length;
		selectAllCheckbox.checked = checkedCheckboxes === totalCheckboxes;
		selectAllCheckbox.indeterminate = checkedCheckboxes > 0 && checkedCheckboxes < totalCheckboxes;
	}
	
	// Bulk delete form submission
	bulkDeleteForm.addEventListener('submit', function(e) {
		const selectedCheckboxes = document.querySelectorAll('.subcategory-checkbox:checked');
		if (selectedCheckboxes.length === 0) {
			e.preventDefault();
			alert('Please select at least one subcategory to delete.');
			return;
		}
		
		// Add selected subcategory IDs to form
		selectedCheckboxes.forEach(checkbox => {
			const hiddenInput = document.createElement('input');
			hiddenInput.type = 'hidden';
			hiddenInput.name = 'subcategory_ids[]';
			hiddenInput.value = checkbox.value;
			this.appendChild(hiddenInput);
		});
	});
	
	// Bulk update form submission
	bulkUpdateForm.addEventListener('submit', function(e) {
		const selectedCheckboxes = document.querySelectorAll('.subcategory-checkbox:checked');
		if (selectedCheckboxes.length === 0) {
			e.preventDefault();
			alert('Please select at least one subcategory to update.');
			return;
		}
		
		// Add updates for selected subcategories
		selectedCheckboxes.forEach(checkbox => {
			const subcategoryId = checkbox.value;
			const nameInput = document.querySelector(`input[data-subcategory-id="${subcategoryId}"].subcategory-name-input`);
			const descriptionInput = document.querySelector(`input[data-subcategory-id="${subcategoryId}"].subcategory-description-input`);
			const scoreCapInput = document.querySelector(`input[data-subcategory-id="${subcategoryId}"].subcategory-score-cap-input`);
			
			if (nameInput) {
				const hiddenInput = document.createElement('input');
				hiddenInput.type = 'hidden';
				hiddenInput.name = `updates[${subcategoryId}][name]`;
				hiddenInput.value = nameInput.value;
				this.appendChild(hiddenInput);
			}
			if (descriptionInput) {
				const hiddenInput = document.createElement('input');
				hiddenInput.type = 'hidden';
				hiddenInput.name = `updates[${subcategoryId}][description]`;
				hiddenInput.value = descriptionInput.value;
				this.appendChild(hiddenInput);
			}
			if (scoreCapInput) {
				const hiddenInput = document.createElement('input');
				hiddenInput.type = 'hidden';
				hiddenInput.name = `updates[${subcategoryId}][score_cap]`;
				hiddenInput.value = scoreCapInput.value;
				this.appendChild(hiddenInput);
			}
		});
	});
});
</script>
<?php endif; ?>



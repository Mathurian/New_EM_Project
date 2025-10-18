<?php use function App\{url, is_organizer}; ?>
<h2>Criteria for <?= htmlspecialchars($subcategory['name']) ?></h2>
<p><a href="<?= url('categories/' . urlencode($subcategory['category_id'] ?? '') . '/subcategories') ?>">Back</a></p>

<?php if (!empty($_GET['success'])): ?>
	<?php 
	$successMessages = [
		'criteria_deleted' => 'Selected criteria deleted successfully!',
		'criteria_updated' => 'Criteria updated successfully!'
	];
	$successMessage = $successMessages[$_GET['success']] ?? 'Operation completed successfully!';
	?>
	<p style="color: green; font-weight: bold;"><?= htmlspecialchars($successMessage) ?></p>
<?php endif; ?>

<?php if (!empty($_GET['error'])): ?>
	<?php 
	$errorMessages = [
		'no_criteria_selected' => 'No criteria selected for operation',
		'delete_failed' => 'Failed to delete criteria',
		'update_failed' => 'Failed to update criteria',
		'no_updates' => 'No updates provided'
	];
	$errorMessage = $errorMessages[$_GET['error']] ?? 'An error occurred';
	?>
	<p style="color: red; font-weight: bold;"><?= htmlspecialchars($errorMessage) ?></p>
<?php endif; ?>

<?php if (is_organizer()): ?>
	<div style="margin-bottom: 20px;">
		<a href="<?= url('subcategories/' . urlencode($subcategory['id']) . '/criteria/new') ?>">+ New Criterion</a>
		
		<form method="post" action="<?= url('subcategories/' . urlencode($subcategory['id']) . '/criteria/bulk-delete') ?>" style="display: inline-block; margin-left: 20px;" id="bulkDeleteForm">
			<button type="submit" onclick="return confirm('Are you sure you want to delete the selected criteria?')" disabled id="bulkDeleteBtn">
				Delete Selected
			</button>
		</form>
		
		<form method="post" action="<?= url('subcategories/' . urlencode($subcategory['id']) . '/criteria/bulk-update') ?>" style="display: inline-block; margin-left: 10px;" id="bulkUpdateForm">
			<button type="submit" onclick="return confirm('Are you sure you want to update the selected criteria?')" disabled id="bulkUpdateBtn">
				Update Selected
			</button>
		</form>
	</div>
<?php endif; ?>

<table>
	<tr>
		<?php if (is_organizer()): ?><th><input type="checkbox" id="selectAll" /></th><?php endif; ?>
		<th>Name</th>
		<th>Max Score</th>
		<?php if (is_organizer()): ?><th>Actions</th><?php endif; ?>
	</tr>
	<?php foreach ($rows as $r): ?>
		<tr>
			<?php if (is_organizer()): ?>
				<td><input type="checkbox" class="criterion-checkbox" value="<?= htmlspecialchars($r['id']) ?>" /></td>
			<?php endif; ?>
			<td><?= htmlspecialchars($r['name']) ?></td>
			<td>
				<?php if (is_organizer()): ?>
					<input type="number" name="updates[<?= htmlspecialchars($r['id']) ?>][max_score]" 
						   value="<?= htmlspecialchars($r['max_score']) ?>" min="1" step="1" 
						   class="max-score-input" data-criterion-id="<?= htmlspecialchars($r['id']) ?>" />
				<?php else: ?>
					<?= htmlspecialchars($r['max_score']) ?>
				<?php endif; ?>
			</td>
			<?php if (is_organizer()): ?>
				<td>
					<form method="post" action="<?= url('subcategories/' . urlencode($subcategory['id']) . '/criteria/bulk-delete') ?>" style="display: inline-block;">
						<input type="hidden" name="criteria_ids[]" value="<?= htmlspecialchars($r['id']) ?>" />
						<button type="submit" onclick="return confirm('Are you sure you want to delete this criterion?')" style="background: #dc3545; color: white; border: none; padding: 2px 6px; border-radius: 3px; font-size: 11px;">
							Delete
						</button>
					</form>
				</td>
			<?php endif; ?>
		</tr>
	<?php endforeach; ?>
</table>

<?php if (is_organizer()): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
	const selectAllCheckbox = document.getElementById('selectAll');
	const criterionCheckboxes = document.querySelectorAll('.criterion-checkbox');
	const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
	const bulkUpdateBtn = document.getElementById('bulkUpdateBtn');
	const bulkDeleteForm = document.getElementById('bulkDeleteForm');
	const bulkUpdateForm = document.getElementById('bulkUpdateForm');
	
	// Select all functionality
	selectAllCheckbox.addEventListener('change', function() {
		criterionCheckboxes.forEach(checkbox => {
			checkbox.checked = this.checked;
		});
		updateBulkButtons();
	});
	
	// Individual checkbox change
	criterionCheckboxes.forEach(checkbox => {
		checkbox.addEventListener('change', function() {
			updateBulkButtons();
			updateSelectAllState();
		});
	});
	
	function updateBulkButtons() {
		const selectedCount = document.querySelectorAll('.criterion-checkbox:checked').length;
		bulkDeleteBtn.disabled = selectedCount === 0;
		bulkUpdateBtn.disabled = selectedCount === 0;
	}
	
	function updateSelectAllState() {
		const totalCheckboxes = criterionCheckboxes.length;
		const checkedCheckboxes = document.querySelectorAll('.criterion-checkbox:checked').length;
		selectAllCheckbox.checked = checkedCheckboxes === totalCheckboxes;
		selectAllCheckbox.indeterminate = checkedCheckboxes > 0 && checkedCheckboxes < totalCheckboxes;
	}
	
	// Bulk delete form submission
	bulkDeleteForm.addEventListener('submit', function(e) {
		const selectedCheckboxes = document.querySelectorAll('.criterion-checkbox:checked');
		if (selectedCheckboxes.length === 0) {
			e.preventDefault();
			alert('Please select at least one criterion to delete.');
			return;
		}
		
		// Add selected criteria IDs to form
		selectedCheckboxes.forEach(checkbox => {
			const hiddenInput = document.createElement('input');
			hiddenInput.type = 'hidden';
			hiddenInput.name = 'criteria_ids[]';
			hiddenInput.value = checkbox.value;
			this.appendChild(hiddenInput);
		});
	});
	
	// Bulk update form submission
	bulkUpdateForm.addEventListener('submit', function(e) {
		const selectedCheckboxes = document.querySelectorAll('.criterion-checkbox:checked');
		if (selectedCheckboxes.length === 0) {
			e.preventDefault();
			alert('Please select at least one criterion to update.');
			return;
		}
		
		// Add updates for selected criteria
		selectedCheckboxes.forEach(checkbox => {
			const criterionId = checkbox.value;
			const maxScoreInput = document.querySelector(`input[data-criterion-id="${criterionId}"]`);
			if (maxScoreInput) {
				const hiddenInput = document.createElement('input');
				hiddenInput.type = 'hidden';
				hiddenInput.name = `updates[${criterionId}][max_score]`;
				hiddenInput.value = maxScoreInput.value;
				this.appendChild(hiddenInput);
			}
		});
	});
});
</script>
<?php endif; ?>



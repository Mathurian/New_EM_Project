<?php use function App\{url, csrf_field, current_user}; ?>
<div class="container">
	<h1>Certification Management</h1>
	<p>Review and certify totals once all judges have submitted and signed their scores.</p>
	
	<?php if (!empty($_GET['success'])): ?>
		<?php 
		$successMessages = [
			'totals_certified' => 'Totals have been successfully certified!'
		];
		$successMessage = $successMessages[$_GET['success']] ?? 'Operation completed successfully.';
		?>
		<div class="alert alert-success" style="background: #d1edff; border: 1px solid #74c0fc; padding: 15px; border-radius: 5px; margin: 20px 0;">
			<h4>✅ Success!</h4>
			<p><?= htmlspecialchars($successMessage) ?></p>
		</div>
	<?php endif; ?>
	
	<?php if (!empty($_GET['error'])): ?>
		<?php 
		$errorMessages = [
			'missing_fields' => 'Please fill in all required fields.',
			'signature_mismatch' => 'Signature does not match your name. Please use your preferred name or full name.',
			'judges_not_certified' => 'Cannot certify totals until all judges have certified their scores for this subcategory.'
		];
		$errorMessage = $errorMessages[$_GET['error']] ?? 'An error occurred. Please try again.';
		?>
		<div class="alert alert-danger" style="background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; margin: 20px 0;">
			<h4>❌ Error</h4>
			<p><?= htmlspecialchars($errorMessage) ?></p>
		</div>
	<?php endif; ?>
	
	<?php if (empty($certificationData)): ?>
		<div class="alert alert-info" style="background: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; border-radius: 5px; margin: 20px 0;">
			<h4>No Subcategories Found</h4>
			<p>No subcategories are available for certification. Subcategories will appear here once they are created and have judges assigned.</p>
		</div>
	<?php else: ?>
		<div class="certification-grid" style="display: grid; gap: 20px; margin: 20px 0;">
			<?php foreach ($certificationData as $item): ?>
				<div class="certification-card" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
					<h3><?= htmlspecialchars($item['contest_name']) ?></h3>
					<h4><?= htmlspecialchars($item['category_name']) ?> - <?= htmlspecialchars($item['subcategory_name']) ?></h4>
					
					<div class="certification-status" style="margin: 15px 0;">
						<div class="status-item" style="margin-bottom: 10px;">
							<strong>Judge Certifications:</strong> 
							<span style="color: <?= $item['judges_certified'] == $item['total_judges'] ? '#198754' : '#dc3545'; ?>">
								<?= $item['judges_certified'] ?> / <?= $item['total_judges'] ?>
							</span>
						</div>
						
						<?php if ($item['tally_master_signature']): ?>
							<div class="status-item" style="margin-bottom: 10px;">
								<strong>Tally Master Certification:</strong> 
								<span style="color: #198754;">✅ <?= htmlspecialchars($item['tally_master_signature']) ?></span>
								<br><small>Certified on <?= date('M j, Y g:i A', strtotime($item['tally_master_certified_at'])) ?></small>
							</div>
						<?php else: ?>
							<div class="status-item" style="margin-bottom: 10px;">
								<strong>Tally Master Certification:</strong> 
								<span style="color: #dc3545;">❌ Not Certified</span>
							</div>
						<?php endif; ?>
					</div>
					
					<?php if (!$item['tally_master_signature'] && $item['judges_certified'] == $item['total_judges'] && $item['total_judges'] > 0): ?>
						<div class="certification-form" style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin-top: 15px;">
							<h5>Certify Totals</h5>
							<p><small>All judges have certified their scores. You may now certify the totals for this subcategory.</small></p>
							
							<form method="post" action="<?= url('tally-master/certify-totals') ?>" style="margin-top: 10px;">
								<?= csrf_field() ?>
								<input type="hidden" name="subcategory_id" value="<?= htmlspecialchars($item['subcategory_id']) ?>">
								
								<div style="margin-bottom: 10px;">
									<label for="signature_<?= $item['subcategory_id'] ?>" style="display: block; margin-bottom: 5px;">
										<strong>Signature:</strong>
									</label>
									<input 
										type="text" 
										id="signature_<?= $item['subcategory_id'] ?>" 
										name="signature_name" 
										value="<?= htmlspecialchars(current_user()['preferred_name'] ?? current_user()['name']) ?>"
										required 
										style="width: 100%; padding: 8px; border: 1px solid #ced4da; border-radius: 4px;"
									>
									<small style="color: #6c757d;">Use your preferred name or full name as it appears in your profile.</small>
								</div>
								
								<button type="submit" class="btn btn-success" style="background: #198754; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer;">
									✅ Certify Totals
								</button>
							</form>
						</div>
					<?php elseif (!$item['tally_master_signature']): ?>
						<div class="certification-pending" style="background: #fff3cd; padding: 15px; border-radius: 5px; margin-top: 15px;">
							<h5>⏳ Pending Judge Certifications</h5>
							<p><small>Cannot certify totals until all judges have certified their scores for this subcategory.</small></p>
						</div>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
	
	<div class="action-buttons" style="margin: 20px 0; text-align: center;">
		<a href="<?= url('tally-master') ?>" class="btn btn-secondary">← Back to Dashboard</a>
		<a href="<?= url('tally-master/score-review') ?>" class="btn btn-primary">Review Scores</a>
	</div>
</div>

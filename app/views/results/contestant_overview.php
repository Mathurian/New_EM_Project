<?php use function App\{url, is_organizer, is_judge}; ?>
<h2>Contestant Overview</h2>
<p><a href="<?= url('results/contestants') ?>" class="btn btn-secondary">← Back to Contestants</a></p>

<?php if (!empty($_SESSION['success_message'])): ?>
	<p style="color: green; font-weight: bold;"><?= htmlspecialchars($_SESSION['success_message']) ?></p>
	<?php unset($_SESSION['success_message']); ?>
<?php endif; ?>

<div class="card" style="padding:12px; margin-bottom:12px;">
	<strong>Name:</strong> <?= htmlspecialchars($contestant['name']) ?>
	<?php if (!empty($contestant['contestant_number'])): ?>
		&nbsp;&nbsp;<strong>#:</strong> <?= htmlspecialchars($contestant['contestant_number']) ?>
	<?php endif; ?>
</div>

<?php if (empty($subcategories)): ?>
	<p>No subcategories for this contestant.</p>
<?php else: ?>
	<table style="width:100%; border-collapse: collapse;">
		<tr style="background:#f8f9fa;">
			<th style="text-align:left; padding:8px; border-bottom:1px solid #ddd;">Category</th>
			<th style="text-align:left; padding:8px; border-bottom:1px solid #ddd;">Subcategory</th>
			<th style="text-align:right; padding:8px; border-bottom:1px solid #ddd;">Total</th>
			<th style="text-align:right; padding:8px; border-bottom:1px solid #ddd;">Deductions</th>
			<th style="text-align:right; padding:8px; border-bottom:1px solid #ddd;">Net</th>
			<th style="padding:8px; border-bottom:1px solid #ddd;">Actions</th>
		</tr>
		<?php 
		$overall = 0; $overallD = 0; $overallNet = 0;
		foreach ($subcategories as $sub): 
			$subTotal = 0; 
			foreach ($scores as $s) { if ($s['subcategory_id'] === $sub['id']) { $subTotal += (float)$s['score']; } }
			$ded = (float)($deductions[$sub['id']] ?? 0);
			$net = $subTotal - $ded;
			$overall += $subTotal; $overallD += $ded; $overallNet += $net;
		?>
		<tr>
			<td style="padding:8px; border-bottom:1px solid #f0f0f0;"><?= htmlspecialchars($sub['category_name']) ?></td>
			<td style="padding:8px; border-bottom:1px solid #f0f0f0;"><?= htmlspecialchars($sub['name']) ?></td>
			<td style="padding:8px; border-bottom:1px solid #f0f0f0; text-align:right;"><?= number_format($subTotal, 2) ?></td>
			<td style="padding:8px; border-bottom:1px solid #f0f0f0; text-align:right;">-<?= number_format($ded, 2) ?></td>
			<td style="padding:8px; border-bottom:1px solid #f0f0f0; text-align:right; font-weight:bold;"><?= number_format($net, 2) ?></td>
			<td style="padding:8px; border-bottom:1px solid #f0f0f0;">
				<?php if (is_organizer() || is_judge()): ?>
					<form method="post" action="<?= url('results/contestants/' . urlencode($contestant['id']) . '/subcategory/' . urlencode($sub['id']) . '/deduction') ?>" style="display:inline-block;">
						<input type="number" name="amount" step="0.01" placeholder="Deduction" required>
						<input type="text" name="comment" placeholder="Comment" required>
						<input type="text" name="signature" placeholder="Sign (preferred name)" required>
						<button type="submit" class="btn btn-secondary">Apply</button>
					</form>
				<?php endif; ?>
			</td>
		</tr>
		<?php endforeach; ?>
		<tr style="background:#f8f9fa; font-weight:bold;">
			<td colspan="2" style="padding:8px;">Overall</td>
			<td style="padding:8px; text-align:right;"><?= number_format($overall, 2) ?></td>
			<td style="padding:8px; text-align:right;">-<?= number_format($overallD, 2) ?></td>
			<td style="padding:8px; text-align:right;"><?= number_format($overallNet, 2) ?></td>
			<td></td>
		</tr>
	</table>
<?php endif; ?>

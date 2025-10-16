<?php use function App\{url, is_organizer, is_judge}; ?>
<h2>Detailed Results: <?= htmlspecialchars($subcategory['category_name']) ?> - <?= htmlspecialchars($subcategory['name']) ?></h2>
<p><a href="<?= url('results') ?>">Back to Results</a></p>

<?php if (!empty($_SESSION['success_message'])): ?>
	<p style="color: green; font-weight: bold;"><?= htmlspecialchars($_SESSION['success_message']) ?></p>
	<?php unset($_SESSION['success_message']); ?>
<?php endif; ?>

<?php if (is_organizer()): ?>
	<div style="margin-bottom: 20px; padding: 10px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px;">
		<h3>Admin Actions</h3>
		<p><strong>Unsign by Judge:</strong></p>
		<?php foreach ($judges as $judge): ?>
			<form method="post" action="<?= url('results/judge/' . urlencode($judge['id']) . '/unsign-all') ?>" style="display: inline-block; margin-right: 10px;">
				<button type="submit" onclick="return confirm('Are you sure you want to unsign ALL scores for <?= htmlspecialchars($judge['name']) ?>? This will unlock all certified scores for editing.')" style="background: #dc3545; color: white; border: none; padding: 3px 8px; border-radius: 3px; font-size: 12px;">
					Unsign <?= htmlspecialchars($judge['name']) ?>
				</button>
			</form>
		<?php endforeach; ?>
	</div>
<?php endif; ?>

<?php if (empty($contestants)): ?>
	<p>No contestants assigned to this subcategory.</p>
<?php elseif (empty($criteria)): ?>
	<p>No criteria defined for this subcategory.</p>
<?php else: ?>
	<table border="1" cellpadding="5" cellspacing="0">
		<tr>
			<th rowspan="2">Contestant</th>
			<?php foreach ($criteria as $criterion): ?>
				<th colspan="<?= count($judges) ?>"><?= htmlspecialchars($criterion['name']) ?> (Max: <?= htmlspecialchars($criterion['max_score']) ?>)</th>
			<?php endforeach; ?>
			<th rowspan="2">Total</th>
			<th rowspan="2">Comments</th>
		</tr>
		<tr>
            <?php foreach ($criteria as $criterion): ?>
                <?php foreach ($judges as $judge): ?>
                    <th><?= htmlspecialchars($judge['name']) ?></th>
                <?php endforeach; ?>
            <?php endforeach; ?>
		</tr>
		<?php foreach ($contestants as $contestant): ?>
			<tr>
				<td><?= htmlspecialchars($contestant['name']) ?></td>
                <?php 
                $contestantTotal = 0;
                foreach ($criteria as $criterion): 
                    foreach ($judges as $judge): 
                        $score = '';
                        foreach ($scores as $s) {
                            if ($s['contestant_id'] === $contestant['id'] && 
                                $s['criterion_id'] === $criterion['id'] && 
                                $s['judge_id'] === $judge['id']) {
                                $score = $s['score'];
                                $contestantTotal += (float)$s['score'];
                                break;
                            }
                        }
                ?>
                    <td style="text-align: center;"><?= htmlspecialchars($score) ?></td>
                <?php endforeach; endforeach; ?>
                <?php $deductionTotal = (float)($deductionsByContestant[$contestant['id']]['total'] ?? 0); ?>
                <td style="text-align: center; font-weight: bold;"><?= number_format($contestantTotal - $deductionTotal, 2) ?></td>
				<td>
					<?php 
					$contestantComments = array_filter($comments, function($c) use ($contestant) {
						return $c['contestant_id'] === $contestant['id'];
					});
					foreach ($contestantComments as $comment): 
						$judgeName = '';
						foreach ($judges as $judge) {
							if ($judge['id'] === $comment['judge_id']) {
								$judgeName = $judge['name'];
								break;
							}
						}
					?>
						<div style="margin-bottom: 5px;">
							<strong><?= htmlspecialchars($judgeName) ?>:</strong><br>
							<?= htmlspecialchars($comment['comment']) ?>
						</div>
					<?php endforeach; ?>
                </td>
			</tr>
            <?php if (is_organizer() || (!empty($isHeadJudge) && $isHeadJudge)): ?>
            <tr>
                <td colspan="<?= 1 + (count($criteria)*count($judges)) ?>" style="text-align:right;">
                    <form method="post" action="<?= url('results/' . urlencode($subcategory['id']) . '/contestant/' . urlencode($contestant['id']) . '/deduction') ?>" style="display:inline-block;">
                        <input type="number" name="amount" step="0.01" placeholder="Deduction amount" required>
                        <input type="text" name="comment" placeholder="Comment (optional)" style="width: 240px;">
                        <button type="submit" class="btn btn-secondary">Add Deduction</button>
                    </form>
                    <?php if (!empty($deductionsByContestant[$contestant['id']]['rows'])): ?>
                        <div style="margin-top:8px; font-size: 0.9em;">
                            <strong>Deductions:</strong>
                            <?php foreach ($deductionsByContestant[$contestant['id']]['rows'] as $d): ?>
                                <div>-<?= number_format((float)$d['amount'], 2) ?> <?= $d['comment'] ? ' — ' . htmlspecialchars($d['comment']) : '' ?></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </td>
                <td style="font-weight:bold;">-<?= number_format($deductionTotal, 2) ?></td>
                <td></td>
            </tr>
            <?php endif; ?>
		<?php endforeach; ?>
	</table>
<?php endif; ?>

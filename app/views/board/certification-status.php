<?php use function App\{url}; ?>
<h2>Certification Status</h2>
<div class="navigation-buttons">
	<a href="/board" class="btn btn-outline">🏠 Dashboard</a>
</div>

<?php if (empty($certificationData)): ?>
	<p>No certification data available.</p>
<?php else: ?>
	<div class="certification-overview">
		<h3>Certification Progress by Subcategory</h3>
		<div class="certification-table">
			<table>
				<thead>
					<tr>
						<th>Contest</th>
						<th>Category</th>
						<th>Subcategory</th>
						<th>Judges Certified</th>
						<th>Tally Master</th>
						<th>Auditor</th>
						<th>Status</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($certificationData as $item): ?>
						<tr>
							<td><?= htmlspecialchars($item['contest_name']) ?></td>
							<td><?= htmlspecialchars($item['category_name']) ?></td>
							<td><?= htmlspecialchars($item['subcategory_name']) ?></td>
							<td><?= $item['judges_certified'] ?>/<?= $item['total_judges'] ?></td>
							<td>
								<?php if ($item['tally_master_signature']): ?>
									<span class="status-certified">✅ <?= htmlspecialchars($item['tally_master_signature']) ?></span>
									<br><small><?= date('M j, Y', strtotime($item['tally_master_certified_at'])) ?></small>
								<?php else: ?>
									<span class="status-pending">⏳ Pending</span>
								<?php endif; ?>
							</td>
							<td>
								<?php if ($item['auditor_certified_at']): ?>
									<span class="status-certified">✅ Certified</span>
									<br><small><?= date('M j, Y', strtotime($item['auditor_certified_at'])) ?></small>
								<?php else: ?>
									<span class="status-pending">⏳ Pending</span>
								<?php endif; ?>
							</td>
							<td>
								<?php 
								$judgesComplete = $item['judges_certified'] == $item['total_judges'];
								$tallyComplete = !empty($item['tally_master_signature']);
								$auditorComplete = !empty($item['auditor_certified_at']);
								
								if ($judgesComplete && $tallyComplete && $auditorComplete): ?>
									<span class="status-complete">✅ Complete</span>
								<?php elseif ($judgesComplete && $tallyComplete): ?>
									<span class="status-partial">🔄 Auditor Pending</span>
								<?php elseif ($judgesComplete): ?>
									<span class="status-partial">🔄 Tally Master Pending</span>
								<?php else: ?>
									<span class="status-pending">⏳ Judges Pending</span>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
<?php endif; ?>

<style>
.certification-overview {
	margin: 20px 0;
}

.certification-table {
	overflow-x: auto;
	margin-top: 20px;
}

.certification-table table {
	width: 100%;
	border-collapse: collapse;
	background: white;
	border-radius: 8px;
	overflow: hidden;
	box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.certification-table th,
.certification-table td {
	padding: 12px 15px;
	text-align: left;
	border-bottom: 1px solid #dee2e6;
}

.certification-table th {
	background: #f8f9fa;
	font-weight: bold;
	color: #495057;
}

.certification-table tr:hover {
	background: #f8f9fa;
}

.status-certified {
	color: #28a745;
	font-weight: bold;
}

.status-pending {
	color: #ffc107;
	font-weight: bold;
}

.status-partial {
	color: #17a2b8;
	font-weight: bold;
}

.status-complete {
	color: #28a745;
	font-weight: bold;
}

@media (max-width: 768px) {
	.certification-table {
		font-size: 0.9em;
	}
	
	.certification-table th,
	.certification-table td {
		padding: 8px 10px;
	}
}
</style>

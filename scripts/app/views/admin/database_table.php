<?php 
use function App\{url, hierarchical_back_url, home_url}; 
$title = 'Database Table: ' . $tableName;
?>

<div class="container-fluid">
	<div class="row">
		<div class="col-12">
			<div class="card">
				<div class="card-header">
					<h5 class="card-title">🗄️ Table: <?= htmlspecialchars($tableName) ?></h5>
					<div class="card-tools">
						<a href="<?= url('admin/database') ?>" class="btn btn-secondary btn-sm">← Back to Database</a>
						<a href="<?= hierarchical_back_url() ?>" class="btn btn-secondary btn-sm">← Back</a>
						<a href="<?= home_url() ?>" class="btn btn-outline btn-sm">🏠 Home</a>
					</div>
				</div>
				<div class="card-body">
					<!-- Table Info -->
					<div class="row mb-3">
						<div class="col-md-6">
							<div class="card">
								<div class="card-body">
									<h6>📊 Table Information</h6>
									<p><strong>Total Rows:</strong> <?= number_format($totalCount) ?></p>
									<p><strong>Columns:</strong> <?= count($columns) ?></p>
									<p><strong>Current Page:</strong> <?= $page ?> of <?= $totalPages ?></p>
									<p><strong>Rows per Page:</strong> <?= $perPage ?></p>
								</div>
							</div>
						</div>
						<div class="col-md-6">
							<div class="card">
								<div class="card-body">
									<h6>🔍 Quick Actions</h6>
									<a href="<?= url('admin/database/table/' . urlencode($tableName) . '?page=1') ?>" 
									   class="btn btn-sm btn-primary">First Page</a>
									<?php if ($page > 1): ?>
										<a href="<?= url('admin/database/table/' . urlencode($tableName) . '?page=' . ($page - 1)) ?>" 
										   class="btn btn-sm btn-secondary">Previous</a>
									<?php endif; ?>
									<?php if ($page < $totalPages): ?>
										<a href="<?= url('admin/database/table/' . urlencode($tableName) . '?page=' . ($page + 1)) ?>" 
										   class="btn btn-sm btn-secondary">Next</a>
									<?php endif; ?>
									<a href="<?= url('admin/database/table/' . urlencode($tableName) . '?page=' . $totalPages) ?>" 
									   class="btn btn-sm btn-primary">Last Page</a>
								</div>
							</div>
						</div>
					</div>

					<!-- Table Structure -->
					<div class="row mb-3">
						<div class="col-12">
							<div class="card">
								<div class="card-header">
									<h6>📋 Table Structure</h6>
								</div>
								<div class="card-body">
									<div class="table-responsive">
										<table class="table table-sm table-striped">
											<thead>
												<tr>
													<th>Column</th>
													<th>Type</th>
													<th>Not Null</th>
													<th>Default</th>
													<th>Primary Key</th>
												</tr>
											</thead>
											<tbody>
												<?php foreach ($columns as $column): ?>
													<tr>
														<td><strong><?= htmlspecialchars($column['name']) ?></strong></td>
														<td><?= htmlspecialchars($column['type']) ?></td>
														<td><?= $column['notnull'] ? 'Yes' : 'No' ?></td>
														<td><?= htmlspecialchars($column['dflt_value'] ?? 'NULL') ?></td>
														<td><?= $column['pk'] ? 'Yes' : 'No' ?></td>
													</tr>
												<?php endforeach; ?>
											</tbody>
										</table>
									</div>
								</div>
							</div>
						</div>
					</div>

					<!-- Table Data -->
					<div class="row">
						<div class="col-12">
							<div class="card">
								<div class="card-header">
									<h6>📊 Table Data</h6>
									<small class="text-muted">
										Showing rows <?= number_format(($page - 1) * $perPage + 1) ?> to 
										<?= number_format(min($page * $perPage, $totalCount)) ?> of 
										<?= number_format($totalCount) ?>
									</small>
								</div>
								<div class="card-body">
									<?php if (empty($data)): ?>
										<div class="alert alert-info">No data found in this table.</div>
									<?php else: ?>
										<div class="table-responsive">
											<table class="table table-striped table-hover">
												<thead class="thead-dark">
													<tr>
														<?php foreach (array_keys($data[0]) as $column): ?>
															<th><?= htmlspecialchars($column) ?></th>
														<?php endforeach; ?>
													</tr>
												</thead>
												<tbody>
													<?php foreach ($data as $row): ?>
														<tr>
															<?php foreach ($row as $value): ?>
																<td>
																	<?php if (is_null($value)): ?>
																		<span class="text-muted">NULL</span>
																	<?php elseif (is_bool($value)): ?>
																		<span class="badge badge-<?= $value ? 'success' : 'secondary' ?>">
																			<?= $value ? 'true' : 'false' ?>
																		</span>
																	<?php elseif (is_numeric($value)): ?>
																		<span class="text-primary"><?= htmlspecialchars($value) ?></span>
																	<?php elseif (strlen($value) > 100): ?>
																		<span title="<?= htmlspecialchars($value) ?>">
																			<?= htmlspecialchars(substr($value, 0, 100)) ?>...
																		</span>
																	<?php else: ?>
																		<?= htmlspecialchars($value) ?>
																	<?php endif; ?>
																</td>
															<?php endforeach; ?>
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

					<!-- Pagination -->
					<?php if ($totalPages > 1): ?>
						<div class="row mt-3">
							<div class="col-12">
								<nav aria-label="Table pagination">
									<ul class="pagination justify-content-center">
										<li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
											<a class="page-link" href="<?= url('admin/database/table/' . urlencode($tableName) . '?page=1') ?>">
												First
											</a>
										</li>
										<li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
											<a class="page-link" href="<?= url('admin/database/table/' . urlencode($tableName) . '?page=' . ($page - 1)) ?>">
												Previous
											</a>
										</li>
										
										<?php
										$startPage = max(1, $page - 2);
										$endPage = min($totalPages, $page + 2);
										
										for ($i = $startPage; $i <= $endPage; $i++):
										?>
											<li class="page-item <?= $i === $page ? 'active' : '' ?>">
												<a class="page-link" href="<?= url('admin/database/table/' . urlencode($tableName) . '?page=' . $i) ?>">
													<?= $i ?>
												</a>
											</li>
										<?php endfor; ?>
										
										<li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
											<a class="page-link" href="<?= url('admin/database/table/' . urlencode($tableName) . '?page=' . ($page + 1)) ?>">
												Next
											</a>
										</li>
										<li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
											<a class="page-link" href="<?= url('admin/database/table/' . urlencode($tableName) . '?page=' . $totalPages) ?>">
												Last
											</a>
										</li>
									</ul>
								</nav>
							</div>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>
</div>

<style>
.table-responsive {
	max-height: 600px;
	overflow-y: auto;
}

.card-tools {
	display: flex;
	gap: 10px;
}

.badge {
	font-size: 0.8em;
}

.page-link {
	color: #007bff;
}

.page-item.active .page-link {
	background-color: #007bff;
	border-color: #007bff;
}

.table th {
	position: sticky;
	top: 0;
	background-color: #343a40;
	color: white;
	z-index: 10;
}

.text-primary {
	font-weight: 500;
}

.alert {
	margin-bottom: 1rem;
}
</style>

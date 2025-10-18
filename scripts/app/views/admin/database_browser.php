<?php 
use function App\{url, hierarchical_back_url, home_url}; 
$title = 'Database Browser';
?>

<div class="container-fluid">
	<div class="row">
		<div class="col-12">
			<div class="card">
				<div class="card-header">
					<h5 class="card-title">🗄️ Database Browser</h5>
					<div class="card-tools">
						<a href="<?= hierarchical_back_url() ?>" class="btn btn-secondary btn-sm">← Back</a>
						<a href="<?= home_url() ?>" class="btn btn-outline btn-sm">🏠 Home</a>
					</div>
				</div>
				<div class="card-body">
					<?php if (isset($_GET['error'])): ?>
						<div class="alert alert-danger">
							<?php
							switch ($_GET['error']) {
								case 'empty_query':
									echo 'Please enter a SQL query.';
									break;
								case 'invalid_query_type':
									echo 'Only SELECT queries are allowed for security reasons.';
									break;
								case 'query_failed':
									echo 'Query execution failed: ' . htmlspecialchars($_GET['message'] ?? 'Unknown error');
									break;
								case 'invalid_table':
									echo 'Invalid table name specified.';
									break;
								default:
									echo 'An error occurred.';
							}
							?>
						</div>
					<?php endif; ?>

					<?php if (isset($_GET['success'])): ?>
						<div class="alert alert-success">
							<?php
							switch ($_GET['success']) {
								case 'query_executed':
									echo 'Query executed successfully!';
									break;
								default:
									echo 'Operation completed successfully.';
							}
							?>
						</div>
					<?php endif; ?>

					<!-- SQL Query Interface -->
					<div class="row mb-4">
						<div class="col-12">
							<div class="card">
								<div class="card-header">
									<h6 class="card-title">🔍 SQL Query Interface</h6>
								</div>
								<div class="card-body">
									<form method="post" action="<?= url('admin/database/query') ?>">
										<div class="form-group">
											<label for="sql">SQL Query (SELECT only):</label>
											<textarea class="form-control" id="sql" name="sql" rows="4" 
												placeholder="SELECT * FROM users LIMIT 10;"><?= htmlspecialchars($_POST['sql'] ?? '') ?></textarea>
											<small class="form-text text-muted">
												Only SELECT queries are allowed for security reasons.
											</small>
										</div>
										<div class="form-group">
											<button type="submit" name="action" value="data" class="btn btn-primary">
												📊 Execute Query
											</button>
											<button type="submit" name="action" value="count" class="btn btn-info">
												🔢 Count Results
											</button>
										</div>
									</form>
								</div>
							</div>
						</div>
					</div>

					<!-- Query Results -->
					<?php if (isset($_SESSION['query_result'])): ?>
						<div class="row mb-4">
							<div class="col-12">
								<div class="card">
									<div class="card-header">
										<h6 class="card-title">📋 Query Results</h6>
									</div>
									<div class="card-body">
										<?php 
										$result = $_SESSION['query_result'];
										unset($_SESSION['query_result']);
										
										if ($result['type'] === 'count'): ?>
											<div class="alert alert-info">
												<strong>Count Result:</strong> <?= number_format($result['result']) ?> rows
											</div>
										<?php else: ?>
											<?php if (empty($result['result'])): ?>
												<div class="alert alert-warning">No results found.</div>
											<?php else: ?>
												<div class="table-responsive">
													<table class="table table-striped table-sm">
														<thead>
															<tr>
																<?php foreach (array_keys($result['result'][0]) as $column): ?>
																	<th><?= htmlspecialchars($column) ?></th>
																<?php endforeach; ?>
															</tr>
														</thead>
														<tbody>
															<?php foreach ($result['result'] as $row): ?>
																<tr>
																	<?php foreach ($row as $value): ?>
																		<td>
																			<?php if (is_null($value)): ?>
																				<span class="text-muted">NULL</span>
																			<?php elseif (is_bool($value)): ?>
																				<?= $value ? 'true' : 'false' ?>
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
												<small class="text-muted">
													Showing <?= count($result['result']) ?> rows
												</small>
											<?php endif; ?>
										<?php endif; ?>
									</div>
								</div>
							</div>
						</div>
					<?php endif; ?>

					<!-- Database Tables -->
					<div class="row">
						<div class="col-12">
							<h6>📊 Database Tables</h6>
							<div class="table-responsive">
								<table class="table table-striped">
									<thead>
										<tr>
											<th>Table Name</th>
											<th>Rows</th>
											<th>Columns</th>
											<th>Actions</th>
										</tr>
									</thead>
									<tbody>
										<?php foreach ($tableInfo as $table): ?>
											<tr>
												<td>
													<strong><?= htmlspecialchars($table['name']) ?></strong>
												</td>
												<td>
													<span class="badge badge-primary"><?= number_format($table['count']) ?></span>
												</td>
												<td>
													<span class="badge badge-secondary"><?= count($table['columns']) ?></span>
												</td>
												<td>
													<a href="<?= url('admin/database/table/' . urlencode($table['name'])) ?>" 
													   class="btn btn-sm btn-primary">
														👁️ Browse Data
													</a>
													<button type="button" class="btn btn-sm btn-info" 
														onclick="showTableStructure('<?= htmlspecialchars($table['name']) ?>')">
														📋 Structure
													</button>
												</td>
											</tr>
										<?php endforeach; ?>
									</tbody>
								</table>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- Table Structure Modal -->
<div class="modal fade" id="structureModal" tabindex="-1">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Table Structure: <span id="modalTableName"></span></h5>
				<button type="button" class="close" data-dismiss="modal">
					<span>&times;</span>
				</button>
			</div>
			<div class="modal-body">
				<div id="tableStructureContent"></div>
			</div>
		</div>
	</div>
</div>

<script>
const tableStructures = <?= json_encode($tableInfo) ?>;

function showTableStructure(tableName) {
	const table = tableStructures[tableName];
	if (!table) return;
	
	document.getElementById('modalTableName').textContent = tableName;
	
	let html = '<div class="table-responsive"><table class="table table-sm">';
	html += '<thead><tr><th>Column</th><th>Type</th><th>Not Null</th><th>Default</th><th>Primary Key</th></tr></thead>';
	html += '<tbody>';
	
	table.columns.forEach(column => {
		html += '<tr>';
		html += '<td><strong>' + column.name + '</strong></td>';
		html += '<td>' + column.type + '</td>';
		html += '<td>' + (column.notnull ? 'Yes' : 'No') + '</td>';
		html += '<td>' + (column.dflt_value || 'NULL') + '</td>';
		html += '<td>' + (column.pk ? 'Yes' : 'No') + '</td>';
		html += '</tr>';
	});
	
	html += '</tbody></table></div>';
	
	document.getElementById('tableStructureContent').innerHTML = html;
	$('#structureModal').modal('show');
}
</script>

<style>
.table-responsive {
	max-height: 500px;
	overflow-y: auto;
}

.badge {
	font-size: 0.8em;
}

.card-tools {
	display: flex;
	gap: 10px;
}

.form-control {
	font-family: 'Courier New', monospace;
}

.alert {
	margin-bottom: 1rem;
}
</style>

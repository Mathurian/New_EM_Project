<?php use function App\{url}; ?>
<h2>Print Reports</h2>
<div class="navigation-buttons">
	<a href="/board" class="btn btn-secondary">← Back to Dashboard</a>
	<a href="/board" class="btn btn-outline">🏠 Dashboard</a>
</div>

<div class="reports-section">
	<h3>Available Reports</h3>
	<p>Select a contest and category to generate reports.</p>
	
	<div class="report-options">
		<div class="report-card">
			<h4>📊 Contest Results</h4>
			<p>Generate comprehensive results for an entire contest.</p>
			<div class="report-form">
				<label for="contest_id">Select Contest:</label>
				<select id="contest_id" class="form-control">
					<option value="">Choose a contest...</option>
					<?php foreach ($contests as $contest): ?>
						<option value="<?= $contest['id'] ?>"><?= htmlspecialchars($contest['name']) ?></option>
					<?php endforeach; ?>
				</select>
				<button type="button" class="btn btn-primary" onclick="generateContestReport()">Generate Report</button>
			</div>
		</div>
		
		<div class="report-card">
			<h4>📋 Category Results</h4>
			<p>Generate detailed results for a specific category.</p>
			<div class="report-form">
				<label for="category_id">Select Category:</label>
				<select id="category_id" class="form-control">
					<option value="">Choose a category...</option>
					<?php foreach ($categories as $category): ?>
						<option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
					<?php endforeach; ?>
				</select>
				<button type="button" class="btn btn-primary" onclick="generateCategoryReport()">Generate Report</button>
			</div>
		</div>
		
		<div class="report-card">
			<h4>👥 Contestant Summary</h4>
			<p>Generate a summary of all contestants and their scores.</p>
			<div class="report-form">
				<button type="button" class="btn btn-primary" onclick="generateContestantSummary()">Generate Summary</button>
			</div>
		</div>
		
		<div class="report-card">
			<h4>⚖️ Judge Summary</h4>
			<p>Generate a summary of all judges and their certifications.</p>
			<div class="report-form">
				<button type="button" class="btn btn-primary" onclick="generateJudgeSummary()">Generate Summary</button>
			</div>
		</div>
	</div>
</div>

<div class="recent-reports">
	<h3>Recent Reports</h3>
	<p>Reports will be generated and available for download here.</p>
	<div class="reports-list">
		<p class="text-muted">No recent reports available.</p>
	</div>
</div>

<script>
function generateContestReport() {
	const contestId = document.getElementById('contest_id').value;
	if (!contestId) {
		alert('Please select a contest.');
		return;
	}
	window.open('/admin/print-reports/contest/' + contestId, '_blank');
}

function generateCategoryReport() {
	const categoryId = document.getElementById('category_id').value;
	if (!categoryId) {
		alert('Please select a category.');
		return;
	}
	window.open('/admin/print-reports/category/' + categoryId, '_blank');
}

function generateContestantSummary() {
	window.open('/admin/print-reports/contestants', '_blank');
}

function generateJudgeSummary() {
	window.open('/admin/print-reports/judges', '_blank');
}
</script>

<style>
.reports-section {
	margin: 20px 0;
}

.report-options {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
	gap: 20px;
	margin: 20px 0;
}

.report-card {
	background: white;
	border: 1px solid #dee2e6;
	border-radius: 8px;
	padding: 20px;
	box-shadow: 0 2px 4px rgba(0,0,0,0.1);
	transition: transform 0.2s, box-shadow 0.2s;
}

.report-card:hover {
	transform: translateY(-2px);
	box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.report-card h4 {
	margin: 0 0 10px 0;
	color: #333;
	font-size: 1.1em;
}

.report-card p {
	margin: 0 0 15px 0;
	color: #666;
	font-size: 0.9em;
	line-height: 1.4;
}

.report-form label {
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
	margin-bottom: 10px;
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

.recent-reports {
	margin: 30px 0;
	padding: 20px;
	background: white;
	border-radius: 8px;
	box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.recent-reports h3 {
	margin: 0 0 15px 0;
	color: #333;
	border-bottom: 2px solid #007bff;
	padding-bottom: 10px;
}

.text-muted {
	color: #666;
	font-style: italic;
}

@media (max-width: 768px) {
	.report-options {
		grid-template-columns: 1fr;
		gap: 15px;
	}
}
</style>

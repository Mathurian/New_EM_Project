<?php use function App\{url, csrf_field}; ?>
<h2>Print Reports</h2>
<div class="navigation-buttons">
	<a href="/board" class="btn btn-outline">🏠 Dashboard</a>
</div>

<div class="reports-section">
	<h3>Available Reports</h3>
	<p>Select a contest and category to generate reports.</p>
	
	<div class="report-options">
		<div class="report-card">
			<h4>📊 Contest Summary</h4>
			<p>Generate comprehensive summary for an entire contest including all categories.</p>
			<div class="report-form">
				<label for="contest_id">Select Contest:</label>
				<select id="contest_id" class="form-control">
					<option value="">Choose a contest...</option>
					<?php foreach ($contests as $contest): ?>
						<option value="<?= $contest['id'] ?>"><?= htmlspecialchars($contest['name']) ?></option>
					<?php endforeach; ?>
				</select>
				<div class="action-buttons">
					<button type="button" class="btn btn-primary" onclick="generateContestSummary()">Generate Summary</button>
					<form method="post" action="/board/print-reports/email" class="email-form" onsubmit="return validateEmailForm(this)">
						<?= csrf_field() ?>
						<input type="hidden" name="report_type" value="contest" />
						<input type="hidden" name="entity_id" value="" id="contest_email_id" />
						<input type="hidden" name="user_id" value="" />
						<input type="hidden" name="to_email" value="" />
						<select class="email-select" onchange="handleRecipientChange(this)">
							<option value="">Select recipient…</option>
							<?php foreach (($usersWithEmail ?? []) as $u): ?>
								<option value="user:<?= htmlspecialchars($u['id']) ?>"><?= htmlspecialchars(($u['preferred_name'] ?: $u['name']) . ' <' . $u['email'] . '>') ?></option>
							<?php endforeach; ?>
							<option value="custom">Custom email…</option>
						</select>
						<input type="email" class="email-input" placeholder="Enter email address" style="display:none;" />
						<button type="submit" class="btn btn-success">📧 Email Summary</button>
					</form>
				</div>
			</div>
		</div>
		
		<div class="report-card">
			<h4>📋 Contest Results</h4>
			<p>Generate detailed results for a specific contest category.</p>
			<div class="report-form">
				<label for="category_id">Select Category:</label>
				<select id="category_id" class="form-control">
					<option value="">Choose a category...</option>
					<?php foreach ($categories as $category): ?>
						<option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
					<?php endforeach; ?>
				</select>
				<div class="action-buttons">
					<button type="button" class="btn btn-primary" onclick="generateContestResults()">Generate Results</button>
					<form method="post" action="/board/print-reports/email" class="email-form" onsubmit="return validateEmailForm(this)">
						<?= csrf_field() ?>
						<input type="hidden" name="report_type" value="category" />
						<input type="hidden" name="entity_id" value="" id="category_email_id" />
						<input type="hidden" name="user_id" value="" />
						<input type="hidden" name="to_email" value="" />
						<select class="email-select" onchange="handleRecipientChange(this)">
							<option value="">Select recipient…</option>
							<?php foreach (($usersWithEmail ?? []) as $u): ?>
								<option value="user:<?= htmlspecialchars($u['id']) ?>"><?= htmlspecialchars(($u['preferred_name'] ?: $u['name']) . ' <' . $u['email'] . '>') ?></option>
							<?php endforeach; ?>
							<option value="custom">Custom email…</option>
						</select>
						<input type="email" class="email-input" placeholder="Enter email address" style="display:none;" />
						<button type="submit" class="btn btn-success">📧 Email Results</button>
					</form>
				</div>
			</div>
		</div>
		
		<div class="report-card">
			<h4>👥 Contestant Summary</h4>
			<p>Generate a summary of all contestants and their scores.</p>
			<div class="report-form">
				<div class="action-buttons">
					<button type="button" class="btn btn-primary" onclick="generateContestantSummary()">Generate Summary</button>
					<form method="post" action="/board/print-reports/email" class="email-form" onsubmit="return validateEmailForm(this)">
						<?= csrf_field() ?>
						<input type="hidden" name="report_type" value="contestant" />
						<input type="hidden" name="entity_id" value="" />
						<input type="hidden" name="user_id" value="" />
						<input type="hidden" name="to_email" value="" />
						<select class="email-select" onchange="handleRecipientChange(this)">
							<option value="">Select recipient…</option>
							<?php foreach (($usersWithEmail ?? []) as $u): ?>
								<option value="user:<?= htmlspecialchars($u['id']) ?>"><?= htmlspecialchars(($u['preferred_name'] ?: $u['name']) . ' <' . $u['email'] . '>') ?></option>
							<?php endforeach; ?>
							<option value="custom">Custom email…</option>
						</select>
						<input type="email" class="email-input" placeholder="Enter email address" style="display:none;" />
						<button type="submit" class="btn btn-success">📧 Email Summary</button>
					</form>
				</div>
			</div>
		</div>
		
		<div class="report-card">
			<h4>⚖️ Judge Summary</h4>
			<p>Generate a summary of all judges and their certifications.</p>
			<div class="report-form">
				<div class="action-buttons">
					<button type="button" class="btn btn-primary" onclick="generateJudgeSummary()">Generate Summary</button>
					<form method="post" action="/board/print-reports/email" class="email-form" onsubmit="return validateEmailForm(this)">
						<?= csrf_field() ?>
						<input type="hidden" name="report_type" value="judge" />
						<input type="hidden" name="entity_id" value="" />
						<input type="hidden" name="user_id" value="" />
						<input type="hidden" name="to_email" value="" />
						<select class="email-select" onchange="handleRecipientChange(this)">
							<option value="">Select recipient…</option>
							<?php foreach (($usersWithEmail ?? []) as $u): ?>
								<option value="user:<?= htmlspecialchars($u['id']) ?>"><?= htmlspecialchars(($u['preferred_name'] ?: $u['name']) . ' <' . $u['email'] . '>') ?></option>
							<?php endforeach; ?>
							<option value="custom">Custom email…</option>
						</select>
						<input type="email" class="email-input" placeholder="Enter email address" style="display:none;" />
						<button type="submit" class="btn btn-success">📧 Email Summary</button>
					</form>
				</div>
			</div>
		</div>
</div>

<script>
// Function to open print windows that can be closed properly
function openPrintWindow(url) {
	const printWindow = window.open(url, 'printWindow', 'width=800,height=600,scrollbars=yes,resizable=yes');
	if (printWindow) {
		printWindow.focus();
	}
}

function generateContestSummary() {
	const contestId = document.getElementById('contest_id').value;
	if (!contestId) {
		alert('Please select a contest.');
		return;
	}
	// For contest summary, we'll generate a category report for each category in the contest
	// This is a simplified approach - you might want to create a dedicated contest summary report
	alert('Contest summary reports are not yet implemented. Please use contest results for now.');
}

function generateContestResults() {
	const categoryId = document.getElementById('category_id').value;
	if (!categoryId) {
		alert('Please select a category.');
		return;
	}
	openPrintWindow('/print/category/' + categoryId);
}

function generateContestantSummary() {
	// Show all contestants for individual printing
	window.location.href = '/board/print-reports?view=contestants';
}

function generateJudgeSummary() {
	// Show all judges for individual printing
	window.location.href = '/board/print-reports?view=judges';
}

// Email form handling functions (from admin view)
function handleRecipientChange(select) {
	const form = select.closest('form');
	const emailInput = form.querySelector('.email-input');
	const userIdInput = form.querySelector('input[name="user_id"]');
	const toEmailInput = form.querySelector('input[name="to_email"]');
	
	if (select.value === 'custom') {
		emailInput.style.display = 'block';
		emailInput.required = true;
		userIdInput.value = '';
		toEmailInput.value = '';
	} else if (select.value.startsWith('user:')) {
		emailInput.style.display = 'none';
		emailInput.required = false;
		userIdInput.value = select.value.replace('user:', '');
		toEmailInput.value = '';
	} else {
		emailInput.style.display = 'none';
		emailInput.required = false;
		userIdInput.value = '';
		toEmailInput.value = '';
	}
}

function validateEmailForm(form) {
	const select = form.querySelector('.email-select');
	const emailInput = form.querySelector('.email-input');
	const userIdInput = form.querySelector('input[name="user_id"]');
	const toEmailInput = form.querySelector('input[name="to_email"]');
	
	if (select.value === 'custom') {
		if (!emailInput.value || !emailInput.value.includes('@')) {
			alert('Please enter a valid email address.');
			return false;
		}
		toEmailInput.value = emailInput.value;
		userIdInput.value = '';
	} else if (select.value.startsWith('user:')) {
		// User selected - userIdInput already set
	} else {
		alert('Please select a recipient.');
		return false;
	}
	
	// Set entity_id for contest and category forms
	const contestSelect = document.getElementById('contest_id');
	const categorySelect = document.getElementById('category_id');
	
	if (contestSelect && contestSelect.value) {
		form.querySelector('input[name="entity_id"]').value = contestSelect.value;
	} else if (categorySelect && categorySelect.value) {
		form.querySelector('input[name="entity_id"]').value = categorySelect.value;
	}
	
	return true;
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

.btn-success {
	background: #28a745;
	color: white;
}

.btn-success:hover {
	background: #218838;
}

.action-buttons {
	display: flex;
	gap: 10px;
	flex-wrap: wrap;
	align-items: flex-start;
}

.email-form {
	display: flex;
	flex-direction: column;
	gap: 5px;
	min-width: 200px;
}

.email-select,
.email-input {
	padding: 6px 8px;
	border: 1px solid #dee2e6;
	border-radius: 4px;
	font-size: 12px;
}

.email-select {
	background: white;
}

.email-input {
	background: white;
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

<?php use function App\{url, is_organizer, hierarchical_back_url, home_url, csrf_field}; ?>
<h2>Print Reports</h2>

<!-- Success/Error Messages -->
<?php if (isset($_GET['success'])): ?>
	<div class="alert alert-success">
		<?php
		switch ($_GET['success']) {
			case 'report_emailed':
				echo 'Report emailed successfully!';
				break;
			default:
				echo 'Operation completed successfully.';
		}
		?>
	</div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
	<div class="alert alert-danger">
		<?php
		switch ($_GET['error']) {
			case 'missing_email':
				echo 'Please select a recipient for the email.';
				break;
			case 'email_failed':
				echo 'Failed to send email. Please try again.';
				break;
			case 'email_exception':
				echo 'An error occurred while sending the email.';
				break;
			case 'invalid_report_type':
				echo 'Invalid report type selected.';
				break;
			case 'contest_not_found':
				echo 'Contest not found.';
				break;
			default:
				echo 'An error occurred. Please try again.';
		}
		?>
	</div>
<?php endif; ?>

<div class="navigation-buttons">
	<a href="<?= hierarchical_back_url() ?>" class="btn btn-secondary">← Back</a>
	<a href="<?= home_url() ?>" class="btn btn-outline">🏠 Home</a>
</div>

<div class="print-reports-container">
	<div class="card">
		<h3>📊 Contestant Reports</h3>
		<p>Generate detailed score reports for individual contestants.</p>
		<div class="contestant-list">
			<?php if (empty($contestants)): ?>
				<p class="no-data">No contestants found.</p>
			<?php else: ?>
				<?php foreach ($contestants as $contestant): ?>
                    <div class="contestant-item">
						<div class="contestant-info">
							<strong><?= htmlspecialchars($contestant['name']) ?></strong>
							<?php if ($contestant['contestant_number']): ?>
								<span class="contestant-number">#<?= htmlspecialchars($contestant['contestant_number']) ?></span>
							<?php endif; ?>
						</div>
                        <div class="report-actions">
                            <button onclick="openPrintWindow('<?= url('print/contestant/' . $contestant['id']) ?>')" class="btn btn-primary">🖨️ Print</button>
                            <form method="post" action="<?= url('admin/print-reports/email') ?>" class="email-form stacked" onsubmit="return validateEmailForm(this)">
                                <input type="hidden" name="report_type" value="contestant" />
                                <input type="hidden" name="entity_id" value="<?= htmlspecialchars($contestant['id']) ?>" />
                                <input type="hidden" name="user_id" value="" />
                                <input type="hidden" name="to_email" value="" />
                                <select class="email-select unified-recipient" onchange="handleRecipientChange(this)">
                                    <option value="">Select recipient…</option>
                                    <?php foreach (($usersWithEmail ?? []) as $u): ?>
                                        <option value="user:<?= htmlspecialchars($u['id']) ?>"><?= htmlspecialchars(($u['preferred_name'] ?: $u['name']) . ' <' . $u['email'] . '>') ?></option>
                                    <?php endforeach; ?>
                                    <option value="custom">Custom email…</option>
                                </select>
                                <input type="email" class="email-input unified-email" placeholder="Enter email address" style="display:none;" />
                                <button type="submit" class="btn btn-secondary">✉️ Send</button>
                            </form>
                        </div>
					</div>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
	</div>

	<div class="card">
		<h3>⚖️ Judge Reports</h3>
		<p>Generate score reports for individual judges showing all their scores.</p>
		<div class="judge-list">
			<?php if (empty($judges)): ?>
				<p class="no-data">No judges found.</p>
			<?php else: ?>
				<?php foreach ($judges as $judge): ?>
                    <div class="judge-item">
						<div class="judge-info">
							<strong><?= htmlspecialchars($judge['name']) ?></strong>
						</div>
                        <div class="report-actions">
                            <button onclick="openPrintWindow('<?= url('print/judge/' . $judge['id']) ?>')" class="btn btn-primary">🖨️ Print</button>
                            <form method="post" action="<?= url('admin/print-reports/email') ?>" class="email-form stacked" onsubmit="return validateEmailForm(this)">
                                <input type="hidden" name="report_type" value="judge" />
                                <input type="hidden" name="entity_id" value="<?= htmlspecialchars($judge['id']) ?>" />
                                <input type="hidden" name="user_id" value="" />
                                <input type="hidden" name="to_email" value="" />
                                <select class="email-select unified-recipient" onchange="handleRecipientChange(this)">
                                    <option value="">Select recipient…</option>
                                    <?php foreach (($usersWithEmail ?? []) as $u): ?>
                                        <option value="user:<?= htmlspecialchars($u['id']) ?>"><?= htmlspecialchars(($u['preferred_name'] ?: $u['name']) . ' <' . $u['email'] . '>') ?></option>
                                    <?php endforeach; ?>
                                    <option value="custom">Custom email…</option>
                                </select>
                                <input type="email" class="email-input unified-email" placeholder="Enter email address" style="display:none;" />
                                <button type="submit" class="btn btn-secondary">✉️ Send</button>
                            </form>
                        </div>
					</div>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
	</div>

	<div class="card">
		<h3>📈 Contest Reports</h3>
		<p>Generate comprehensive reports for entire contests.</p>
		<div class="category-list">
			<?php 
			$groupedStructure = [];
			if (!empty($structure)) {
				foreach ($structure as $row) {
					if ($row['id']) {
						$groupedStructure[$row['contest_id']][$row['id']] = [
							'contest_name' => $row['contest_name'],
							'category_name' => $row['name']
						];
					}
				}
			}
			?>
			<?php if (empty($groupedStructure)): ?>
				<p class="no-data">No contests found.</p>
			<?php else: ?>
				<?php foreach ($groupedStructure as $contestId => $categories): ?>
					<div class="contest-group">
						<h4><?= htmlspecialchars($categories[array_key_first($categories)]['contest_name']) ?></h4>
						<?php foreach ($categories as $categoryId => $category): ?>
                            <div class="category-item">
								<div class="category-info">
									<strong><?= htmlspecialchars($category['category_name']) ?></strong>
								</div>
                                <div class="report-actions">
                                    <button onclick="openPrintWindow('<?= url('print/category/' . $categoryId) ?>')" class="btn btn-primary">🖨️ Print</button>
                                    <form method="post" action="<?= url('admin/print-reports/email') ?>" class="email-form stacked" onsubmit="return validateEmailForm(this)">
                                        <input type="hidden" name="report_type" value="category" />
                                        <input type="hidden" name="entity_id" value="<?= htmlspecialchars($categoryId) ?>" />
                                        <input type="hidden" name="user_id" value="" />
                                        <input type="hidden" name="to_email" value="" />
                                        <select class="email-select unified-recipient" onchange="handleRecipientChange(this)">
                                            <option value="">Select recipient…</option>
                                            <?php foreach (($usersWithEmail ?? []) as $u): ?>
                                                <option value="user:<?= htmlspecialchars($u['id']) ?>"><?= htmlspecialchars(($u['preferred_name'] ?: $u['name']) . ' <' . $u['email'] . '>') ?></option>
                                            <?php endforeach; ?>
                                            <option value="custom">Custom email…</option>
                                        </select>
                                        <input type="email" class="email-input unified-email" placeholder="Enter email address" style="display:none;" />
                                        <button type="submit" class="btn btn-secondary">✉️ Send</button>
                                    </form>
                                </div>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
	</div>

	<!-- Summary Reports Section -->
	<div class="card">
		<h3>📊 Contest Summary</h3>
		<p>Generate comprehensive summary for an entire contest including all categories.</p>
		<div class="report-form">
			<label for="contest_id">Select Contest:</label>
			<select id="contest_id" class="form-control" onchange="updateContestEmailId()">
				<option value="">Choose a contest...</option>
				<?php if (!empty($contests)): ?>
					<?php foreach ($contests as $contest): ?>
						<option value="<?= $contest['id'] ?>"><?= htmlspecialchars($contest['name']) ?></option>
					<?php endforeach; ?>
				<?php endif; ?>
			</select>
			<div class="action-buttons">
				<button type="button" class="btn btn-primary" onclick="generateContestSummary()">Generate Summary</button>
				<form method="post" action="/admin/print-reports/email" class="email-form" onsubmit="return validateEmailForm(this)">
					<?= csrf_field() ?>
					<input type="hidden" name="report_type" value="contest" />
					<input type="hidden" name="entity_id" value="" id="contest_email_id" />
					<input type="hidden" name="user_id" value="" />
					<input type="hidden" name="to_email" value="" />
					<select class="email-select unified-recipient" onchange="handleRecipientChange(this)">
						<option value="">Select recipient…</option>
						<?php foreach (($usersWithEmail ?? []) as $u): ?>
							<option value="user:<?= htmlspecialchars($u['id']) ?>"><?= htmlspecialchars(($u['preferred_name'] ?: $u['name']) . ' <' . $u['email'] . '>') ?></option>
						<?php endforeach; ?>
						<option value="custom">Custom email…</option>
					</select>
					<input type="email" class="email-input unified-email" placeholder="Enter email address" style="display:none;" />
					<button type="submit" class="btn btn-success">📧 Email Summary</button>
				</form>
			</div>
		</div>
	</div>
	
	<div class="card">
		<h3>📋 Contest Results</h3>
		<p>Generate results for a specific category with contestant rankings.</p>
		<div class="report-form">
			<label for="category_id">Select Category:</label>
			<select id="category_id" class="form-control" onchange="updateCategoryEmailId()">
				<option value="">Choose a category...</option>
				<?php if (!empty($summaryCategories)): ?>
					<?php foreach ($summaryCategories as $category): ?>
					<option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
					<?php endforeach; ?>
				<?php endif; ?>
			</select>
			<div class="action-buttons">
				<button type="button" class="btn btn-primary" onclick="generateContestResults()">Generate Results</button>
				<form method="post" action="/admin/print-reports/email" class="email-form" onsubmit="return validateEmailForm(this)">
					<?= csrf_field() ?>
					<input type="hidden" name="report_type" value="category" />
					<input type="hidden" name="entity_id" value="" id="category_email_id" />
					<input type="hidden" name="user_id" value="" />
					<input type="hidden" name="to_email" value="" />
					<select class="email-select unified-recipient" onchange="handleRecipientChange(this)">
						<option value="">Select recipient…</option>
						<?php foreach (($usersWithEmail ?? []) as $u): ?>
							<option value="user:<?= htmlspecialchars($u['id']) ?>"><?= htmlspecialchars(($u['preferred_name'] ?: $u['name']) . ' <' . $u['email'] . '>') ?></option>
						<?php endforeach; ?>
						<option value="custom">Custom email…</option>
					</select>
					<input type="email" class="email-input unified-email" placeholder="Enter email address" style="display:none;" />
					<button type="submit" class="btn btn-success">📧 Email Results</button>
				</form>
			</div>
		</div>
	</div>

	<div class="card">
		<h3>👥 Contestant Summary</h3>
		<p>Generate a comprehensive summary of all contestants and their scores.</p>
		<div class="action-buttons">
			<form method="post" action="/admin/print-reports/email" class="email-form" onsubmit="return validateEmailForm(this)">
				<?= csrf_field() ?>
				<input type="hidden" name="report_type" value="contestant_summary" />
				<input type="hidden" name="entity_id" value="" />
				<input type="hidden" name="user_id" value="" />
				<input type="hidden" name="to_email" value="" />
				<select class="email-select unified-recipient" onchange="handleRecipientChange(this)">
					<option value="">Select recipient…</option>
					<?php foreach (($usersWithEmail ?? []) as $u): ?>
						<option value="user:<?= htmlspecialchars($u['id']) ?>"><?= htmlspecialchars(($u['preferred_name'] ?: $u['name']) . ' <' . $u['email'] . '>') ?></option>
					<?php endforeach; ?>
					<option value="custom">Custom email…</option>
				</select>
				<input type="email" class="email-input unified-email" placeholder="Enter email address" style="display:none;" />
				<button type="submit" class="btn btn-success">📧 Email Summary</button>
			</form>
		</div>
	</div>

	<div class="card">
		<h3>⚖️ Judge Summary</h3>
		<p>Generate a comprehensive summary of all judges and their certifications.</p>
		<div class="action-buttons">
		<form method="post" action="/admin/print-reports/email" class="email-form" onsubmit="return validateEmailForm(this)">
			<?= csrf_field() ?>
			<input type="hidden" name="report_type" value="judge_summary" />
			<input type="hidden" name="entity_id" value="" />
			<input type="hidden" name="user_id" value="" />
			<input type="hidden" name="to_email" value="" />
			<select class="email-select unified-recipient" onchange="handleRecipientChange(this)">
				<option value="">Select recipient…</option>
				<?php foreach (($usersWithEmail ?? []) as $u): ?>
					<option value="user:<?= htmlspecialchars($u['id']) ?>"><?= htmlspecialchars(($u['preferred_name'] ?: $u['name']) . ' <' . $u['email'] . '>') ?></option>
				<?php endforeach; ?>
				<option value="custom">Custom email…</option>
			</select>
			<input type="email" class="email-input unified-email" placeholder="Enter email address" style="display:none;" />
			<button type="submit" class="btn btn-success">📧 Email Summary</button>
		</form>
		</div>
	</div>
</div>

<style>
.print-reports-container {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
	gap: 20px;
	margin-top: 20px;
}

.card {
	background: var(--bg-primary);
	border: 1px solid var(--border-color);
	border-radius: 8px;
	padding: 20px;
}

.card h3 {
	margin-top: 0;
	color: var(--text-primary);
	border-bottom: 2px solid var(--accent-color);
	padding-bottom: 10px;
}

.card p {
	color: var(--text-secondary);
	margin-bottom: 15px;
}

.contestant-item,
.judge-item,
.category-item {
	display: flex;
    justify-content: space-between;
    align-items: flex-start;
	padding: 15px;
	margin: 8px 0;
	background: var(--bg-secondary);
	border: 1px solid var(--border-color);
	border-radius: 6px;
}

.contestant-info,
.judge-info,
.category-info {
    flex: 1;
}

.report-actions {
    display: flex;
    gap: 10px;
    align-items: center;
}

.email-form {
    display: flex;
    gap: 8px;
    align-items: center;
}

.email-input, .email-select {
    padding: 8px 10px;
    border: 1px solid var(--border-color);
    border-radius: 4px;
    background: var(--bg-primary);
    color: var(--text-primary);
}

.email-input { width: 210px; }
.email-select { max-width: 260px; }

.email-sep { color: var(--text-secondary); font-size: 0.9em; }

@media (max-width: 640px) {
    .report-actions { flex-direction: column; align-items: stretch; width: 100%; }
    .email-form { flex-direction: column; align-items: stretch; }
    .email-input, .email-select { width: 100%; max-width: 100%; }
}

/* Stacked variation (dropdown under button) */
.email-form.stacked { 
    flex-direction: column; 
    align-items: stretch; 
    margin-left: 10px; 
    gap: 8px;
}
.email-form.stacked .unified-recipient { width: 260px; }
.email-form.stacked .unified-email { width: 260px; }

.contestant-number {
	background: var(--accent-color);
	color: white;
	padding: 2px 8px;
	border-radius: 12px;
	font-size: 0.8em;
	margin-left: 8px;
}

.contest-group {
	margin-bottom: 20px;
}

.contest-group h4 {
	color: var(--text-primary);
	margin-bottom: 10px;
	padding-bottom: 5px;
	border-bottom: 1px solid var(--border-color);
}

.no-data {
	color: var(--text-secondary);
	font-style: italic;
	text-align: center;
	padding: 20px;
}

.btn {
	text-decoration: none;
	padding: 8px 16px;
	border-radius: 4px;
	font-size: 0.9em;
	white-space: nowrap;
}

.btn-primary {
	background: var(--accent-color);
	color: white;
}

.btn-primary:hover {
	opacity: 0.9;
}

@media (max-width: 768px) {
	.print-reports-container {
		grid-template-columns: 1fr;
	}
	
	.contestant-item,
	.judge-item,
	.category-item {
		flex-direction: column;
		align-items: stretch;
		gap: 10px;
	}
	
	.btn {
		text-align: center;
	}
}

/* Summary Report Styles */
.report-form {
	margin-bottom: 15px;
}

.report-form label {
	display: block;
	margin-bottom: 5px;
	font-weight: bold;
	color: var(--text-primary);
}

.form-control {
	width: 100%;
	padding: 8px 12px;
	border: 1px solid var(--border-color);
	border-radius: 4px;
	background: var(--bg-primary);
	color: var(--text-primary);
	margin-bottom: 10px;
}

.action-buttons {
	display: flex;
	gap: 10px;
	align-items: center;
	flex-wrap: wrap;
}

.email-form {
	display: flex;
	gap: 10px;
	align-items: center;
	flex-wrap: wrap;
}

.email-select {
	padding: 8px 12px;
	border: 1px solid var(--border-color);
	border-radius: 4px;
	background: var(--bg-primary);
	color: var(--text-primary);
	min-width: 200px;
}

.email-input {
	padding: 8px 12px;
	border: 1px solid var(--border-color);
	border-radius: 4px;
	background: var(--bg-primary);
	color: var(--text-primary);
	min-width: 200px;
}

.btn-success {
	background: #28a745;
	color: white;
	border: none;
	padding: 8px 16px;
	border-radius: 4px;
	cursor: pointer;
	text-decoration: none;
	display: inline-block;
}

.btn-success:hover {
	background: #218838;
}

/* Success/Error Messages */
.alert {
	padding: 12px 16px;
	border-radius: 4px;
	margin: 10px 0;
}

.alert-success {
	background: #d4edda;
	color: #155724;
	border: 1px solid #c3e6cb;
}

.alert-danger {
	background: #f8d7da;
	color: #721c24;
	border: 1px solid #f5c6cb;
}
</style>

<script>
// Function to open print windows that can be closed properly
function openPrintWindow(url) {
	const printWindow = window.open(url, 'printWindow', 'width=800,height=600,scrollbars=yes,resizable=yes');
	if (printWindow) {
		printWindow.focus();
	}
}

function handleRecipientChange(selectEl) {
    const form = selectEl.closest('form');
    const hiddenUserId = form.querySelector('input[name="user_id"]');
    const hiddenToEmail = form.querySelector('input[name="to_email"]');
    const emailInput = form.querySelector('.unified-email');
    const val = selectEl.value;

    if (val.startsWith('user:')) {
        hiddenUserId.value = val.substring(5);
        hiddenToEmail.value = '';
        if (emailInput) { emailInput.style.display = 'none'; emailInput.value = ''; }
    } else if (val === 'custom') {
        hiddenUserId.value = '';
        if (emailInput) { 
            emailInput.style.display = 'block'; 
            emailInput.focus();
            // Copy value from visible input to hidden field when typing
            emailInput.addEventListener('input', function() {
                hiddenToEmail.value = this.value;
            });
        }
    } else {
        hiddenUserId.value = '';
        hiddenToEmail.value = '';
        if (emailInput) { emailInput.style.display = 'none'; emailInput.value = ''; }
    }
}

function validateEmailForm(form) {
    const hiddenUserId = form.querySelector('input[name="user_id"]');
    const hiddenToEmail = form.querySelector('input[name="to_email"]');
    const emailInput = form.querySelector('.unified-email');
    
    // If custom email is selected, ensure the email input has a value
    if (emailInput && emailInput.style.display !== 'none') {
        if (!emailInput.value.trim()) {
            alert('Please enter an email address');
            emailInput.focus();
            return false;
        }
        hiddenToEmail.value = emailInput.value.trim();
    }
    
    // Ensure we have either a user_id or to_email
    if (!hiddenUserId.value && !hiddenToEmail.value) {
        alert('Please select a recipient');
        return false;
    }
    
    return true;
}

// Summary report generation functions
function generateContestSummary() {
	const contestId = document.getElementById('contest_id').value;
	if (!contestId) {
		alert('Please select a contest.');
		return;
	}
	// Generate contest summary by opening a new window with contest data
	openPrintWindow('/print/contest/' + contestId);
}

function generateContestResults() {
	const categoryId = document.getElementById('category_id').value;
	if (!categoryId) {
		alert('Please select a category.');
		return;
	}
	openPrintWindow('/print/category/' + categoryId);
}

function updateContestEmailId() {
	const contestId = document.getElementById('contest_id').value;
	document.getElementById('contest_email_id').value = contestId;
}

function updateCategoryEmailId() {
	const categoryId = document.getElementById('category_id').value;
	document.getElementById('category_email_id').value = categoryId;
}
</script>

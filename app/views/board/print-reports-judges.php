<?php use function App\{url, csrf_field}; ?>
<h2>Print Reports - Judges</h2>
<div class="navigation-buttons">
	<a href="/board/print-reports" class="btn btn-outline">🏠 Dashboard</a>
</div>

<div class="reports-section">
	<h3>Judge Reports</h3>
	<p>Generate individual reports for each judge showing all their scores.</p>
	
	<div class="judge-list">
		<?php if (empty($judges)): ?>
			<p class="no-data">No judges found.</p>
		<?php else: ?>
			<?php foreach ($judges as $judge): ?>
				<div class="judge-item">
					<div class="judge-info">
						<strong><?= htmlspecialchars($judge['name']) ?></strong>
						<?php if (!empty($judge['email'])): ?>
							<span class="judge-email"><?= htmlspecialchars($judge['email']) ?></span>
						<?php endif; ?>
					</div>
					<div class="report-actions">
						<button onclick="openPrintWindow('<?= url('print/judge/' . $judge['id']) ?>')" class="btn btn-primary">🖨️ Print</button>
						<form method="post" action="/admin/print-reports/email" class="email-form" onsubmit="return validateEmailForm(this)">
							<input type="hidden" name="report_type" value="judge" />
							<input type="hidden" name="entity_id" value="<?= htmlspecialchars($judge['id']) ?>" />
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
							<button type="submit" class="btn btn-success">📧 Email</button>
						</form>
					</div>
				</div>
			<?php endforeach; ?>
		<?php endif; ?>
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
	
	return true;
}
</script>

<style>
.reports-section {
	margin: 20px 0;
}

.judge-list {
	display: grid;
	gap: 15px;
	margin: 20px 0;
}

.judge-item {
	background: white;
	border: 1px solid #dee2e6;
	border-radius: 8px;
	padding: 15px;
	display: flex;
	justify-content: space-between;
	align-items: center;
	box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.judge-info {
	display: flex;
	flex-direction: column;
	gap: 5px;
}

.judge-info strong {
	font-size: 1.1em;
	color: #333;
}

.judge-email {
	color: #666;
	font-size: 0.9em;
}

.report-actions {
	display: flex;
	gap: 10px;
	align-items: center;
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

.btn {
	padding: 8px 16px;
	border: none;
	border-radius: 4px;
	cursor: pointer;
	text-decoration: none;
	display: inline-block;
	font-size: 14px;
	font-weight: bold;
	transition: background-color 0.2s;
}

.btn-primary {
	background: #007bff;
	color: white;
}

.btn-primary:hover {
	background: #0056b3;
}

.btn-success {
	background: #28a745;
	color: white;
}

.btn-success:hover {
	background: #218838;
}

.btn-outline {
	background: transparent;
	color: #007bff;
	border: 1px solid #007bff;
}

.btn-outline:hover {
	background: #007bff;
	color: white;
}

.no-data {
	text-align: center;
	color: #666;
	font-style: italic;
	padding: 40px;
}

@media (max-width: 768px) {
	.judge-item {
		flex-direction: column;
		gap: 15px;
		align-items: stretch;
	}
	
	.report-actions {
		flex-direction: column;
		gap: 10px;
	}
}
</style>

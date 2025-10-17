<?php use function App\{url, is_organizer, hierarchical_back_url, home_url}; ?>
<h2>Print Reports</h2>
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
                            <a href="<?= url('print/contestant/' . $contestant['id']) ?>" class="btn btn-primary" target="_blank">🖨️ Print</a>
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
                            <a href="<?= url('print/judge/' . $judge['id']) ?>" class="btn btn-primary" target="_blank">🖨️ Print</a>
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
			foreach ($structure as $row) {
				if ($row['id']) {
					$groupedStructure[$row['contest_id']][$row['id']] = [
						'contest_name' => $row['contest_name'],
						'category_name' => $row['name']
					];
				}
			}
			?>
			<?php if (empty($groupedStructure)): ?>
				<p class="no-data">No categories found.</p>
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
                                    <a href="<?= url('print/category/' . $categoryId) ?>" class="btn btn-primary" target="_blank">🖨️ Print</a>
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
</style>

<script>
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
</script>

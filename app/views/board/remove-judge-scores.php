<?php use function App\{url, csrf_field}; ?>
<h2>Judge Score Removal</h2>
<div class="navigation-buttons">
	<a href="/board" class="btn btn-outline">🏠 Dashboard</a>
</div>

<div class="removal-section">
	<h3>Initiate Score Removal Request</h3>
	<p>Remove a judge's scores from the totals. This requires co-signatures from Auditor, Tally Master, and optionally the Head Judge.</p>
	
	<form method="POST" action="/board/remove-judge-scores" class="removal-form">
		<?= csrf_field() ?>
		<div class="form-group">
			<label for="judge_id">Select Judge:</label>
			<select id="judge_id" name="judge_id" required class="form-control">
				<option value="">Choose a judge...</option>
				<?php foreach ($judges as $judge): ?>
					<option value="<?= $judge['id'] ?>"><?= htmlspecialchars($judge['name']) ?> (<?= htmlspecialchars($judge['email']) ?>)</option>
				<?php endforeach; ?>
			</select>
		</div>
		
		<div class="form-group">
			<label for="reason">Reason for Removal:</label>
			<textarea id="reason" name="reason" required class="form-control" rows="4" placeholder="Please provide a detailed reason for removing this judge's scores..."></textarea>
		</div>
		
		<div class="warning-box">
			<h4>⚠️ Important Notice</h4>
			<p>Removing judge scores is a serious action that requires:</p>
			<ul>
				<li>✅ Board or Admin authorization (you)</li>
				<li>✅ Auditor co-signature</li>
				<li>✅ Tally Master co-signature</li>
				<li>✅ Optional Head Judge co-signature</li>
			</ul>
			<p>This action cannot be undone once completed.</p>
		</div>
		
		<button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to initiate score removal for this judge? This action requires multiple co-signatures.')">Initiate Removal Request</button>
	</form>
</div>

<div class="pending-requests">
	<h3>Pending Removal Requests</h3>
	<p>Track the status of score removal requests.</p>
	<div class="requests-list">
		<p class="text-muted">No pending requests available.</p>
	</div>
</div>

<style>
.removal-section {
	margin: 20px 0;
	padding: 20px;
	background: white;
	border-radius: 8px;
	box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.removal-form {
	max-width: 600px;
}

.form-group {
	margin-bottom: 20px;
}

.form-group label {
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
	font-family: inherit;
}

.form-control:focus {
	outline: none;
	border-color: #007bff;
	box-shadow: 0 0 0 2px rgba(0,123,255,0.25);
}

.warning-box {
	background: #fff3cd;
	border: 1px solid #ffeaa7;
	border-radius: 6px;
	padding: 15px;
	margin: 20px 0;
}

.warning-box h4 {
	margin: 0 0 10px 0;
	color: #856404;
}

.warning-box p {
	margin: 0 0 10px 0;
	color: #856404;
}

.warning-box ul {
	margin: 10px 0;
	padding-left: 20px;
	color: #856404;
}

.warning-box li {
	margin-bottom: 5px;
}

.btn {
	padding: 10px 20px;
	border: none;
	border-radius: 4px;
	cursor: pointer;
	text-decoration: none;
	display: inline-block;
	font-size: 14px;
	font-weight: bold;
	transition: background-color 0.2s;
}

.btn-danger {
	background: #dc3545;
	color: white;
}

.btn-danger:hover {
	background: #c82333;
}

.pending-requests {
	margin: 30px 0;
	padding: 20px;
	background: white;
	border-radius: 8px;
	box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.pending-requests h3 {
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
	.removal-form {
		max-width: 100%;
	}
	
	.warning-box {
		padding: 10px;
	}
}
</style>

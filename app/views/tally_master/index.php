<?php use function App\{url, current_user}; ?>
<div class="container">
	<h1>Tally Master Dashboard</h1>
	<p>Welcome, <?= htmlspecialchars(current_user()['preferred_name'] ?? current_user()['name']) ?>!</p>
	
	<div class="dashboard-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin: 20px 0;">
		<div class="dashboard-card" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
			<h3>📊 System Overview</h3>
			<div class="stats-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
				<div class="stat-item">
					<strong><?= $totalContests ?></strong>
					<br><small>Contests</small>
				</div>
				<div class="stat-item">
					<strong><?= $totalCategories ?></strong>
					<br><small>Categories</small>
				</div>
				<div class="stat-item">
					<strong><?= $totalSubcategories ?></strong>
					<br><small>Subcategories</small>
				</div>
				<div class="stat-item">
					<strong><?= $totalContestants ?></strong>
					<br><small>Contestants</small>
				</div>
				<div class="stat-item">
					<strong><?= $totalJudges ?></strong>
					<br><small>Judges</small>
				</div>
			</div>
		</div>
		
		<div class="dashboard-card" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
			<h3>✅ Certification Status</h3>
			<div class="certification-stats">
				<div class="stat-item" style="margin-bottom: 10px;">
					<strong><?= $certificationStatus['judge_certified'] ?></strong> / <strong><?= $certificationStatus['total_subcategories'] ?></strong>
					<br><small>Judge Certifications</small>
				</div>
				<div class="stat-item" style="margin-bottom: 10px;">
					<strong><?= $certificationStatus['tally_master_certified'] ?></strong> / <strong><?= $certificationStatus['total_subcategories'] ?></strong>
					<br><small>Tally Master Certifications</small>
				</div>
				<div class="stat-item" style="color: <?= $certificationStatus['pending_certification'] > 0 ? '#d63384' : '#198754'; ?>">
					<strong><?= $certificationStatus['pending_certification'] ?></strong>
					<br><small>Pending Certification</small>
				</div>
			</div>
		</div>
		
		<div class="dashboard-card" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
			<h3>🔍 Quick Actions</h3>
			<div class="action-buttons" style="display: flex; flex-direction: column; gap: 10px;">
				<a href="<?= url('tally-master/score-review') ?>" class="btn btn-primary">
					📊 Review All Scores
				</a>
				<a href="<?= url('tally-master/certification') ?>" class="btn btn-success">
					✅ Manage Certifications
				</a>
			</div>
		</div>
	</div>
	
	<?php if ($certificationStatus['pending_certification'] > 0): ?>
		<div class="alert alert-warning" style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0;">
			<h4>⚠️ Action Required</h4>
			<p>There are <strong><?= $certificationStatus['pending_certification'] ?></strong> subcategories pending your certification. 
			<a href="<?= url('tally-master/certification') ?>">Review and certify totals</a> once all judges have submitted their scores.</p>
		</div>
	<?php endif; ?>
	
	<?php if ($certificationStatus['tally_master_certified'] === $certificationStatus['total_subcategories'] && $certificationStatus['total_subcategories'] > 0): ?>
		<div class="alert alert-success" style="background: #d1edff; border: 1px solid #74c0fc; padding: 15px; border-radius: 5px; margin: 20px 0;">
			<h4>🎉 All Certifications Complete!</h4>
			<p>All subcategories have been certified. The scoring process is complete.</p>
		</div>
	<?php endif; ?>
</div>

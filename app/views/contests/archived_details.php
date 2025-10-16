<?php use function App\{url, is_organizer, hierarchical_back_url, home_url}; ?>
<h2>Archived Contest Details</h2>
<div class="navigation-buttons">
	<a href="<?= url('admin/archived-contests') ?>" class="btn btn-secondary">← Back to Archived Contests</a>
	<a href="<?= home_url() ?>" class="btn btn-outline">🏠 Home</a>
</div>

<div class="card">
	<h3><?= htmlspecialchars($contest['name']) ?></h3>
	<div class="contest-info">
		<p><strong>Start Date:</strong> <?= htmlspecialchars($contest['start_date']) ?></p>
		<p><strong>End Date:</strong> <?= htmlspecialchars($contest['end_date']) ?></p>
		<p><strong>Archived By:</strong> <?= htmlspecialchars($contest['archived_by']) ?></p>
		<p><strong>Archived Date:</strong> <?= htmlspecialchars($contest['archived_at']) ?></p>
		<?php if (!empty($contest['description'])): ?>
			<p><strong>Description:</strong> <?= htmlspecialchars($contest['description']) ?></p>
		<?php endif; ?>
	</div>
</div>

<div class="row">
	<div class="col-6">
		<div class="card">
			<h4>Categories (<?= count($categories) ?>)</h4>
			<?php if (empty($categories)): ?>
				<p>No categories found.</p>
			<?php else: ?>
				<ul>
					<?php foreach ($categories as $category): ?>
						<li><?= htmlspecialchars($category['name']) ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	</div>
	
	<div class="col-6">
		<div class="card">
			<h4>Subcategories (<?= count($subcategories) ?>)</h4>
			<?php if (empty($subcategories)): ?>
				<p>No subcategories found.</p>
			<?php else: ?>
				<ul>
					<?php foreach ($subcategories as $subcategory): ?>
						<li><?= htmlspecialchars($subcategory['category_name']) ?> - <?= htmlspecialchars($subcategory['name']) ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	</div>
</div>

<div class="row">
	<div class="col-6">
		<div class="card">
			<h4>Contestants (<?= count($contestants) ?>)</h4>
			<?php if (empty($contestants)): ?>
				<p>No contestants found.</p>
			<?php else: ?>
				<ul>
					<?php foreach ($contestants as $contestant): ?>
						<li>
							<?php if (!empty($contestant['contestant_number'])): ?>
								#<?= htmlspecialchars($contestant['contestant_number']) ?> - 
							<?php endif; ?>
							<?= htmlspecialchars($contestant['name']) ?>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	</div>
	
	<div class="col-6">
		<div class="card">
			<h4>Judges (<?= count($judges) ?>)</h4>
			<?php if (empty($judges)): ?>
				<p>No judges found.</p>
			<?php else: ?>
				<ul>
					<?php foreach ($judges as $judge): ?>
						<li>
							<?= htmlspecialchars($judge['name']) ?>
							<?php if (!empty($judge['is_head_judge'])): ?>
								<span class="badge badge-primary">Head Judge</span>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	</div>
</div>

<div class="card">
	<div class="alert alert-info">
		<p><strong>Note:</strong> This contest has been archived. All data has been preserved for historical reference but is no longer active for scoring or management.</p>
	</div>
</div>

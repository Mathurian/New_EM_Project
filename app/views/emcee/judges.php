<?php use function App\{url}; ?>
<h2>Judges by Category</h2>
<p><a href="<?= url('emcee') ?>">Back to Contestant Bios</a></p>

<?php if (empty($groupedJudges)): ?>
	<p>No judges assigned to categories yet.</p>
<?php else: ?>
	<?php foreach ($groupedJudges as $categoryName => $judges): ?>
		<div class="category-section">
			<h3><?= htmlspecialchars($categoryName) ?></h3>
			<div class="judges-grid">
				<?php foreach ($judges as $judge): ?>
					<div class="judge-card">
						<?php if (!empty($judge['image_path'])): ?>
							<div class="judge-image">
								<img src="<?= url($judge['image_path']) ?>" alt="<?= htmlspecialchars($judge['judge_name']) ?>" />
							</div>
						<?php endif; ?>
                        <div class="judge-info">
                            <h4>
                                <?= htmlspecialchars($judge['judge_name']) ?>
                                <?php if (!empty($judge['is_head_judge'])): ?>
                                    <span style="display:inline-block; margin-left:8px; padding:2px 6px; background:#ffc107; color:#000; border-radius:4px; font-size:12px; font-weight:bold;">Head Judge</span>
                                <?php endif; ?>
                            </h4>
							<?php if (!empty($judge['email'])): ?>
								<p><strong>Email:</strong> <a href="mailto:<?= htmlspecialchars($judge['email']) ?>"><?= htmlspecialchars($judge['email']) ?></a></p>
							<?php endif; ?>
							<?php if (!empty($judge['bio'])): ?>
								<div class="judge-bio">
									<p><?= nl2br(htmlspecialchars($judge['bio'])) ?></p>
								</div>
							<?php else: ?>
								<div class="judge-bio">
									<p><em>No bio available</em></p>
								</div>
							<?php endif; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endforeach; ?>
<?php endif; ?>

<style>
.category-section {
	margin-bottom: 40px;
}

.category-section h3 {
	border-bottom: 2px solid #007cba;
	padding-bottom: 10px;
	margin-bottom: 20px;
	color: #333;
}

.judges-grid {
	display: grid;
	grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
	gap: 20px;
}

.judge-card {
	border: 1px solid #ddd;
	border-radius: 8px;
	padding: 15px;
	background: #f9f9f9;
}

.judge-image img {
	width: 100%;
	height: 200px;
	object-fit: cover;
	border-radius: 5px;
	margin-bottom: 10px;
}

.judge-info h4 {
	margin: 0 0 10px 0;
	color: #333;
}

.judge-bio {
	margin-top: 10px;
	padding: 10px;
	background: #f0f0f0;
	border-radius: 5px;
	font-style: italic;
}
</style>

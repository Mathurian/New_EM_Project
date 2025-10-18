<?php use function App\{url, hierarchical_back_url, home_url}; ?>
<h2>Score Contestants</h2>
<div class="navigation-buttons">
	<a href="<?= hierarchical_back_url() ?>" class="btn btn-secondary">← Back</a>
	<a href="<?= home_url() ?>" class="btn btn-outline">🏠 Home</a>
</div>

<div class="subcategory-info">
	<h3><?= htmlspecialchars($subcategory['category_name']) ?> - <?= htmlspecialchars($subcategory['name']) ?></h3>
	<?php if (!empty($subcategory['description'])): ?>
		<p class="subcategory-description"><?= htmlspecialchars($subcategory['description']) ?></p>
	<?php endif; ?>
</div>

<?php if (empty($contestants)): ?>
	<div class="alert alert-info">
		<p>No contestants have been assigned to this subcategory yet.</p>
	</div>
<?php else: ?>
	<div class="contestants-grid">
		<?php foreach ($contestants as $contestant): ?>
			<div class="contestant-card">
				<?php if (!empty($contestant['image_path'])): ?>
					<div class="contestant-image">
						<img src="<?= $contestant['image_path'] ?>" alt="<?= htmlspecialchars($contestant['name']) ?>" />
					</div>
				<?php endif; ?>
				<div class="contestant-info">
					<h4><?= htmlspecialchars($contestant['name']) ?></h4>
					<?php if (!empty($contestant['contestant_number'])): ?>
						<p class="contestant-number">Contestant #<?= htmlspecialchars($contestant['contestant_number']) ?></p>
					<?php endif; ?>
					<?php if (!empty($contestant['bio'])): ?>
						<p class="bio-preview"><?= htmlspecialchars(substr($contestant['bio'], 0, 100)) ?><?= strlen($contestant['bio']) > 100 ? '...' : '' ?></p>
					<?php endif; ?>
				</div>
				<div class="contestant-actions">
					<a href="<?= url('score/' . $subcategory['id'] . '/contestant/' . $contestant['id']) ?>" class="btn btn-primary">
						🎯 Score This Contestant
					</a>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
<?php endif; ?>

<style>
.navigation-buttons {
	margin-bottom: 20px;
	display: flex;
	gap: 10px;
	flex-wrap: wrap;
}

.btn-outline {
	background: transparent;
	color: var(--accent-color);
	border: 1px solid var(--accent-color);
}

.btn-outline:hover {
	background: var(--accent-color);
	color: white;
	text-decoration: none;
}

.subcategory-info {
	background: var(--bg-secondary);
	border: 1px solid var(--border-color);
	border-radius: 8px;
	padding: 20px;
	margin: 20px 0;
}

.subcategory-info h3 {
	margin: 0 0 10px 0;
	color: #007bff;
	font-size: 1.4em;
}

.subcategory-description {
	color: #666;
	font-style: italic;
	margin: 0;
}

.contestants-grid {
	display: grid;
	grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
	gap: 20px;
	margin: 20px 0;
}

.contestant-card {
	background: white;
	border: 1px solid #dee2e6;
	border-radius: 8px;
	padding: 20px;
	box-shadow: 0 2px 4px rgba(0,0,0,0.1);
	transition: transform 0.2s, box-shadow 0.2s;
	display: flex;
	flex-direction: column;
}

.contestant-card:hover {
	transform: translateY(-2px);
	box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.contestant-image img {
	width: 100%;
	height: 150px;
	object-fit: cover;
	border-radius: 5px;
	margin-bottom: 15px;
}

.contestant-info h4 {
	margin: 0 0 5px 0;
	color: #333;
	font-size: 1.2em;
}

.contestant-number {
	font-weight: bold;
	color: #007bff;
	margin: 5px 0;
	font-size: 0.9em;
}

.bio-preview {
	color: #666;
	font-style: italic;
	margin: 10px 0;
	font-size: 0.9em;
	line-height: 1.4;
}

.contestant-actions {
	margin-top: auto;
	padding-top: 15px;
}

.btn {
	display: inline-block;
	padding: 10px 20px;
	text-decoration: none;
	border-radius: 4px;
	font-weight: bold;
	text-align: center;
	transition: background-color 0.2s;
	width: 100%;
}

.btn-primary {
	background: #007bff;
	color: white;
}

.btn-primary:hover {
	background: #0056b3;
	color: white;
	text-decoration: none;
}

.alert {
	padding: 15px 20px;
	margin: 20px 0;
	border-radius: 4px;
	border: 1px solid transparent;
}

.alert-info {
	background: #d1ecf1;
	border-color: #bee5eb;
	color: #0c5460;
}

@media (max-width: 768px) {
	.contestants-grid {
		grid-template-columns: 1fr;
	}
	
	.contestant-card {
		padding: 15px;
	}
}
</style>

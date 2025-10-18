<?php use function App\{url}; ?>
<h2>Contestant Bios</h2>
<p><a href="/judge">← Back to Dashboard</a></p>

<?php if (empty($contestants)): ?>
	<p>No contestants available.</p>
<?php else: ?>
	<div class="contestants-grid">
		<?php foreach ($contestants as $contestant): ?>
			<div class="contestant-card">
				<?php if (!empty($contestant['image_path'])): ?>
					<div class="contestant-image">
						<img src="<?= url($contestant['image_path']) ?>" alt="<?= htmlspecialchars($contestant['name']) ?>" />
					</div>
				<?php endif; ?>
				
				<div class="contestant-info">
					<h3>
						<?= htmlspecialchars($contestant['name']) ?>
						<?php if (!empty($contestant['contestant_number'])): ?>
							<span class="contestant-number">#<?= htmlspecialchars($contestant['contestant_number']) ?></span>
						<?php endif; ?>
					</h3>
					
					<?php if (!empty($contestant['bio'])): ?>
						<p class="bio-preview"><?= htmlspecialchars(substr($contestant['bio'], 0, 100)) ?><?= strlen($contestant['bio']) > 100 ? '...' : '' ?></p>
					<?php endif; ?>
					
					<a href="/judge/contestant/<?= $contestant['contestant_number'] ?>" class="view-bio-btn">View Full Bio</a>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
<?php endif; ?>

<style>
.contestants-grid {
	display: grid;
	grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
	gap: 20px;
	margin-top: 20px;
}

.contestant-card {
	background: white;
	border: 1px solid #dee2e6;
	border-radius: 8px;
	padding: 20px;
	box-shadow: 0 2px 4px rgba(0,0,0,0.1);
	transition: transform 0.2s, box-shadow 0.2s;
}

.contestant-card:hover {
	transform: translateY(-2px);
	box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.contestant-image {
	text-align: center;
	margin-bottom: 15px;
}

.contestant-image img {
	max-width: 150px;
	max-height: 150px;
	border-radius: 8px;
	object-fit: cover;
}

.contestant-info h3 {
	margin: 0 0 10px 0;
	color: #333;
	display: flex;
	align-items: center;
	gap: 10px;
}

.contestant-number {
	background: #007bff;
	color: white;
	padding: 2px 8px;
	border-radius: 4px;
	font-size: 0.8em;
	font-weight: bold;
}

.bio-preview {
	color: #666;
	font-size: 0.9em;
	line-height: 1.4;
	margin: 10px 0;
}

.view-bio-btn {
	display: inline-block;
	background: #007bff;
	color: white;
	padding: 8px 16px;
	text-decoration: none;
	border-radius: 4px;
	font-size: 0.9em;
	transition: background-color 0.2s;
}

.view-bio-btn:hover {
	background: #0056b3;
	text-decoration: none;
	color: white;
}

@media (max-width: 768px) {
	.contestants-grid {
		grid-template-columns: 1fr;
		gap: 15px;
	}
	
	.contestant-card {
		padding: 15px;
	}
}
</style>

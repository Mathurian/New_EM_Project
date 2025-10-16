<?php use function App\{url}; ?>
<h2>Judgy Time!</h2>
<p>Select a subcategory to begin scoring contestants.</p>

<?php if (empty($subcategories)): ?>
	<div class="alert alert-info">
		<p>You haven't been assigned to any subcategories yet. Please contact an organizer.</p>
	</div>
<?php else: ?>
	<div class="subcategories-grid">
		<?php foreach ($subcategories as $subcategory): ?>
			<div class="subcategory-card">
				<div class="subcategory-header">
					<h3><?= htmlspecialchars($subcategory['category_name']) ?></h3>
					<h4><?= htmlspecialchars($subcategory['name']) ?></h4>
				</div>
				<div class="subcategory-actions">
					<a href="<?= url('judge/subcategory/' . $subcategory['id']) ?>" class="btn btn-primary">
						🎯 Score Contestants
					</a>
					<a href="<?= url('results/' . $subcategory['id']) ?>" class="btn btn-secondary">
						📊 View Results
					</a>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
<?php endif; ?>

<style>
.subcategories-grid {
	display: grid;
	grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
	gap: 20px;
	margin: 20px 0;
}

.subcategory-card {
	background: white;
	border: 1px solid #dee2e6;
	border-radius: 8px;
	padding: 20px;
	box-shadow: 0 2px 4px rgba(0,0,0,0.1);
	transition: transform 0.2s, box-shadow 0.2s;
}

.subcategory-card:hover {
	transform: translateY(-2px);
	box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.subcategory-header h3 {
	margin: 0 0 5px 0;
	color: #007bff;
	font-size: 1.1em;
}

.subcategory-header h4 {
	margin: 0 0 15px 0;
	color: #333;
	font-size: 1.3em;
	font-weight: bold;
}

.subcategory-actions {
	display: flex;
	gap: 10px;
	flex-wrap: wrap;
}

.subcategory-actions .btn {
	flex: 1;
	min-width: 120px;
	text-align: center;
	text-decoration: none;
	padding: 10px 15px;
	border-radius: 4px;
	font-weight: bold;
	transition: background-color 0.2s;
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

.btn-secondary {
	background: #6c757d;
	color: white;
}

.btn-secondary:hover {
	background: #545b62;
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
	.subcategories-grid {
		grid-template-columns: 1fr;
	}
	
	.subcategory-actions {
		flex-direction: column;
	}
	
	.subcategory-actions .btn {
		flex: none;
	}
}
</style>
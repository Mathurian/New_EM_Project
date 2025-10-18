<?php use function App\{url, is_organizer, is_judge, hierarchical_back_url, home_url}; ?>
<h2>All Results</h2>
<div class="navigation-buttons">
	<a href="<?= hierarchical_back_url() ?>" class="btn btn-secondary">← Back</a>
	<a href="<?= home_url() ?>" class="btn btn-outline">🏠 Home</a>
</div>

<?php if (empty($results)): ?>
	<div class="alert alert-info">
		<p>No results available yet. Scores will appear here once judges start scoring contestants.</p>
	</div>
<?php else: ?>
    <?php 
    // Group by category name without changing backend: derive from $results structure
    $grouped = [];
    foreach ($results as $scId => $data) {
        $cat = $data['subcategory']['category_name'] ?? 'Uncategorized';
        $grouped[$cat][] = [$scId, $data];
    }
    ?>
    <div class="results-container">
        <?php foreach ($grouped as $categoryName => $rows): ?>
            <div class="category-group">
                <button class="category-toggle" type="button" aria-expanded="false">
                    <span class="twist">▶</span>
                    <strong><?= htmlspecialchars($categoryName) ?></strong>
                </button>
                <div class="subcategory-list" style="display:none;">
                    <?php foreach ($rows as [$subcategoryId, $data]): ?>
                        <div class="subcategory-results">
                            <h3><?= htmlspecialchars($data['subcategory']['name']) ?></h3>
                            <div class="results-actions">
                                <a href="<?= url('results/' . $subcategoryId) ?>" class="btn btn-primary">View Detailed Results</a>
                                <?php if (is_organizer()): ?>
                                    <a href="<?= url('results/' . $subcategoryId . '/detailed') ?>" class="btn btn-secondary">View All Scores</a>
                                <?php endif; ?>
                            </div>
                            <table class="results-table">
                                <thead>
                                    <tr>
                                        <th>Rank</th>
                                        <th>Contestant</th>
                                        <th>Total Score</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $rank = 1; foreach ($data['results'] as $result): ?>
                                        <tr>
                                            <td><?= $rank++ ?></td>
                                            <td><?= htmlspecialchars($result['contestantName']) ?></td>
                                            <td><?= number_format((float)$result['totalScore'], 2) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endforeach; ?>
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

.results-container { display: grid; gap: 20px; margin: 20px 0; }
.category-group { border: 1px solid var(--border-color); border-radius: 8px; background: var(--bg-primary); }
.category-toggle { width: 100%; text-align: left; padding: 12px 16px; border: 0; cursor: pointer; display: flex; align-items: center; gap: 8px; }
.category-toggle .twist { display: inline-block; transition: transform 0.2s ease; }
.category-toggle[aria-expanded="true"] .twist { transform: rotate(90deg); }
.subcategory-list { padding: 10px 16px 16px 16px; }

.subcategory-results {
	background: var(--bg-primary);
	border: 1px solid var(--border-color);
	border-radius: 8px;
	padding: 20px;
	box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.subcategory-results h3 {
	margin: 0 0 15px 0;
	color: var(--accent-color);
	font-size: 1.3em;
}

.results-actions {
	margin-bottom: 20px;
	display: flex;
	gap: 10px;
	flex-wrap: wrap;
}

.results-table {
	width: 100%;
	border-collapse: collapse;
	margin-top: 10px;
}

.results-table th,
.results-table td {
	padding: 12px;
	text-align: left;
	border-bottom: 1px solid var(--border-color);
}

.results-table th {
	background: var(--bg-tertiary);
	font-weight: bold;
	color: var(--text-primary);
}

.results-table tbody tr:hover {
	background: var(--bg-secondary);
}

.btn {
	display: inline-block;
	padding: 8px 16px;
	text-decoration: none;
	border-radius: 4px;
	font-weight: bold;
	transition: background-color 0.2s;
}

.btn-primary {
	background: var(--accent-color);
	color: white;
}

.btn-primary:hover {
	opacity: 0.9;
	color: white;
	text-decoration: none;
}

.btn-secondary {
	background: var(--bg-tertiary);
	color: var(--text-primary);
	border: 1px solid var(--border-color);
}

.btn-secondary:hover {
	background: var(--bg-secondary);
	color: var(--text-primary);
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

[data-theme="dark"] .alert-info {
	background: #1e3d4d;
	border-color: #2d4d5a;
	color: #d1ecf1;
}

@media (max-width: 768px) {
	.results-actions {
		flex-direction: column;
	}
	
	.btn {
		width: 100%;
		text-align: center;
	}
}
</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.category-toggle').forEach(function(btn){
        btn.addEventListener('click', function(){
            const expanded = this.getAttribute('aria-expanded') === 'true';
            this.setAttribute('aria-expanded', expanded ? 'false' : 'true');
            const list = this.nextElementSibling;
            if (list) {
                list.style.display = expanded ? 'none' : 'block';
            }
        });
    });
});
</script>

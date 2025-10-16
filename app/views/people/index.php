<?php use function App\{url, hierarchical_back_url, home_url}; ?>
<h2>People Management</h2>
<div class="navigation-buttons">
	<a href="<?= hierarchical_back_url() ?>" class="btn btn-secondary">← Back</a>
	<a href="<?= home_url() ?>" class="btn btn-outline">🏠 Home</a>
</div>

<div class="people-actions">
	<a href="<?= url('users/new') ?>" class="btn btn-primary">+ Create New User</a>
</div>

<?php if (!empty($_SESSION['success_message'])): ?>
	<p style="color: green; font-weight: bold;"><?= htmlspecialchars($_SESSION['success_message']) ?></p>
	<?php unset($_SESSION['success_message']); ?>
<?php endif; ?>

<div class="two-col">
	<div>
		<h3>Contestants</h3>
		<table>
			<tr><th>Number</th><th>Name</th><th>Email</th><th>Gender</th><th>Actions</th></tr>
			<?php foreach ($contestants as $p): ?>
				<tr>
					<td><?= $p['contestant_number'] ? htmlspecialchars($p['contestant_number']) : '-' ?></td>
					<td><?= htmlspecialchars($p['name']) ?></td>
					<td><?= htmlspecialchars($p['email'] ?? '') ?></td>
					<td><?= htmlspecialchars($p['gender'] ?? '') ?></td>
					<td>
						<a href="/people/contestants/<?= urlencode($p['id']) ?>/edit">Edit</a> |
						<a href="/people/contestants/<?= urlencode($p['id']) ?>/bio">Bio</a> |
						<a href="/print/contestant/<?= urlencode($p['id']) ?>">Print Scores</a> |
						<form method="post" action="/people/contestants/<?= urlencode($p['id']) ?>/delete" style="display:inline">
							<button type="submit" onclick="return confirm('Are you sure you want to delete this contestant?')">Delete</button>
						</form>
					</td>
				</tr>
			<?php endforeach; ?>
		</table>
	</div>
	<div>
		<h3>Judges</h3>
		<table>
			<tr><th>Name</th><th>Email</th><th>Gender</th><th>Actions</th></tr>
			<?php foreach ($judges as $j): ?>
				<tr>
					<td><?= htmlspecialchars($j['name']) ?></td>
					<td><?= htmlspecialchars($j['email'] ?? '') ?></td>
					<td><?= htmlspecialchars($j['gender'] ?? '') ?></td>
					<td>
						<a href="/people/judges/<?= urlencode($j['id']) ?>/edit">Edit</a> |
						<a href="/people/judges/<?= urlencode($j['id']) ?>/bio">Bio</a> |
						<a href="/print/judge/<?= urlencode($j['id']) ?>">Print Scores</a> |
						<form method="post" action="/people/judges/<?= urlencode($j['id']) ?>/delete" style="display:inline">
							<button type="submit" onclick="return confirm('Are you sure you want to delete this judge?')">Delete</button>
						</form>
					</td>
				</tr>
			<?php endforeach; ?>
		</table>
	</div>
</div>



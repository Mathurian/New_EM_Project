<h2>Emcee Dashboard</h2>

<div class="emcee-dashboard">
	<div class="dashboard-grid">
		<a href="/emcee/scripts" class="dashboard-card">
			<div class="card-icon">📋</div>
			<div class="card-title">Scripts</div>
			<div class="card-description">View and access contest scripts</div>
		</a>
		
		<a href="/emcee/judges" class="dashboard-card">
			<div class="card-icon">⚖️</div>
			<div class="card-title">Judges</div>
			<div class="card-description">View judges by category</div>
		</a>
		
		<a href="/emcee/contestants" class="dashboard-card">
			<div class="card-icon">👥</div>
			<div class="card-title">Contestants</div>
			<div class="card-description">View contestant bios and information</div>
		</a>
	</div>
</div>

<style>
.emcee-dashboard {
	margin: 20px 0;
}

.dashboard-grid {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
	gap: 20px;
	margin-top: 20px;
}

.dashboard-card {
	background: white;
	border: 1px solid #dee2e6;
	border-radius: 8px;
	padding: 30px 20px;
	text-decoration: none;
	color: inherit;
	box-shadow: 0 2px 4px rgba(0,0,0,0.1);
	transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s;
	display: flex;
	flex-direction: column;
	align-items: center;
	text-align: center;
}

.dashboard-card:hover {
	transform: translateY(-2px);
	box-shadow: 0 4px 8px rgba(0,0,0,0.15);
	border-color: #007bff;
	text-decoration: none;
	color: inherit;
}

.card-icon {
	font-size: 3em;
	margin-bottom: 15px;
}

.card-title {
	font-size: 1.3em;
	font-weight: bold;
	margin-bottom: 10px;
	color: #333;
}

.card-description {
	font-size: 0.9em;
	color: #666;
	line-height: 1.4;
}

@media (max-width: 768px) {
	.dashboard-grid {
		grid-template-columns: 1fr;
		gap: 15px;
	}
	
	.dashboard-card {
		padding: 25px 15px;
	}
	
	.card-icon {
		font-size: 2.5em;
		margin-bottom: 12px;
	}
}
</style>

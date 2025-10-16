<?php use function App\{url}; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Category Results: <?= htmlspecialchars($category['name']) ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; color: #333; }
        .container { max-width: 800px; margin: 0 auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h1, h2, h3 { color: #0056b3; }
        .header-info { margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid #eee; }
        .header-info p { margin: 5px 0; }
        .results-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .results-table th, .results-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .results-table th { background-color: #f2f2f2; }
        .results-table tr:nth-child(even) { background-color: #f9f9f9; }
        .rank-1 { background-color: #d4edda !important; font-weight: bold; }
        .rank-2 { background-color: #fff3cd !important; font-weight: bold; }
        .rank-3 { background-color: #f8d7da !important; font-weight: bold; }
        .subcategory-list { margin-top: 20px; }
        .subcategory-list ul { list-style-type: none; padding: 0; }
        .subcategory-list li { padding: 5px 0; border-bottom: 1px solid #eee; }
        .print-button { display: block; width: 150px; margin: 20px auto; padding: 10px; text-align: center; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px; }
        @media print {
            .print-button { display: none; }
            .container { box-shadow: none; border: none; }
            body { margin: 0; }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="<?= url('results') ?>" class="print-button" style="background-color: #6c757d;">Back to Results</a>
        <a href="#" onclick="window.print()" class="print-button">Print Report</a>

        <h1>Category Results Report</h1>
        <div class="header-info">
            <p><strong>Contest:</strong> <?= htmlspecialchars($category['contest_name']) ?></p>
            <p><strong>Category:</strong> <?= htmlspecialchars($category['name']) ?></p>
            <p><strong>Description:</strong> <?= htmlspecialchars($category['description'] ?? 'N/A') ?></p>
            <p><strong>Report Generated:</strong> <?= date('Y-m-d H:i:s') ?></p>
        </div>

        <?php if (!empty($subcategories)): ?>
            <div class="subcategory-list">
                <h3>Subcategories</h3>
                <ul>
                    <?php foreach ($subcategories as $subcategory): ?>
                        <li><?= htmlspecialchars($subcategory['name']) ?>
                            <?php if ($subcategory['description']): ?>
                                - <?= htmlspecialchars($subcategory['description']) ?>
                            <?php endif; ?>
                            <?php if ($subcategory['score_cap']): ?>
                                (Score Cap: <?= htmlspecialchars($subcategory['score_cap']) ?>)
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (empty($contestants)): ?>
            <p>No contestants found in this category.</p>
        <?php else: ?>
            <h3>Contestant Rankings</h3>
            <table class="results-table">
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Contestant Number</th>
                        <th>Name</th>
                        <th>Total Score</th>
                        <th>Email</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $rank = 1; foreach ($contestants as $contestant): ?>
                        <tr class="rank-<?= $rank <= 3 ? $rank : '' ?>">
                            <td><?= $rank++ ?></td>
                            <td><?= htmlspecialchars($contestant['contestant_number'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($contestant['name']) ?></td>
                            <td><?= number_format((float)$contestant['total_score'], 2) ?></td>
                            <td><?= htmlspecialchars($contestant['email'] ?? 'N/A') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <?php if (count($contestants) > 0): ?>
                <div style="margin-top: 20px; padding: 15px; background-color: #e9ecef; border-radius: 5px;">
                    <h4>Summary</h4>
                    <p><strong>Total Contestants:</strong> <?= count($contestants) ?></p>
                    <p><strong>Highest Score:</strong> <?= number_format((float)$contestants[0]['total_score'], 2) ?></p>
                    <?php if (count($contestants) > 1): ?>
                        <p><strong>Lowest Score:</strong> <?= number_format((float)$contestants[count($contestants)-1]['total_score'], 2) ?></p>
                        <p><strong>Score Range:</strong> <?= number_format((float)$contestants[0]['total_score'] - (float)$contestants[count($contestants)-1]['total_score'], 2) ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>

<?php use function App\{url, calculate_score_tabulation, format_score_tabulation}; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contest Summary: <?= htmlspecialchars($contest['name']) ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; color: #333; }
        .container { max-width: 1000px; margin: 0 auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h1, h2, h3 { color: #0056b3; }
        .header-info { margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid #eee; }
        .header-info p { margin: 5px 0; }
        .category-section { margin-bottom: 30px; border: 1px solid #ddd; padding: 15px; border-radius: 5px; }
        .category-section h3 { margin-top: 0; color: #007bff; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .no-data { color: #888; font-style: italic; }
        .print-button { display: block; width: 150px; margin: 20px auto; padding: 10px; text-align: center; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px; }
        @media print {
            .print-button { display: none; }
            .container { box-shadow: none; border: none; }
            body { margin: 0; }
            .category-section { page-break-inside: avoid; }
        }
    </style>
    <script>
        function handleBackClick(link) {
            // Check if this window was opened by another window (popup/new tab)
            if (window.opener || window.history.length <= 1) {
                // Close the window/tab
                window.close();
                return false; // Prevent default navigation
            }
            // If not a popup, allow normal navigation
            return true;
        }
    </script>
</head>
<body>
    <div class="container">
        <div class="header-info">
            <h1>Contest Summary: <?= htmlspecialchars($contest['name']) ?></h1>
            <p><strong>Contest ID:</strong> <?= htmlspecialchars($contest['id']) ?></p>
            <p><strong>Generated:</strong> <?= date('Y-m-d H:i:s') ?></p>
        </div>

        <?php if (empty($categoryData)): ?>
            <div class="no-data">
                <p>No categories found for this contest.</p>
            </div>
        <?php else: ?>
            <?php foreach ($categoryData as $data): ?>
                <div class="category-section">
                    <h3><?= htmlspecialchars($data['category']['name']) ?></h3>
                    
                    <?php if (empty($data['contestants'])): ?>
                        <p class="no-data">No contestants found for this category.</p>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Contestant</th>
                                    <th>Number</th>
                                    <th>Total Score</th>
                                    <th>Rank</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                // Sort contestants by total score (descending)
                                usort($data['contestants'], function($a, $b) {
                                    return $b['total_current'] <=> $a['total_current'];
                                });
                                
                                $rank = 1;
                                foreach ($data['contestants'] as $contestant): 
                                ?>
                                    <tr>
                                        <td><?= htmlspecialchars($contestant['contestant_name']) ?></td>
                                        <td><?= htmlspecialchars($contestant['contestant_number'] ?? '') ?></td>
                                        <td><?= number_format($contestant['total_current'], 2) ?></td>
                                        <td><?= $rank++ ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <a href="<?= url('admin/print-reports') ?>" class="print-button" style="background-color: #6c757d;" onclick="return handleBackClick(this)">Back to Print Reports</a>
        <a href="#" onclick="window.print()" class="print-button">Print Report</a>
    </div>
</body>
</html>

<?php use function App\{url}; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archived Contest Scores: <?= htmlspecialchars($contest['name']) ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; color: #333; }
        .container { max-width: 1200px; margin: 0 auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h1, h2, h3 { color: #0056b3; }
        .header-info { margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid #eee; }
        .header-info p { margin: 5px 0; }
        .print-button { background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px; display: inline-block; }
        .category-section { margin: 30px 0; page-break-inside: avoid; }
        .category-section { margin: 20px 0; padding-left: 20px; }
        .contestant-section { margin: 15px 0; padding-left: 40px; }
        .scores-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .scores-table th, .scores-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .scores-table th { background-color: #f2f2f2; font-weight: bold; }
        .scores-table tr:nth-child(even) { background-color: #f9f9f9; }
        .total-score { font-weight: bold; background-color: #e7f3ff !important; }
        .winner { background-color: #d4edda !important; font-weight: bold; }
        .runner-up { background-color: #fff3cd !important; font-weight: bold; }
        .third-place { background-color: #f8d7da !important; font-weight: bold; }
        .comment-section { margin-top: 10px; padding: 10px; background-color: #f8f9fa; border-left: 4px solid #007bff; }
        .deduction-section { margin-top: 10px; padding: 10px; background-color: #fff3cd; border-left: 4px solid #ffc107; }
        .category-winner { background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%); padding: 15px; border-radius: 8px; margin: 10px 0; border: 2px solid #28a745; }
        .category-winner h4 { color: #155724; margin-top: 0; }
        @media print {
            .print-button { display: none; }
            .container { box-shadow: none; margin: 0; padding: 0; }
            .category-section { page-break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="<?= url('admin/archived-contest/' . urlencode($contest['id'])) ?>" class="print-button" style="background-color: #6c757d;">Back to Details</a>
        <a href="#" onclick="window.print()" class="print-button">Print Report</a>

        <h1>Archived Contest Complete Score Report</h1>
        <div class="header-info">
            <p><strong>Contest:</strong> <?= htmlspecialchars($contest['name']) ?></p>
            <p><strong>Start Date:</strong> <?= htmlspecialchars($contest['start_date']) ?></p>
            <p><strong>End Date:</strong> <?= htmlspecialchars($contest['end_date']) ?></p>
            <p><strong>Archived By:</strong> <?= htmlspecialchars($contest['archived_by']) ?></p>
            <p><strong>Archived Date:</strong> <?= htmlspecialchars($contest['archived_at']) ?></p>
            <?php if (!empty($contest['description'])): ?>
                <p><strong>Description:</strong> <?= htmlspecialchars($contest['description']) ?></p>
            <?php endif; ?>
        </div>

        <?php if (!empty($categoryTotals)): ?>
        <h2>🏆 Category Winners & Rankings</h2>
        <?php foreach ($categoryTotals as $categoryName => $contestants): ?>
            <div class="category-winner">
                <h4><?= htmlspecialchars($categoryName) ?></h4>
                <?php 
                $rank = 1;
                foreach ($contestants as $contestantName => $data): 
                    $class = '';
                    if ($rank === 1) $class = 'winner';
                    elseif ($rank === 2) $class = 'runner-up';
                    elseif ($rank === 3) $class = 'third-place';
                ?>
                    <p class="<?= $class ?>">
                        <strong><?= $rank ?>.</strong> 
                        <?php if (!empty($data['contestant_number'])): ?>
                            #<?= htmlspecialchars($data['contestant_number']) ?> - 
                        <?php endif; ?>
                        <?= htmlspecialchars($contestantName) ?> 
                        <strong>(<?= number_format($data['total_score'], 1) ?> points)</strong>
                    </p>
                <?php 
                    $rank++;
                endforeach; 
                ?>
            </div>
        <?php endforeach; ?>
        <?php endif; ?>

        <h2>📊 Detailed Scores by Category</h2>
        <?php foreach ($organizedData as $categoryName => $subcategories): ?>
            <div class="category-section">
                <h3><?= htmlspecialchars($categoryName) ?></h3>
                
                <?php foreach ($subcategories as $subcategoryName => $contestants): ?>
                    <div class="category-section">
                        <h4><?= htmlspecialchars($subcategoryName) ?></h4>
                        
                        <?php foreach ($contestants as $contestantName => $data): ?>
                            <div class="contestant-section">
                                <h5>
                                    <?php if (!empty($data['contestant_number'])): ?>
                                        #<?= htmlspecialchars($data['contestant_number']) ?> - 
                                    <?php endif; ?>
                                    <?= htmlspecialchars($contestantName) ?>
                                </h5>
                                
                                <?php if (!empty($data['scores'])): ?>
                                    <table class="scores-table">
                                        <thead>
                                            <tr>
                                                <th>Judge</th>
                                                <th>Criterion</th>
                                                <th>Score</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $subtotal = 0;
                                            foreach ($data['scores'] as $score): 
                                                $subtotal += $score['score'];
                                            ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($score['judge']) ?></td>
                                                    <td><?= htmlspecialchars($score['criterion']) ?></td>
                                                    <td><?= number_format($score['score'], 1) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                            <tr class="total-score">
                                                <td colspan="2"><strong>Category Subtotal:</strong></td>
                                                <td><strong><?= number_format($subtotal, 1) ?></strong></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                <?php else: ?>
                                    <p><em>No scores recorded</em></p>
                                <?php endif; ?>
                                
                                <?php if (!empty($data['deductions'])): ?>
                                    <div class="deduction-section">
                                        <h6>Overall Deductions:</h6>
                                        <?php 
                                        $totalDeductions = 0;
                                        foreach ($data['deductions'] as $deduction): 
                                            $totalDeductions += $deduction['amount'];
                                        ?>
                                            <p><strong>-<?= number_format($deduction['amount'], 1) ?> points:</strong> <?= htmlspecialchars($deduction['comment']) ?></p>
                                        <?php endforeach; ?>
                                        <p><strong>Total Deductions: -<?= number_format($totalDeductions, 1) ?> points</strong></p>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($data['comments'])): ?>
                                    <div class="comment-section">
                                        <h6>Judge Comments:</h6>
                                        <?php foreach ($data['comments'] as $comment): ?>
                                            <p><strong><?= htmlspecialchars($comment['judge']) ?>:</strong> <?= htmlspecialchars($comment['comment']) ?></p>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>

        <div style="margin-top: 40px; padding-top: 20px; border-top: 2px solid #eee;">
            <p><strong>Report Generated:</strong> <?= date('F j, Y \a\t g:i A') ?></p>
            <p><em>This report contains all scores, comments, and deductions for the archived contest "<?= htmlspecialchars($contest['name']) ?>".</em></p>
        </div>
    </div>
</body>
</html>

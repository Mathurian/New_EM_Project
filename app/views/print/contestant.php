<?php use function App\{url, calculate_score_tabulation, format_score_tabulation}; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contestant Scores: <?= htmlspecialchars($contestant['name']) ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; color: #333; }
        .container { max-width: 800px; margin: 0 auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h1, h2, h3 { color: #0056b3; }
        .header-info { margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid #eee; }
        .header-info p { margin: 5px 0; }
        .category-section { margin-bottom: 30px; border: 1px solid #ddd; padding: 15px; border-radius: 5px; }
        .category-section h3 { margin-top: 0; color: #007bff; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .comment-section { margin-top: 15px; padding: 10px; background-color: #f9f9f9; border: 1px solid #eee; border-radius: 4px; }
        .comment-section p { margin: 5px 0; font-size: 0.9em; }
        .comment-section strong { color: #555; }
        .no-data { color: #888; font-style: italic; }
        .print-button { display: block; width: 150px; margin: 20px auto; padding: 10px; text-align: center; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px; }
        @media print {
            .print-button { display: none; }
            .container { box-shadow: none; border: none; }
            body { margin: 0; }
            .category-section { page-break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if (empty($isEmail)): ?>
            <a href="<?= url('admin/print-reports') ?>" class="print-button" style="background-color: #6c757d;">Back to Print Reports</a>
            <a href="#" onclick="window.print()" class="print-button">Print Report</a>
        <?php endif; ?>

        <h1>Contestant Score Report</h1>
        <div class="header-info">
            <p><strong>Contestant:</strong> <?= htmlspecialchars($contestant['name']) ?> (Number: <?= htmlspecialchars($contestant['contestant_number'] ?? 'N/A') ?>)</p>
            <p><strong>Email:</strong> <?= htmlspecialchars($contestant['email'] ?? 'N/A') ?></p>
            <p><strong>Gender:</strong> <?= htmlspecialchars($contestant['gender'] ?? 'N/A') ?></p>
            <p><strong>Total Score:</strong> <?= format_score_tabulation($tabulation, 'overall') ?></p>
            <p><strong>Report Generated:</strong> <?= date('Y-m-d H:i:s') ?></p>
        </div>

        <?php if (empty($scores)): ?>
            <p class="no-data">No scores recorded for this contestant.</p>
        <?php else: ?>
            <?php
            // Group scores by contest and category
            $groupedScores = [];
            foreach ($scores as $score) {
                $groupedScores[$score['contest_name']][$score['category_name']][$score['subcategory_name']][] = $score;
            }
            ?>
            
            <?php foreach ($groupedScores as $contestName => $categories): ?>
                <h2><?= htmlspecialchars($contestName) ?> 
                    <span style="font-size: 0.8em; font-weight: normal; color: #666;">
                        (<?= format_score_tabulation($tabulation['by_contest'][$contestName] ?? ['current' => 0, 'possible' => 0]) ?>)
                    </span>
                </h2>
                
                <?php foreach ($categories as $categoryName => $subcategories): ?>
                    <h3><?= htmlspecialchars($categoryName) ?> 
                        <span style="font-size: 0.8em; font-weight: normal; color: #666;">
                            (<?= format_score_tabulation($tabulation['by_category'][$categoryName] ?? ['current' => 0, 'possible' => 0]) ?>)
                        </span>
                    </h3>
                    
                    <?php foreach ($subcategories as $subcategoryName => $subcategoryScores): ?>
                        <div class="category-section">
                            <h4><?= htmlspecialchars($subcategoryName) ?> 
                                <span style="font-size: 0.8em; font-weight: normal; color: #666;">
                                    (<?= format_score_tabulation($tabulation['by_subcategory'][$subcategoryName] ?? ['current' => 0, 'possible' => 0]) ?>)
                                </span>
                            </h4>
                            
                            <table>
                                <thead>
                                    <tr>
                                        <th>Judge</th>
                                        <th>Criterion</th>
                                        <th>Max Score</th>
                                        <th>Score</th>
                                        <th>Percentage</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($subcategoryScores as $score): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($score['judge_name']) ?></td>
                                            <td><?= htmlspecialchars($score['criterion_name']) ?></td>
                                            <td><?= htmlspecialchars($score['max_score']) ?></td>
                                            <td><?= htmlspecialchars($score['score']) ?></td>
                                            <td><?= $score['max_score'] > 0 ? number_format(($score['score'] / $score['max_score']) * 100, 2) . '%' : 'N/A' ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            
                            <?php
                            // Get comments for this category
                            $subcategoryComments = array_filter($comments, function($c) use ($subcategoryName, $categoryName, $contestName) {
                                return $c['subcategory_name'] === $subcategoryName && 
                                       $c['category_name'] === $categoryName && 
                                       $c['contest_name'] === $contestName;
                            });
                            ?>
                            
                            <?php if (!empty($subcategoryComments)): ?>
                                <div class="comment-section">
                                    <h5>Comments</h5>
                                    <?php foreach ($subcategoryComments as $comment): ?>
                                        <p><strong><?= htmlspecialchars($comment['judge_name']) ?>:</strong> <?= htmlspecialchars($comment['comment']) ?></p>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <?php
                            // Deductions for this category
                            $subcatId = null;
                            foreach ($subcategoryScores as $s) { $subcatId = $s['subcategory_id']; break; }
                            $ded = $deductions[$subcatId] ?? null;
                            $subTotal = 0; foreach ($subcategoryScores as $s) { $subTotal += (float)$s['score']; }
                            $net = $subTotal - (float)($ded['total'] ?? 0);
                            ?>
                            <div class="comment-section">
                                <p><strong>Category Total:</strong> <?= number_format($subTotal, 2) ?></p>
                                <p><strong>Deductions:</strong> -<?= number_format((float)($ded['total'] ?? 0), 2) ?> <?= !empty($ded['comments']) ? '(' . htmlspecialchars($ded['comments']) . ')' : '' ?></p>
                                <p><strong>Net Total:</strong> <?= number_format($net, 2) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>

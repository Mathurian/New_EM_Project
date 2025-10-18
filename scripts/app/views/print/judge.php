<?php use function App\{url}; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Judge Scores: <?= htmlspecialchars($judge['name']) ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; color: #333; }
        .container { max-width: 800px; margin: 0 auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h1, h2, h3 { color: #0056b3; }
        .header-info { margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid #eee; }
        .header-info p { margin: 5px 0; }
        .subcategory-section { margin-bottom: 30px; border: 1px solid #ddd; padding: 15px; border-radius: 5px; }
        .subcategory-section h3 { margin-top: 0; color: #007bff; }
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
            .subcategory-section { page-break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="<?= url('admin/print-reports') ?>" class="print-button" style="background-color: #6c757d;">Back to Print Reports</a>
        <a href="#" onclick="window.print()" class="print-button">Print Report</a>

        <h1>Judge Score Report</h1>
        <div class="header-info">
            <p><strong>Judge:</strong> <?= htmlspecialchars($judge['name']) ?></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($judge['email'] ?? 'N/A') ?></p>
            <p><strong>Gender:</strong> <?= htmlspecialchars($judge['gender'] ?? 'N/A') ?></p>
            <p><strong>Report Generated:</strong> <?= date('Y-m-d H:i:s') ?></p>
        </div>

        <?php if (empty($scores)): ?>
            <p class="no-data">No scores recorded by this judge.</p>
        <?php else: ?>
            <?php
            // Group scores by contest and category
            $groupedScores = [];
            foreach ($scores as $score) {
                $groupedScores[$score['contest_name']][$score['category_name']][$score['subcategory_name']][] = $score;
            }
            ?>
            
            <?php foreach ($groupedScores as $contestName => $categories): ?>
                <h2><?= htmlspecialchars($contestName) ?></h2>
                
                <?php foreach ($categories as $categoryName => $subcategories): ?>
                    <h3><?= htmlspecialchars($categoryName) ?></h3>
                    
                    <?php foreach ($subcategories as $subcategoryName => $subcategoryScores): ?>
                        <div class="subcategory-section">
                            <h4><?= htmlspecialchars($subcategoryName) ?></h4>
                            
                            <table>
                                <thead>
                                    <tr>
                                        <th>Contestant</th>
                                        <th>Criterion</th>
                                        <th>Max Score</th>
                                        <th>Score</th>
                                        <th>Percentage</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($subcategoryScores as $score): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($score['contestant_name']) ?></td>
                                            <td><?= htmlspecialchars($score['criterion_name']) ?></td>
                                            <td><?= htmlspecialchars($score['max_score']) ?></td>
                                            <td><?= htmlspecialchars($score['score']) ?></td>
                                            <td><?= $score['max_score'] > 0 ? number_format(($score['score'] / $score['max_score']) * 100, 2) . '%' : 'N/A' ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            
                            <?php
                            // Get comments for this subcategory
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
                                        <p><strong><?= htmlspecialchars($comment['contestant_name']) ?>:</strong> <?= htmlspecialchars($comment['comment']) ?></p>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>

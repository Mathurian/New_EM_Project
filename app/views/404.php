<?php use function App\{url, home_url}; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found - Event Manager</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #333;
        }
        
        .container {
            text-align: center;
            background: white;
            padding: 3rem 2rem;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            max-width: 500px;
            width: 90%;
        }
        
        .error-code {
            font-size: 6rem;
            font-weight: bold;
            color: #667eea;
            margin: 0;
            line-height: 1;
        }
        
        .error-title {
            font-size: 1.5rem;
            color: #333;
            margin: 1rem 0;
            font-weight: 600;
        }
        
        .error-message {
            color: #666;
            margin: 1rem 0 2rem;
            line-height: 1.6;
        }
        
        .buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            cursor: pointer;
            display: inline-block;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5a6fd8;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: #f8f9fa;
            color: #333;
            border: 2px solid #e9ecef;
        }
        
        .btn-secondary:hover {
            background: #e9ecef;
            transform: translateY(-2px);
        }
        
        .icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.7;
        }
        
        @media (max-width: 600px) {
            .container {
                padding: 2rem 1rem;
            }
            
            .error-code {
                font-size: 4rem;
            }
            
            .buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .btn {
                width: 100%;
                max-width: 200px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">🔍</div>
        <h1 class="error-code">404</h1>
        <h2 class="error-title">Page Not Found</h2>
        <p class="error-message">
            Sorry, the page you're looking for doesn't exist or has been moved. 
            This might be due to an incorrect URL or the page being removed.
        </p>
        
        <div class="buttons">
            <a href="<?= home_url() ?>" class="btn btn-primary">🏠 Go Home</a>
            <a href="javascript:history.back()" class="btn btn-secondary">← Go Back</a>
        </div>
        
        <div style="margin-top: 2rem; padding-top: 1rem; border-top: 1px solid #eee; color: #999; font-size: 0.9rem;">
            <p>If you believe this is an error, please contact your system administrator.</p>
        </div>
    </div>
</body>
</html>

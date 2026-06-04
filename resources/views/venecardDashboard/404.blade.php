<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>page not found</title>
</head>
<body>
<div style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100vh; font-family: Arial, sans-serif;">
    <div style="text-align: center;">
        <h1 style="font-size: 120px; margin: 0; color: #e74c3c;">404</h1>
        <h2 style="font-size: 30px; margin: 0; color: #2c3e50;">Page Not Found</h2>
        <p style="font-size: 18px; color: #7f8c8d; margin: 20px 0;">The page you are looking for might have been removed, had its name changed, or is temporarily unavailable.</p>
        <a href="{{ route('dashboard') }}" style="display: inline-block; padding: 12px 30px; background-color: #3498db; color: white; text-decoration: none; border-radius: 5px; font-size: 16px; transition: background-color 0.3s;">
            Return to Dashboard
        </a>
    </div>
</div>

</body>
</html>
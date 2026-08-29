<?php
// ১. ব্রাউজারকে ৪০৪ স্ট্যাটাস হেডার পাঠানো
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 Not Found</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f8f9fa; color: #333; text-align: center; padding-top: 10%; }
        h1 { font-size: 50px; color: #dc3545; }
        p { font-size: 18px; color: #6c757d; }
    </style>
</head>
<body>

    <h1>404</h1>
    <h2>Object not found!</h2>
    <p>The requested URL was not found on this server.</p>

</body>
</html>
<?php
exit;
?>
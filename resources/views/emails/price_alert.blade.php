<!DOCTYPE html>
<html>
<head>
    <title>Price Alert</title>
</head>
<body>
    <h2>Price Change Alert</h2>
    <p>The price of the advert <strong>{{ $title }}</strong> has changed!</p>
    <p>Old Price: <del>{{ $oldPrice }} UAH</del></p>
    <p>New Price: <strong>{{ $newPrice }} UAH</strong></p>
    <p><a href="{{ $link }}">View Advert</a></p>
</body>
</html>

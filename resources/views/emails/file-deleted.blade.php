<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>File Deleted</title>
</head>
<body>
<h2>File Deleted Notification</h2>
<p>A file has been removed from File Storage.</p>
<dl>
    <dt>Filename</dt>
    <dd>{{ $filename }}</dd>
    <dt>Reason</dt>
    <dd>{{ ucfirst($reason) }}</dd>
    <dt>Deleted at</dt>
    <dd>{{ $deletedAt }}</dd>
</dl>
</body>
</html>


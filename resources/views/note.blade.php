<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>File Upload Example</title>
</head>
<body>

<form action="/addNote" method="post" enctype="multipart/form-data">
@csrf
  <label for="file">Select a file:</label>
  <input type="file" id="file" name="file" accept=".xlsx, .xls">
  <button type="submit">Upload</button>
</form>

</body>
</html>

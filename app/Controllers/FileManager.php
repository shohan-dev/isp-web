<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\Exceptions\PageNotFoundException;

class FileManager extends BaseController
{
    private $rootPath;

    public function __construct()
    {
        // Define root path, starting with ROOTPATH but limiting access for safety
        $this->rootPath = realpath(ROOTPATH);
        helper(['filesystem', 'url', 'form']);
    }

    private function checkAccess()
    {
        $role = getSession('user_role');
        /* Was ['admin', 'super_admin']. 'admin' is a TENANT admin — any paying
           customer of the platform — and this controller browses/downloads
           anything under ROOTPATH, which contains .env (zapi.jwtSecret,
           security.sessionBridgeKey, cron.secret) and the whole backend source.
           A tenant admin could therefore read the session-bridge key and forge
           an authenticated session for any user. This is a platform-owner tool;
           restrict it to super_admin. */
        if ($role !== 'super_admin') {
            throw PageNotFoundException::forPageNotFound('You do not have permission to access this page.');
        }
    }

    /**
     * Phase 0.6b: refuse writes to server-executable / secret files.
     * saveFile() can overwrite ANY existing file inside ROOTPATH, so without
     * this an admin (or a hijacked admin session) could plant a webshell by
     * editing an existing .php file, or rewrite .env/.htaccess. This blocks the
     * RCE-capable write path; read/download stay open since the role gate
     * already limits access to sAdmin/admin.
     */
    private function isBlockedForWrite($path)
    {
        static $blockedExt = [
            'php', 'phtml', 'php3', 'php4', 'php5', 'php7', 'php8', 'phps', 'pht', 'phar',
            'cgi', 'pl', 'py', 'sh', 'bash', 'so', 'asp', 'aspx', 'jsp', 'env', 'htaccess', 'htpasswd',
        ];
        $ext  = strtolower(pathinfo((string) $path, PATHINFO_EXTENSION));
        // Dotfiles (.env / .htaccess) have no real extension — match the basename too.
        $base = strtolower(basename((string) $path));

        return in_array($ext, $blockedExt, true)
            || in_array($base, ['.env', '.htaccess', '.htpasswd'], true);
    }

    /**
     * Phase 0.6b: refuse to read/download credential & key files.
     * The manager roots at ROOTPATH, so without this any 'super_admin' (the role gate
     * admits tenant admins, not just sAdmin) could view/download the global
     * .env — DB, gateway, JWT and SMTP secrets — or a TLS private key. Blocks
     * the secret-exfiltration read path; ordinary files stay readable.
     */
    private function isBlockedForRead($path)
    {
        static $secretExt = ['env', 'pem', 'key', 'crt', 'p12', 'pfx', 'htpasswd', 'htaccess'];
        $ext  = strtolower(pathinfo((string) $path, PATHINFO_EXTENSION));
        $base = strtolower(basename((string) $path));

        return in_array($ext, $secretExt, true)
            || in_array($base, ['.env', '.htaccess', '.htpasswd'], true);
    }

    private function getSafePath($path = '')
    {
        $path = $path ?? '';
        $fullPath = realpath($this->rootPath . DIRECTORY_SEPARATOR . $path);

        // Security: Prevent directory traversal (must be within rootPath)
        if (!$fullPath || strpos($fullPath, $this->rootPath) !== 0) {
            return $this->rootPath;
        }

        return $fullPath;
    }

    public function index()
    {
        $this->checkAccess();

        $relativePath = $this->request->getGet('path') ?? '';
        $fullPath = $this->getSafePath($relativePath);

        // If returned root because traversal attempt, reset relative path
        if ($fullPath === $this->rootPath && $relativePath !== '') {
            $relativePath = '';
        }

        $items = [];
        if (is_dir($fullPath)) {
            $files = scandir($fullPath);
            foreach ($files as $file) {
                if ($file === '.') continue;
                if ($file === '..' && ($relativePath === '' || $relativePath === '.')) continue;

                $itemPath = $fullPath . DIRECTORY_SEPARATOR . $file;
                $relItemPath = ltrim($relativePath . '/' . $file, '/');
                
                // If it's "..", it should point to dirname($relativePath)
                if ($file === '..') {
                    $relItemPath = dirname($relativePath);
                    if ($relItemPath === '.' || $relItemPath === '\\' || $relItemPath === '/') $relItemPath = '';
                }
                
                $relItemPath = str_replace('\\', '/', $relItemPath);

                $stat = stat($itemPath);
                $items[] = [
                    'name' => $file,
                    'is_dir' => is_dir($itemPath),
                    'size' => is_file($itemPath) ? $stat['size'] : 0,
                    'mtime' => $stat['mtime'],
                    'path' => $relItemPath,
                    'ext' => is_file($itemPath) ? pathinfo($file, PATHINFO_EXTENSION) : ''
                ];
            }
        }

        // Sort: directories first, then files
        usort($items, function($a, $b) {
            if ($a['name'] === '..') return -1;
            if ($b['name'] === '..') return 1;
            if ($a['is_dir'] && !$b['is_dir']) return -1;
            if (!$a['is_dir'] && $b['is_dir']) return 1;
            return strcasecmp($a['name'], $b['name']);
        });

        $data = [
            'title' => 'File Manager',
            'items' => $items,
            'currentPath' => $relativePath,
            'root' => $this->rootPath
        ];

        return view('file-manager/index', $data);
    }

    public function viewFile()
    {
        $this->checkAccess();

        $path = $this->request->getGet('path');
        $fullPath = $this->getSafePath($path);

        if (!is_file($fullPath)) {
            return requestResponse('error', 'File not found or access denied', 404);
        }

        // Phase 0.6b: never expose credential/key files through the editor.
        if ($this->isBlockedForRead($fullPath)) {
            return requestResponse('error', 'Viewing this file type is not allowed', 403);
        }

        $content = file_get_contents($fullPath);
        return requestResponse('success', [
            'content' => $content,
            'path' => $path,
            'name' => basename($fullPath),
            'extension' => pathinfo($fullPath, PATHINFO_EXTENSION)
        ], 200);
    }

    public function saveFile()
    {
        $this->checkAccess();

        $path = $this->request->getPost('path');
        $content = $this->request->getPost('content');
        $fullPath = $this->getSafePath($path);

        if (!is_file($fullPath)) {
            return requestResponse('error', 'Invalid file path or access denied', 403);
        }

        // Phase 0.6b: block writes to server-executable / secret files (RCE primitive).
        if ($this->isBlockedForWrite($fullPath)) {
            return requestResponse('error', 'Editing this file type is not allowed', 403);
        }

        if (file_put_contents($fullPath, $content) !== false) {
            return requestResponse('success', 'File updated successfully', 200);
        }

        return requestResponse('error', 'Failed to save file. Check permissions.', 500);
    }

    public function deleteItem()
    {
        $this->checkAccess();

        $path = $this->request->getPost('path');
        $fullPath = $this->getSafePath($path);

        if ($fullPath === $this->rootPath) {
            return requestResponse('error', 'Cannot delete root directory', 403);
        }

        if (is_dir($fullPath)) {
            if (delete_files($fullPath, true, false, true)) {
                @rmdir($fullPath);
                return requestResponse('success', 'Folder deleted successfully', 200);
            }
        } else {
            if (@unlink($fullPath)) {
                return requestResponse('success', 'File deleted successfully', 200);
            }
        }

        return requestResponse('error', 'Failed to delete item. Check permissions.', 500);
    }

    public function downloadFile()
    {
        $this->checkAccess();

        $path = $this->request->getGet('path');
        $fullPath = $this->getSafePath($path);

        if (!is_file($fullPath)) {
            return requestResponse('error', 'File not found or access denied', 404);
        }

        // Phase 0.6b: never expose credential/key files through download.
        if ($this->isBlockedForRead($fullPath)) {
            return requestResponse('error', 'Downloading this file type is not allowed', 403);
        }

        $fileName = basename($fullPath);
        $extension = pathinfo($fullPath, PATHINFO_EXTENSION);
        $mimeType = service('mimes')->guessTypeFromExtension($extension) ?: 'application/octet-stream';

        header('Content-Description: File Transfer');
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($fullPath));
        ob_clean();
        flush();
        readfile($fullPath);
        exit;
    }

    public function createFolder()
    {
        $this->checkAccess();

        $parent = $this->request->getPost('parent');
        $name = $this->request->getPost('name');
        
        $basePath = $this->getSafePath($parent);
        $fullPath = $basePath . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], '', $name);

        if (file_exists($fullPath)) {
            return requestResponse('error', 'Folder or file already exists', 400);
        }

        if (@mkdir($fullPath, 0755, true)) {
            return requestResponse('success', 'Folder created successfully', 200);
        }

        return requestResponse('error', 'Failed to create folder', 500);
    }
}

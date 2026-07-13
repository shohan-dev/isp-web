<?php

namespace App\Controllers;

use App\Models\MovieServersModel;
use App\Models\News_notice;
use App\Models\User;

class MovieNewsApiController extends BaseController
{
    protected $movieModel;
    protected $newsModel;
    protected $userModel;

    public function __construct()
    {
        $this->movieModel = new MovieServersModel();
        $this->newsModel = new News_notice();
        $this->userModel = new User();
    }

    private function getAdminId()
    {
        $sessionUserId = session()->get('user_id');
        if (!$sessionUserId) {
            return null;
        }

        // Resolve admin_id for the logged in user
        $user = $this->userModel->find($sessionUserId);
        if (!$user) {
            return null;
        }

        $role = is_object($user) ? ($user->role ?? null) : ($user['role'] ?? null);
        $adminId = is_object($user) ? ($user->admin_id ?? null) : ($user['admin_id'] ?? null);

        if ($role === 'admin') {
            return $sessionUserId;
        }

        return $adminId ? (int)$adminId : (int)$sessionUserId;
    }

    /* -------------------------
       MOVIE SERVERS ENDPOINTS
    ------------------------- */

    public function movieIndex()
    {
        $adminId = $this->getAdminId();
        if (!$adminId) {
            return $this->response->setJSON(['statusCode' => 401, 'success' => false, 'message' => 'Unauthorized'], 401);
        }

        $servers = $this->movieModel->getAllServers($adminId);
        return $this->response->setJSON([
            'statusCode' => 200,
            'success' => true,
            'data' => [
                'data' => $servers
            ]
        ]);
    }

    public function movieView($id)
    {
        $adminId = $this->getAdminId();
        if (!$adminId) {
            return $this->response->setJSON(['statusCode' => 401, 'success' => false, 'message' => 'Unauthorized'], 401);
        }

        $movie = $this->movieModel->getServerById($id);
        if (!$movie) {
            return $this->response->setJSON(['statusCode' => 404, 'success' => false, 'message' => 'Movie not found'], 404);
        }

        // Verify ownership
        $movieAdminId = is_object($movie) ? ($movie->admin_id ?? null) : ($movie['admin_id'] ?? null);
        if ($movieAdminId != $adminId) {
            return $this->response->setJSON(['statusCode' => 403, 'success' => false, 'message' => 'Forbidden'], 403);
        }

        return $this->response->setJSON([
            'statusCode' => 200,
            'success' => true,
            'data' => $movie
        ]);
    }

    public function movieAdd()
    {
        $adminId = $this->getAdminId();
        if (!$adminId) {
            return $this->response->setJSON(['statusCode' => 401, 'success' => false, 'message' => 'Unauthorized'], 401);
        }

        helper(['form']);
        $imageFile = $this->request->getFile('image');
        $imageName = null;

        if ($imageFile && $imageFile->isValid()) {
            $imageName = $imageFile->getRandomName();
            $uploadPath = FCPATH . 'assets/movies/';
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }
            $imageFile->move($uploadPath, $imageName);
        }

        $data = [
            'name' => $this->request->getPost('name'),
            'url' => $this->request->getPost('url'),
            'details' => $this->request->getPost('details'),
            'rating' => $this->request->getPost('rating'),
            'admin_id' => $adminId,
            'image' => $imageName,
        ];

        if ($this->movieModel->addServer($data)) {
            return $this->response->setJSON([
                'statusCode' => 200,
                'success' => true,
                'message' => 'Movie server added successfully',
                'data' => ['message' => 'Movie server added']
            ]);
        }

        return $this->response->setJSON(['statusCode' => 400, 'success' => false, 'message' => 'Failed to add movie server'], 400);
    }

    public function movieUpdate($id)
    {
        $adminId = $this->getAdminId();
        if (!$adminId) {
            return $this->response->setJSON(['statusCode' => 401, 'success' => false, 'message' => 'Unauthorized'], 401);
        }

        $server = $this->movieModel->getServerById($id);
        if (!$server) {
            return $this->response->setJSON(['statusCode' => 404, 'success' => false, 'message' => 'Movie not found'], 404);
        }

        // Verify ownership
        $movieAdminId = is_object($server) ? ($server->admin_id ?? null) : ($server['admin_id'] ?? null);
        if ($movieAdminId != $adminId) {
            return $this->response->setJSON(['statusCode' => 403, 'success' => false, 'message' => 'Forbidden'], 403);
        }

        helper(['form']);
        $imageFile = $this->request->getFile('image');
        $oldImage = is_object($server) ? ($server->image ?? null) : ($server['image'] ?? null);
        $imageName = $oldImage;

        if ($imageFile && $imageFile->isValid()) {
            if (!empty($oldImage) && file_exists(FCPATH . 'assets/movies/' . $oldImage)) {
                unlink(FCPATH . 'assets/movies/' . $oldImage);
            }
            $imageName = $imageFile->getRandomName();
            $uploadPath = FCPATH . 'assets/movies/';
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }
            $imageFile->move($uploadPath, $imageName);
        }

        $data = [
            'name' => $this->request->getPost('name'),
            'url' => $this->request->getPost('url'),
            'details' => $this->request->getPost('details'),
            'rating' => $this->request->getPost('rating'),
            'image' => $imageName,
        ];

        if ($this->movieModel->updateServer($id, $data)) {
            return $this->response->setJSON([
                'statusCode' => 200,
                'success' => true,
                'message' => 'Movie server updated successfully',
                'data' => ['message' => "Server ID {$id} updated"]
            ]);
        }

        return $this->response->setJSON(['statusCode' => 400, 'success' => false, 'message' => 'Failed to update movie server'], 400);
    }

    public function movieDelete($id)
    {
        $adminId = $this->getAdminId();
        if (!$adminId) {
            return $this->response->setJSON(['statusCode' => 401, 'success' => false, 'message' => 'Unauthorized'], 401);
        }

        $server = $this->movieModel->getServerById($id);
        if (!$server) {
            return $this->response->setJSON(['statusCode' => 404, 'success' => false, 'message' => 'Movie not found'], 404);
        }

        // Verify ownership
        $movieAdminId = is_object($server) ? ($server->admin_id ?? null) : ($server['admin_id'] ?? null);
        if ($movieAdminId != $adminId) {
            return $this->response->setJSON(['statusCode' => 403, 'success' => false, 'message' => 'Forbidden'], 403);
        }

        $imageName = is_object($server) ? ($server->image ?? null) : ($server['image'] ?? null);
        if (!empty($imageName) && file_exists(FCPATH . 'assets/movies/' . $imageName)) {
            unlink(FCPATH . 'assets/movies/' . $imageName);
        }

        if ($this->movieModel->deleteServer($id)) {
            return $this->response->setJSON([
                'statusCode' => 200,
                'success' => true,
                'message' => 'Movie server deleted successfully',
                'data' => ['message' => "Server ID {$id} deleted"]
            ]);
        }

        return $this->response->setJSON(['statusCode' => 400, 'success' => false, 'message' => 'Failed to delete movie server'], 400);
    }

    /* -------------------------
       NEWS & NOTICE ENDPOINTS
    ------------------------- */

    public function newsIndex()
    {
        $adminId = $this->getAdminId();
        if (!$adminId) {
            return $this->response->setJSON(['statusCode' => 401, 'success' => false, 'message' => 'Unauthorized'], 401);
        }

        $notices = $this->newsModel->getAllServers($adminId);
        return $this->response->setJSON([
            'statusCode' => 200,
            'success' => true,
            'data' => [
                'data' => $notices
            ]
        ]);
    }

    public function newsView($id)
    {
        $adminId = $this->getAdminId();
        if (!$adminId) {
            return $this->response->setJSON(['statusCode' => 401, 'success' => false, 'message' => 'Unauthorized'], 401);
        }

        $news = $this->newsModel->getServerById($id);
        if (!$news) {
            return $this->response->setJSON(['statusCode' => 404, 'success' => false, 'message' => 'News not found'], 404);
        }

        // Verify ownership
        $newsAdminId = is_object($news) ? ($news->admin_id ?? null) : ($news['admin_id'] ?? null);
        if ($newsAdminId != $adminId) {
            return $this->response->setJSON(['statusCode' => 403, 'success' => false, 'message' => 'Forbidden'], 403);
        }

        return $this->response->setJSON([
            'statusCode' => 200,
            'success' => true,
            'data' => $news
        ]);
    }

    public function newsAdd()
    {
        $adminId = $this->getAdminId();
        if (!$adminId) {
            return $this->response->setJSON(['statusCode' => 401, 'success' => false, 'message' => 'Unauthorized'], 401);
        }

        helper(['form']);
        $imageFile = $this->request->getFile('image');
        $imageName = null;

        if ($imageFile && $imageFile->isValid()) {
            $imageName = $imageFile->getRandomName();
            $uploadPath = FCPATH . 'assets/news/';
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }
            $imageFile->move($uploadPath, $imageName);
        }

        $data = [
            'name' => $this->request->getPost('name'),
            'url' => $this->request->getPost('url'),
            'details' => $this->request->getPost('details'),
            'opened' => 'false',
            'admin_id' => $adminId,
            'image' => $imageName,
        ];

        if ($this->newsModel->addServer($data)) {
            return $this->response->setJSON([
                'statusCode' => 200,
                'success' => true,
                'message' => 'News notice added successfully',
                'data' => ['message' => 'news server added']
            ]);
        }

        return $this->response->setJSON(['statusCode' => 400, 'success' => false, 'message' => 'Failed to add news notice'], 400);
    }

    public function newsUpdate($id)
    {
        $adminId = $this->getAdminId();
        if (!$adminId) {
            return $this->response->setJSON(['statusCode' => 401, 'success' => false, 'message' => 'Unauthorized'], 401);
        }

        $server = $this->newsModel->getServerById($id);
        if (!$server) {
            return $this->response->setJSON(['statusCode' => 404, 'success' => false, 'message' => 'News not found'], 404);
        }

        // Verify ownership
        $newsAdminId = is_object($server) ? ($server->admin_id ?? null) : ($server['admin_id'] ?? null);
        if ($newsAdminId != $adminId) {
            return $this->response->setJSON(['statusCode' => 403, 'success' => false, 'message' => 'Forbidden'], 403);
        }

        helper(['form']);
        $imageFile = $this->request->getFile('image');
        $oldImage = is_object($server) ? ($server->image ?? null) : ($server['image'] ?? null);
        $imageName = $oldImage;

        if ($imageFile && $imageFile->isValid()) {
            if (!empty($oldImage) && file_exists(FCPATH . 'assets/news/' . $oldImage)) {
                unlink(FCPATH . 'assets/news/' . $oldImage);
            }
            $imageName = $imageFile->getRandomName();
            $uploadPath = FCPATH . 'assets/news/';
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }
            $imageFile->move($uploadPath, $imageName);
        }

        $data = [
            'name' => $this->request->getPost('name'),
            'url' => $this->request->getPost('url'),
            'details' => $this->request->getPost('details'),
            'image' => $imageName,
        ];

        if ($this->newsModel->updateServer($id, $data)) {
            return $this->response->setJSON([
                'statusCode' => 200,
                'success' => true,
                'message' => 'News notice updated successfully',
                'data' => ['message' => "News ID {$id} updated"]
            ]);
        }

        return $this->response->setJSON(['statusCode' => 400, 'success' => false, 'message' => 'Failed to update news notice'], 400);
    }

    public function newsDelete($id)
    {
        $adminId = $this->getAdminId();
        if (!$adminId) {
            return $this->response->setJSON(['statusCode' => 401, 'success' => false, 'message' => 'Unauthorized'], 401);
        }

        $server = $this->newsModel->getServerById($id);
        if (!$server) {
            return $this->response->setJSON(['statusCode' => 404, 'success' => false, 'message' => 'News not found'], 404);
        }

        // Verify ownership
        $newsAdminId = is_object($server) ? ($server->admin_id ?? null) : ($server['admin_id'] ?? null);
        if ($newsAdminId != $adminId) {
            return $this->response->setJSON(['statusCode' => 403, 'success' => false, 'message' => 'Forbidden'], 403);
        }

        $imageName = is_object($server) ? ($server->image ?? null) : ($server['image'] ?? null);
        if (!empty($imageName) && file_exists(FCPATH . 'assets/news/' . $imageName)) {
            unlink(FCPATH . 'assets/news/' . $imageName);
        }

        if ($this->newsModel->deleteServer($id)) {
            return $this->response->setJSON([
                'statusCode' => 200,
                'success' => true,
                'message' => 'News notice deleted successfully',
                'data' => ['message' => "News ID {$id} deleted"]
            ]);
        }

        return $this->response->setJSON(['statusCode' => 400, 'success' => false, 'message' => 'Failed to delete news notice'], 400);
    }
}

<?php

namespace App\Controllers;

use App\Models\ProductShowcaseCategory;
use App\Models\ProductShowcaseImage;

/**
 * Super-admin management of the landing page "Product Showcase" website/mobile
 * screenshot galleries. Every mutating action is filtered role:super_admin at
 * the route (Routes.php) AND re-checked in-method here, matching
 * Admin::savePricingSettings() / collectPlanTypeFields().
 */
class ProductShowcase extends BaseController
{
    protected ProductShowcaseCategory $categories;
    protected ProductShowcaseImage $images;

    public function __construct()
    {
        $this->categories = new ProductShowcaseCategory();
        $this->images     = new ProductShowcaseImage();
    }

    /**
     * requestResponse() sends the JSON response directly and returns null
     * (see utility_helper.php), so the deny check must stay a plain boolean
     * and the caller must send+return the response itself — capturing
     * requestResponse()'s return value here would always be null, including
     * on denial, and the guard would silently fall through.
     */
    private function isNotSuperAdmin(): bool
    {
        return getSession('user_role') !== 'super_admin';
    }

    public function index()
    {
        if (getSession('user_role') !== 'super_admin') {
            return redirect()->to(route_to('route.dashboard'))->with('error', 'Access denied');
        }

        // Same graceful-degrade discipline as AuthController::home()'s
        // landingShowcasePayload() call — a DB hiccup here (e.g. pending
        // migrations on a fresh deploy) should show an empty state with a
        // clear message, not a raw mysqli stack trace to a logged-in admin.
        $website = [];
        $mobile  = [];
        $loadError = null;

        try {
            $website = $this->categories->listForTarget('website');
            $mobile  = $this->categories->listForTarget('mobile');

            foreach ($website as &$category) {
                $category['images'] = $this->images->forCategory((int) $category['id']);
            }
            unset($category);
            foreach ($mobile as &$category) {
                $category['images'] = $this->images->forCategory((int) $category['id']);
            }
            unset($category);
        } catch (\Throwable $e) {
            log_message('error', 'ProductShowcase::index() failed to load categories: ' . $e->getMessage());
            $website = [];
            $mobile  = [];
            $loadError = 'Could not load Product Showcase data. If this is a fresh install, run "php spark migrate" then reload this page.';
        }

        return view('SecondAdmin/product_showcase', [
            'title'          => 'Product Showcase',
            'websiteCategories' => $website,
            'mobileCategories'  => $mobile,
            'loadError'         => $loadError,
        ]);
    }

    public function storeCategory()
    {
        if ($this->isNotSuperAdmin()) {
            return requestResponse('error', 'Access denied.', 403);
        }

        $this->validate([
            'name' => 'required|min_length[2]|max_length[150]',
            'target' => 'required|in_list[website,mobile]',
            'sort_order' => 'permit_empty|integer',
        ]);

        if (!$this->validation->run()) {
            return requestResponse('validation-error', $this->validation->getErrors(), 400);
        }

        $name   = trim((string) $this->request->getPost('name'));
        $target = (string) $this->request->getPost('target');
        $slug   = $this->generateUniqueSlug($name);

        $data = [
            'name'       => $name,
            'slug'       => $slug,
            'target'     => $target,
            'bullets'    => $this->encodeBullets((string) $this->request->getPost('bullets')),
            'sort_order' => (int) ($this->request->getPost('sort_order') ?? 0),
            'status'     => 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $id = $this->categories->insert($data, true);
        if (!$id) {
            return requestResponse('error', 'Failed to create category.', 500);
        }

        $this->ensureCategoryStorage($slug);

        $category = $this->categories->find($id);

        return requestResponse('success', [
            'msg'      => 'Category created successfully.',
            'category' => $category,
        ], 200);
    }

    public function updateCategory($id)
    {
        if ($this->isNotSuperAdmin()) {
            return requestResponse('error', 'Access denied.', 403);
        }

        $id = (int) $id;
        $category = $this->categories->find($id);
        if (!$category) {
            return requestResponse('error', 'Category not found.', 404);
        }

        $this->validate([
            'name' => 'required|min_length[2]|max_length[150]',
            'target' => 'required|in_list[website,mobile]',
            'sort_order' => 'permit_empty|integer',
        ]);

        if (!$this->validation->run()) {
            return requestResponse('validation-error', $this->validation->getErrors(), 400);
        }

        // slug/folder are immutable after creation — editing the name must not
        // silently break existing image paths.
        $data = [
            'name'       => trim((string) $this->request->getPost('name')),
            'target'     => (string) $this->request->getPost('target'),
            'bullets'    => $this->encodeBullets((string) $this->request->getPost('bullets')),
            'sort_order' => (int) ($this->request->getPost('sort_order') ?? 0),
            'status'     => in_array($this->request->getPost('status'), ['active', 'inactive'], true)
                ? $this->request->getPost('status')
                : $category['status'],
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if (!$this->categories->update($id, $data)) {
            return requestResponse('error', 'Failed to update category.', 500);
        }

        return requestResponse('success', [
            'msg'      => 'Category updated successfully.',
            'category' => $this->categories->find($id),
        ], 200);
    }

    public function deleteCategory($id)
    {
        if ($this->isNotSuperAdmin()) {
            return requestResponse('error', 'Access denied.', 403);
        }

        $id = (int) $id;
        $category = $this->categories->find($id);
        if (!$category) {
            return requestResponse('error', 'Category not found.', 404);
        }

        if (!$this->categories->deleteWithImages($id)) {
            return requestResponse('error', 'Failed to delete category.', 500);
        }

        $dir = FCPATH . 'assets/img/product_images/' . $category['slug'];
        if (is_dir($dir) && count(glob($dir . '/*') ?: []) === 0) {
            @rmdir($dir);
        }

        return requestResponse('success', 'Category deleted successfully.', 200);
    }

    public function storeImage($categoryId)
    {
        if ($this->isNotSuperAdmin()) {
            return requestResponse('error', 'Access denied.', 403);
        }

        $categoryId = (int) $categoryId;
        $category = $this->categories->find($categoryId);
        if (!$category) {
            return requestResponse('error', 'Category not found.', 404);
        }

        $this->validate([
            'image' => [
                'rules' => 'uploaded[image]|max_size[image,2048]|is_image[image]|ext_in[image,png,jpg,jpeg,gif,webp]',
                'errors' => [
                    'uploaded' => 'Please upload an image',
                    'max_size' => 'Image size is too large (max 2MB)',
                    'is_image' => 'The file must be an image',
                    'ext_in'   => 'Invalid extension. Allowed: png, jpg, jpeg, gif, webp',
                ],
            ],
        ]);

        if (!$this->validation->run()) {
            return requestResponse('validation-error', $this->validation->getErrors(), 400);
        }

        $file = $this->request->getFile('image');
        if (!$file || !$file->isValid() || $file->hasMoved()) {
            return requestResponse('error', 'Invalid file upload.', 400);
        }

        $dir = $this->ensureCategoryStorage($category['slug']);
        $filename = $file->getRandomName();
        $file->move($dir, $filename);

        $relativePath = 'assets/img/product_images/' . $category['slug'] . '/' . $filename;

        $data = [
            'category_id' => $categoryId,
            'image_path'  => $relativePath,
            'caption'     => trim((string) $this->request->getPost('caption')) ?: null,
            'sort_order'  => $this->images->nextSortOrder($categoryId),
            'created_at'  => date('Y-m-d H:i:s'),
        ];

        $imageId = $this->images->insert($data, true);
        if (!$imageId) {
            return requestResponse('error', 'Failed to save image.', 500);
        }

        return requestResponse('success', [
            'msg' => 'Image uploaded successfully.',
            'image' => [
                'id'         => (int) $imageId,
                'url'        => base_url($relativePath),
                'caption'    => $data['caption'] ?? '',
                'sort_order' => $data['sort_order'],
            ],
        ], 200);
    }

    public function deleteImage($id)
    {
        if ($this->isNotSuperAdmin()) {
            return requestResponse('error', 'Access denied.', 403);
        }

        $id = (int) $id;
        if (!$this->images->find($id)) {
            return requestResponse('error', 'Image not found.', 404);
        }

        if (!$this->images->deleteAndUnlink($id)) {
            return requestResponse('error', 'Failed to delete image.', 500);
        }

        return requestResponse('success', 'Image deleted successfully.', 200);
    }

    public function reorderImages()
    {
        if ($this->isNotSuperAdmin()) {
            return requestResponse('error', 'Access denied.', 403);
        }

        // order is a map of image id => sort_order, matching the admin UI's
        // per-image numeric sort_order inputs (jQuery serializes
        // order[id]=value into this associative array automatically).
        $order = $this->request->getPost('order');
        if (!is_array($order) || empty($order)) {
            return requestResponse('validation-error', ['order' => 'No order data received.'], 400);
        }

        $idToSortOrder = [];
        foreach ($order as $imageId => $sortOrder) {
            $idToSortOrder[(int) $imageId] = (int) $sortOrder;
        }

        $this->images->reorder($idToSortOrder);

        return requestResponse('success', 'Order saved successfully.', 200);
    }

    /**
     * Textarea, one bullet per line, trimmed, empty lines dropped, JSON-encoded.
     */
    private function encodeBullets(string $raw): string
    {
        $lines = preg_split('/\r\n|\r|\n/', $raw) ?: [];
        $bullets = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line !== '') {
                $bullets[] = $line;
            }
        }

        return json_encode($bullets);
    }

    /**
     * lowercase, non-alnum to hyphens, collapse repeats, trim hyphens; appends
     * -2/-3/... on collision.
     */
    private function generateUniqueSlug(string $name): string
    {
        $base = strtolower(trim($name));
        $base = preg_replace('/[^a-z0-9]+/', '-', $base) ?? '';
        $base = trim($base, '-');
        if ($base === '') {
            $base = 'category';
        }

        $slug = $base;
        $suffix = 2;
        while ($this->categories->slugExists($slug)) {
            $slug = $base . '-' . $suffix;
            $suffix++;
        }

        return $slug;
    }

    /**
     * Creates the per-category upload folder on demand, mirroring
     * ensureTenantStorage()'s mkdir 0755 recursive pattern.
     */
    private function ensureCategoryStorage(string $slug): string
    {
        $dir = FCPATH . 'assets/img/product_images/' . $slug;
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        return $dir;
    }
}

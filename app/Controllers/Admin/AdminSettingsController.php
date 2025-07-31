<?php
namespace App\Controllers\Admin;

use App\Core\Response;
use App\Models\OrderStatus;
use App\Core\Request;

class AdminSettingsController
{
    private OrderStatus $statusModel;
    private Request $request;

    public function __construct()
    {
        $this->request = new Request();
        $this->statusModel = new OrderStatus();
    }

    /**
     * GET /api/admin/order-statuses
     * List all statuses
     */
    public function allStatuses(): Response
    {
        $statuses = $this->statusModel->all();
        return Response::success('All order statuses', $statuses);
    }

    
    /**
     * POST /api/admin/order-statuses
     * Body: { "key":"archived", "label":"Archived" }
     */
    public function createStatus(): Response
    {
        $body = $this->request->all();
        $key   = $body['key']   ?? '';
        $label = $body['label'] ?? '';
        if (!$key || !$label) {
            return Response::error('Both key and label are required', [], 422);
        }
        // 1) Check uniqueness
        if ($this->statusModel->findByKey($key)) {
            return Response::error('Status key already exists', [], 409);
        }
        // 2) Insert
        $result = $this->statusModel->create($key, $label);
        if (!$result) {
            return Response::error('Failed to create status', [], 500);
        } 

        return Response::success('Status created', [$result], 201);
    }

    /**
     * GET/PUT/DELETE /api/admin/settings/{key}
     *   - GET   /settings/{key} → fetch one status
     *   - PUT   /settings/{key} → update label
     */
    public function getStatus(string $key): Response
    {
        $status = $this->statusModel->findByKey($key);
        if (!$status) {
            return Response::error('Status not found', [], 404);
        }
        return Response::success('Status detail', $status);
    }

    public function updateStatus(string $key): Response
    {
        $body = $this->request->all();
        $label = $body['label'] ?? '';
        if (!$label) {
            return Response::error('Label is required', [], 422);
        }
        $status = $this->statusModel->findByKey($key);
        if (!$status) {
            return Response::error('Status not found', [], 404);
        }
        $result = $this->statusModel->update($status['id'], $label);
        if (!$result) {
            return Response::error('Failed to update', [], 500);
        }

        return Response::success('Status updated');
    }

    public function deleteStatus(string $key): Response
    {
        $status = $this->statusModel->findByKey($key);
        if (!$status) {
            return Response::error('Status not found', [], 404);
        }
        $result = $this->statusModel->delete($status['id']);
        if (!$result) {
            return Response::error('Failed to delete', [], 500);
        }
        return Response::success('Status deleted');
    }
}
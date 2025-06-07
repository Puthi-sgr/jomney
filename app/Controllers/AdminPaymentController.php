<?php
namespace App\Controllers\Admin;

use App\Core\Response;
use App\Models\Payment;

class AdminPaymentController{

    private Payment $paymentModel;

    public function __construct()
    {
        $this->paymentModel = new Payment();
    }

    /**
     * GET /api/admin/payments
     * List all payments
     */
    public function index(): void
    {
        $payments = $this->paymentModel->all();
        Response::success('All payments', $payments);
    }

    /**
     * GET /api/admin/payments/{id}
     * View a single payment by ID
     */
    public function show(int $id): void
    {
        $payment = $this->paymentModel->find($id);
        if (!$payment) {
            Response::error('Payment not found', [], 404);
            return;
        }
        Response::success('Payment details', $payment);
        return;
    }
}
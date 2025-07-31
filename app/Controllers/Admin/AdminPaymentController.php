<?php
namespace App\Controllers\Admin;

use App\Core\Response;
use App\Models\Payment;
use App\Core\Request;

class AdminPaymentController{

    private Payment $paymentModel;
    private Request $request;

    public function __construct()
    {
        $this->request = new Request();
        $this->paymentModel = new Payment();
    }

    /**
     * GET /api/admin/payments
     * List all payments
     */
    public function index(): Response
    {
        $payments = $this->paymentModel->all();
        return Response::success('All payments', $payments);
    }

    /**
     * GET /api/admin/payments/{id}
     * View a single payment by ID
     */
    public function show(int $id): Response
    {
        $payment = $this->paymentModel->find($id);
        if (!$payment) {
            return Response::error('Payment not found', [], 404);
        }
        return Response::success('Payment details', $payment);
    }
}